<?php

namespace App\Domains\Sales\Models;

use App\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $id_producto
 * @property int $id_venta
 * @property int $cantidad
 * @property string $precio
 * @property string $subtotal
 * @property-read Sale $sale
 * @property-read Product|null $product
 */
class SaleItem extends Model
{
    protected $table = 'detalle_venta';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_venta',
        'cantidad',
        'precio',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'id_venta', 'id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_producto', 'codproducto');
    }
}
