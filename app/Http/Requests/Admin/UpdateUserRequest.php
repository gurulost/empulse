<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'new_name' => ['required','string','max:255'],
            'new_email' => ['required','email','max:255'],
            'new_role' => ['nullable','integer','in:1,2,3,4'],
            'new_department' => ['nullable','string','max:255'],
        ];
    }
}

