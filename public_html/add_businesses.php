<?php
session_start();

include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_business'])) {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $status = isset($_POST['status']) ? 1 : 0;
        $icon = $_POST['icon'] ?? 'fa-store';
        $filename = $_POST['filename'] ?? '';
        
        $image_url = 'img/default-business.jpg';
        if (isset($_FILES['image'])) {
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }
        
        $sql = "INSERT INTO business_listings (name, category_id, status, image_url, icon, filename) 
                VALUES ('$name', '$category', '$status', '$image_url', '$icon', '$filename')";
        $conn->query($sql);
    } 
    elseif (isset($_POST['update_business'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $status = isset($_POST['status']) ? 1 : 0;
        $icon = $_POST['icon'] ?? 'fa-store';
        $filename = $_POST['filename'] ?? '';
        
        $image_sql = '';
        if (isset($_FILES['image'])) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["image"]["name"]);
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_sql = ", image_url = '$target_file'";
            }
        }
        
        $sql = "UPDATE business_listings SET 
                name = '$name',  
                category_id = '$category', 
                status = '$status',
                icon = '$icon',
                filename = '$filename'
                $image_sql
                WHERE id = $id";
        $conn->query($sql);
    } 
    elseif (isset($_POST['delete_business'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM business_listings WHERE id = $id";
        $conn->query($sql);
    } 
    elseif (isset($_POST['add_category'])) {
        $name = $_POST['category_name'];
        $icon = $_POST['category_icon'] ?? 'fa-tag';
        $status = isset($_POST['status']) ? 1 : 0;
        
        $sql = "INSERT INTO business_categories (name, icon, status) VALUES ('$name', '$icon', '$status')";
        $conn->query($sql);
    } 
    elseif (isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = $_POST['category_name'];
        $icon = $_POST['category_icon'] ?? 'fa-tag';
        $status = isset($_POST['status']) ? 1 : 0;
        
        $sql = "UPDATE business_categories SET 
                name = '$name', 
                icon = '$icon',
                status = '$status'
                WHERE id = $id";
        $conn->query($sql);
    }
    elseif (isset($_POST['delete_category'])) {
        $id = $_POST['category_id'];
        $sql = "DELETE FROM business_categories WHERE id = $id";
        $conn->query($sql);
    }
}

$businesses = $conn->query("SELECT b.*, c.name as category_name 
                           FROM business_listings b
                           LEFT JOIN business_categories c ON b.category_id = c.id
                           ORDER BY b.name");

$categories = $conn->query("SELECT * FROM business_categories ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #343a40;
            color: white;
        }
        .table {
            margin-top: 20px;
        }
        .business-image {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Admin Panel</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Business</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category" required>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Icon</label>
                                <input type="text" class="form-control" name="icon" value="fa-store">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Filename (optional)</label>
                                <input type="text" class="form-control" name="filename">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image</label>
                                <input type="file" class="form-control" name="image">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="status" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                            <button type="submit" name="add_business" class="btn btn-primary">Add Business</button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" class="form-control" name="category_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Icon</label>
                                <input type="text" class="form-control" name="category_icon" value="fa-tag">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="status" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Business List</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($business = $businesses->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $business['name'] ?></td>
                                        <td><?= $business['category_name'] ?></td>
                                        <td><?= $business['status'] ? 'Active' : 'Inactive' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?= $business['id'] ?>">
                                                Edit
                                            </button>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="id" value="<?= $business['id'] ?>">
                                                <button type="submit" name="delete_business" class="btn btn-sm btn-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?= $business['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <input type="hidden" name="id" value="<?= $business['id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Business</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Business Name</label>
                                                            <input type="text" class="form-control" name="name" value="<?= $business['name'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Category</label>
                                                            <select class="form-select" name="category">
                                                                <?php 
                                                                $cats = $conn->query("SELECT * FROM business_categories ORDER BY name");
                                                                while ($cat = $cats->fetch_assoc()): ?>
                                                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $business['category_id'] ? 'selected' : '' ?>>
                                                                        <?= $cat['name'] ?>
                                                                    </option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Icon</label>
                                                            <input type="text" class="form-control" name="icon" value="<?= $business['icon'] ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Filename (optional)</label>
                                                            <input type="text" class="form-control" name="filename" value="<?= $business['filename'] ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Image</label>
                                                            <input type="file" class="form-control" name="image">
                                                            <?php if ($business['image_url']): ?>
                                                                <img src="<?= $business['image_url'] ?>" class="business-image mt-2">
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" class="form-check-input" name="status" <?= $business['status'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label">Active</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_business" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Category List</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $cats = $conn->query("SELECT * FROM business_categories ORDER BY name");
                                while ($cat = $cats->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $cat['name'] ?></td>
                                        <td><?= $cat['status'] ? 'Active' : 'Inactive' ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editCatModal<?= $cat['id'] ?>">
                                                Edit
                                            </button>
                                            <form method="POST" style="display:inline">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Category Modal -->
                                    <div class="modal fade" id="editCatModal<?= $cat['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Category</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Category Name</label>
                                                            <input type="text" class="form-control" name="category_name" value="<?= $cat['name'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Icon</label>
                                                            <input type="text" class="form-control" name="category_icon" value="<?= $cat['icon'] ?>">
                                                        </div>
                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" class="form-check-input" name="status" <?= $cat['status'] ? 'checked' : '' ?>>
                                                            <label class="form-check-label">Active</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_category" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>