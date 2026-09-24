<?php

namespace App\Http\Controllers\Tenancy;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
            'store_id' => ['nullable', 'integer'],
        ]);

        $membership = AccountMembership::query()
            ->where('account_id', $validated['account_id'])
            ->where('user_id', $user->idusuario)
            ->where('is_active', true)
            ->whereHas('account', fn ($query) => $query->where('is_active', true))
            ->first();

        if (!$membership) {
            throw ValidationException::withMessages([
                'account_id' => 'No tenés acceso a la cuenta seleccionada.',
            ]);
        }

        $stores = Store::query()
            ->where('account_id', $membership->account_id)
            ->where('is_active', true);

        if (!in_array($membership->role, ['owner', 'admin'], true)) {
            $stores->whereHas('users', fn ($query) => $query->where('usuario.idusuario', $user->idusuario));
        }

        $storeId = $validated['store_id'] ?? $membership->default_store_id;
        $store = $stores->find($storeId) ?? $stores->orderBy('name')->first();

        if (!$store) {
            throw ValidationException::withMessages([
                'store_id' => 'No tenés acceso a una tienda activa de esa cuenta.',
            ]);
        }

        $request->session()->put('tenant.account_id', $membership->account_id);
        $request->session()->put('tenant.store_id', $store->id);

        return back()->with('success', 'Contexto de trabajo actualizado.');
    }
}
