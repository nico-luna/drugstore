<?php

declare(strict_types=1);

final class Auth
{
    private static ?array $cachedUser = null;

    public static function attempt(string $username, string $password): bool
    {
        $db = Database::connection();
        $identifier = self::attemptIdentifier($username);

        if (self::isLocked($db, $identifier)) {
            throw new RuntimeException('Demasiados intentos. Esperá 15 minutos antes de volver a probar.');
        }

        $statement = $db->prepare('SELECT idusuario, clave FROM usuario WHERE usuario = ? AND estado = 1 LIMIT 1');
        $statement->execute([$username]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['clave'])) {
            self::recordFailedAttempt($db, $identifier);
            return false;
        }

        $db->prepare('DELETE FROM intentos_login WHERE identificador = ?')->execute([$identifier]);
        if (password_needs_rehash($user['clave'], PASSWORD_DEFAULT)) {
            $db->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user['idusuario']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['idusuario'];
        self::$cachedUser = null;
        return true;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id < 1) {
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT u.idusuario, u.nombre, u.correo, u.usuario, u.es_admin, u.estado,
                    GROUP_CONCAT(p.nombre ORDER BY p.nombre SEPARATOR \',\') AS permisos
             FROM usuario u
             LEFT JOIN detalle_permisos dp ON dp.id_usuario = u.idusuario
             LEFT JOIN permisos p ON p.id = dp.id_permiso
             WHERE u.idusuario = ?
             GROUP BY u.idusuario'
        );
        $statement->execute([$id]);
        $user = $statement->fetch();
        if (!$user || !(bool) $user['estado']) {
            self::logout();
            return null;
        }
        $user['permisos'] = $user['permisos'] ? explode(',', $user['permisos']) : [];
        self::$cachedUser = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        return $user !== null && ((bool) $user['es_admin'] || in_array($permission, $user['permisos'], true));
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Iniciá sesión para continuar.');
            redirect('index.php');
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::can($permission)) {
            http_response_code(403);
            require BASE_PATH . '/public/errors/403.php';
            exit;
        }
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private static function attemptIdentifier(string $username): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
        return hash('sha256', strtolower(trim($username)) . '|' . $ip . '|' . config('app.key'));
    }

    private static function isLocked(PDO $db, string $identifier): bool
    {
        $statement = $db->prepare('SELECT bloqueado_hasta > NOW() AS bloqueado FROM intentos_login WHERE identificador = ?');
        $statement->execute([$identifier]);
        return (bool) ($statement->fetchColumn() ?: false);
    }

    private static function recordFailedAttempt(PDO $db, string $identifier): void
    {
        $statement = $db->prepare(
            'INSERT INTO intentos_login (identificador, intentos, ultimo_intento, bloqueado_hasta)
             VALUES (?, 1, NOW(), NULL)
             ON DUPLICATE KEY UPDATE
                intentos = IF(ultimo_intento < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, intentos + 1),
                bloqueado_hasta = IF(
                    IF(ultimo_intento < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, intentos) >= 5,
                    DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                    bloqueado_hasta
                ),
                ultimo_intento = NOW()'
        );
        $statement->execute([$identifier]);
    }
}
