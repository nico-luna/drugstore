# Base de Datos y Relaciones (Baseline - Fase 0)

## 1. Motores y Configuración
- **Motor**: MySQL 8.4 (InnoDB)
- **Charset / Collation**: `utf8mb4` / `utf8mb4_unicode_ci`
- **Zona Horaria**: `-03:00` (`America/Argentina/Buenos_Aires`)

---

## 2. Inventario de Tablas

### A. Tabla `usuario`
Almacena las cuentas de usuario y administradores del sistema.

| Columna | Tipo | Nulo | Default | Descripción / Restricciones |
| :--- | :--- | :--- | :--- | :--- |
| `idusuario` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `nombre` | VARCHAR(100) | NO | | Nombre completo |
| `correo` | VARCHAR(190) | NO | | Email de acceso (UNIQUE) |
| `usuario` | VARCHAR(50) | NO | | Nombre de usuario (UNIQUE) |
| `clave` | VARCHAR(255) | NO | | Hash bcrypt |
| `es_admin` | BOOLEAN | NO | FALSE | Superusuario (omite checks de permisos) |
| `estado` | BOOLEAN | NO | TRUE | Soft-disable (`1` activo, `0` inactivo) |
| `creado_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Fecha de creación |
| `actualizado_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Fecha de modificación (ON UPDATE) |

### B. Tabla `permisos`
Catálogo de permisos asignables a usuarios no administradores.

| Columna | Tipo | Nulo | Default | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `id` | SMALLINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `nombre` | VARCHAR(50) | NO | | Identificador del permiso (UNIQUE) |
| `etiqueta` | VARCHAR(80) | NO | | Etiqueta legible |

#### Registros Iniciales (Semilla):
1. `configuracion` -> Configuración
2. `usuarios` -> Usuarios
3. `clientes` -> Clientes
4. `productos` -> Productos
5. `ventas` -> Ventas
6. `nueva_venta` -> Nueva venta

### C. Tabla `detalle_permisos`
Pivote de permisos por usuario (relación Muchos a Muchos).

| Columna | Tipo | Nulo | Default | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `id_permiso` | SMALLINT UNSIGNED | NO | | FK a `permisos.id` (ON DELETE CASCADE) |
| `id_usuario` | BIGINT UNSIGNED | NO | | FK a `usuario.idusuario` (ON DELETE CASCADE) |

- **Restricción Única**: `UQ(id_permiso, id_usuario)`

### D. Tabla `cliente`
Clientes registrados y cliente por defecto para mostrador.

| Columna | Tipo | Nulo | Default | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `idcliente` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `nombre` | VARCHAR(100) | NO | | Nombre completo / Razón social |
| `telefono` | VARCHAR(30) | NO | `''` | Teléfono |
| `direccion` | VARCHAR(200) | NO | `''` | Dirección |
| `usuario_id` | BIGINT UNSIGNED | SI | NULL | Usuario que lo creó (FK `usuario`, ON DELETE SET NULL) |
| `estado` | BOOLEAN | NO | TRUE | Soft-disable (`1` activo, `0` inactivo) |
| `creado_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Fecha de creación |
| `actualizado_at` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Fecha de actualización |

#### Registro Inicial:
- `idcliente = 1`: "Público en general" (S/D)

### E. Tabla `producto`
Catálogo de productos e inventario mutable directo.

| Columna | Tipo | Nulo | Default | Descripción / CHECK |
| :--- | :--- | :--- | :--- | :--- |
| `codproducto` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `codigo` | VARCHAR(50) | NO | | Código numérico / SKU / Barcode (UNIQUE) |
| `descripcion` | VARCHAR(200) | NO | | Nombre del producto |
| `precio` | DECIMAL(12,2) UNSIGNED| NO | | Precio de venta (CHECK `>= 0`) |
| `existencia` | INT UNSIGNED | NO | 0 | Stock actual directo (CHECK `>= 0`) |
| `controla_stock` | BOOLEAN | NO | TRUE | Si requiere descuente de inventario |
| `usuario_id` | BIGINT UNSIGNED | SI | NULL | Usuario creador (FK `usuario`, ON DELETE SET NULL) |
| `estado` | BOOLEAN | NO | TRUE | Soft-disable |

### F. Tabla `ventas`
Encabezado de ventas realizadas.

| Columna | Tipo | Nulo | Default | Descripción / CHECK |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `id_cliente` | BIGINT UNSIGNED | NO | | FK a `cliente.idcliente` (ON DELETE RESTRICT) |
| `total` | DECIMAL(12,2) UNSIGNED| NO | | Importe total pagado (CHECK `>= 0`) |
| `id_usuario` | BIGINT UNSIGNED | NO | | Operador que registró (FK `usuario`, RESTRICT) |
| `estado` | ENUM('confirmada','anulada')| NO | 'confirmada' | Estado de la venta |
| `fecha` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Timestamp de registración |
| `anulada_at` | TIMESTAMP | SI | NULL | Fecha de anulación |
| `anulada_por` | BIGINT UNSIGNED | SI | NULL | Usuario que anuló (FK `usuario`, RESTRICT) |

### G. Tabla `detalle_venta`
Renglones y productos incluidos en cada venta.

| Columna | Tipo | Nulo | Default | Descripción / CHECK |
| :--- | :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | NO | AUTO_INCREMENT | Clave Primaria |
| `id_producto` | BIGINT UNSIGNED | NO | | FK a `producto.codproducto` (RESTRICT) |
| `id_venta` | BIGINT UNSIGNED | NO | | FK a `ventas.id` (ON DELETE CASCADE) |
| `cantidad` | INT UNSIGNED | NO | | Cantidad vendida (CHECK `> 0`) |
| `precio` | DECIMAL(12,2) UNSIGNED| NO | | Precio unitario al momento de la venta |
| `subtotal` | DECIMAL(12,2) UNSIGNED| NO | | `cantidad * precio` |

- **Restricción Única**: `UQ(id_venta, id_producto)`

### H. Tabla `configuracion`
Configuración única de la empresa.

| Columna | Tipo | Nulo | Default | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `id` | TINYINT UNSIGNED | NO | | Primary Key (CHECK `id = 1`) |
| `nombre` | VARCHAR(100) | NO | `''` | Nombre de la empresa |
| `telefono` | VARCHAR(30) | NO | `''` | Teléfono de contacto |
| `email` | VARCHAR(190) | NO | `''` | Email institucional |
| `direccion` | VARCHAR(255) | NO | `''` | Domicilio fiscal |

### I. Tabla `intentos_login`
Registro para mitigación de ataques de fuerza bruta.

| Columna | Tipo | Nulo | Default | Descripción |
| :--- | :--- | :--- | :--- | :--- |
| `identificador` | CHAR(64) | NO | | Hash `SHA256(usuario + IP + APP_KEY)` |
| `intentos` | TINYINT UNSIGNED | NO | 0 | Conteo de fallos consecutivos |
| `ultimo_intento` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Timestamp del último fallo |
| `bloqueado_hasta` | TIMESTAMP | SI | NULL | Expiración de bloqueo |
