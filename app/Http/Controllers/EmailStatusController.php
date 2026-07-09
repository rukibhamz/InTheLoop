<?php

namespace App\Http\Controllers;

use App\Enums\EmailStatus;
use App\Http\Requests\UpdateEmailStatusRequest;
use App\Models\Email;
use App\Models\EmailEvent;
use App\Jobs\SendEmailStatusNotification;
use Illuminate\Http\RedirectResponse;

class EmailStatusController extends Controller
{
    public function update(UpdateEmailStatusRequest $request, Email $email): RedirectResponse
    {
        $this->authorize('updateStatus', $email);

        $newStatus = EmailStatus::from($request->string('status')->toString());
        $previous = $email->status;

        if ($previous === $newStatus) {
            return back()->with('success', 'Status unchanged.');
        }

        $email->update(['status' => $newStatus]);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'status_changed',
            'meta' => [
                'from' => $previous->value,
                'to' => $newStatus->value,
                'user_id' => $request->user()->id,
                'manual' => true,
            ],
        ]);

        SendEmailStatusNotification::dispatch($email, $newStatus->label(), $request->user()->id);

        return back()->with('success', 'Email status updated to '.$newStatus->label().'.');
    }
}
