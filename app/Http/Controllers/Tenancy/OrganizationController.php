<?php

namespace App\Http\Controllers\Tenancy;

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Http\Controllers\Controller;
use App\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function index(): Response
    {
        $stores = Store::query()
            ->where('account_id', $this->tenant->accountId())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active']);

        $members = AccountMembership::query()
            ->with('user')
            ->where('account_id', $this->tenant->accountId())
            ->orderBy('id')
            ->get()
            ->map(function (AccountMembership $membership): array {
                $storeIds = DB::table('store_user')
                    ->join('stores', 'stores.id', '=', 'store_user.store_id')
                    ->where('stores.account_id', $this->tenant->accountId())
                    ->where('store_user.user_id', $membership->user_id)
                    ->pluck('store_user.store_id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();

                return [
                    'id' => $membership->id,
                    'user_id' => $membership->user_id,
                    'name' => $membership->user->nombre,
                    'email' => $membership->user->correo,
                    'role' => $membership->role,
                    'is_active' => (bool) $membership->is_active,
                    'default_store_id' => $membership->default_store_id,
                    'store_ids' => $storeIds,
                ];
            });

        return Inertia::render('Organization/Index', [
            'account' => [
                'id' => $this->tenant->accountId(),
                'name' => $this->tenant->account()->name,
                'role' => $this->tenant->membership()->role,
            ],
            'stores' => $stores,
            'members' => $members,
        ]);
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $this->tenant->account()->update(['name' => $validated['name']]);

        return back()->with('success', 'Datos de la cuenta actualizados.');
    }

    public function storeStore(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $baseSlug = Str::slug($validated['name']) ?: 'tienda';
        $slug = $baseSlug;
        $suffix = 2;
        while (Store::query()->where('account_id', $this->tenant->accountId())->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        DB::transaction(function () use ($validated, $slug): void {
            $store = Store::create([
                'account_id' => $this->tenant->accountId(),
                'name' => $validated['name'],
                'slug' => $slug,
                'is_active' => true,
            ]);

            DB::table('configuracion')->insert([
                'account_id' => $this->tenant->accountId(),
                'store_id' => $store->id,
                'nombre' => $validated['name'],
                'telefono' => '',
                'email' => '',
                'direccion' => '',
                'actualizado_at' => now(),
            ]);

            Product::query()->orderBy('codproducto')->chunk(500, function ($products) use ($store): void {
                $now = now();
                $rows = $products->map(fn (Product $product): array => [
                    'account_id' => $this->tenant->accountId(),
                    'store_id' => $store->id,
                    'product_id' => $product->codproducto,
                    'price' => $product->precio,
                    'stock' => 0,
                    'is_available' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('store_inventory')->insert($rows);
                }
            });
        });

        return back()->with('success', 'Sucursal creada. Completá sus precios y existencias.');
    }

    public function toggleStore(Store $store): RedirectResponse
    {
        abort_unless($store->account_id === $this->tenant->accountId(), 404);

        if ($store->id === $this->tenant->storeId()) {
            return back()->with('error', 'No podés desactivar la tienda activa.');
        }
        if ($store->is_active && Store::query()->where('account_id', $this->tenant->accountId())->where('is_active', true)->count() <= 1) {
            return back()->with('error', 'Debe quedar al menos una tienda activa.');
        }

        $store->update(['is_active' => !$store->is_active]);

        return back()->with('success', 'Estado de la sucursal actualizado.');
    }

    public function updateMember(Request $request, AccountMembership $membership): RedirectResponse
    {
        abort_unless($membership->account_id === $this->tenant->accountId(), 404);

        $storeIds = Store::query()->where('account_id', $this->tenant->accountId())->pluck('id')->all();
        $validated = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'staff'])],
            'is_active' => ['required', 'boolean'],
            'default_store_id' => ['required', 'integer', Rule::in($storeIds)],
            'store_ids' => ['required', 'array', 'min:1'],
            'store_ids.*' => ['integer', Rule::in($storeIds)],
        ]);

        if ($validated['role'] === 'owner' && $this->tenant->membership()->role !== 'owner') {
            throw ValidationException::withMessages(['role' => 'Sólo un propietario puede asignar ese rol.']);
        }
        if ($membership->role === 'owner' && $this->tenant->membership()->role !== 'owner') {
            throw ValidationException::withMessages(['role' => 'Sólo otro propietario puede modificar ese acceso.']);
        }
        $selectedStoreIds = array_values(array_unique(array_map(
            static fn (mixed $storeId): int => (int) $storeId,
            (array) $validated['store_ids'],
        )));

        if (!in_array((int) $validated['default_store_id'], $selectedStoreIds, true)) {
            throw ValidationException::withMessages(['default_store_id' => 'La tienda predeterminada debe estar habilitada para el usuario.']);
        }
        if ($membership->user_id === $this->tenant->membership()->user_id && !$validated['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'No podés quitar tu propio acceso.']);
        }
        if ($membership->role === 'owner' && ($validated['role'] !== 'owner' || !$validated['is_active'])) {
            $otherOwners = AccountMembership::query()
                ->where('account_id', $this->tenant->accountId())
                ->where('id', '!=', $membership->id)
                ->where('role', 'owner')
                ->where('is_active', true)
                ->exists();
            if (!$otherOwners) {
                throw ValidationException::withMessages(['role' => 'Debe quedar al menos un propietario activo.']);
            }
        }

        DB::transaction(function () use ($membership, $validated, $storeIds, $selectedStoreIds): void {
            $membership->update([
                'role' => $validated['role'],
                'is_active' => $validated['is_active'],
                'default_store_id' => $validated['default_store_id'],
            ]);

            DB::table('store_user')
                ->where('user_id', $membership->user_id)
                ->whereIn('store_id', $storeIds)
                ->delete();

            $now = now();
            DB::table('store_user')->insert(array_map(fn (int $storeId): array => [
                'store_id' => $storeId,
                'user_id' => $membership->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ], $selectedStoreIds));
        });

        return back()->with('success', 'Acceso del usuario actualizado.');
    }
}
