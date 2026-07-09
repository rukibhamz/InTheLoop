<?php

use App\Models\EmailMessage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailMessage::query()
            ->with(['email.user'])
            ->where('direction', 'inbound')
            ->where('show_in_thread', true)
            ->get()
            ->each(function (EmailMessage $message) {
                $email = $message->email;
                $user = $email?->user;

                if (! $email || ! $user || $message->subject !== $email->subject) {
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
