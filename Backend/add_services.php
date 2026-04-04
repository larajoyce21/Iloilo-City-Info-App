<?php
session_start();

$db = new mysqli('localhost', 'iloincgi_iloilocityinfoapp', 'iloilocityinfoapp', 'iloincgi_app');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

date_default_timezone_set('Asia/Manila');

// File upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['document_file'])) {
    $service_id = (int)$_POST['service_id'];
    $upload_dir = 'uploads/documents/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['application/pdf'];
    $file_type = $_FILES['document_file']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['error'] = "Only PDF files are allowed.";
        header("Location: add_services.php?edit=".$service_id);
        exit;
    }
    
    $file_name = basename($_FILES['document_file']['name']);
    $file_path = $upload_dir . uniqid() . '_' . $file_name;
    
    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
        $stmt = $db->prepare("INSERT INTO service_documents (service_id, document_name, file_path) VALUES (?, ?, ?)");
        $document_name = pathinfo($file_name, PATHINFO_FILENAME);
        $stmt->bind_param("iss", $service_id, $document_name, $file_path);
        if (!$stmt->execute()) {
            $_SESSION['error'] = "Failed to save document to database.";
        }
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }
    
    header("Location: add_services.php?edit=".$service_id);
    exit;
}

// Delete document
if (isset($_GET['delete_document'])) {
    $doc_id = (int)$_GET['delete_document'];
    $service_id = (int)$_GET['service_id'];
    
    $stmt = $db->prepare("SELECT file_path FROM service_documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
        $stmt = $db->prepare("DELETE FROM service_documents WHERE id = ?");
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
    }
    
    header("Location: add_services.php?edit=".$service_id);
    exit;
}

// Delete service
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $result = $db->query("SELECT file_path FROM service_documents WHERE service_id = $id");
    while ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }
    $db->query("DELETE FROM service_documents WHERE service_id = $id");
    $db->query("DELETE FROM services WHERE id = $id");
    
    $_SESSION['message'] = "Service deleted successfully.";
    header("Location: add_services.php");
    exit;
}

// Save service
if (isset($_POST['save_service'])) {
    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'services' => $_POST['services_offered'],
        'location' => $_POST['location'],
        'contact' => $_POST['contact'],
        'hours' => $_POST['hours'],
        'requirements' => $_POST['requirements']
    ];
    
    if (!empty($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
        $stmt = $db->prepare("UPDATE services SET 
            title=?, description=?, services_offered=?, location=?, 
            contact=?, hours=?, requirements=? WHERE id=?");
        $stmt->bind_param("sssssssi", 
            $data['title'], $data['description'], $data['services'], 
            $data['location'], $data['contact'], $data['hours'], 
            $data['requirements'], $id);
    } else {
        $stmt = $db->prepare("INSERT INTO services 
            (title, description, services_offered, location, contact, hours, requirements) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", 
            $data['title'], $data['description'], $data['services'], 
            $data['location'], $data['contact'], $data['hours'], 
            $data['requirements']);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Service saved successfully.";
    } else {
        $_SESSION['error'] = "Error saving service: " . $db->error;
    }
    
    header("Location: add_services.php");
    exit;
}

// Edit data
$edit_data = [];
$documents = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    
    $stmt = $db->prepare("SELECT * FROM service_documents WHERE service_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all services (no categories)
$all_services = $db->query("SELECT * FROM services ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - Iloilo City Services</title>
<style>
    body {font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;}
    .container {max-width: 1200px; margin: auto;}
    .header {background: #0056b3; color: white; padding: 20px; border-radius: 8px; display: flex; justify-content: space-between;}
    .form-container, .service-list {background: white; padding: 20px; border-radius: 8px; margin-top: 20px;}
    input, textarea {width: 100%; padding: 10px; margin-bottom: 10px;}
    button, .btn {padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;}
    .btn-danger {background: #dc3545;}
    .btn-secondary {background: #6c757d;}
    .service-card {padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between;}
</style>
</head>
<body>
    
<div class="container">
    <div class="container mt-3">
        <button type="button" class="btn btn-light text-dark" onclick="window.location.href='dashboard.php'">Back to Dashboard</button>
    </div>
    <br>
    <div class="header">
        <h1>Iloilo City Services Admin</h1>
        <div>
            <a href="add_how_to.php" class="btn">Add How To Guides</a>
            <a href="services.php" class="btn">View Public Site</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
        <div style="background:#d4edda;padding:10px;margin-top:10px;"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div style="background:#f8d7da;padding:10px;margin-top:10px;"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="form-container" id="form">
        <h2><?= !empty($edit_data) ? 'Edit Service' : 'Add New Service' ?></h2>
        <form method="post">
            <input type="hidden" name="edit_id" value="<?= $edit_data['id'] ?? '' ?>">
            <label>Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($edit_data['title'] ?? '') ?>">
            <label>Description</label>
            <textarea name="description"><?= htmlspecialchars($edit_data['description'] ?? '') ?></textarea>
            <label>Services Offered</label>
            <textarea name="services_offered"><?= htmlspecialchars($edit_data['services_offered'] ?? '') ?></textarea>
            <label>Requirements</label>
            <textarea name="requirements"><?= htmlspecialchars($edit_data['requirements'] ?? '') ?></textarea>
            <label>Location</label>
            <input type="text" name="location" value="<?= htmlspecialchars($edit_data['location'] ?? '') ?>">
            <label>Contact</label>
            <textarea name="contact"><?= htmlspecialchars($edit_data['contact'] ?? '') ?></textarea>
            <label>Hours</label>
            <input type="text" name="hours" value="<?= htmlspecialchars($edit_data['hours'] ?? '') ?>">
            <button type="submit" name="save_service">Save</button>
            <?php if (!empty($edit_data)): ?>
                <a href="add_services.php" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($edit_data)): ?>
            <h3>Documents (<?= count($documents) ?>/10)</h3>
            <?php foreach ($documents as $doc): ?>
                <div class="service-card">
                    <span><?= htmlspecialchars($doc['document_name']) ?></span>
                    <div>
                        <a href="download.php?id=<?= $doc['id'] ?>" class="btn">Download</a>
                        <a href="?delete_document=<?= $doc['id'] ?>&service_id=<?= $edit_data['id'] ?>" class="btn btn-danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($documents) < 10): ?>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="service_id" value="<?= $edit_data['id'] ?>">
                    <input type="file" name="document_file" accept=".pdf" required>
                    <button type="submit">Upload Document</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="service-list">
        <h2>All Services</h2>
        <?php foreach ($all_services as $service): ?>
            <div class="service-card">
                <strong><?= htmlspecialchars($service['title']) ?></strong>
                <div>
                    <a href="?edit=<?= $service['id'] ?>#form" class="btn">Edit</a>
                    <a href="?delete=<?= $service['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this service?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
