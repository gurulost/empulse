<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommitRosterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'confirmation_token' => ['required', 'string', 'size:64'],
        ];
    }
}
