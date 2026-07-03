<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'array'],
            'to.email' => ['required', 'email', 'max:255'],
            'to.name' => ['nullable', 'string', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*.email' => ['required_with:cc', 'email', 'max:255'],
            'cc.*.name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:25600',
                Rule::file()->types(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']),
            ],
        ];
    }
}
