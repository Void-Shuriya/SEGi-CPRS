<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// Environment variables override the local XAMPP defaults; no .env loader is used.
$db_host = getenv('CPRS_DB_HOST');
if ($db_host === false) {
    $db_host = 'localhost';
}
$db_user = getenv('CPRS_DB_USER');
if ($db_user === false) {
    $db_user = 'root';
}
$db_password = getenv('CPRS_DB_PASSWORD');
if ($db_password === false) {
    $db_password = '';
}
$db_name = getenv('CPRS_DB_NAME');
if ($db_name === false) {
    $db_name = 'segi_cprs';
}
try {
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('CPRS database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('The CPR database is unavailable. Please check that MySQL is running and try again.');
}
