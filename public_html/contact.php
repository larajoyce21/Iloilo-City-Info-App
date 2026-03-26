<?php
session_start();
include "conn.php";

// Initialize variables
$errors = [];
$success = false;
$contact_success = false;
$editing_post = false;
$edit_post_id = null;
$edit_post_data = null;

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : ''; 
$isAdmin = $isLoggedIn && $userRole === 'admin';

// Create tables if they don't exist
$createPostsTable = "CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    image_path VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createPostsTable);

$createContactTable = "CREATE TABLE IF NOT EXISTS contact_us (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(20),
    type VARCHAR(50),
    subject VARCHAR(255),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createContactTable);

$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(150),
    password VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createUsersTable);

// Handle Contact Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $type = $_POST['type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Simple validation
    if (empty($firstName)) {
        $errors['first_name'] = "First name is required";
    }
    if (empty($lastName)) {
        $errors['last_name'] = "Last name is required";
    }
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO contact_us (first_name, last_name, email, phone, type, subject, message) 
                 VALUES ('$firstName', '$lastName', '$email', '$phone', '$type', '$subject', '$message')";
        
        if ($conn->query($query)) {
            $contact_success = true;
            $_POST = array(); // Clear form
        } else {
            $errors[] = "Failed to send message. Please try again.";
        }
    }
}

// Handle Create Post Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_post'])) {
    if (!$isLoggedIn) {
        $errors[] = "Please login to create a post.";
    } else {
        $description = trim($_POST['description']);
        
        if (empty($description)) {
            $errors[] = "Description is required.";
        }
        
        $imagePath = '';
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['post_image']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . $_FILES['post_image']['name'];
                $imagePath = $uploadDir . $fileName;
                
                if (!move_uploaded_file($_FILES['post_image']['tmp_name'], $imagePath)) {
                    $errors[] = "Failed to upload image.";
                }
            } else {
                $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        if (empty($errors)) {
            $userId = $_SESSION['user_id'];
            $query = "INSERT INTO posts (user_id, image_path, description) 
                     VALUES ('$userId', '$imagePath', '$description')";
            
            if ($conn->query($query)) {
                $success = "Post created successfully!";
                $_POST['description'] = '';
            } else {
                $errors[] = "Failed to create post. Please try again.";
            }
        }
    }
}

// Handle Delete Post
if (isset($_GET['delete_post'])) {
    if (!$isLoggedIn) {
        $errors[] = "Please login to delete a post.";
    } else {
        $post_id = $_GET['delete_post'];
        $user_id = $_SESSION['user_id'];
        
        // Check if user owns the post or is admin
        if ($isAdmin) {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id'";
        } else {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id' AND user_id = '$user_id'";
        }
        
        $result = $conn->query($check_query);
        
        if ($result->num_rows > 0) {
            $post = $result->fetch_assoc();
            
            // Delete image file if exists
            if (!empty($post['image_path']) && file_exists($post['image_path'])) {
                unlink($post['image_path']);
            }
            
            // Delete post from database
            $delete_query = "DELETE FROM posts WHERE id = '$post_id'";
            if ($conn->query($delete_query)) {
                $success = "Post deleted successfully.";
            } else {
                $errors[] = "Failed to delete post.";
            }
        } else {
            $errors[] = "Post not found or you don't have permission to delete it.";
        }
    }
}

// Handle Edit Post Request
if (isset($_GET['edit_post'])) {
    if (!$isLoggedIn) {
        $errors[] = "Please login to edit a post.";
    } else {
        $post_id = $_GET['edit_post'];
        $user_id = $_SESSION['user_id'];
        
        if ($isAdmin) {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id'";
        } else {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id' AND user_id = '$user_id'";
        }
        
        $result = $conn->query($check_query);
        
        if ($result->num_rows > 0) {
            $editing_post = true;
            $edit_post_id = $post_id;
            $edit_post_data = $result->fetch_assoc();
        } else {
            $errors[] = "Post not found or you don't have permission to edit it.";
        }
    }
}

// Handle Update Post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_post'])) {
    if (!$isLoggedIn) {
        $errors[] = "Please login to update a post.";
    } else {
        $post_id = $_POST['post_id'];
        $description = trim($_POST['description']);
        $user_id = $_SESSION['user_id'];
        
        if ($isAdmin) {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id'";
        } else {
            $check_query = "SELECT * FROM posts WHERE id = '$post_id' AND user_id = '$user_id'";
        }
        
        $result = $conn->query($check_query);
        
        if ($result->num_rows > 0) {
            $old_post = $result->fetch_assoc();
            
            if (empty($description)) {
                $errors[] = "Description is required.";
            }
            
            $imagePath = $old_post['image_path'];
            if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = $_FILES['post_image']['type'];
                
                if (in_array($fileType, $allowedTypes)) {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = time() . '_' . $_FILES['post_image']['name'];
                    $imagePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['post_image']['tmp_name'], $imagePath)) {
                        // Delete old image if exists
                        if (!empty($old_post['image_path']) && file_exists($old_post['image_path'])) {
                            unlink($old_post['image_path']);
                        }
                    } else {
                        $errors[] = "Failed to upload image.";
                    }
                } else {
                    $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
                }
            }
            
            if (empty($errors)) {
                $update_query = "UPDATE posts SET description = '$description', image_path = '$imagePath' WHERE id = '$post_id'";
                
                if ($conn->query($update_query)) {
                    $success = "Post updated successfully!";
                    $editing_post = false;
                } else {
                    $errors[] = "Failed to update post. Please try again.";
                }
            }
        } else {
            $errors[] = "Post not found or you don't have permission to edit it.";
        }
    }
}

// Fetch all posts
$posts = [];
$postsQuery = "SELECT p.*, u.first_name, u.last_name 
               FROM posts p 
               JOIN users u ON p.user_id = u.id 
               ORDER BY p.created_at DESC 
               LIMIT 50";
$postsResult = $conn->query($postsQuery);

if ($postsResult && $postsResult->num_rows > 0) {
    while ($row = $postsResult->fetch_assoc()) {
        $posts[] = $row;
    }
}

$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Community Posts | Iloilo City Info App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --primary-color: #e30613; 
        --secondary-color: white; 
        --accent-color: #f8b400; 
        --dark-color: #212529;
        --light-color: #f8f9fa;
        --gray-light: #e9ecef;
        --transition: all 0.3s ease;
        --info-color: #3498db;
        --success-color: #27ae60;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f7fa;
        color: #333;
        line-height: 1.6;
        padding-top: 70px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    h1, h2, h3, h4, h5, h6 {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
    }
    
    .navbar {
        display: flex;
        width: 100%;
        padding: 8px 15px;
        transition: var(--transition);
        background-color: rgba(255, 255, 255, 0.95);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        height: 90px;
    }

    .navbar.scrolled {
        background-color: rgba(255, 255, 255, 0.95);
        padding: 5px 15px;
        height: 50px;
    }

    .logo {
        width: 200px;
        height: 60px;
        transition: var(--transition);
    }

    .navbar a {
        padding: 8px 12px;
        color: black;
        text-decoration: none;
        font-size: 13px;
        transition: var(--transition);
        position: relative;
        font-weight: bold;
    }

    .navbar a:hover,
    .navbar a:focus {
        color: #ccc;
        outline: none;
    }

    .navbar a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 8px;
        left: 15px;
        background-color: #ccc;
        transition: var(--transition);
    }

    .navbar a:hover::after,
    .navbar a:focus::after {
        width: calc(100% - 30px);
    }

    .sidebar a {
        padding: 12px 20px;
        font-size: 15px;
    }
    
    .navbar-toggler {
        background: transparent !important;
        box-shadow: none !important;
        border: none;
        padding: 4px 8px;
    }
    
    .navbar-toggler:focus {
        box-shadow: none !important;
        background: transparent !important;
    }
    
    .navbar-nav {
        align-items: center;
    }
    
    .navbar-toggler-icon {
        background-image: none;
        position: relative;
        width: 20px;
        height: 20px;
        transition: all 0.3s ease;
        background-color: #508acbff;
    }

    .navbar-toggler-icon::before,
    .navbar-toggler-icon::after,
    .navbar-toggler-icon span {
        content: '';
        position: absolute;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: white;
        transition: all 0.3s ease;
    }

    .navbar-toggler-icon::before {
        top: 5px;
    }

    .navbar-toggler-icon::after {
        bottom: 5px;
    }

    .navbar-toggler-icon span {
        top: 50%;
        transform: translateY(-50%);
    }

    /* Sidebar */
    .sidebar {
        height: 100vh;
        width: 0;
        position: fixed;
        top: 0;
        right: 0;
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        overflow-x: hidden;
        transition: var(--transition);
        padding-top: 50px;
        display: flex;
        flex-direction: column;
        z-index: 1050;
        box-shadow: -4px 0 12px rgba(0, 0, 0, 0.2);
    }
    
    .sidebar.open {
        width: 280px;
    }

    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: var(--transition);
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .sidebar a {
        padding: 12px 20px;
        text-decoration: none;
        font-size: 16px;
        color: black;
        display: flex;
        align-items: center;
        transition: var(--transition);
        font-family: 'Poppins', sans-serif;
        border-left: 3px solid transparent;
        font-weight: bold;
    }

    .sidebar a i {
        margin-right: 10px;
        font-size: 18px;
        width: 20px;
        text-align: center;
    }

    .sidebar a:hover,
    .sidebar a:focus {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 3px solid var(--primary-color);
        padding-left: 25px;
        outline: none;
    }

    .sidebar a.active {
        background-color: rgba(255, 255, 255, 0.05);
        border-left: 3px solid var(--primary-color);
        font-weight: 600;
    }
    
    .close-btn {
        position: absolute;
        top: 12px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
        color: white;
        background: rgba(255, 255, 255, 0.1);
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        border: none;
    }

    .close-btn:hover,
    .close-btn:focus {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
        outline: none;
    }
    
    /* Main Content */
    .main-content {
        max-width: 100%;
        margin: 0 auto;
        padding: 20px 15px;
        width: 100%;
        flex: 1;
    }
    
    @media (min-width: 768px) {
        .main-content {
            max-width: 95%;
            padding: 30px 20px;
        }
    }
    
    @media (min-width: 1200px) {
        .main-content {
            max-width: 1140px;
        }
    }
    
    /* Welcome Section */
    .welcome-section {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(240, 248, 255, 0.95));
        border-radius: 15px;
        padding: 25px 20px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(52, 152, 219, 0.1);
        text-align: center;
    }
    
    /* Action Buttons Container */
    .action-buttons-container {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .action-button-group {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
    }
    
    .action-button-group.left {
        order: 1;
    }
    
    .action-button-group.right {
        order: 2;
    }
    
    /* Action Buttons */
    .main-action-btn {
        background: linear-gradient(135deg, var(--info-color), #2980b9);
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(52, 152, 219, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        width: 100%;
        min-height: 45px;
        white-space: nowrap;
    }
    
    .main-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }
    
    .secondary-action-btn {
        background: linear-gradient(135deg, var(--success-color), #219653);
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(39, 174, 96, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        width: 100%;
        min-height: 45px;
        text-decoration: none;
        white-space: nowrap;
    }
    
    .secondary-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
    }
    
    .action-btn-text {
        font-size: 0.8rem;
        color: var(--secondary-color);
        margin-top: 5px;
        text-align: center;
        line-height: 1.3;
    }
    
    /* Mobile Styles */
    @media (max-width: 768px) {
        .action-buttons-container {
            flex-direction: row;
            gap: 12px;
        }
        
        .action-button-group {
            width: 48%;
            min-width: unset;
        }
        
        .main-action-btn {
            padding: 8px 12px;
            font-size: 0.75rem;
            min-height: 38px;
            gap: 6px;
            border-radius: 8px;
        }
        
        .secondary-action-btn {
            padding: 8px 12px;
            font-size: 0.75rem;
            min-height: 38px;
            gap: 6px;
            border-radius: 8px;
        }
        
        .action-btn-text {
            font-size: 0.7rem;
        }
    }
    
    /* Posts Section */
    .posts-section {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 30px;
        border: 1px solid #eaeaea;
    }
    
    .section-header {
        background: linear-gradient(#545454);
        color: white;
        padding: 25px 20px;
        text-align: center;
    }
    
    .section-body {
        padding: 20px;
    }
    
    /* Create Post Form */
    .create-post-form {
        display: none;
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 5px solid var(--success-color);
    }
    
    .create-post-form.show {
        display: block;
    }
    
    /* Post Cards */
    .post-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s;
        border: 1px solid #eaeaea;
    }
    
    .post-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    
    .post-header-card {
        padding: 15px;
        border-bottom: 2px solid #f1f5f9;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        background: #f8fafc;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        flex: 1;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--info-color), #2980b9);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        margin-right: 12px;
    }
    
    .post-content {
        padding: 20px;
    }
    
    .post-image {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: transform 0.3s;
    }
    
    .post-image:hover {
        transform: scale(1.02);
    }
    
    /* Contact Modal */
    .contact-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.7);
        z-index: 2000;
        padding: 10px;
        overflow-y: auto;
    }
    
    .contact-modal-content {
        position: relative;
        background-color: white;
        margin: 50px auto;
        padding: 30px;
        width: 100%;
        max-width: 600px;
        border-radius: 15px;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .contact-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .contact-modal-header h2 {
        margin: 0;
        color: black;
        font-size: 1.8rem;
    }
    
    .contact-modal-header p {
        margin: 10px 0 0;
        color: #666;
        font-size: 1rem;
    }
    
    .close-contact-modal {
        background: none;
        border: none;
        font-size: 32px;
        cursor: pointer;
        color: #999;
        transition: color 0.3s;
        padding: 0;
        line-height: 1;
        margin-top: -5px;
    }
    
    .close-contact-modal:hover {
        color: black;
    }
    
    /* Form Styling */
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: black;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .form-label.required:after {
        content: " *";
        color: var(--accent-color);
    }
    
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 12px 15px;
        transition: all 0.3s;
        font-size: 1rem;
    }
    
    .form-control:focus {
        border-color: var(--info-color);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        outline: none;
    }
    
    .form-control.is-invalid {
        border-color: var(--accent-color);
    }
    
    .form-control.is-invalid:focus {
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }
    
    .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #ddd;
        font-size: 1rem;
    }
    
    .form-select:focus {
        border-color: var(--info-color);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    
    .invalid-feedback {
        color: var(--accent-color);
        font-size: 0.85rem;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .btn-success {
        background: linear-gradient(135deg, var(--success-color), #219653);
        border: none;
        padding: 14px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
        width: 100%;
        font-size: 1.1rem;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #219653, var(--success-color));
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--info-color), #2980b9);
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 1rem;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #2980b9, var(--info-color));
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, var(--secondary-color), #666);
        border: none;
        padding: 12px 30px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
        font-size: 1rem;
    }
    
    .btn-secondary:hover {
        background: linear-gradient(135deg, #666, var(--secondary-color));
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(84, 84, 84, 0.4);
    }
    
    /* Contact Info in Modal */
    .contact-info {
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-top: 30px;
        border-left: 4px solid var(--info-color);
    }
    
    .contact-info h5 {
        color: black;
        margin-bottom: 20px;
        font-weight: 600;
        font-size: 1.2rem;
    }
    
    .contact-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .contact-icon {
        background-color: var(--info-color);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .contact-details h6 {
        color: black;
        margin-bottom: 5px;
        font-size: 1rem;
    }
    
    .contact-details p {
        color: #666;
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    /* Footer */
    .footer {
        background-color: #545454;
        color: white;
        padding: 40px 0 12px;
        margin-top: auto;
    }

    .footer-logo {
        width: 140px;
        margin-bottom: 12px;
    }

    .footer h5,
    .footer h6 {
        font-family: 'Playfair Display', serif;
        color: var(--light-color);
        margin-bottom: 12px;
        position: relative;
        font-size: 1rem;
    }

    .footer h5::after,
    .footer h6::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 30px;
        height: 2px;
        background-color: var(--primary-color);
    }

    .footer .nav-link {
        color: rgba(255,255,255,0.7);
        padding: 3px 0;
        transition: var(--transition);
        font-size: 0.85rem;
    }

    .footer .nav-link:hover,
    .footer .nav-link:focus {
        color: white;
        padding-left: 3px;
    }

    .social-icons .btn {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 6px;
        transition: var(--transition);
        background: rgba(255,255,255,0.1);
        color: white;
        font-size: 0.8rem;
    }

    .social-icons .btn:hover,
    .social-icons .btn:focus {
        transform: translateY(-2px);
        background: var(--primary-color);
    }

    .copyright {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 12px;
        margin-top: 25px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.6);
    }
    
    /* Post Actions Dropdown */
    .post-actions {
        position: relative;
    }
    
    .post-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 150px;
        z-index: 1000;
    }
    
    .post-dropdown-item {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        border-bottom: 1px solid #f1f1f1;
        transition: background 0.3s;
    }
    
    .post-dropdown-item:hover {
        background: #f8f9fa;
    }
    
    .post-dropdown-item i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
    
    /* Alerts */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        border-left: 4px solid;
    }
    
    .alert i {
        font-size: 1.2rem;
        margin-top: 2px;
    }
    
    .alert-success {
        background-color: rgba(39, 174, 96, 0.1);
        border-color: var(--success-color);
        color: #155724;
    }
    
    .alert-danger {
        background-color: rgba(231, 76, 60, 0.1);
        border-color: var(--accent-color);
        color: #721c24;
    }
    
    /* File Upload */
    .file-upload {
        position: relative;
        overflow: hidden;
        margin-bottom: 10px;
    }
    
    .file-upload-btn {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    
    .file-upload-btn:hover {
        border-color: var(--info-color);
        background-color: rgba(52, 152, 219, 0.05);
    }
    
    .file-upload-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    
    /* Image Modal */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.95);
        overflow: auto;
        padding: 10px;
    }
    
    .modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90vh;
        object-fit: contain;
    }
    
    .close-modal {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }
    
    .close-modal:hover {
        color: #bbb;
    }
    
    /* Loading Spinner */
    .spinner-container {
        display: none;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--info-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #666;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h5 {
        color: #666;
        margin-bottom: 10px;
    }
    
    .empty-state p {
        color: #999;
        max-width: 400px;
        margin: 0 auto 20px;
    }
    
    /* Scroll to Top Button */
    .scroll-top-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--info-color);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1000;
        box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    }
    
    .scroll-top-btn.show {
        opacity: 1;
        visibility: visible;
    }
    
    .scroll-top-btn:hover {
        background: #2980b9;
        transform: translateY(-3px);
    }
  
    
    /* Character Counter */
    .char-counter {
        font-size: 0.8rem;
        color: #666;
        text-align: right;
        margin-top: 5px;
    }
    
    .char-counter.near-limit {
        color: orange;
    }
    
    .char-counter.over-limit {
        color: red;
        font-weight: bold;
    }
    
    /* Tooltip */
    .tooltip-icon {
        cursor: help;
        color: var(--info-color);
        margin-left: 5px;
    }
    
    /* Confirmation Dialog */
    .confirmation-dialog {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }
    
    .dialog-content {
        background: white;
        padding: 30px;
        border-radius: 15px;
        max-width: 400px;
        width: 90%;
        text-align: center;
    }
    
    /* Welcome User Section */
    .welcome-user {
        background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .welcome-user i {
        font-size: 1.5rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-white fixed-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand me-4" href="index.php" aria-label="Iloilo City Info App">
      <img src="img/logo4.png" alt="Iloilo City Info App Logo" class="logo">
    </a>
    <button class="navbar-toggler" type="button" onclick="openSidebar()" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php" aria-current="page">🏠 Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="popular.php">🗺️ Tourism</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="categories.php">🗂️ Categories</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="transportations.php">🚌 Transport</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="services.php">🛎️ Transactions</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="announcement.php">📣 News & Events</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="contact.php">📬 Contact Us</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="about.php">ℹ️ About Us</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>
<div id="mySidebar" class="sidebar">
  <a href="index.php" aria-current="page"> 🏠  Home</a>
  <a href="popular.php"> 🗺️  Tourism</a>
  <a href="categories.php"> 🗂️  Categories</a>
  <a href="transportations.php"> 🚌  Transport</a>
  <a href="services.php"> 🛎️  Transactions</a>
  <a href="announcement.php">📣  News & Events</a>
  <a href="contact.php"> 📬  Contact Us</a>
  <a href="about.php" >ℹ️  About Us</a>

  <div class="mt-auto p-4 text-center">
    <div class="row g-2 text-center">
      <div class="col-4">
        <a href="#" class="text-black d-block p-2 rounded hover-effect" aria-label="Facebook">
          <i class="fab fa-facebook-f fa-lg"></i>
        </a>
      </div>
      <div class="col-4">
        <a href="#" class="text-black d-block p-2 rounded hover-effect" aria-label="Twitter">
          <i class="fab fa-twitter fa-lg"></i>
        </a>
      </div>
      <div class="col-4">
        <a href="#" class="text-black d-block p-2 rounded hover-effect" aria-label="Instagram">
          <i class="fab fa-instagram fa-lg"></i>
        </a>
      </div>
    </div>    
    <p class="small text-black-50">© Iloilo City Info App</p>
  </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="contact-modal">
    <div class="contact-modal-content">
        <div class="contact-modal-header">
            <div>
                <h2><i class="fas fa-envelope me-3"></i>Contact Us</h2>
                <p>Have questions or feedback? We're here to help!</p>
            </div>
            <button class="close-contact-modal" onclick="closeContactModal()" aria-label="Close contact modal">&times;</button>
        </div>
        
        <div class="contact-modal-body">
            <?php if ($contact_success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h5 class="mb-1">Message Sent Successfully!</h5>
                        <p class="mb-0">Thank you for contacting us. We'll respond within 24-48 hours.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="post" id="contactForm" novalidate>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required" for="first_name">
                                <i class="fas fa-user"></i> First Name
                            </label>
                            <input type="text" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" 
                                   id="first_name" name="first_name" placeholder="Enter your first name" 
                                   value="<?= isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : '' ?>" required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?= $errors['first_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required" for="last_name">
                                <i class="fas fa-user"></i> Last Name
                            </label>
                            <input type="text" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" 
                                   id="last_name" name="last_name" placeholder="Enter your last name" 
                                   value="<?= isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : '' ?>" required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?= $errors['last_name'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required" for="email">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                   id="email" name="email" placeholder="your.email@example.com" 
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle"></i> <?= $errors['email'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="phone">
                                <i class="fas fa-phone"></i> Phone Number (Optional)
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="(123) 456-7890" 
                                   value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                            <small class="form-text text-muted">We'll only call if we need clarification</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="type">
                        <i class="fas fa-question-circle"></i> How can we help you?
                    </label>
                    <select class="form-select" id="type" name="type">
                        <option value="General" <?= (isset($_POST['type']) && $_POST['type'] == 'General') ? 'selected' : '' ?>>General Inquiry</option>
                        <option value="Complaint" <?= (isset($_POST['type']) && $_POST['type'] == 'Complaint') ? 'selected' : '' ?>>Report an Issue</option>
                        <option value="Request" <?= (isset($_POST['type']) && $_POST['type'] == 'Request') ? 'selected' : '' ?>>Service Request</option>
                        <option value="Feedback" <?= (isset($_POST['type']) && $_POST['type'] == 'Feedback') ? 'selected' : '' ?>>Feedback & Suggestions</option>
                        <option value="Technical" <?= (isset($_POST['type']) && $_POST['type'] == 'Technical') ? 'selected' : '' ?>>Technical Support</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="subject">
                        <i class="fas fa-tag"></i> Subject (Optional)
                    </label>
                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief description of your inquiry" 
                           value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="message">
                        <i class="fas fa-comment"></i> Your Message
                    </label>
                    <textarea class="form-control" id="message" name="message" rows="6" 
                              placeholder="Please provide detailed information about your inquiry. The more details you provide, the better we can assist you."><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                    <div class="char-counter" id="messageCounter">0/2000 characters</div>
                    <small class="form-text text-muted">Please be as detailed as possible so we can better assist you.</small>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" name="submit_contact" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-paper-plane me-2"></i> Send Message
                    </button>
                    <p class="text-muted text-center mt-3 mb-0">
                        <i class="fas fa-clock me-1"></i> We typically respond within 24-48 hours
                    </p>
                </div>
            </form>
            
            <div class="contact-info">
                <h5>Other Ways to Reach Us</h5>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h6>By Phone</h6>
                        <p>(033) 337-7777<br>Monday-Friday, 8:00 AM - 5:00 PM</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h6>By Email</h6>
                        <p>info@iloilocity.gov.ph<br>We typically respond within 24 hours</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h6>In Person</h6>
                        <p>Iloilo City Hall<br>Iznart St, Iloilo City Proper</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="close-modal" onclick="closeImageModal()">&times;</span>
    <img class="modal-content" id="modalImage" alt="Enlarged post image">
</div>


<!-- Confirmation Dialog -->
<div class="confirmation-dialog" id="confirmationDialog">
    <div class="dialog-content">
        <i class="fas fa-question-circle fa-3x text-info mb-3"></i>
        <h5 id="dialogTitle">Confirm Action</h5>
        <p id="dialogMessage">Are you sure you want to perform this action?</p>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-secondary flex-fill" onclick="hideDialog()">Cancel</button>
            <button class="btn btn-danger flex-fill" id="dialogConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <!-- Posts Section -->
    <div class="posts-section">
        <div class="section-header">
            <h2><i class="fas fa-users me-3"></i>Community Posts</h2>
            <p>Discover what's happening in Iloilo City through our community</p>
        </div>
        
        <div class="section-body">
            <!-- Welcome Message for Logged-in Users -->
            <?php if ($isLoggedIn): ?>
                <div class="welcome-user">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <h5 class="mb-1">Welcome, <?php echo htmlspecialchars($userName); ?>!</h5>
                        <p class="mb-0">Share your Iloilo experience with our community.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons-container">
                <!-- Left side: Login to Post -->
                <div class="action-button-group left">
                    <?php if ($isLoggedIn): ?>
                        <button class="secondary-action-btn" id="createPostBtn">
                            <i class="fas fa-plus-circle"></i>
                            <span>CREATE POST</span>
                        </button>
                        <div class="action-btn-text">Share your Iloilo experience</div>
                    <?php else: ?>
                        <a href="login.php" class="secondary-action-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>POST</span>
                        </a>
                        <div class="action-btn-text">Join the conversation</div>
                    <?php endif; ?>
                </div>
                
                <!-- Right side: Contact Us -->
                <div class="action-button-group right">
                    <button class="main-action-btn" onclick="openContactModal()">
                        <i class="fas fa-envelope"></i>
                        <span>CONTACT US</span>
                    </button>
                    <div class="action-btn-text">Have questions? Get in touch with us!</div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors) && !isset($errors['first_name']) && !isset($errors['last_name']) && !isset($errors['email'])): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Create Post Form -->
            <?php if ($isLoggedIn): ?>
                <div class="create-post-form" id="createPostForm">
                    <div class="form-header mb-3">
                        <h4><i class="fas fa-pen-alt me-2"></i><?php echo $editing_post ? 'Edit Your Post' : 'Create a New Post'; ?></h4>
                    </div>
                    
                    <?php if ($editing_post): ?>
                        <form method="post" enctype="multipart/form-data" id="editPostForm">
                            <input type="hidden" name="post_id" value="<?php echo $edit_post_id; ?>">
                            <div class="form-group">
                                <label class="form-label" for="edit_description">
                                    <i class="fas fa-edit"></i> Share your experience
                                </label>
                                <textarea class="form-control" id="edit_description" name="description" rows="5" 
                                          placeholder="What's happening in Iloilo City? Share your thoughts, experiences, recommendations, or ask questions..." 
                                          required><?php echo htmlspecialchars($edit_post_data['description']); ?></textarea>
                                <div class="char-counter" id="editCharCounter"><?php echo strlen($edit_post_data['description']); ?>/2000 characters</div>
                                <small class="form-text text-muted">Share what makes Iloilo special to you!</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image"></i> Change photo (optional)
                                </label>
                                <?php if (!empty($edit_post_data['image_path'])): ?>
                                    <div class="mb-3">
                                        <p class="small text-muted">Current image:</p>
                                        <img src="<?php echo htmlspecialchars($edit_post_data['image_path']); ?>" 
                                             alt="Current post image" class="post-image" style="max-width: 200px; cursor: pointer;"
                                             onclick="openImageModal('<?php echo htmlspecialchars($edit_post_data['image_path']); ?>')">
                                    </div>
                                <?php endif; ?>
                                <div class="file-upload">
                                    <div class="file-upload-btn">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span id="edit-file-name">Click to upload a new image</span>
                                        <small class="text-muted">Click to browse</small>
                                    </div>
                                    <input type="file" class="file-upload-input" id="edit_post_image" name="post_image" accept="image/*">
                                </div>
                                <small class="form-text text-muted">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" name="update_post" class="btn btn-primary flex-fill">
                                    <i class="fas fa-save me-2"></i> Update Post
                                </button>
                                <a href="contact.php" class="btn btn-secondary flex-fill">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="post" enctype="multipart/form-data" id="postForm">
                            <div class="form-group">
                                <label class="form-label" for="description">
                                    <i class="fas fa-comment"></i> Share your experience
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="5" 
                                          placeholder="What's happening in Iloilo City? Share your thoughts, experiences, recommendations, or ask questions..." 
                                          required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="char-counter" id="charCounter">0/2000 characters</div>
                                <small class="form-text text-muted">Share what makes Iloilo special to you!</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-image"></i> Add a photo (optional)
                                </label>
                                <div class="file-upload">
                                    <div class="file-upload-btn">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span id="file-name">Click to upload an image</span>
                                        <small class="text-muted">Click to browse</small>
                                    </div>
                                    <input type="file" class="file-upload-input" id="post_image" name="post_image" accept="image/*">
                                </div>
                                <small class="form-text text-muted">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
                            </div>
                            
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" name="submit_post" class="btn btn-primary flex-fill">
                                    <i class="fas fa-paper-plane me-2"></i> Publish Post
                                </button>
                                <button type="button" class="btn btn-secondary flex-fill" id="cancelPostBtn">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Posts Feed -->
            <div class="posts-feed mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="fas fa-stream me-2"></i>Recent Posts</h5>
                    <span class="badge bg-info"><?php echo count($posts); ?> posts</span>
                </div>
                
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="far fa-comments"></i>
                        <h5>No posts yet</h5>
                        <p>Be the first to share your Iloilo City experience! Share photos, stories, or ask questions about our beautiful city.</p>
                        <?php if (!$isLoggedIn): ?>
                            <a href="login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Login to Create First Post
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary" onclick="showPostForm()">
                                <i class="fas fa-plus me-2"></i> Create Your First Post
                            </button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $index => $post): ?>
                        <div class="post-card" id="post-<?php echo $post['id']; ?>">
                            <div class="post-header-card">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($post['first_name'], 0, 1)); ?>
                                    </div>
                                    <div class="user-details">
                                        <div class="user-name"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></div>
                                        <div class="post-date text-muted small">
                                            <i class="far fa-clock"></i>
                                            <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($isLoggedIn && ($_SESSION['user_id'] == $post['user_id'] || $isAdmin)): ?>
                                <div class="post-actions">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleDropdown(this)" aria-label="Post options">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="post-dropdown-menu">
                                        <a href="contact.php?edit_post=<?php echo $post['id']; ?>" class="post-dropdown-item">
                                            <i class="fas fa-edit"></i> Edit Post
                                        </a>
                                        <a href="#" class="post-dropdown-item text-danger" 
                                           onclick="confirmDelete('<?php echo $post['id']; ?>')">
                                            <i class="fas fa-trash"></i> Delete Post
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="post-content">
                                <div class="post-description mb-3">
                                    <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                                </div>
                                <?php if (!empty($post['image_path'])): ?>
                                    <div class="text-center">
                                        <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                             alt="Post image" 
                                             class="post-image"
                                             onclick="openImageModal('<?php echo htmlspecialchars($post['image_path']); ?>')"
                                             loading="lazy">
                                        <p class="text-muted mt-2 small"><i class="fas fa-image me-1"></i> Click image to view larger</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="footer mt-5 py-4 text-white">
    <div class="container">
        <div class="row row-cols-3 g-3 text-center text-sm-start">
            <div class="col">
                <h3 class="text-uppercase mb-2 fw-semibold small">About</h3>
                <img src="img/logo4.png" alt="Iloilo City Logo" class="img-fluid mb-2" style="max-width: 100px;" loading="lazy">
                <p class="text-white small mb-2">
                    Discover Iloilo City — rich in culture, history, and heart.
                </p>
                <div class="social-icons">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f small"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter small"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-instagram small"></i></a>
                </div>
            </div>

            <div class="col">
                <h3 class="text-uppercase mb-2 fw-semibold small">Links</h3>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-1"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                    <li class="mb-1"><a href="about.php" class="text-white text-decoration-none">About</a></li>
                    <li class="mb-1"><a href="categories.php" class="text-white text-decoration-none">Categories</a></li>
                    <li class="mb-1"><a href="login.php" class="text-white text-decoration-none">Login</a></li>
                </ul>
            </div>

            <div class="col">
                <h3 class="text-uppercase mb-2 fw-semibold small">Contact</h3>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>Iloilo City Hall</li>
                    <li class="mb-1"><i class="fas fa-phone me-2"></i>(033) 337-7777</li>
                </ul>
            </div>
        </div>

        <hr class="my-3 bg-secondary opacity-50">

        <div class="row">
            <div class="col-12 text-center">
                <p class="small text-white mb-0">&copy; Iloilo City Tourism Office. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initialize on DOM loaded
document.addEventListener('DOMContentLoaded', function() {
    // Post Form Toggle
    const createPostBtn = document.getElementById('createPostBtn');
    const createPostForm = document.getElementById('createPostForm');
    const cancelPostBtn = document.getElementById('cancelPostBtn');
    
    function showPostForm() {
        if (createPostForm) {
            createPostForm.style.display = 'block';
            createPostForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const textarea = createPostForm.querySelector('textarea');
            if (textarea) {
                setTimeout(() => textarea.focus(), 300);
            }
        }
    }
    
    function hidePostForm() {
        if (createPostForm) {
            createPostForm.style.display = 'none';
        }
    }
    
    if (createPostBtn && createPostForm) {
        createPostBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showPostForm();
        });
    }
    
    if (cancelPostBtn) {
        cancelPostBtn.addEventListener('click', function() {
            hidePostForm();
        });
    }
    
    // If editing a post, show form automatically
    <?php if ($editing_post): ?>
        setTimeout(function() {
            showPostForm();
        }, 500);
    <?php endif; ?>
    
    // Character counter for post description
    const descriptionInput = document.getElementById('description');
    const charCounter = document.getElementById('charCounter');
    const editDescriptionInput = document.getElementById('edit_description');
    const editCharCounter = document.getElementById('editCharCounter');
    
    function updateCharCounter(input, counter) {
        const length = input.value.length;
        counter.textContent = length + '/2000 characters';
        
        if (length > 1900 && length <= 2000) {
            counter.classList.add('near-limit');
            counter.classList.remove('over-limit');
        } else if (length > 2000) {
            counter.classList.add('over-limit');
            counter.classList.remove('near-limit');
        } else {
            counter.classList.remove('near-limit', 'over-limit');
        }
    }
    
    if (descriptionInput && charCounter) {
        descriptionInput.addEventListener('input', function() {
            updateCharCounter(this, charCounter);
        });
        // Initial update
        updateCharCounter(descriptionInput, charCounter);
    }
    
    if (editDescriptionInput && editCharCounter) {
        editDescriptionInput.addEventListener('input', function() {
            updateCharCounter(this, editCharCounter);
        });
        // Initial update
        updateCharCounter(editDescriptionInput, editCharCounter);
    }
    
    // Character counter for contact message
    const messageInput = document.getElementById('message');
    const messageCounter = document.getElementById('messageCounter');
    
    if (messageInput && messageCounter) {
        messageInput.addEventListener('input', function() {
            const length = this.value.length;
            messageCounter.textContent = length + '/2000 characters';
            
            if (length > 1900 && length <= 2000) {
                messageCounter.classList.add('near-limit');
                messageCounter.classList.remove('over-limit');
            } else if (length > 2000) {
                messageCounter.classList.add('over-limit');
                messageCounter.classList.remove('near-limit');
            } else {
                messageCounter.classList.remove('near-limit', 'over-limit');
            }
        });
        // Initial update
        const initialLength = messageInput.value.length;
        messageCounter.textContent = initialLength + '/2000 characters';
    }
    
    // File Upload Preview
    const fileInput = document.getElementById('post_image');
    const fileName = document.getElementById('file-name');
    
    if (fileInput && fileName) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    this.value = '';
                    fileName.textContent = 'Click to upload an image';
                }
            } else {
                fileName.textContent = 'Click to upload an image';
            }
        });
    }
    
    const editFileInput = document.getElementById('edit_post_image');
    const editFileName = document.getElementById('edit-file-name');
    
    if (editFileInput && editFileName) {
        editFileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const file = this.files[0];
                editFileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB limit. Please choose a smaller file.');
                    this.value = '';
                    editFileName.textContent = 'Click to upload a new image';
                }
            } else {
                editFileName.textContent = 'Click to upload a new image';
            }
        });
    }
    
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' bytes';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
    
    // Contact form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const email = document.getElementById('email');
            const message = document.getElementById('message');
            let valid = true;
            
            // Reset previous errors
            [firstName, lastName, email].forEach(input => {
                input.classList.remove('is-invalid');
            });
            
            // Validate first name
            if (!firstName.value.trim()) {
                firstName.classList.add('is-invalid');
                valid = false;
            }
            
            // Validate last name
            if (!lastName.value.trim()) {
                lastName.classList.add('is-invalid');
                valid = false;
            }
            
            // Validate email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email.value.trim()) {
                email.classList.add('is-invalid');
                valid = false;
            } else if (!emailPattern.test(email.value)) {
                email.classList.add('is-invalid');
                valid = false;
            }
            
            // Validate message length
            if (message.value.length > 2000) {
                alert('Message is too long. Maximum 2000 characters allowed.');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            } else {
                // Show loading state
                const submitBtn = document.getElementById('submitBtn');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds (in case submission fails)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        });
    }

    // Floating Action Button
    const fabMobile = document.getElementById('fabMobile');
    
    fabMobile.addEventListener('click', function() {
        if (<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {
            showPostForm();
        } else {
            openContactModal();
        }
    });
    
    // Auto-close success messages after 5 seconds
    setTimeout(() => {
        const successAlerts = document.querySelectorAll('.alert-success');
        successAlerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});

// Sidebar functions
function openSidebar() {
    const sidebar = document.getElementById("mySidebar");
    const overlay = document.getElementById("sidebarOverlay");
    
    sidebar.classList.add("open");
    overlay.classList.add("active");
    document.body.style.overflow = "hidden";
    
    sidebar.style.animation = "slideInRight 0.3s ease-out";
}

function closeSidebar() {
    const sidebar = document.getElementById("mySidebar");
    const overlay = document.getElementById("sidebarOverlay");
    
    sidebar.classList.remove("open");
    overlay.classList.remove("active");
    document.body.style.overflow = "auto";
    
    sidebar.style.animation = "";
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeSidebar();
    }
});

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('mySidebar');
    const toggleButton = document.querySelector('.navbar-toggler');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('open') && 
        !sidebar.contains(event.target) && 
        event.target !== toggleButton && 
        !toggleButton.contains(event.target)) {
        closeSidebar();
    }
});

// Contact Modal Functions
function openContactModal() {
    const modal = document.getElementById('contactModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Clear form if success message was shown
    <?php if ($contact_success): ?>
        setTimeout(function() {
            document.getElementById('contactForm').reset();
            const messageCounter = document.getElementById('messageCounter');
            if (messageCounter) {
                messageCounter.textContent = '0/2000 characters';
                messageCounter.classList.remove('near-limit', 'over-limit');
            }
        }, 100);
    <?php endif; ?>
}

function closeContactModal() {
    const modal = document.getElementById('contactModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close contact modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const contactModal = document.getElementById('contactModal');
        if (contactModal.style.display === 'block') {
            closeContactModal();
        }
    }
});

// Close contact modal when clicking outside
window.addEventListener('click', function(event) {
    const contactModal = document.getElementById('contactModal');
    if (event.target == contactModal) {
        closeContactModal();
    }
});

// Post Dropdown
function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    const isShowing = dropdown.style.display === 'block';
    
    // Close all other dropdowns
    document.querySelectorAll('.post-dropdown-menu').forEach(menu => {
        if (menu !== dropdown) {
            menu.style.display = 'none';
        }
    });
    
    // Toggle current dropdown
    dropdown.style.display = isShowing ? 'none' : 'block';
    
    // Close dropdown when clicking outside
    if (!isShowing) {
        setTimeout(() => {
            document.addEventListener('click', closeDropdowns);
        }, 10);
    } else {
        document.removeEventListener('click', closeDropdowns);
    }
}

function closeDropdowns(e) {
    if (!e.target.closest('.post-actions')) {
        document.querySelectorAll('.post-dropdown-menu').forEach(menu => {
            menu.style.display = 'none';
        });
        document.removeEventListener('click', closeDropdowns);
    }
}

// Global functions for buttons
function showPostForm() {
    const createPostForm = document.getElementById('createPostForm');
    if (createPostForm) {
        createPostForm.style.display = 'block';
        createPostForm.scrollIntoView({ behavior: 'smooth' });
        const textarea = createPostForm.querySelector('textarea');
        if (textarea) {
            setTimeout(() => textarea.focus(), 300);
        }
    }
}

// Confirmation dialog for delete
let deletePostId = null;

function confirmDelete(postId) {
    deletePostId = postId;
    const dialog = document.getElementById('confirmationDialog');
    const dialogTitle = document.getElementById('dialogTitle');
    const dialogMessage = document.getElementById('dialogMessage');
    const dialogConfirmBtn = document.getElementById('dialogConfirmBtn');
    
    dialogTitle.textContent = 'Delete Post';
    dialogMessage.textContent = 'Are you sure you want to delete this post? This action cannot be undone.';
    dialogConfirmBtn.textContent = 'Delete';
    
    dialog.style.display = 'flex';
}

function hideDialog() {
    const dialog = document.getElementById('confirmationDialog');
    dialog.style.display = 'none';
    deletePostId = null;
}

// Handle delete confirmation
document.getElementById('dialogConfirmBtn').addEventListener('click', function() {
    if (deletePostId) {
        window.location.href = 'contact.php?delete_post=' + deletePostId;
    }
    hideDialog();
});

// Close dialog with Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideDialog();
    }
});

// Image modal functionality
function openImageModal(imageSrc) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    
    modal.style.display = 'block';
    modalImg.src = imageSrc;
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close the modal when clicking the X
document.querySelector('.close-modal').addEventListener('click', closeImageModal);

// Close the modal when clicking outside the image
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

// Close the modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const imageModal = document.getElementById('imageModal');
        if (imageModal.style.display === 'block') {
            closeImageModal();
        }
    }
});

// Add some helpful tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add tooltip to file upload
    const fileUploadBtns = document.querySelectorAll('.file-upload-btn');
    fileUploadBtns.forEach(btn => {
        btn.title = 'Click to select an image file (max 5MB)';
    });
    
    // Add tooltip to character counter
    const charCounters = document.querySelectorAll('.char-counter');
    charCounters.forEach(counter => {
        counter.title = 'Character count: 2000 maximum';
    });
});
</script>
</body>
</html>