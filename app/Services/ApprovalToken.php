<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Support\Str;

class ApprovalToken
{
    public function generate(Report $report): string
    {
        $plain = Str::random(64);

        $report->forceFill([
            'approval_token_hash' => hash('sha256', $plain),
        ])->save();

        return $plain;
    }

    public function matches(Report $report, string $plain): bool
    {
        if (! filled($report->approval_token_hash)) {
            return false;
        }

        return hash_equals($report->approval_token_hash, hash('sha256', $plain));
    }

    public function url(Report $report, string $plain): string
    {
        return route('reports.approve.link', [
            'report' => $report,
            'token' => $plain,
        ]);
    }
}
