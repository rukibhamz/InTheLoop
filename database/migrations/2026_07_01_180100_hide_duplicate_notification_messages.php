<?php

use App\Models\ReportMessage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ReportMessage::query()
            ->with(['report.user'])
            ->where('direction', 'inbound')
            ->where('show_in_thread', true)
            ->get()
            ->each(function (ReportMessage $message) {
                $report = $message->report;
                $user = $report?->user;

                if (! $report || ! $user || $message->subject !== $report->subject) {
                    return;
                }

                $from = strtolower($message->from_email);
                $matchesSubmitter = $from === strtolower($user->email)
                    || ($user->shared_mailbox_email && $from === strtolower($user->shared_mailbox_email));

                if ($matchesSubmitter) {
                    $message->update(['show_in_thread' => false]);
                }
            });
    }

    public function down(): void
    {
        // No-op
    }
};
