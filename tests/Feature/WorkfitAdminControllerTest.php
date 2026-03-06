<?php

namespace Tests\Feature;

use App\Models\Companies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkfitAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_search_respects_company_filter(): void
    {
        $companyA = Companies::create([
            'title' => 'Alpha',
            'manager' => 'Manager A',
            'manager_email' => 'manager-a@example.com',
        ]);

        $companyB = Companies::create([
            'title' => 'Beta',
            'manager' => 'Manager B',
            'manager_email' => 'manager-b@example.com',
        ]);

        $admin = User::factory()->create([
            'role' => 0,
            'is_admin' => 1,
        ]);

        $alphaChief = User::factory()->create([
            'name' => 'Alice Alpha',
            'email' => 'alice.alpha@example.com',
            'role' => 2,
            'company_id' => $companyA->id,
        ]);

        User::factory()->create([
            'name' => 'Alice Beta',
            'email' => 'alice.beta@example.com',
            'role' => 2,
            'company_id' => $companyB->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/admin/api/users?search=alice&company_id={$companyA->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'email' => $alphaChief->email,
            'company_id' => $companyA->id,
        ]);
        $response->assertJsonMissing([
            'email' => 'alice.beta@example.com',
            'company_id' => $companyB->id,
        ]);
    }
}
