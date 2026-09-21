<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Models\User;
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
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user instanceof User ? [
                    'idusuario' => $user->idusuario,
                    'nombre' => $user->nombre,
                    'correo' => $user->correo,
                    'usuario' => $user->usuario,
                    'es_admin' => $user->es_admin,
                    'estado' => $user->estado,
                    'permisos' => $user->getPermissionsList(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
