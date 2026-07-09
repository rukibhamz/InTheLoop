<?php

namespace App\Models;

use App\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Email extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'subject',
        'body',
        'status',
        'conversation_id',
        'sent_at',
        'approved_by',
        'approved_at',
        'approval_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailStatus::class,
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmailCategory::class, 'category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EmailParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class)->orderBy('created_at');
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(EmailMessage::class)
            ->where('show_in_thread', true)
            ->orderBy('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class)->orderBy('created_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
