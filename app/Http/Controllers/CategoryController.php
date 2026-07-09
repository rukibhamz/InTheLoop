<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Email;
use App\Models\EmailCategory;
use App\Models\Recipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = EmailCategory::query()
            ->withCount('emails')
            ->with('defaultRecipient')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_categories' => $categories->count(),
            'active_emails' => Email::query()->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'avg_review_days' => round(
                Email::query()
                    ->whereNotNull('approved_at')
                    ->get(['created_at', 'approved_at'])
                    ->avg(fn (Email $email) => $email->created_at->diffInDays($email->approved_at)) ?? 0,
                1
            ),
            'compliance_score' => $categories->isEmpty()
                ? 100
                : min(100, (int) round(($categories->where('emails_count', '>', 0)->count() / max($categories->count(), 1)) * 100)),
        ];

        return view('categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        return view('categories.form', [
            'category' => new EmailCategory,
            'recipients' => Recipient::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        EmailCategory::query()->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(EmailCategory $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'recipients' => Recipient::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, EmailCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(EmailCategory $category): RedirectResponse
    {
        if ($category->emails()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has emails.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
