<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard.index', [
            'statistics' => [
                [
                    'label' => 'Total Personel Aktif',
                    'value' => '128',
                    'note' => 'Personel terdaftar aktif',
                    'icon' => 'people',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Tugas Berjalan',
                    'value' => '24',
                    'note' => 'Tugas operasional hari ini',
                    'icon' => 'tasks',
                    'tone' => 'violet',
                ],
                [
                    'label' => 'Komplain Aktif',
                    'value' => '6',
                    'note' => 'Perlu tindak lanjut',
                    'icon' => 'complaints',
                    'tone' => 'amber',
                ],
                [
                    'label' => 'Peringatan Stok Minimum',
                    'value' => '3',
                    'note' => 'Item perlu segera diisi ulang',
                    'icon' => 'inventory',
                    'tone' => 'rose',
                ],
            ],
        ]);
    }
}
