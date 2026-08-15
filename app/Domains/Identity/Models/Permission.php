<?php

namespace App\Domains\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'permisos';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'etiqueta',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'detalle_permisos',
            'id_permiso',
            'id_usuario',
            'id',
            'idusuario'
        );
    }
}
