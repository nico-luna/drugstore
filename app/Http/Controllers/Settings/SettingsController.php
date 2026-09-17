<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $config = DB::table('configuracion')->where('id', 1)->first();

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

        DB::table('configuracion')->where('id', 1)->update($data);

        return redirect()
            ->route('configuracion.edit')
            ->with('success', 'Configuracion actualizada.');
    }
}