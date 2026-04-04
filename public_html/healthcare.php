<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Healthcare</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/carousel/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/footers/">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

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

  /* New styles for icon cards */
  .icon-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
      cursor: pointer;
      background-color: #f8f9fa;
      border-radius: 10px;
         height: 130px; 
  }
  .icon-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
  }
  .icon-card .card-body {
      padding: 15px;
      text-align: center;
  }
  .icon-card i {
      font-size: 2rem;
      color: #545454;
      margin-bottom: 10px;
  }
  .icon-card .card-title {
      font-size: 0.9rem;
      font-weight: 600;
      color: #333;
      margin: 0;
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
<br>
<br>
<br>
<h1 class="page-title text-light text-center">Healthcare Information in Iloilo City</h1>
<br>
<!-- Icon Cards Section -->
<div class="container mt-4">
    <div class="row g-3 justify-content-center">
        <!-- START: Specialized Clinics Cards -->

        <!-- Birth Centers -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="birth_centers.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-baby fa-2x"></i>
                        <h6 class="card-title mt-2">Birth Centers</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- Dental clinics -->
       <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="dental_clinics.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-tooth fa-2x"></i>
                        <h6 class="card-title mt-2">Dental Clinics</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- Medical diagnostic clinics -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="medical_diagnostic_clinics.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-stethoscope fa-2x"></i>
                        <h6 class="card-title mt-2">Medical Diagnostic</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- Dialysis Centers -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="dialysis_centers.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-procedures fa-2x"></i>
                        <h6 class="card-title mt-2">Dialysis Centers</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- Hospitals -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="hospitals.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-hospital fa-2x"></i>
                        <h6 class="card-title mt-2">Hospitals</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- Pharmacy -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="pharmacies.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-prescription-bottle-alt fa-2x"></i>
                        <h6 class="card-title mt-2">Pharmacies</h6>
                    </div>
                </div>
            </a>
        </div>
        <!-- Optical -->
        <div class="col-4 col-md-4 col-lg-2-4 healthcare-card">
            <a href="optical_clinics.php" class="text-decoration-none">
                <div class="card icon-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-glasses fa-2x"></i>
                        <h6 class="card-title mt-2">Optical Clinics</h6>
                    </div>
                </div>
            </a>
        </div>

        <!-- END: Specialized Clinics Cards -->
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
    </main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

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

    <script src="../assets/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://bootstrapmade.com/assets/js/demo.js?v=42"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
      /* Custom column class for 5 cards per row on desktop */
      @media (min-width: 992px) {
        .col-lg-2-4 {
          flex: 0 0 auto;
          width: 20%;
        }
      }
      
      /* Ensure 3 cards per row on mobile */
      @media (max-width: 767px) {
        .col-4 {
          flex: 0 0 auto;
          width: 33.333333%;
        }
      }
    </style>
  </body>
</html>