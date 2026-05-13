<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$service_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($service_id <= 0) {
    header("Location: services.php");
    exit;
}

// Fetch service details
$stmt = $pdo->prepare("
    SELECT s.*, c.name as category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) {
    die("Service not found.");
}

$error = '';
$success = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    requireLogin();
    
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = sanitizeInput($_POST['comment'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } elseif (empty($comment)) {
        $error = 'Please write a comment for your review.';
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO reviews (user_id, service_id, rating, comment) VALUES (?, ?, ?, ?)");
        if ($insert_stmt->execute([$user_id, $service_id, $rating, $comment])) {
            $success = 'Thank you! Your review has been submitted.';
        } else {
            $error = 'Failed to submit review. Please try again.';
        }
    }
}

// Fetch all reviews for this service
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name, u.profile_image as user_image 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.service_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$service_id]);
$reviews = $reviews_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($service['title']); ?> - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
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

            <div class="details-layout">
                <!-- Main Service Information -->
                <div class="details-main">
                    <div class="details-banner">
                        <?php
                        $img_src = ($service['image'] && file_exists('uploads/services/' . $service['image'])) ? 'uploads/services/' . $service['image'] : 'assets/images/default-service.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" onerror="this.src='https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=1200'">
                    </div>

                    <h1 class="details-title"><?php echo htmlspecialchars($service['title']); ?></h1>
                    
                    <div class="details-meta">
                        <div class="meta-item">
                            <span>📂</span> Category: <strong><?php echo htmlspecialchars($service['category_name'] ?? 'General'); ?></strong>
                        </div>
                        <div class="meta-item">
                            <span>⭐</span> Average Rating: 
                            <strong>
                                <?php
                                if (count($reviews) > 0) {
                                    $total_rating = array_sum(array_column($reviews, 'rating'));
                                    echo number_format($total_rating / count($reviews), 1) . ' / 5.0';
                                } else {
                                    echo 'No reviews yet';
                                }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <div class="details-desc">
                        <h3>About this Service</h3>
                        <p><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
                    </div>

                    <!-- Reviews Section -->
                    <div class="reviews-section">
                        <h2>Customer Reviews</h2>
                        
                        <?php if (isLoggedIn()): ?>
                            <!-- Write a review form -->
                            <form action="service-details.php?id=<?php echo $service_id; ?>" method="POST" class="review-form">
                                <h4 style="margin-bottom: 0.5rem;">Write a Review</h4>
                                
                                <div class="form-group">
                                    <label class="form-label">Rating</label>
                                    <div class="stars-rating">
                                        <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars">★</label>
                                        <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">★</label>
                                        <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">★</label>
                                        <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">★</label>
                                        <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">★</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment" class="form-label">Comment</label>
                                    <textarea id="comment" name="comment" rows="3" class="form-input" placeholder="Share your experience with this service..." required></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="btn btn-secondary btn-sm">Submit Review</button>
                            </form>
                        <?php else: ?>
                            <p style="background-color: var(--light); padding: 1rem; border-radius: var(--radius-md); font-size: 0.9rem; margin-bottom: 1.5rem;">
                                Please <a href="login.php">login</a> to write a review.
                            </p>
                        <?php endif; ?>

                        <!-- Reviews List -->
                        <div class="reviews-list">
                            <?php if (empty($reviews)): ?>
                                <p style="color: var(--text-muted); font-style: italic;">No reviews have been written for this service yet.</p>
                            <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                    <div class="review-card">
                                        <div class="review-header">
                                            <div class="review-author">
                                                <?php
                                                $author_img = ($review['user_image'] && file_exists('uploads/users/' . $review['review_image'])) ? 'uploads/users/' . $review['review_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; // Fallback online
                                                // Note: we fetch user_image which contains users.profile_image from join
                                                $author_img = ($review['user_image'] && file_exists('uploads/users/' . $review['user_image'])) ? 'uploads/users/' . $review['user_image'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
                                                ?>
                                                <img src="<?php echo htmlspecialchars($author_img); ?>" alt="User profile" class="review-author-img" onerror="this.src='https://cdn-icons-png.flaticon.com/512/149/149071.png'">
                                                <span><?php echo htmlspecialchars($review['user_name']); ?></span>
                                            </div>
                                            <div class="review-stars">
                                                <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                            </div>
                                        </div>
                                        <p class="review-body" style="font-size: 0.95rem; color: var(--text-main);">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </p>
                                        <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">
                                            Posted on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Booking Panel -->
                <div class="details-sidebar">
                    <div class="sidebar-card">
                        <div class="price-box">
                            <span class="amount">$<?php echo number_format($service['price'], 2); ?></span>
                            <span class="label">Flat rate booking price</span>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <form action="reserve.php" method="POST">
                                <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="reservation_date" class="form-label">Reservation Date & Time</label>
                                    <input type="datetime-local" id="reservation_date" name="reservation_date" class="form-input" required min="<?php echo date('Y-m-d\TH:i'); ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                                    Confirm Reservation
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="text-align: center;">
                                <p style="margin-bottom: 1.5rem; color: var(--text-muted); font-size: 0.95rem;">
                                    You must be logged in to book a reservation.
                                </p>
                                <a href="login.php" class="btn btn-primary" style="width: 100%;">
                                    Log In to Book
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
