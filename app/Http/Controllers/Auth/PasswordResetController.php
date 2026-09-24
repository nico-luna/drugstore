<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\AuthenticationService;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function email(Request $request): RedirectResponse
    {
        $validated = $request->validate(['correo' => ['required', 'email', 'max:190']]);

        Password::sendResetLink(['correo' => strtolower($validated['correo'])]);

        return back()->with('success', 'Si el correo está registrado, vas a recibir un enlace para restablecer la contraseña.');
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'correo' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        if ($error = AuthenticationService::validatePasswordStrength($validated['password'])) {
            return back()->withErrors(['password' => $error]);
        }

        $status = Password::reset(
            [
                'correo' => strtolower($validated['correo']),
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill(['clave' => Hash::make($password)]);
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['correo' => 'El enlace es inválido o venció. Solicitá uno nuevo.']);
        }

        return redirect()->route('login')->with('success', 'Contraseña actualizada. Ya podés ingresar.');
    }
}
