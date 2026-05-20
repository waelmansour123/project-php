<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$success = '';
$error = '';

$action = sanitizeInput($_GET['action'] ?? '');
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;


$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();


if ($action === 'delete' && $edit_id > 0) {
    $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
    $stmt->execute([$edit_id]);
    $service = $stmt->fetch();
    
    if ($service) {
       
        if (!empty($service['image']) && file_exists('../uploads/services/' . $service['image'])) {
            unlink('../uploads/services/' . $service['image']);
        }
        
        $delete_stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        if ($delete_stmt->execute([$edit_id])) {
            $_SESSION['admin_services_success'] = "Service deleted successfully.";
        } else {
            $_SESSION['admin_services_error'] = "Failed to delete service.";
        }
    } else {
        $_SESSION['admin_services_error'] = "Service not found.";
    }
    header("Location: services.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0.00);
    $category_id = (int)($_POST['category_id'] ?? 0);
    
  
    $is_edit = isset($_POST['service_id']) && (int)$_POST['service_id'] > 0;
    $service_id = $is_edit ? (int)$_POST['service_id'] : 0;


    if (empty($title) || empty($description) || $price <= 0 || $category_id <= 0) {
        $error = "Please fill in all fields with valid details.";
    } else {
       
        $image_name = '';
        $upload_image = false;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_parts = explode('.', $file['name']);
            $file_ext = strtolower(end($file_parts));
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $error = "Invalid service image format. Allowed formats: JPG, JPEG, PNG, GIF.";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $error = "Service image size must be smaller than 2MB.";
            } else {
                $upload_dir = '../uploads/services/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $image_name = uniqid('service_', true) . '.' . $file_ext;
                $dest_path = $upload_dir . $image_name;
                
                if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                    $upload_image = true;
                    
                    
                    if ($is_edit) {
                        $old_img_stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
                        $old_img_stmt->execute([$service_id]);
                        $old_img = $old_img_stmt->fetchColumn();
                        if ($old_img && file_exists($upload_dir . $old_img)) {
                            unlink($upload_dir . $old_img);
                        }
                    }
                } else {
                    $error = "Failed to upload service image.";
                }
            }
        } elseif (!$is_edit) {
           
            $error = "Please upload an image for the service.";
        }

   
        if (empty($error)) {
            if ($is_edit) {
           
                if ($upload_image) {
                    $update_stmt = $pdo->prepare("
                        UPDATE services 
                        SET title = ?, description = ?, price = ?, category_id = ?, image = ? 
                        WHERE id = ?
                    ");
                    $result = $update_stmt->execute([$title, $description, $price, $category_id, $image_name, $service_id]);
                } else {
                    $update_stmt = $pdo->prepare("
                        UPDATE services 
                        SET title = ?, description = ?, price = ?, category_id = ? 
                        WHERE id = ?
                    ");
                    $result = $update_stmt->execute([$title, $description, $price, $category_id, $service_id]);
                }
                
                if ($result) {
                    $_SESSION['admin_services_success'] = "Service updated successfully.";
                    header("Location: services.php");
                    exit;
                } else {
                    $error = "Failed to update service.";
                }
            } else {
              
                $insert_stmt = $pdo->prepare("
                    INSERT INTO services (title, description, price, category_id, image) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                if ($insert_stmt->execute([$title, $description, $price, $category_id, $image_name])) {
                    $_SESSION['admin_services_success'] = "Service added successfully.";
                    header("Location: services.php");
                    exit;
                } else {
                    $error = "Failed to add service.";
                }
            }
        }
    }
}


if (isset($_SESSION['admin_services_success'])) {
    $success = $_SESSION['admin_services_success'];
    unset($_SESSION['admin_services_success']);
}
if (isset($_SESSION['admin_services_error'])) {
    $error = $_SESSION['admin_services_error'];
    unset($_SESSION['admin_services_error']);
}


$services_stmt = $pdo->query("
    SELECT s.*, c.name as category_name 
    FROM services s 
    LEFT JOIN categories c ON s.category_id = c.id 
    ORDER BY s.title ASC
");
$services = $services_stmt->fetchAll();


$edit_service = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_service = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - SkillHub</title>
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
                    <a href="services.php" class="admin-menu-link active">🛠️ Services</a>
                    <a href="users.php" class="admin-menu-link">👥 Users</a>
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

                  
                    <?php if ($action === 'add' || ($action === 'edit' && $edit_service)): ?>
                        <div class="profile-details" style="margin-bottom: 2.5rem; max-width: 700px;">
                            <h2 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                                <?php echo ($action === 'add') ? 'Add New Service' : 'Edit Service'; ?>
                            </h2>
                            
                            <form action="services.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $edit_service['id'] : ''; ?>" 
                                  method="POST" enctype="multipart/form-data">
                                
                                <?php if ($action === 'edit'): ?>
                                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                                <?php endif; ?>

                                <div class="form-group">
                                    <label for="title" class="form-label">Service Title <span style="color: var(--danger);">*</span></label>
                                    <input type="text" id="title" name="title" class="form-input" required 
                                           value="<?php echo htmlspecialchars($edit_service['title'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="category_id" class="form-label">Category <span style="color: var(--danger);">*</span></label>
                                    <select id="category_id" name="category_id" class="form-input" required>
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                <?php 
                                                if (isset($edit_service['category_id']) && $edit_service['category_id'] == $cat['id']) {
                                                    echo 'selected';
                                                }
                                                ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="price" class="form-label">Price ($) <span style="color: var(--danger);">*</span></label>
                                    <input type="number" id="price" name="price" step="0.01" min="1.00" class="form-input" required 
                                           value="<?php echo htmlspecialchars($edit_service['price'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="description" class="form-label">Description <span style="color: var(--danger);">*</span></label>
                                    <textarea id="description" name="description" rows="4" class="form-input" required><?php echo htmlspecialchars($edit_service['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="image" class="form-label">Service Image <?php echo ($action === 'add') ? '<span style="color: var(--danger);">*</span>' : '(Optional)'; ?></label>
                                    <input type="file" id="image" name="image" class="form-input" accept="image/*" <?php echo ($action === 'add') ? 'required' : ''; ?>>
                                    <small style="color: var(--text-muted); display: block; margin-top: 0.25rem;">Max file size: 2MB. Formats: JPG, PNG, GIF.</small>
                                    
                                    <?php if ($action === 'edit' && !empty($edit_service['image'])): ?>
                                        <div style="margin-top: 1rem;">
                                            <p style="font-size: 0.85rem; margin-bottom: 0.25rem;">Current Image:</p>
                                            <img src="../uploads/services/<?php echo htmlspecialchars($edit_service['image']); ?>" alt="Current Image" style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--radius-sm);">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                                    <button type="submit" class="btn btn-primary"><?php echo ($action === 'add') ? 'Add Service' : 'Save Changes'; ?></button>
                                    <a href="services.php" class="btn btn-outline">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

             
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <div>
                            <h1 style="margin: 0; font-size: 1.75rem;">Service Management</h1>
                            <p style="color: var(--text-muted); margin-top: 0.25rem;">Add, edit, or delete local services catalog.</p>
                        </div>
                        <?php if ($action !== 'add'): ?>
                            <a href="services.php?action=add" class="btn btn-primary">➕ Add Service</a>
                        <?php endif; ?>
                    </div>

              
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Thumbnail</th>
                                    <th>Service</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($services)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No services found. Click "Add Service" to create one.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($services as $srv): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $img = ($srv['image'] && file_exists('../uploads/services/' . $srv['image'])) ? '../uploads/services/' . $srv['image'] : '../assets/images/default-service.jpg';
                                                ?>
                                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Service Image" style="width: 60px; height: 40px; object-fit: cover; border-radius: var(--radius-sm);" onerror="this.src='https://images.unsplash.com/photo-1581578731548-c64695cc6952?auto=format&fit=crop&q=80&w=150'">
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($srv['title']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($srv['category_name'] ?? 'General'); ?></td>
                                            <td><strong style="color: var(--dark);">$<?php echo number_format($srv['price'], 2); ?></strong></td>
                                            <td>
                                                <div style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.875rem;" title="<?php echo htmlspecialchars($srv['description']); ?>">
                                                    <?php echo htmlspecialchars($srv['description']); ?>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                    <a href="services.php?action=edit&id=<?php echo $srv['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                                    <a href="services.php?action=delete&id=<?php echo $srv['id']; ?>" class="btn btn-danger btn-sm" 
                                                       data-confirm="Are you sure you want to permanently delete this service? All linked reservations and reviews will be removed.">Delete</a>
                                                </div>
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
