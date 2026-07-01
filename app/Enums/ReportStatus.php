<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Resolved => 'Resolved',
        };
    }
}
