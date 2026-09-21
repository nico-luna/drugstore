# Drugstore

Sistema web de gestión y punto de venta reconstruido en Laravel 13, Inertia y Vue 3.

Para publicar una instancia de prueba en Hostinger, consultar [HOSTINGER_DEPLOY.md](HOSTINGER_DEPLOY.md).

## Inicio rápido con Docker

1. Copiar `.env.example` como `.env` y reemplazar todas las claves marcadas para cambio.
2. Ejecutar `docker compose up -d --build`.
3. Crear el primer administrador:

   ```powershell
   $securePassword = Read-Host "Contraseña inicial" -AsSecureString
   $env:DRUGSTORE_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $securePassword).Password
   docker compose exec -e DRUGSTORE_ADMIN_PASSWORD app php scripts/create-admin.php
   Remove-Item Env:DRUGSTORE_ADMIN_PASSWORD
   ```

4. Abrir `http://localhost:8081`.

La contraseña debe tener al menos 12 caracteres, mayúsculas, minúsculas, números y símbolos.

## Inicio con PHP local

Se requiere PHP 8.4 con `pdo_mysql`, Composer 2, Node.js 22 y una base MySQL 8/MariaDB compatible.

1. Configurar `.env` con el host y puerto reales de MySQL.
2. Ejecutar `composer install`, `npm ci` y `npm run build`.
3. Ejecutar `php artisan migrate`.
4. Ejecutar `php scripts/create-admin.php` con `DRUGSTORE_ADMIN_PASSWORD` definido.
5. Iniciar `php -S 127.0.0.1:8081 -t public`.

## Verificaciones

```powershell
php artisan test
npm run type-check
npm run lint
npm run build
Get-ChildItem -Recurse -Filter *.php | Where-Object { $_.FullName -notmatch 'vendor|storage|node_modules' } | ForEach-Object { php -l $_.FullName }
```

El volcado de 2022 se considera legado y no debe importarse directamente: contenía credenciales débiles, referencias huérfanas y fechas de venta mutables.
