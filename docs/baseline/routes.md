# Inventario de Rutas y Endpoints (Baseline - Fase 0)

## 1. Mapeo de Rutas HTTP

Todas las rutas web son procesadas de forma síncrona mediante scripts PHP individuales bajo la raíz pública.

| Método HTTP | Ruta | Permiso Requerido | Archivo Fuente | Propósito / Acción |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/` | *(Ninguno / Público)* | `public/index.php` | Redirige a `src/index.php` si está autenticado; muestra login si no. |
| `POST` | `/` | *(Público con CSRF)* | `public/index.php` | Procesa intento de autenticación y regenera ID de sesión. |
| `GET` | `/src/index.php` | Autenticado | `public/src/index.php` | Muestra el Dashboard principal con métricas del día. |
| `GET` | `/src/clientes.php` | `clientes` | `public/src/clientes.php` | Muestra listado de clientes activos y formulario de alta/edición. |
| `POST` | `/src/clientes.php` | `clientes` | `public/src/clientes.php` | Procesa creación, actualización o desactivación (soft-delete) de cliente. |
| `GET` | `/src/productos.php` | `productos` | `public/src/productos.php` | Muestra catálogo de productos y stock actual. |
| `POST` | `/src/productos.php` | `productos` | `public/src/productos.php` | Procesa alta, modificación de datos/stock o desactivación de producto. |
| `GET` | `/src/usuarios.php` | `usuarios` | `public/src/usuarios.php` | Muestra usuarios del sistema y matriz de asignación de permisos. |
| `POST` | `/src/usuarios.php` | `usuarios` | `public/src/usuarios.php` | Procesa creación de usuario, actualización de clave o cambios de permisos. |
| `GET` | `/src/nueva-venta.php` | `nueva_venta` | `public/src/nueva-venta.php` | Carga el formulario de creación de venta (clientes y productos activos). |
| `POST` | `/src/nueva-venta.php` | `nueva_venta` | `public/src/nueva-venta.php` | Ejecuta `SaleService::create()` dentro de transacción `SELECT ... FOR UPDATE`. |
| `GET` | `/src/ventas.php` | `ventas` | `public/src/ventas.php` | Muestra historial de ventas, filtro por fecha y detalle de ítems. |
| `POST` | `/src/ventas.php` | `ventas` | `public/src/ventas.php` | Procesa la anulación de una venta mediante `SaleService::cancel()`. |
| `GET` | `/src/configuracion.php`| `configuracion` | `public/src/configuracion.php` | Muestra los datos institucionales de la empresa. |
| `POST` | `/src/configuracion.php`| `configuracion` | `public/src/configuracion.php` | Actualiza la información de la tabla `configuracion`. |
| `GET/POST`| `/src/logout.php` | Autenticado | `public/src/logout.php` | Destruye la sesión actual y redirige a la pantalla de login. |

---

## 2. Parámetros de Petición por Endpoint

### A. Login (`POST /`)
- `_csrf`: Token CSRF.
- `usuario`: String (máx 50 caracteres).
- `clave`: String.

### B. Clientes (`POST /src/clientes.php`)
- `_csrf`: Token CSRF.
- `action`: `create` | `update` | `toggle`.
- `idcliente`: Int ID (para update/toggle).
- `nombre`: String (requerido, máx 100).
- `telefono`: String (opcional, máx 30).
- `direccion`: String (opcional, máx 200).

### C. Productos (`POST /src/productos.php`)
- `_csrf`: Token CSRF.
- `action`: `create` | `update` | `toggle`.
- `codproducto`: Int ID.
- `codigo`: String (requerido, único, máx 50).
- `descripcion`: String (requerido, máx 200).
- `precio`: Float / Decimal (mayor o igual a 0).
- `existencia`: Int (mayor o igual a 0).
- `controla_stock`: Int / Bool (`1` o `0`).

### D. Nueva Venta (`POST /src/nueva-venta.php`)
- `_csrf`: Token CSRF.
- `cliente_id`: Int (requerido, cliente activo).
- `productos[]`: Array de IDs de productos.
- `cantidades[]`: Array de cantidades por producto.

### E. Anulación de Venta (`POST /src/ventas.php`)
- `_csrf`: Token CSRF.
- `action`: `cancel`.
- `id_venta`: Int (ID de venta a anular).
