<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StageRosterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:1024',
                'extensions:csv',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Roster CSV files may not exceed 1 MB.',
            'file.extensions' => 'The roster file must use the .csv extension.',
            'file.mimetypes' => 'The roster file must be a plain CSV document.',
        ];
    }
}
