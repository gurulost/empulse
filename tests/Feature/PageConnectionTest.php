<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageConnectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_login_paga()
    {
        $result = $this->get('/login');
        $result->assertOk();
    }

    public function test_home_page()
    {
        $user = User::factory()->create([
            'role' => 1,
            'company' => 1,
        ]);

        $result = $this->actingAs($user)
            ->withSession(['banned' => false])
            ->get('/login');

        $result->assertRedirect('/home');
    }
}
