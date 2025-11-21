<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\UserService;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_service_can_be_instantiated()
    {
        $service = app(EmailService::class);
        $this->assertInstanceOf(EmailService::class, $service);
    }

    public function test_user_service_has_helper_methods()
    {
        $service = app(UserService::class);
        
        // Test generatePassword
        $password = $service->generatePassword(10);
        $this->assertEquals(10, strlen($password));
        $this->assertIsString($password);

        // Test checkStatus
        // Manager (1) can add chief/manager
        $this->assertTrue($service->checkStatus(1, 'chief'));
        $this->assertTrue($service->checkStatus(1, 'manager'));
        $this->assertFalse($service->checkStatus(1, 'invalid'));

        // Chief (2) can add teamlead/employee
        $this->assertTrue($service->checkStatus(2, 'teamlead'));
        $this->assertTrue($service->checkStatus(2, 'employee'));
        $this->assertFalse($service->checkStatus(2, 'manager'));

        // Teamlead (3) can add employee
        $this->assertTrue($service->checkStatus(3, 'employee'));
        $this->assertFalse($service->checkStatus(3, 'teamlead'));
    }

    public function test_user_service_has_worker_creation_methods()
    {
        $service = app(UserService::class);
        $this->assertTrue(method_exists($service, 'addWorker'));
        $this->assertTrue(method_exists($service, 'addWorkerTeamlead'));
    }
}
