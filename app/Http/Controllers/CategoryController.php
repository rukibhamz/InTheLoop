<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\Recipient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = ReportCategory::query()
            ->withCount('reports')
            ->with('defaultRecipient')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_categories' => $categories->count(),
            'active_reports' => Report::query()->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'avg_review_days' => round(
                Report::query()
                    ->whereNotNull('approved_at')
                    ->get(['created_at', 'approved_at'])
                    ->avg(fn (Report $report) => $report->created_at->diffInDays($report->approved_at)) ?? 0,
                1
            ),
            'compliance_score' => $categories->isEmpty()
                ? 100
                : min(100, (int) round(($categories->where('reports_count', '>', 0)->count() / max($categories->count(), 1)) * 100)),
        ];

        return view('categories.index', compact('categories', 'stats'));
    }

    public function create(): View
    {
        return view('categories.form', [
            'category' => new ReportCategory,
            'recipients' => Recipient::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        ReportCategory::query()->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function edit(ReportCategory $category): View
    {
        return view('categories.form', [
            'category' => $category,
            'recipients' => Recipient::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, ReportCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ReportCategory $category): RedirectResponse
    {
        if ($category->reports()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has reports.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
