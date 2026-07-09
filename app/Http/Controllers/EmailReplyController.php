<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailReplyRequest;
use App\Enums\MessageDirection;
use App\Jobs\SendGraphReply;
use App\Models\Email;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Services\Graph\GraphReplySender;
use Illuminate\Http\RedirectResponse;

class EmailReplyController extends Controller
{
    public function store(
        StoreEmailReplyRequest $request,
        Email $email,
        GraphReplySender $graphReply
    ): RedirectResponse {
        $this->authorize('reply', $email);

        $user = $request->user();
        $mailbox = $user->shared_mailbox_email ?: $user->email;
        $body = $request->string('body')->toString();

        $message = EmailMessage::query()->create([
            'email_id' => $email->id,
            'direction' => MessageDirection::Outbound,
            'mailbox' => $mailbox,
            'from_email' => $user->email,
            'to_emails' => $email->participants()->where('type', 'to')->pluck('email')->all(),
            'cc_emails' => $email->participants()->where('type', 'cc')->pluck('email')->all(),
            'subject' => 'Re: '.$email->subject,
            'body_text' => $body,
            'body_html' => nl2br(e($body)),
            'conversation_id' => $email->conversation_id,
            'email_pending' => $graphReply->isConfigured() && filled($user->shared_mailbox_email),
        ]);

        EmailEvent::query()->create([
            'email_id' => $email->id,
            'type' => 'replied',
            'meta' => ['user_id' => $user->id, 'in_app' => true],
        ]);

        if ($email->status->value === 'sent') {
            $email->update(['status' => 'in_review']);
        }

        if ($graphReply->isConfigured() && filled($user->shared_mailbox_email)) {
            $result = $graphReply->replyAll($email, $user, $body);

            if ($result['sent']) {
                $message->update(['email_pending' => false]);
            } else {
                SendGraphReply::dispatch($message, $user);
            }
        }

        $success = match (true) {
            ! $graphReply->isConfigured() => 'Reply posted to the conversation.',
            $message->email_pending => 'Reply saved in-app. Email delivery will complete once your mailbox copy syncs.',
            default => 'Reply posted and sent to the email thread.',
        };

        return back()->with('success', $success);
    }
}
