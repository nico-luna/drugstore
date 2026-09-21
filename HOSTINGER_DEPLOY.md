# Despliegue de prueba en Hostinger

El proyecto está preparado para un plan Web/Cloud de Hostinger con PHP 8.4, MySQL, acceso SSH y Composer 2. El frontend compilado se versiona en `public/build`, por lo que el servidor no necesita Node.js.

## 1. Preparar Hostinger

1. Crear un sitio o subdominio exclusivo para pruebas.
2. Seleccionar PHP 8.4 para el sitio y como versión principal del plan.
3. Crear una base MySQL y guardar host, nombre, usuario y contraseña.
4. Activar SSH y Git en hPanel.

## 2. Conectar el repositorio

En hPanel, abrir **Git**, usar `https://github.com/nico-luna/drugstore.git`, rama `main`, y dejar vacío el directorio de instalación para desplegar en `public_html`. El directorio debe estar vacío antes del primer despliegue.

La raíz incluye un `.htaccess` que bloquea archivos sensibles y deriva el tráfico a `public/`, que es el front controller de Laravel.

## 3. Configurar el entorno

Desde SSH, entrar en el directorio del sitio:

```sh
cd domains/TU-DOMINIO/public_html
cp .env.hostinger.example .env
```

Editar `.env` con el dominio, las credenciales MySQL y una contraseña administrativa temporal fuerte. No subir `.env` a Git.

## 4. Ejecutar el despliegue

```sh
chmod +x scripts/hostinger-deploy.sh
./scripts/hostinger-deploy.sh
```

El script instala exactamente `composer.lock` sin dependencias de desarrollo, genera `APP_KEY` si falta, migra MySQL, crea el administrador de forma idempotente y genera las cachés de producción.

Después del primer acceso, cambiar la contraseña y eliminar `DRUGSTORE_ADMIN_PASSWORD` de `.env`. Los despliegues siguientes omitirán la creación del administrador.

Si la ruta PHP del plan difiere, ejecutar:

```sh
PHP_BIN=/opt/alt/php84/usr/bin/php COMPOSER_BIN=/usr/local/bin/composer2 ./scripts/hostinger-deploy.sh
```

## 5. Verificación obligatoria

- Abrir `https://TU-DOMINIO/up` y confirmar respuesta `200`.
- Iniciar sesión con el administrador configurado.
- Cambiar la contraseña temporal desde Usuarios.
- Probar alta de cliente y producto, venta, descuento de stock, historial y anulación.
- Confirmar que `APP_DEBUG=false` y que HTTPS está forzado desde hPanel.

## Actualizaciones

Después de desplegar el último commit desde hPanel, repetir `./scripts/hostinger-deploy.sh`. Las migraciones y la creación del administrador son idempotentes.
