# Matriz de Permisos Legacy (Baseline - Fase 0)

Este documento define la matriz completa de permisos del sistema legacy y el mapeo exacto de autorización por vista y acción.

---

## 1. Permisos Registrados en la Base de Datos

| ID | Permiso (`nombre`) | Etiqueta | Alcance Funcional |
| :--- | :--- | :--- | :--- |
| 1 | `configuracion` | Configuración | Modificación de datos institucionales de la empresa. |
| 2 | `usuarios` | Usuarios | Alta, edición de datos, reseteo de claves y asignación de permisos de usuarios no administradores. |
| 3 | `clientes` | Clientes | Alta, edición de datos y deshabilitación soft de clientes. |
| 4 | `productos` | Productos | Alta, modificación de descripción, precio, código y alteración directa de existencia de stock. |
| 5 | `ventas` | Ventas | Visualización del historial de ventas, detalles de renglones y anulación de ventas confirmadas. |
| 6 | `nueva_venta` | Nueva venta | Acceso a la pantalla de registro de venta y procesamiento en el servidor (`SaleService`). |

---

## 2. Matriz de Autorización por Pantalla y Acción

- **`es_admin = TRUE`**: Posee bypass total (`Auth::can()` retorna `TRUE` implícitamente).
- **Usuarios No Administradores**: Requieren tener la fila correspondiente en `detalle_permisos`.

| Acceso / Acción | Permiso Legacy Requerido | Evaluación en `Auth::requirePermission()` |
| :--- | :--- | :--- |
| Ver Dashboard (`src/index.php`) | Autenticado | Muestra únicamente las tarjetas autorizadas (`clientes`, `productos`, `ventas`, `nueva_venta`). |
| Acceder a Clientes (`src/clientes.php`) | `clientes` | Bloquea con `403.php` si no lo tiene. |
| Crear / Editar / Toggle Cliente | `clientes` | Bloquea con `403.php` en POST si no lo tiene. |
| Acceder a Productos (`src/productos.php`)| `productos` | Bloquea con `403.php` si no lo tiene. |
| Editar Stock / Precio de Producto | `productos` | Bloquea con `403.php` en POST si no lo tiene. |
| Acceder a Usuarios (`src/usuarios.php`) | `usuarios` | Bloquea con `403.php` si no lo tiene. |
| Editar Permisos / Reset Clave Usuario | `usuarios` | Bloquea con `403.php` en POST si no lo tiene. |
| Registrar Venta (`src/nueva-venta.php`) | `nueva_venta` | Bloquea con `403.php` si no lo tiene. |
| Acceder a Ventas (`src/ventas.php`) | `ventas` | Bloquea con `403.php` si no lo tiene. |
| Anular Venta (`POST /src/ventas.php`) | `ventas` | Bloquea con `403.php` si no lo tiene. |
| Acceder a Configuración (`configuracion.php`)| `configuracion` | Bloquea con `403.php` si no lo tiene. |
