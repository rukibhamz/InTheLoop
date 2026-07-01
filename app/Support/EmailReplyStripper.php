<?php

namespace App\Support;

class EmailReplyStripper
{
    public static function strip(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        if ($text === '') {
            return '';
        }

        $patterns = [
            '/\n_{3,}\s*\n.*/s',
            '/\n-{3,}\s*\n.*/s',
            '/\nFrom:\s.+?\nSent:\s.*/is',
            '/\n-+\s*Original Message\s*-+.*/is',
            '/\nOn .+ wrote:\s*\n.*/is',
        ];

        foreach ($patterns as $pattern) {
            $stripped = preg_replace($pattern, '', $text);

            if (is_string($stripped)) {
                $text = trim($stripped);
            }
        }

        return self::normalizeWhitespace($text);
    }

    public static function stripHtml(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $cutPatterns = [
            '/<div[^>]*\bid=["\']divRplyFwdMsg["\'][^>]*>/i',
            '/<hr\b/i',
            '/<blockquote\b/i',
            '/<div[^>]*\bclass=["\'][^"\']*gmail_quote[^"\']*["\'][^>]*>/i',
        ];

        foreach ($cutPatterns as $pattern) {
            if (preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE)) {
                $html = substr($html, 0, $match[0][1]);
            }
        }

        return self::strip(self::htmlToText($html));
    }

    private static function htmlToText(string $html): string
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|div|li|tr|h[1-6]|blockquote)>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<(p|div|li|tr|h[1-6]|blockquote)(\s[^>]*)?>/i', '', $html) ?? $html;

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);

        return self::normalizeWhitespace($text);
    }

    private static function normalizeWhitespace(string $text): string
    {
        $lines = explode("\n", $text);
        $lines = array_map(
            static fn (string $line): string => trim(preg_replace('/[ \t]+/', ' ', $line) ?? $line),
            $lines
        );

        $text = trim(implode("\n", $lines));

        return preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    }
}
