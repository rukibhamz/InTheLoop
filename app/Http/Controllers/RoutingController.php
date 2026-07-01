<?php

namespace App\Http\Controllers;

use App\Models\ReportCategory;
use App\Models\Recipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoutingController extends Controller
{
    public function index(): View
    {
        $categories = ReportCategory::query()
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
            ReportCategory::query()
                ->whereKey($categoryId)
                ->update(['default_recipient_id' => $recipientId ?: null]);
        }

        return back()->with('success', 'Routing rules saved.');
    }
}
