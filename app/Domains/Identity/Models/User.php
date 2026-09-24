<?php

namespace App\Domains\Identity\Models;

use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $tenant = app(CurrentTenant::class);
            if (!$tenant->workspaceResolved()) {
                return;
            }

            AccountMembership::query()->firstOrCreate(
                [
                    'account_id' => $tenant->accountId(),
                    'user_id' => $user->idusuario,
                ],
                [
                    'default_store_id' => $tenant->storeId(),
                    'role' => $user->es_admin ? 'admin' : 'staff',
                    'is_active' => true,
                ]
            );

            $user->stores()->syncWithoutDetaching([$tenant->storeId()]);
        });
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

    /** @return BelongsToMany<Account, $this> */
    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_user', 'user_id', 'account_id', 'idusuario', 'id')
            ->withPivot(['role', 'is_active', 'default_store_id'])
            ->withTimestamps();
    }

    /** @return BelongsToMany<Store, $this> */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_user', 'user_id', 'store_id', 'idusuario', 'id')
            ->withTimestamps();
    }

    /** @return HasMany<AccountMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(AccountMembership::class, 'user_id', 'idusuario');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAccountAdmin()) {
            return true;
        }

        return $this->permissions->contains('nombre', $permission);
    }

    /** @return array<int, string> */
    public function getPermissionsList(): array
    {
        if ($this->isAccountAdmin()) {
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

    public function isAccountAdmin(): bool
    {
        $tenant = app(CurrentTenant::class);

        if (!$tenant->resolved()) {
            return (bool) $this->es_admin;
        }

        $membership = $tenant->membership()->user_id === $this->idusuario
            ? $tenant->membership()
            : AccountMembership::query()
                ->where('account_id', $tenant->accountId())
                ->where('user_id', $this->idusuario)
                ->where('is_active', true)
                ->first();

        return $membership !== null
            && in_array($membership->role, ['owner', 'admin'], true);
    }
}
