<?php

namespace App\Models;

use App\Enums\ParticipantType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailParticipant extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_id',
        'email',
        'name',
        'type',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ParticipantType::class,
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailParticipant $participant) {
            $participant->created_at ??= now();
        });
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
