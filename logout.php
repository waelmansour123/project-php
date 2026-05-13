<?php
require_once 'includes/auth.php';

// Unset all session values
$_SESSION = array();

// If session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit;
?>
