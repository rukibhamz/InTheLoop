<?php

namespace App\Services\Graph;

use App\Models\DirectoryContact;
use Illuminate\Support\Facades\Http;

class GraphUserProfileResolver
{
    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    public function primaryMail(?string $azureObjectId, ?string $fallbackEmail = null): ?string
    {
        if (filled($azureObjectId)) {
            $fromDirectory = DirectoryContact::query()
                ->where('azure_object_id', $azureObjectId)
                ->value('email');

            if (filled($fromDirectory)) {
                return $fromDirectory;
            }

            $fromGraph = $this->fetchPrimaryMailFromGraph($azureObjectId);

            if (filled($fromGraph)) {
                return $fromGraph;
            }
        }

        if (filled($fallbackEmail)) {
            $fromDirectory = DirectoryContact::query()
                ->whereRaw('lower(email) = ?', [strtolower($fallbackEmail)])
                ->value('email');

            if (filled($fromDirectory)) {
                return $fromDirectory;
            }
        }

        return filled($fallbackEmail) ? $fallbackEmail : null;
    }

    private function fetchPrimaryMailFromGraph(string $azureObjectId): ?string
    {
        if (! $this->settings->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken($this->tokens->getAppToken())
                ->get(config('graph.base_url').'/users/'.urlencode($azureObjectId), [
                    '$select' => 'mail,userPrincipalName',
                ])
                ->throw()
                ->json();

            return $response['mail'] ?? $response['userPrincipalName'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
