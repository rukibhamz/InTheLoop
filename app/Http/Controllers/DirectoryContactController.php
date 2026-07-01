<?php

namespace App\Http\Controllers;

use App\Models\DirectoryContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryContactController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if (strlen($query) < 1) {
            return response()->json(['results' => []]);
        }

        $contacts = DirectoryContact::query()
            ->where(function ($builder) use ($query) {
                $builder->where('display_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('department', 'like', "%{$query}%");
            })
            ->orderBy('display_name')
            ->limit(10)
            ->get()
            ->map(fn (DirectoryContact $contact) => [
                'email' => $contact->email,
                'name' => $contact->display_name,
                'department' => $contact->department,
                'label' => $contact->department
                    ? "{$contact->display_name} — {$contact->department}"
                    : $contact->display_name,
            ]);

        $results = $contacts->values();

        if (filter_var($query, FILTER_VALIDATE_EMAIL) && $results->where('email', $query)->isEmpty()) {
            $results->push([
                'email' => $query,
                'name' => null,
                'department' => null,
                'label' => $query,
                'custom' => true,
            ]);
        }

        return response()->json(['results' => $results]);
    }
}
