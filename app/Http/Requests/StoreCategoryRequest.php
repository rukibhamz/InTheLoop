<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:report_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_recipient_id' => ['nullable', 'exists:recipients,id'],
        ];
    }
}
