<?php
include 'conn.php';

function recordCategoryVisit($conn, $categoryId, $categoryName) {
    $categoryId = (int)$categoryId;
    $categoryName = htmlspecialchars(trim($categoryName));
    
    if ($categoryId <= 0 || empty($categoryName)) return false;
    
    // Record the visit
    $stmt = $conn->prepare("INSERT INTO category_visits (category_id, category_name, visit_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $categoryId, $categoryName);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// visit tracking 
if (isset($_GET['view_id'])) {
    $stmt = $conn->prepare("SELECT id, name, user_link FROM categories WHERE id = ?");
    $stmt->bind_param("i", $_GET['view_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        recordCategoryVisit($conn, $row['id'], $row['name']);
        header("Location: " . $row['user_link']);
        exit();
    }
    $stmt->close();
}

$query = "SELECT * FROM categories ORDER BY name ASC";
$result = $conn->query($query);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="description" content="Browse all available categories in Iloilo City">
    <meta name="author" content="Iloilo City Tourism Office">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Categories - Iloilo City Info App</title>

    <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
   <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/carousel/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --transition: all 0.3s ease;
        --primary-color: #508acb;
        --light-color: #ffffff;
        --dark-color: #292929;
        --gray-color: #848484;
        --hover-color: #6a6a6a;
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
            font-family: 'Poppins', sans-serif;

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

    
    /*  Button  */
    .category-button {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        background: linear-gradient(135deg, var(--dark-color), var(--gray-color));
        color: white;
        font-family: "Poppins", sans-serif;
        font-weight: 500;
        font-size: 1rem;
        border: none;
        border-radius: 10px;
        padding: 12px 16px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        width: 100%;
        height: 60px; /* Fixed height for consistency */
        min-height: 60px; /* Fixed minimum height */
        text-align: left;
        overflow: hidden;
        text-decoration: none;
        outline: none;
        margin-bottom: 12px;
    }

    .category-button i {
        margin-right: 10px;
        font-size: 1.1rem;
        transition: transform 0.3s ease;
        min-width: 24px;
        text-align: center;
        flex-shrink: 0; /* Prevent icon from shrinking */
    }

    .category-button span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .category-button:hover,
    .category-button:focus {
        background: linear-gradient(135deg, var(--dark-color), var(--hover-color));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        color: white;
    }

    .category-button:hover i,
    .category-button:focus i {
        transform: scale(1.1);
    }

    .category-button:active {
        transform: translateY(1px);
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
    }

    .category-button:focus {
        outline: 2px solid rgba(255, 255, 255, 0.5);
        outline-offset: 2px;
    }

    .category-button .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.4);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }
    
    /* Category Grid Layout */
    .category-column {
        margin-bottom: 1.5rem;
        display: flex;
        flex-direction: column;
    }
    
    .category-container {
        box-shadow: 0 8px 20px 0 rgba(41, 41, 41, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        background-color: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
        padding: 1.5rem;
        border-radius: 12px;
    }

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border-width: 0;
    }

    .page-title {
        font-family: 'Playfair Display', serif;
        color: white;
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
    
    /* Search box */
    .search-container {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .search-input {
        width: 100%;
        padding: 12px 20px;
        border-radius: 25px;
        border: none;
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        font-family: 'Poppins', sans-serif;
        padding-left: 45px;
    }
    
    .search-input:focus {
        outline: 2px solid var(--primary-color);
    }
    
    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-color);
    }
    
    /* No results message */
    .no-results {
        text-align: center;
        padding: 2rem;
        color: var(--gray-color);
    }
    
    /* Space after every 5 items */
    .category-item-spacer {
        height: 30px;
        width: 100%;
        display: block;
    }
    
    /* Responsive adjustments */
    @media (min-width: 992px) {
        /* Desktop - 4 columns */
        .category-column {
            width: 25%;
            float: left;
            padding: 0 15px;
        }
        
        .category-button {
            height: 60px;
            min-height: 60px;
            font-size: 1rem;
            padding: 12px 16px;
        }
    }

    @media (max-width: 991px) and (min-width: 576px) {
        /* Tablet - 2 columns */
        .category-column {
            width: 50%;
            float: left;
            padding: 0 10px;
        }
        
        .category-button {
            height: 55px;
            min-height: 55px;
            font-size: 0.9rem;
            padding: 10px 12px;
        }
        
        .category-button i {
            font-size: 1rem;
            margin-right: 8px;
        }
        
        .category-item-spacer {
            height: 25px;
        }
    }

    @media (max-width: 575px) {
        /* Mobile - 2 columns */
        .category-column {
            width: 50%;
            float: left;
            padding: 0 8px;
        }
        
        .category-button {
            height: 50px;
            min-height: 50px;
            font-size: 0.85rem;
            padding: 8px 10px;
        }
        
        .category-button i {
            font-size: 0.9rem;
            margin-right: 6px;
        }
        
        .category-item-spacer {
            height: 20px;
        }
        
        .page-title {
            font-size: 1.75rem;
        }
    }

    /* Clearfix for columns */
    .category-row::after {
        content: "";
        display: table;
        clear: both;
    }
    
    /* Footer */
    .footer {
        background-color: #545454;
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
        outline: none;
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
        border: none;
    }

    .social-icons .btn:hover,
    .social-icons .btn:focus {
        transform: translateY(-3px);
        background: var(--primary-color);
        outline: none;
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

    <div class="container my-5">
        <h1 class="page-title text-center">Available Categories</h1>
    
        
        <div class="row">
            <div class="col-12">
                <div class="category-container p-4 rounded-4">
                    <?php if ($result->num_rows > 0) { 
                        // Get all categories and sort alphabetically
                        $categories = [];
                        while ($row = $result->fetch_assoc()) {
                            $categories[] = $row;
                        }
                        
                        // Determine number of columns based on screen size
                        $columns = 4; // Default for large screens
                        if (isset($_SERVER['HTTP_USER_AGENT'])) {
                            if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $_SERVER['HTTP_USER_AGENT'])) {
                                $columns = 2; // Tablet
                            } else if (preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'])) {
                                $columns = 2; // Mobile
                            }
                        }
                        
                        // Calculate items per column (5 items per column)
                        $itemsPerColumn = 5;
                        $totalCategories = count($categories);
                        $totalColumns = ceil($totalCategories / $itemsPerColumn);
                        
                        // Create columns with 5 items each, arranged vertically
                        $columnCategories = array_fill(0, $totalColumns, []);
                        for ($i = 0; $i < $totalCategories; $i++) {
                            $columnIndex = floor($i / $itemsPerColumn);
                            if ($columnIndex < $totalColumns) {
                                $columnCategories[$columnIndex][] = $categories[$i];
                            }
                        }
                    ?>
                    <div class="category-row" id="categoryContainer">
                        <?php foreach ($columnCategories as $column) { ?>
                            <div class="category-column">
                                <?php 
                                foreach ($column as $category) { 
                                    ?>
                                    <button class="category-button" onclick="navigateToCategory(<?= $category['id'] ?>)" aria-label="View <?= htmlspecialchars($category['name']); ?> category">
                                        <i class="<?= htmlspecialchars($category['icon']); ?>"></i>
                                        <span><?= htmlspecialchars($category['name']); ?></span>
                                    </button>
                                    <?php 
                                } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <?php } else { ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-3x mb-3 text-muted"></i>
                            <h3 class="text-muted">No categories available</h3>
                            <p class="text-muted">Check back later for updated categories.</p>
                        </div>
                    <?php } ?>
                    
                    <!-- No results message (hidden by default) -->
                    <div id="noResults" class="no-results" style="display: none;">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h3>No categories found</h3>
                        <p>Try different search terms</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
            <p class="small text-white mb-0">&copy; Iloilo City Tourism Office. All rights reserved.</p>
          </div>
        </div>
      </div>
    </footer>
    <script>
    function openSidebar() {
      const sidebar = document.getElementById("mySidebar");
      const overlay = document.getElementById("sidebarOverlay");
      
      sidebar.classList.add("open");
      overlay.classList.add("active");
      document.body.style.overflow = "hidden";
      document.querySelector('.close-btn').focus();
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
      }
    });

    document.addEventListener('DOMContentLoaded', function () {
      const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
      popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
      });

      <?php if (!empty($searchNameToTrigger)): ?>
        const modalTitle = "<?= addslashes($searchNameToTrigger) ?>";
        const modals = document.querySelectorAll(".modal");
        modals.forEach(modal => {
          const title = modal.querySelector(".modal-title");
          if (title && title.textContent.trim().toLowerCase() === modalTitle.toLowerCase()) {
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
          }
        });
      <?php endif; ?>
      
      const buttons = document.querySelectorAll('.category-button');
      buttons.forEach(button => {
        button.addEventListener('click', function(e) {
          const ripple = document.createElement('span');
          ripple.classList.add('ripple');
          
          const rect = button.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          
          ripple.style.left = `${x}px`;
          ripple.style.top = `${y}px`;
          
          button.appendChild(ripple);
          
          setTimeout(() => {
            ripple.remove();
          }, 600);
        });
      });
      
    });

    function navigateToCategory(categoryId) {
      window.location.href = `categories.php?view_id=${categoryId}`;
    }

    document.addEventListener('touchstart', function(){}, true);

    document.addEventListener('keydown', function(e) {
      const buttons = document.querySelectorAll('.category-button');
      const currentIndex = Array.from(buttons).indexOf(document.activeElement);
      
      if (e.key === 'ArrowRight' && currentIndex < buttons.length - 1) {
        buttons[currentIndex + 1].focus();
        e.preventDefault();
      } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
        buttons[currentIndex - 1].focus();
        e.preventDefault();
      } else if (e.key === 'Enter' && document.activeElement.classList.contains('category-button')) {
        document.activeElement.click();
      }
    });
    </script>
    <?php $conn->close(); ?>
  </body>
</html>