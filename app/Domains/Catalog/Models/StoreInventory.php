<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\Concerns\BelongsToAccount;
use App\Tenancy\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $store_id
 * @property int $product_id
 * @property string $price
 * @property int $stock
 * @property bool $is_available
 * @property-read Product $product
 * @property-read Account $account
 * @property-read Store $store
 */
class StoreInventory extends Model
{
    use BelongsToAccount, BelongsToStore;

    protected $table = 'store_inventory';

    protected $fillable = [
        'account_id',
        'store_id',
        'product_id',
        'price',
        'stock',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'codproducto');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
