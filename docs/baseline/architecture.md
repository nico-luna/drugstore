# Arquitectura del MVP (Baseline - Fase 0)

## 1. Visión General
El MVP actual de **Drugstore** es una aplicación monolítica liviana escrita en PHP 8.2 (sin framework MVC externo) utilizando PDO para la comunicación con MySQL 8.4 y HTML/CSS/JS nativo en el cliente.

- **Líneas de código aproximadas**: ~2.000 líneas.
- **Runtime**: PHP 8.2 en contenedor Apache (`php:8.2-apache`).
- **Base de datos**: MySQL 8.4 en contenedor independiente (puerto 3306 interno, 3307 forward en host).
- **Almacenamiento**:
  - `storage/logs/app.log`: Logs de excepciones y errores del sistema.
  - `storage/sessions`: Archivos de sesión nativos de PHP.

---

## 2. Componentes de la Arquitectura

```
                        BROWSER (Cliente)
                               │
                       HTTP (Port 8081)
                               │
                         Nginx / Apache
                               │
                   ┌───────────┴───────────┐
                   │   public/index.php    │  (Front Controller / Router)
                   └───────────┬───────────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
           bootstrap.php    Auth.php    SaleService.php
                │              │              │
                └──────────────┼──────────────┘
                               │
                         Database.php
                               │
                          PDO / MySQL
```

---

## 3. Capas del Sistema

### A. Capa HTTP / Vistas (`public/` y `public/src/`)
- `public/index.php`: Pantalla de inicio de sesión y validación de credenciales POST.
- `public/src/index.php`: Panel principal / Dashboard con tarjetas resumidas.
- `public/src/clientes.php`: Gestión CRUD de clientes (alta, edición, deshabilitación soft).
- `public/src/productos.php`: Gestión CRUD de catálogo y actualización directa de stock.
- `public/src/usuarios.php`: Gestión CRUD de usuarios, contraseñas y permisos.
- `public/src/nueva-venta.php`: Formulario de registro de venta.
- `public/src/ventas.php`: Listado de ventas e invocación de anulación.
- `public/src/configuracion.php`: Configuración de datos de la empresa.
- `public/src/logout.php`: Destrucción de sesión y redirección.

### B. Capa de Servicios y Core (`app/`)
- `app/bootstrap.php`: Punto de entrada de inicialización, carga de variables `.env`, configuración de sesiones, headers HTTP de seguridad (CSP, SameSite) y manejador global de excepciones.
- `app/env.php`: Parser de variables de entorno `.env` sin dependencias externas.
- `app/config.php`: Repositorio estático de configuración del sistema (`app.*`, `db.*`, `session.*`).
- `app/Database.php`: Wrapper de conexión PDO Singleton con manejo de reconexión y charset UTF-8.
- `app/helpers.php`: Funciones helper globales (`e()` para escape XSS, `url()`, `redirect()`, `flash()`, `csrf_*`, `money()`, `validate_password_strength()`).
- `app/Auth.php`: Servicio centralizado de autenticación, validación de permisos RBAC, control de intentos fallidos de login y rehash de contraseñas.
- `app/SaleService.php`: Capa de dominio aislada para creación de ventas en transacción con locking `FOR UPDATE` y anulación de ventas con devolución de stock.
- `app/views.php`: Renderizado de encabezado y pie de página HTML unificados.

---

## 4. Seguridad e Infraestructura Actual

| Mecanismo | Implementación | Evaluación Baseline |
| :--- | :--- | :--- |
| **Sentencias preparadas** | PDO en todas las consultas con parámetros | ✅ Correcto |
| **Protección XSS** | Escape explícito con `htmlspecialchars` (`e()`) | ✅ Correcto |
| **Protección CSRF** | Tokens en sesión verificados por `verify_csrf()` en POST | ✅ Correcto |
| **Hashing de Contraseñas** | `password_hash()` (PASSWORD_DEFAULT) + `password_verify()` | ✅ Correcto |
| **Rehash Automático** | `password_needs_rehash()` al iniciar sesión | ✅ Correcto |
| **Bloqueo por Intentos** | Tabla `intentos_login`: 5 intentos = 15 min bloqueo | ✅ Correcto |
| **Seguridad de Sesión** | `session_regenerate_id(true)`, `HttpOnly`, `SameSite=Lax` | ✅ Correcto |
| **Headers HTTP (CSP)** | Headers estrictos CSP, `nosniff`, `Referrer-Policy` | ✅ Correcto |
| **Contención Docker** | MySQL en red interna, puerto app en `127.0.0.1:8081` | ✅ Correcto |
