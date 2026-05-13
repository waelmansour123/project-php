<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email address is already registered.';
        } else {
            // Handle Profile Image Upload
            $profile_image_name = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                $file_name_parts = explode('.', $file['name']);
                $file_ext = strtolower(end($file_name_parts));
                
                // Validate file extension
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF.';
                } 
                // Validate file size (limit to 2MB)
                elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error = 'Profile image must be smaller than 2MB.';
                } else {
                    // Check if directory exists
                    $upload_dir = 'uploads/users/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $profile_image_name = uniqid('user_', true) . '.' . $file_ext;
                    $dest_path = $upload_dir . $profile_image_name;
                    
                    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
                        $error = 'Failed to upload profile image.';
                    }
                }
            }

            // Insert into Database if no errors
            if (empty($error)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; // Default registration role

                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, profile_image, role) VALUES (?, ?, ?, ?, ?, ?)");
                
                if ($insert_stmt->execute([$name, $email, $hashed_password, $phone, $profile_image_name, $role])) {
                    $_SESSION['reg_success'] = 'Registration successful! You can now log in.';
                    header("Location: login.php");
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="form-card">
                <h2 class="form-card-title">Create an Account</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <span>⚠️</span> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="POST" enctype="multipart/form-data" class="register-form">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address <span style="color: var(--danger);">*</span></label>
                        <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password <span style="color: var(--danger);">*</span> (min. 6 characters)</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span style="color: var(--danger);">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label for="profile_image" class="form-label">Profile Image (Optional)</label>
                        <input type="file" id="profile_image" name="profile_image" class="form-input" accept="image/*">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register</button>
                </form>

                <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
