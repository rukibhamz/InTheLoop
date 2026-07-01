<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectoryContact extends Model
{
    protected $fillable = [
        'azure_object_id',
        'display_name',
        'email',
        'department',
        'job_title',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }
}
