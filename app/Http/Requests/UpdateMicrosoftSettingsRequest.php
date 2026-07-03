<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMicrosoftSettingsRequest extends FormRequest
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
            'graph_tenant_id' => ['nullable', 'string', 'max:255'],
            'graph_client_id' => ['nullable', 'string', 'max:255'],
            'graph_client_secret' => ['nullable', 'string', 'max:500'],
            'graph_default_sender_mailbox' => ['nullable', 'email', 'max:255'],
            'graph_monitored_mailboxes' => ['nullable', 'string', 'max:2000'],
            'graph_announcement_mailboxes' => ['nullable', 'string', 'max:2000'],
            'microsoft_tenant_id' => ['nullable', 'string', 'max:255'],
            'microsoft_client_id' => ['nullable', 'string', 'max:255'],
            'microsoft_client_secret' => ['nullable', 'string', 'max:500'],
            'sso_enabled' => ['boolean'],
        ];
    }
}
