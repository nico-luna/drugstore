<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Account;
use App\Tenancy\Concerns\BelongsToAccount;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $codproducto
 * @property int $account_id
 * @property string $codigo
 * @property string $descripcion
 * @property string $precio
 * @property int $existencia
 * @property bool $controla_stock
 * @property int|null $usuario_id
 * @property bool $estado
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StoreInventory> $inventories
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToAccount, HasFactory;

    protected $table = 'producto';
    protected $primaryKey = 'codproducto';

    const CREATED_AT = 'creado_at';
    const UPDATED_AT = 'actualizado_at';

    protected $fillable = [
        'account_id',
        'codigo',
        'descripcion',
        'precio',
        'existencia',
        'controla_stock',
        'usuario_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'existencia' => 'integer',
            'controla_stock' => 'boolean',
            'estado' => 'boolean',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected static function booted(): void
    {
        static::created(function (Product $product): void {
            $tenant = app(CurrentTenant::class);
            if (!$tenant->workspaceResolved() || !Schema::hasTable('store_inventory')) {
                return;
            }

            StoreInventory::withoutGlobalScopes()->firstOrCreate(
                [
                    'store_id' => $tenant->storeId(),
                    'product_id' => $product->codproducto,
                ],
                [
                    'account_id' => $tenant->accountId(),
                    'price' => $product->precio,
                    'stock' => $product->existencia,
                    'is_available' => true,
                ],
            );
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'idusuario');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasMany<StoreInventory, $this> */
    public function inventories(): HasMany
    {
        return $this->hasMany(StoreInventory::class, 'product_id', 'codproducto');
    }
}
