<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Public/Home', [
            'authenticated' => auth()->check(),
        ]);
    }

    public function requestAccess(): Response
    {
        return Inertia::render('Public/RequestAccess');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:120'],
            'contact_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'store_count' => ['required', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('onboarding_requests')->insert([
            'business_name' => $validated['business_name'],
            'contact_name' => $validated['contact_name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? '',
            'store_count' => $validated['store_count'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'new',
            'source_ip' => (string) $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Recibimos tu solicitud. Te contactaremos para preparar la cuenta.');
    }
}
