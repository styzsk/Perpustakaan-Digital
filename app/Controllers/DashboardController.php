<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\FineModel;
use App\Models\LoanModel;
use App\Models\MemberModel;
use App\Models\ReportModel;
use App\Models\SettingsModel;

final class DashboardController extends BaseController
{
    protected string $pageKey = 'dashboard';

    public function index(): void
    {
        $tab = (string) input('loan_filter', 'all');
        if (!in_array($tab, ['all', 'active', 'overdue', 'returned'], true)) {
            $tab = 'all';
        }

        $series = ReportModel::monthlySeries(12);
        $from = date('Y-m-d', strtotime('first day of -11 months'));
        $categories = ReportModel::categoryStats($from, today());

        $peak = ['label' => '—', 'value' => 0];
        $total = 0;
        foreach ($series as $month) {
            $total += $month['current'];
            if ($month['current'] > $peak['value']) {
                $peak = ['label' => $month['label'] . ' ' . $month['year'], 'value' => $month['current']];
            }
        }

        $this->render('dashboard/index', [
            'pageTitle' => 'Ringkasan Perpustakaan',
            'bookStats' => BookModel::stats(),
            'memberStats' => MemberModel::stats(),
            'loanStats' => LoanModel::stats(),
            'fineStats' => FineModel::stats(),
            'series' => $series,
            'categories' => $categories,
            'categoryTotal' => array_sum(array_column($categories, 'total')),
            'peak' => $peak,
            'average' => (int) round($total / max(1, count($series))),
            'popular' => BookModel::popular(4),
            'recent' => LoanModel::paginate(['tab' => $tab], 1, 6),
            'tabCounts' => LoanModel::countsForTabs(),
            'activeTab' => $tab,
            'recentFines' => FineModel::recentPayments(4),
            'categoriesList' => BookModel::categories(),
            'defaultLimit' => SettingsModel::defaultLimit(),
            'members' => MemberModel::activeForSelect(),
            'books' => BookModel::availableForSelect(),
            'duration' => SettingsModel::loanDuration(),
            'tariff' => SettingsModel::tariff(),
            'showBookModal' => true,
            'showMemberModal' => true,
            'showLoanModal' => true,
        ]);
    }
}
