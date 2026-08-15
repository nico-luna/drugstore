<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Domains\Identity\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class LoginController extends Controller
{
    public function __construct(private readonly AuthenticationService $authService)
    {
    }

    public function create(): Response|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'usuario' => ['required', 'string', 'max:50'],
            'clave' => ['required', 'string'],
        ], [
            'usuario.required' => 'Ingresá tu usuario.',
            'clave.required' => 'Ingresá tu contraseña.',
        ]);

        $username = $request->input('usuario');
        $password = $request->input('clave');
        $ip = (string) $request->ip();

        try {
            if ($this->authService->attempt($username, $password, $ip)) {
                return redirect()->intended(route('dashboard'));
            }

            return back()->withErrors([
                'usuario' => 'Usuario o contraseña incorrectos.',
            ])->withInput($request->only('usuario'));
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'usuario' => $exception->getMessage(),
            ])->withInput($request->only('usuario'));
        }
    }

    public function destroy(): RedirectResponse
    {
        $this->authService->logout();
        return redirect()->route('login');
    }
}
