<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Route protection
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    
    // Server-side validation
    if (empty($name) || empty($email)) {
        $error = 'Name and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email already exists for another user
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        
        if ($check_stmt->fetch()) {
            $error = 'This email address is already in use by another account.';
        } else {
            // Handle Profile Image Upload
            $profile_image_name = $user['profile_image']; // Default to current image
            
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
                    $upload_dir = 'uploads/users/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $profile_image_name = uniqid('user_', true) . '.' . $file_ext;
                    $dest_path = $upload_dir . $profile_image_name;
                    
                    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                        // Delete old profile image if it exists and is not the default
                        if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                            unlink($upload_dir . $user['profile_image']);
                        }
                    } else {
                        $error = 'Failed to upload profile image.';
                    }
                }
            }

            // Update Database if no errors
            if (empty($error)) {
                $update_stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, profile_image = ? 
                    WHERE id = ?
                ");
                
                if ($update_stmt->execute([$name, $email, $phone, $profile_image_name, $user_id])) {
                    $success = 'Profile updated successfully!';
                    
                    // Refresh session variables
                    $_SESSION['user_name'] = $name;
                    
                    // Refresh user data array
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } else {
                    $error = 'Failed to update profile. Please try again.';
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
    <title>My Profile - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 style="margin-bottom: 0.5rem;">Account Settings</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Manage your profile information and contact details.</p>

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

            <div class="profile-layout">
                <!-- User Summary Card -->
                <div class="profile-card">
                    <div class="profile-avatar-container">
                        <?php
                        $avatar = ($user['profile_image'] && file_exists('uploads/users/' . $user['profile_image'])) ? 'uploads/users/' . $user['profile_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                        ?>
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" class="profile-avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                    </div>
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="profile-role-badge admin">Administrator</span>
                    <?php else: ?>
                        <span class="profile-role-badge">Standard User</span>
                    <?php endif; ?>
                    
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1.5rem;">
                        Member since: <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>

                <!-- Update Form Card -->
                <div class="profile-details">
                    <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Personal Information</h3>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                            <input type="text" id="name" name="name" class="form-input" required value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span style="color: var(--danger);">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="profile_image" class="form-label">Change Profile Picture</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-input" accept="image/*">
                            <small style="color: var(--text-muted); font-size: 0.8rem;">Max size: 2MB. Formats: JPG, PNG, GIF.</small>
                        </div>

                        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="index.php" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
