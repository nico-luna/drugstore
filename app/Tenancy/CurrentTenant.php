<?php

namespace App\Tenancy;

use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use LogicException;

class CurrentTenant
{
    private ?Account $account = null;
    private ?Store $store = null;
    private ?AccountMembership $membership = null;

    public function initialize(Account $account, Store $store): void
    {
        if ($store->account_id !== $account->id) {
            throw new LogicException('La tienda debe pertenecer a la cuenta activa.');
        }

        $this->account = $account;
        $this->store = $store;
        $this->membership = null;
    }

    public function set(Account $account, Store $store, AccountMembership $membership): void
    {
        if ($store->account_id !== $account->id || $membership->account_id !== $account->id) {
            throw new LogicException('La tienda y la membresía deben pertenecer a la cuenta activa.');
        }

        $this->account = $account;
        $this->store = $store;
        $this->membership = $membership;
    }

    public function clear(): void
    {
        $this->account = null;
        $this->store = null;
        $this->membership = null;
    }

    public function resolved(): bool
    {
        return $this->account !== null && $this->store !== null && $this->membership !== null;
    }

    public function workspaceResolved(): bool
    {
        return $this->account !== null && $this->store !== null;
    }

    public function account(): Account
    {
        return $this->account ?? throw new LogicException('No hay una cuenta activa.');
    }

    public function store(): Store
    {
        return $this->store ?? throw new LogicException('No hay una tienda activa.');
    }

    public function membership(): AccountMembership
    {
        return $this->membership ?? throw new LogicException('No hay una membresía activa.');
    }

    public function accountId(): int
    {
        return (int) $this->account()->id;
    }

    public function storeId(): int
    {
        return (int) $this->store()->id;
    }
}
