<?php

namespace App\Http\Controllers;

use App\Models\EmailEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = EmailEvent::query()
            ->with('email:id,subject')
            ->whereIn('type', ['sent', 'approved', 'rejected', 'replied', 'created'])
            ->whereHas('email', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('participants', function ($participantQuery) use ($user) {
                        $participantQuery->where('user_id', $user->id)
                            ->orWhere('email', $user->email);
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (EmailEvent $event) => [
                'id' => $event->id,
                'type' => $event->type,
                'subject' => $event->email?->subject,
                'email_id' => $event->email_id,
                'time' => $event->created_at->diffForHumans(),
                'url' => $event->email_id ? route('emails.show', $event->email_id) : null,
            ]);

        return response()->json(['notifications' => $events]);
    }
}
