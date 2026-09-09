<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $table = 'producto';
    protected $primaryKey = 'codproducto';

    const CREATED_AT = 'creado_at';
    const UPDATED_AT = 'actualizado_at';

    protected $fillable = [
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'idusuario');
    }
}
