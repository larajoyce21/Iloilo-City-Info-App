<?php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_content.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

include 'conn.php';
$email = $password = '';
$email_err = $password_err = $login_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter your email address.';
    } else {
        $email = trim($_POST['email']);
    }
    
    if (empty(trim($_POST['pass']))) {
        $password_err = 'Please enter your password.';     
    } else {
        $password = trim($_POST['pass']);
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
                            
                            $_SESSION['user_id'] = $id;
                            $_SESSION['email'] = $email;
                            $_SESSION['first_name'] = $first_name;
                            $_SESSION['last_name'] = $last_name;
                            $_SESSION['role'] = $role;
                            $_SESSION['loggedin'] = true;
                            
                            if (isset($_POST['remember'])) {
                                setcookie('remember_user', $email, time() + (30 * 24 * 60 * 60), '/');
                            }
                            
                            if ($role === 'admin') {
                                header("Location: dashboard.php");
                            } else {
                                header("Location: index.php");
                            }
                            exit();
                        } else {
                            $login_err = 'Invalid email or password.';
                        }
                    }
                } else {
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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Learn about Iloilo City Info App - Your comprehensive guide to exploring Iloilo City">
    <meta name="keywords" content="Iloilo, tourism, guide, Philippines, travel, city info">
    <meta name="author" content="Iloilo City Info App Team">
    
    <title>About Us | Iloilo City Info App</title>

    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
        :root {
          --primary-color: #545454;
          --secondary-color: #6c757d;
          --transition: all 0.3s ease;
        }

        body {
          background: url('img/bg.png') no-repeat center center fixed;
          background-size: cover;
          padding-top: 90px; 
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


      /* Content */
      .hero-section {
        color: white;
        padding: 4rem 0;
        margin-bottom: 3rem;
      }

      .about-card {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
      }

      .about-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
      }

      .feature-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: var(--primary-color);
      }

      .mission-section {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        padding: 2rem;
        margin: 3rem 0;
      }

      /*  Gallery */
      .gallery-container {
        overflow-x: auto;
        white-space: nowrap;
        scrollbar-width: none;
        -ms-overflow-style: none;
        padding: 20px 0;
      }

      .gallery-container::-webkit-scrollbar {
        display: none;
      }

      .gallery-scroll {
        display: inline-flex;
        gap: 20px;
      }

      .gallery-item {
        flex: 0 0 auto;
        width: 350px;
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s ease;
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        background: white;
        display: inline-block;
        white-space: normal;
        vertical-align: top;
      }

      .gallery-item:hover {
        transform: scale(1.03);
      }

      .gallery-item img {
        width: 100%;
        height: 250px;
        object-fit: cover;
        transition: transform 0.5s ease;
      }

      .gallery-item:hover img {
        transform: scale(1.05);
      }

      .gallery-content {
        padding: 20px;
      }

      .gallery-content h5 {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
      }

      .gallery-content p {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 0;
      }

      /* Team  */
      .team-card {
        border: none;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
      }

      .team-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
      }

      .team-img {
        height: 250px;
        object-fit: cover;
      }

      /* Button  */
      .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
      }

      .btn-primary:hover {
        background-color: #424242;
        border-color: #424242;
      }

      .btn-outline-light {
        color: white;
        border-color: white;
      }

      .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
      }

      /* Login Form  */
      .login-section {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 2rem;
        margin: 3rem auto;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.15);
        animation: fadeIn 0.5s ease-out;
      }

      .login-title {
        color: white;
        margin-bottom: 1.5rem;
        font-weight: 600;
        text-align: center;
        font-size: 2rem;
        position: relative;
        padding-bottom: 10px;
      }

      .login-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background-color: #ffc107;
        border-radius: 3px;
      }

      /* Custom form field styling - no floating label for password */
      .form-group {
        margin-bottom: 1.25rem;
      }
      
      .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: white;
        font-size: 0.9rem;
      }
      
      .input-wrapper {
        position: relative;
        width: 100%;
      }
      
      .input-wrapper input {
        width: 100%;
        padding: 12px 45px 12px 15px;
        background-color: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s ease;
        color: #212529;
      }
      
      .input-wrapper input:focus {
        outline: none;
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.25);
        background-color: white;
      }
      
      .input-wrapper input.is-invalid {
        border-color: #dc3545;
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
        color: #4c6fc7;
      }
      
      .invalid-feedback-custom {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
      }

      .btn-login {
        background-color: #4c6fc7;
        color: white;
        border: none;
        padding: 12px;
        font-weight: 600;
        transition: var(--transition);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        font-size: 0.95rem;
        border-radius: 8px;
        width: 100%;
      }

      .btn-login:hover {
        background-color: #A52A2A;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }

      .remember-me {
        margin-top: 1rem;
      }

      .form-check-input:checked {
        background-color: #4c6fc7;
        border-color: #4c6fc7;
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
        color: #ffc107;
        text-decoration: underline;
      }

      .signup-link {
        color: #ffc107;
        text-decoration: none;
        font-weight: 600;
        transition: var(--transition);
      }

      .signup-link:hover {
        text-decoration: underline;
        color: #ffab00;
      }

      .alert-danger {
        background-color: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 20px;
      }

      /* Footer */
      footer {
        background-color: #545454;
        padding: 2rem 0;
        margin-top: auto;
      }

      .footer-logo {
        width: 200px;
        height: auto;
      }

      .footer-link {
        color: #6c757d;
        transition: color 0.3s ease;
      }

      .footer-link:hover {
        color: var(--primary-color);
        text-decoration: none;
      }

      .social-icon {
        font-size: 1.5rem;
        margin-right: 1rem;
        color: var(--primary-color);
        transition: color 0.3s ease;
      }

      .social-icon:hover {
        color: var(--secondary-color);
      }

      /* Adjustments */
      @media (max-width: 992px) {
        .logo {
          width: 200px;
          height: 50px;
        }
      }

      @media (max-width: 768px) {
        .navbar .navbar-nav {
          display: none;
        }
        
        .navbar-toggler {
          display: block;
        }
        
        .hero-section {
          padding: 2rem 0;
        }
        
        .footer-logo {
          width: 150px;
        }
        
        body {
          padding-top: 70px;
        }
        
        .navbar {
          height: 70px;
        }
        
        .gallery-item {
          width: 300px;
        }
        
        .login-section {
          padding: 1.5rem;
          max-width: 90%;
        }
      }

      @media (max-width: 576px) {
        .logo {
          width: 180px;
          height: 45px;
        }
        
        .gallery-item {
          width: 280px;
        }
        
        .hero-section h1 {
          font-size: 2rem;
        }
        
        .login-title {
          font-size: 1.6rem;
        }
      }

      /* Animation */
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .animate-fade {
        animation: fadeIn 0.8s ease forwards;
      }

      .delay-1 { animation-delay: 0.2s; }
      .delay-2 { animation-delay: 0.4s; }
      .delay-3 { animation-delay: 0.6s; }

      html {
        scroll-behavior: smooth;
      }
      
      .page-title {
        font-family: 'Playfair Display', serif;
        color: white;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
        margin-bottom: 5rem;
        position: relative;
        padding-bottom: 0.5rem;
      }

      .page-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(to right, transparent, #fff, transparent);
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
              <a class="nav-link active" href="about.php">ℹ️ About Us</a>
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
      <a href="about.php" class="active">ℹ️  About Us</a>

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


    <!-- Hero Section -->
    <section class="hero-section text-center animate-fade">
      <div class="container">
        <h1 class="page-title display-4 fw-bold mb-3">About Iloilo City Info App</h1>
        <p class="lead mb-4">Your ultimate digital guide to exploring the beautiful City of Love</p>
        <a href="#our-mission" class="btn btn-primary btn-lg px-4 me-2">Our Mission</a>
        <a href="#features" class="btn btn-outline-light btn-lg px-4">Features</a>
      </div>
    </section>
    
    <!-- About Section -->
    <section class="container mb-5 animate-fade delay-1">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="about-card p-4 h-100">
            <h2 class="mb-3">Discover Iloilo City</h2>
            <p class="lead">Welcome to your comprehensive guide to everything Iloilo City has to offer!</p>
            <p>Whether you're a first-time visitor, a returning traveler, or a local resident looking to rediscover your city, our app provides all the information you need to make the most of your Iloilo experience.</p>
            <p>From historical landmarks to hidden culinary gems, from transportation routes to upcoming events - we've got you covered with accurate, up-to-date information presented in an easy-to-use format.</p>
          </div>
        </div>
        
        <div class="col-lg-6">
          <div class="about-card p-4 h-100">
            <h2 class="mb-3">Why Choose Our App?</h2>
            <div class="d-flex align-items-start mb-3">
              <i class="fas fa-check-circle text-success me-3 mt-1"></i>
              <div>
                <h5 class="mb-1">Comprehensive Information</h5>
                <p class="mb-0">All the details you need in one place, carefully curated and regularly updated.</p>
              </div>
            </div>
            <div class="d-flex align-items-start mb-3">
              <i class="fas fa-check-circle text-success me-3 mt-1"></i>
              <div>
                <h5 class="mb-1">User-Friendly Design</h5>
                <p class="mb-0">Intuitive interface that makes finding information quick and easy.</p>
              </div>
            </div>
            <div class="d-flex align-items-start">
              <i class="fas fa-check-circle text-success me-3 mt-1"></i>
              <div>
                <h5 class="mb-1">Local Expertise</h5>
                <p class="mb-0">Created by Ilonggos who know and love the city inside out.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Mission Section -->
    <section id="our-mission" class="container animate-fade delay-2">
      <div class="mission-section">
        <div class="row align-items-center">
          <div class="col-lg-6 mb-4 mb-lg-0">
            <img src="img/cover15.jpg" alt="Iloilo City" class="img-fluid rounded shadow">
          </div>
          <div class="col-lg-6">
            <h2 class="mb-3">Our Mission</h2>
            <p class="lead mb-4">To promote Iloilo City's rich culture, heritage, and modern attractions through accessible digital technology.</p>
            <p>We believe that everyone should have the opportunity to experience the best of Iloilo City, whether they're visiting for the first time or have lived here their whole lives. Our mission is to bridge the information gap and make exploring Iloilo easier, more enjoyable, and more rewarding.</p>
            <p>By providing comprehensive, accurate, and easy-to-access information, we aim to:</p>
            <ul>
              <li>Boost local tourism and support small businesses</li>
              <li>Preserve and promote Iloilo's cultural heritage</li>
              <li>Improve accessibility to city services and information</li>
              <li>Create a sense of community among residents and visitors</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="container my-5 animate-fade delay-3">
      <h2 class="text-center text-white mb-5">Key Features</h2>
      
      <div class="row g-4">
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-map-marked-alt feature-icon"></i>
            <h3>Interactive Maps</h3>
            <p>Navigate Iloilo City with ease using our detailed maps with points of interest, transportation routes, and walking directions.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-utensils feature-icon"></i>
            <h3>Dining Guide</h3>
            <p>Discover the best places to eat, from famous La Paz Batchoy spots to hidden café gems and fine dining establishments.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-landmark feature-icon"></i>
            <h3>Cultural Heritage</h3>
            <p>Explore Iloilo's rich history through our guides to historical landmarks, museums, and cultural sites.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-bus feature-icon"></i>
            <h3>Transportation</h3>
            <p>Get around easily with our comprehensive jeepney and bus route information, terminal locations, and schedules.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-calendar-alt feature-icon"></i>
            <h3>Events Calendar</h3>
            <p>Stay updated on upcoming festivals, concerts, exhibits, and other events happening around the city.</p>
          </div>
        </div>
        
        <div class="col-md-4">
          <div class="about-card p-4 text-center h-100">
            <i class="fas fa-building feature-icon"></i>
            <h3>Government Services</h3>
            <p>Quick access to government offices, services, and contact information when you need it.</p>
          </div>
        </div>
      </div>
    </section>
    

    <!-- Gallery Section -->
    <section class="container my-5">
      <h2 class="text-center text-white mb-4">Explore Iloilo Through Our Eyes</h2>
      <p class="text-center text-white mb-5">
        A glimpse of what awaits you in the beautiful City of Love
      </p>
      
      <div class="gallery-container">
        <div class="gallery-scroll">
          <!-- Iloilo River Esplanade -->
          <div class="gallery-item">
            <a href="new_tourist_spot1.php" class="text-decoration-none text-white">
              <img src="img/place2.webp" alt="Iloilo Esplanade" class="img-fluid">
              <div class="gallery-content">
                <h5>Iloilo River Esplanade</h5>
                <p>One of the longest linear parks in the country, perfect for evening strolls and morning jogs with scenic river views.</p>
              </div>
            </a>
          </div>

          <!-- Calle Real -->
          <div class="gallery-item">
            <a href="historical13.php" class="text-decoration-none text-white">
              <img src="img/place.jpg" alt="Calle Real" class="img-fluid">
              <div class="gallery-content">
                <h5>Calle Real</h5>
                <p>Iloilo's historic downtown district featuring well-preserved heritage buildings from the American colonial period.</p>
              </div>
            </a>
          </div>

          <!-- Molo Church -->
          <div class="gallery-item">
            <a href="historical6.php" class="text-decoration-none text-white">
              <img src="img/molochurch.jpg" alt="Molo Church" class="img-fluid">
              <div class="gallery-content">
                <h5>Molo Church</h5>
                <p>Known as the feminist church of Iloilo, this Gothic-Renaissance style church is dedicated to female saints.</p>
              </div>
            </a>
          </div>

          <!-- Dinagyang Festival -->
          <div class="gallery-item">
            <a href="festival1.php" class="text-decoration-none text-white">
              <img src="img/dinagyang.jpg" alt="Dinagyang Festival" class="img-fluid">
              <div class="gallery-content">
                <h5>Dinagyang Festival</h5>
                <p>Colorful cultural celebration every January featuring vibrant costumes, street dancing, and religious processions.</p>
              </div>
            </a>
          </div>

          <!-- La Paz Batchoy -->
          <div class="gallery-item">
            <a href="food17.php" class="text-decoration-none text-white">
              <img src="img/batchoy2.jpg" alt="La Paz Batchoy" class="img-fluid">
              <div class="gallery-content">
                <h5>La Paz Batchoy</h5>
                <p>Iloilo's famous noodle soup dish made with pork organs, crushed pork cracklings, and rich bone broth.</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Login Section - Password label removed when typing -->
    <section class="container login-section">
      <h1 class="login-title">Welcome Back</h1>
      
      <?php 
      if (!empty($login_err)) {
          echo '<div class="alert alert-danger">' . $login_err . '</div>';
      }
      ?>
      
      <form id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" autocomplete="on" novalidate>
        <!-- Email Field -->
        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrapper">
            <input type="email" id="email" name="email" placeholder="Enter your email address"
                   value="<?php echo htmlspecialchars($email); ?>"
                   class="<?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                   required>
          </div>
          <?php if (!empty($email_err)): ?>
            <div class="invalid-feedback-custom"><?php echo $email_err; ?></div>
          <?php endif; ?>
        </div>
        
        <!-- Password Field - Label removed when typing -->
        <div class="form-group">
          <label for="password" id="passwordLabel">Password</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="pass" placeholder="Enter your password"
                   class="<?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                   minlength="8" required
                   onfocus="hidePasswordLabel()" onblur="showPasswordLabelIfEmpty()" oninput="handlePasswordInput()">
            <i class="password-toggle-icon fas fa-eye" id="togglePassword" onclick="togglePasswordVisibility()"></i>
          </div>
          <?php if (!empty($password_err)): ?>
            <div class="invalid-feedback-custom"><?php echo $password_err; ?></div>
          <?php endif; ?>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check remember-me">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>
          <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
        </div>
        
        <button class="btn btn-login mb-3" type="submit" name="login" value="LOGIN">
          <i class="fas fa-sign-in-alt me-2"></i>Login
        </button>
        
        <p class="mt-3 text-center text-white">New to Iloilo City Info? <a href="sign-up.php" class="signup-link">Create an account</a></p>
      </form>
    </section>
    
    <!-- Call to Action -->
    <section class="container my-5 text-center py-5">
      <h2 class="mb-4 text-white">Ready to Explore Iloilo City?</h2>
      <p class="lead mb-4 text-white">Download our app or start exploring now to discover everything Iloilo has to offer</p>
      <a href="index.php" class="btn btn-primary btn-lg px-4 me-3">Start Exploring</a>
      <a href="#" class="btn btn-outline-light btn-lg px-4">Download App</a>
    </section>
    
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
            <p class="small text-white mb-0">&copy; 2025 Iloilo City Tourism Office. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Sidebar Functions
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

    // Close sidebar with Escape key
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
    
    // Function to hide password label when user starts typing
    function hidePasswordLabel() {
      const passwordLabel = document.getElementById('passwordLabel');
      if (passwordLabel) {
        passwordLabel.style.display = 'none';
      }
    }
    
    // Function to show password label if input is empty
    function showPasswordLabelIfEmpty() {
      const password = document.getElementById('password');
      const passwordLabel = document.getElementById('passwordLabel');
      if (passwordLabel && (!password.value || password.value.trim() === '')) {
        passwordLabel.style.display = 'block';
      }
    }
    
    // Function to handle password input - hide label when typing
    function handlePasswordInput() {
      const passwordLabel = document.getElementById('passwordLabel');
      if (passwordLabel) {
        passwordLabel.style.display = 'none';
      }
    }
    
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('loginForm');
      const email = document.getElementById('email');
      const password = document.getElementById('password');
      
      form.addEventListener('submit', function(event) {
        let valid = true;
        
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
        
        if (!valid) {
          event.preventDefault();
          event.stopPropagation();
        }
      });
      
      // Real-time validation
      email.addEventListener('input', function() {
        if (this.value && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
          this.classList.remove('is-invalid');
        }
      });
      
      password.addEventListener('input', function() {
        if (this.value && this.value.length >= 8) {
          this.classList.remove('is-invalid');
        }
        // Hide password label when typing
        const passwordLabel = document.getElementById('passwordLabel');
        if (passwordLabel) {
          passwordLabel.style.display = 'none';
        }
      });
      
      // Show password label if empty when clicking outside
      password.addEventListener('blur', function() {
        const passwordLabel = document.getElementById('passwordLabel');
        if (passwordLabel && (!this.value || this.value.trim() === '')) {
          passwordLabel.style.display = 'block';
        }
      });
    });
    </script>
  </body>
</html>