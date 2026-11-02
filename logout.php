<?php
require __DIR__ . '/includes/security.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}
validateCsrf();
endSession();
header('Location: index.php');
exit();
