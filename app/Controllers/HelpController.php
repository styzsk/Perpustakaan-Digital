<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\FineModel;
use App\Models\LoanModel;
use App\Models\MemberModel;
use App\Models\SettingsModel;

final class HelpController extends BaseController
{
    protected string $pageKey = 'help';

    protected function actions(): array
    {
        return ['index' => 'index'];
    }

    public function index(): void
    {
        $this->render('help/index', [
            'pageTitle' => 'Pusat Bantuan',
            'bookStats' => BookModel::stats(),
            'memberStats' => MemberModel::stats(),
            'loanStats' => LoanModel::stats(),
            'fineStats' => FineModel::stats(),
            'duration' => SettingsModel::loanDuration(),
            'tariff' => SettingsModel::tariff(),
        ]);
    }
}
