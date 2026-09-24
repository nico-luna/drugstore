<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToAccount
{
    public static function bootBelongsToAccount(): void
    {
        static::addGlobalScope('account', function (Builder $builder): void {
            $tenant = app(CurrentTenant::class);
            if ($tenant->workspaceResolved()) {
                $builder->where($builder->getModel()->qualifyColumn('account_id'), $tenant->accountId());
            }
        });

        static::creating(function (Model $model): void {
            $tenant = app(CurrentTenant::class);
            if ($tenant->workspaceResolved() && $model->getAttribute('account_id') === null) {
                $model->setAttribute('account_id', $tenant->accountId());
            }
        });
    }
}
