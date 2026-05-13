<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Route protection
requireAdmin();

$success = '';
$error = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $status = sanitizeInput($_POST['status'] ?? '');
    
    $allowed_statuses = ['Pending', 'Accepted', 'Completed', 'Cancelled'];

    if ($reservation_id <= 0 || !in_array($status, $allowed_statuses)) {
        $error = "Invalid status update request.";
    } else {
        $update_stmt = $pdo->prepare("
            UPDATE reservations 
            SET status = ? 
            WHERE id = ?
        ");
        if ($update_stmt->execute([$status, $reservation_id])) {
            $success = "Reservation #$reservation_id status updated to '$status'.";
        } else {
            $error = "Failed to update reservation status.";
        }
    }
}

// Fetch all reservations in the system
$reservations_stmt = $pdo->query("
    SELECT r.*, u.name as user_name, u.email as user_email, u.phone as user_phone, 
           s.title as service_title, s.price as service_price
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN services s ON r.service_id = s.id
    ORDER BY r.reservation_date DESC
");
$reservations = $reservations_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - SkillHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <div class="admin-layout">
                <!-- Sidebar Menu -->
                <aside class="admin-sidebar">
                    <h3 style="font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; padding-left: 0.5rem;">Administration</h3>
                    <a href="dashboard.php" class="admin-menu-link">📊 Dashboard</a>
                    <a href="services.php" class="admin-menu-link">🛠️ Services</a>
                    <a href="users.php" class="admin-menu-link">👥 Users</a>
                    <a href="reservations.php" class="admin-menu-link active">📅 Reservations</a>
                </aside>

                <!-- Content Area -->
                <div class="admin-main">
                    <h1 style="margin-bottom: 0.5rem;">Reservation Management</h1>
                    <p style="color: var(--text-muted); margin-bottom: 2rem;">Track and change the status of bookings made by platform customers.</p>

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

                    <!-- Reservations Table -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer Details</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Price</th>
                                    <th>Current Status</th>
                                    <th style="text-align: center;">Change Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No reservations have been placed yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $res): ?>
                                        <tr>
                                            <td>#<?php echo $res['id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($res['user_name']); ?></strong>
                                                <span style="display: block; font-size: 0.8rem; color: var(--text-muted);">📧 <?php echo htmlspecialchars($res['user_email']); ?></span>
                                                <span style="display: block; font-size: 0.8rem; color: var(--text-muted);">📞 <?php echo htmlspecialchars($res['user_phone'] ?? 'N/A'); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($res['service_title']); ?></strong>
                                            </td>
                                            <td>
                                                <strong><?php echo date('M d, Y', strtotime($res['reservation_date'])); ?></strong>
                                                <span style="display: block; font-size: 0.85rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($res['reservation_date'])); ?></span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--dark);">$<?php echo number_format($res['service_price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($res['status']) {
                                                    case 'Pending': $status_class = 'status-pending'; break;
                                                    case 'Accepted': $status_class = 'status-accepted'; break;
                                                    case 'Completed': $status_class = 'status-completed'; break;
                                                    case 'Cancelled': $status_class = 'status-cancelled'; break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo htmlspecialchars($res['status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <form action="reservations.php" method="POST" style="display: flex; gap: 0.25rem; justify-content: center; align-items: center;">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                    
                                                    <select name="status" class="form-input" style="padding: 0.35rem 0.5rem; font-size: 0.85rem; width: auto; height: auto; min-width: 100px;">
                                                        <option value="Pending" <?php echo ($res['status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Accepted" <?php echo ($res['status'] === 'Accepted') ? 'selected' : ''; ?>>Accepted</option>
                                                        <option value="Completed" <?php echo ($res['status'] === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                                                        <option value="Cancelled" <?php echo ($res['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                    
                                                    <button type="submit" name="update_status" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.75rem;">
                                                        Update
                                                    </button>
                                                </form>
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
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
