<?php
session_start();
include "conn.php";

$result = $conn->query("SELECT * FROM pawnshops");

$searchNameToTrigger = '';
if (isset($_GET['search'])) {
    $searchNameToTrigger = $_GET['search'];
} elseif (isset($_SESSION['search_trigger'])) {
    if ($_SESSION['search_trigger']['table'] === 'pawnshops') {
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

$query = "SELECT * FROM pawnshops";
$pawnshops_exist = false;

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $pawnshops_exist = $result->num_rows > 0;
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
    <title>PAWNSHOPS</title>

     <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/carousel/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="style.css">
<style>
body {
    background: url('img/bg.png') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
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
<div class="album py-5" id="pawnshops">
    <div class="container">
        <br>
        <br>
        <br>
        <br>
        <h1 class="page-title text-light text-center">Pawnshops in Iloilo City</h1> 
        
        <div class="card-container">
            <?php if ($pawnshops_exist): ?>
                <?php while ($pawnshop = $result->fetch_assoc()): ?>
                    <div class="card shadow-sm">
                        <img src="<?= htmlspecialchars($pawnshop['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($pawnshop['name']) ?>">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?= htmlspecialchars($pawnshop['name']) ?></h6>
                            <?php if (!empty($pawnshop['details_link'])): ?>
                                <a href="<?= htmlspecialchars($pawnshop['details_link']) ?>" class="card-button">
                                    View Details
                                </a>
                            <?php else: ?>
                                <span class="card-button">Details Not Available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No pawnshop found.</div>
            <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>