<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Support\DirectoryDisplayResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = Announcement::query()
            ->when($request->string('mailbox')->trim()->toString(), function ($query, $mailbox) {
                $query->whereRaw('lower(mailbox) = ?', [strtolower($mailbox)]);
            })
            ->when($request->string('q')->trim()->toString(), function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('subject', 'like', "%{$search}%")
                        ->orWhere('from_email', 'like', "%{$search}%")
                        ->orWhere('body_text', 'like', "%{$search}%");
                });
            })
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        $mailboxes = Announcement::query()
            ->select('mailbox')
            ->distinct()
            ->orderBy('mailbox')
            ->pluck('mailbox');

        return view('announcements.index', compact('announcements', 'mailboxes'));
    }

    public function show(Announcement $announcement): View
    {
        $thread = Announcement::query()
            ->when(
                filled($announcement->conversation_id),
                fn ($query) => $query->where('conversation_id', $announcement->conversation_id),
                fn ($query) => $query->whereKey($announcement->id),
            )
            ->orderBy('received_at')
            ->with('attachments')
            ->get();

        $directory = DirectoryDisplayResolver::forAnnouncements($thread);

        return view('announcements.show', compact('announcement', 'thread', 'directory'));
    }
}
