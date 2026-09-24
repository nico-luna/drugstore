<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountAdmin
{
    public function __construct(private readonly CurrentTenant $tenant)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array($this->tenant->membership()->role, ['owner', 'admin'], true),
            403,
            'Sólo los administradores de la cuenta pueden gestionar la organización.',
        );

        return $next($request);
    }
}
