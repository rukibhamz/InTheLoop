<?php

namespace App\Services;

class MicrosoftSocialiteConfigurator
{
    public function __construct(
        private readonly MicrosoftSettings $settings
    ) {}

    public function apply(): void
    {
        config([
            'services.microsoft.client_id' => $this->settings->clientId(),
            'services.microsoft.client_secret' => $this->settings->clientSecret(),
            'services.microsoft.tenant' => $this->settings->tenantId() ?: 'common',
            'services.microsoft.redirect' => config('microsoft.redirect'),
        ]);
    }
}
