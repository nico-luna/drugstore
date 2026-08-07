# Matriz de Casos de Prueba de Regresión (Baseline - Fase 0)

Esta matriz especifica los escenarios funcionales y de seguridad requeridos para validar la **paridad del 100% (Baseline Parity)** durante la Fase 1B.

---

## 1. Escenarios de Autenticación y Seguridad

| ID Escenario | Categoría | Acción / Inputs | Resultado Esperado en Baseline MVP | Criterio Paridad Fase 1B |
| :--- | :--- | :--- | :--- | :--- |
| `TC-AUTH-01` | Autenticación | Login con credenciales válidas. | Inicia sesión, regenera ID de sesión, redirige a `src/index.php`. | Mismo flujo y redirección. |
| `TC-AUTH-02` | Autenticación | Login con contraseña incorrecta. | Falla con mensaje "Usuario o contraseña incorrectos", incrementa `intentos_login`. | Mismo error y registro de intento. |
| `TC-AUTH-03` | Seguridad | 5 intentos fallidos consecutivos en <15 min. | Bloquea la cuenta/IP por 15 minutos con mensaje "Demasiados intentos...". | Mismo comportamiento de bloqueo. |
| `TC-AUTH-04` | Seguridad | Enviar formulario POST sin token `_csrf` o inválido. | Detiene ejecución, retorna HTTP 403 y renderiza `errors/csrf.php`. | HTTP 403 y bloqueo CSRF. |
| `TC-AUTH-05` | Contraseñas | Intentar crear usuario con clave `< 12` caracteres. | Rechaza con mensaje "La contraseña debe tener al menos 12 caracteres." | Misma validación. |
| `TC-AUTH-06` | Contraseñas | Crear usuario con clave sin símbolos ni mayúsculas. | Rechaza con mensaje de requerimiento de complejidad. | Misma validación. |

---

## 2. Escenarios de Ventas e Inventario (`SaleService`)

| ID Escenario | Categoría | Acción / Inputs | Resultado Esperado en Baseline MVP | Criterio Paridad Fase 1B |
| :--- | :--- | :--- | :--- | :--- |
| `TC-SALE-01` | Venta Válida | Venta de producto con stock disponible (`existencia = 10`, `cantidad = 2`). | Crea venta, inserta renglones en `detalle_venta`, descuenta stock a `8`, commit de transacción. | Mismo descuento y registros. |
| `TC-SALE-02` | Stock | Intentar vender `cantidad = 20` de producto con `existencia = 10`. | Rechaza con `InvalidArgumentException("Stock insuficiente para...")`, `rollBack()` total. | Mismo rechazo y rollback. |
| `TC-SALE-03` | Revalidación | Modificar precio de producto en el cliente antes de enviar el POST. | El servidor ignora el precio enviado y calcula el total consultando la base de datos. | Mismo cálculo en servidor. |
| `TC-SALE-04` | Lock | Ventas concurrentes sobre el mismo producto. | Utiliza `SELECT ... FOR UPDATE` (MySQL), procesa en serie sin overselling. | Mismo bloqueo transaccional. |
| `TC-SALE-05` | Anulación | Anular venta confirmada con productos que `controla_stock = TRUE`. | Cambia `estado = 'anulada'`, registra `anulada_at` y `anulada_por`, restaura el stock. | Misma restauración de stock. |
| `TC-SALE-06` | Anulación | Re-intentar anular una venta que ya figura como `anulada`. | Lanza excepción `InvalidArgumentException("La venta ya estaba anulada.")`. | Mismo error. |

---

## 3. Escenarios de Administración y Permisos

| ID Escenario | Categoría | Acción / Inputs | Resultado Esperado en Baseline MVP | Criterio Paridad Fase 1B |
| :--- | :--- | :--- | :--- | :--- |
| `TC-PERM-01` | Autorización | Usuario sin permiso `productos` intenta acceder a `src/productos.php`. | HTTP 403 y renderizado de `errors/403.php`. | Mismo bloqueo 403. |
| `TC-PERM-02` | Usuarios | Desactivar o desmarcar `es_admin` al único administrador activo. | Sistema bloquea la acción para garantizar al menos 1 admin operativo. | Misma protección de admin. |
| `TC-PERM-03` | Clientes | Intentar eliminar o deshabilitar cliente `idcliente = 1` ("Público en general"). | Acción denegada por la aplicación. | Mismo bloqueo. |
