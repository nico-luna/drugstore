<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Tenancy\CurrentTenant;

class SettingsController extends Controller
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function edit(): Response
    {
        $config = DB::table('configuracion')
            ->where('account_id', $this->tenant->accountId())
            ->where('store_id', $this->tenant->storeId())
            ->first();

        return Inertia::render('Settings/Edit', [
            'config' => [
                'nombre'    => $config->nombre    ?? '',
                'telefono'  => $config->telefono  ?? '',
                'email'     => $config->email     ?? '',
                'direccion' => $config->direccion ?? '',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'    => ['required', 'string', 'max:100'],
            'telefono'  => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:190'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $data['email']     = isset($data['email'])     ? strtolower($data['email'])  : '';
        $data['telefono']  = $data['telefono']  ?? '';
        $data['direccion'] = $data['direccion'] ?? '';

        $data['actualizado_at'] = now();

        DB::table('configuracion')->updateOrInsert(
            [
                'account_id' => $this->tenant->accountId(),
                'store_id' => $this->tenant->storeId(),
            ],
            $data,
        );

        return redirect()
            ->route('configuracion.edit')
            ->with('success', 'Configuracion actualizada.');
    }
}
