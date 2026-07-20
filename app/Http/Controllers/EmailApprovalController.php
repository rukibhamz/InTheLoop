<?php

namespace App\Http\Controllers;

use App\Enums\EmailStatus;
use App\Jobs\SendEmailStatusNotification;
use App\Models\Email;
use App\Models\EmailEvent;
use App\Services\ApprovalToken;
use App\Support\QueueWorkerKick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailApprovalController extends Controller
{
    public function showLink(Request $request, Email $email, string $token, ApprovalToken $approvalToken): View|RedirectResponse
    {
        if (! $approvalToken->matches($email, $token)) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        if (! $request->user()) {
            return redirect()->guest(route('login', [
                'redirect' => route('emails.approve.link', ['email' => $email, 'token' => $token]),
            ]));
        }

        $this->authorize('approve', $email);

        return view('email.approve', compact('email', 'token'));
    }

    public function approve(Request $request, Email $email, ApprovalToken $approvalToken): RedirectResponse
    {
        $this->authorize('approve', $email);

        if ($request->filled('token') && ! $approvalToken->matches($email, $request->string('token')->toString())) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        if (in_array($email->status, [EmailStatus::Approved, EmailStatus::Rejected, EmailStatus::Resolved], true)) {
            return redirect()
                ->route('emails.show', $email)
                ->with('success', 'This email has already been finalized.');
        }

        $email->update([
            'status' => EmailStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'approved',
            'meta' => ['user_id' => $request->user()->id],
        ]);

        SendEmailStatusNotification::dispatch($email, EmailStatus::Approved->label(), $request->user()->id);
        QueueWorkerKick::afterMail();

        return redirect()
            ->route('emails.show', $email)
            ->with('success', 'Email approved.');
    }

    public function reject(Request $request, Email $email, ApprovalToken $approvalToken): RedirectResponse
    {
        $this->authorize('approve', $email);

        if ($request->filled('token') && ! $approvalToken->matches($email, $request->string('token')->toString())) {
            abort(403, 'This approval link is invalid or has expired.');
        }

        $email->update([
            'status' => EmailStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'rejected',
            'meta' => ['user_id' => $request->user()->id],
        ]);

        SendEmailStatusNotification::dispatch($email, EmailStatus::Rejected->label(), $request->user()->id);
        QueueWorkerKick::afterMail();

        return redirect()
            ->route('emails.show', $email)
            ->with('success', 'Email rejected.');
    }
}
