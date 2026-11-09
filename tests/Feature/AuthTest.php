<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use LogicException;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke Dashboard');
    }

    public function test_active_user_can_login_and_login_is_audited(): void
    {
        User::factory()->create([
            'email' => 'spv@example.com',
            'password' => 'password123',
            'role' => 'supervisor',
        ]);

        $this->post('/login', [
            'email' => 'spv@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('audit_logs', [
            'role' => 'supervisor',
            'module' => 'authentication',
            'action' => 'login',
        ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'role' => 'supervisor',
            'action' => 'login',
            'reason' => 'Failed login attempt',
        ]);
    }

    public function test_logout_invalidates_session_and_is_audited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_profile_can_be_updated_and_audited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->from('/profile')->put('/profile', [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '08123456789',
        ])->assertRedirect('/profile')->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '08123456789',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'module' => 'authentication',
            'action' => 'update',
        ]);
    }

    public function test_supervisor_cannot_access_admin_only_routes(): void
    {
        Route::middleware(['auth', 'admin'])->get('/admin-only-test', fn () => response('ok'));
        $this->actingAs(User::factory()->create(['role' => 'supervisor']));

        $this->get('/admin-only-test')->assertForbidden();
    }

    public function test_admin_can_access_admin_only_routes(): void
    {
        Route::middleware(['auth', 'admin'])->get('/admin-only-test', fn () => response('ok'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/admin-only-test')->assertOk();
    }

    public function test_admin_and_supervisor_can_access_operational_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->assertTrue(Gate::forUser($admin)->allows('view-operations'));
        $this->assertTrue(Gate::forUser($supervisor)->allows('view-operations'));
        $this->assertTrue(Gate::forUser($admin)->allows('manage-system-settings'));
        $this->assertFalse(Gate::forUser($supervisor)->allows('manage-system-settings'));
    }

    public function test_audit_logs_cannot_be_updated_or_deleted(): void
    {
        $log = \App\Models\AuditLog::create([
            'role' => 'admin',
            'module' => 'test',
            'action' => 'create',
        ]);

        foreach ([
            fn () => $log->update(['action' => 'delete']),
            fn () => $log->delete(),
            fn () => \App\Models\AuditLog::query()->whereKey($log->id)->update(['action' => 'delete']),
            fn () => \App\Models\AuditLog::query()->whereKey($log->id)->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Mutating an audit log should throw an exception.');
            } catch (LogicException $exception) {
                $this->assertSame('Audit logs are immutable.', $exception->getMessage());
            }
        }

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id, 'action' => 'create']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => 'throttled@example.com',
                'password' => 'incorrect',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'email' => 'throttled@example.com',
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }

    public function test_default_users_are_seeded_with_hashed_passwords(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@cleaningservice.local')->firstOrFail();
        $supervisor = User::where('email', 'spv@cleaningservice.local')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($supervisor->isSupervisor());
        $this->assertTrue($admin->is_active);
        $this->assertTrue($supervisor->is_active);
    }
}
