<?php
define('CPRS_SESSION_ENDPOINT', true);

require __DIR__ . '/includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['last_activity'] = time();
}
echo json_encode(['remaining' => max(0, CPRS_IDLE_SECONDS - (time() - $_SESSION['last_activity']))]);
