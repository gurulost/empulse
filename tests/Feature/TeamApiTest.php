<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Companies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    protected $manager;
    protected $companyId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $company = Companies::create([
            'title' => 'Test Company',
            'manager' => 'Manager Name',
            'manager_email' => 'manager@test.com'
        ]);
        $this->companyId = $company->id;

        // Create a manager user
        $this->manager = User::create([
            'name' => 'Manager Name',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 1, // Manager
            'company_id' => $this->companyId,
            'company_title' => 'Test Company',
            'tariff' => 1
        ]);
    }

    // --- Members API Tests ---

    public function test_manager_can_list_members()
    {
        // Create some workers
        DB::table('company_worker')->insert([
            ['company_id' => $this->companyId, 'name' => 'Worker 1', 'email' => 'w1@test.com', 'role' => 4, 'department' => 'IT'],
            ['company_id' => $this->companyId, 'name' => 'Worker 2', 'email' => 'w2@test.com', 'role' => 4, 'department' => 'HR'],
        ]);

        $response = $this->actingAs($this->manager)
                         ->getJson('/team/api/members');

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }

    public function test_manager_can_add_member()
    {
        $response = $this->actingAs($this->manager)
                         ->postJson('/team/api/members', [
                             'name' => 'New Worker',
                             'email' => 'new@test.com',
                             'role' => 4,
                             'department' => 'IT'
                         ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('company_worker', [
            'email' => 'new@test.com',
            'company_id' => $this->companyId
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'new@test.com',
            'company_id' => $this->companyId
        ]);
    }

    public function test_manager_can_update_member()
    {
        // Create a worker
        $worker = User::create([
            'name' => 'Old Name',
            'email' => 'old@test.com',
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $this->companyId,
            'company_title' => 'Test Company'
        ]);
        
        DB::table('company_worker')->insert([
            'company_id' => $this->companyId,
            'name' => 'Old Name',
            'email' => 'old@test.com',
            'role' => 4,
            'department' => 'IT'
        ]);

        $response = $this->actingAs($this->manager)
                         ->putJson("/team/api/members/{$worker->email}", [
                             'new_name' => 'New Name',
                             'new_email' => 'old@test.com', // Keeping email same
                             'new_role' => 4,
                             'new_department' => 'HR'
                         ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('company_worker', [
            'email' => 'old@test.com',
            'name' => 'New Name',
            'department' => 'HR'
        ]);
    }

    public function test_manager_can_delete_member()
    {
        // Create a worker
        $worker = User::create([
            'name' => 'To Delete',
            'email' => 'delete@test.com',
            'password' => bcrypt('password'),
            'role' => 4,
            'company_id' => $this->companyId,
            'company_title' => 'Test Company'
        ]);
        
        DB::table('company_worker')->insert([
            'company_id' => $this->companyId,
            'name' => 'To Delete',
            'email' => 'delete@test.com',
            'role' => 4,
            'department' => 'IT'
        ]);

        $response = $this->actingAs($this->manager)
                         ->deleteJson("/team/api/members/{$worker->email}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['email' => 'delete@test.com']);
        $this->assertDatabaseMissing('company_worker', ['email' => 'delete@test.com']);
    }

    // --- Departments API Tests ---

    public function test_manager_can_list_departments()
    {
        DB::table('company_department')->insert([
            ['company_id' => $this->companyId, 'title' => 'IT'],
            ['company_id' => $this->companyId, 'title' => 'HR'],
        ]);

        $response = $this->actingAs($this->manager)
                         ->getJson('/team/api/departments');

        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_manager_can_add_department()
    {
        $response = $this->actingAs($this->manager)
                         ->postJson('/team/api/departments', [
                             'title' => 'Marketing'
                         ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('company_department', [
            'company_id' => $this->companyId,
            'title' => 'Marketing'
        ]);
    }

    public function test_manager_can_update_department()
    {
        DB::table('company_department')->insert([
            'company_id' => $this->companyId,
            'title' => 'Old Dept'
        ]);

        $response = $this->actingAs($this->manager)
                         ->putJson('/team/api/departments/Old Dept', [
                             'newTitle' => 'New Dept'
                         ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('company_department', [
            'company_id' => $this->companyId,
            'title' => 'New Dept'
        ]);
        
        $this->assertDatabaseMissing('company_department', [
            'company_id' => $this->companyId,
            'title' => 'Old Dept'
        ]);
    }

    public function test_manager_can_delete_department()
    {
        DB::table('company_department')->insert([
            'company_id' => $this->companyId,
            'title' => 'To Delete'
        ]);

        $response = $this->actingAs($this->manager)
                         ->deleteJson('/team/api/departments/To Delete');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('company_department', [
            'company_id' => $this->companyId,
            'title' => 'To Delete'
        ]);
    }
}
