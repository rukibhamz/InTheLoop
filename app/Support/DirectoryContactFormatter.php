<?php

namespace App\Support;

class DirectoryContactFormatter
{
    public static function label(string $name, ?string $jobTitle): string
    {
        $name = trim($name);
        $jobTitle = filled($jobTitle) ? trim($jobTitle) : null;

        return $jobTitle ? "{$name} — {$jobTitle}" : $name;
    }

    /**
     * @return array{email: string, name: string, job_title: ?string, label: string}
     */
    public static function entry(string $email, string $name, ?string $jobTitle): array
    {
        return [
            'email' => $email,
            'name' => $name,
            'job_title' => $jobTitle,
            'label' => self::label($name, $jobTitle),
        ];
    }
}
