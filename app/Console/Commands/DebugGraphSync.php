<?php

namespace App\Console\Commands;

use App\Models\Email;
use App\Models\EmailMessage;
use App\Services\Graph\GraphMailSync;
use App\Services\Graph\GraphSettings;
use App\Services\Graph\GraphTokenService;
use App\Services\Graph\GraphUserPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugGraphSync extends Command
{
    protected $signature = 'graph:debug-sync {mailbox?}';

    protected $description = 'Inspect recent Graph mail and report matching for reply sync';

    public function handle(GraphSettings $settings, GraphTokenService $tokens, GraphMailSync $sync): int
    {
        $this->info('Recent emails:');
        foreach (Email::query()->latest()->take(5)->get() as $email) {
            $this->line("  #{$email->id} [{$email->status->value}] {$email->subject}");
            $this->line("    conversation_id: ".($email->conversation_id ?: 'null'));
            $this->line('    messages: '.EmailMessage::where('email_id', $email->id)->count());
        }

        $mailbox = $this->argument('mailbox') ?: $settings->defaultSenderMailbox();

        if (! $mailbox) {
            $this->warn('No mailbox specified.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Recent messages in {$mailbox}:");

        $token = $tokens->getAppToken();
        $userPath = GraphUserPath::for($mailbox);
        $select = 'conversationId,from,subject,receivedDateTime,sentDateTime';

        foreach (['inbox', 'sentitems'] as $folder) {
            $dateField = $folder === 'sentitems' ? 'sentDateTime' : 'receivedDateTime';
            $url = config('graph.base_url')."/users/{$userPath}/mailFolders/{$folder}/messages"
                .'?$top=10&$orderby='.urlencode("{$dateField} desc").'&$select='.urlencode($select);

            try {
                $messages = Http::withToken($token)->timeout(30)->get($url)->throw()->json('value') ?? [];
            } catch (\Throwable $e) {
                $this->warn("  {$folder}: failed — {$e->getMessage()}");

                continue;
            }

            $this->line("  {$folder}:");

            foreach ($messages as $message) {
                $from = $message['from']['emailAddress']['address'] ?? '?';
                $subject = $message['subject'] ?? '(no subject)';
                $conv = substr($message['conversationId'] ?? 'null', 0, 24).'…';
                $matched = $sync->debugMatchEmail($message)?->id;
                $this->line("    - {$subject}");
                $this->line("      from: {$from} | conv: {$conv} | matches report: ".($matched ?: 'no'));
            }
        }

        return self::SUCCESS;
    }
}
