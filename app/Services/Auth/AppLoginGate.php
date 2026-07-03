<?php

namespace App\Services\Auth;

use App\Models\User;

class AppLoginGate
{
    public function userMayAuthenticate(User $user): bool
    {
        return (bool) $user->is_active;
    }
}
