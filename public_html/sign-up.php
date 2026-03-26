<?php
// Start session
session_start();
include "conn.php";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}



// Initialize variables
$first_name = $last_name = $email = $password = '';
$first_name_err = $last_name_err = $email_err = $password_err = $terms_err = '';
$success_msg = '';

// Process form data when submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate first name
    if (empty(trim($_POST['first_name']))) {
        $first_name_err = 'Please enter your first name.';
    } else {
        $first_name = trim($_POST['first_name']);
    }
    
    // Validate last name
    if (empty(trim($_POST['last_name']))) {
        $last_name_err = 'Please enter your last name.';
    } else {
        $last_name = trim($_POST['last_name']);
    }
    
    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email address.';
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST['email']);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $email_err = 'This email is already taken.';
                } else {
                    $email = trim($_POST['email']);
                }
            } else {
                echo 'Oops! Something went wrong. Please try again later.';
            }
            
            $stmt->close();
        }
    }
    
    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';     
    } elseif (strlen(trim($_POST['password'])) < 8) {
        $password_err = 'Password must have at least 8 characters.';
    } else {
        $password = trim($_POST['password']);
    }
    
    // Validate terms
    if (!isset($_POST['terms'])) {
        $terms_err = 'You must agree to the terms and conditions.';
    }
    
    // Check for errors before inserting into database
    if (empty($first_name_err) && empty($last_name_err) && empty($email_err) && empty($password_err) && empty($terms_err)) {
        // Prepare SQL statement - default role is 'user'
        $sql = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'user')";
         
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $param_first_name, $param_last_name, $param_email, $param_password);
            
            // Set parameters
            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            if ($stmt->execute()) {
                // Registration successful - redirect to login page
                $success_msg = "Account created successfully! Please log in.";
                
                // Clear form fields after successful registration
                $first_name = $last_name = $email = $password = '';
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            $stmt->close();
        }
    }
    
    $conn->close();
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="description" content="Sign up for Iloilo City Information App to access local services and resources">
    <meta name="author" content="Iloilo City Info App Team">
    <title>Sign Up | Iloilo City Info</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

    <style>
      :root {
        --primary-color: rgb(76, 111, 199);
        --secondary-color: #A52A2A;
        --light-color: #f8f9fa;
        --dark-color: #212529;
        --highlight-color: #ffc107;
        --transition: all 0.3s ease;
      }
      

      body {
        background: url('img/bg.png') no-repeat center center fixed;
        background-size: cover;
        padding-top: 70px; 
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        font-family: 'Poppins', sans-serif;
      }
      
      /* Navbar Styling */
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
   
      /* Sign Up Form */
      .form-signup {
        width: 100%;
        max-width: 450px;
        padding: 2rem;
        margin: 2rem auto;
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.15);
        animation: fadeIn 0.5s ease-out;
      }
      
      .form-title {
        color: white;
        margin-bottom: 1.5rem;
        font-weight: 600;
        text-align: center;
        font-size: 2rem;
        position: relative;
        padding-bottom: 10px;
      }
      
      .form-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: var(--highlight-color);
        border-radius: 3px;
      }
      
      /* Custom form field styling - label above input */
      .form-field {
        margin-bottom: 1.25rem;
      }
      
      .form-field label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: white;
        font-size: 0.9rem;
      }
      
      .form-field .input-wrapper {
        position: relative;
        width: 100%;
      }
      
      .form-field input {
        width: 100%;
        padding: 12px 15px;
        background-color: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        color: #212529;
      }
      
      .form-field input:focus {
        outline: none;
        border-color: var(--highlight-color);
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.25);
        background-color: white;
      }
      
      .form-field input.is-invalid {
        border-color: #dc3545;
        padding-right: 40px;
      }
      
      .form-field .invalid-feedback {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
      }
      
      /* Password wrapper and toggle icon */
      .password-wrapper {
        position: relative;
        width: 100%;
      }
      
      .password-wrapper input {
        width: 100%;
        padding-right: 45px;
      }
      
      .password-toggle-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        z-index: 10;
        font-size: 1.2rem;
        background: transparent;
        transition: color 0.3s ease;
      }
      
      .password-toggle-icon:hover {
        color: var(--primary-color);
      }
      
      .btn-signup {
        background-color: var(--primary-color);
        border: none;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.95rem;
        width: 100%;
        color: white;
        border-radius: 8px;
        margin-top: 0.5rem;
      }
      
      .btn-signup:hover {
        background-color: var(--secondary-color);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }
      
      .btn-signup:active {
        transform: translateY(1px);
      }
      
      .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
      }
      
      .login-link {
        color: var(--highlight-color);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s ease;
      }
      
      .login-link:hover {
        text-decoration: underline;
        color: #ffab00;
      }
      
      /* Password strength indicator */
      .password-strength {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 8px;
        overflow: hidden;
      }
      
      .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
      }
      
      /* Animations */
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .form-signup {
          margin: 1.5rem auto;
          padding: 1.5rem;
          max-width: 90%;
        }
        
        .logo {
          width: 200px;
        }
      }
      
      /* Accessibility focus styles */
      a:focus, button:focus, input:focus {
        outline: 3px solid var(--highlight-color);
        outline-offset: 2px;
      }
      
      /* Footer */
      footer {
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
      }
      
      footer a {
        color: #adb5bd;
        text-decoration: none;
        transition: var(--transition);
      }
      
      footer a:hover {
        color: var(--highlight-color);
      }
      
      /* Success Alert Styling */
      .alert-success {
        background-color: rgba(25, 135, 84, 0.9);
        border-color: #198754;
        color: white;
        backdrop-filter: blur(5px);
        border-radius: 8px;
        margin-bottom: 1.5rem;
        text-align: center;
        animation: slideDown 0.5s ease-out;
      }
      
      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
      
      /* Row styling for first/last name */
      .name-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.25rem;
      }
      
      .name-row .form-field {
        flex: 1;
        margin-bottom: 0;
      }
      
      @media (max-width: 576px) {
        .name-row {
          flex-direction: column;
          gap: 1.25rem;
        }
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
        <p class="small text-black-50">© 2025 Iloilo City Info App</p>
      </div>
    </div>
    
    <!-- Main Content -->
    <main class="container d-flex flex-grow-1 align-items-center justify-content-center">
      <div class="form-signup">
        <h1 class="form-title">Create Account</h1>
        
        <?php 
        // Display success message if exists
        if (!empty($success_msg)) {
            echo '<div class="alert alert-success">' . $success_msg . ' <i class="fas fa-spinner fa-spin ms-2"></i></div>';
        }
        ?>
        
        <form id="signupForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="on" novalidate>
          <!-- First and Last Name Row -->
          <div class="name-row">
            <div class="form-field">
              <label for="first_name">First Name <span class="text-danger">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="first_name" name="first_name" placeholder="Enter your first name"
                       value="<?php echo htmlspecialchars($first_name); ?>"
                       class="<?php echo (!empty($first_name_err)) ? 'is-invalid' : ''; ?>"
                       pattern="[A-Za-z ]+" minlength="2" required>
              </div>
              <?php if (!empty($first_name_err)): ?>
                <div class="invalid-feedback"><?php echo $first_name_err; ?></div>
              <?php endif; ?>
            </div>
            
            <div class="form-field">
              <label for="last_name">Last Name <span class="text-danger">*</span></label>
              <div class="input-wrapper">
                <input type="text" id="last_name" name="last_name" placeholder="Enter your last name"
                       value="<?php echo htmlspecialchars($last_name); ?>"
                       class="<?php echo (!empty($last_name_err)) ? 'is-invalid' : ''; ?>"
                       pattern="[A-Za-z ]+" minlength="2" required>
              </div>
              <?php if (!empty($last_name_err)): ?>
                <div class="invalid-feedback"><?php echo $last_name_err; ?></div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Email Field -->
          <div class="form-field">
            <label for="email">Email Address <span class="text-danger">*</span></label>
            <div class="input-wrapper">
              <input type="email" id="email" name="email" placeholder="Enter your email address"
                     value="<?php echo htmlspecialchars($email); ?>"
                     class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                     required>
            </div>
            <?php if (!empty($email_err)): ?>
              <div class="invalid-feedback"><?php echo $email_err; ?></div>
            <?php endif; ?>
          </div>
          
          <!-- Password Field with label above like email -->
          <div class="form-field">
            <label for="password">Password <span class="text-danger">*</span></label>
            <div class="password-wrapper">
              <input type="password" id="password" name="password" placeholder="Create a password"
                     class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                     minlength="8" required oninput="checkPasswordStrength(this.value)">
              <i class="password-toggle-icon fas fa-eye" id="togglePassword" onclick="togglePasswordVisibility()"></i>
            </div>
            <?php if (!empty($password_err)): ?>
              <div class="invalid-feedback"><?php echo $password_err; ?></div>
            <?php endif; ?>
            <div class="password-strength">
              <div class="password-strength-bar" id="passwordStrengthBar"></div>
            </div>
            <small class="text-white-50 d-block mt-1">Use 8+ characters with a mix of letters, numbers & symbols</small>
          </div>
          
          <!-- Terms Checkbox -->
          <div class="form-check mb-3 mt-3">
            <input class="form-check-input <?php echo (!empty($terms_err)) ? 'is-invalid' : ''; ?>" type="checkbox" id="terms" name="terms" required <?php echo (isset($_POST['terms'])) ? 'checked' : ''; ?>>
            <label class="form-check-label text-white" for="terms">
              I agree to the <a href="terms.php" class="text-white"><u>Terms of Service</u></a> and <a href="privacy.php" class="text-white"><u>Privacy Policy</u></a>
            </label>
            <?php if (!empty($terms_err)): ?>
              <div class="invalid-feedback d-block"><?php echo $terms_err; ?></div>
            <?php endif; ?>
          </div>
          
          <button class="btn btn-signup mb-3" type="submit" name="signup" value="signup" <?php echo (!empty($success_msg)) ? 'disabled' : ''; ?>>
            <i class="fas fa-user-plus me-2"></i>Create Account
          </button>
          
          <p class="text-center text-white">Already have an account? <a href="login.php" class="login-link">Log In</a></p>
        </form>
      </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-3 mt-auto">
      <div class="container">
        <p class="mb-0">&copy; 2025 Iloilo City Info App. All rights reserved.</p>
        <p class="mb-0 small mt-1">
          <a href="privacy.php" class="text-white-50 me-2">Privacy Policy</a>
          <a href="terms.php" class="text-white-50">Terms of Service</a>
        </p>
      </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
      // Enhanced Sidebar Functions
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

      // Close sidebar when pressing Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          closeSidebar();
        }
      });

      // Password toggle functionality
      function togglePasswordVisibility() {
        const password = document.getElementById('password');
        const icon = document.getElementById('togglePassword');
        
        if (password.type === 'password') {
          password.type = 'text';
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        } else {
          password.type = 'password';
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      }
      
      // Password strength checker
      function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrengthBar');
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        if (password.length >= 12) strength += 1;
        
        // Complexity checks
        if (password.match(/[a-z]/)) strength += 1; // lowercase
        if (password.match(/[A-Z]/)) strength += 1; // uppercase
        if (password.match(/[0-9]/)) strength += 1; // numbers
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1; // special chars
        
        // Update strength bar
        let width = 0;
        let color = 'red';
        
        if (strength <= 2) {
          width = 25;
          color = '#dc3545'; // red
        } else if (strength <= 4) {
          width = 50;
          color = '#fd7e14'; // orange
        } else if (strength <= 6) {
          width = 75;
          color = '#ffc107'; // yellow
        } else {
          width = 100;
          color = '#198754'; // green
        }
        
        strengthBar.style.width = width + '%';
        strengthBar.style.backgroundColor = color;
      }
      
      // Form validation
      document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('signupForm');
        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const terms = document.getElementById('terms');
        
        // Focus on first name field when page loads
        if (firstName) firstName.focus();
        
        form.addEventListener('submit', function(event) {
          // Only validate if there's no success message (form not already submitted successfully)
          <?php if (empty($success_msg)): ?>
          let valid = true;
          
          // First name validation
          if (!firstName.value.trim() || !/^[A-Za-z ]+$/.test(firstName.value)) {
            firstName.classList.add('is-invalid');
            valid = false;
          } else {
            firstName.classList.remove('is-invalid');
          }
          
          // Last name validation
          if (!lastName.value.trim() || !/^[A-Za-z ]+$/.test(lastName.value)) {
            lastName.classList.add('is-invalid');
            valid = false;
          } else {
            lastName.classList.remove('is-invalid');
          }
          
          // Email validation
          if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.classList.add('is-invalid');
            valid = false;
          } else {
            email.classList.remove('is-invalid');
          }
          
          // Password validation
          if (!password.value || password.value.length < 8) {
            password.classList.add('is-invalid');
            valid = false;
          } else {
            password.classList.remove('is-invalid');
          }
          
          // Terms validation
          if (!terms.checked) {
            terms.classList.add('is-invalid');
            valid = false;
          } else {
            terms.classList.remove('is-invalid');
          }
          
          if (!valid) {
            event.preventDefault();
            event.stopPropagation();
          }
          <?php else: ?>
          // Prevent form submission if already successfully registered
          event.preventDefault();
          <?php endif; ?>
        });
        
        // Real-time validation
        if (firstName) {
          firstName.addEventListener('input', function() {
            if (this.value.trim() && /^[A-Za-z ]+$/.test(this.value)) {
              this.classList.remove('is-invalid');
            }
          });
        }
        
        if (lastName) {
          lastName.addEventListener('input', function() {
            if (this.value.trim() && /^[A-Za-z ]+$/.test(this.value)) {
              this.classList.remove('is-invalid');
            }
          });
        }
        
        if (email) {
          email.addEventListener('input', function() {
            if (this.value && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
              this.classList.remove('is-invalid');
            }
          });
        }
        
        if (password) {
          password.addEventListener('input', function() {
            if (this.value && this.value.length >= 8) {
              this.classList.remove('is-invalid');
            }
          });
        }
        
        if (terms) {
          terms.addEventListener('change', function() {
            if (this.checked) {
              this.classList.remove('is-invalid');
            }
          });
        }
      });
    </script>
  </body>
</html>