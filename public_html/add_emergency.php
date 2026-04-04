<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'conn.php';

// Check if connection is successful
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = '';
$messageType = '';

// Delete operation
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    $delete_sql = "DELETE FROM emergency_contacts WHERE id = $id";
    if ($conn->query($delete_sql)) {
        $message = "Record deleted successfully!";
        $messageType = "danger";
    } else {
        $message = "Error deleting record: " . $conn->error;
        $messageType = "danger";
    }
}

// Add/Edit operation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $subcategory = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $number1 = mysqli_real_escape_string($conn, $_POST['number1']);
    $number2 = mysqli_real_escape_string($conn, $_POST['number2']);
    $display_order = mysqli_real_escape_string($conn, $_POST['display_order']);

    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $sql = "UPDATE emergency_contacts SET 
                category='$category', 
                subcategory='$subcategory', 
                name='$name', 
                number1='$number1', 
                number2='$number2', 
                display_order='$display_order' 
                WHERE id=$id";
        $action_message = "Record updated successfully!";
    } else {
        // Insert
        $sql = "INSERT INTO emergency_contacts (category, subcategory, name, number1, number2, display_order) 
                VALUES ('$category', '$subcategory', '$name', '$number1', '$number2', '$display_order')";
        $action_message = "Record added successfully!";
    }
    
    if ($conn->query($sql)) {
        $message = $action_message;
        $messageType = "success";
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}

// Fetch all records
$result = $conn->query("SELECT * FROM emergency_contacts ORDER BY category, display_order");
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Fetch record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $id = mysqli_real_escape_string($conn, $_GET['edit']);
    $editResult = $conn->query("SELECT * FROM emergency_contacts WHERE id = $id");
    if ($editResult) {
        $editRecord = $editResult->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Emergency Contacts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .card {
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border: none;
            border-radius: 10px;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .btn-action {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <h1><i class="fas fa-ambulance me-2"></i>Manage Emergency Contacts</h1>
            <p class="lead">Add, Edit, or Delete emergency hotlines</p>
            <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </button>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-<?php echo $editRecord ? 'edit' : 'plus'; ?> me-2"></i>
                            <?php echo $editRecord ? 'Edit Contact' : 'Add New Contact'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php if ($editRecord): ?>
                                <input type="hidden" name="id" value="<?php echo $editRecord['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <option value="Emergency" <?php echo ($editRecord && $editRecord['category'] == 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                                    <option value="Tourist" <?php echo ($editRecord && $editRecord['category'] == 'Tourist') ? 'selected' : ''; ?>>Tourist</option>
                                    <option value="Hospitals" <?php echo ($editRecord && $editRecord['category'] == 'Hospitals') ? 'selected' : ''; ?>>Hospitals</option>
                                    <option value="Police Stations" <?php echo ($editRecord && $editRecord['category'] == 'Police Stations') ? 'selected' : ''; ?>>Police Stations</option>
                                    <option value="Fire Stations" <?php echo ($editRecord && $editRecord['category'] == 'Fire Stations') ? 'selected' : ''; ?>>Fire Stations</option>
                                    <option value="Airlines" <?php echo ($editRecord && $editRecord['category'] == 'Airlines') ? 'selected' : ''; ?>>Airlines</option>
                                    <option value="Shipping lines" <?php echo ($editRecord && $editRecord['category'] == 'Shipping lines') ? 'selected' : ''; ?>>Shipping lines</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subcategory (Optional)</label>
                                <input type="text" name="subcategory" class="form-control" 
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord['subcategory']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contact Name/Office</label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord['name']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Primary Number</label>
                                <input type="text" name="number1" class="form-control"
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord['number1']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Secondary Number (Optional)</label>
                                <input type="text" name="number2" class="form-control"
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord['number2']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Display Order</label>
                                <input type="number" name="display_order" class="form-control" 
                                       value="<?php echo $editRecord ? htmlspecialchars($editRecord['display_order']) : '0'; ?>">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>
                                <?php echo $editRecord ? 'Update Contact' : 'Save Contact'; ?>
                            </button>
                            
                            <?php if ($editRecord): ?>
                                <a href="add_emergency.php" class="btn btn-secondary w-100 mt-2">
                                    <i class="fas fa-times me-2"></i>Cancel Edit
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="table-responsive">
                    <h4 class="mb-3">Current Emergency Contacts</h4>
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Name/Office</th>
                                <th>Number 1</th>
                                <th>Number 2</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo htmlspecialchars($row['subcategory']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['number1']); ?></td>
                                <td><?php echo htmlspecialchars($row['number2']); ?></td>
                                <td><?php echo $row['display_order']; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning btn-action">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger btn-action" 
                                       onclick="return confirm('Are you sure you want to delete this record?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo "<tr><td colspan='8' class='text-center'>No records found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>