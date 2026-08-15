<?php

namespace App\Domains\Identity\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';
    protected $primaryKey = 'idusuario';

    const CREATED_AT = 'creado_at';
    const UPDATED_AT = 'actualizado_at';

    protected $fillable = [
        'nombre',
        'correo',
        'usuario',
        'clave',
        'es_admin',
        'estado',
    ];

    protected $hidden = [
        'clave',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'es_admin' => 'boolean',
            'estado' => 'boolean',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->clave;
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'detalle_permisos',
            'id_usuario',
            'id_permiso',
            'idusuario',
            'id'
        );
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->es_admin) {
            return true;
        }

        return $this->permissions->contains('nombre', $permission);
    }

    public function getPermissionsList(): array
    {
        if ($this->es_admin) {
            return Permission::pluck('nombre')->toArray();
        }

        return $this->permissions->pluck('nombre')->toArray();
    }
}
