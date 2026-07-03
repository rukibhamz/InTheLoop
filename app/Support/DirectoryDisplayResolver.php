<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\DirectoryContact;
use App\Models\Report;
use App\Models\ReportParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

class DirectoryDisplayResolver
{
    /** @var array<string, string> */
    private array $names = [];

    public static function forReport(Report $report): self
    {
        $resolver = new self;

        $emails = collect([
            $report->user?->email,
            $report->user?->shared_mailbox_email,
        ]);

        foreach ($report->participants as $participant) {
            $emails->push($participant->email);

            if (filled($participant->name)) {
                $resolver->names[strtolower($participant->email)] = $participant->name;
            }
        }

        foreach ($report->threadMessages as $message) {
            $emails->push($message->from_email);
            $emails = $emails->merge($message->to_emails ?? [])->merge($message->cc_emails ?? []);
        }

        $resolver->loadFromDirectory($emails);

        return $resolver;
    }

    /**
     * @param  Collection<int, Announcement>|iterable<int, Announcement>  $announcements
     */
    public static function forAnnouncements(iterable $announcements): self
    {
        $resolver = new self;
        $emails = collect();

        foreach ($announcements as $announcement) {
            $emails->push($announcement->from_email);
            $emails = $emails->merge($announcement->to_emails ?? [])->merge($announcement->cc_emails ?? []);

            if (filled($announcement->from_name)) {
                $resolver->names[strtolower($announcement->from_email)] = $announcement->from_name;
            }
        }

        $resolver->loadFromDirectory($emails);

        return $resolver;
    }

    /**
     * @param  Collection<int, string|null>|array<int, string|null>  $emails
     */
    public function loadFromDirectory(Collection|array $emails): void
    {
        $normalized = collect($emails)
            ->filter(fn ($email) => filled($email))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return;
        }

        $contacts = DirectoryContact::query()
            ->where(function ($query) use ($normalized) {
                foreach ($normalized as $email) {
                    $query->orWhereRaw('lower(email) = ?', [$email]);
                }
            })
            ->get(['email', 'display_name']);

        foreach ($contacts as $contact) {
            $key = strtolower($contact->email);
            $this->names[$key] ??= $contact->display_name;
        }

        $users = User::query()
            ->where(function ($query) use ($normalized) {
                foreach ($normalized as $email) {
                    $query->orWhereRaw('lower(email) = ?', [$email])
                        ->orWhereRaw('lower(shared_mailbox_email) = ?', [$email]);
                }
            })
            ->get(['name', 'email', 'shared_mailbox_email']);

        foreach ($users as $user) {
            $this->names[strtolower($user->email)] ??= $user->name;

            if (filled($user->shared_mailbox_email)) {
                $this->names[strtolower($user->shared_mailbox_email)] ??= $user->name;
            }
        }
    }

    public function name(string $email): string
    {
        $key = strtolower(trim($email));

        return $this->names[$key] ?? $email;
    }

    public function formatted(string $email): string
    {
        $name = $this->name($email);

        if (strcasecmp($name, $email) === 0) {
            return $email;
        }

        return "{$name} <{$email}>";
    }

    /**
     * @param  array<int, string|null>|null  $emails
     */
    public function formattedList(?array $emails): string
    {
        return collect($emails)
            ->filter()
            ->map(fn (string $email) => $this->formatted($email))
            ->join('; ');
    }

    public function formattedParticipant(ReportParticipant $participant): string
    {
        if (filled($participant->name)) {
            return "{$participant->name} <{$participant->email}>";
        }

        return $this->formatted($participant->email);
    }

    /**
     * @param  Collection<int, ReportParticipant>|iterable<int, ReportParticipant>  $participants
     */
    public function formattedParticipants(iterable $participants): string
    {
        return collect($participants)
            ->map(fn (ReportParticipant $participant) => $this->formattedParticipant($participant))
            ->join('; ');
    }

    public function initial(string $email): string
    {
        $name = $this->name($email);

        return strtoupper(substr($name, 0, 1));
    }
}
