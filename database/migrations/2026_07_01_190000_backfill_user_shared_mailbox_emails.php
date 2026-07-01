<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereNull('shared_mailbox_email')
            ->whereNotNull('email')
            ->each(function (User $user) {
                $user->update(['shared_mailbox_email' => $user->email]);
            });
    }

    public function down(): void
    {
        // No-op
    }
};
