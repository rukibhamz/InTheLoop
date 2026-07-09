<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCategory extends Model
{
    protected $fillable = ['name', 'description', 'default_recipient_id'];

    public function defaultRecipient(): BelongsTo
    {
        return $this->belongsTo(Recipient::class, 'default_recipient_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class, 'category_id');
    }
}
