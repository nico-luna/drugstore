<?php

declare(strict_types=1);

final class SaleService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $clientId, int $userId, array $rawLines): int
    {
        $lines = $this->normalizeLines($rawLines);
        if ($clientId < 1 || $lines === []) {
            throw new InvalidArgumentException('Seleccioná un cliente y al menos un producto.');
        }

        $this->db->beginTransaction();
        try {
            $client = $this->db->prepare('SELECT idcliente FROM cliente WHERE idcliente = ? AND estado = 1');
            $client->execute([$clientId]);
            if (!$client->fetchColumn()) {
                throw new InvalidArgumentException('El cliente seleccionado no está disponible.');
            }

            $ids = array_keys($lines);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $lock = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
            $products = $this->db->prepare(
                "SELECT codproducto, descripcion, precio, existencia, controla_stock
                 FROM producto WHERE estado = 1 AND codproducto IN ($placeholders)$lock"
            );
            $products->execute($ids);
            $found = [];
            foreach ($products->fetchAll() as $product) {
                $found[(int) $product['codproducto']] = $product;
            }
            if (count($found) !== count($ids)) {
                throw new InvalidArgumentException('Uno de los productos ya no está disponible.');
            }

            $total = 0.0;
            foreach ($lines as $productId => $quantity) {
                $product = $found[$productId];
                if ((bool) $product['controla_stock'] && (int) $product['existencia'] < $quantity) {
                    throw new InvalidArgumentException('Stock insuficiente para ' . $product['descripcion'] . '.');
                }
                $total += round((float) $product['precio'] * $quantity, 2);
            }

            $sale = $this->db->prepare('INSERT INTO ventas (id_cliente, total, id_usuario) VALUES (?, ?, ?)');
            $sale->execute([$clientId, number_format($total, 2, '.', ''), $userId]);
            $saleId = (int) $this->db->lastInsertId();

            $detail = $this->db->prepare(
                'INSERT INTO detalle_venta (id_producto, id_venta, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)'
            );
            $decrement = $this->db->prepare(
                'UPDATE producto SET existencia = existencia - ? WHERE codproducto = ? AND existencia >= ?'
            );

            foreach ($lines as $productId => $quantity) {
                $product = $found[$productId];
                $subtotal = round((float) $product['precio'] * $quantity, 2);
                $detail->execute([$productId, $saleId, $quantity, $product['precio'], number_format($subtotal, 2, '.', '')]);
                if ((bool) $product['controla_stock']) {
                    $decrement->execute([$quantity, $productId, $quantity]);
                    if ($decrement->rowCount() !== 1) {
                        throw new RuntimeException('El stock cambió durante la venta. Volvé a intentarlo.');
                    }
                }
            }

            $this->db->commit();
            return $saleId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel(int $saleId, int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
            $sale = $this->db->prepare('SELECT id, estado FROM ventas WHERE id = ?' . $lock);
            $sale->execute([$saleId]);
            $row = $sale->fetch();
            if (!$row) {
                throw new InvalidArgumentException('La venta no existe.');
            }
            if ($row['estado'] === 'anulada') {
                throw new InvalidArgumentException('La venta ya estaba anulada.');
            }

            $details = $this->db->prepare(
                'SELECT dv.id_producto, dv.cantidad, p.controla_stock
                 FROM detalle_venta dv JOIN producto p ON p.codproducto = dv.id_producto
                 WHERE dv.id_venta = ?' . $lock
            );
            $details->execute([$saleId]);
            $restore = $this->db->prepare('UPDATE producto SET existencia = existencia + ? WHERE codproducto = ?');
            foreach ($details->fetchAll() as $detail) {
                if ((bool) $detail['controla_stock']) {
                    $restore->execute([(int) $detail['cantidad'], (int) $detail['id_producto']]);
                }
            }

            $update = $this->db->prepare(
                "UPDATE ventas SET estado = 'anulada', anulada_at = CURRENT_TIMESTAMP, anulada_por = ? WHERE id = ? AND estado = 'confirmada'"
            );
            $update->execute([$userId, $saleId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('No se pudo anular la venta.');
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

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
