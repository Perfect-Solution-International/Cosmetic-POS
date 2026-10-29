<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Psi One');
        $response->assertSee('Global POS');
        $response->assertSee('Operator Login');
    }

    public function test_user_can_authenticate_using_username(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_authenticate_using_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'cashier@psipos.com',
            'password' => 'cashier123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('username', 'cashier')->first();
        $user->update(['status' => 'inactive']);

        $response = $this->post('/login', [
            'login' => 'cashier',
            'password' => 'cashier123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome, ' . $user->name);
        $response->assertSee('Terminal Online');
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}

