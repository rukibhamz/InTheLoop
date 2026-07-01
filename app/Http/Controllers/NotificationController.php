<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = ReportEvent::query()
            ->with('report:id,subject')
            ->whereIn('type', ['sent', 'approved', 'rejected', 'replied', 'created'])
            ->whereHas('report', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('participants', function ($participantQuery) use ($user) {
                        $participantQuery->where('user_id', $user->id)
                            ->orWhere('email', $user->email);
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (ReportEvent $event) => [
                'id' => $event->id,
                'type' => $event->type,
                'subject' => $event->report?->subject,
                'report_id' => $event->report_id,
                'time' => $event->created_at->diffForHumans(),
                'url' => $event->report_id ? route('reports.show', $event->report_id) : null,
            ]);

        return response()->json(['notifications' => $events]);
    }
}
