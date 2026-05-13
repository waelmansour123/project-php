<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';
$success = '';

// Check for registration success message
if (isset($_SESSION['reg_success'])) {
    $success = $_SESSION['reg_success'];
    unset($_SESSION['reg_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Fetch user from DB
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Setup Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="form-card">
                <h2 class="form-card-title">Login to SkillHub</h2>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <span>✅</span> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
                </form>

                <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
