<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LoginDatabaseCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'database',
            'cache.stores.database.connection' => config('database.default'),
        ]);

        Artisan::call('cache:clear');
    }

    public function test_failed_login_does_not_crash_with_database_cache_store(): void
    {
        User::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('correct-password'),
            'role' => 1,
            'company_id' => 1,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'manager@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_successful_login_redirects_with_database_cache_store(): void
    {
        User::factory()->create([
            'email' => 'manager@example.com',
            'password' => bcrypt('correct-password'),
            'role' => 1,
            'company_id' => 1,
        ]);

        $response = $this->post('/login', [
            'email' => 'manager@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticated();
    }
}
