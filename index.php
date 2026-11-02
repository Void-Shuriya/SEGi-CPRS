<?php
require __DIR__ . '/includes/security.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
validateCsrf();
$error_msg = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(getPostText('username'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    if ($username === '' || strlen($username) > 50 || $password === '' || strlen($password) > 255) {
        $error_msg = 'Enter your username and password.';
    } else {
        try {
            $stmt = $conn->prepare('SELECT user_id, username, fullname, password, role FROM users WHERE username=?');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            // Local demonstration accounts use plain-text passwords from the starter SQL.
            $valid = $user && $password === $user['password'];
            if ($valid && in_array($user['role'], ['admin', 'lecturer'], true)) {
                session_regenerate_id(true);
                $_SESSION = [
                    'user_id' => $user['user_id'],
                    'role' => $user['role'],
                    'fullname' => $user['fullname'],
                    'last_activity' => time(),
                    'csrf_token' => bin2hex(random_bytes(32)),
                ];
                header('Location: ' . ($user['role'] === 'admin' ? 'manage_courses.php' : 'view_assigned_courses.php'));
                exit();
            }
            $error_msg = 'Invalid username or password.';
        } catch (mysqli_sql_exception $exception) {
            error_log('CPRS login failed: ' . $exception->getMessage());
            $error_msg = 'Login is temporarily unavailable. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEGi CPRS - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="login-page">
    <main class="login-box">
        <h1>SEGi CPRS</h1>
        <p class="login-subtitle">Course Performance Report System</p>
        <?php if (
            isset($_GET['expired'])
        ): ?><p class="notice" role="status">Your session ended. Sign in to continue.</p><?php endif; ?>
        <?php if ($error_msg !== ''): ?><p class="error" role="alert"><?php echo htmlspecialchars(
    $error_msg,
); ?></p><?php endif; ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input id="username" name="username" type="text" placeholder="Username" aria-label="Username" value="<?php echo htmlspecialchars(
                $username,
            ); ?>" maxlength="50" autocomplete="username" required>
            <input id="password" name="password" type="password" placeholder="Password" aria-label="Password"
                maxlength="255" autocomplete="current-password" required>
            <button type="submit">Login</button>
        </form>
    </main>
</body>

</html>
