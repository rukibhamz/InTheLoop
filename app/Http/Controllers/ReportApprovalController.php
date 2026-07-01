<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Jobs\SendReportStatusNotification;
use App\Models\Report;
use App\Models\ReportEvent;
use App\Services\ApprovalToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportApprovalController extends Controller
{
    public function showLink(Request $request, Report $report, string $token, ApprovalToken $approvalToken): View|RedirectResponse
    {
        if (! $approvalToken->matches($report, $token)) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        if (! $request->user()) {
            return redirect()->guest(route('login', [
                'redirect' => route('reports.approve.link', ['report' => $report, 'token' => $token]),
            ]));
        }

        $this->authorize('approve', $report);

        return view('reports.approve', compact('report', 'token'));
    }

    public function approve(Request $request, Report $report, ApprovalToken $approvalToken): RedirectResponse
    {
        $this->authorize('approve', $report);

        if ($request->filled('token') && ! $approvalToken->matches($report, $request->string('token')->toString())) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        if (in_array($report->status, [ReportStatus::Approved, ReportStatus::Rejected, ReportStatus::Resolved], true)) {
            return redirect()
                ->route('reports.show', $report)
                ->with('success', 'This report has already been finalized.');
        }

        $report->update([
            'status' => ReportStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'approved',
            'meta' => ['user_id' => $request->user()->id],
        ]);

        SendReportStatusNotification::dispatch($report, ReportStatus::Approved->label(), $request->user()->id);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report approved.');
    }

    public function reject(Request $request, Report $report, ApprovalToken $approvalToken): RedirectResponse
    {
        $this->authorize('approve', $report);

        if ($request->filled('token') && ! $approvalToken->matches($report, $request->string('token')->toString())) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        $report->update([
            'status' => ReportStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'rejected',
            'meta' => ['user_id' => $request->user()->id],
        ]);

        SendReportStatusNotification::dispatch($report, ReportStatus::Rejected->label(), $request->user()->id);

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Report rejected.');
    }
}
