<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingRequestController extends Controller
{
    public function index(): Response
    {
        $requests = DB::table('onboarding_requests')
            ->orderByRaw("CASE status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 ELSE 2 END")
            ->orderByDesc('created_at')
            ->limit(250)
            ->get()
            ->map(static fn (object $request): array => [
                'id' => (int) $request->id,
                'business_name' => (string) $request->business_name,
                'contact_name' => (string) $request->contact_name,
                'email' => (string) $request->email,
                'phone' => (string) $request->phone,
                'store_count' => (int) $request->store_count,
                'notes' => $request->notes ? (string) $request->notes : null,
                'status' => (string) $request->status,
                'created_at' => (string) $request->created_at,
            ]);

        return Inertia::render('Platform/OnboardingRequests', ['requests' => $requests]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'approved', 'rejected'])],
        ]);

        $updated = DB::table('onboarding_requests')->where('id', $id)->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);
        abort_unless($updated === 1, 404);

        return back()->with('success', 'Estado de la solicitud actualizado.');
    }
}
