<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
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
                    'es_admin' => $user->isAccountAdmin(),
                    'is_platform_admin' => (bool) $user->is_platform_admin,
                    'estado' => $user->estado,
                    'role' => app(CurrentTenant::class)->resolved()
                        ? app(CurrentTenant::class)->membership()->role
                        : null,
                    'permisos' => $user->getPermissionsList(),
                ] : null,
            ],
            'tenant' => $this->tenantProps($user),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }

    /** @return array<string, mixed>|null */
    private function tenantProps(mixed $user): ?array
    {
        $tenant = app(CurrentTenant::class);
        if (!$user instanceof User || !$tenant->resolved()) {
            return null;
        }

        $accounts = AccountMembership::query()
            ->with('account')
            ->where('user_id', $user->idusuario)
            ->where('is_active', true)
            ->whereHas('account', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get()
            ->map(static fn (AccountMembership $membership): array => [
                'id' => $membership->account_id,
                'name' => $membership->account->name,
                'role' => $membership->role,
            ])
            ->values()
            ->all();

        $storesQuery = Store::query()
            ->where('account_id', $tenant->accountId())
            ->where('is_active', true);

        if (!in_array($tenant->membership()->role, ['owner', 'admin'], true)) {
            $storesQuery->whereHas('users', fn ($query) => $query->where('usuario.idusuario', $user->idusuario));
        }

        $stores = $storesQuery
            ->orderBy('name')
            ->get()
            ->map(static fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
            ])
            ->values()
            ->all();

        return [
            'account' => [
                'id' => $tenant->accountId(),
                'name' => $tenant->account()->name,
                'role' => $tenant->membership()->role,
            ],
            'store' => [
                'id' => $tenant->storeId(),
                'name' => $tenant->store()->name,
            ],
            'accounts' => $accounts,
            'stores' => $stores,
        ];
    }
}
