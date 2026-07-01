<?php

namespace App\Http\Controllers;

use App\Services\Branding;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function logo(Branding $branding): Response
    {
        $path = $branding->settings()->logo_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
