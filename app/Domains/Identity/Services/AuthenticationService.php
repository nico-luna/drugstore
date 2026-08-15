<?php

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class AuthenticationService
{
    public function attempt(string $username, string $password, string $ip): bool
    {
        $identifier = $this->attemptIdentifier($username, $ip);

        if ($this->isLocked($identifier)) {
            throw new RuntimeException('Demasiados intentos. Esperá 15 minutos antes de volver a probar.');
        }

        $user = User::where('usuario', $username)
            ->where('estado', true)
            ->first();

        if (!$user || !Hash::check($password, $user->clave)) {
            $this->recordFailedAttempt($identifier);
            return false;
        }

        $this->clearAttempts($identifier);

        if (Hash::needsRehash($user->clave)) {
            $user->update(['clave' => Hash::make($password)]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        return true;
    }

    public function logout(): void
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    public function attemptIdentifier(string $username, string $ip): string
    {
        return hash('sha256', strtolower(trim($username)) . '|' . $ip . '|' . config('app.key'));
    }

    public function isLocked(string $identifier): bool
    {
        $record = DB::table('intentos_login')
            ->where('identificador', $identifier)
            ->where('bloqueado_hasta', '>', now())
            ->first();

        return $record !== null;
    }

    public function recordFailedAttempt(string $identifier): void
    {
        $existing = DB::table('intentos_login')->where('identificador', $identifier)->first();

        if (!$existing) {
            DB::table('intentos_login')->insert([
                'identificador' => $identifier,
                'intentos' => 1,
                'ultimo_intento' => now(),
                'bloqueado_hasta' => null,
            ]);
            return;
        }

        $fifteenMinutesAgo = now()->subMinutes(15);
        $attempts = ($existing->ultimo_intento < $fifteenMinutesAgo) ? 1 : ($existing->intentos + 1);
        $lockedUntil = $existing->bloqueado_hasta;

        if ($attempts >= 5) {
            $lockedUntil = now()->addMinutes(15);
        }

        DB::table('intentos_login')->where('identificador', $identifier)->update([
            'intentos' => $attempts,
            'ultimo_intento' => now(),
            'bloqueado_hasta' => $lockedUntil,
        ]);
    }

    public function clearAttempts(string $identifier): void
    {
        DB::table('intentos_login')->where('identificador', $identifier)->delete();
    }

    public static function validatePasswordStrength(string $password): ?string
    {
        if (strlen($password) < 12) {
            return 'La contraseña debe tener al menos 12 caracteres.';
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password) || !preg_match('/[^a-zA-Z\d]/', $password)) {
            return 'La contraseña debe incluir mayúsculas, minúsculas, números y símbolos.';
        }
        return null;
    }
}
