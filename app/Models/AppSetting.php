<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'org_name',
        'logo_path',
        'accent_color',
        'graph_tenant_id',
        'graph_client_id',
        'graph_client_secret',
        'graph_default_sender_mailbox',
        'graph_monitored_mailboxes',
        'microsoft_tenant_id',
        'microsoft_client_id',
        'microsoft_client_secret',
        'sso_enabled',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
            'sso_enabled' => 'boolean',
            'graph_client_secret' => 'encrypted',
            'microsoft_client_secret' => 'encrypted',
        ];
    }
}
