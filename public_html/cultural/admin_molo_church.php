<?php
// Database connection
$db = new mysqli('localhost', 'root', '', 'app');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $history = $_POST['history'];
    $location = $_POST['location'];
    $maps_link = $_POST['maps_link'];
    $id = $_POST['id'] ?? null;

    // File upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $image = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], 'uploads/' . $image);
    }

    if ($id) {
        // Update
        $sql = "UPDATE molo_church SET 
                name='$name', description='$description', history='$history',
                location='$location', maps_link='$maps_link'";

        if ($image) $sql .= ", image='$image'";
        $sql .= " WHERE id=$id";
        $db->query($sql);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO molo_church (name, description, history, location, maps_link, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $description, $history, $location, $maps_link, $image);
        $stmt->execute();
    }

    header("Location: admin_molo_church.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $db->query("DELETE FROM molo_church WHERE id=$id");
    header("Location: admin_molo_church.php");
    exit;
}

// Fetch data
$result = $db->query("SELECT * FROM molo_church ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Molo Church Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h2 class="mb-4">Manage Molo Church Details</h2>

    <!-- Add/Edit Form -->
    <form method="post" enctype="multipart/form-data" class="card p-4 mb-4">
        <input type="hidden" name="id" value="">
        <div class="mb-3">
            <label class="form-label">Name:</label>
            <input type="text" name="name" class="form-control" placeholder="Molo Church" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description:</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">History:</label>
            <textarea name="history" class="form-control" rows="4"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Location:</label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Google Maps Link:</label>
            <input type="text" name="maps_link" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Image:</label>
            <input type="file" name="image" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save Details</button>
    </form>

    <!-- Display Records -->
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>History</th>
                <th>Image</th>
                <th>Location</th>
                <th>Maps</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars(substr($row['description'], 0, 50)) ?>...</td>
                <td><?= htmlspecialchars(substr($row['history'], 0, 50)) ?>...</td>
                <td><img src="uploads/<?= $row['image'] ?>" width="80"></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><a href="<?= $row['maps_link'] ?>" target="_blank">View Map</a></td>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this record?')">Delete</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
