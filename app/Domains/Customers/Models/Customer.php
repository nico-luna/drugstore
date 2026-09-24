<?php

namespace App\Domains\Customers\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Account;
use App\Tenancy\Concerns\BelongsToAccount;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $idcliente
 * @property int $account_id
 * @property string $nombre
 * @property string $telefono
 * @property string $direccion
 * @property int|null $usuario_id
 * @property bool $estado
 * @property-read User|null $user
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToAccount, HasFactory;

    protected $table = 'cliente';
    protected $primaryKey = 'idcliente';

    const CREATED_AT = 'creado_at';
    const UPDATED_AT = 'actualizado_at';

    protected $fillable = [
        'account_id',
        'nombre',
        'telefono',
        'direccion',
        'usuario_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
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
}
