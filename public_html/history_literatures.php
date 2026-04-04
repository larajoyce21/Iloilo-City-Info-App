<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'conn.php';
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $periods = array();
    $sql = "SELECT * FROM historical_periods ORDER BY start_year";
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $periods[] = $row;
        }
    }

    $writers = array();
    $sql = "SELECT w.*, GROUP_CONCAT(ww.title SEPARATOR '|') as works 
            FROM writers w 
            LEFT JOIN writer_works ww ON w.id = ww.writer_id 
            GROUP BY w.id 
            ORDER BY w.name";
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $row['works'] = !empty($row['works']) ? explode('|', $row['works']) : [];
            $writers[] = $row;
        }
    }

    $conn->close();
} catch (Exception $e) {
    error_log($e->getMessage());
    
    die("An error occurred while loading the page. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore the rich history and literature of Iloilo City">
    <title>Iloilo History and Literature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('img/bg.png') no-repeat center center fixed;
            background-size: cover;
            padding-top: 70px; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        
        .tab-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }
        
        .nav-tabs .nav-link.active {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .highlight {
            background-color: #fff3cd;
            padding: 2px 5px;
            border-radius: 3px;
        }
        
        .cultural-img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box i {
            position: absolute;
            top: 12px;
            left: 12px;
            color: #6c757d;
        }
        
        .search-input {
            padding-left: 35px;
        }
        
        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.8);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .favorite-btn.active {
            color: #dc3545;
        }
        
        .map-container {
            height: 300px;
            background-color: #e9ecef;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
            margin-bottom: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #0d6efd;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid white;
        }
        
        .timeline-date {
            font-weight: bold;
            color: #0d6efd;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                padding-top: 15px;
            }
            
            .sidebar a {
                font-size: 16px;
                padding: 8px 16px;
            }
        }
        /* Footer */
      .footer {
        background-color: #545454;
        color: white;
        padding: 60px 0 20px;
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
    <div class="container my-4">
        <ul class="nav nav-tabs" id="iloiloTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-landmark me-1"></i> History
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="literature-tab" data-bs-toggle="tab" data-bs-target="#literature" type="button" role="tab">
                    <i class="fas fa-book me-1"></i> Literature
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites" type="button" role="tab">
                    <i class="fas fa-star me-1"></i> Favorites <span class="badge bg-primary" id="favorites-count">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- History Tab -->
            <div class="tab-pane fade show active" id="history" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fas fa-landmark me-2"></i> The Rich History of Iloilo City</h4>
                        <div class="filter-buttons mb-3">
                            <button class="btn btn-outline-primary btn-sm filter-history active" data-filter="all">All</button>
                            <button class="btn btn-outline-primary btn-sm filter-history" data-filter="precolonial">Pre-Colonial</button>
                            <button class="btn btn-outline-primary btn-sm filter-history" data-filter="spanish">Spanish Era</button>
                            <button class="btn btn-outline-primary btn-sm filter-history" data-filter="american">American Era</button>
                            <button class="btn btn-outline-primary btn-sm filter-history" data-filter="modern">Modern</button>
                        </div>
                        
                        <div class="timeline">
                            <?php foreach ($periods as $period): ?>
                            <div class="timeline-item" data-era="<?= htmlspecialchars($period['era']) ?>">
                                <div class="timeline-date">
                                    <?= htmlspecialchars($period['start_year']) ?>
                                    <?php if ($period['end_year'] != $period['start_year']): ?>
                                        -<?= htmlspecialchars($period['end_year']) ?>
                                    <?php endif; ?>
                                </div>
                                <h5><?= htmlspecialchars($period['title']) ?></h5>
                                <p><?= htmlspecialchars($period['description']) ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Literature Tab -->
            <div class="tab-pane fade" id="literature" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fas fa-book me-2"></i> The Literary Heritage of Iloilo</h4>
                        <div class="filter-buttons mb-3">
                            <button class="btn btn-outline-primary btn-sm filter-literature active" data-filter="all">All</button>
                            <button class="btn btn-outline-primary btn-sm filter-literature" data-filter="poetry">Poetry</button>
                            <button class="btn btn-outline-primary btn-sm filter-literature" data-filter="fiction">Fiction</button>
                            <button class="btn btn-outline-primary btn-sm filter-literature" data-filter="drama">Drama</button>
                            <button class="btn btn-outline-primary btn-sm filter-literature" data-filter="nonfiction">Non-Fiction</button>
                        </div>
                        
                        <div class="row mt-3" id="writers-container">
                            <?php foreach ($writers as $writer): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card card-hover h-100">
                                    <div class="card-body">
                                        <button class="favorite-btn" data-type="writer" data-id="<?= htmlspecialchars($writer['id']) ?>">
                                            <i class="far fa-star"></i>
                                        </button>
                                        <h5 class="card-title"><?= htmlspecialchars($writer['name']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars($writer['bio']) ?></p>
                                        <div class="mt-2">
                                            <span class="badge bg-info"><?= htmlspecialchars(ucfirst($writer['type'])) ?></span>
                                        </div>
                                        <?php if (!empty($writer['works'])): ?>
                                        <div class="mt-2">
                                            <strong>Notable Works:</strong>
                                            <ul class="mb-0">
                                                <?php foreach ($writer['works'] as $work): ?>
                                                <li><?= htmlspecialchars($work) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card sticky-top" style="top: 20px;">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Literary Works</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" id="literary-work-search" placeholder="Search works...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="list-group" id="literary-works-list">
                                    <?php 
                                    $allWorks = [];
                                    foreach ($writers as $writer) {
                                        $allWorks = array_merge($allWorks, $writer['works']);
                                    }
                                    foreach (array_unique($allWorks) as $work): ?>
                                    <a href="#" class="list-group-item list-group-item-action"><?= htmlspecialchars($work) ?></a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Favorites Tab -->
            <div class="tab-pane fade" id="favorites" role="tabpanel">
                <h4><i class="fas fa-star me-2"></i> Your Favorites</h4>
                <p class="text-muted">Items you've marked as favorites will appear here</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Favorite Landmarks</h5>
                            </div>
                            <div class="card-body" id="favorite-landmarks">
                                <p class="text-muted">No favorite landmarks yet</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Favorite Writers</h5>
                            </div>
                            <div class="card-body" id="favorite-writers">
                                <p class="text-muted">No favorite writers yet</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
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
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
 
</body>
</html>