<?php

namespace App\Policies;

use App\Models\Email;
use App\Models\User;

class EmailPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Email $email): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($email->user_id === $user->id) {
            return true;
        }

        return $email->participants()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function reply(User $user, Email $email): bool
    {
        return $this->view($user, $email);
    }

    public function approve(User $user, Email $email): bool
    {
        return $user->isApprover();
    }

    public function updateStatus(User $user, Email $email): bool
    {
        return $user->isAdmin();
    }
}
