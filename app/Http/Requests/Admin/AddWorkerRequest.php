<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','min:5','max:255'],
            'email' => ['required','email','max:255'],
            'role' => ['required','integer','in:1,2,3,4'],
            'department' => ['nullable','string','max:255'],
        ];
    }
}

