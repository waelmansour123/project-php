<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();


$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_services = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$total_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();


$most_booked_stmt = $pdo->query("
    SELECT s.title, c.name as category_name, s.price, COUNT(r.id) as bookings_count
    FROM services s
    LEFT JOIN reservations r ON s.id = r.service_id
    LEFT JOIN categories c ON s.category_id = c.id
    GROUP BY s.id
    ORDER BY bookings_count DESC
    LIMIT 5
");
$most_booked_services = $most_booked_stmt->fetchAll();


$recent_stmt = $pdo->query("
    SELECT r.*, u.name as user_name, s.title as service_title
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN services s ON r.service_id = s.id
    ORDER BY r.reservation_date DESC
    LIMIT 5
");
$recent_reservations = $recent_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SkillHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="admin-layout">
                
                <aside class="admin-sidebar">
                    <h3 style="font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; padding-left: 0.5rem;">Administration</h3>
                    <a href="dashboard.php" class="admin-menu-link active">📊 Dashboard</a>
                    <a href="services.php" class="admin-menu-link">🛠️ Services</a>
                    <a href="users.php" class="admin-menu-link">👥 Users</a>
                    <a href="reservations.php" class="admin-menu-link">📅 Reservations</a>
                </aside>

                
                <div class="admin-main">
                    <h1 style="margin-bottom: 0.5rem;">Admin Dashboard</h1>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">Overview of platform performance and statistics.</p>

                   
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon users">👥</div>
                            <div class="stat-details">
                                <h3>Total Users</h3>
                                <div class="value"><?php echo $total_users; ?></div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon services">🛠️</div>
                            <div class="stat-details">
                                <h3>Total Services</h3>
                                <div class="value"><?php echo $total_services; ?></div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon reservations">📅</div>
                            <div class="stat-details">
                                <h3>Total Bookings</h3>
                                <div class="value"><?php echo $total_reservations; ?></div>
                            </div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;">
                       
                        <div style="background-color: var(--white); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow);">
                            <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">🔥 Most Booked Services</h2>
                            
                            <table class="data-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Service Name</th>
                                        <th>Category</th>
                                        <th style="text-align: right;">Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($most_booked_services)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; color: var(--text-muted);">No bookings yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($most_booked_services as $mb): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($mb['title']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($mb['category_name'] ?? 'General'); ?></td>
                                                <td style="text-align: right; font-weight: bold; color: var(--primary);"><?php echo $mb['bookings_count']; ?> bookings</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="background-color: var(--white); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow);">
                            <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">🔔 Recent Reservations</h2>
                            
                            <table class="data-table" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_reservations)): ?>
                                        <tr>
                                            <td colspan="3" style="text-align: center; color: var(--text-muted);">No reservations found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_reservations as $rr): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($rr['user_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($rr['service_title']); ?></td>
                                                <td>
                                                    <?php
                                                    $rr_status = '';
                                                    switch ($rr['status']) {
                                                        case 'Pending': $rr_status = 'status-pending'; break;
                                                        case 'Accepted': $rr_status = 'status-accepted'; break;
                                                        case 'Completed': $rr_status = 'status-completed'; break;
                                                        case 'Cancelled': $rr_status = 'status-cancelled'; break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $rr_status; ?>" style="font-size: 0.75rem; padding: 0.15rem 0.5rem;">
                                                        <?php echo $rr['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
