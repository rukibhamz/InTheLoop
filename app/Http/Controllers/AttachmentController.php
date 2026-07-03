<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attachment;
use App\Models\Report;
use App\Models\ReportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof Report) {
            $this->authorize('view', $attachable);
        } elseif ($attachable instanceof ReportMessage) {
            $this->authorize('view', $attachable->report);
        } elseif ($attachable instanceof Announcement) {
            abort_unless($request->user() !== null, 403);
        } else {
            abort(403);
        }

        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_filename);
    }
}
