<?php

namespace App\Http\Controllers;

use App\Enums\MessageDirection;
use App\Enums\ParticipantType;
use App\Enums\EmailStatus;
use App\Http\Requests\StoreEmailRequest;
use App\Jobs\SendOutboundEmail;
use App\Models\DirectoryContact;
use App\Models\Email;
use App\Models\EmailCategory;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailParticipant;
use App\Models\User;
use App\Support\DirectoryDisplayResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $baseQuery = Email::query()
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($visibility) use ($user) {
                    $visibility->where('user_id', $user->id)
                        ->orWhereHas('participants', function ($participantQuery) use ($user) {
                            $participantQuery->where('user_id', $user->id)
                                ->orWhere('email', $user->email);
                        });
                });
            });

        $stats = [
            'sent' => EmailMessage::query()
                ->where('direction', MessageDirection::Outbound)
                ->whereHas('email', fn ($query) => $this->applyEmailVisibility($query, $user))
                ->count(),
            'replied' => EmailMessage::query()
                ->where('direction', MessageDirection::Inbound)
                ->where('show_in_thread', true)
                ->whereHas('email', fn ($query) => $this->applyEmailVisibility($query, $user))
                ->count(),
        ];

        $emails = (clone $baseQuery)
            ->with(['category', 'participants', 'user'])
            ->withCount('messages')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('email.index', compact('emails', 'stats'));
    }

    public function create(): View
    {
        return view('email.create');
    }

    public function store(StoreEmailRequest $request): RedirectResponse
    {
        $email = DB::transaction(function () use ($request) {
            $email = Email::query()->create([
                'user_id' => $request->user()->id,
                'category_id' => $this->defaultCategoryId(),
                'subject' => $request->string('subject')->toString(),
                'body' => $request->string('body')->toString(),
                'status' => EmailStatus::Pending,
            ]);

            $this->syncParticipant($email, $request->input('to'), ParticipantType::To);
            foreach ($request->input('cc', []) as $cc) {
                if (! empty($cc['email'])) {
                    $this->syncParticipant($email, $cc, ParticipantType::Cc);
                }
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/'.$email->id, 'local');
                    $email->attachments()->create([
                        'path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            EmailEvent::query()->create([
                'email_id' => $email->id,
                'type' => 'created',
                'meta' => ['user_id' => $request->user()->id],
            ]);

            return $email;
        });

        SendOutboundEmail::dispatch($email);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'queued',
        ]);

        return redirect()
            ->route('emails.show', $email)
            ->with('success', 'Email submitted. It will be sent shortly.');
    }

    public function show(Email $email): View
    {
        $this->authorize('view', $email);

        $email->load(['participants', 'threadMessages.attachments', 'attachments', 'user', 'events']);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'viewed',
            'meta' => ['user_id' => auth()->id()],
        ]);

        $directory = DirectoryDisplayResolver::forEmail($email);

        return view('email.show', compact('email', 'directory'));
    }

    private function syncParticipant(Email $email, array $participant, ParticipantType $type): void
    {
        $participantEmail = $participant['email'];
        $name = $participant['name'] ?? null;

        if (! filled($name)) {
            $name = DirectoryContact::query()
                ->whereRaw('lower(email) = ?', [strtolower($participantEmail)])
                ->value('display_name')
                ?? User::query()->whereRaw('lower(email) = ?', [strtolower($participantEmail)])->value('name');
        }

        EmailParticipant::query()->create([
            'email_id' => $email->id,
            'email' => $participantEmail,
            'name' => $name,
            'type' => $type,
            'user_id' => User::query()->where('email', $participantEmail)->value('id'),
        ]);
    }

    private function defaultCategoryId(): int
    {
        return EmailCategory::query()->firstOrCreate(
            ['name' => 'General'],
            ['description' => 'Uncategorized emails']
        )->id;
    }

    private function applyEmailVisibility($query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $query->where(function ($visibility) use ($user) {
            $visibility->where('user_id', $user->id)
                ->orWhereHas('participants', function ($participantQuery) use ($user) {
                    $participantQuery->where('user_id', $user->id)
                        ->orWhere('email', $user->email);
                });
        });
    }
}
