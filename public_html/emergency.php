<?php
include 'conn.php';

// Define all 7 categories
$categories = ['Emergency', 'Tourist', 'Hospitals', 'Police Stations', 'Fire Stations', 'Airlines', 'Shipping lines'];
$contactsByCategory = [];

foreach ($categories as $category) {
    $result = $conn->query("SELECT * FROM emergency_contacts WHERE category='$category'");
    $contactsByCategory[$category] = [];
    while($row = $result->fetch_assoc()){
        $contactsByCategory[$category][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Hotlines - Iloilo City</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e30613; 
            --secondary-color: #004a8d; 
            --accent-color: #f8b400; 
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --gray-light: #e9ecef;
            --transition: all 0.3s ease;
        }
        
        body {
            background: url('img/bg.png') no-repeat center center fixed;
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
        
        /* Navbar */
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

        /* Hero Section */
        .hero-section {
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: black;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .hero-section h1 {
            font-size: 2rem;
            margin-bottom: 0.8rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .hero-section p {
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        /* Main Content */
        .content-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 10px;
            color: black;
            font-size: 1.5rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 2px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        /* Cards */
        .info-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            overflow: hidden;
            border-top: 3px solid var(--primary-color);
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .info-card .card-header {
            background-color: var(--secondary-color);
            color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .info-card .card-body {
            padding: 18px;
        }
        
        /* Emergency Cards */
        .emergency-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--secondary-color);
            transition: var(--transition);
        }
        
        .emergency-item.emergency {
            border-left-color: var(--primary-color);
            background: #fff5f5;
        }
        
        .emergency-item:hover {
            background: #f0f0f0;
            transform: translateX(3px);
        }
        
        .emergency-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .emergency-number {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 3px 0;
            color: #555;
            font-size: 0.9rem;
        }
        
        .emergency-number i {
            width: 16px;
            font-size: 0.85rem;
            color: var(--secondary-color);
        }
        
        .call-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
        }
        
        .call-link:hover {
            color: #218838;
            text-decoration: underline;
        }
        
        /* Mobile-specific layout */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }
            
            .navbar {
                height: 85px;
                padding: 6px 0;
            }
            
            .logo {
                width: 170px;
                height: 50px;
            }
            
            .container-fluid {
                padding-left: 12px;
                padding-right: 12px;
            }
            
            .hero-section {
                padding: 50px 0;
                margin-bottom: 25px;
            }
            
            .hero-section h1 {
                font-size: 1.6rem;
                margin-bottom: 0.6rem;
            }
            
            .hero-section p {
                font-size: 0.9rem;
            }
            
            .section-title {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }
            
            .content-section {
                margin-bottom: 30px;
            }
            
            .footer {
                padding: 30px 0 10px;
            }
            
            /* Mobile layout changes - smaller cards side by side */
            .mobile-side-by-side .row {
                display: flex;
                flex-wrap: wrap;
                margin-left: -5px;
                margin-right: -5px;
            }
            
            .mobile-side-by-side .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
                padding-left: 5px;
                padding-right: 5px;
            }
            
            /* Make mobile cards more compact */
            .mobile-side-by-side .info-card {
                margin-bottom: 10px;
            }
            
            .mobile-side-by-side .info-card .card-header {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .mobile-side-by-side .info-card .card-body {
                padding: 12px;
            }
            
            .emergency-item {
                padding: 10px 12px;
                margin-bottom: 8px;
            }
            
            .emergency-name {
                font-size: 0.9rem;
                margin-bottom: 3px;
            }
            
            .emergency-number {
                font-size: 0.85rem;
                padding: 2px 0;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                padding: 40px 0;
            }
            
            .hero-section h1 {
                font-size: 1.4rem;
            }
            
            .hero-section p {
                font-size: 0.85rem;
            }
            
            .section-title {
                font-size: 1.2rem;
            }
            
            .info-card .card-header {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .info-card .card-body {
                padding: 12px;
            }
            
            .footer-logo {
                width: 120px;
            }
            
            /* Even more compact for very small screens */
            .mobile-side-by-side .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
            
            .mobile-side-by-side .info-card .card-header {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            
            .mobile-side-by-side .info-card .card-body {
                padding: 10px;
            }
            
            .emergency-item {
                padding: 8px 10px;
            }
            
            .emergency-name {
                font-size: 0.85rem;
            }
            
            .emergency-number {
                font-size: 0.8rem;
            }
            
            .call-link {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 400px) {
            .hero-section h1 {
                font-size: 1.2rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .sidebar.open {
                width: 250px;
            }
            
            /* Stack cards vertically on very small screens */
            .mobile-side-by-side .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Icon Cards */
        .icon-card {
            border: none;
            border-radius: 10px;
            background: white;
            transition: var(--transition);
            text-align: center;
            padding: 15px 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .icon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .icon-card i {
            font-size: 28px;
            color:  rgba(19, 18, 18, 0.91);;
            margin-bottom: 8px;
        }
        
        .icon-card .card-title {
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0;
            color: black;
        }
        
        /* Emergency Banner */
        .emergency-banner {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .emergency-banner h4 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }
        
        .emergency-banner .emergency-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .emergency-banner .call-now {
            background: white;
            color: var(--primary-color);
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
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
        
        .page-title {
            font-family: 'Playfair Display', serif;
            color: black;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: 2rem;
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
        
        .category-icon {
            width: 24px;
            text-align: center;
            margin-right: 8px;
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
<br>
    <h1 class="page-title text-white text-center">Emergency Hotlines in Iloilo City</h1>

    <!-- ICON SHORTCUTS -->
    <div class="container mb-4">
        <div class="row justify-content-center g-2">
            <div class="col-4 col-md-3 col-lg-2">
                <a href="pnps.php" class="text-decoration-none">
                    <div class="icon-card">
                        <i class="fas fa-shield-alt"></i>
                        <div class="card-title">Police</div>
                    </div>
                </a>
            </div>
            <div class="col-4 col-md-3 col-lg-2">
                <a href="fires.php" class="text-decoration-none">
                    <div class="icon-card">
                        <i class="fas fa-fire-extinguisher"></i>
                        <div class="card-title">Fire</div>
                    </div>
                </a>
            </div>
            <div class="col-4 col-md-3 col-lg-2">
                <a href="pcg.php" class="text-decoration-none">
                    <div class="icon-card">
                        <i class="fas fa-anchor"></i>
                        <div class="card-title">Coast Guard</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="container mb-4">
        <section class="content-section">
            <h2 class="section-title text-white">Emergency Contacts</h2>
            
            <!-- Desktop Layout - Shows all 7 cards -->
            <div class="row d-none d-md-flex">
                <?php 
                // Define icons for each category
                $categoryIcons = [
                    'Emergency' => 'fa-exclamation-triangle',
                    'Hospitals' => 'fa-hospital',
                    'Tourist' => 'fa-map-marker-alt',
                    'Police Stations' => 'fa-shield-alt',
                    'Fire Stations' => 'fa-fire-extinguisher',
                    'Airlines' => 'fa-plane',
                    'Shipping lines' => 'fa-ship'
                ];
                
                foreach($categories as $category): 
                    $icon = $categoryIcons[$category] ?? 'fa-phone';
                    $emergencyClass = ($category == "Emergency") ? "emergency" : "";
                ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card info-card h-100">
                            <div class="card-header">
                                <i class="fas <?php echo $icon ?> me-2"></i>
                                <?php echo $category ?>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($contactsByCategory[$category])): ?>
                                    <?php foreach($contactsByCategory[$category] as $contact): ?>
                                        <div class="emergency-item <?php echo $emergencyClass; ?>">
                                            <div class="emergency-name">
                                                <?php echo $contact['name']; ?>
                                            </div>
                                            
                                            <?php if($contact['number1']): ?>
                                                <div class="emergency-number">
                                                    <i class="fas fa-phone-alt"></i>
                                                    <?php echo $contact['number1']; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if($contact['number2']): ?>
                                                <div class="emergency-number">
                                                    <i class="fas fa-mobile-alt"></i>
                                                    <?php echo $contact['number2']; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($contact['number3']) && $contact['number3']): ?>
                                                <div class="emergency-number">
                                                    <i class="fas fa-phone"></i>
                                                    <?php echo $contact['number3']; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info py-2 small mb-0">
                                        No contacts available
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Mobile Layout - Shows all 7 cards side by side -->
            <div class="d-md-none mobile-side-by-side">
                <div class="row">
                    <?php foreach($categories as $category): 
                        $icon = $categoryIcons[$category] ?? 'fa-phone';
                        $emergencyClass = ($category == "Emergency") ? "emergency" : "";
                    ?>
                        <div class="col-6 mb-3">
                            <div class="card info-card h-100">
                                <div class="card-header">
                                    <i class="fas <?php echo $icon ?> me-2"></i>
                                    <?php echo $category ?>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($contactsByCategory[$category])): ?>
                                        <?php foreach($contactsByCategory[$category] as $contact): ?>
                                            <div class="emergency-item <?php echo $emergencyClass; ?>">
                                                <div class="emergency-name">
                                                    <?php echo $contact['name']; ?>
                                                </div>
                                                
                                                <?php if($contact['number1']): ?>
                                                    <div class="emergency-number">
                                                        <i class="fas fa-phone-alt"></i>
                                                        <?php echo $contact['number1']; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if($contact['number2']): ?>
                                                    <div class="emergency-number">
                                                        <i class="fas fa-mobile-alt"></i>
                                                        <?php echo $contact['number2']; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if(isset($contact['number3']) && $contact['number3']): ?>
                                                    <div class="emergency-number">
                                                        <i class="fas fa-phone"></i>
                                                        <?php echo $contact['number3']; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info py-2 small mb-0">
                                            No contacts
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
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
    </script>
</body>
</html>
<?php $conn->close(); ?>