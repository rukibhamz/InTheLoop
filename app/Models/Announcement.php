<?php

namespace App\Models;

use App\Support\EmailReplyStripper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Announcement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'mailbox',
        'from_email',
        'from_name',
        'to_emails',
        'cc_emails',
        'subject',
        'body_html',
        'body_text',
        'graph_message_id',
        'internet_message_id',
        'conversation_id',
        'folder',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'to_emails' => 'array',
            'cc_emails' => 'array',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Announcement $announcement) {
            $announcement->created_at ??= now();
        });
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
