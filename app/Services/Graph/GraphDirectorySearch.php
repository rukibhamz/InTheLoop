<?php

namespace App\Services\Graph;

use App\Models\DirectoryContact;
use App\Support\DirectoryContactFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GraphDirectorySearch
{
    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    /**
     * @return Collection<int, array{email: string, name: string, job_title: ?string, label: string}>
     */
    public function search(string $query, int $limit = 10): Collection
    {
        if (! $this->settings->isConfigured()) {
            return collect();
        }

        $token = $this->tokens->getAppToken();
        $escaped = str_replace("'", "''", $query);

        $users = $this->fetchWithFilter($token, $escaped, $limit);

        if ($users->isEmpty()) {
            $users = $this->fetchWithSearch($token, $query, $limit);
        }

        return $users
            ->map(function (array $user) {
                $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;

                if (! filled($email)) {
                    return null;
                }

                $name = $user['displayName'] ?? $email;
                $jobTitle = $user['jobTitle'] ?? null;

                DirectoryContact::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'azure_object_id' => $user['id'] ?? null,
                        'display_name' => $name,
                        'department' => $user['department'] ?? null,
                        'job_title' => $jobTitle,
                        'last_synced_at' => now(),
                    ]
                );

                return DirectoryContactFormatter::entry($email, $name, $jobTitle);
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchWithFilter(string $token, string $escaped, int $limit): Collection
    {
        $filter = "startswith(displayName,'{$escaped}') or startswith(mail,'{$escaped}') or startswith(userPrincipalName,'{$escaped}')";

        try {
            $response = Http::withToken($token)
                ->get(config('graph.base_url').'/users', [
                    '$filter' => $filter,
                    '$select' => 'id,displayName,mail,userPrincipalName,department,jobTitle',
                    '$top' => $limit,
                ])
                ->throw()
                ->json();

            return collect($response['value'] ?? []);
        } catch (\Throwable) {
            return collect();
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchWithSearch(string $token, string $query, int $limit): Collection
    {
        $search = '"displayName:'.addcslashes($query, '"\\').'" OR "mail:'.addcslashes($query, '"\\').'"';

        try {
            $response = Http::withToken($token)
                ->withHeaders(['ConsistencyLevel' => 'eventual'])
                ->get(config('graph.base_url').'/users', [
                    '$search' => $search,
                    '$select' => 'id,displayName,mail,userPrincipalName,department,jobTitle',
                    '$top' => $limit,
                ])
                ->throw()
                ->json();

            return collect($response['value'] ?? []);
        } catch (\Throwable) {
            return collect();
        }
    }
}
