<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportReplyRequest;
use App\Enums\MessageDirection;
use App\Jobs\SendGraphReply;
use App\Models\Report;
use App\Models\ReportEvent;
use App\Models\ReportMessage;
use App\Services\Graph\GraphReplySender;
use Illuminate\Http\RedirectResponse;

class ReportReplyController extends Controller
{
    public function store(
        StoreReportReplyRequest $request,
        Report $report,
        GraphReplySender $graphReply
    ): RedirectResponse {
        $this->authorize('reply', $report);

        $user = $request->user();
        $mailbox = $user->shared_mailbox_email ?: $user->email;
        $body = $request->string('body')->toString();

        $message = ReportMessage::query()->create([
            'report_id' => $report->id,
            'direction' => MessageDirection::Outbound,
            'mailbox' => $mailbox,
            'from_email' => $user->email,
            'to_emails' => $report->participants()->where('type', 'to')->pluck('email')->all(),
            'cc_emails' => $report->participants()->where('type', 'cc')->pluck('email')->all(),
            'subject' => 'Re: '.$report->subject,
            'body_text' => $body,
            'body_html' => nl2br(e($body)),
            'conversation_id' => $report->conversation_id,
            'email_pending' => $graphReply->isConfigured() && filled($user->shared_mailbox_email),
        ]);

        ReportEvent::query()->create([
            'report_id' => $report->id,
            'type' => 'replied',
            'meta' => ['user_id' => $user->id, 'in_app' => true],
        ]);

        if ($report->status->value === 'sent') {
            $report->update(['status' => 'in_review']);
        }

        if ($graphReply->isConfigured() && filled($user->shared_mailbox_email)) {
            $result = $graphReply->replyAll($report, $user, $body);

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
