<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppSettingCache
{
    public const CACHE_KEY = 'app_settings_public';

    /** @var list<string> */
    private const PUBLIC_COLUMNS = [
        'id',
        'org_name',
        'logo_path',
        'accent_color',
        'graph_tenant_id',
        'graph_client_id',
        'graph_default_sender_mailbox',
        'graph_monitored_mailboxes',
        'graph_announcement_mailboxes',
        'microsoft_tenant_id',
        'microsoft_client_id',
        'sso_enabled',
        'updated_at',
    ];

    public function settings(): AppSetting
    {
        $attributes = Cache::remember(self::CACHE_KEY, 300, function () {
            $model = AppSetting::query()->find(1);

            return $model?->only(self::PUBLIC_COLUMNS) ?? [
                'org_name' => config('app.name'),
                'accent_color' => '#4648D4',
            ];
        });

        return (new AppSetting)->forceFill($attributes);
    }

    public function graphClientSecret(): ?string
    {
        $secret = AppSetting::query()->find(1)?->graph_client_secret;

        return filled($secret) ? $secret : config('graph.client_secret');
    }

    public function microsoftClientSecret(): ?string
    {
        $secret = AppSetting::query()->find(1)?->microsoft_client_secret;

        return filled($secret) ? $secret : config('microsoft.client_secret');
    }

    public function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('app_settings');
    }
}
