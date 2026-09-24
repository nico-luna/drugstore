# Base multicuenta y multitienda

## Decisiones vigentes

- Una cuenta representa una empresa cliente de la plataforma.
- Una cuenta puede tener varias tiendas o sucursales.
- La identidad del usuario es global y puede participar en varias cuentas.
- Los clientes y el catálogo se comparten dentro de la cuenta.
- Las ventas pertenecen siempre a una cuenta y a una tienda.
- El precio, la disponibilidad y el stock por tienda se implementan en la siguiente fase mediante una tabla de inventario por sucursal.
- El alta de cuentas será administrada durante el MVP; no hay registro público ni cobro automático en esta fase.

## Garantías del primer bloque

- La instalación existente se migra a `Cuenta principal` y `Casa Central`.
- Los usuarios existentes reciben una membresía y acceso a la tienda inicial.
- El rol de administrador se evalúa dentro de la cuenta activa y no se hereda a otras cuentas.
- Clientes y productos quedan aislados por `account_id`.
- Las ventas quedan aisladas por `account_id` y `store_id`.
- La configuración se resuelve por tienda.
- El contexto activo se valida en el servidor y se almacena en la sesión.
- El selector sólo muestra cuentas y tiendas autorizadas.
- El código de producto puede repetirse entre cuentas, pero no dentro de una misma cuenta.

## Fuera de alcance de este bloque

- Registro público y onboarding autoservicio.
- Invitaciones y recuperación de contraseña.
- Permisos granulares diferentes por cada cuenta para una misma identidad.
- Inventario, precios y transferencias por tienda.
- Planes, límites, suscripciones y pagos.
- Panel del operador de la plataforma.
- Sitio público comercial.

## Condición de cierre

El bloque se considera cerrado cuando las migraciones funcionan en SQLite y MySQL, toda la regresión existente permanece verde y existen pruebas negativas que impiden cambiar a una cuenta no autorizada o consultar productos de otra cuenta.
