<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Transportation Information in Iloilo City">
    <meta name="author" content="Iloilo City Info App">
    <title>Transportations - Iloilo City</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

    <style>
        :root {
            --primary-color: #508acb;
            --secondary-color: #545454;
            --light-color: #ffffff;
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

        /* Navbar */
        .navbar {
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
            color: var(--primary-color);
            outline: none;
        }

        .navbar a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 8px;
            left: 15px;
            background-color: var(--primary-color);
            transition: var(--transition);
        }

        .navbar a:hover::after,
        .navbar a:focus::after {
            width: calc(100% - 30px);
        }

        .navbar-toggler {
            background: transparent !important;
            box-shadow: none !important;
            border: none;
            padding: 4px 8px;
        }

        .navbar-toggler:focus {
            box-shadow: none !important;
        }

        .navbar-toggler-icon {
            background-image: none;
            position: relative;
            width: 20px;
            height: 20px;
            transition: all 0.3s ease;
            background-color: var(--primary-color);
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
            background-color: rgba(0, 0, 0, 0.05);
            border-left: 3px solid var(--primary-color);
            padding-left: 25px;
            outline: none;
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

        /* Page Header */
        .page-header {
            padding: 1.5rem 0 1rem 0; /* Reduced padding */
            position: relative;
            overflow: hidden;
        }

        .page-header-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .page-title-section {
            flex: 1;
            min-width: 300px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: 0.25rem;
            position: relative;
            padding-bottom: 0;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            line-height: 1.1;
        }

        .page-subtitle {
            font-family: 'Playfair Display', serif;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            margin-bottom: 0.5rem;
            font-weight: 400;
        }

        .page-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: clamp(0.9rem, 2vw, 1.2rem);
            max-width: 600px;
            margin-top: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), #fff);
        }

        .header-image-section {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-left: 2rem;
        }

        .header-image {
            width: 300px;
            height: 250px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
            border: 3px solid white;
            cursor: pointer;
        }

        .header-image:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        /* Mobile adjustments - Image ALWAYS on right side */
        @media (max-width: 992px) {
            .page-header {
                padding: 1.2rem 0 0.8rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: center;
            }
            
            .header-image {
                width: 300px;
                height: 250px;
            }
            
            .page-title-section {
                padding-right: 1rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1rem 0 0.5rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
            
            .header-image {
                width: 150px;
                height: 113px; /* Maintain 3:2 aspect ratio */
            }
            
            .page-title {
                font-size: 1.6rem;
                margin-bottom: 0.1rem;
            }
            
            .page-subtitle {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }
            
            .page-description {
                font-size: 0.9rem;
                margin-top: 0.3rem;
            }
            
            .header-image-section {
                padding-left: 1rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 0.8rem 0 0.3rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: center;
            }
            
            .header-image {
                width: 190px;
                height: 150px; /* Maintain 4:3 aspect ratio for mobile */
            }
            
            .page-title {
                font-size: 1.4rem;
                line-height: 1;
            }
            
            .page-subtitle {
                font-size: 1.1rem;
                line-height: 1.1;
            }
            
            .page-description {
                font-size: 0.8rem;
                line-height: 1.3;
            }
            
            .page-title-section {
                min-width: 200px;
            }
        }

        @media (max-width: 400px) {
            .page-header {
                padding: 0.6rem 0 0.2rem 0;
            }
            
            .header-image {
                width: 100px;
                height: 75px; /* Maintain 4:3 aspect ratio */
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
            
            .page-description {
                font-size: 0.75rem;
            }
        }

        /* Main Content */
        main {
            margin-top: 0 !important; /* Remove default margin */
            padding-top: 0; /* Remove padding */
        }

        .container.mt-4 {
            margin-top: 0.5rem !important; /* Reduced from default */
            padding-top: 0;
        }

        /* Icon Cards - UPDATED FOR MOBILE */
        .icon-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            cursor: pointer;
            background-color: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none !important;
            color: inherit;
            height: 130px; 
            margin: 0 auto;
        }

        .icon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
        }

        .icon-card .card-body {
            padding: 15px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
            width: 100%;
        }

        .icon-card i {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .icon-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin: 0;
            line-height: 1.2;
        }

        /* Mobile adjustments for cards */
        @media (max-width: 768px) {
            .icon-card {
                height: 110px; /* Smaller height for tablets */
            }
            
            .icon-card i {
                font-size: 1.8rem;
                margin-bottom: 8px;
            }
            
            .icon-card .card-title {
                font-size: 0.85rem;
            }
            
            .icon-card .card-body {
                padding: 12px;
            }
            
            .container.mt-4 {
                margin-top: 0.3rem !important;
            }
        }

        @media (max-width: 576px) {
            .icon-card {
                height: 100px; /* Even smaller for mobile */
            }
            
            .icon-card i {
                font-size: 1.6rem;
                margin-bottom: 6px;
            }
            
            .icon-card .card-title {
                font-size: 0.8rem;
            }
            
            .icon-card .card-body {
                padding: 10px;
            }
            
            .container.mt-4 {
                margin-top: 0.2rem !important;
            }
            
            .row.justify-content-center {
                --bs-gutter-x: 0.4rem;
                --bs-gutter-y: 0.4rem;
            }
        }

        @media (max-width: 400px) {
            .icon-card {
                height: 90px; /* Smallest for very small screens */
            }
            
            .icon-card i {
                font-size: 1.4rem;
                margin-bottom: 5px;
            }
            
            .icon-card .card-title {
                font-size: 0.75rem;
            }
            
            .icon-card .card-body {
                padding: 8px;
            }
            
            .container.mt-4 {
                margin-top: 0.1rem !important;
            }
        }

        /* Adjust grid columns for better mobile layout */
        @media (max-width: 768px) {
            .col-4 {
                flex: 0 0 auto;
                width: 33.333333%; /* 3 cards per row on tablets */
            }
        }

        @media (max-width: 576px) {
            .col-4 {
                flex: 0 0 auto;
                width: 50%; /* 2 cards per row on mobile */
            }
            
            .row.justify-content-center {
                --bs-gutter-x: 0.5rem;
                --bs-gutter-y: 0.5rem;
            }
        }

        @media (max-width: 400px) {
            .col-4 {
                flex: 0 0 auto;
                width: 50%; /* Keep 2 cards per row on very small screens */
            }
            
            .icon-card {
                height: 85px;
            }
            
            .icon-card i {
                font-size: 1.3rem;
            }
            
            .icon-card .card-title {
                font-size: 0.7rem;
            }
        }

        /* Footer */
        .footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 60px 0 20px;
            margin-top: auto;
        }

        .footer-logo {
            width: 180px;
            margin-bottom: 20px;
        }

        .footer h5,
        .footer h6 {
            font-family: 'Playfair Display', serif;
            color: var(--light-color);
            margin-bottom: 20px;
            position: relative;
        }

        .footer h5::after,
        .footer h6::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .footer .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 5px 0;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .footer .nav-link:hover,
        .footer .nav-link:focus {
            color: white;
            padding-left: 5px;
        }

        .social-icons .btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            transition: var(--transition);
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .social-icons .btn:hover,
        .social-icons .btn:focus {
            transform: translateY(-3px);
            background: var(--primary-color);
        }

        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 40px;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
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
<br>
    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <div class="page-header-container">
                <div class="page-title-section">
                    <h1 class="page-title">Transportation Information</h1>
                    <h2 class="page-subtitle">Iloilo City</h2>
                    <p class="page-description">Explore various transportation options available in Iloilo City - from traditional jeepneys to modern buses and ferry services</p>
                </div>
                <div class="header-image-section">
                    <a href="transportations.php" aria-label="View transportation information">
                        <img src="img/ICROUTES.jpg" alt="Iloilo City Transportation" class="header-image">
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow-1">
        <div class="container mt-4">
            <div class="row justify-content-center">
                <!-- Jeepneys -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="jeeps.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fa fa-shuttle-van fa-2x"></i>
                                <h6 class="card-title mt-2">Jeepneys</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Modernized Bus -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="bus.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fa fa-bus-alt fa-2x"></i>
                                <h6 class="card-title mt-2">Modernized Bus</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Terminals -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="terminals.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fa fa-map-marker-alt fa-2x"></i>
                                <h6 class="card-title mt-2">Terminals</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Travel Agencies -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="travel_agencies.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fas fa-plane-departure fa-2x"></i>
                                <h6 class="card-title mt-2">Travel and Tours Agencies</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Travel Ticketing -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="travel_ticketings.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                                <h6 class="card-title mt-2">Travel Ticketing</h6>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Shipping & Ferry Services -->
                <div class="col-4 col-lg-3 mb-3">
                    <a href="ferries.php" class="text-decoration-none">
                        <div class="card icon-card">
                            <div class="card-body text-center">
                                <i class="fa fa-ship fa-2x"></i>
                                <h6 class="card-title mt-2">Shipping & Ferry</h6>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer mt-5 py-4 text-white">
        <div class="container">
            <div class="row row-cols-3 g-3 text-center text-sm-start">
                <div class="col">
                    <h3 class="text-uppercase mb-2 fw-semibold small">About</h3>
                    <img src="img/logo4.png" alt="Iloilo City Logo" class="img-fluid mb-2" style="max-width: 100px;">
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Sidebar functions
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

        // Close sidebar on ESC key
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
                !toggleButton.contains(event.target) &&
                event.target !== overlay) {
                closeSidebar();
            }
        });

        // Image fallback for header image
        document.addEventListener('DOMContentLoaded', function() {
            const headerImage = document.querySelector('.header-image');
            if (headerImage) {
                headerImage.addEventListener('error', function() {
                    this.src = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
                });
            }
        });
    </script>
</body>
</html>