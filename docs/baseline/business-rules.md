# Reglas de Negocio Registradas (Baseline - Fase 0)

Este documento contiene la especificación de todas las reglas de negocio ejecutables identificadas en la base de código actual.

---

## 1. Autenticación y Seguridad de Acceso

1. **Credenciales Fuertes**:
   - Todo usuario/administrador creado debe cumplir: mínimo 12 caracteres, al menos una mayúscula, una minúscula, un número y un carácter especial.
2. **Mitigación de Fuerza Bruta**:
   - Se genera una clave identificadora con `SHA256(usuario + IP + APP_KEY)`.
   - Si se registran 5 intentos fallidos dentro de un intervalo de 15 minutos, la cuenta/IP queda bloqueada por 15 minutos (`bloqueado_hasta > NOW()`).
   - Al autenticar exitosamente, se eliminan los intentos fallidos previos de esa clave.
3. **Rehash Automático**:
   - Tras validar la clave con `password_verify()`, si el algoritmo o costo predeterminado de PHP ha cambiado (`password_needs_rehash()`), la contraseña se vuelve a encriptar y actualizar transparentemente en base de datos.
4. **Protección de Sesión**:
   - En cada login exitoso se ejecuta `session_regenerate_id(true)`.

---

## 2. Gestión de Usuarios y Permisos

1. **Control de Administradores**:
   - El atributo `es_admin = TRUE` concede acceso a todas las pantallas y acciones sin verificar permisos individuales en `detalle_permisos`.
   - Al deshabilitar (`estado = 0`) o eliminar un usuario, el sistema exige que permanezca al menos un administrador activo en el sistema.
2. **Permisos Granulares en Legacy**:
   - Los permisos asignables son exactamente 6: `configuracion`, `usuarios`, `clientes`, `productos`, `ventas`, `nueva_venta`.
   - El permiso `usuarios` otorga la capacidad de listar, crear, editar contraseñas y modificar permisos de cualquier usuario no administrador.
   - El permiso `productos` otorga control total para modificar nombres, códigos, precios y cantidades de inventario directas.

---

## 3. Gestión de Clientes

1. **Cliente Mostrador Protegido**:
   - Existe un cliente por defecto `idcliente = 1` denominado `"Público en general"`.
   - El cliente por defecto no puede ser eliminado ni deshabilitado.

---

## 4. Catálogo e Inventario Directo

1. **Unicidad de Código**:
   - Cada producto debe tener un atributo `codigo` único (ej. código de barras o SKU).
2. **Control de Stock Flag**:
   - Si `controla_stock = TRUE`, el sistema exige existencia suficiente para realizar la venta y descuenta stock automáticamente.
   - Si `controla_stock = FALSE`, el producto puede venderse con stock `0` o indeterminado sin descontar inventario.
3. **Edición Manual de Existencia**:
   - La edición de stock en `productos.php` sobrescribe directamente la columna `existencia` sin generar movimientos ni historial auditado.

---

## 5. Procesamiento de Ventas (`SaleService::create()`)

1. **Normalización y Consolidación**:
   - Si una petición de venta contiene el mismo `producto_id` múltiples veces, las cantidades se suman en un único renglón consolidado.
2. **Revalidación de Datos en Servidor**:
   - El precio del producto y la descripción se consultan nuevamente en el servidor dentro de la transacción, ignorando cualquier precio enviado desde el cliente web.
3. **Bloqueo Concurrente y Transaccionalidad**:
   - Las consultas de productos y clientes utilizan `SELECT ... FOR UPDATE` (en MySQL) para bloquear las filas hasta finalizar la transacción.
   - Si durante el descuento de stock (`UPDATE producto SET existencia = existencia - ? WHERE codproducto = ? AND existencia >= ?`) el `rowCount()` es diferente a 1, la transacción falla y realiza `rollBack()`.
4. **Generación de Detalle**:
   - Se inserta un renglón en `ventas` y N renglones en `detalle_venta` con `id_producto`, `id_venta`, `cantidad`, `precio` (unitario) y `subtotal`.

---

## 6. Anulación de Ventas (`SaleService::cancel()`)

1. **Estado Inmutable una vez Anulada**:
   - Solo se pueden anular ventas con estado `confirmada`. Intentar anular una venta previamente `anulada` lanza una excepción.
2. **Restauración de Stock**:
   - Al anular, se consulta el valor actual de `producto.controla_stock` para los productos incluidos en la venta. Si es `TRUE`, se devuelve la cantidad vendida al campo `existencia` (`UPDATE producto SET existencia = existencia + ?`).
3. **Auditoría de Anulación**:
   - Se actualiza `ventas.estado = 'anulada'`, `ventas.anulada_at = CURRENT_TIMESTAMP` y `ventas.anulada_por = $userId`.
