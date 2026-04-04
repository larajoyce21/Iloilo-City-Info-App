<?php
session_start();
include "conn.php";

// Check if admin is logged in
// if (!isset($_SESSION['admin_logged_in']) {
//     header("Location: admin_login.php");
//     exit();
// }

// Function to get all categories and their items
function getCategoryItems($conn) {
    $categories = [];
    
    // Get festivals
    $stmt = $conn->prepare("SELECT id, name FROM festivals");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories['festival'] = [
        'name' => 'Festivals',
        'items' => $result->fetch_all(MYSQLI_ASSOC)
    ];
    $stmt->close();
    
    // Get historical places
    $stmt = $conn->prepare("SELECT id, name FROM historicals");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories['historical'] = [
        'name' => 'Historical Places',
        'items' => $result->fetch_all(MYSQLI_ASSOC)
    ];
    $stmt->close();
    
    // Get food items
    $stmt = $conn->prepare("SELECT id, name FROM foods");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories['food'] = [
        'name' => 'Famous Food',
        'items' => $result->fetch_all(MYSQLI_ASSOC)
    ];
    $stmt->close();
    
    return $categories;
}

// Add new popular item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_popular'])) {
    $category = $_POST['category'];
    $item_id = $_POST['item_id'];
    
    // Check if already exists
    $stmt = $conn->prepare("SELECT id FROM popular WHERE category = ? AND item_id = ?");
    $stmt->bind_param("si", $category, $item_id);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO popular (category, item_id) VALUES (?, ?)");
        $stmt->bind_param("si", $category, $item_id);
        $stmt->execute();
        $success = "Item added to popular list successfully!";
    } else {
        $error = "This item is already in the popular list!";
    }
    $stmt->close();
}

// Remove popular item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM popular WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $success = "Item removed from popular list successfully!";
    $stmt->close();
}

// Get current popular items
$stmt = $conn->prepare("
    SELECT p.id, p.category, p.item_id, 
           COALESCE(f.name, h.name, fd.name) AS name,
           COALESCE(f.image, h.image, fd.image) AS image
    FROM popular p
    LEFT JOIN festivals f ON p.category = 'festival' AND p.item_id = f.id
    LEFT JOIN historicals h ON p.category = 'historical' AND p.item_id = h.id
    LEFT JOIN foods fd ON p.category = 'food' AND p.item_id = fd.id
    ORDER BY p.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
$popular_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all available categories and items
$categories = getCategoryItems($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Manage Popular Items</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 70px;
        }
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #adb5bd;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            color: #fff;
            background-color: #495057;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .card-img-admin {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
        }
        .category-selector {
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-none d-md-block">
        <div class="text-center mb-4">
            <h4 class="text-white">Iloilo City Admin</h4>
        </div>
        <a href="admin_dashboard.php" class=""><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a href="admin_popular.php" class="active"><i class="fas fa-star me-2"></i> Popular Items</a>
        <a href="admin_festivals.php"><i class="fas fa-calendar-alt me-2"></i> Festivals</a>
        <a href="admin_historicals.php"><i class="fas fa-landmark me-2"></i> Historical Places</a>
        <a href="admin_foods.php"><i class="fas fa-utensils me-2"></i> Foods</a>
        <a href="admin_users.php"><i class="fas fa-users me-2"></i> Users</a>
        <a href="admin_logout.php" class="mt-4"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
    </div>

    <!-- Mobile Navbar -->
    <nav class="navbar navbar-dark bg-dark fixed-top d-md-none">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Iloilo City Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Mobile Sidebar -->
    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <a href="admin_dashboard.php" class="d-block py-2"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
            <a href="admin_popular.php" class="d-block py-2 active"><i class="fas fa-star me-2"></i> Popular Items</a>
            <a href="admin_festivals.php" class="d-block py-2"><i class="fas fa-calendar-alt me-2"></i> Festivals</a>
            <a href="admin_historicals.php" class="d-block py-2"><i class="fas fa-landmark me-2"></i> Historical Places</a>
            <a href="admin_foods.php" class="d-block py-2"><i class="fas fa-utensils me-2"></i> Foods</a>
            <a href="admin_users.php" class="d-block py-2"><i class="fas fa-users me-2"></i> Users</a>
            <a href="admin_logout.php" class="d-block py-2 mt-4"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Manage Popular Items</h2>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Popular Item</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $key => $category): ?>
                                        <option value="<?= $key ?>"><?= $category['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="item_id" class="form-label">Item</label>
                                <select class="form-select" id="item_id" name="item_id" required disabled>
                                    <option value="">Select Category First</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_popular" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add to Popular
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Popular Items</h5>
                </div>
                <div class="card-body">
                    <?php if (count($popular_items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($popular_items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?= htmlspecialchars($item['image']) ?>" class="card-img-admin" alt="<?= htmlspecialchars($item['name']) ?>">
                                                <?php else: ?>
                                                    <div class="card-img-admin bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td>
                                                <?php 
                                                    $categoryName = isset($categories[$item['category']]['name']) ? 
                                                        $categories[$item['category']]['name'] : 
                                                        ucfirst($item['category']);
                                                    echo htmlspecialchars($categoryName);
                                                ?>
                                            </td>
                                            <td>
                                                <a href="admin_popular.php?delete=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this item from popular list?')">
                                                    <i class="fas fa-trash-alt"></i> Remove
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No popular items found. Add some using the form above.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dynamic item loading based on category selection
        document.getElementById('category').addEventListener('change', function() {
            const category = this.value;
            const itemSelect = document.getElementById('item_id');
            
            if (category) {
                // Fetch items for the selected category
                fetch(`get_category_items.php?category=${category}`)
                    .then(response => response.json())
                    .then(data => {
                        itemSelect.innerHTML = '';
                        if (data.length > 0) {
                            itemSelect.disabled = false;
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.id;
                                option.textContent = item.name;
                                itemSelect.appendChild(option);
                            });
                        } else {
                            itemSelect.disabled = true;
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'No items available in this category';
                            itemSelect.appendChild(option);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        itemSelect.disabled = true;
                        itemSelect.innerHTML = '<option value="">Error loading items</option>';
                    });
            } else {
                itemSelect.disabled = true;
                itemSelect.innerHTML = '<option value="">Select Category First</option>';
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>