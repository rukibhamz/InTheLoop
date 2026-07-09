<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class PostLoginRedirect
{
    /**
     * Path segments that must never be used as a post-login redirect (e.g. assets hit from the login page).
     *
     * @var list<string>
     */
    private const BLOCKED_PATH_SEGMENTS = [
        '/branding/',
        '/api/',
        '/attachments/',
    ];

    public static function to(): RedirectResponse
    {
        $default = route('emails.index');

        if (! session()->has('url.intended')) {
            return redirect()->to($default);
        }

        $intended = session()->pull('url.intended');

        if (! is_string($intended) || ! self::isAllowed($intended)) {
            return redirect()->to($default);
        }

        return redirect()->to($intended);
    }

    private static function isAllowed(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        foreach (self::BLOCKED_PATH_SEGMENTS as $segment) {
            if (str_contains($path, $segment)) {
                return false;
            }
        }

        return true;
    }
}
