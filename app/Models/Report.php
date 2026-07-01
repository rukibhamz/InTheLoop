<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Report extends Model
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
            'status' => ReportStatus::class,
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
        return $this->belongsTo(ReportCategory::class, 'category_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ReportParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ReportMessage::class)->orderBy('created_at');
    }

    public function threadMessages(): HasMany
    {
        return $this->hasMany(ReportMessage::class)
            ->where('show_in_thread', true)
            ->orderBy('created_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReportEvent::class)->orderBy('created_at');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
