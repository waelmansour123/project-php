<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';


requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $reservation_date = sanitizeInput($_POST['reservation_date'] ?? '');
    $user_id = $_SESSION['user_id'];

    if ($service_id <= 0 || empty($reservation_date)) {
        $_SESSION['booking_error'] = 'Invalid reservation details.';
        header("Location: service-details.php?id=" . $service_id);
        exit;
    }


    $service_stmt = $pdo->prepare("SELECT id FROM services WHERE id = ?");
    $service_stmt->execute([$service_id]);
    if (!$service_stmt->fetch()) {
        $_SESSION['booking_error'] = 'Service does not exist.';
        header("Location: services.php");
        exit;
    }

    $current_time = time();
    $booking_time = strtotime($reservation_date);

    if (!$booking_time || $booking_time <= $current_time) {
        $_SESSION['booking_error'] = 'Please choose a future date and time for reservation.';
        header("Location: service-details.php?id=" . $service_id);
        exit;
    }

  
    $insert_stmt = $pdo->prepare("
        INSERT INTO reservations (user_id, service_id, reservation_date, status) 
        VALUES (?, ?, ?, 'Pending')
    ");
    
    if ($insert_stmt->execute([$user_id, $service_id, date('Y-m-d H:i:s', $booking_time)])) {
        $_SESSION['booking_success'] = 'Your reservation request was submitted successfully!';
        header("Location: reservations.php");
        exit;
    } else {
        $_SESSION['booking_error'] = 'Could not reserve the service. Please try again.';
        header("Location: service-details.php?id=" . $service_id);
        exit;
    }
} else {
    header("Location: services.php");
    exit;
}
?>
