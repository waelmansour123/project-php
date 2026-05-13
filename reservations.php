<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Route protection
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if (isset($_SESSION['booking_success'])) {
    $success = $_SESSION['booking_success'];
    unset($_SESSION['booking_success']);
}

// Handle Reservation Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservation'])) {
    $reservation_id = (int)$_POST['reservation_id'];

    // Check if reservation exists, belongs to the current user, and is not already Cancelled or Completed
    $check_stmt = $pdo->prepare("
        SELECT status FROM reservations 
        WHERE id = ? AND user_id = ?
    ");
    $check_stmt->execute([$reservation_id, $user_id]);
    $res = $check_stmt->fetch();

    if ($res) {
        if ($res['status'] === 'Cancelled') {
            $error = 'This reservation is already cancelled.';
        } elseif ($res['status'] === 'Completed') {
            $error = 'Completed reservations cannot be cancelled.';
        } else {
            // Update reservation status to Cancelled
            $update_stmt = $pdo->prepare("
                UPDATE reservations 
                SET status = 'Cancelled' 
                WHERE id = ? AND user_id = ?
            ");
            if ($update_stmt->execute([$reservation_id, $user_id])) {
                $success = 'Reservation cancelled successfully.';
            } else {
                $error = 'Failed to cancel the reservation. Please try again.';
            }
        }
    } else {
        $error = 'Reservation not found or unauthorized access.';
    }
}

// Fetch all reservations for the logged-in user
$reservations_stmt = $pdo->prepare("
    SELECT r.*, s.title as service_title, s.price as service_price, s.image as service_image 
    FROM reservations r 
    JOIN services s ON r.service_id = s.id 
    WHERE r.user_id = ? 
    ORDER BY r.reservation_date DESC
");
$reservations_stmt->execute([$user_id]);
$reservations = $reservations_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 style="margin-bottom: 0.5rem;">My Reservations</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Track, view, and manage your booked services.</p>

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

            <?php if (empty($reservations)): ?>
                <div style="text-align: center; padding: 4rem 1.5rem; background-color: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow);">
                    <span style="font-size: 3.5rem;">📅</span>
                    <h3 style="margin-top: 1rem;">No Reservations Yet</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You have not made any service reservations yet. Browse services to find the perfect professional.</p>
                    <a href="services.php" class="btn btn-primary">Browse Services</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <?php
                                            $img_src = ($res['service_image'] && file_exists('uploads/services/' . $res['service_image'])) ? 'uploads/services/' . $res['service_image'] : 'assets/images/default-service.jpg';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($img_src); ?>" alt="Service Thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);" onerror="this.src='https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=150'">
                                            <strong style="color: var(--dark);"><?php echo htmlspecialchars($res['service_title']); ?></strong>
                                        </div>
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
                                        <?php if (in_array($res['status'], ['Pending', 'Accepted'])): ?>
                                            <form action="reservations.php" method="POST" style="display: inline-block;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $res['id']; ?>">
                                                <button type="submit" name="cancel_reservation" class="btn btn-danger btn-sm" 
                                                        data-confirm="Are you sure you want to cancel this reservation?">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: var(--text-muted); font-style: italic;">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
