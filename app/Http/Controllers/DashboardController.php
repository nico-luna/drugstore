<?php

namespace App\Http\Controllers;

use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Sales\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $cards = [];

        if ($user->hasPermission('clientes')) {
            $cards[] = [
                'label' => 'Clientes activos',
                'value' => (string) Customer::where('estado', true)->count(),
                'target' => '/clientes',
            ];
        }

        if ($user->hasPermission('productos')) {
            $cards[] = [
                'label' => 'Productos activos',
                'value' => (string) Product::where('estado', true)->count(),
                'target' => '/productos',
            ];
        }

        if ($user->hasPermission('ventas')) {
            $todaySalesCount = Sale::where('estado', 'confirmada')
                ->whereDate('fecha', today())
                ->count();

            $todaySalesTotal = (float) Sale::where('estado', 'confirmada')
                ->whereDate('fecha', today())
                ->sum('total');

            $cards[] = [
                'label' => 'Ventas de hoy',
                'value' => (string) $todaySalesCount,
                'target' => '/ventas',
            ];

            $cards[] = [
                'label' => 'Total de hoy',
                'value' => '$ ' . number_format($todaySalesTotal, 2, ',', '.'),
                'target' => '/ventas',
            ];
        }

        return Inertia::render('Dashboard/Index', [
            'cards' => $cards,
        ]);
    }
}
