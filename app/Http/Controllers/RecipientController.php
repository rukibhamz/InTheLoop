<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipientRequest;
use App\Http\Requests\UpdateRecipientRequest;
use App\Models\Recipient;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecipientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Recipient::query()->orderBy('name');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('shared_mailbox_email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        if ($department = $request->string('department')->trim()->toString()) {
            $query->where('department', $department);
        }

        if ($role = $request->string('role')->trim()->toString()) {
            $query->where('role', 'like', "%{$role}%");
        }

        $recipients = $query->paginate(10)->withQueryString();

        $stats = [
            'total' => Recipient::query()->count(),
            'admins' => User::query()->where('is_admin', true)->count(),
            'active_threads' => Report::query()->whereIn('status', ['sent', 'in_review', 'pending'])->count(),
            'pending_invites' => User::query()->where('is_active', false)->count(),
        ];

        $departments = Recipient::query()
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $roles = Recipient::query()
            ->whereNotNull('role')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        return view('recipients.index', compact('recipients', 'stats', 'departments', 'roles'));
    }

    public function create(): View
    {
        return view('recipients.form', ['recipient' => new Recipient]);
    }

    public function store(StoreRecipientRequest $request): RedirectResponse
    {
        Recipient::query()->create($request->validated());

        return redirect()->route('recipients.index')->with('success', 'Recipient added.');
    }

    public function edit(Recipient $recipient): View
    {
        return view('recipients.form', compact('recipient'));
    }

    public function update(UpdateRecipientRequest $request, Recipient $recipient): RedirectResponse
    {
        $recipient->update($request->validated());

        return redirect()->route('recipients.index')->with('success', 'Recipient updated.');
    }

    public function destroy(Recipient $recipient): RedirectResponse
    {
        $recipient->delete();

        return redirect()->route('recipients.index')->with('success', 'Recipient removed.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'recipients-'.now()->format('Y-m-d').'.csv';

        return Response::streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['name', 'shared_mailbox_email', 'department', 'role']);

            Recipient::query()->orderBy('name')->chunk(100, function ($recipients) use ($handle) {
                foreach ($recipients as $recipient) {
                    fputcsv($handle, [
                        $recipient->name,
                        $recipient->shared_mailbox_email,
                        $recipient->department,
                        $recipient->role,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            Recipient::query()->updateOrCreate(
                ['shared_mailbox_email' => trim($row[1])],
                [
                    'name' => trim($row[0]),
                    'department' => trim($row[2] ?? '') ?: null,
                    'role' => trim($row[3] ?? '') ?: null,
                ]
            );
            $imported++;
        }

        fclose($handle);

        return redirect()->route('recipients.index')->with('success', "Imported {$imported} recipients.");
    }
}
