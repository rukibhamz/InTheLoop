<?php

namespace App\Services;

use App\Models\Email;
use Illuminate\Support\Str;

class ApprovalToken
{
    public function generate(Email $email): string
    {
        $plain = Str::random(64);

        $email->forceFill([
            'approval_token_hash' => hash('sha256', $plain),
        ])->save();

        return $plain;
    }

    public function matches(Email $email, string $plain): bool
    {
        if (! filled($email->approval_token_hash)) {
            return false;
        }

        return hash_equals($email->approval_token_hash, hash('sha256', $plain));
    }

    public function url(Email $email, string $plain): string
    {
        return route('emails.approve.link', [
            'email' => $email,
            'token' => $plain,
        ]);
    }
}
