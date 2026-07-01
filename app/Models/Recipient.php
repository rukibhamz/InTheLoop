<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    protected $fillable = [
        'name',
        'shared_mailbox_email',
        'department',
        'role',
    ];
}
