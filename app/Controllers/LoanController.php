<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Validator;
use App\Models\BookModel;
use App\Models\LoanModel;
use App\Models\MemberModel;
use App\Models\SettingsModel;

final class LoanController extends BaseController
{
    protected string $pageKey = 'borrowing';
    protected array $actionAliases = ['return' => 'returnBook'];

    public function index(): void
    {
        $filters = [
            'q' => (string) input('q', ''),
            'tab' => (string) input('tab', 'all'),
        ];

        $this->render('borrowing/index', [
            'pageTitle' => 'Peminjaman & Pengembalian',
            'filters' => $filters,
            'loans' => LoanModel::paginate($filters, $this->pageNumber(), $this->perPage()),
            'tabs' => LoanModel::countsForTabs(),
            'stats' => LoanModel::stats(),
            'members' => MemberModel::activeForSelect(),
            'books' => BookModel::availableForSelect(),
            'duration' => SettingsModel::loanDuration(),
            'tariff' => SettingsModel::tariff(),
            'prefillBook' => int_input('book', 0),
            'prefillMember' => int_input('member', 0),
            'showLoanModal' => true,
        ]);
    }

    public function store(): void
    {
        $this->guardCsrf();

        $data = [
            'id_anggota' => (string) input('id_anggota', ''),
            'id_buku' => (string) input('id_buku', ''),
            'tgl_pinjam' => (string) input('tgl_pinjam', today()),
        ];

        $validator = Validator::make($data)
            ->required('id_anggota', 'Anggota')
            ->required('id_buku', 'Buku')
            ->required('tgl_pinjam', 'Tanggal pinjam')
            ->date('tgl_pinjam', 'Tanggal pinjam');

        $memberId = (int) $data['id_anggota'];
        $bookId = (int) $data['id_buku'];

        if ($memberId <= 0) {
            $validator->addError('id_anggota', 'Pilih anggota yang valid.');
        }
        if ($bookId <= 0) {
            $validator->addError('id_buku', 'Pilih buku yang valid.');
        }

        if ($data['tgl_pinjam'] !== '' && !$validator->fails()) {
            $today = today();
            $minDate = date('Y-m-d', strtotime('-30 days'));
            if ($data['tgl_pinjam'] > $today) {
                $validator->addError('tgl_pinjam', 'Tanggal pinjam tidak boleh melebihi hari ini.');
            } elseif ($data['tgl_pinjam'] < $minDate) {
                $validator->addError('tgl_pinjam', 'Tanggal pinjam maksimal 30 hari ke belakang.');
            }
        }

        if ($validator->fails()) {
            $this->failValidation($validator, $data, 'loan');
        }

        try {
            $loanId = LoanModel::createLoan($memberId, $bookId, $data['tgl_pinjam']);
            $loan = LoanModel::find($loanId);
            audit_trail(
                'create',
                'peminjaman',
                $loanId,
                'Peminjaman buku #' . $bookId . ' oleh anggota #' . $memberId
            );
            flash(
                'success',
                'Peminjaman dibuat. Jatuh tempo ' . format_date($loan['tgl_kembali_rencana'] ?? '') . '.'
            );
            redirect(url('borrowing', ['tab' => 'active']));
        } catch (AppException $exception) {
            flash('error', $exception->getMessage());
            redirect_back(url('borrowing'));
        } catch (\Throwable $exception) {
            app_log('Gagal membuat peminjaman: ' . $exception->getMessage());
            flash('error', 'Peminjaman gagal diproses. Silakan coba lagi.');
            redirect_back(url('borrowing'));
        }
    }

    public function returnBook(): void
    {
        $this->guardCsrf();

        $id = id_input();
        if ($id <= 0) {
            flash('error', 'Transaksi tidak valid.');
            redirect(url('borrowing'));
        }

        try {
            $result = LoanModel::returnLoan($id);
            audit_trail(
                'return',
                'peminjaman',
                $id,
                'Pengembalian buku; keterlambatan ' . $result['late_days'] . ' hari; denda ' . $result['fine']
            );
            if ($result['late_days'] > 0) {
                flash(
                    'warning',
                    'Buku dikembalikan terlambat ' . $result['late_days'] . ' hari. Denda '
                    . money($result['fine']) . ' dicatat pada menu Denda.'
                );
            } else {
                flash('success', 'Buku berhasil dikembalikan tepat waktu.');
            }
            redirect(url('borrowing', ['tab' => 'returned']));
        } catch (AppException $exception) {
            flash('error', $exception->getMessage());
            redirect(url('borrowing'));
        } catch (\Throwable $exception) {
            app_log('Gagal memproses pengembalian #' . $id . ': ' . $exception->getMessage());
            flash('error', 'Pengembalian gagal diproses. Silakan coba lagi.');
            redirect(url('borrowing'));
        }
    }

    public function export(): void
    {
        $rows = [];
        foreach (LoanModel::allForExport() as $loan) {
            $state = loan_state($loan);
            $rows[] = [
                $loan['id_peminjaman'],
                $loan['judul'],
                $loan['pengarang'],
                $loan['anggota_nama'],
                $loan['nim_nik'],
                $loan['tgl_pinjam'],
                $loan['tgl_kembali_rencana'],
                $loan['tgl_kembali_real'],
                $state['label'],
                $state['late'],
                $loan['total_denda'] !== null ? (int) $loan['total_denda'] : projected_fine($loan),
                $loan['status_denda'] ?? ($state['key'] === 'overdue' ? 'belum_lunas' : ''),
            ];
        }

        send_csv('bukuhub-peminjaman-' . today() . '.csv', [
            'ID', 'Buku', 'Pengarang', 'Anggota', 'NIM/NIK', 'Tgl Pinjam', 'Jatuh Tempo',
            'Tgl Kembali', 'Status', 'Hari Terlambat', 'Denda', 'Status Denda',
        ], $rows);
    }
}
