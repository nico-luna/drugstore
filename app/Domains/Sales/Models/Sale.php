<?php

namespace App\Domains\Sales\Models;

use App\Domains\Customers\Models\Customer;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_cliente', 'idcliente');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'idusuario');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por', 'idusuario');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'id_venta', 'id');
    }
}
