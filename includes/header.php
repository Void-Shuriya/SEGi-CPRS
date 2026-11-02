<?php
// Shared helper: open the page controllers, not this file directly.
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit('Page not found.');
}
$home_link = 'view_assigned_courses.php';
if ($_SESSION['role'] === 'admin') {
    $home_link = 'manage_courses.php';
}
?>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="header">
    <h2><a class="brand" href="<?php echo $home_link; ?>">SEGi CPRS</a></h2>
    <div class="header-user">
        <span>Welcome, <b><?php echo htmlspecialchars($_SESSION['fullname']); ?></b></span>
        <form method="POST" action="logout.php" class="inline-form logout-form">
            <?php echo csrfField(); ?><button type="submit" class="btn-logout">↪ Logout</button>
        </form>
    </div>
</header>
<dialog id="session-warning" aria-labelledby="session-title" aria-describedby="session-description">
    <h2 id="session-title">Are you still there?</h2>
    <p id="session-description">You will be signed out after 10 minutes of inactivity. Unsaved changes may be lost.</p>
    <p>Time remaining: <strong id="session-countdown">2:00</strong></p>
    <p id="session-status" role="status"></p>
    <div class="form-actions">
        <button type="button" id="continue-session" class="btn-primary">Continue Session</button>
        <form method="POST" action="logout.php" class="inline-form logout-form"><?php echo csrfField(); ?><button
                class="btn-secondary" type="submit">Log Out</button></form>
    </div>
</dialog>
<script id="session-config" type="application/json">
    <?php echo json_encode(
        [
            'csrf' => $_SESSION['csrf_token'],
            'remaining' => max(0, CPRS_IDLE_SECONDS - (time() - $_SESSION['last_activity'])),
        ],
        JSON_HEX_TAG | JSON_HEX_AMP,
    ); ?>

</script>
<script src="js/common.js" defer></script>
