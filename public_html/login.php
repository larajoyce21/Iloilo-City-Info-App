<?php
session_start();
include "conn.php";


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}


$email = $password = '';
$email_err = $password_err = $login_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['email'] ?? '')) {
        $email_err = 'Please enter your email address.';
    } else {
        $email = trim($_POST['email']);
    }
    
    if (empty($_POST['password'] ?? '')) {
        $password_err = 'Please enter your password.';     
    } else {
        $password = trim($_POST['password']);
    }
    
    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, email, password, first_name, last_name, role FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $email, $hashed_password, $first_name, $last_name, $role);
                    if ($stmt->fetch()) {
                       
                        if (password_verify($password, $hashed_password)) {
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION['user_id'] = $id;
                            $_SESSION['email'] = $email;
                            $_SESSION['first_name'] = $first_name;
                            $_SESSION['last_name'] = $last_name;
                            $_SESSION['role'] = $role;
                            $_SESSION['loggedin'] = true;
                            
                            // Check if "Remember me" was checked
                            if (isset($_POST['remember'])) {
                                // Set cookie to expire in 30 days
                                setcookie('remember_user', $email, time() + (30 * 24 * 60 * 60), '/');
                            }
                            
                            // Redirect based on role
                            if ($role === 'admin') {
                                header("Location: dashboard.php");
                            } else {
                                header("Location: index.php");
                            }
                            exit();
                        } else {
                            // Password is not valid
                            $login_err = 'Invalid email or password.';
                        }
                    }
                } else {
                    // Email doesn't exist
                    $login_err = 'Invalid email or password.';
                }
            } else {
                $login_err = 'Oops! Something went wrong. Please try again later.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login to access Iloilo City information, services, and resources">
    <title>Login | Iloilo City Info</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
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

      /* Login Form */
      .form-container {
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
      
      .form-floating label {
        color: #adb5bd;
        font-size: 0.95rem;
      }
      
      .form-control {
        background-color: rgba(255, 255, 255, 0.95);
        border: none;
        border-radius: 8px;
        padding: 15px;
        transition: var(--transition);
      }
      
      .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(139, 0, 0, 0.25);
        background-color: white;
      }
      
      .input-group-text {
        background-color: rgba(255, 255, 255, 0.9);
        border: none;
      }
      
      .btn-login {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 12px;
        font-weight: 600;
        transition: var(--transition);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.95rem;
      }
      
      .btn-login:hover {
        background-color: var(--secondary-color);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }
      
      .btn-login:active {
        transform: translateY(1px);
      }
      
      .remember-me {
        margin-top: 1rem;
      }
      
      .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
      }
      
      .form-check-label {
        color: #adb5bd;
        font-size: 0.9rem;
      }
      
      .forgot-password {
        display: block;
        color: #adb5bd;
        text-decoration: none;
        transition: color 0.2s ease;
        font-size: 0.9rem;
      }
      
      .forgot-password:hover {
        color: var(--highlight-color);
        text-decoration: underline;
      }
      
      .signup-link {
        color: var(--highlight-color);
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
      }
      
      .signup-link:hover {
        text-decoration: underline;
        color: #ffab00;
      }
      
      .divider {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
        color: #adb5bd;
        font-size: 0.9rem;
      }
      
      .divider::before, .divider::after {
        content: "";
        flex: 1;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        margin: 0 10px;
      }
      
      .social-login {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-bottom: 1.5rem;
      }
      
      .social-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        transition: var(--transition);
        border: none;
      }
      
      .social-btn:hover {
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }
      
      .social-btn.facebook {
        background-color: #3b5998;
      }
      
      .social-btn.google {
        background-color: #db4437;
      }
      
      .social-btn.apple {
        background-color: #000000;
      }
      
      /* Form feedback */
      .form-feedback {
        font-size: 0.85rem;
        margin-top: 0.5rem;
        display: none;
        color: #dc3545;
      }
      
      .is-invalid {
        border-color: #dc3545 !important;
      }

      .alert-danger {
        background-color: rgba(220, 53, 69, 0.8);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 20px;
      }
      
      /* Password wrapper - eye icon at the end like second image */
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
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .form-container {
          margin: 1.5rem auto;
          padding: 2rem;
          max-width: 90%;
        }
        
        .logo {
          width: 200px;
        }
        
        .form-title {
          font-size: 1.8rem;
        }

        .sidebar {
          width: 100%;
          right: -100%;
        }

        .sidebar.open {
          right: 0;
        }
      }
      
      @media (max-width: 576px) {
        .form-container {
          padding: 1.5rem;
        }
        
        .form-title {
          font-size: 1.6rem;
        }
        
        .navbar {
          height: auto;
          padding: 10px;
        }
      }
      
      /* Animations */
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      @keyframes slideInRight {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
      }
      
      /* Accessibility focus styles */
      a:focus, button:focus, input:focus {
        outline: 3px solid var(--highlight-color);
        outline-offset: 2px;
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
    <!-- Main Content -->
    <main class="container d-flex flex-grow-1 align-items-center justify-content-center">
      <div class="form-container">
        <h1 class="form-title">Welcome Back</h1>
        
        <?php 
        // Display login error if exists
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        
        <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="on" novalidate>
          <div class="form-floating mb-3">
            <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                   id="email" name="email" placeholder="Enter email" required
                   aria-describedby="emailHelp" value="<?php echo $email; ?>">
            <label for="email">Email address</label>
            <div id="emailHelp" class="form-feedback <?php echo (!empty($email_err)) ? 'd-block' : ''; ?>">
              <?php echo $email_err; ?>
            </div>
          </div>
          
          <!-- Password field with eye icon at the end like the second image -->
          <div class="form-floating mb-3">
            <div class="password-wrapper">
              <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                     id="password" name="password" placeholder="Enter password" required
                     aria-describedby="passwordHelp" minlength="8">
              <i class="password-toggle-icon fas fa-eye" id="togglePassword" onclick="togglePasswordVisibility()" role="button" tabindex="0" aria-label="Toggle password visibility"></i>
            </div>
            <label for="password">Password</label>
            <div id="passwordHelp" class="form-feedback <?php echo (!empty($password_err)) ? 'd-block' : ''; ?>">
              <?php echo $password_err; ?>
            </div>
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check remember-me">
              <input class="form-check-input" type="checkbox" id="remember" name="remember">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
          </div>
          
          <button class="w-100 btn btn-lg btn-login mb-3" type="submit" name="login" value="LOGIN">
            <i class="fas fa-sign-in-alt me-2"></i>Login
          </button>
          
          <p class="mt-3 text-center text-white">New to Iloilo City Info? <a href="sign-up.php" class="signup-link">Create an account</a></p>
        </form>
      </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-3">
      <div class="container">
        <p class="mb-0">&copy; 2023 Iloilo City Info App. All rights reserved.</p>
        <p class="mb-0 small mt-1">
          <a href="privacy.php" class="me-2">Privacy Policy</a>
          <a href="terms.php">Terms of Service</a>
        </p>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
      
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('email').focus();
        
        // Form validation
        const form = document.getElementById('loginForm');
        const email = document.getElementById('email');
        const passwordField = document.getElementById('password');
        const emailFeedback = document.getElementById('emailHelp');
        const passwordFeedback = document.getElementById('passwordHelp');
        
        form.addEventListener('submit', function(event) {
          let valid = true;
          
          // Email validation
          if (!email.value || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.classList.add('is-invalid');
            emailFeedback.style.display = 'block';
            valid = false;
          } else {
            email.classList.remove('is-invalid');
            emailFeedback.style.display = 'none';
          }
          
          // Password validation
          if (!passwordField.value || passwordField.value.length < 8) {
            passwordField.classList.add('is-invalid');
            passwordFeedback.style.display = 'block';
            valid = false;
          } else {
            passwordField.classList.remove('is-invalid');
            passwordFeedback.style.display = 'none';
          }
          
          if (!valid) {
            event.preventDefault();
            event.stopPropagation();
          }
        });
        
        // Real-time validation
        email.addEventListener('input', function() {
          if (this.value && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
            this.classList.remove('is-invalid');
            emailFeedback.style.display = 'none';
          }
        });
        
        passwordField.addEventListener('input', function() {
          if (this.value && this.value.length >= 8) {
            this.classList.remove('is-invalid');
            passwordFeedback.style.display = 'none';
          }
        });
        
        // Add scroll effect for navbar
        window.addEventListener('scroll', function() {
          if (window.scrollY > 50) {
            document.querySelector('.navbar').classList.add('scrolled');
          } else {
            document.querySelector('.navbar').classList.remove('scrolled');
          }
        });
      });

      // Enhanced Sidebar Functions
      function openSidebar() {
        const sidebar = document.getElementById("mySidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        sidebar.classList.add("open");
        overlay.classList.add("active");
        document.body.style.overflow = "hidden";
        
        // Add animation
        sidebar.style.animation = "slideInRight 0.3s ease-out";
      }

      function closeSidebar() {
        const sidebar = document.getElementById("mySidebar");
        const overlay = document.getElementById("sidebarOverlay");
        
        sidebar.classList.remove("open");
        overlay.classList.remove("active");
        document.body.style.overflow = "auto";
        
        // Reset animation
        sidebar.style.animation = "";
      }

      // Close sidebar when pressing Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          closeSidebar();
        }
      });

      // Close sidebar when clicking outside
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

      // Initialize popovers
      document.addEventListener('DOMContentLoaded', function () {
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
          return new bootstrap.Popover(popoverTriggerEl);
        });
      });
    </script>
  </body>
</html>