<?php

namespace App\Models;

use App\Enums\MessageDirection;
use App\Support\EmailReplyStripper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmailMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_id',
        'direction',
        'mailbox',
        'from_email',
        'to_emails',
        'cc_emails',
        'subject',
        'body_html',
        'body_text',
        'graph_message_id',
        'internet_message_id',
        'conversation_id',
        'email_pending',
        'show_in_thread',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'email_pending' => 'boolean',
            'show_in_thread' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailMessage $message) {
            $message->created_at ??= now();
        });
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function displayBody(): string
    {
        if (filled($this->body_html)) {
            return EmailReplyStripper::stripHtml($this->body_html);
        }

        if (filled($this->body_text)) {
            return EmailReplyStripper::strip($this->body_text);
        }

        return '';
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
