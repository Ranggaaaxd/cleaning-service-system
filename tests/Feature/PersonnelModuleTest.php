<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Personnel;
use App\Models\PersonnelChangeRequest;
use App\Models\Position;
use App\Models\User;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_and_personnel_seeder_creates_related_master_data_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $building = Building::with('floors.areas')->firstOrFail();

        $this->assertSame('Gedung Pusat', $building->name);
        $this->assertCount(3, $building->floors);
        $this->assertCount(12, Area::all());
        $this->assertSame(3, Position::count());
        $this->assertSame(5, Personnel::count());
        $this->assertMatchesRegularExpression('/^\d{16}$/', Personnel::firstOrFail()->nik);
        $this->assertCount(1, Personnel::firstOrFail()->statusHistories);
    }

    public function test_personnel_creation_records_initial_status_mutation_and_audit(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $floor = Floor::firstOrFail();
        $position = Position::firstOrFail();

        $personnel = app(PersonnelService::class)->createPersonnel([
            'nik' => '9876543210123456',
            'name' => 'Petugas Baru',
            'gender' => 'L',
            'position_id' => $position->id,
            'floor_id' => $floor->id,
            'status' => 'Aktif',
            'join_date' => '2026-11-09',
        ], $user->id);

        $this->assertSame('Aktif', $personnel->status);
        $this->assertDatabaseHas('personnel_status_histories', [
            'personnel_id' => $personnel->id,
            'old_status' => '',
            'new_status' => 'Aktif',
        ]);
        $this->assertDatabaseHas('personnel_mutation_histories', [
            'personnel_id' => $personnel->id,
            'from_floor_id' => null,
            'to_floor_id' => $floor->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'personnel',
            'action' => 'create',
        ]);
    }

    public function test_personnel_create_form_persists_a_new_personnel(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $floor = Floor::firstOrFail();
        $position = Position::firstOrFail();
        $this->actingAs($user);

        $this->post(route('personnel.store'), [
            'nik' => '9876543210123457',
            'name' => 'Petugas dari Form',
            'gender' => 'P',
            'phone' => '08123456789',
            'position_id' => $position->id,
            'floor_id' => $floor->id,
            'status' => 'Aktif',
            'join_date' => '2026-11-09',
        ])->assertRedirect();

        $this->assertDatabaseHas('personnels', [
            'nik' => '9876543210123457',
            'name' => 'Petugas dari Form',
        ]);
    }

    public function test_supervisor_identity_changes_require_admin_approval(): void
    {
        $this->seed();
        $supervisor = User::where('role', 'supervisor')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $service = app(PersonnelService::class);

        $service->updatePersonnel($personnel, [
            'nik' => '1234567890123456',
            'name' => 'Nama Koreksi',
            'change_reason' => 'Koreksi berdasarkan dokumen resmi',
        ], $supervisor);

        $this->assertNotSame('1234567890123456', $personnel->fresh()->nik);
        $this->assertSame(2, PersonnelChangeRequest::where('status', 'pending')->count());

        $request = PersonnelChangeRequest::where('personnel_id', $personnel->id)
            ->where('field_name', 'nik')
            ->firstOrFail();
        $service->approveChangeRequest($request->id, $admin, 'Dokumen telah diverifikasi');

        $this->assertSame('1234567890123456', $personnel->fresh()->nik);
        $this->assertDatabaseHas('personnel_change_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'approve_change_request',
        ]);
    }

    public function test_supervisor_can_update_operational_fields_but_not_identity_directly(): void
    {
        $this->seed();
        $supervisor = User::where('role', 'supervisor')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $oldName = $personnel->name;
        $position = Position::where('code', 'TL')->firstOrFail();

        app(PersonnelService::class)->updatePersonnel($personnel, [
            'name' => 'Identity Change Request',
            'position_id' => $position->id,
            'change_reason' => 'Perubahan sesuai dokumen',
        ], $supervisor);

        $this->assertSame($oldName, $personnel->fresh()->name);
        $this->assertSame($position->id, $personnel->fresh()->position_id);
        $this->assertDatabaseHas('personnel_change_requests', [
            'personnel_id' => $personnel->id,
            'field_name' => 'name',
            'status' => 'pending',
        ]);
    }

    public function test_status_and_floor_changes_record_histories(): void
    {
        $this->seed();
        $user = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $newFloor = Floor::where('id', '!=', $personnel->floor_id)->firstOrFail();
        $service = app(PersonnelService::class);

        $service->changeStatus($personnel, 'Cuti', 'Cuti tahunan', $user->id);
        $service->mutatePlacement($personnel, $newFloor->id, 'Kebutuhan operasional', $user->id);

        $this->assertSame('Cuti', $personnel->fresh()->status);
        $this->assertSame($newFloor->id, $personnel->fresh()->floor_id);
        $this->assertDatabaseHas('personnel_status_histories', [
            'personnel_id' => $personnel->id,
            'old_status' => 'Aktif',
            'new_status' => 'Cuti',
        ]);
        $this->assertDatabaseHas('personnel_mutation_histories', [
            'personnel_id' => $personnel->id,
            'to_floor_id' => $newFloor->id,
            'reason' => 'Kebutuhan operasional',
        ]);
    }

    public function test_editing_status_and_floor_also_records_histories(): void
    {
        $this->seed();
        $user = User::where('role', 'supervisor')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $oldFloorId = $personnel->floor_id;
        $newFloor = Floor::where('id', '!=', $oldFloorId)->firstOrFail();

        app(PersonnelService::class)->updatePersonnel($personnel, [
            'status' => 'Cuti',
            'floor_id' => $newFloor->id,
        ], $user);

        $this->assertDatabaseHas('personnel_status_histories', [
            'personnel_id' => $personnel->id,
            'old_status' => 'Aktif',
            'new_status' => 'Cuti',
        ]);
        $this->assertDatabaseHas('personnel_mutation_histories', [
            'personnel_id' => $personnel->id,
            'from_floor_id' => $oldFloorId,
            'to_floor_id' => $newFloor->id,
        ]);
    }

    public function test_admin_can_reject_a_supervisor_change_request(): void
    {
        $this->seed();
        $supervisor = User::where('role', 'supervisor')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $personnel = Personnel::firstOrFail();
        $service = app(PersonnelService::class);

        $service->updatePersonnel($personnel, [
            'name' => 'Nama Ditolak',
            'change_reason' => 'Alasan perubahan',
        ], $supervisor);

        $request = PersonnelChangeRequest::where('field_name', 'name')->firstOrFail();
        $service->rejectChangeRequest($request->id, $admin, 'Dokumen pendukung tidak cocok');

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame($request->old_value, $personnel->fresh()->name);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'reject_change_request',
            'reason' => 'Dokumen pendukung tidak cocok',
        ]);
    }

    public function test_personnel_pages_are_auth_protected_and_change_requests_are_admin_only(): void
    {
        $this->seed();
        $personnel = Personnel::firstOrFail();
        $supervisor = User::where('role', 'supervisor')->firstOrFail();

        $this->get(route('personnel.index'))->assertRedirect(route('login'));
        $this->actingAs($supervisor)
            ->get(route('personnel.index'))
            ->assertOk()
            ->assertSee($personnel->name);
        $this->get(route('personnel.create'))->assertOk();
        $this->get(route('personnel.show', $personnel))->assertOk();
        $this->get(route('personnel.change-requests.index'))->assertForbidden();

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('personnel.change-requests.index'))->assertOk();
    }
}
