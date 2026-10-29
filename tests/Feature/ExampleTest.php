<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
    }

    public function test_root_redirects_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard_with_sidebar(): void
    {
        $user = User::where('username', 'cashier')->first();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('PSI ONE');
        $response->assertSee('GLOBAL POS');
        $response->assertSee('Cashier Register');
        $response->assertSee('Terminal Online');
    }
}
