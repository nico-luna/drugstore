<?php

namespace App\Domains\Tenancy\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property int $user_id
 * @property int|null $default_store_id
 * @property string $role
 * @property bool $is_active
 * @property-read Account $account
 * @property-read Store|null $defaultStore
 * @property-read User $user
 */
class AccountMembership extends Model
{
    protected $table = 'account_user';

    protected $fillable = [
        'account_id',
        'user_id',
        'default_store_id',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function defaultStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'default_store_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'idusuario');
    }
}
