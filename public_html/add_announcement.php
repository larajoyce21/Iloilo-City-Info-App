<?php
include 'conn.php';

function handleFileUpload($file, $target_dir = "uploads/") {
    if (!empty($file['name'])) {
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target_file = $target_dir . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $target_file;
        }
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    if (isset($_POST['add'])) {
        $image_path = handleFileUpload($_FILES['image']);
        $query = "INSERT INTO announcements (title, description, image_path) 
                  VALUES ('$title', '$description', '$image_path')";
        mysqli_query($conn, $query);
    }

    if (isset($_POST['update'])) {
        $id = intval($_POST['id']);
        $image_path = $_POST['existing_image'];
        if (!empty($_FILES['image']['name'])) {
            $image_path = handleFileUpload($_FILES['image']);
        }

        $query = "UPDATE announcements 
                  SET title='$title', description='$description', image_path='$image_path' 
                  WHERE id=$id";
        mysqli_query($conn, $query);
        header("Location: add_announcement.php");
        exit;
    }
}

//  GET 
$edit_announcement = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $query = "DELETE FROM announcements WHERE id=$id";
        mysqli_query($conn, $query);
        header("Location: add_announcement.php");
        exit;
    }

    if (isset($_GET['edit'])) {
        $id = intval($_GET['edit']);
        $result = mysqli_query($conn, "SELECT * FROM announcements WHERE id=$id");
        $edit_announcement = mysqli_fetch_assoc($result);
    }
}

$result = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Announcements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
            margin-top: 0;
        }
        .back-link {
            margin-bottom: 20px;
            display: inline-block;
            color: #0066cc;
            text-decoration: none;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .btn-cancel {
            background-color: #f44336;
        }
        .btn-cancel:hover {
            background-color: #d32f2f;
        }
        .announcement-image {
            max-height: 60px;
            display: block;
            margin-top: 8px;
        }
        .edit-form {
            background-color: #f9f9f9;
            padding: 25px;
            margin-bottom: 30px;
            border-radius: 6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-links a {
            margin-right: 10px;
            color: #0066cc;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="announcements.php" class="back-link">← Back to Public View</a>
        <h1>Manage Announcements</h1>

        <?php if ($edit_announcement): ?>
            <div class="edit-form">
                <h2>Edit Announcement</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_announcement['id']); ?>">
                    <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($edit_announcement['image_path']); ?>">

                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_announcement['title']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" required><?php echo htmlspecialchars($edit_announcement['description']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Current Image:</label>
                        <?php if (!empty($edit_announcement['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($edit_announcement['image_path']); ?>" class="announcement-image">
                        <?php else: ?>
                            <p><i>No image uploaded</i></p>
                        <?php endif; ?>
                        <label>Change Image (optional):</label>
                        <input type="file" name="image">
                    </div>
                    <button type="submit" name="update">Update Announcement</button>
                    <a href="add_announcement.php" class="btn-cancel" style="padding: 10px 20px; text-decoration: none;">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <div>
                <h2>Add New Announcement</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image (optional):</label>
                        <input type="file" name="image">
                    </div>
                    <button type="submit" name="add">Add Announcement</button>
                </form>
            </div>
        <?php endif; ?>

        <h2>Existing Announcements</h2>
        <table>
            <tr>
                <th>Title</th>
                <th>Image</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($announcements as $announcement): ?>
                <tr>
                    <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                    <td>
                        <?php if (!empty($announcement['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" class="announcement-image">
                        <?php else: ?>
                            <span style="color: #aaa;">No image</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></td>
                    <td class="action-links">
                        <a href="?edit=<?php echo $announcement['id']; ?>">Edit</a>
                        <a href="?delete=<?php echo $announcement['id']; ?>" onclick="return confirm('Are you sure you want to delete this announcement?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>

<?php mysqli_close($conn); ?>
