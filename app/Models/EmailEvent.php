<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_id',
        'type',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmailEvent $event) {
            $event->created_at ??= now();
        });
    }

    public function email(): BelongsTo
    {
        return $this->belongsTo(Email::class);
    }
}
