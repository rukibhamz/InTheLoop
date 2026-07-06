<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class Branding
{
    public function __construct(
        private readonly AppSettingCache $settingsCache
    ) {}

    public function settings(): \App\Models\AppSetting
    {
        return $this->settingsCache->settings();
    }

    public function orgName(): string
    {
        return $this->settings()->org_name ?? config('app.name');
    }

    public function accentColor(): string
    {
        return $this->settings()->accent_color ?? '#4648D4';
    }

    public function logoUrl(): ?string
    {
        $path = $this->settings()->logo_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return route('branding.logo');
    }

    /**
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        return $this->paletteFromHex($this->accentColor());
    }

    /**
     * @return array<string, string>
     */
    public function paletteFromHex(string $hex): array
    {
        [$h, $s, $l] = $this->hexToHsl($hex);

        return [
            '--color-primary-50' => $this->hsl($h, max($s - 20, 10), min($l + 42, 97)),
            '--color-primary-100' => $this->hsl($h, max($s - 15, 15), min($l + 35, 92)),
            '--color-primary-500' => $this->hsl($h, $s, $l),
            '--color-primary-600' => $this->hsl($h, $s, max($l - 8, 20)),
            '--color-primary-700' => $this->hsl($h, $s, max($l - 14, 15)),
        ];
    }

    public function clearCache(): void
    {
        $this->settingsCache->clear();
    }

    private function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0, 0, (int) round($l * 100)];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [(int) round($h * 60), (int) round($s * 100), (int) round($l * 100)];
    }

    private function hsl(int $h, int $s, int $l): string
    {
        return "hsl({$h}, {$s}%, {$l}%)";
    }
}
