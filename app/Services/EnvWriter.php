<?php

namespace App\Services;

use RuntimeException;

class EnvWriter
{
    public function __construct(
        private readonly ?string $path = null,
    ) {}

    private function envPath(): string
    {
        return $this->path ?? base_path('.env');
    }

    public function get(string $key): ?string
    {
        if (! file_exists($this->envPath())) {
            return null;
        }

        $pattern = '/^'.preg_quote($key, '/').'=(.*)$/m';

        if (preg_match($pattern, file_get_contents($this->envPath()), $matches)) {
            return $this->unquote(trim($matches[1]));
        }

        return null;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values): void
    {
        if (! file_exists($this->envPath())) {
            if (! file_exists(base_path('.env.example'))) {
                throw new RuntimeException('.env file is missing and no .env.example template was found.');
            }

            copy(base_path('.env.example'), $this->envPath());
        }

        if (! is_writable($this->envPath())) {
            throw new RuntimeException('The .env file is not writable. Check file permissions and try again.');
        }

        $contents = file_get_contents($this->envPath());

        foreach ($values as $key => $value) {
            $contents = $this->setInContents($contents, $key, $value);
        }

        file_put_contents($this->envPath(), $contents);
    }

    public function set(string $key, ?string $value): void
    {
        $this->setMany([$key => $value]);
    }

    private function setInContents(string $contents, string $key, ?string $value): string
    {
        $line = $key.'='.$this->quote($value);
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            return preg_replace($pattern, $line, $contents);
        }

        return rtrim($contents).PHP_EOL.$line.PHP_EOL;
    }

    private function quote(?string $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if ($value === '') {
            return '""';
        }

        if (preg_match('/[\s#="\']/', $value) || str_contains($value, '\\')) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    private function unquote(string $value): string
    {
        if ($value === 'null') {
            return '';
        }

        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }
}
