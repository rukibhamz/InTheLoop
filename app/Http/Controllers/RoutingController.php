<?php

namespace App\Http\Controllers;

use App\Models\EmailCategory;
use App\Models\Recipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoutingController extends Controller
{
    public function index(): View
    {
        $categories = EmailCategory::query()
            ->with('defaultRecipient')
            ->orderBy('name')
            ->get();

        $recipients = Recipient::query()->orderBy('name')->get();

        return view('routing.index', compact('categories', 'recipients'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'routes' => ['required', 'array'],
            'routes.*' => ['nullable', 'exists:recipients,id'],
        ]);

        foreach ($validated['routes'] as $categoryId => $recipientId) {
            EmailCategory::query()
                ->whereKey($categoryId)
                ->update(['default_recipient_id' => $recipientId ?: null]);
        }

        return back()->with('success', 'Routing rules saved.');
    }
}
