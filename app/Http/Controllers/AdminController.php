<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use App\Models\Companies;
use Illuminate\Support\Facades\Gate;
use App\Models\CompanyWorker;
use App\Services\UserService;
use App\Services\DepartmentService;
use App\Mail\CoworkersMsg;
use App\Mail\AdminMsg;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use MongoDB\Driver\Session;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminController extends Controller
{
    public $companyDepartmentTable = 'company_department';
    public $companyWorkerTable = 'company_worker';

    protected UserService $userService;
    protected DepartmentService $departmentService;

    public function __construct(UserService $userService, DepartmentService $departmentService)
    {
        $this->userService = $userService;
        $this->departmentService = $departmentService;
    }

    public function send_letter($email, $name, $subject, $content) {
        try {
            $response = Http::withHeaders([
                'api-key' => env("BREVO_API_KEY"),
                'Content-Type' => 'application/json'
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => 'Workfitdx',
                    'email' => 'billing@workfitdx.com'
                ],
                'to' => [
                    [
                        'email' => $email,
                        'name' => $name
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $content
            ]);

            return ['status' => 200];
        } catch (\Exception $e) {
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    // Methods removed as they have been migrated to TeamController

}
