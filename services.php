<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Fetch all categories for filter buttons
$categories_stmt = $pdo->query("SELECT * FROM categories");
$categories = $categories_stmt->fetchAll();

// Build dynamic query for services based on search and category filter
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$search_query = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

$sql = "SELECT s.*, c.name as category_name 
        FROM services s 
        LEFT JOIN categories c ON s.category_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND s.category_id = :category_id";
    $params['category_id'] = $category_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (s.title LIKE :search OR s.description LIKE :search)";
    $params['search'] = '%' . $search_query . '%';
}

$services_stmt = $pdo->prepare($sql);
$services_stmt->execute($params);
$services = $services_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - SkillHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 style="margin-bottom: 0.5rem;">Browse Services</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Find local service professionals for any task.</p>

            <!-- Search and Filter Form -->
            <div style="background-color: var(--white); padding: 1.5rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 2.5rem; box-shadow: var(--shadow);">
                <form action="services.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" class="form-input" placeholder="Search services..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search_query) || !empty($category_filter)): ?>
                        <a href="services.php" class="btn btn-outline">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Categories Horizontal Filter List -->
            <div class="services-filter">
                <a href="services.php?search=<?php echo urlencode($search_query); ?>" 
                   class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                    All Services
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="services.php?category=<?php echo $cat['id']; ?>&search=<?php echo urlencode($search_query); ?>" 
                       class="filter-btn <?php echo ($category_filter == $cat['id']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Services Grid -->
            <div class="grid">
                <?php if (empty($services)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 4rem 1.5rem; background-color: var(--white); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                        <span style="font-size: 3rem;">🔍</span>
                        <h3 style="margin-top: 1rem;">No Services Found</h3>
                        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">We couldn't find any services matching your filters. Try search keywords or different category filters.</p>
                        <a href="services.php" class="btn btn-primary">Reset Browse Filters</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
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
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
