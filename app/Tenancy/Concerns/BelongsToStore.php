<?php

namespace App\Tenancy\Concerns;

use App\Tenancy\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $builder): void {
            $tenant = app(CurrentTenant::class);
            if ($tenant->workspaceResolved()) {
                $builder->where($builder->getModel()->qualifyColumn('store_id'), $tenant->storeId());
            }
        });

        static::creating(function (Model $model): void {
            $tenant = app(CurrentTenant::class);
            if ($tenant->workspaceResolved() && $model->getAttribute('store_id') === null) {
                $model->setAttribute('store_id', $tenant->storeId());
            }
        });
    }
}
