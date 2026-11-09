<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MonitoringAndComplaintSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@cleaningservice.local')->firstOrFail();

            $templateDefinitions = [
                [
                    'name' => 'Checklist Kebersihan Toilet',
                    'area_type' => 'toilet',
                    'items' => [
                        'Kloset bersih',
                        'Wastafel & cermin bersih',
                        'Sabun/tisu terisi',
                        'Lantai kering',
                        'Tempat sampah dikosongkan',
                    ],
                ],
                [
                    'name' => 'Checklist Kebersihan Public/Lobby',
                    'area_type' => 'public',
                    'items' => [
                        'Lantai dipel mengkilap',
                        'Kaca bersih',
                        'Tempat sampah kosong',
                    ],
                ],
            ];

            foreach ($templateDefinitions as $definition) {
                $template = ChecklistTemplate::updateOrCreate(
                    ['name' => $definition['name'], 'area_type' => $definition['area_type']],
                    ['is_active' => true],
                );

                foreach ($definition['items'] as $index => $taskName) {
                    ChecklistItem::updateOrCreate(
                        ['template_id' => $template->id, 'order' => $index + 1],
                        ['task_name' => $taskName, 'is_active' => true],
                    );
                }
            }

            $categories = [
                ['name' => 'Sampah Menumpuk', 'description' => 'Sampah penuh atau terlambat diangkut'],
                ['name' => 'Toilet Bau/Kotor', 'description' => 'Kondisi toilet kotor atau berbau'],
                ['name' => 'Tumpahan Cairan', 'description' => 'Tumpahan yang berisiko menyebabkan lantai licin'],
                ['name' => 'Tisu/Sabun Habis', 'description' => 'Persediaan tisu atau sabun perlu diisi ulang'],
            ];

            foreach ($categories as $category) {
                ComplaintCategory::updateOrCreate(
                    ['name' => $category['name']],
                    ['description' => $category['description']],
                );
            }

            $area = Area::query()
                ->where('is_active', true)
                ->where('type', 'toilet')
                ->orderBy('id')
                ->firstOrFail();
            $category = ComplaintCategory::where('name', 'Toilet Bau/Kotor')->firstOrFail();
            $ticketNumber = 'KMP-'.now()->format('Ym').'-0001';
            $complaint = Complaint::firstOrCreate(
                ['ticket_number' => $ticketNumber],
                [
                    'reporter_name' => 'Contoh Pelapor',
                    'reporter_contact' => null,
                    'area_id' => $area->id,
                    'category_id' => $category->id,
                    'description' => 'Contoh tiket untuk menguji alur pencatatan dan tindak lanjut komplain.',
                    'priority' => 'Sedang',
                    'status' => 'Baru',
                ],
            );

            if ($complaint->wasRecentlyCreated) {
                ComplaintStatusHistory::create([
                    'complaint_id' => $complaint->id,
                    'old_status' => '',
                    'new_status' => 'Baru',
                    'notes' => 'Tiket contoh dari seeder.',
                    'changed_by' => $admin->id,
                ]);
            }
        });
    }
}
