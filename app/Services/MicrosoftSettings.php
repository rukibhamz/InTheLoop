<?php

namespace App\Services;

class MicrosoftSettings
{
    public function __construct(
        private readonly AppSettingCache $settingsCache
    ) {}

    public function settings(): \App\Models\AppSetting
    {
        return $this->settingsCache->settings();
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
        return $this->settingsCache->microsoftClientSecret();
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return config('microsoft.scopes', ['openid', 'profile', 'email', 'User.Read']);
    }
}
