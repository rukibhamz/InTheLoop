<?php

namespace App\Http\Controllers;

use App\Enums\ParticipantType;
use App\Enums\ReportStatus;
use App\Http\Requests\StoreReportRequest;
use App\Jobs\SendReportEmail;
use App\Models\DirectoryContact;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportEvent;
use App\Models\ReportParticipant;
use App\Models\User;
use App\Support\DirectoryDisplayResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $baseQuery = Report::query()
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($visibility) use ($user) {
                    $visibility->where('user_id', $user->id)
                        ->orWhereHas('participants', function ($participantQuery) use ($user) {
                            $participantQuery->where('user_id', $user->id)
                                ->orWhere('email', $user->email);
                        });
                });
            });

        $approvedReports = (clone $baseQuery)
            ->whereNotNull('approved_at')
            ->get(['created_at', 'approved_at']);

        $stats = [
            'sent' => (clone $baseQuery)->where('status', ReportStatus::Sent)->count(),
            'completed' => (clone $baseQuery)->whereIn('status', [
                ReportStatus::Approved,
                ReportStatus::Resolved,
            ])->count(),
            'average_turnaround_days' => $approvedReports->isEmpty()
                ? 0
                : round($approvedReports->avg(fn (Report $report) => $report->created_at->diffInDays($report->approved_at)), 1),
        ];

        $reports = (clone $baseQuery)
            ->with(['category', 'participants', 'user'])
            ->withCount('messages')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('reports.index', compact('reports', 'stats'));
    }

    public function create(): View
    {
        return view('reports.create');
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $report = DB::transaction(function () use ($request) {
            $report = Report::query()->create([
                'user_id' => $request->user()->id,
                'category_id' => $this->defaultCategoryId(),
                'subject' => $request->string('subject')->toString(),
                'body' => $request->string('body')->toString(),
                'status' => ReportStatus::Pending,
            ]);

            $this->syncParticipant($report, $request->input('to'), ParticipantType::To);
            foreach ($request->input('cc', []) as $cc) {
                if (! empty($cc['email'])) {
                    $this->syncParticipant($report, $cc, ParticipantType::Cc);
                }
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('attachments/'.$report->id, 'local');
                    $report->attachments()->create([
                        'path' => $path,
                        'original_filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            ReportEvent::query()->create([
                'report_id' => $report->id,
                'type' => 'created',
                'meta' => ['user_id' => $request->user()->id],
            ]);

            return $report;
        });

        SendReportEmail::dispatch($report);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'queued',
        ]);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report submitted. It will be emailed shortly.');
    }

    public function show(Report $report): View
    {
        $this->authorize('view', $report);

        $report->load(['participants', 'threadMessages.attachments', 'attachments', 'user', 'events']);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'viewed',
            'meta' => ['user_id' => auth()->id()],
        ]);

        $directory = DirectoryDisplayResolver::forReport($report);

        return view('reports.show', compact('report', 'directory'));
    }

    private function syncParticipant(Report $report, array $participant, ParticipantType $type): void
    {
        $email = $participant['email'];
        $name = $participant['name'] ?? null;

        if (! filled($name)) {
            $name = DirectoryContact::query()
                ->whereRaw('lower(email) = ?', [strtolower($email)])
                ->value('display_name')
                ?? User::query()->whereRaw('lower(email) = ?', [strtolower($email)])->value('name');
        }

        ReportParticipant::query()->create([
            'report_id' => $report->id,
            'email' => $email,
            'name' => $name,
            'type' => $type,
            'user_id' => User::query()->where('email', $email)->value('id'),
        ]);
    }

    private function defaultCategoryId(): int
    {
        return ReportCategory::query()->firstOrCreate(
            ['name' => 'General'],
            ['description' => 'Uncategorized reports']
        )->id;
    }
}
