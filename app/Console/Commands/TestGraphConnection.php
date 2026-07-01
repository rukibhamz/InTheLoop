<?php

namespace App\Console\Commands;

use App\Services\Graph\GraphSettings;
use App\Services\Graph\GraphTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGraphConnection extends Command
{
    protected $signature = 'graph:test';

    protected $description = 'Verify Microsoft Graph app credentials and permissions';

    public function handle(GraphSettings $settings, GraphTokenService $tokens): int
    {
        if (! $settings->isConfigured()) {
            $this->error('Graph is not configured. Set credentials in App Settings or .env.');

            return self::FAILURE;
        }

        $tokens->forgetCachedToken();

        $clientId = $settings->clientId() ?? '';
        $this->line('Using client ID: '.$this->mask($clientId));
        $this->line('Compare this to Application (client) ID in Entra → App registrations.');

        try {
            $token = $tokens->getAppToken();
            $this->info('Graph token acquired successfully.');
            $this->printApplicationRoles($token);
        } catch (\Throwable $e) {
            $this->error('Could not acquire token — check tenant ID, client ID, and client secret.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $response = Http::withToken($token)
                ->get(config('graph.base_url').'/users', [
                    '$top' => 1,
                    '$select' => 'displayName,userPrincipalName',
                ])
                ->throw()
                ->json();

            $sampleUser = $response['value'][0] ?? null;
            $this->line('Directory read (User.Read.All): OK');
            $this->line('Sample user: '.($sampleUser['displayName'] ?? $sampleUser['userPrincipalName'] ?? 'none returned'));
        } catch (\Throwable $e) {
            $this->error('Directory read failed.');
            $this->line('Entra must have Microsoft Graph → Application permissions (not Delegated): Mail.Send, Mail.Read, User.Read.All — then Grant admin consent.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('Monitored mailboxes: '.count($settings->allMonitoredMailboxes()));

        $sender = $settings->defaultSenderMailbox();
        if (! $sender) {
            $this->warn('Set a default sender mailbox to also verify Mail.Read.');

            return self::SUCCESS;
        }

        try {
            Http::withToken($token)
                ->get(config('graph.base_url')."/users/{$sender}/messages", ['$top' => 1])
                ->throw();
            $this->line("Mailbox read (Mail.Read) for {$sender}: OK");
        } catch (\Throwable $e) {
            $this->warn("Mailbox read failed for {$sender}.");
            $this->line('If credentials and User.Read.All work, this is often an Application Access Policy issue — restrict the app to your shared mailboxes in Exchange Online PowerShell.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function mask(string $value): string
    {
        if (strlen($value) <= 12) {
            return $value;
        }

        return substr($value, 0, 8).'…'.substr($value, -4);
    }

    private function printApplicationRoles(string $token): void
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $roles = $payload['roles'] ?? [];

        if ($roles === []) {
            $this->warn('Token has no application roles — only Delegated permissions are configured, or admin consent was not granted for Application permissions.');

            return;
        }

        $this->line('Application roles in token: '.implode(', ', $roles));

        if (! in_array('User.Read.All', $roles, true)) {
            $this->warn('User.Read.All is missing from the token — add it as an Application permission and grant admin consent.');
        }
    }
}
