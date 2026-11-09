<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Building;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\InspectionFinding;
use App\Models\MonitoringInspection;
use App\Models\User;
use App\Modules\Complaint\Services\ComplaintService;
use App\Modules\Monitoring\Services\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MonitoringComplaintModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitoring_and_complaint_seeder_is_repeatable(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, ChecklistTemplate::count());
        $this->assertSame(8, ChecklistItem::count());
        $this->assertSame(4, ComplaintCategory::count());
        $this->assertSame(1, Complaint::count());
        $this->assertSame(1, Complaint::firstOrFail()->statusHistories()->count());
    }

    public function test_inspection_creates_snapshot_details_open_findings_and_audit(): void
    {
        $this->seed();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $area = Area::where('type', 'toilet')->firstOrFail();
        $items = ChecklistItem::whereHas('template', fn ($query) => $query->where('area_type', 'toilet'))
            ->orderBy('order')
            ->get();

        $inspection = app(MonitoringService::class)->storeInspection([
            'area_id' => $area->id,
            'inspection_type' => 'rutin',
            'inspection_date' => today()->toDateString(),
            'inspection_time' => '09:30',
            'overall_status' => 'Kotor/Perlu Tindakan',
            'notes' => 'Pemeriksaan rutin pagi',
        ], [
            [
                'checklist_item_id' => $items[0]->id,
                'item_snapshot_text' => 'Teks manipulasi dari klien',
                'status' => 'Kotor',
                'notes' => 'Perlu dibersihkan',
            ],
            [
                'checklist_item_id' => $items[1]->id,
                'status' => 'Rusak',
                'item_snapshot_text' => 'Wastafel & cermin bersih',
            ],
            [
                'checklist_item_id' => $items[2]->id,
                'status' => 'Bersih',
            ],
        ], $user->id);

        $this->assertMatchesRegularExpression('/^INS-\d{8}-\d{4}$/', $inspection->code);
        $this->assertSame(3, $inspection->details()->count());
        $this->assertSame(2, $inspection->findings()->count());
        $firstDetail = $inspection->details()->where('checklist_item_id', $items[0]->id)->firstOrFail();
        $this->assertSame($items[0]->task_name, $firstDetail->item_snapshot_text);
        $this->assertSame(2, $inspection->findings()->where('status', 'open')->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'monitoring',
            'action' => 'create',
        ]);
    }

    public function test_finding_recheck_updates_status_and_audit(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $area = Area::where('type', 'toilet')->firstOrFail();
        $item = ChecklistItem::whereHas('template', fn ($query) => $query->where('area_type', 'toilet'))->firstOrFail();
        $inspection = app(MonitoringService::class)->storeInspection([
            'area_id' => $area->id,
            'inspection_type' => 'mendadak',
            'inspection_date' => today()->toDateString(),
            'inspection_time' => '10:00',
            'overall_status' => 'Cukup',
        ], [['checklist_item_id' => $item->id, 'status' => 'Kotor']], $user->id);
        $finding = $inspection->findings()->firstOrFail();

        app(MonitoringService::class)->recheckFinding($finding->id, 'resolved', 'Kondisi sudah diperbaiki', $user->id);

        $this->assertSame('resolved', $finding->fresh()->status);
        $this->assertSame($user->id, $finding->fresh()->recheck_by);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'monitoring',
            'action' => 'finding_recheck',
        ]);
    }

    public function test_inspection_create_route_accepts_dynamic_checklist_and_photo(): void
    {
        Storage::fake('public');
        $this->seed();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $area = Area::where('type', 'toilet')->firstOrFail();
        $item = ChecklistItem::whereHas('template', fn ($query) => $query->where('area_type', 'toilet'))->firstOrFail();
        $this->actingAs($user);

        $this->get(route('monitoring.create'))
            ->assertOk()
            ->assertViewHas('buildings', fn ($buildings) => $buildings->isNotEmpty()
                && $buildings->every(fn ($building) => $building->is_active
                    && $building->floors->every(fn ($floor) => $floor->is_active
                        && $floor->areas->every(fn ($nestedArea) => $nestedArea->is_active))))
            ->assertViewHas('templates', fn ($templates) => $templates->isNotEmpty()
                && $templates->every(fn ($template) => $template->is_active
                    && $template->items->every(fn ($templateItem) => $templateItem->is_active)))
            ->assertSee('Pilih Gedung')
            ->assertSee('Pilih Lantai')
            ->assertSee('Pilih Area / Ruangan')
            ->assertSee('Silakan pilih Gedung, Lantai, dan Area terlebih dahulu untuk memuat item checklist kebersihan.');
        $this->get(route('monitoring.checklist', $area))->assertOk()->assertJsonFragment(['task_name' => 'Kloset bersih']);
        $response = $this->post(route('monitoring.store'), [
            'area_id' => $area->id,
            'inspection_type' => 'rutin',
            'inspection_date' => today()->toDateString(),
            'inspection_time' => '08:00',
            'overall_status' => 'Cukup',
            'items' => [
                [
                    'checklist_item_id' => $item->id,
                    'item_snapshot_text' => $item->task_name,
                    'status' => 'Kotor',
                    'notes' => 'Ada noda di lantai',
                    'photo' => UploadedFile::fake()->image('evidence.jpg'),
                ],
            ],
        ]);

        $inspection = MonitoringInspection::firstOrFail();
        $response->assertRedirect(route('monitoring.show', $inspection));
        $detail = $inspection->details()->firstOrFail();
        Storage::disk('public')->assertExists($detail->photo_path);
        $this->assertSame('Kotor', $detail->status);
    }

    public function test_complaint_lifecycle_generates_ticket_history_and_audit(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $area = Area::where('type', 'toilet')->firstOrFail();
        $category = ComplaintCategory::firstOrFail();
        $complaint = app(ComplaintService::class)->createComplaint([
            'reporter_name' => 'Pelapor Uji',
            'reporter_contact' => '08123456789',
            'area_id' => $area->id,
            'category_id' => $category->id,
            'description' => 'Lantai toilet licin.',
            'priority' => 'Tinggi',
        ], $user->id);

        $this->assertMatchesRegularExpression('/^KMP-\d{6}-\d{4}$/', $complaint->ticket_number);
        $this->assertSame('Baru', $complaint->status);
        $this->assertSame(1, $complaint->statusHistories()->count());

        app(ComplaintService::class)->updateStatus($complaint, 'Diverifikasi', 'Laporan valid.', $user->id);
        app(ComplaintService::class)->updateStatus($complaint, 'Diproses', 'Petugas ditugaskan.', $user->id);
        app(ComplaintService::class)->updateStatus($complaint, 'Selesai', 'Area sudah dibersihkan.', $user->id);

        $this->assertSame('Selesai', $complaint->fresh()->status);
        $this->assertNotNull($complaint->fresh()->verified_at);
        $this->assertNotNull($complaint->fresh()->resolved_at);
        $this->assertSame(4, $complaint->statusHistories()->count());
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'complaint',
            'action' => 'status_change',
        ]);
    }

    public function test_complaint_routes_show_filters_and_status_history(): void
    {
        $this->seed();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $complaint = Complaint::firstOrFail();
        $this->actingAs($user);

        $this->get(route('complaint.index'))->assertOk()->assertSee($complaint->ticket_number);
        $this->get(route('complaint.create'))->assertOk();
        $this->get(route('complaint.show', $complaint))
            ->assertOk()
            ->assertSee($complaint->ticket_number)
            ->assertSee('Riwayat status');

        $this->post(route('complaint.status', $complaint), [
            'status' => 'Diverifikasi',
            'notes' => 'Terverifikasi.',
        ])->assertRedirect();
        $this->assertSame('Diverifikasi', $complaint->fresh()->status);
    }

    public function test_complaint_create_form_receives_active_location_hierarchy_and_old_area(): void
    {
        $this->seed();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $area = Area::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession(['_old_input' => ['area_id' => (string) $area->id]])
            ->get(route('complaint.create'));

        $response->assertOk()
            ->assertViewHas('buildings', function ($buildings) {
                return $buildings->isNotEmpty()
                    && $buildings->every(fn ($building) => $building->is_active
                        && $building->floors->every(fn ($floor) => $floor->is_active
                            && $floor->areas->every(fn ($nestedArea) => $nestedArea->is_active)));
            })
            ->assertSee('Pilih Gedung')
            ->assertSee('Pilih Lantai')
            ->assertSee('Pilih Area / Ruangan')
            ->assertSee('complaintLocation(')
            ->assertSee('name="area_id"', false);
    }

    public function test_monitoring_and_complaint_pages_require_authentication(): void
    {
        $this->get(route('monitoring.index'))->assertRedirect(route('login'));
        $this->get(route('complaint.index'))->assertRedirect(route('login'));

        $this->seed();
        $this->actingAs(User::where('role', 'supervisor')->firstOrFail());
        $this->get(route('monitoring.index'))->assertOk();
        $this->get(route('complaint.index'))->assertOk();
    }
}
