<?php

namespace App\Http\Controllers\Identity;

use App\Http\Controllers\Controller;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use App\Domains\Identity\Services\AuthenticationService;
use App\Domains\Tenancy\Models\AccountMembership;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use App\Tenancy\CurrentTenant;

class UserController extends Controller
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser instanceof User, 401);

        $users = User::query()
            ->whereHas('accounts', fn ($query) => $query->where('accounts.id', $this->tenant->accountId()))
            ->orderByDesc('estado')
            ->orderBy('nombre')
            ->get(['idusuario', 'nombre', 'correo', 'usuario', 'es_admin', 'estado'])
            ->map(function (User $user): array {
                $membership = $this->accountMembership($user);

                return [
                    'idusuario' => $user->idusuario,
                    'nombre' => $user->nombre,
                    'correo' => $user->correo,
                    'usuario' => $user->usuario,
                    'es_admin' => $this->membershipIsAdmin($membership),
                    'estado' => (bool) $membership->is_active && (bool) $user->estado,
                ];
            });

        $permissions = Permission::orderBy('id')->get(['id', 'nombre', 'etiqueta']);

        $editing = null;
        $editId = filter_var($request->query('edit'), FILTER_VALIDATE_INT);
        if ($editId !== false) {
            $userToEdit = User::with('permissions')
                ->whereHas('accounts', fn ($query) => $query->where('accounts.id', $this->tenant->accountId()))
                ->find($editId);
            if ($userToEdit) {
                $membership = $this->accountMembership($userToEdit);
                if ($this->membershipIsAdmin($membership) && !$currentUser->isAccountAdmin()) {
                    return back()->with('error', 'Solo otro administrador puede modificar esa cuenta.');
                }

                $editing = [
                    'idusuario' => $userToEdit->idusuario,
                    'nombre' => $userToEdit->nombre,
                    'correo' => $userToEdit->correo,
                    'usuario' => $userToEdit->usuario,
                    'es_admin' => $this->membershipIsAdmin($membership),
                    'permisos' => $userToEdit->permissions->pluck('id')->toArray(),
                ];
            }
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'permissions' => $permissions,
            'editing' => $editing,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser instanceof User, 401);

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:190', 'unique:usuario,correo'],
            'usuario' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]{3,50}$/', 'unique:usuario,usuario'],
            'clave' => ['required', 'string'],
            'es_admin' => ['nullable', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', 'exists:permisos,id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'correo.email' => 'Ingresá un correo válido.',
            'correo.unique' => 'El usuario o el correo ya están registrados.',
            'usuario.regex' => 'El usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números, punto, guion y guion bajo.',
            'usuario.unique' => 'El usuario o el correo ya están registrados.',
            'clave.required' => 'La contraseña es obligatoria para un usuario nuevo.',
        ]);

        if ($error = AuthenticationService::validatePasswordStrength($validated['clave'])) {
            return back()->withErrors(['clave' => $error])->withInput();
        }

        $isAdmin = $currentUser->isAccountAdmin() && !empty($validated['es_admin']);

        DB::transaction(function () use ($validated, $isAdmin) {
            $user = User::create([
                'nombre' => $validated['nombre'],
                'correo' => strtolower($validated['correo']),
                'usuario' => $validated['usuario'],
                'clave' => Hash::make($validated['clave']),
                'es_admin' => $isAdmin,
                'estado' => true,
            ]);

            if (!$isAdmin && !empty($validated['permisos'])) {
                $user->permissions()->sync($validated['permisos']);
            }
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario guardado.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser instanceof User, 401);

        $user = $this->findAccountUser($id);
        $membership = $this->accountMembership($user);

        if ($this->membershipIsAdmin($membership) && !$currentUser->isAccountAdmin()) {
            return back()->with('error', 'Solo otro administrador puede modificar esta cuenta.');
        }

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'string', 'email', 'max:190', 'unique:usuario,correo,' . $id . ',idusuario'],
            'usuario' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]{3,50}$/', 'unique:usuario,usuario,' . $id . ',idusuario'],
            'clave' => ['nullable', 'string'],
            'es_admin' => ['nullable', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', 'exists:permisos,id'],
        ], [
            'nombre.required' => 'El nombre es obligatorio y admite hasta 100 caracteres.',
            'correo.email' => 'Ingresá un correo válido.',
            'correo.unique' => 'El usuario o el correo ya están registrados.',
            'usuario.regex' => 'El usuario debe tener entre 3 y 50 caracteres y solo puede incluir letras, números, punto, guion y guion bajo.',
            'usuario.unique' => 'El usuario o el correo ya están registrados.',
        ]);

        if (!empty($validated['clave']) && ($error = AuthenticationService::validatePasswordStrength($validated['clave']))) {
            return back()->withErrors(['clave' => $error])->withInput();
        }

        $wasAdmin = $this->membershipIsAdmin($membership);
        $isAdmin = $currentUser->isAccountAdmin() ? !empty($validated['es_admin']) : $wasAdmin;

        // Last active admin protection
        if ($wasAdmin && !$isAdmin) {
            $activeAdminCount = $this->activeAdminCount();
            if ($activeAdminCount <= 1) {
                return back()->with('error', 'Debe quedar al menos un administrador activo.');
            }
        }

        DB::transaction(function () use ($user, $membership, $validated, $isAdmin) {
            $updateData = [
                'nombre' => $validated['nombre'],
                'correo' => strtolower($validated['correo']),
                'usuario' => $validated['usuario'],
            ];

            if (!empty($validated['clave'])) {
                $updateData['clave'] = Hash::make($validated['clave']);
            }

            $user->update($updateData);
            $membership->update(['role' => $isAdmin ? 'admin' : 'staff']);

            if ($isAdmin) {
                $user->permissions()->detach();
            } else {
                $user->permissions()->sync($validated['permisos'] ?? []);
            }
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario guardado.');
    }

    public function toggle(Request $request, int $id): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser instanceof User, 401);

        $user = $this->findAccountUser($id);
        $membership = $this->accountMembership($user);

        if ($user->idusuario === $currentUser->idusuario) {
            return back()->with('error', 'No podés desactivar tu propia cuenta.');
        }

        if ($this->membershipIsAdmin($membership) && !$currentUser->isAccountAdmin()) {
            return back()->with('error', 'Solo un administrador puede modificar otra cuenta administradora.');
        }

        if ($this->membershipIsAdmin($membership) && $membership->is_active) {
            $activeAdminCount = $this->activeAdminCount();
            if ($activeAdminCount <= 1) {
                return back()->with('error', 'Debe quedar al menos un administrador activo.');
            }
        }

        $membership->update(['is_active' => !$membership->is_active]);

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    private function findAccountUser(int $id): User
    {
        return User::query()
            ->whereHas('accounts', fn ($query) => $query->where('accounts.id', $this->tenant->accountId()))
            ->findOrFail($id);
    }

    private function activeAdminCount(): int
    {
        return AccountMembership::query()
            ->where('account_id', $this->tenant->accountId())
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->whereHas('user', fn ($query) => $query->where('estado', true))
            ->count();
    }

    private function accountMembership(User $user): AccountMembership
    {
        return AccountMembership::query()
            ->where('account_id', $this->tenant->accountId())
            ->where('user_id', $user->idusuario)
            ->firstOrFail();
    }

    private function membershipIsAdmin(AccountMembership $membership): bool
    {
        return in_array($membership->role, ['owner', 'admin'], true);
    }
}
