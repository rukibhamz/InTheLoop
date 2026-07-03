<?php

namespace App\Http\Controllers;

use App\Jobs\SyncDirectoryContacts;
use App\Models\DirectoryContact;
use App\Models\Recipient;
use App\Models\User;
use App\Services\Graph\GraphDirectorySearch;
use App\Services\Graph\GraphSettings;
use App\Support\DirectoryContactFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DirectoryContactController extends Controller
{
    public function search(
        Request $request,
        GraphSettings $settings,
        GraphDirectorySearch $graphSearch
    ): JsonResponse {
        $query = trim((string) $request->string('q'));

        if (strlen($query) < 1) {
            return response()->json(['results' => []]);
        }

        $this->queueSyncIfNeeded($settings);

        $results = $this->searchLocal($query);

        if ($results->isEmpty() && $settings->isConfigured()) {
            $results = $graphSearch->search($query);
        }

        if (filter_var($query, FILTER_VALIDATE_EMAIL) && $results->where('email', $query)->isEmpty()) {
            $results->push(array_merge(
                DirectoryContactFormatter::entry($query, $query, null),
                ['custom' => true]
            ));
        }

        return response()->json(['results' => $results->values()]);
    }

    /**
     * @return Collection<int, array{email: string, name: string, job_title: ?string, label: string, custom?: bool}>
     */
    private function searchLocal(string $query): Collection
    {
        $recipients = Recipient::query()
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('shared_mailbox_email', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (Recipient $recipient) => DirectoryContactFormatter::entry(
                $recipient->shared_mailbox_email,
                $recipient->name,
                $recipient->role
            ));

        $contacts = DirectoryContact::query()
            ->where(function ($builder) use ($query) {
                $builder->where('display_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('job_title', 'like', "%{$query}%");
            })
            ->orderBy('display_name')
            ->limit(10)
            ->get()
            ->map(fn (DirectoryContact $contact) => DirectoryContactFormatter::entry(
                $contact->email,
                $contact->display_name,
                $contact->job_title
            ));

        $users = User::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('shared_mailbox_email', 'like', "%{$query}%")
                    ->orWhereIn('email', DirectoryContact::query()
                        ->where('job_title', 'like', "%{$query}%")
                        ->select('email'))
                    ->orWhereIn('email', DirectoryContact::query()
                        ->where('display_name', 'like', "%{$query}%")
                        ->select('email'));
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        $jobTitles = DirectoryContact::query()
            ->whereIn('email', $users->pluck('email'))
            ->pluck('job_title', 'email');

        $userResults = $users->flatMap(function (User $user) use ($jobTitles) {
            $jobTitle = $jobTitles[$user->email] ?? null;
            $entries = [DirectoryContactFormatter::entry($user->email, $user->name, $jobTitle)];

            if (filled($user->shared_mailbox_email) && strcasecmp($user->shared_mailbox_email, $user->email) !== 0) {
                $entries[] = DirectoryContactFormatter::entry(
                    $user->shared_mailbox_email,
                    $user->name,
                    $jobTitle
                );
            }

            return $entries;
        });

        return $recipients
            ->concat($contacts)
            ->concat($userResults)
            ->unique('email')
            ->take(10)
            ->values();
    }

    private function queueSyncIfNeeded(GraphSettings $settings): void
    {
        if (! $settings->isConfigured()) {
            return;
        }

        $latestSync = DirectoryContact::query()->max('last_synced_at');
        $isStale = $latestSync === null || now()->parse($latestSync)->lt(now()->subDay());
        $isSparse = DirectoryContact::query()->count() < 20;

        if ($isStale || $isSparse) {
            SyncDirectoryContacts::dispatch();
        }
    }
}
