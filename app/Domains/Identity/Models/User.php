<?php

namespace App\Domains\Identity\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $idusuario
 * @property string $nombre
 * @property string $correo
 * @property string $usuario
 * @property string $clave
 * @property bool $es_admin
 * @property bool $estado
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function getAuthPassword(): string
    {
        return $this->clave;
    }

    /** @return BelongsToMany<Permission, $this> */
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

    /** @return array<int, string> */
    public function getPermissionsList(): array
    {
        if ($this->es_admin) {
            return Permission::query()
                ->pluck('nombre')
                ->map(static fn (mixed $permission): string => (string) $permission)
                ->values()
                ->all();
        }

        return $this->permissions
            ->pluck('nombre')
            ->map(static fn (mixed $permission): string => (string) $permission)
            ->values()
            ->all();
    }
}
