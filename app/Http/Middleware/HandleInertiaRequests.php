<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'idusuario' => $request->user()->idusuario,
                    'nombre' => $request->user()->nombre,
                    'correo' => $request->user()->correo,
                    'usuario' => $request->user()->usuario,
                    'es_admin' => (bool) $request->user()->es_admin,
                    'estado' => (bool) $request->user()->estado,
                    'permisos' => method_exists($request->user(), 'getPermissionsAttribute') ? $request->user()->permissions : [],
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
