<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use App\Domains\Sales\Services\SaleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use RuntimeException;

class SaleHistoryController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    public function index(Request $request): Response
    {
        $desde  = $request->string('desde', now()->startOfMonth()->format('Y-m-d'))->toString();
        $hasta  = $request->string('hasta', now()->format('Y-m-d'))->toString();
        $viewId = $request->query('view');

        $sales = Sale::with(['customer', 'user'])
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(fn (Sale $s) => [
                'id'       => $s->id,
                'fecha'    => $s->fecha->format('Y-m-d H:i'),
                'cliente'  => $s->customer instanceof Customer ? $s->customer->nombre : '—',
                'vendedor' => $s->user instanceof User ? $s->user->nombre : '—',
                'total'    => (float) $s->total,
                'estado'   => $s->estado,
            ]);

        $detail = null;
        if ($viewId !== null) {
            $sale = Sale::with(['customer', 'user', 'cancelledBy', 'items.product'])
                ->find((int) $viewId);

            if ($sale) {
                $detail = [
                    'id'          => $sale->id,
                    'fecha'       => $sale->fecha->format('Y-m-d H:i'),
                    'cliente'     => $sale->customer instanceof Customer ? $sale->customer->nombre : '—',
                    'vendedor'    => $sale->user instanceof User ? $sale->user->nombre : '—',
                    'total'       => (float) $sale->total,
                    'estado'      => $sale->estado,
                    'anulada_at'  => $sale->anulada_at?->format('Y-m-d H:i'),
                    'anulada_por' => $sale->cancelledBy?->nombre,
                    'items'       => $sale->items->map(fn (SaleItem $item) => [
                        'producto' => $item->product instanceof Product ? $item->product->descripcion : '—',
                        'cantidad' => (int) $item->cantidad,
                        'precio'   => (float) $item->precio,
                        'subtotal' => (float) $item->subtotal,
                    ])->all(),
                ];
            }
        }

        return Inertia::render('Sales/Index', [
            'sales'  => $sales,
            'detail' => $detail,
            'desde'  => $desde,
            'hasta'  => $hasta,
        ]);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        /** @var \App\Domains\Identity\Models\User $user */
        $user = Auth::user();

        try {
            $this->saleService->cancel($id, (int) $user->idusuario);
            return redirect()
                ->route('ventas.index', ['view' => $id])
                ->with('success', 'Venta anulada correctamente.');
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('ventas.index', ['view' => $id])
                ->with('error', $e->getMessage());
        } catch (RuntimeException $e) {
            return redirect()
                ->route('ventas.index', ['view' => $id])
                ->with('error', $e->getMessage());
        }
    }
}
