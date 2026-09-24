# Inventario y precios por tienda

## Modelo

- El producto continúa perteneciendo a la cuenta y comparte código, descripción y política de control de stock.
- Cada tienda mantiene su propio precio, stock y disponibilidad en `store_inventory`.
- Una venta toma el precio de la tienda activa y sólo descuenta stock de esa tienda.
- Una anulación devuelve las unidades al inventario de la tienda donde se registró la venta.
- Un producto puede existir en el catálogo de la cuenta y no estar disponible para venta en una sucursal.

## Migración

- Los precios y existencias actuales se copian a todas las tiendas existentes de la cuenta.
- Las columnas históricas `producto.precio` y `producto.existencia` se conservan temporalmente para compatibilidad, pero dejaron de ser la fuente de verdad de las ventas.

## Próximos pasos

- Transferencias de mercadería entre tiendas con trazabilidad.
- Movimientos de inventario y ajustes con motivo, usuario y fecha.
- Alertas de stock mínimo configurables por tienda.
- Importación masiva de precios y existencias.
