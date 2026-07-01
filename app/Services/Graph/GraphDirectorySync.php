<?php

namespace App\Services\Graph;

use App\Models\DirectoryContact;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GraphDirectorySync
{
    public function __construct(
        private readonly GraphSettings $settings,
        private readonly GraphTokenService $tokens
    ) {}

    public function sync(): int
    {
        if (! $this->settings->isConfigured()) {
            throw new RuntimeException('Microsoft Graph is not configured.');
        }

        $token = $this->tokens->getAppToken();
        $url = config('graph.base_url').'/users?$select=id,displayName,mail,userPrincipalName,department,jobTitle&$top=100';
        $synced = 0;

        do {
            $response = Http::withToken($token)->get($url)->throw()->json();

            foreach ($response['value'] ?? [] as $user) {
                $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;

                if (! filled($email)) {
                    continue;
                }

                DirectoryContact::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'azure_object_id' => $user['id'] ?? null,
                        'display_name' => $user['displayName'] ?? $email,
                        'department' => $user['department'] ?? null,
                        'job_title' => $user['jobTitle'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );

                $this->linkDirectoryContactToUser($email, $user);

                $synced++;
            }

            $url = $response['@odata.nextLink'] ?? null;
        } while ($url);

        return $synced;
    }

    /**
     * @param  array<string, mixed>  $graphUser
     */
    private function linkDirectoryContactToUser(string $email, array $graphUser): void
    {
        $localUser = User::query()->where('email', $email)->first();

        if (! $localUser && filled($graphUser['id'] ?? null)) {
            $localUser = User::query()->where('azure_object_id', $graphUser['id'])->first();
        }

        if (! $localUser) {
            return;
        }

        $updates = [];

        if (filled($graphUser['id'] ?? null) && ! $localUser->azure_object_id) {
            $updates['azure_object_id'] = $graphUser['id'];
        }

        if (filled($graphUser['department'] ?? null) && ! $localUser->department) {
            $updates['department'] = $graphUser['department'];
        }

        $localUser->fill($updates);
        $localUser->syncSharedMailboxFromAzure($email);
        $localUser->save();
    }
}
