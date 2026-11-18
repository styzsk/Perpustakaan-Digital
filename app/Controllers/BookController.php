<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AppException;
use App\Core\Validator;
use App\Models\BookModel;
use App\Models\LoanModel;
use App\Models\SettingsModel;

final class BookController extends BaseController
{
    protected string $pageKey = 'books';

    protected function actions(): array
    {
        return [
            'index' => 'index',
            'show' => 'show',
            'save' => 'save',
            'delete' => 'delete',
            'export' => 'export',
        ];
    }

    public function index(): void
    {
        $filters = [
            'q' => (string) input('q', ''),
            'kategori' => (string) input('kategori', ''),
            'ketersediaan' => (string) input('ketersediaan', ''),
            'sort' => (string) input('sort', 'judul'),
            'dir' => (string) input('dir', 'asc'),
        ];

        $this->render('books/index', [
            'pageTitle' => 'Katalog Buku',
            'filters' => $filters,
            'books' => BookModel::paginate($filters, $this->pageNumber(), $this->perPage()),
            'categories' => BookModel::categories(),
            'stats' => BookModel::stats(),
            'defaultLimit' => SettingsModel::defaultLimit(),
            'showBookModal' => true,
        ]);
    }

    public function show(): void
    {
        $id = id_input();
        $book = $id > 0 ? BookModel::find($id) : null;

        if (!$book) {
            flash('error', 'Buku tidak ditemukan.');
            redirect(url('books'));
        }

        $this->render('books/show', [
            'pageTitle' => 'Detail Buku',
            'book' => $book,
            'loans' => LoanModel::loansForBook($id),
            'categories' => BookModel::categories(),
            'showBookModal' => true,
        ]);
    }

    public function save(): void
    {
        $this->guardCsrf();

        $id = id_input();
        $existing = $id > 0 ? BookModel::find($id) : null;

        if ($id > 0 && !$existing) {
            flash('error', 'Buku yang akan diubah tidak ditemukan.');
            redirect(url('books'));
        }

        $data = [
            'judul' => (string) input('judul', ''),
            'pengarang' => (string) input('pengarang', ''),
            'kategori' => (string) input('kategori', ''),
            'isbn' => (string) input('isbn', ''),
            'penerbit' => (string) input('penerbit', ''),
            'tahun_terbit' => (string) input('tahun_terbit', ''),
            'sinopsis' => (string) input('sinopsis', ''),
            'jumlah_eksemplar' => (string) input('jumlah_eksemplar', '1'),
        ];

        $validator = $this->validate($data);
        if ($validator->fails()) {
            $this->failValidation($validator, $data + ['id' => $id], 'book');
        }

        try {
            if ($existing) {
                BookModel::update($id, $data);
                audit_trail('update', 'buku', $id, 'Mengubah buku: ' . $data['judul']);
                flash('success', 'Perubahan buku "' . $data['judul'] . '" tersimpan.');
            } else {
                $id = BookModel::create($data);
                audit_trail('create', 'buku', $id, 'Menambah buku: ' . $data['judul']);
                flash('success', 'Buku "' . $data['judul'] . '" berhasil ditambahkan.');
            }
        } catch (AppException $exception) {
            flash('error', $exception->getMessage());
            redirect(url('books'));
        } catch (\Throwable $exception) {
            app_log('Gagal menyimpan buku: ' . $exception->getMessage());
            flash('error', 'Buku gagal disimpan. Silakan coba lagi.');
            redirect(url('books'));
        }

        redirect(url('books', ['action' => 'show', 'id' => $id]));
    }

    public function delete(): void
    {
        $this->guardCsrf();

        $id = id_input();
        $book = $id > 0 ? BookModel::find($id) : null;
        if (!$book) {
            flash('error', 'Buku tidak ditemukan.');
            redirect(url('books'));
        }

        if (BookModel::hasLoans($id)) {
            flash('error', 'Buku "' . $book['judul'] . '" tidak dapat dihapus karena memiliki riwayat peminjaman.');
            redirect(url('books'));
        }

        try {
            BookModel::delete($id);
        } catch (\Throwable $exception) {
            app_log('Gagal menghapus buku #' . $id . ': ' . $exception->getMessage());
            flash('error', 'Buku "' . $book['judul'] . '" tidak dapat dihapus. Periksa relasi datanya.');
            redirect(url('books'));
        }

        audit_trail('delete', 'buku', $id, 'Menghapus buku: ' . $book['judul']);
        flash('success', 'Buku "' . $book['judul'] . '" dihapus dari katalog.');
        redirect(url('books'));
    }

    public function export(): void
    {
        $rows = [];
        foreach (BookModel::allForExport() as $book) {
            $rows[] = [
                $book['id_buku'],
                $book['judul'],
                $book['pengarang'],
                $book['kategori'],
                $book['isbn'],
                $book['penerbit'],
                $book['tahun_terbit'],
                $book['jumlah_eksemplar'],
                $book['stok_tersedia'],
                (int) $book['dipinjam'],
                (int) $book['total_pinjam'],
            ];
        }

        send_csv('bukuhub-buku-' . today() . '.csv', [
            'ID', 'Judul', 'Pengarang', 'Kategori', 'ISBN', 'Penerbit', 'Tahun',
            'Total Eksemplar', 'Stok Tersedia', 'Sedang Dipinjam', 'Total Peminjaman',
        ], $rows);
    }

    private function validate(array $data): Validator
    {
        $validator = Validator::make($data)
            ->required('judul', 'Judul')
            ->maxLength('judul', 'Judul', 180)
            ->required('pengarang', 'Pengarang')
            ->maxLength('pengarang', 'Pengarang', 120)
            ->required('kategori', 'Kategori')
            ->maxLength('kategori', 'Kategori', 40)
            ->maxLength('isbn', 'ISBN', 30)
            ->maxLength('penerbit', 'Penerbit', 120)
            ->maxLength('sinopsis', 'Sinopsis', 2000)
            ->integer('jumlah_eksemplar', 'Jumlah eksemplar', 1, 999);

        if ($data['tahun_terbit'] !== '') {
            $validator->integer('tahun_terbit', 'Tahun terbit', 1900, (int) date('Y') + 1);
        }

        return $validator;
    }
}
