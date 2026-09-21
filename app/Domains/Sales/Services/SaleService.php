<?php

namespace App\Domains\Sales\Services;

use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SaleService
{
    /**
     * Creates a new sale in a database transaction with server-side price re-validation,
     * SELECT ... FOR UPDATE locking, and atomic stock decrements.
     *
     * @param array<int, array{producto_id: mixed, cantidad: mixed}> $rawLines
     */
    public function create(int $clientId, int $userId, array $rawLines): int
    {
        $lines = $this->normalizeLines($rawLines);
        if ($clientId < 1 || $lines === []) {
            throw new InvalidArgumentException('Seleccioná un cliente y al menos un producto.');
        }

        return DB::transaction(function () use ($clientId, $userId, $lines) {
            $client = Customer::where('idcliente', $clientId)
                ->where('estado', true)
                ->first();

            if (!$client) {
                throw new InvalidArgumentException('El cliente seleccionado no está disponible.');
            }

            $ids = array_keys($lines);
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            $query = Product::where('estado', true)
                ->whereIn('codproducto', $ids);

            if (!$isSqlite) {
                $query->lockForUpdate();
            }

            $products = $query->get(['codproducto', 'descripcion', 'precio', 'existencia', 'controla_stock']);

            $found = [];
            foreach ($products as $product) {
                $found[(int) $product->codproducto] = $product;
            }

            if (count($found) !== count($ids)) {
                throw new InvalidArgumentException('Uno de los productos ya no está disponible.');
            }

            $total = 0.0;
            foreach ($lines as $productId => $quantity) {
                $product = $found[$productId];
                if ((bool) $product->controla_stock && (int) $product->existencia < $quantity) {
                    throw new InvalidArgumentException('Stock insuficiente para ' . $product->descripcion . '.');
                }
                $total += round((float) $product->precio * $quantity, 2);
            }

            $sale = Sale::create([
                'id_cliente' => $clientId,
                'total' => number_format($total, 2, '.', ''),
                'id_usuario' => $userId,
                'estado' => 'confirmada',
                'fecha' => now(),
            ]);

            $saleId = (int) $sale->id;

            foreach ($lines as $productId => $quantity) {
                $product = $found[$productId];
                $subtotal = round((float) $product->precio * $quantity, 2);

                SaleItem::create([
                    'id_producto' => $productId,
                    'id_venta' => $saleId,
                    'cantidad' => $quantity,
                    'precio' => $product->precio,
                    'subtotal' => number_format($subtotal, 2, '.', ''),
                ]);

                if ((bool) $product->controla_stock) {
                    $affected = DB::table('producto')
                        ->where('codproducto', $productId)
                        ->where('existencia', '>=', $quantity)
                        ->decrement('existencia', $quantity);

                    if ($affected !== 1) {
                        throw new RuntimeException('El stock cambió durante la venta. Volvé a intentarlo.');
                    }
                }
            }

            return $saleId;
        });
    }

    /**
     * Cancels a confirmed sale and returns stock for stock-controlled items.
     */
    public function cancel(int $saleId, int $userId): void
    {
        DB::transaction(function () use ($saleId, $userId) {
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            $saleQuery = Sale::where('id', $saleId);
            if (!$isSqlite) {
                $saleQuery->lockForUpdate();
            }
            $sale = $saleQuery->first();

            if (!$sale) {
                throw new InvalidArgumentException('La venta no existe.');
            }
            if ($sale->estado === 'anulada') {
                throw new InvalidArgumentException('La venta ya estaba anulada.');
            }

            $itemsQuery = SaleItem::where('id_venta', $saleId)->with('product');
            if (!$isSqlite) {
                $itemsQuery->lockForUpdate();
            }
            $items = $itemsQuery->get();

            foreach ($items as $item) {
                if ($item->product && (bool) $item->product->controla_stock) {
                    DB::table('producto')
                        ->where('codproducto', $item->id_producto)
                        ->increment('existencia', (int) $item->cantidad);
                }
            }

            $affected = DB::table('ventas')
                ->where('id', $saleId)
                ->where('estado', 'confirmada')
                ->update([
                    'estado' => 'anulada',
                    'anulada_at' => now(),
                    'anulada_por' => $userId,
                ]);

            if ($affected !== 1) {
                throw new RuntimeException('No se pudo anular la venta.');
            }
        });
    }

    /**
     * @param array<int, array{producto_id: mixed, cantidad: mixed}> $rawLines
     * @return array<int, int>
     */
    private function normalizeLines(array $rawLines): array
    {
        $lines = [];
        foreach ($rawLines as $line) {
            $productId = filter_var($line['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($line['cantidad'] ?? null, FILTER_VALIDATE_INT);

            if (!$productId || !$quantity || $quantity < 1 || $quantity > 100000) {
                throw new InvalidArgumentException('Las cantidades y productos de la venta no son válidos.');
            }

            $lines[(int) $productId] = ($lines[(int) $productId] ?? 0) + (int) $quantity;
            if ($lines[(int) $productId] > 100000) {
                throw new InvalidArgumentException('La cantidad acumulada de un producto es demasiado alta.');
            }
        }
        return $lines;
    }
}
