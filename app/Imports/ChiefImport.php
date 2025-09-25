<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Companies;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Mail\CoworkersMsg;
use App\Mail\AdminMsg;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;

class ChiefImport implements ToModel, WithHeadingRow
{
    public function department() {
        $userEmail = \Auth::user()->email;
        return \DB::table('company_worker')->where('email', $userEmail)->value('department');
    }

    public function model(array $row) {
        if (empty(array_filter($row))) {
            return null;
        }

        $validator = Validator::make($row, [
            'name' => 'required|string|min:5',
            'email' => 'required|email',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $adminController = new AdminController();

        $name = $row['name'];
        $email = $row['email'];
        $password = $adminController->generatePassword();
        $status = isset($row['status']) && strlen($row['status']) > 0 ? $row['status'] : 'employee';
        $department = $this->department();

        $userAuthRole = \Auth::user()->role;
        $checkStatus = $adminController->checkStatus($userAuthRole, $status);

        if($checkStatus === true) {
            return User::add_worker($name, $email, $password, $status, $department);
        }
    }

    public function rules(): array {
        return [
            'name' => [
                'required',
                'string',
                'min:5'
            ],
            'email' => [
                'required',
                'email',
            ],
            'status' => [
                'required',
                'string',
            ]
        ];
    }
}
