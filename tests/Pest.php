<?php

use Tests\TestCase;
use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\Schema;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

beforeEach(function (): void {
    if (!Schema::hasTable('accounts') || !Schema::hasTable('stores')) {
        return;
    }

    $account = Account::query()->first();
    $store = Store::query()->first();

    if ($account && $store) {
        app(CurrentTenant::class)->initialize($account, $store);
    }
});
