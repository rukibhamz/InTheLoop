<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->string('q')->trim()->toString(), function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                match ($request->string('role')->toString()) {
                    'admin' => $query->where('is_admin', true),
                    'approver' => $query->where('is_approver', true)->where('is_admin', false),
                    'inactive' => $query->where('is_active', false),
                    default => $query,
                };
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => User::query()->count(),
            'admins' => User::query()->where('is_admin', true)->count(),
            'approvers' => User::query()->where('is_approver', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.form', ['user' => new User]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'employee_id' => $request->string('employee_id')->toString() ?: null,
            'department' => $request->string('department')->toString() ?: null,
            'shared_mailbox_email' => $request->string('shared_mailbox_email')->toString() ?: $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'auth_method' => 'local',
            'is_admin' => $request->boolean('is_admin'),
            'is_approver' => $request->boolean('is_approver'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.form', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->fill([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'employee_id' => $request->string('employee_id')->toString() ?: null,
            'department' => $request->string('department')->toString() ?: null,
            'shared_mailbox_email' => $request->string('shared_mailbox_email')->toString() ?: $request->string('email')->toString(),
        ]);

        if ($request->user()->isAdmin()) {
            $user->is_admin = $request->boolean('is_admin');
            $user->is_approver = $request->boolean('is_approver');
            $user->is_active = $request->boolean('is_active', true);
        }

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User removed.');
    }
}
