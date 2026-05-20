<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';


$categories_stmt = $pdo->query("SELECT * FROM categories");
$categories = $categories_stmt->fetchAll();


$services_stmt = $pdo->query("
    SELECT s.*, c.name as category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    LIMIT 4
");
$featured_services = $services_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillHub - Local Services Reservation Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
       
        <section class="hero">
            <div class="container">
                <h1 class="hero-title">Expert Local Services, Reserved Instantly</h1>
                <p class="hero-subtitle">Find trusted cleaners, plumbers, electricians, and trainers in your local neighborhood.</p>
                
                <form action="services.php" method="GET" class="hero-search">
                    <input type="text" name="search" placeholder="What service do you need today? (e.g. cleaning, pipe repair)" aria-label="Search services" required>
                    <button type="submit" class="btn btn-secondary">Search</button>
                </form>
            </div>
        </section>

       
        <section class="container" style="margin-bottom: 4rem;">
            <h2 style="text-align: center; margin-bottom: 2rem;">Explore Categories</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <?php foreach ($categories as $category): ?>
                    <?php
             
                    $emoji = '🛠️';
                    if (stripos($category['name'], 'clean') !== false) $emoji = '🧹';
                    elseif (stripos($category['name'], 'plumb') !== false) $emoji = '🚰';
                    elseif (stripos($category['name'], 'elect') !== false) $emoji = '⚡';
                    elseif (stripos($category['name'], 'fit') !== false) $emoji = '💪';
                    ?>
                    <a href="services.php?category=<?php echo $category['id']; ?>" class="service-card" style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;">
                        <span style="font-size: 2.5rem;"><?php echo $emoji; ?></span>
                        <h3 style="font-size: 1.15rem; margin: 0; color: var(--dark);"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;"><?php echo htmlspecialchars($category['description']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

   
        <section class="container services-section">
            <div class="section-header">
                <div>
                    <h2>Featured Services</h2>
                    <p style="color: var(--text-muted);">Verified professionals ready to help you.</p>
                </div>
                <a href="services.php" class="btn btn-outline">View All Services</a>
            </div>

            <div class="grid">
                <?php if (empty($featured_services)): ?>
                    <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 3rem 0;">No services available at the moment. Please check back later.</p>
                <?php else: ?>
                    <?php foreach ($featured_services as $service): ?>
                        <div class="service-card">
                            <div class="service-img-container">
                                <?php
                                $img_src = ($service['image'] && file_exists('uploads/services/' . $service['image'])) ? 'uploads/services/' . $service['image'] : 'assets/images/default-service.jpg';
                                ?>
                                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>" class="service-img" onerror="this.src='https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=600'">
                                <span class="service-category"><?php echo htmlspecialchars($service['category_name'] ?? 'General'); ?></span>
                            </div>
                            <div class="service-body">
                                <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="service-desc"><?php echo htmlspecialchars($service['description']); ?></p>
                                <div class="service-footer">
                                    <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                    <a href="service-details.php?id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
