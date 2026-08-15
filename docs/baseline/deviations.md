# Desviaciones Técnicas Aceptadas (Baseline / Foundation)

## 1. Servidor Web en Contenedor Docker: Apache con `mod_rewrite`

- **Definición Original**: Nginx + PHP-FPM.
- **Implementación Actual**: Apache 2.4 con `mod_rewrite` (`php:8.3-apache` / `php:8.5`).
- **Motivo de la Decisión**:
  1. Simplificación operativa: un único contenedor `app` resuelve la ejecución PHP y la entrega HTTP estática con configuración de seguridad unificada (`docker/security.conf`, CSP, headers estrictos y rewrite rules hacia `public/index.php`).
  2. Compatibilidad directa con la infraestructura existente del MVP sin requerir un contenedor adicional exclusivo para Nginx en local.
  3. En entornos de producción de alta demanda o balanceo de carga, se puede colocar un reverse-proxy Nginx frontal sin alterar la aplicación interna.
- **Impacto**: Cero impacto en reglas de negocio, modelos de datos, sesiones en Redis o transacciones de base de datos.
