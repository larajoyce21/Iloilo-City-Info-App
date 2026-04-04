<?php
session_start();
include "conn.php";

$result = $conn->query("SELECT * FROM pcg_details");

$searchNameToTrigger = '';
if (isset($_GET['search'])) {
    $searchNameToTrigger = $_GET['search'];
} elseif (isset($_SESSION['search_trigger'])) {
    if ($_SESSION['search_trigger']['table'] === 'pcg') {
        $searchNameToTrigger = $_SESSION['search_trigger']['name'];
        unset($_SESSION['search_trigger']); 
    }
}

function convertToEmbedURL($googleMapsLink) {
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {
        return str_replace("maps/place/", "maps/embed?pb=", $googleMapsLink);
    }
    return $googleMapsLink;
}

$query = "SELECT * FROM pcg_details";
$pcg_exist = false;

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $pcg_exist = $result->num_rows > 0;
    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Philippine Coast Guard in Iloilo</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/carousel/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<style>
  body {
      background: url('img/bg.png') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
  }
    
  /* Navbar Styling */
  .navbar {
      display: flex;
      width: 100%;
      padding: 10px 20px; 
      transition: all 0.3s ease;
      background-color:rgba(250, 250, 250, 0.95);
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      height: 90px; 
  }

  .navbar.scrolled {
      background-color:rgba(250, 250, 250, 0.95);
      padding: 5px 20px;
      height: 50px; 
  }

    .logo {
          width: 250px;
          height: 60px;
          transition: var(--transition);
        }

  .navbar a {
      padding: 10px 15px; 
      color: black;
      text-decoration: none;
      font-size: 15px; 
      transition: all 0.3s ease;
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
    bottom: 10px;
    left: 20px;
    background-color: #ccc;
    transition: all 0.3s ease;
  }

  .navbar a:hover::after,
  .navbar a:focus::after {
    width: calc(100% - 40px);
  }

  .sidebar a {
      padding: 12px 25px; 
      font-size: 16px; 
  }

  .navbar-toggler {
      padding: 0.25rem 0.5rem;
      font-size: 1rem;
  }
  .navbar-nav {
      align-items: center;
  }
  .navbar-toggler-icon {
      background-image: none;
      position: relative;
      width: 24px;
      height: 24px;
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
      top: 6px;
  }

  .navbar-toggler-icon::after {
      bottom: 6px;
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
      background-color: rgba(250, 250, 250, 0.95);
      backdrop-filter: blur(5px);
      overflow-x: hidden;
      transition: all 0.3s ease;
      padding-top: 60px; 
      display: flex;
      flex-direction: column;
      z-index: 1050;
      box-shadow: -5px 0 15px rgba(0, 0, 0, 0.2);
  }
  .sidebar.open {
    width: 300px;
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
    transition: all 0.3s ease;
  }

  .sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
  }

  .sidebar a {
    padding: 15px 25px;
    text-decoration: none;
    font-size: 18px;
    color: black;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
    border-left: 4px solid transparent;
    font-weight: bold;
  }

  .sidebar a i {
    margin-right: 12px;
    font-size: 20px;
    width: 24px;
    text-align: center;
  }

  .sidebar a:hover,
  .sidebar a:focus {
    background-color: rgba(255, 255, 255, 0.1);
    border-left: 4px solid #008bf8;
    padding-left: 30px;
    outline: none;
  }

  .sidebar a.active {
    background-color: rgba(255, 255, 255, 0.05);
    border-left: 4px solid #008bf8;
    font-weight: 600;
  }

  /* Card Grid  */
  .card-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
      gap: 30px;
      padding: 20px;
      max-width: 800px;
      margin: 0 auto;
      justify-content: center; /* ✅ Center the cards */
  }


  /* Card  */
  .card {
      width: 100%;
      height: 400px;
      border-radius: 20px;
      background: #f5f5f5;
      position: relative;
      padding: 1.8rem;
      border: 2px solid #c3c6ce;
      transition: all 0.5s ease-out;
      overflow: visible;
      transform: translateY(0);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      margin: 20px
  }

  .card:hover {
      transform: translateY(-5px);
      box-shadow: 20 10px 20px rgba(0, 0, 0, 0.2);
      border-color: #7D0A0A;
  }

  .card-img-top {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 15px 15px 0 0;
  }

  .card-body {
      padding: 1rem;
      text-align: center;
  }

  .card-title {
      font-size: .9rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
  }

  .card-button {
      transform: translate(-50%, 125%);
      width: 80%;
      border-radius: 1rem;
      border: none;
      background-color: #008bf8;
      color: #fff;
      font-size: 1rem;
      padding: .5rem 1rem;
      position: absolute;
      left: 50%;
      bottom: 0;
      opacity: 0;
      transition: all 0.3s ease-out;
      cursor: pointer;
  }

  .card:hover .card-button {
      transform: translate(-50%, 50%);
      opacity: 1;
  }

  .google-map iframe {
      width: 100%;
      height: 200px; 
      border-radius: 10px; 
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
  }

  .nav-tabs .nav-link {
      color: #495057;
      font-weight: 500;
  }

  .nav-tabs .nav-link.active {
      color: #0d6efd;
      font-weight: 600;
  }

  .tab-content {
      padding: 15px 0;
  }

  @media (max-width: 1200px) {
      .card-container {
          grid-template-columns: repeat(3, 1fr); 
      }
  }

  @media (max-width: 768px) {
      .card-container {
          grid-template-columns: repeat(2, 1fr); 
          gap: 15px;
          padding: 15px;
      }
      
      .card {
          height: 300px;
          padding: 1.2rem;
      }
      
      .card-title {
          font-size: 1rem;
      }
      
      .card-button {
          font-size: 1rem;
          width: 80%;
      }
      
    
      .google-map iframe {
          height: 150px;
      }
      
      .nav-tabs .nav-link {
          padding: 8px 12px;
          font-size: 12px;
      }
      .card-title{
        font-size: .8em;
      }
  }

  @media (max-width: 576px) {
      .card-container {
          grid-template-columns: repeat(2, 1fr);
          gap: 10px;
      }
      
      .card {
          margin: 5px;
      }
  }


  .logo {
    width: 250px; 
    height: 60px; 
    display: block;
  }

  h1.fw-light {
    font-weight: 300; 
    color: white; 
    text-align: center; 
    font-family: Georgia, 'Times New Roman', Times, serif;
    padding: 20px;
    border-radius: 10px; 
    animation: gradientAnimation 10s ease infinite;
    background-size: 200% 200%;
    margin: 20px auto;
    max-width: 80%;
  }

  @keyframes gradientAnimation {
    0% {
      background-position: 0% 50%;
    }
    50% {
      background-position: 100% 50%;
    }
    100% {
      background-position: 0% 50%;
    }
  }

  @media screen and (max-width: 768px) {
    h1.fw-light {
      font-size: 1.5rem;
      padding: 15px;
    }
  }

  @media screen and (max-width: 480px) {
    h1.fw-light {
      font-size: 1.2rem;
      padding: 10px;
    }
  }

  /* Footer  */
  .footer {
      background-color: #545454;
      padding: 2rem 0;
      margin-top: auto;
  }

  .footer h6 {
      font-weight: 600;
      margin-bottom: 1rem;
  }

  .footer p, .footer li {
      font-size: 0.9rem;
      color: #6c757d;
  }

  .social-icons a {
      color: #6c757d;
      font-size: 1.2rem;
      margin-right: 0.8rem;
      transition: color 0.3s ease;
  }

  .social-icons a:hover {
      color: #0d6efd;
  }

  .list-unstyled li {
      margin-bottom: 0.5rem;
  }

  .list-unstyled a {
      color: #6c757d;
      text-decoration: none;
      transition: color 0.3s ease;
  }

  .list-unstyled a:hover {
      color: #0d6efd;
  }
  /* Footer */
        .footer {
          background-color: #004a8d;
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

    <div class="album py-5" id="pcg">
        <div class="container">
            <br>
            <br>
            <br>
            <br>
            <h1 class="page-title text-light text-center">Philippine Coast Guard in Iloilo City</h1>
            <p class="lead text-center text-white mb-4">Maritime safety, security, and environmental protection</p>
            
            <div class="card-container">
                <?php if ($pcg_exist): ?>
                    <?php while ($pcg = $result->fetch_assoc()): ?>
                        <div class="card shadow-sm">
                            <img src="<?= htmlspecialchars($pcg['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($pcg['name']) ?>">
                            <div class="card-body text-center">
                                <h6 class="card-title"><?= htmlspecialchars($pcg['name']) ?></h6>
                                <?php if (!empty($pcg['details_link'])): ?>
                                    <a href="<?= htmlspecialchars($pcg['details_link']) ?>" class="card-button">
                                        View Details
                                    </a>
                                <?php else: ?>
                                    <span class="card-button">Details Not Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No Coast Guard stations found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        <?php if (!empty($searchNameToTrigger)): ?>
            const modalTitleToFind = "<?= addslashes($searchNameToTrigger) ?>";
            const modals = document.querySelectorAll(".modal");
            
            modals.forEach(modal => {
                const titleElement = modal.querySelector(".modal-title");
                if (titleElement && titleElement.textContent.trim().toLowerCase() === modalTitleToFind.toLowerCase()) {
                    const modalInstance = new bootstrap.Modal(modal);
                    modalInstance.show();
                    
                    setTimeout(() => {
                        modal.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 500);
                }
            });
        <?php endif; ?>
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($highlightItem)): ?>
            const modals = document.querySelectorAll('.modal');
            let foundModal = null;
            
            modals.forEach(modal => {
                const title = modal.querySelector('.modal-title');
                if (title && title.textContent.trim().toLowerCase() === '<?= strtolower($highlightItem) ?>') {
                    foundModal = modal;
                }
            });
            
            if (foundModal) {
                const modalInstance = new bootstrap.Modal(foundModal);
                modalInstance.show();
                
                foundModal.querySelector('.btn-close').addEventListener('click', function() {
                    modalInstance.hide();
                    
                    if (window.history.replaceState) {
                        const url = new URL(window.location);
                        url.searchParams.delete('highlight');
                        window.history.replaceState(null, '', url);
                    }
                });
                
                const card = document.querySelector(`[data-modal-target="#${foundModal.id}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('highlight-card');
                    setTimeout(() => {
                        card.classList.remove('highlight-card');
                    }, 3000);
                }
            }
        <?php endif; ?>
    });
    </script>
    <?php $conn->close(); ?>
  </body>
</html>