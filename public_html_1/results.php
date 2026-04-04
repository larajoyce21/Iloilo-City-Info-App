<?php
session_start();

if (!isset($_SESSION['search_results']) || !isset($_SESSION['search_query'])) {
    header("Location: homepage.php");
    exit;
}

$results = $_SESSION['search_results'];
$query = $_SESSION['search_query'];

unset($_SESSION['search_results']);
unset($_SESSION['search_query']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Discover Iloilo City - The City of Love. Explore cultural heritage, delicious food, festivals, and more.">
    <meta name="author" content="Iloilo City Info App Team">
    <title>Search Results - Discover Iloilo City</title>

    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
      :root {
        --primary-color: #545454;
        --primary-dark: #3d3d3d;
        --secondary-color: #1e1e2f;
        --accent-color: #800000;
        --accent-light: #a52a2a;
        --light-color: #f8f4e3;
        --dark-color: #2c2f48;
        --text-color: #333;
        --text-light: #f8f9fa;
        --transition: all 0.3s ease;
      }

      /* Base Styles */
      body {
        font-family: 'Open Sans', sans-serif;
        font-size: 0.9rem;
        line-height: 1.5;
        color: var(--text-color);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        position: relative;
      }

      body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
          linear-gradient(135deg, rgba(84, 84, 84, 0.85) 0%, rgba(128, 0, 0, 0.75) 100%),
          url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="none" width="100" height="100"/><path fill="rgba(255,255,255,0.05)" d="M25,25 L75,25 L75,75 L25,75 Z" /></svg>');
        background-size: cover, 200px 200px;
        z-index: -1;
      }

      h1, h2, h3, h4, h5, h6 {
          font-family: 'Poppins', sans-serif;
          font-weight: 600;
      }

      /* Remove all text decoration */
      a {
        text-decoration: none !important;
      }

      a:hover, a:focus {
        text-decoration: none !important;
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
        text-decoration: none !important;
        font-size: 13px;
        transition: var(--transition);
        position: relative;
        font-weight: bold;
      }

      .navbar a:hover,
      .navbar a:focus {
        color: #ccc;
        outline: none;
        text-decoration: none !important;
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
        text-decoration: none !important;
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
        text-decoration: none !important;
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
        text-decoration: none !important;
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

      /* Enhanced Search Results Section - WIDE & COMPACT */
      .search-results-section {
        padding: 100px 0 30px;
        min-height: 80vh;
      }

      .search-header {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(15px);
        border-radius: 12px;
        padding: 25px 30px;
        margin-bottom: 25px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.25);
        position: relative;
        overflow: hidden;
      }

      .search-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-color), var(--accent-light));
      }

      .search-results-count {
        color: var(--accent-color);
        font-weight: 600;
        font-size: 0.95rem;
        background: rgba(128, 0, 0, 0.08);
        padding: 6px 14px;
        border-radius: 50px;
        display: inline-block;
        margin-top: 8px;
      }

      /* WIDE Results Container */
      .search-results-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
      }

      .search-result-item {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 12px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        text-decoration: none !important;
        display: block;
        color: inherit;
      }

      .search-result-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        border-color: rgba(128, 0, 0, 0.3);
        text-decoration: none !important;
        color: inherit;
      }

      .search-result-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 3px;
        height: 100%;
        background: linear-gradient(to bottom, var(--accent-color), var(--accent-light));
        opacity: 0;
        transition: var(--transition);
      }

      .search-result-item:hover::before {
        opacity: 1;
      }

      .search-result-title {
        color: var(--dark-color);
        font-weight: 700;
        margin-bottom: 6px;
        font-family: 'Open Sans', sans-serif;
        font-size: 1.1rem;
        position: relative;
        padding-left: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-decoration: none !important;
      }

      .search-result-title::before {
        content: '🔍';
        position: absolute;
        left: -5px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.9rem;
      }

      .search-result-description {
        color: var(--text-color);
        opacity: 0.85;
        margin-bottom: 0;
        line-height: 1.5;
        font-size: 0.85rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-decoration: none !important;
      }

      .search-result-category {
        display: inline-block;
        background: rgba(128, 0, 0, 0.1);
        color: var(--accent-color);
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-top: 6px;
        white-space: nowrap;
        text-decoration: none !important;
      }

      .no-results {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(15px);
        border-radius: 15px;
        padding: 50px 30px;
        text-align: center;
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.25);
        position: relative;
        overflow: hidden;
        max-width: 800px;
        margin: 0 auto;
      }

      .no-results::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-color), var(--accent-light));
      }

      .no-results-icon {
        font-size: 4rem;
        color: var(--accent-color);
        margin-bottom: 20px;
        opacity: 0.8;
      }

      .search-suggestions {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 25px;
        margin-top: 30px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
      }

      .suggestion-tag {
        display: inline-block;
        background: rgba(128, 0, 0, 0.1);
        color: var(--accent-color);
        padding: 8px 16px;
        border-radius: 50px;
        margin: 6px;
        text-decoration: none !important;
        font-size: 0.9rem;
        font-weight: 600;
        transition: var(--transition);
        border: 1px solid transparent;
      }

      .suggestion-tag:hover {
        background: var(--accent-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(128, 0, 0, 0.3);
        border-color: var(--accent-color);
        text-decoration: none !important;
      }

      .search-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 25px;
      }

      .search-actions .btn {
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        transition: var(--transition);
        font-size: 0.9rem;
        text-decoration: none !important;
      }

      .btn-new-search {
        background: var(--accent-color);
        color: white;
        border: none;
        text-decoration: none !important;
      }

      .btn-new-search:hover {
        background: var(--accent-light);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(128, 0, 0, 0.3);
        text-decoration: none !important;
      }

      .btn-browse {
        background: transparent;
        color: var(--accent-color);
        border: 2px solid var(--accent-color);
        text-decoration: none !important;
      }

      .btn-browse:hover {
        background: var(--accent-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(128, 0, 0, 0.3);
        text-decoration: none !important;
      }

      /* Footer */
      .footer {
          background-color: rgba(84, 84, 84, 0.9);
          color: white;
          padding: 30px 0 10px;
          backdrop-filter: blur(10px);
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
          text-decoration: none !important;
      }

      .footer .nav-link:hover,
      .footer .nav-link:focus {
          color: white;
          padding-left: 3px;
          text-decoration: none !important;
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
          text-decoration: none !important;
      }

      .social-icons .btn:hover,
      .social-icons .btn:focus {
          transform: translateY(-2px);
          background: var(--primary-color);
          text-decoration: none !important;
      }

      .copyright {
          border-top: 1px solid rgba(255,255,255,0.1);
          padding-top: 12px;
          margin-top: 20px;
          font-size: 0.8rem;
          color: rgba(255,255,255,0.6);
      }

      /* Responsive Adjustments */
      @media (max-width: 1200px) {
        .search-results-container {
          max-width: 100%;
          padding: 0 15px;
        }
      }

      @media (max-width: 768px) {
        .logo {
          width: 200px;
          height: auto;
        }
        
        .navbar a {
          padding: 10px 15px;
          font-size: 0.95rem;
        }
        
        .search-results-section {
          padding: 80px 0 20px;
        }
        
        .search-header {
          padding: 20px;
        }
        
        .search-result-item {
          padding: 15px;
        }
        
        .footer .col-md-3 {
          margin-bottom: 25px;
        }
        
        .search-actions {
          flex-direction: column;
          align-items: center;
        }
        
        .search-actions .btn {
          width: 100%;
          max-width: 250px;
        }
      }

      @media (max-width: 576px) {
        body {
          font-size: 0.85rem;
        }
        
        .footer {
          padding: 25px 0 15px;
        }
        
        .search-result-item {
          padding: 12px 15px;
        }
        
        .no-results {
          padding: 30px 20px;
        }
        
        .search-suggestions {
          padding: 20px;
        }
        
        .suggestion-tag {
          display: block;
          margin: 6px 0;
        }
        
        .search-result-title {
          font-size: 1rem;
        }
      }

      /* Print styles */
      @media print {
        .navbar,
        .sidebar,
        .back-to-top,
        body {
          background: none !important;
          color: #000 !important;
        }
        
        .footer {
          background: none !important;
          color: #000 !important;
          padding: 20px 0 !important;
        }
        
        a {
          color: #000 !important;
        }
        
        .btn {
          display: none !important;
        }
      }
    </style>
  </head>
  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-white fixed-top">
      <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="homepage.php" aria-label="Iloilo City Info App">
          <img src="img/logo4.png" alt="Iloilo City Info App Logo" class="logo">
        </a>
        <button class="navbar-toggler ms-auto" type="button" onclick="openSidebar()" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" href="homepage.php" aria-current="page">🏠 Home</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="popular.php">🗺️ Tourist Guide</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="categories.php">🗂️ Categories</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="transportations.php">🚌 Transport</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="services.php">🛎️ Services</a>
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

    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <div id="mySidebar" class="sidebar">
      <a href="homepage.php" aria-current="page"> 🏠  Home</a>
      <a href="popular.php"> 🗺️  Tourist Guide</a>
      <a href="categories.php"> 🗂️  Categories</a>
      <a href="transportations.php"> 🚌  Transport</a>
      <a href="services.php"> 🛎️  Services</a>
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
    <!-- Search Results Section -->
    <section class="search-results-section">
      <div class="search-results-container">
        <div class="search-header text-center">
          <h1 class="mb-3" style="font-size: 1.8rem;">Search Results for "<?= htmlspecialchars($query) ?>"</h1>
          <p class="search-results-count">
            <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> found
          </p>
        </div>

        <?php if (count($results) > 0): ?>
          <div class="list-group">
            <?php foreach ($results as $result): ?>
              <a href="<?= $result['redirect'] ?>?search=<?= urlencode($result['name']) ?>" class="search-result-item">
                <h5 class="search-result-title"><?= htmlspecialchars($result['name']) ?></h5>
                <p class="search-result-description"><?= htmlspecialchars(substr($result['description'], 0, 150)) ?>...</p>
                <?php if (isset($result['category'])): ?>
                  <span class="search-result-category"><?= htmlspecialchars($result['category']) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
          
          <div class="search-actions">
            <a href="homepage.php" class="btn btn-new-search">
              <i class="fas fa-search me-2"></i>New Search
            </a>
            <a href="categories.php" class="btn btn-browse">
              <i class="fas fa-compass me-2"></i>Browse Categories
            </a>
          </div>
        <?php else: ?>
          <div class="no-results">
            <div class="no-results-icon">
              <i class="fas fa-search"></i>
            </div>
            <h3 class="mb-3" style="font-size: 1.5rem;">No results found</h3>
            <p class="mb-4">We couldn't find any matches for "<?= htmlspecialchars($query) ?>".</p>
            
            <div class="search-suggestions">
              <h5 class="mb-3" style="font-size: 1.1rem;">Try these popular searches:</h5>
              <div class="d-flex flex-wrap justify-content-center">
                <a href="popular.php" class="suggestion-tag">Tourist Spots</a>
                <a href="categories.php" class="suggestion-tag">Restaurants</a>
                <a href="transportations.php" class="suggestion-tag">Transportation</a>
                <a href="services.php" class="suggestion-tag">Services</a>
                <a href="announcement.php" class="suggestion-tag">Events</a>
              </div>
            </div>
            
            <div class="search-actions mt-4">
              <a href="homepage.php" class="btn btn-new-search">
                <i class="fas fa-search me-2"></i>Try Another Search
              </a>
              <a href="categories.php" class="btn btn-browse">
                <i class="fas fa-compass me-2"></i>Browse All Categories
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <footer class="footer py-4 text-white">
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
              <li class="mb-1"><a href="homepage.php" class="text-white text-decoration-none">Home</a></li>
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
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
      // Enhanced Sidebar Functions
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
        document.querySelector('.navbar-toggler').focus();
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
    </script>
  </body>
</html>