<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
require_once __DIR__ . '/security.php';
$signed_in = isset($_SESSION['role'], $_SESSION['user_id'], $_SESSION['fullname'], $_SESSION['last_activity']);
$expired = $signed_in && time() - $_SESSION['last_activity'] >= CPRS_IDLE_SECONDS;
if (!$signed_in || $expired) {
    if ($expired) {
        endSession();
    }
    if (defined('CPRS_SESSION_ENDPOINT')) {
        http_response_code(401);
        header('Content-Type: application/json');
        exit(json_encode(['expired' => true]));
    }
    header('Location: index.php?expired=1');
    exit();
}
validateCsrf();
// Polling is not user activity.
if (!defined('CPRS_SESSION_ENDPOINT')) {
    $_SESSION['last_activity'] = time();
}
