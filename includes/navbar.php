<?php
$is_admin_dir = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$root_prefix = $is_admin_dir ? '../' : '';
$admin_prefix = $is_admin_dir ? '' : 'admin/';
?>
<header class="navbar-header">
    <div class="navbar-container">
        <a href="<?php echo $root_prefix; ?>index.php" class="logo">
            <span class="logo-icon">🛠️</span> SkillHub
        </a>
        <nav class="nav-links">
            <a href="<?php echo $root_prefix; ?>services.php" class="nav-link">Services</a>
            
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="<?php echo $root_prefix; ?><?php echo $admin_prefix; ?>dashboard.php" class="nav-link admin-tag">Admin Dashboard</a>
                    <a href="<?php echo $root_prefix; ?><?php echo $admin_prefix; ?>services.php" class="nav-link">Manage Services</a>
                    <a href="<?php echo $root_prefix; ?><?php echo $admin_prefix; ?>users.php" class="nav-link">Manage Users</a>
                    <a href="<?php echo $root_prefix; ?><?php echo $admin_prefix; ?>reservations.php" class="nav-link">Manage Bookings</a>
                <?php else: ?>
                    
                    <a href="<?php echo $root_prefix; ?>reservations.php" class="nav-link">My Reservations</a>
                    <a href="<?php echo $root_prefix; ?>profile.php" class="nav-link">My Profile</a>
                <?php endif; ?>
                
                <div class="user-menu">
                    <?php
                    $db_file = $is_admin_dir ? '../includes/db.php' : 'includes/db.php';
                    require_once $db_file;
                    $stmt = $pdo->prepare("SELECT name, profile_image FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $nav_user = $stmt->fetch();
                    $avatar = ($nav_user && $nav_user['profile_image'] && file_exists($root_prefix . 'uploads/users/' . $nav_user['profile_image'])) ? $root_prefix . 'uploads/users/' . $nav_user['profile_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Profile" class="nav-avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                    <span class="nav-username"><?php echo htmlspecialchars($nav_user['name'] ?? 'User'); ?></span>
                    <a href="<?php echo $root_prefix; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
                </div>
            <?php else: ?>
                <a href="<?php echo $root_prefix; ?>login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?php echo $root_prefix; ?>register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </nav>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle Navigation">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</header>
