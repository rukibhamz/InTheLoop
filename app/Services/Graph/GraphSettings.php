<?php

namespace App\Services\Graph;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class GraphSettings
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

    public function tenantId(): ?string
    {
        return $this->settings()->graph_tenant_id ?: config('graph.tenant_id');
    }

    public function clientId(): ?string
    {
        return $this->settings()->graph_client_id ?: config('graph.client_id');
    }

    public function clientSecret(): ?string
    {
        return $this->settings()->graph_client_secret ?: config('graph.client_secret');
    }

    public function defaultSenderMailbox(): ?string
    {
        return $this->settings()->graph_default_sender_mailbox ?: config('graph.default_sender_mailbox');
    }

    /**
     * Mailboxes the Entra application access policy allows for Mail.Send.
     * Only the configured default sender and explicitly listed monitored mailboxes qualify.
     */
    public function isPolicyAllowedMailbox(string $mailbox): bool
    {
        $allowed = array_map('strtolower', array_filter(array_merge(
            [$this->defaultSenderMailbox()],
            $this->monitoredMailboxes(),
            $this->announcementMailboxes(),
        )));

        return in_array(strtolower($mailbox), $allowed, true);
    }

    /**
     * @return list<string>
     */
    public function announcementMailboxes(): array
    {
        $fromDb = $this->settings()->graph_announcement_mailboxes;

        if (filled($fromDb)) {
            return array_values(array_filter(array_map('trim', explode(',', $fromDb))));
        }

        return config('graph.announcement_mailboxes', []);
    }

    public function isAnnouncementMailbox(string $mailbox): bool
    {
        return in_array(strtolower(trim($mailbox)), array_map('strtolower', $this->announcementMailboxes()), true);
    }

    /**
     * @return list<string>
     */
    public function monitoredMailboxes(): array
    {
        $fromDb = $this->settings()->graph_monitored_mailboxes;

        if (filled($fromDb)) {
            return array_values(array_filter(array_map('trim', explode(',', $fromDb))));
        }

        return config('graph.monitored_mailboxes', []);
    }

    /**
     * All mailboxes to poll: configured list + staff shared mailboxes + routing recipients + default sender.
     *
     * @return list<string>
     */
    public function allMonitoredMailboxes(): array
    {
        $mailboxes = $this->monitoredMailboxes();

        $fromAnnouncement = $this->announcementMailboxes();

        $fromUsers = \App\Models\User::query()
            ->whereNotNull('shared_mailbox_email')
            ->where('is_active', true)
            ->pluck('shared_mailbox_email')
            ->all();

        $fromRecipients = \App\Models\Recipient::query()
            ->pluck('shared_mailbox_email')
            ->all();

        if ($default = $this->defaultSenderMailbox()) {
            $mailboxes[] = $default;
        }

        return array_values(array_unique(array_filter(array_map(
            fn (string $mailbox) => trim($mailbox),
            array_merge($mailboxes, $fromAnnouncement, $fromUsers, $fromRecipients)
        ), fn (string $mailbox) => $this->isPollableMailbox($mailbox))));
    }

    private function isPollableMailbox(string $mailbox): bool
    {
        if ($mailbox === '') {
            return false;
        }

        $domain = strtolower(substr(strrchr($mailbox, '@') ?: '', 1));

        return ! in_array($domain, ['intheloop.test', 'org.com', 'example.com'], true);
    }

    /**
     * Mailboxes tied to a specific report thread (submitter + in-app users on To/CC).
     *
     * @return list<string>
     */
    public function mailboxesForReport(\App\Models\Report $report): array
    {
        $report->loadMissing(['user', 'participants']);

        $mailboxes = $this->allMonitoredMailboxes();

        $participantEmails = $report->participants->pluck('email')->filter()->all();
        $participantUserMailboxes = \App\Models\User::query()
            ->whereIn('email', $participantEmails)
            ->whereNotNull('shared_mailbox_email')
            ->pluck('shared_mailbox_email')
            ->all();

        if ($report->user?->shared_mailbox_email) {
            $mailboxes[] = $report->user->shared_mailbox_email;
        } elseif ($report->user?->email) {
            $mailboxes[] = $report->user->email;
        }

        return array_values(array_unique(array_filter(array_map(
            'trim',
            array_merge($mailboxes, $participantUserMailboxes)
        ))));
    }

    public function clearCache(): void
    {
        Cache::forget('app_settings');
    }
}
