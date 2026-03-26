<?php
session_start();

$db = new mysqli('localhost', 'iloincgi_iloilocityinfoapp', 'iloilocityinfoapp', 'iloincgi_app');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

function getServiceDocuments($db, $service_id) {
    $service_id = (int)$service_id;
    $documents = [];
    $result = $db->query("SELECT * FROM service_documents WHERE service_id = $service_id ORDER BY document_name");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        $result->free();
    }
    return $documents;
}

// Get services data
$services_data = [];
$result = $db->query("SELECT * FROM services ORDER BY title");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $service_documents = getServiceDocuments($db, $row['id']);
        $services_data[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'services_offered' => $row['services_offered'],
            'location' => $row['location'],
            'contact' => $row['contact'],
            'hours' => $row['hours'],
            'requirements' => $row['requirements'],
            'documents' => $service_documents
        ];
    }
    $result->free();
}

// Get howto guides data
$howto_guides = [];
$howto_result = $db->query("SELECT * FROM howto_guides ORDER BY created_at ASC");
if ($howto_result) {
    while ($row = $howto_result->fetch_assoc()) {
        $howto_guides[] = $row;
    }
    $howto_result->free();
}

$service_icons = [
    'health' => ['icon' => 'fa-hospital', 'color' => '#ef476f'],
    'education' => ['icon' => 'fa-graduation-cap', 'color' => '#7209b7'],
    'transport' => ['icon' => 'fa-bus', 'color' => '#ff9e00'],
    'business' => ['icon' => 'fa-briefcase', 'color' => '#4361ee'],
    'emergency' => ['icon' => 'fa-ambulance', 'color' => '#d00000'],
    'water' => ['icon' => 'fa-tint', 'color' => '#3a86ff'],
    'electric' => ['icon' => 'fa-bolt', 'color' => '#ffbe0b'],
    'waste' => ['icon' => 'fa-trash', 'color' => '#6a4c93'],
    'housing' => ['icon' => 'fa-home', 'color' => '#f8961e'],
    'social' => ['icon' => 'fa-hands-helping', 'color' => '#4895ef'],
    'government' => ['icon' => 'fa-landmark', 'color' => '#3a86ff']
];
$default_icon = ['icon' => 'fa-building', 'color' => '#6c757d'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <title>Iloilo City Services</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <style>
        :root {
            --primary: #545454;
            --primary-dark: #545454;
            --secondary: #f8fafc;
            --accent: #f59e0b;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --emergency: #dc2626;
            --transition: all 0.3s ease;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding-top: 70px;
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

        .main-container {
            padding: 20px;
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
        }
        .services-container { 
            background-color: rgba(255, 255, 255, 0.9); 
            border-radius: 10px; 
            padding: 20px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
        }
        .section-title { 
            color: #545454; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #e2e8f0; 
            font-weight: 600; 
        }
        .search-box {
            width: 100%; 
            padding: 10px 15px 10px 40px; 
            border: 1px solid #e2e8f0; 
            border-radius: 6px; font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            margin-bottom: 20px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%232563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat; 
            background-position: 15px center; 
            background-size: 16px;
        }
        .service-card { 
            background-color: white; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            padding: 15px; 
            margin-bottom: 15px; 
            transition: all 0.3s ease; 
            border-left: 4px solid #545454; 
        }
        .service-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 15px rgba(0,0,0,0.1); 
        }
        .service-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .card-title { 
            font-size: 20px; 
            font-weight: 600; 
            margin-bottom: 10px; 
            color: #1e293b; 
            display: flex; 
            align-items: center; 
        }
        .card-title i { 
            margin-right: 10px; 
            font-size: 20px; 
        }
        .card-description {
             color: #64748b; 
             margin-bottom: 15px; 
             font-size: 14px; 
            }
        .service-content { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 20px; 
            font-size: 15px;
        }
        .services-column, .details-column { 
            flex: 1; 
            min-width: 280px; 
        }
        .services-list { 
            padding-left: 20px; 
            margin: 0; 
            list-style-type: disc; 
        }
        .services-list li { 
            margin-bottom: 8px; 
            font-size: 12px; 
        }
        .detail-item { 
            margin-bottom: 10px; 
            font-size: 16px; 
        }
        .detail-item strong { 
            display: block; 
            margin-bottom: 5px; 
            color: #1e293b; 
        }
        .detail-item i { 
            margin-right: 8px; 
            width: 18px; 
            text-align: center; 
            color: #545454; 
        }
        .requirements-section, .documents-section { 
            margin-top: 15px; 
            padding-top: 15px; 
            border-top: 1px solid #e2e8f0; 
            font-size: 14px; width: 100%; 
        }
        .section-subtitle { 
            font-size: 16px; 
            font-weight: 600; 
            margin-bottom: 10px; 
            color: #1e293b; 
            display: flex; 
            align-items: center; 
        }
        .section-subtitle i { margin-right: 8px; }
        .documents-list { list-style: none; padding-left: 0; }
        .document-item { margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 8px; border-radius: 4px; }
        .document-link { display: flex; align-items: center; color: #545454; text-decoration: none; }
        .document-actions { display: flex; gap: 5px; }
        .document-btn { padding: 5px 10px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-view { background-color: #3a86ff; color: white; }
        .btn-download { background-color: #4cc9f0; color: white; }
        .no-results { text-align: center; padding: 20px; color: #64748b; }
        .card-toggle {
            background: none;
            border: none;
            font-size: 18px;
            color: #64748b;
            cursor: pointer;
            transition: var(--transition);
            padding: 5px;
        }
        .card-toggle:hover {
            color: #3a86ff;
        }
        .card-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .card-content.expanded {
            max-height: 2000px; /* Adjust based on content */
        }
        
        /* How to Use Section */
        .how-to-column {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .how-to-title {
            color: #545454;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .how-to-title i {
            margin-right: 10px;
            color: #4361ee;
        }
        
        /* How To Guides Accordion */
        .how-to-accordion {
            margin-bottom: 20px;
        }
        .how-to-card {
            border: none;
            border-radius: 6px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .how-to-card-header {
            background: #f8fafc;
            border: none;
            padding: 15px;
            border-radius: 6px !important;
        }
        .how-to-card-header button {
            background: none;
            border: none;
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
            width: 100%;
            text-align: left;
            padding: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .how-to-card-header button:hover {
            color: #4361ee;
        }
        .how-to-card-header button:focus {
            outline: none;
            box-shadow: none;
        }
        .how-to-card-header button:not(.collapsed) {
            color: #4361ee;
        }
        .how-to-card-body {
            padding: 15px;
            background: white;
            border-radius: 0 0 6px 6px;
        }
        .how-to-content {
            color: #64748b;
            line-height: 1.6;
        }
        
        .how-to-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        .how-to-btn {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: #4361ee;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: var(--transition);
            font-weight: 500;
        }
        .how-to-btn i {
            margin-right: 10px;
            font-size: 16px;
        }
        .how-to-btn:hover {
            background: #3a56d4;
            color: white;
            transform: translateY(-2px);
        }
        .how-to-btn.secondary {
            background: #6c757d;
        }
        .how-to-btn.secondary:hover {
            background: #5a6268;
        }
        
        /* Layout for main content - Modified for mobile */
        .main-content-wrapper {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .services-main {
            flex: 3;
            min-width: 300px;
        }
        .how-to-sidebar {
            flex: 1;
            min-width: 280px;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 992px) {
            .main-content-wrapper {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .services-main {
                flex: 2;
                min-width: 300px;
            }
            .how-to-sidebar {
                flex: 1;
                min-width: 280px;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            .main-content-wrapper {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 15px;
            }
            .services-main {
                flex: 2;
                min-width: 200px;
            }
            .how-to-sidebar {
                flex: 1;
                min-width: 100px;
            }
            .how-to-card-header button {
                font-size: 8px;
                padding: 3px;
            }
            .how-to-card-body {
                padding: 12px;
            }
            .how-to-column {
                margin-bottom: 15px;
            }
            .how-to-title{
                font-size: 15px;
                font-weight: bold;
            }
            .service-card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-title {
                font-size: 12px;
            }
            .service-content {
                flex-direction: column;
                gap: 15px;
            }
            .how-to-buttons{
                font-size: 6px;
                width: 100px;
            }
            .how-to-content{
                 font-size: 8px;
            }
        }
        
        /* For very small screens */
        @media (max-width: 480px) {
            .main-content-wrapper {
                gap: 10px;
            }
            .services-main, .how-to-sidebar {
                min-width: 100%;
            }
            .how-to-card-header button {
                font-size: 9px;
                padding: 10px;
            }
            .how-to-card-body {
                padding: 10px;
            }
            .card-title {
                font-size: 16px;
            }
            .services-list li, .detail-item {
                font-size: 14px;
            }
        }
          /* Footer */
        .footer {
            background-color: #545454;
            color: white;
            padding: 40px 0 12px;
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
<br>
<main class="container main-container">
      <div class="services-container">
        <h2 class="section-title"><i class="fas fa-concierge-bell me-2" style="color: #4361ee;"></i>Services Directory</h2>
        
        <div class="main-content-wrapper">
            <div class="services-main">
                <input type="text" class="search-box" placeholder="Search services..." id="servicesSearch">

                <?php foreach ($services_data as $service): 
                    $offered_list = array_filter(array_map('trim', explode("\n", $service['services_offered'])));
                ?>
                    <div class="service-card" data-search="<?=
                        strtolower(htmlspecialchars($service['title'].' '.$service['description'].' '.$service['services_offered'].' '.$service['location'].' '.$service['contact'].' '.$service['hours'].' '.$service['requirements']))
                    ?>">
                        <div class="service-card-header" onclick="toggleCard(<?= $service['id'] ?>)">
                            <h4 class="card-title">
                                <?php
                                $icon = $default_icon;
                                $service_lower = strtolower($service['title']);
                                foreach ($service_icons as $key => $value) {
                                    if (strpos($service_lower, $key) !== false) {
                                        $icon = $value;
                                        break;
                                    }
                                }
                                ?>
                                <i class="fas <?= $icon['icon'] ?>" style="color: <?= $icon['color'] ?>;"></i>
                                <?= htmlspecialchars($service['title']) ?>
                            </h4>
                            <button class="card-toggle" aria-label="Toggle service details">
                                <i class="fas fa-chevron-down" id="toggle-icon-<?= $service['id'] ?>"></i>
                            </button>
                        </div>
                        
                        <div class="card-content" id="card-content-<?= $service['id'] ?>">
                            <p class="card-description"><?= htmlspecialchars($service['description']) ?></p>

                            <div class="service-content">
                                <div class="services-column">
                                    <strong>🛠️ Services Offered:</strong>
                                    <?php if (!empty($offered_list)): ?>
                                        <ul class="services-list">
                                            <?php foreach ($offered_list as $item): ?>
                                                <li><?= htmlspecialchars($item) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">No services listed.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="details-column">
                                    <div class="detail-item">
                                        <strong>📍 Location:</strong>
                                        <?= htmlspecialchars($service['location']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong> 🕒 Hours:</strong>
                                        <?= htmlspecialchars($service['hours']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>📞  Contact Details:</strong>
                                        <?php
                                        $contact_list = array_filter(array_map('trim', explode("\n", $service['contact'])));
                                        if (!empty($contact_list)): ?>
                                            <ul class="services-list" style="list-style-type: none; padding-left: 0;">
                                                <?php foreach ($contact_list as $contact): ?>
                                                    <li><?= htmlspecialchars($contact) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No contact details provided.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($service['requirements'])): ?>
                                <div class="requirements-section">
                                    <h5 class="section-subtitle">📄 Requirements:</h5>
                                    <div><?= nl2br(htmlspecialchars($service['requirements'])) ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($service['documents'])): ?>
                                <div class="documents-section">
                                    <h5 class="section-subtitle">📄 Documents:</h5>
                                    <ul class="documents-list">
                                        <?php foreach ($service['documents'] as $doc): ?>
                                            <li class="document-item">
                                                <a href="#" class="document-link">
                                                    <i class="fas fa-file-pdf"></i>
                                                    <?= htmlspecialchars($doc['document_name']) ?>
                                                </a>
                                                <div class="document-actions">
                                                    <button class="document-btn btn-view" onclick="window.open('<?= $doc['file_path'] ?>', '_blank')">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="document-btn btn-download" onclick="window.location.href='?download=<?= urlencode($doc['file_path']) ?>'">
                                                        <i class="fas fa-download"></i> Download
                                                    </button>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="noResults" class="no-results" style="display: none;">
                    <i class="fas fa-search fa-2x mb-3"></i>
                    <p>No services found matching your search.</p>
                </div>
            </div>
            
            <!-- How to Use Section - Will stay on right side in mobile -->
            <div class="how-to-sidebar">
                <div class="how-to-column">
                    <h3 class="how-to-title"><i class="fas fa-info-circle"></i>How To Guides</h3>
                    
                    <?php if (!empty($howto_guides)): ?>
                        <div class="how-to-accordion" id="howToAccordion">
                            <?php foreach ($howto_guides as $index => $guide): ?>
                                <div class="how-to-card">
                                    <div class="how-to-card-header" id="heading<?= $guide['id'] ?>">
                                        <button class="collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?= $guide['id'] ?>" 
                                                aria-expanded="false" 
                                                aria-controls="collapse<?= $guide['id'] ?>">
                                            <?= htmlspecialchars($guide['title']) ?>
                                            <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                    <div id="collapse<?= $guide['id'] ?>" class="collapse" 
                                         aria-labelledby="heading<?= $guide['id'] ?>" 
                                         data-bs-parent="#howToAccordion">
                                        <div class="how-to-card-body">
                                            <div class="how-to-content">
                                                <?= nl2br(htmlspecialchars($guide['content'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No how-to guides available at the moment.</p>
                    <?php endif; ?>
                    
                    <div class="how-to-buttons">
                        <a href="contact.php" class="how-to-btn">
                            <i class="fas fa-headset"></i>Need Help? Contact Support
                        </a>
                        <a href="popular.php" class="how-to-btn secondary">
                            <i class="fas fa-map-marked-alt"></i>Explore Iloilo City Guide
                        </a>
                        <a href="transportations.php" class="how-to-btn secondary">
                            <i class="fas fa-bus"></i>Transportation Options
                        </a>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="how-to-column">
                    <h3 class="how-to-title"><i class="fas fa-bolt"></i>Quick Actions</h3>
                    <div class="how-to-buttons">
                        <a href="emergency.php" class="how-to-btn" style="background: #dc2626;">
                            <i class="fas fa-ambulance"></i>Emergency Services
                        </a>
                        <a href="announcement.php" class="how-to-btn secondary">
                            <i class="fas fa-bullhorn"></i>Latest Announcements
                        </a>
                        <a href="categories.php" class="how-to-btn secondary">
                            <i class="fas fa-th-large"></i>Browse by Category
                        </a>
                    </div>
                </div>
            </div>
        </div>
      </div>
    </main>

    <footer class="footer mt-5 py-4  text-white">
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

    <script>
      // Sidebar controls
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
        if (event.key === 'Escape') closeSidebar();
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

      function filterServices() {
        var input = document.getElementById('servicesSearch');
        var term = input.value.toLowerCase().trim();
        var cards = document.querySelectorAll('.service-card');
        var anyVisible = false;

        cards.forEach(function(card) {
          var haystack = card.getAttribute('data-search') || '';
          var show = term === '' || haystack.indexOf(term) !== -1;
          card.style.display = show ? 'block' : 'none';
          if (show) anyVisible = true;
        });

        document.getElementById('noResults').style.display = anyVisible ? 'none' : 'block';
      }

      document.addEventListener('DOMContentLoaded', function() {
        filterServices();
        var box = document.getElementById('servicesSearch');
        box.addEventListener('input', filterServices);
        
        // Expand first card by default
        if (document.querySelector('.service-card')) {
          toggleCard(<?= $services_data[0]['id'] ?? 0 ?>, true);
        }
        
        // Initialize Bootstrap collapse for how-to guides
        var howToCollapses = document.querySelectorAll('.how-to-card .collapse');
        howToCollapses.forEach(function(collapse) {
            collapse.addEventListener('show.bs.collapse', function() {
                var icon = this.previousElementSibling.querySelector('.fa-chevron-down');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            });
            
            collapse.addEventListener('hide.bs.collapse', function() {
                var icon = this.previousElementSibling.querySelector('.fa-chevron-up');
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        });
      });

      function viewDocument(filePath) { window.open(filePath, '_blank'); }
      
      // Toggle card dropdown
      function toggleCard(serviceId, forceOpen = false) {
        const cardContent = document.getElementById(`card-content-${serviceId}`);
        const toggleIcon = document.getElementById(`toggle-icon-${serviceId}`);
        
        if (forceOpen || cardContent.classList.contains('expanded')) {
          cardContent.classList.remove('expanded');
          toggleIcon.classList.remove('fa-chevron-up');
          toggleIcon.classList.add('fa-chevron-down');
        } else {
          cardContent.classList.add('expanded');
          toggleIcon.classList.remove('fa-chevron-down');
          toggleIcon.classList.add('fa-chevron-up');
        }
      }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>