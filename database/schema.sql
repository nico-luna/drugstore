SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS usuario (
    idusuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(190) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    clave VARCHAR(255) NOT NULL,
    es_admin BOOLEAN NOT NULL DEFAULT FALSE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idusuario),
    UNIQUE KEY uq_usuario_usuario (usuario),
    UNIQUE KEY uq_usuario_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permisos (
    id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    etiqueta VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permisos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (nombre, etiqueta) VALUES
    ('configuracion', 'Configuración'),
    ('usuarios', 'Usuarios'),
    ('clientes', 'Clientes'),
    ('productos', 'Productos'),
    ('ventas', 'Ventas'),
    ('nueva_venta', 'Nueva venta')
ON DUPLICATE KEY UPDATE etiqueta = VALUES(etiqueta);

CREATE TABLE IF NOT EXISTS detalle_permisos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_permiso SMALLINT UNSIGNED NOT NULL,
    id_usuario BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_permiso_usuario (id_permiso, id_usuario),
    KEY idx_detalle_permisos_usuario (id_usuario),
    CONSTRAINT fk_detalle_permisos_permiso FOREIGN KEY (id_permiso) REFERENCES permisos (id) ON DELETE CASCADE,
    CONSTRAINT fk_detalle_permisos_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (idusuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cliente (
    idcliente BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    direccion VARCHAR(200) NOT NULL DEFAULT '',
    usuario_id BIGINT UNSIGNED NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idcliente),
    KEY idx_cliente_nombre_estado (nombre, estado),
    KEY idx_cliente_usuario (usuario_id),
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (idusuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cliente (idcliente, nombre, telefono, direccion, usuario_id, estado)
VALUES (1, 'Público en general', '', 'S/D', NULL, TRUE)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estado = TRUE;

CREATE TABLE IF NOT EXISTS producto (
    codproducto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL,
    descripcion VARCHAR(200) NOT NULL,
    precio DECIMAL(12,2) UNSIGNED NOT NULL,
    existencia INT UNSIGNED NOT NULL DEFAULT 0,
    controla_stock BOOLEAN NOT NULL DEFAULT TRUE,
    usuario_id BIGINT UNSIGNED NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (codproducto),
    UNIQUE KEY uq_producto_codigo (codigo),
    KEY idx_producto_descripcion_estado (descripcion, estado),
    KEY idx_producto_usuario (usuario_id),
    CONSTRAINT fk_producto_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (idusuario) ON DELETE SET NULL,
    CONSTRAINT ck_producto_precio CHECK (precio >= 0),
    CONSTRAINT ck_producto_existencia CHECK (existencia >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ventas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_cliente BIGINT UNSIGNED NOT NULL,
    total DECIMAL(12,2) UNSIGNED NOT NULL,
    id_usuario BIGINT UNSIGNED NOT NULL,
    estado ENUM('confirmada', 'anulada') NOT NULL DEFAULT 'confirmada',
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anulada_at TIMESTAMP NULL DEFAULT NULL,
    anulada_por BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_ventas_fecha (fecha),
    KEY idx_ventas_cliente (id_cliente),
    KEY idx_ventas_usuario (id_usuario),
    KEY idx_ventas_estado (estado),
    CONSTRAINT fk_ventas_cliente FOREIGN KEY (id_cliente) REFERENCES cliente (idcliente) ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (idusuario) ON DELETE RESTRICT,
    CONSTRAINT fk_ventas_anulada_por FOREIGN KEY (anulada_por) REFERENCES usuario (idusuario) ON DELETE RESTRICT,
    CONSTRAINT ck_ventas_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_venta (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_producto BIGINT UNSIGNED NOT NULL,
    id_venta BIGINT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    precio DECIMAL(12,2) UNSIGNED NOT NULL,
    subtotal DECIMAL(12,2) UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_detalle_venta_producto (id_venta, id_producto),
    KEY idx_detalle_producto (id_producto),
    CONSTRAINT fk_detalle_venta_producto FOREIGN KEY (id_producto) REFERENCES producto (codproducto) ON DELETE RESTRICT,
    CONSTRAINT fk_detalle_venta_venta FOREIGN KEY (id_venta) REFERENCES ventas (id) ON DELETE CASCADE,
    CONSTRAINT ck_detalle_cantidad CHECK (cantidad > 0),
    CONSTRAINT ck_detalle_precio CHECK (precio >= 0),
    CONSTRAINT ck_detalle_subtotal CHECK (subtotal >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion (
    id TINYINT UNSIGNED NOT NULL,
    nombre VARCHAR(100) NOT NULL DEFAULT '',
    telefono VARCHAR(30) NOT NULL DEFAULT '',
    email VARCHAR(190) NOT NULL DEFAULT '',
    direccion VARCHAR(255) NOT NULL DEFAULT '',
    actualizado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT ck_configuracion_unica CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO configuracion (id, nombre, telefono, email, direccion)
VALUES (1, 'Drugstore', '', '', '')
ON DUPLICATE KEY UPDATE id = id;

CREATE TABLE IF NOT EXISTS intentos_login (
    identificador CHAR(64) NOT NULL,
    intentos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_intento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    bloqueado_hasta TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (identificador),
    KEY idx_intentos_login_ultimo (ultimo_intento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
