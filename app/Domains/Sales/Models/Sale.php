<?php

namespace App\Domains\Sales\Models;

use App\Domains\Customers\Models\Customer;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $id_cliente
 * @property int $id_usuario
 * @property string $total
 * @property string $estado
 * @property \Illuminate\Support\Carbon $fecha
 * @property \Illuminate\Support\Carbon|null $anulada_at
 * @property int|null $anulada_por
 * @property-read Customer|null $customer
 * @property-read User|null $user
 * @property-read User|null $cancelledBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SaleItem> $items
 */
class Sale extends Model
{
    protected $table = 'ventas';
    public $timestamps = false;

    protected $fillable = [
        'id_cliente',
        'total',
        'id_usuario',
        'estado',
        'fecha',
        'anulada_at',
        'anulada_por',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'fecha' => 'datetime',
            'anulada_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_cliente', 'idcliente');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'idusuario');
    }

    /** @return BelongsTo<User, $this> */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por', 'idusuario');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'id_venta', 'id');
    }
}
