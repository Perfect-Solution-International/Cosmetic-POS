<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_all_seven_retail_roles_are_seeded(): void
    {
        $expectedRoles = [
            'admin',
            'manager',
            'cashier',
            'head_office_manager',
            'inventory_staff',
            'warehouse_manager',
            'accountant',
        ];

        foreach ($expectedRoles as $role) {
            $this->assertDatabaseHas('users', ['role' => $role]);
        }
    }

    public function test_admin_can_access_staff_and_roles_page(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
        $response->assertSee('Staff & Role Permissions', false);
        $response->assertSee('System Administrator');
    }

    public function test_non_admin_cannot_access_staff_and_roles_page(): void
    {
        $cashier = User::where('role', 'cashier')->first();

        $response = $this->actingAs($cashier)->get('/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_staff_member(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Ruwan Perera',
            'username' => 'ruwan_pos',
            'email' => 'ruwan@example.com',
            'phone' => '+94 77 123 4567',
            'role' => 'inventory_staff',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'ruwan_pos',
            'role' => 'inventory_staff',
        ]);
    }

    public function test_admin_can_toggle_staff_status(): void
    {
        $admin = User::where('role', 'admin')->first();
        $cashier = User::where('role', 'cashier')->first();

        $this->assertEquals('active', $cashier->status);

        $response = $this->actingAs($admin)->post("/users/{$cashier->id}/toggle-status");

        $response->assertRedirect();
        $cashier->refresh();
        $this->assertEquals('inactive', $cashier->status);
    }

    public function test_role_navigation_visibility_for_cashier(): void
    {
        $cashier = User::where('role', 'cashier')->first();

        $response = $this->actingAs($cashier)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Cashier Register');
        $response->assertDontSee('Daily Expenses');
        $response->assertDontSee('Staff & Roles', false);
        $response->assertDontSee('Branches & Counters', false);
    }

    public function test_role_navigation_visibility_for_inventory_staff(): void
    {
        $invStaff = User::where('role', 'inventory_staff')->first();

        $response = $this->actingAs($invStaff)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Stock & Inventory', false);
        $response->assertSee('Expiry Date Alerts');
        $response->assertDontSee('Cashier Register');
        $response->assertDontSee('Staff & Roles', false);
    }

    public function test_admin_can_update_staff_member(): void
    {
        $admin = User::where('role', 'admin')->first();
        $cashier = User::where('role', 'cashier')->first();

        $response = $this->actingAs($admin)->put(route('users.update', $cashier->id), [
            'name' => 'Kusal Mendis',
            'username' => 'kusal_pos',
            'email' => 'kusal@psipos.com',
            'phone' => '+94 77 999 8877',
            'role' => 'manager',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $cashier->refresh();
        $this->assertEquals('Kusal Mendis', $cashier->name);
        $this->assertEquals('kusal_pos', $cashier->username);
        $this->assertEquals('manager', $cashier->role);
    }

    public function test_admin_can_reset_staff_password(): void
    {
        $admin = User::where('role', 'admin')->first();
        $cashier = User::where('role', 'cashier')->first();

        $response = $this->actingAs($admin)->post(route('users.reset-password', $cashier->id), [
            'new_password' => 'newSecretPass123',
            'new_password_confirmation' => 'newSecretPass123',
        ]);

        $response->assertRedirect(route('users.index'));
        $cashier->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newSecretPass123', $cashier->password));
    }

    public function test_admin_can_safely_delete_staff_member(): void
    {
        $admin = User::where('role', 'admin')->first();
        $cashier = User::where('role', 'cashier')->first();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $cashier->id));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin->id));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
