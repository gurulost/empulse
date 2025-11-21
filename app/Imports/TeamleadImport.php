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


class TeamleadImport implements ToModel, WithHeadingRow
{
    public function model(array $row) {
        if (empty(array_filter($row))) {
            return null;
        }

        $validator = Validator::make($row, [
            'name' => 'required|string|min:5',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $userService = app(\App\Services\UserService::class);

        $name = $row['name'];
        $email = $row['email'];
        $status = 'employee';
        $teamlead = \Auth::user()->name;

        $userAuthRole = \Auth::user()->role;
        $checkStatus = $userService->checkStatus($userAuthRole, $status);

        if($checkStatus === true) {
            return $userService->addWorkerTeamlead($name, $email, $teamlead);
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
            ]
        ];
    }
}
