<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'conn.php';

$first_name = $last_name = $email = $current_password = $new_password = $confirm_password = '';
$first_name_err = $last_name_err = $email_err = $avatar_err = '';
$current_password_err = $new_password_err = $confirm_password_err = '';
$success_msg = $password_success_msg = '';

$userId = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, avatar, role, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$first_name = $user['first_name'];
$last_name = $user['last_name'];
$email = $user['email'];
$current_avatar = $user['avatar'] ?? 'default-avatar.jpg';
$user_role = $user['role'];
$created_at = $user['created_at'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    if (empty(trim($_POST['first_name']))) {
        $first_name_err = 'Please enter your first name.';
    } else {
        $first_name = trim($_POST['first_name']);
    }
    
    if (empty(trim($_POST['last_name']))) {
        $last_name_err = 'Please enter your last name.';
    } else {
        $last_name = trim($_POST['last_name']);
    }
    
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email address.';
    } else {
        $new_email = trim($_POST['email']);
        if ($new_email !== $email) {
            $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_email);
            $check_stmt->bind_param("si", $new_email, $userId);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows == 1) {
                $email_err = 'This email is already taken.';
            } else {
                $email = $new_email;
            }
            $check_stmt->close();
        } else {
            $email = $new_email;
        }
    }
    
    $avatar_path = $current_avatar;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; 
        
        if (in_array($_FILES['avatar']['type'], $allowed_types)) {
            if ($_FILES['avatar']['size'] <= $max_size) {
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_filename = 'avatar_' . $userId . '_' . time() . '.' . $file_extension;
                $upload_path = 'uploads/avatars/' . $new_filename;
                
                if (!is_dir('uploads/avatars')) {
                    mkdir('uploads/avatars', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    $avatar_path = $upload_path;
                    if ($current_avatar !== 'default-avatar.jpg' && file_exists($current_avatar)) {
                        unlink($current_avatar);
                    }
                    $current_avatar = $avatar_path;
                } else {
                    $avatar_err = 'Failed to upload avatar.';
                }
            } else {
                $avatar_err = 'Avatar file size must be less than 2MB.';
            }
        } else {
            $avatar_err = 'Please upload a valid image file (JPEG, PNG, GIF).';
        }
    }
    
    if (empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($avatar_err)) {
        $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, avatar = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $avatar_path, $userId);
        
        if ($update_stmt->execute()) {
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['avatar'] = $avatar_path;
            
            $success_msg = 'Profile updated successfully!';
        } else {
            $avatar_err = 'Failed to update profile. Please try again.';
        }
        $update_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    if (empty(trim($_POST['current_password']))) {
        $current_password_err = 'Please enter your current password.';
    } else {
        $current_password = trim($_POST['current_password']);
    }
    
    if (empty(trim($_POST['new_password']))) {
        $new_password_err = 'Please enter a new password.';     
    } elseif (strlen(trim($_POST['new_password'])) < 8) {
        $new_password_err = 'Password must have at least 8 characters.';
    } else {
        $new_password = trim($_POST['new_password']);
    }
    
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm the new password.';     
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = 'Passwords did not match.';
        }
    }
    
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        
        if (password_verify($current_password, $hashed_password)) {
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt->bind_param("si", $new_hashed_password, $userId);
            
            if ($update_stmt->execute()) {
                $password_success_msg = 'Password changed successfully!';
                $current_password = $new_password = $confirm_password = '';
            } else {
                $current_password_err = 'Failed to change password. Please try again.';
            }
            $update_stmt->close();
        } else {
            $current_password_err = 'Current password is incorrect.';
        }
    }
}

$stmt->close();
$conn->close();

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Iloilo City Info</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
<style>
      :root {
        --primary-color: #545454;
        --primary-dark: #3d3d3d;
        --secondary-color: #1e1e2f;
        --accent-color: #800000;
        --light-color: #f8f4e3;
        --dark-color: #2c2f48;
        --text-color: #333;
        --text-light: #f8f9fa;
        --transition: all 0.3s ease;
      }

      body {
        font-family: 'Open Sans', sans-serif;
        background: url('img/bg.png') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      /* Profile Section Styles */
      .profile-section {
        padding: 120px 0 80px;
        min-height: 100vh;
      }

      .profile-card {
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.15);
        overflow: hidden;
      }

      .profile-header {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        padding: 2rem;
        text-align: center;
      }

      .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        object-fit: cover;
        background: var(--light-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: var(--primary-color);
        font-weight: bold;
        margin: 0 auto 1rem;
      }

      .avatar-upload {
        position: relative;
        display: inline-block;
      }

      .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: var(--transition);
        cursor: pointer;
      }

      .avatar-upload:hover .avatar-overlay {
        opacity: 1;
      }

      .avatar-overlay i {
        color: white;
        font-size: 2rem;
      }

      #avatar-input {
        display: none;
      }

      .profile-body {
        padding: 2rem;
      }

      /* Tab Styles */
      .profile-tabs {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 2rem;
      }

      .profile-tabs .nav-link {
        border: none;
        color: var(--dark-color);
        font-weight: 600;
        padding: 1rem 2rem;
        transition: var(--transition);
      }

      .profile-tabs .nav-link:hover {
        color: var(--primary-color);
        background-color: rgba(84, 84, 84, 0.05);
      }

      .profile-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: transparent;
        border-bottom: 3px solid var(--primary-color);
      }

      /* Form Styles */
      .form-control {
        background-color: rgba(255, 255, 255, 0.9);
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px 15px;
        transition: var(--transition);
      }

      .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(84, 84, 84, 0.25);
        border-color: var(--primary-color);
        background-color: white;
      }

      .password-field {
        position: relative;
      }

      .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        transition: var(--transition);
      }

      .password-toggle:hover {
        color: var(--primary-color);
      }

      /* Password strength indicator */
      .password-strength {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 5px;
        overflow: hidden;
      }

      .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
      }

      /* Profile Info Styles */
      .profile-info-item {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
      }

      .profile-info-item:last-child {
        border-bottom: none;
      }

      .info-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
      }

      .info-value {
        color: var(--text-color);
        font-size: 1.1rem;
      }

      /* Navbar Styles */
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
        font-size: 14px;
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

      /* User Profile in Navbar */
      .user-profile-nav {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 15px;
        border-radius: 25px;
        background: rgba(255, 255, 255, 0.9);
        margin-left: 15px;
        transition: var(--transition);
      }

      .user-profile-nav:hover {
        background: rgba(255, 255, 255, 1);
        transform: translateY(-2px);
      }

      .user-avatar-nav {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
        overflow: hidden;
      }

      .user-avatar-nav img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .user-info-nav {
        display: flex;
        flex-direction: column;
      }

      .user-name-nav {
        font-weight: 600;
        font-size: 14px;
        color: var(--dark-color);
        margin: 0;
      }

      .user-role-nav {
        font-size: 11px;
        color: #666;
        margin: 0;
      }

      /* Sidebar Styles */
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

      /* Footer Styles */
      .footer {
        background-color: #545454;
        color: white;
        padding: 60px 0 20px;
        margin-top: auto;
      }

      /* Button Styles */
      .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        transition: var(--transition);
      }

      .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
      }

      .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
      }

      .btn-outline-primary:hover {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .profile-section {
          padding: 100px 0 60px;
        }
        
        .profile-body {
          padding: 1.5rem;
        }
        
        .user-profile-nav {
          margin-left: 0;
          margin-top: 10px;
        }
        
        .profile-tabs .nav-link {
          padding: 0.75rem 1rem;
          font-size: 0.9rem;
        }
      }

      @media (max-width: 576px) {
        .profile-header {
          padding: 1.5rem;
        }
        
        .profile-body {
          padding: 1rem;
        }
        
        .user-profile-nav {
          padding: 6px 10px;
        }
        
        .user-name-nav {
          font-size: 12px;
        }
        
        .user-role-nav {
          font-size: 10px;
        }
        
        .user-avatar-nav {
          width: 30px;
          height: 30px;
          font-size: 12px;
        }
        
        .profile-tabs .nav-link {
          padding: 0.5rem 0.75rem;
          font-size: 0.85rem;
        }
      }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-white fixed-top">
  <div class="container-fluid px-3">
    <a class="navbar-brand me-4" href="homepage.php" aria-label="Iloilo City Info App">
      <img src="img/logo4.png" alt="Iloilo City Info App Logo" class="logo">
    </a>
    <button class="navbar-toggler" type="button" onclick="openSidebar()" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="homepage.php" aria-current="page">🏠 Home</a>
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
  <a href="homepage.php" aria-current="page"> 🏠  Home</a>
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
    <p class="small text-black-50">© 2025 Iloilo City Info App</p>
  </div>
</div>

    <main class="profile-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="avatar-upload">
                                <?php if ($current_avatar && file_exists($current_avatar) && $current_avatar !== 'default-avatar.jpg'): ?>
                                    <img src="<?php echo htmlspecialchars($current_avatar); ?>" alt="Profile Avatar" class="profile-avatar" id="avatar-preview">
                                <?php else: ?>
                                    <div class="profile-avatar" id="avatar-preview">
                                        <?php echo strtoupper(substr($first_name, 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="avatar-overlay" onclick="document.getElementById('avatar-input').click()">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <input type="file" id="avatar-input" name="avatar" accept="image/*" onchange="previewAvatar(event)">
                            </div>
                            <h1 class="h3 mb-2"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></h1>
                            <p class="mb-0 opacity-75">Member since <?php echo date('F Y', strtotime($created_at)); ?></p>
                        </div>
                        
                        <div class="profile-body">
                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($password_success_msg)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $password_success_msg; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <ul class="nav nav-tabs profile-tabs" id="profileTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" 
                                            id="profile-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#profile" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="profile" 
                                            aria-selected="<?php echo $active_tab === 'profile' ? 'true' : 'false'; ?>">
                                        <i class="fas fa-user me-2"></i>Profile Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $active_tab === 'edit' ? 'active' : ''; ?>" 
                                            id="edit-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#edit" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="edit" 
                                            aria-selected="<?php echo $active_tab === 'edit' ? 'true' : 'false'; ?>">
                                        <i class="fas fa-edit me-2"></i>Edit Profile
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>" 
                                            id="password-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#password" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="password" 
                                            aria-selected="<?php echo $active_tab === 'password' ? 'true' : 'false'; ?>">
                                        <i class="fas fa-lock me-2"></i>Change Password
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="profileTabsContent">
                                <div class="tab-pane fade <?php echo $active_tab === 'profile' ? 'show active' : ''; ?>" 
                                     id="profile" 
                                     role="tabpanel" 
                                     aria-labelledby="profile-tab">
                                    <div class="profile-info-item">
                                        <div class="info-label">Full Name</div>
                                        <div class="info-value"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                                    </div>
                                    
                                    <div class="profile-info-item">
                                        <div class="info-label">Email Address</div>
                                        <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                                    </div>
                                    
                                    <div class="profile-info-item">
                                        <div class="info-label">Account Type</div>
                                        <div class="info-value">
                                            <span class="badge bg-primary"><?php echo ucfirst($user_role); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="profile-info-item">
                                        <div class="info-label">Member Since</div>
                                        <div class="info-value"><?php echo date('F j, Y', strtotime($created_at)); ?></div>
                                    </div>
                                    
                                    <div class="text-center mt-4">
                                        <a href="?tab=edit" class="btn btn-primary me-3">
                                            <i class="fas fa-edit me-2"></i>Edit Profile
                                        </a>
                                        <a href="homepage.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Home
                                        </a>
                                    </div>
                                </div>

                                <div class="tab-pane fade <?php echo $active_tab === 'edit' ? 'show active' : ''; ?>" 
                                     id="edit" 
                                     role="tabpanel" 
                                     aria-labelledby="edit-tab">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=edit" enctype="multipart/form-data" novalidate>
                                        <input type="hidden" name="update_profile" value="1">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control <?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>" 
                                                       id="first_name" name="first_name" 
                                                       value="<?php echo htmlspecialchars($first_name); ?>" 
                                                       required>
                                                <?php if (!empty($first_name_err)): ?>
                                                    <div class="invalid-feedback"><?php echo $first_name_err; ?></div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control <?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>" 
                                                       id="last_name" name="last_name" 
                                                       value="<?php echo htmlspecialchars($last_name); ?>" 
                                                       required>
                                                <?php if (!empty($last_name_err)): ?>
                                                    <div class="invalid-feedback"><?php echo $last_name_err; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                                   id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($email); ?>" 
                                                   required>
                                            <?php if (!empty($email_err)): ?>
                                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">Profile Picture</label>
                                            <input type="file" class="form-control <?php echo (!empty($avatar_err)) ? 'is-invalid' : ''; ?>" 
                                                   name="avatar" accept="image/*">
                                            <div class="form-text">Upload JPG, PNG, or GIF (Max 2MB)</div>
                                            <?php if (!empty($avatar_err)): ?>
                                                <div class="invalid-feedback d-block"><?php echo $avatar_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-center mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary me-3">
                                                <i class="fas fa-save me-2"></i>Save Changes
                                            </button>
                                            <a href="?tab=profile" class="btn btn-outline-secondary me-3">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                            <a href="?tab=password" class="btn btn-outline-primary">
                                                <i class="fas fa-lock me-2"></i>Change Password
                                            </a>
                                        </div>
                                    </form>
                                </div>

                                <div class="tab-pane fade <?php echo $active_tab === 'password' ? 'show active' : ''; ?>" 
                                     id="password" 
                                     role="tabpanel" 
                                     aria-labelledby="password-tab">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?tab=password" novalidate>
                                        <input type="hidden" name="change_password" value="1">
                                        <div class="mb-3 password-field">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>" 
                                                   id="current_password" name="current_password" 
                                                   value="<?php echo htmlspecialchars($current_password); ?>" 
                                                   required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!empty($current_password_err)): ?>
                                                <div class="invalid-feedback"><?php echo $current_password_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mb-3 password-field">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" 
                                                   id="new_password" name="new_password" 
                                                   value="<?php echo htmlspecialchars($new_password); ?>" 
                                                   required
                                                   oninput="checkPasswordStrength(this.value)">
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="password-strength mt-2">
                                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                            </div>
                                            <?php if (!empty($new_password_err)): ?>
                                                <div class="invalid-feedback"><?php echo $new_password_err; ?></div>
                                            <?php endif; ?>
                                            <div class="form-text">Password must be at least 8 characters long</div>
                                        </div>
                                        
                                        <div class="mb-4 password-field">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                                   id="confirm_password" name="confirm_password" 
                                                   value="<?php echo htmlspecialchars($confirm_password); ?>" 
                                                   required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (!empty($confirm_password_err)): ?>
                                                <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-center mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary me-3">
                                                <i class="fas fa-key me-2"></i>Change Password
                                            </button>
                                            <a href="?tab=profile" class="btn btn-outline-secondary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Profile
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="footer mt-5 py-4 text-white">
      <div class="container">
        <div class="row row-cols-3 g-3 text-center text-sm-start">
         
          <div class="col">
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">About</h3>
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
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">Links</h3>
            <ul class="list-unstyled mb-0 small">
              <li class="mb-1"><a href="homepage.php" class="text-white text-decoration-none">Home</a></li>
              <li class="mb-1"><a href="about.php" class="text-white text-decoration-none">About</a></li>
              <li class="mb-1"><a href="categories.php" class="text-white text-decoration-none">Categories</a></li>
              <li class="mb-1"><a href="profile.php" class="text-white text-decoration-none">Profile</a></li>
            </ul>
          </div>

          <div class="col">
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">Contact</h3>
            <ul class="list-unstyled mb-0 small">
              <li class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>Iloilo City Hall</li>
              <li class="mb-1"><i class="fas fa-phone me-2"></i>(033) 337-7777</li>
            </ul>
          </div>
        </div>

        <hr class="my-3 bg-secondary opacity-50">

        <div class="row">
          <div class="col-12 text-center">
            <p class="small text-white mb-0">&copy; 2025 Iloilo City Tourism Office. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
      function openSidebar() {
        const sidebar = document.getElementById("mySidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        sidebar.classList.add("open");
        overlay.classList.add("active");
        document.body.style.overflow = "hidden";
      }

      function closeSidebar() {
        const sidebar = document.getElementById("mySidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        sidebar.classList.remove("open");
        overlay.classList.remove("active");
        document.body.style.overflow = "auto";
      }

      function previewAvatar(event) {
        const input = event.target;
        const preview = document.getElementById('avatar-preview');
        
        if (input.files && input.files[0]) {
          const reader = new FileReader();
          
          reader.onload = function(e) {
            if (preview.tagName === 'IMG') {
              preview.src = e.target.result;
            } else {
              const img = document.createElement('img');
              img.src = e.target.result;
              img.alt = 'Profile Avatar';
              img.className = 'profile-avatar';
              img.id = 'avatar-preview';
              preview.parentNode.replaceChild(img, preview);
            }
          }
          
          reader.readAsDataURL(input.files[0]);
        }
      }

      function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
      }

      function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrengthBar');
        let strength = 0;
        
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        
        if (password.match(/[a-z]/)) strength += 1; // lowercase
        if (password.match(/[A-Z]/)) strength += 1; // uppercase
        if (password.match(/[0-9]/)) strength += 1; // numbers
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1; // special chars
        
        let width = 0;
        let color = 'red';
        
        if (strength <= 2) {
          width = 25;
          color = 'red';
        } else if (strength <= 4) {
          width = 50;
          color = 'orange';
        } else if (strength <= 6) {
          width = 75;
          color = 'yellow';
        } else {
          width = 100;
          color = 'green';
        }
        
        strengthBar.style.width = width + '%';
        strengthBar.style.backgroundColor = color;
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

      document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab) {
          const tabElement = document.getElementById(tab + '-tab');
          if (tabElement) {
            const tab = new bootstrap.Tab(tabElement);
            tab.show();
          }
        }
      });
    </script>
</body>
</html>