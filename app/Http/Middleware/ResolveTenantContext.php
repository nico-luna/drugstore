<?php

namespace App\Http\Middleware;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $membership = $this->resolveMembership($request, $user);
        abort_unless($membership !== null, 403, 'Tu usuario no tiene una cuenta activa asignada.');

        $account = $membership->account;
        abort_unless($account->is_active, 403, 'La cuenta está suspendida.');

        $store = $this->resolveStore($request, $membership);
        abort_unless($store !== null, 403, 'Tu usuario no tiene una tienda activa asignada.');

        $this->tenant->set($account, $store, $membership);
        $request->session()->put('tenant.account_id', $account->id);
        $request->session()->put('tenant.store_id', $store->id);

        return $next($request);
    }

    private function resolveMembership(Request $request, User $user): ?AccountMembership
    {
        $memberships = AccountMembership::query()
            ->with('account')
            ->where('user_id', $user->idusuario)
            ->where('is_active', true)
            ->whereHas('account', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();

        $requestedAccountId = filter_var($request->session()->get('tenant.account_id'), FILTER_VALIDATE_INT);

        return $memberships->firstWhere('account_id', $requestedAccountId) ?? $memberships->first();
    }

    private function resolveStore(Request $request, AccountMembership $membership): ?Store
    {
        $query = Store::query()
            ->where('account_id', $membership->account_id)
            ->where('is_active', true);

        if (!in_array($membership->role, ['owner', 'admin'], true)) {
            $query->whereHas('users', fn ($users) => $users->where('usuario.idusuario', $membership->user_id));
        }

        $stores = $query->orderBy('name')->get();
        $requestedStoreId = filter_var($request->session()->get('tenant.store_id'), FILTER_VALIDATE_INT);

        return $stores->firstWhere('id', $requestedStoreId)
            ?? $stores->firstWhere('id', $membership->default_store_id)
            ?? $stores->first();
    }
}
