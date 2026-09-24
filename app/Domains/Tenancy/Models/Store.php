<?php

namespace App\Domains\Tenancy\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property-read Account $account
 */
class Store extends Model
{
    protected $fillable = ['account_id', 'name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user', 'store_id', 'user_id', 'id', 'idusuario')
            ->withTimestamps();
    }
}
