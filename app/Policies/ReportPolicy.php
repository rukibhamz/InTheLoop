<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($report->user_id === $user->id) {
            return true;
        }

        return $report->participants()
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

    public function reply(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    public function approve(User $user, Report $report): bool
    {
        return $user->isApprover();
    }

    public function updateStatus(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }
}
