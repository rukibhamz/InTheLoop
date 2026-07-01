<?php

namespace App\Services;

class RequirementsChecker
{
    /**
     * @return array<int, array{name: string, passed: bool, message: string}>
     */
    public function check(): array
    {
        return [
            $this->phpVersion(),
            $this->extension('pdo'),
            $this->extension('mbstring'),
            $this->extension('openssl'),
            $this->extension('tokenizer'),
            $this->extension('xml'),
            $this->extension('ctype'),
            $this->extension('json'),
            $this->extension('fileinfo'),
            $this->writable('storage'),
            $this->writable('bootstrap/cache'),
            $this->envWritable(),
        ];
    }

    public function passes(): bool
    {
        return collect($this->check())->every(fn (array $item) => $item['passed']);
    }

    private function phpVersion(): array
    {
        $passed = PHP_VERSION_ID >= 80200;

        return [
            'name' => 'PHP 8.2+',
            'passed' => $passed,
            'message' => $passed ? PHP_VERSION : 'PHP '.PHP_VERSION.' detected. Upgrade to PHP 8.2 or newer.',
        ];
    }

    private function extension(string $extension): array
    {
        $passed = extension_loaded($extension);

        return [
            'name' => "PHP extension: {$extension}",
            'passed' => $passed,
            'message' => $passed ? 'Available' : "Missing required PHP extension: {$extension}",
        ];
    }

    private function writable(string $directory): array
    {
        $path = base_path($directory);
        $passed = is_dir($path) && is_writable($path);

        return [
            'name' => "Writable: {$directory}",
            'passed' => $passed,
            'message' => $passed ? 'Writable' : "{$directory} must exist and be writable",
        ];
    }

    private function envWritable(): array
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (file_exists($envPath)) {
            $passed = is_writable($envPath);
            $message = $passed ? 'Writable' : '.env exists but is not writable';
        } else {
            $passed = file_exists($examplePath) && is_writable(base_path());
            $message = $passed ? 'Will be created from .env.example' : 'Project root must be writable to create .env';
        }

        return [
            'name' => 'Environment file',
            'passed' => $passed,
            'message' => $message,
        ];
    }
}
