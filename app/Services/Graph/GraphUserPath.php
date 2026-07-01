<?php

namespace App\Services\Graph;

final class GraphUserPath
{
    public static function for(string $mailbox): string
    {
        return rawurlencode($mailbox);
    }
}
