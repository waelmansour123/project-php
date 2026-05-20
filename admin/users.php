<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$current_admin_id = $_SESSION['user_id'];
$success = '';
$error = '';

$action = sanitizeInput($_GET['action'] ?? '');
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


if ($action === 'delete' && $edit_id > 0) {
    if ($edit_id === $current_admin_id) {
        $error = "You cannot delete your own administrator account.";
    } else {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$edit_id]);
        $u = $stmt->fetch();
        
        if ($u) {
            // Delete user image if exists
            if (!empty($u['profile_image']) && file_exists('../uploads/users/' . $u['profile_image'])) {
                unlink('../uploads/users/' . $u['profile_image']);
            }
            
            $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($delete_stmt->execute([$edit_id])) {
                $_SESSION['admin_msg_success'] = "User account deleted successfully.";
            } else {
                $_SESSION['admin_msg_error'] = "Failed to delete user account.";
            }
        } else {
            $_SESSION['admin_msg_error'] = "User account not found.";
        }
    }
    header("Location: users.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'user');

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!in_array($role, ['user', 'admin'])) {
        $error = "Invalid user role selected.";
    } else {
      
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->execute([$email, $user_id]);
        if ($check_stmt->fetch()) {
            $error = "The email address is already in use by another user.";
        } else {
      
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, phone = ?, role = ? 
                WHERE id = ?
            ");
            if ($update_stmt->execute([$name, $email, $phone, $role, $user_id])) {
            
                if ($user_id === $current_admin_id) {
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;
                }
                $_SESSION['admin_msg_success'] = "User account updated successfully.";
                header("Location: users.php");
                exit;
            } else {
                $error = "Failed to update user account. Please try again.";
            }
        }
    }
}


if (isset($_SESSION['admin_msg_success'])) {
    $success = $_SESSION['admin_msg_success'];
    unset($_SESSION['admin_msg_success']);
}
if (isset($_SESSION['admin_msg_error'])) {
    $error = $_SESSION['admin_msg_error'];
    unset($_SESSION['admin_msg_error']);
}


$users_stmt = $pdo->query("SELECT * FROM users ORDER BY name ASC");
$users = $users_stmt->fetchAll();


$edit_user = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SkillHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="admin-layout">
            
                <aside class="admin-sidebar">
                    <h3 style="font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; padding-left: 0.5rem;">Administration</h3>
                    <a href="dashboard.php" class="admin-menu-link">📊 Dashboard</a>
                    <a href="services.php" class="admin-menu-link">🛠️ Services</a>
                    <a href="users.php" class="admin-menu-link active">👥 Users</a>
                    <a href="reservations.php" class="admin-menu-link">📅 Reservations</a>
                </aside>

       
                <div class="admin-main">
                    
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

              
                    <?php if ($action === 'edit' && $edit_user): ?>
                        <div class="profile-details" style="max-width: 600px; margin-bottom: 2rem;">
                            <h2 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Edit User Account</h2>
                            
                            <form action="users.php?action=edit&id=<?php echo $edit_user['id']; ?>" method="POST">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                                    <input type="text" id="name" name="name" class="form-input" required value="<?php echo htmlspecialchars($edit_user['name']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address <span style="color: var(--danger);">*</span></label>
                                    <input type="email" id="email" name="email" class="form-input" required value="<?php echo htmlspecialchars($edit_user['email']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="role" class="form-label">User Role <span style="color: var(--danger);">*</span></label>
                                    <select id="role" name="role" class="form-input" required>
                                        <option value="user" <?php echo ($edit_user['role'] === 'user') ? 'selected' : ''; ?>>Standard User</option>
                                        <option value="admin" <?php echo ($edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                </div>

                                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                                    <a href="users.php" class="btn btn-outline">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <h1 style="margin-bottom: 0.5rem;">User Management</h1>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Display and control registered platform users.</p>

               
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Avatar</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $avatar = ($u['profile_image'] && file_exists('../uploads/users/' . $u['profile_image'])) ? '../uploads/users/' . $u['profile_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid var(--border-color);" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($u['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo htmlspecialchars($u['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo ($u['role'] === 'admin') ? 'status-pending' : 'status-accepted'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                                
                                                <?php if ($u['id'] !== $current_admin_id): ?>
                                                    <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" 
                                                       data-confirm="Are you sure you want to permanently delete this user account?">Delete</a>
                                                <?php else: ?>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted); font-style: italic; padding: 0.4rem 0;">Self</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
