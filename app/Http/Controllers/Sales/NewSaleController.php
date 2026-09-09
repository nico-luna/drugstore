<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Sales\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;

class NewSaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function create(): Response
    {
        $clients = Customer::where('estado', true)
            ->orderBy('nombre')
            ->get(['idcliente', 'nombre']);

        $products = Product::where('estado', true)
            ->where(function ($q) {
                $q->where('controla_stock', false)
                  ->orWhere('existencia', '>', 0);
            })
            ->orderBy('descripcion')
            ->get(['codproducto', 'codigo', 'descripcion', 'precio', 'existencia', 'controla_stock']);

        return Inertia::render('Sales/Create', [
            'clients' => $clients,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cliente_id' => ['required', 'integer'],
            'producto_id' => ['required', 'array', 'min:1'],
            'producto_id.*' => ['required', 'integer'],
            'cantidad' => ['required', 'array', 'min:1'],
            'cantidad.*' => ['required', 'integer', 'min:1'],
        ], [
            'cliente_id.required' => 'Seleccioná un cliente y al menos un producto.',
            'producto_id.required' => 'Seleccioná un cliente y al menos un producto.',
            'producto_id.min' => 'Seleccioná un cliente y al menos un producto.',
        ]);

        $productIds = (array) $request->input('producto_id', []);
        $quantities = (array) $request->input('cantidad', []);

        $lines = [];
        foreach ($productIds as $index => $productId) {
            $lines[] = [
                'producto_id' => $productId,
                'cantidad' => $quantities[$index] ?? null,
            ];
        }

        try {
            $saleId = $this->saleService->create(
                (int) $request->input('cliente_id'),
                (int) $request->user()->idusuario,
                $lines
            );

            return redirect("/ventas?view={$saleId}")
                ->with('success', "Venta #{$saleId} registrada correctamente.");
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->with('error', $exception->getMessage())->withInput();
        }
    }
}
