# Drugstore MVP — Baseline de Referencia (Fase 0)

Este documento describe el estado del **MVP Drugstore** al momento de congelar la línea base (Fase 0).
Representa la especificación ejecutable exacta contra la cual se validará la paridad en la migración a Laravel + Vue (`Fase 1B`).

---

## 1. Comandos Exactos de Inicio y Operación

### Inicio con Docker (Entorno Principal)

1. **Copiar archivo de entorno**:
   ```bash
   cp .env.example .env
   ```

2. **Levantar contenedores**:
   ```bash
   docker compose up -d --build
   ```

3. **Crear Administrador Inicial (PowerShell / Bash)**:
   ```powershell
   $securePassword = Read-Host "Contraseña inicial" -AsSecureString
   $env:DRUGSTORE_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $securePassword).Password
   docker compose exec -e DRUGSTORE_ADMIN_PASSWORD app php scripts/create-admin.php
   Remove-Item Env:DRUGSTORE_ADMIN_PASSWORD
   ```

4. **Acceso Web**:
   - URL: `http://localhost:8081`

---

## 2. Healthcheck y Verificación de Estado

### Healthcheck HTTP / CLI
Para verificar el estado de conexión con la base de datos y métricas de registros:

```bash
docker compose exec app php scripts/status.php
```

Salida esperada:
```
database_timezone=-03:00
users=1
clients=1
products=0
sales=0
```

---

## 3. Estado Actual de la Suite de Tests

Se dispone del runner de pruebas `tests/run.php`:

```bash
php tests/run.php
```

### Reporte Diagnosticado en Auditoría:
- **Pruebas Sintácticas**: 100% de los archivos `.php` superan la comprobación de sintaxis (`php -l`).
- **Pruebas Unitarias (`tests/run.php`)**: La suite actual está configurada para ejecutar contra SQLite en memoria (`sqlite::memory:`). En la imagen Docker oficial `Dockerfile`, la extensión cargada es `pdo_mysql`, por lo que la ejecución local directa de `tests/run.php` requiere instalar la extensión `pdo_sqlite` o correr dentro de un runtime PHP con SQLite habilitado.
- **Alineación en Fase 1**: En la nueva arquitectura de Fase 1A/1B, la suite de pruebas se ejecutará con **Pest PHP**, habilitando tanto tests unitarios ultra-rápidos (SQLite) como tests de integración contra el contenedor **MySQL 8.4** para validar `SELECT ... FOR UPDATE`, locks y restricciones `DECIMAL`.

---

## 4. Documentación Baseline

Los detalles completos de arquitectura, modelo de datos, rutas, reglas de negocio, matriz de permisos y matriz de pruebas de regresión se encuentran en:

- [`docs/baseline/architecture.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/architecture.md)
- [`docs/baseline/database.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/database.md)
- [`docs/baseline/routes.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/routes.md)
- [`docs/baseline/business-rules.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/business-rules.md)
- [`docs/baseline/permissions.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/permissions.md)
- [`docs/baseline/regression-matrix.md`](file:///c:/Users/Nico/Desktop/drugstore-main/docs/baseline/regression-matrix.md)
- [`database/legacy-schema.sql`](file:///c:/Users/Nico/Desktop/drugstore-main/database/legacy-schema.sql)
