<?php

namespace App\Services\Graph;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GraphTokenService
{
    public function __construct(
        private readonly GraphSettings $settings
    ) {}

    public function getAppToken(): string
    {
        if (! $this->settings->isConfigured()) {
            throw new RuntimeException('Microsoft Graph is not configured.');
        }

        return Cache::remember($this->cacheKey(), 3500, function () {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$this->settings->tenantId()}/oauth2/v2.0/token",
                [
                    'client_id' => $this->settings->clientId(),
                    'client_secret' => $this->settings->clientSecret(),
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            )->throw()->json();

            return $response['access_token'];
        });
    }

    public function forgetCachedToken(): void
    {
        Cache::forget($this->cacheKey());
    }

    private function cacheKey(): string
    {
        return config('graph.token_cache_key', 'graph_app_access_token');
    }
}
