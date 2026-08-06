<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';

if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
verify_csrf();
Auth::logout();
redirect('index.php');
