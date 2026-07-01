<?php

namespace App\Http\Controllers;

use App\Enums\ReportStatus;
use App\Http\Requests\UpdateReportStatusRequest;
use App\Models\Report;
use App\Models\ReportEvent;
use App\Jobs\SendReportStatusNotification;
use Illuminate\Http\RedirectResponse;

class ReportStatusController extends Controller
{
    public function update(UpdateReportStatusRequest $request, Report $report): RedirectResponse
    {
        $this->authorize('updateStatus', $report);

        $newStatus = ReportStatus::from($request->string('status')->toString());
        $previous = $report->status;

        if ($previous === $newStatus) {
            return back()->with('success', 'Status unchanged.');
        }

        $report->update(['status' => $newStatus]);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'status_changed',
            'meta' => [
                'from' => $previous->value,
                'to' => $newStatus->value,
                'user_id' => $request->user()->id,
                'manual' => true,
            ],
        ]);

        SendReportStatusNotification::dispatch($report, $newStatus->label(), $request->user()->id);

        return back()->with('success', 'Report status updated to '.$newStatus->label().'.');
    }
}
