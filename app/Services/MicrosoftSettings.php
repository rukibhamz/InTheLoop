<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class MicrosoftSettings
{
    public function settings(): AppSetting
    {
        return Cache::remember('app_settings', 300, function () {
            return AppSetting::query()->find(1) ?? new AppSetting;
        });
    }

    public function isConfigured(): bool
    {
        return filled($this->tenantId())
            && filled($this->clientId())
            && filled($this->clientSecret());
    }

    public function isSsoEnabled(): bool
    {
        return $this->isConfigured() && (bool) $this->settings()->sso_enabled;
    }

    public function tenantId(): ?string
    {
        return $this->settings()->microsoft_tenant_id ?: config('microsoft.tenant_id');
    }

    public function clientId(): ?string
    {
        return $this->settings()->microsoft_client_id ?: config('microsoft.client_id');
    }

    public function clientSecret(): ?string
    {
        return $this->settings()->microsoft_client_secret ?: config('microsoft.client_secret');
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return config('microsoft.scopes', ['openid', 'profile', 'email', 'User.Read']);
    }
}
