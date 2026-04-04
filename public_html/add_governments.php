<?php
include 'conn.php';

// \ adding new Barangay Captain
if (isset($_POST['add_captain'])) {
    $name = $_POST['name'];
    $barangay = $_POST['barangay'];
    $contact = $_POST['contact'];
    $conn->query("INSERT INTO barangay_captains (name, barangay, contact) VALUES ('$name', '$barangay', '$contact')");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// updating Barangay Captain
if (isset($_POST['update_captain'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $barangay = $_POST['barangay'];
    $contact = $_POST['contact'];
    $conn->query("UPDATE barangay_captains SET name='$name', barangay='$barangay', contact='$contact' WHERE id=$id");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

//  deleting Barangay Captain
if (isset($_POST['delete_captain'])) {
    $id = $_POST['id'];
    $conn->query("DELETE FROM barangay_captains WHERE id=$id");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

//  adding new government official
if (isset($_POST['submit_official'])) {
    $name = $_POST['name'];
    $position = $_POST['position'];
    
    //  image upload
    $target_dir = "uploads/officials/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $imageFileType = strtolower(pathinfo($_FILES["image"]["name"],PATHINFO_EXTENSION));
    $target_file = $target_dir . uniqid() . '.' . $imageFileType;
    
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $conn->query("INSERT INTO officials (name, position, image) VALUES ('$name', '$position', '$target_file')");
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Error uploading image.";
    }
}

//  editing government official
if (isset($_POST['edit_official'])) {
    $id = $_POST['id'];
    $name = $_POST['edit_name'];
    $position = $_POST['edit_position'];
    
    if ($_FILES['edit_image']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/officials/";
        $imageFileType = strtolower(pathinfo($_FILES["edit_image"]["name"],PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid() . '.' . $imageFileType;
        
        if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $target_file)) {
            // Delete old image
            $old_image = $conn->query("SELECT image FROM officials WHERE id=$id")->fetch_assoc()['image'];
            if (file_exists($old_image)) {
                unlink($old_image);
            }
            
            $conn->query("UPDATE officials SET name='$name', position='$position', image='$target_file' WHERE id=$id");
        } else {
            $error = "Error uploading new image.";
        }
    } else {
        //  update name and position
        $conn->query("UPDATE officials SET name='$name', position='$position' WHERE id=$id");
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

//  deleting government official
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Delete image file first
    $image = $conn->query("SELECT image FROM officials WHERE id=$id")->fetch_assoc()['image'];
    if (file_exists($image)) {
        unlink($image);
    }
    $conn->query("DELETE FROM officials WHERE id=$id");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

//  Barangay Captains
$captains = $conn->query("SELECT * FROM barangay_captains");

//  Government Officials
$officials = $conn->query("SELECT * FROM officials ORDER BY FIELD(position, 'CONGRESSWOMAN','GOVERNOR','VICE GOVERNOR','MAYOR','VICE MAYOR','SB MEMBER')");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Government Officials</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .edit-form {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .official-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body class="p-4">
    <br>
    <div class="container">
         <button type="button" class="btn btn-dark text-light" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
     </div>
     <br>
    <h2>🛠️ Admin Panel - Manage Government Officials</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Add Official Form -->
    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="col-md-3">
                <select name="position" class="form-select" required>
                    <option value="">Select Position</option>
                    <option value="CONGRESSWOMAN">Congresswoman</option>
                    <option value="GOVERNOR">Governor</option>
                    <option value="VICE GOVERNOR">Vice Governor</option>
                    <option value="MAYOR">Mayor</option>
                    <option value="VICE MAYOR">Vice Mayor</option>
                    <option value="SB MEMBER">SB Member</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="submit_official" class="btn btn-success w-100">Add Official</button>
            </div>
        </div>
    </form>

    <div class="row">
        <!-- Column 1 Government Officials -->
        <div class="col-md-6">
            <h4>Government Officials</h4>
            <table class="table table-bordered text-center">
                <thead class="table-dark">
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $officials->fetch_assoc()): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($row['image']) ?>" class="official-img"></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['position']) ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this official?')" class="btn btn-danger btn-sm">Delete</a>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Official</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <div class="mb-3">
                                                    <label>Name</label>
                                                    <input type="text" name="edit_name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Position</label>
                                                    <select name="edit_position" class="form-select" required>
                                                        <option value="CONGRESSWOMAN" <?= $row['position'] == 'CONGRESSWOMAN' ? 'selected' : '' ?>>Congresswoman</option>
                                                        <option value="GOVERNOR" <?= $row['position'] == 'GOVERNOR' ? 'selected' : '' ?>>Governor</option>
                                                        <option value="VICE GOVERNOR" <?= $row['position'] == 'VICE GOVERNOR' ? 'selected' : '' ?>>Vice Governor</option>
                                                        <option value="MAYOR" <?= $row['position'] == 'MAYOR' ? 'selected' : '' ?>>Mayor</option>
                                                        <option value="VICE MAYOR" <?= $row['position'] == 'VICE MAYOR' ? 'selected' : '' ?>>Vice Mayor</option>
                                                        <option value="SB MEMBER" <?= $row['position'] == 'SB MEMBER' ? 'selected' : '' ?>>SB Member</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label>Change Image (optional)</label>
                                                    <input type="file" name="edit_image" class="form-control" accept="image/*">
                                                    <small class="text-muted">Current: <?= basename($row['image']) ?></small>
                                                    <div class="mt-2">
                                                        <img src="<?= htmlspecialchars($row['image']) ?>" class="official-img">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" name="edit_official" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Column 2 Barangay Captains -->
        <div class="col-md-6">
            <h4>Barangay Captains</h4>
            
            <!-- Add Barangay Captain Form -->
            <form method="POST" class="mb-4">
                <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
                <input type="text" name="barangay" class="form-control mb-2" placeholder="Barangay Name" required>
                <input type="text" name="contact" class="form-control mb-2" placeholder="Contact Number" required>
                <button type="submit" name="add_captain" class="btn btn-primary">Add Captain</button>
            </form>

            <!-- List of Barangay Captains -->
            <div class="list-group mt-3">
                <?php 
                $captains->data_seek(0);
                while ($row = $captains->fetch_assoc()): 
                ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                            <?= htmlspecialchars($row['barangay']) ?> - <?= htmlspecialchars($row['contact']) ?>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCaptainModal<?= $row['id'] ?>">Edit</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_captain" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Edit Modal for Barangay Captain -->
                    <div class="modal fade" id="editCaptainModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Barangay Captain</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="mb-3">
                                            <label>Name</label>
                                            <input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Barangay</label>
                                            <input type="text" name="barangay" value="<?= htmlspecialchars($row['barangay']) ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label>Contact Number</label>
                                            <input type="text" name="contact" value="<?= htmlspecialchars($row['contact']) ?>" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="update_captain" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>