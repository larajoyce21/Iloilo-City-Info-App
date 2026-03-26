<?php
session_start();
include "conn.php";

define('DOCUMENT_UPLOAD_DIR', 'uploads/documents/');
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx']);
define('MAX_DOC_SIZE', 5 * 1024 * 1024); 

if (!file_exists(DOCUMENT_UPLOAD_DIR)) {
    mkdir(DOCUMENT_UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_document'])) {
    $agency_id = $_POST['agency_id'];
    $document_name = $_POST['document_name'];
    
    if (isset($_FILES['document_file'])) {
        $file = $_FILES['document_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ALLOWED_DOC_TYPES)) {
            if ($file['size'] <= MAX_DOC_SIZE) {
                $new_filename = uniqid('doc_', true) . '.' . $file_ext;
                $target_path = DOCUMENT_UPLOAD_DIR . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt = $conn->prepare("INSERT INTO agency_documents (agency_id, document_name, file_path) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $agency_id, $document_name, $target_path);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Document uploaded successfully";
                    } else {
                        $_SESSION['error'] = "Error saving document to database";
                    }
                } else {
                    $_SESSION['error'] = "Error uploading file";
                }
            } else {
                $_SESSION['error'] = "File size exceeds maximum limit (5MB)";
            }
        } else {
            $_SESSION['error'] = "Only PDF and Word documents are allowed";
        }
    }
    header("Location: agencies.php?id=" . $agency_id);
    exit();
}
if (isset($_GET['delete_document'])) {
    $doc_id = $_GET['delete_document'];
    $agency_id = $_GET['agency_id'];
    
    $stmt = $conn->prepare("SELECT file_path FROM agency_documents WHERE id=?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    
    if ($document) {
        if (file_exists($document['file_path'])) {
            unlink($document['file_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM agency_documents WHERE id=?");
        $stmt->bind_param("i", $doc_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Document deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting document";
        }
    }
    header("Location: agencies.php?id=" . $agency_id);
    exit();
}

if (isset($_GET['view_document'])) {
    $doc_id = $_GET['view_document'];
    $stmt = $conn->prepare("SELECT * FROM agency_documents WHERE id=?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document = $result->fetch_assoc();
    
    if ($document) {
        $file_path = $document['file_path'];
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: inline; filename=" . basename($file_path));
        
        if ($file_ext == 'pdf') {
            header("Content-Type: application/pdf");
            readfile($file_path);
        } elseif (in_array($file_ext, ['doc', 'docx'])) {
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . basename($file_path));
            readfile($file_path);
        } else {
            readfile($file_path);
        }
        exit();
    } else {
        $_SESSION['error'] = "Document not found";
        header("Location: agencies.php");
        exit();
    }
}

$searchNameToTrigger = '';
if (isset($_GET['search'])) {
    $searchNameToTrigger = $_GET['search'];
} elseif (isset($_SESSION['search_trigger'])) {
    if ($_SESSION['search_trigger']['table'] === 'agencies') {
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

$query = "SELECT * FROM agencies ORDER BY name ASC";
$agencies_exist = false;

$current_agency = null;
$agency_documents = [];
if (isset($_GET['id'])) {
    $agency_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM agencies WHERE id=?");
    $stmt->bind_param("i", $agency_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_agency = $result->fetch_assoc();
    
    $stmt = $conn->prepare("SELECT * FROM agency_documents WHERE agency_id=?");
    $stmt->bind_param("i", $agency_id);
    $stmt->execute();
    $agency_documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $agencies_exist = $result->num_rows > 0;
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
    <title>GOVERNMENT AGENCIES</title>

     <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/carousel/">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
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

    /* Card Grid */
    .card-container {
        display: grid;
        grid-template-columns: repeat(5, 1fr); 
        gap: 30px;
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Card */
    .card {
        width: 100%;
        height: 254px;
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
        height: 150px;
        object-fit: cover;
        border-radius: 15px 15px 0 0;
    }

    .card-body {
        padding: 1rem;
        text-align: center;
    }

    .card-title {
        font-size: .7rem;
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
        font-size: .8rem;
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

    /* Modal */
    .modal-lg {
        max-width: 700px; 
    }

    .modal-body {
        text-align: left; 
    }

    .modal-content {
        font-size: 15px;
        padding: 10px; 
    }

    .modal-body img {
        max-width: 50%;
        height: auto;
        display: block;
        margin: 0 auto 15px;
    }

    .google-map iframe {
        width: 100%;
        height: 200px; 
        border-radius: 10px; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
    }

    /* Tab */
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

    /* Agency Details Page */
    .agency-header {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .agency-image {
        width: 100%;
        height: 300px;
        object-fit: cover;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .detail-images {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .detail-image {
        width: 150px;
        height: 100px;
        object-fit: cover;
        border-radius: 5px;
        cursor: pointer;
        transition: transform 0.3s;
    }

    .detail-image:hover {
        transform: scale(1.05);
    }

    .document-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .document-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transform: translateY(-3px);
    }

    .document-icon {
        font-size: 2.5rem;
        color: #0d6efd;
        margin-bottom: 10px;
    }

    .back-button {
        margin-bottom: 20px;
    }

    /* Toast Notifications */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1100;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .card-container {
            grid-template-columns: repeat(3, 1fr); 
        }
    }

    @media (max-width: 768px) {
        .card-container {
            grid-template-columns: repeat(3, 1fr); 
            gap: 15px;
            padding: 15px;
        }
        
        .card {
            height: 220px;
            padding: 1.2rem;
        }
        
        .card-title {
            font-size: .7rem;
        }
        
        .card-button {
            font-size: .6rem;
            width: 80%;
        }
        
        /* adjustments for mobile */
        .modal-dialog {
            margin: 10px auto;
            max-width: 80%;
            height: 80%
        }
        
        .modal-lg {
            max-width: 80%;
        }
        
        .modal-content {
            font-size: 14px;
        }
        
        .modal-body img {
            max-height: 200px;
        }
        
        .google-map iframe {
            height: 150px;
        }
        
        .nav-tabs .nav-link {
            padding: 8px 12px;
            font-size: 12px;
        }
        .card-title{
          font-size: .5em;
        }
    }

    @media (max-width: 576px) {
        .card-container {
            grid-template-columns: repeat(3, 1fr); 
            gap: 10px;
        }
        
        .card {
            margin: 5px;
        }
        
        .modal-title {
            font-size: 15px; 
        }
    }

    .logo {
        width: 250px;
        height: 60px;
        transition: var(--transition);
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

    /* gradient animation */
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

    /* Responsive for smaller screens */
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

    .business-card {
        background: #fff;
        border: none;
        border-radius: 10px;
        padding: 20px;
        transition: 0.3s ease-in-out;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        text-align: center;
    }

    .business-card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        transform: translateY(-5px);
    }

    .business-card i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #333;
    }

    .no-businesses {
        text-align: center;
        background: #fff;
        padding: 40px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    @media (max-width: 576px) {
        .business-card {
            min-width: 100px;
        }
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
<br>
<br>
<br>
<br>
<br>

    <h1 class="page-title text-light text-center">Government Offices in Iloilo City</h1> 
    <!-- Toast Notifications -->
    <div class="toast-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
        <div class="card-container">
            <?php if ($agencies_exist): ?>
                <?php while ($agency = $result->fetch_assoc()): ?>
                    <div class="card shadow-sm">
                        <img src="<?= htmlspecialchars($agency['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($agency['name']) ?>">
                        <div class="card-body text-center">
                            <h6 class="card-title"><?= htmlspecialchars($agency['name']) ?></h6>
                            <?php if (!empty($agency['details_link'])): ?>
                                <a href="<?= htmlspecialchars($agency['details_link']) ?>" class="card-button">
                                    View Details
                                </a>
                            <?php else: ?>
                                <span class="card-button">Details Not Available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">No agency found.</div>
            <?php endif; ?>
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

    // Auto-hide toasts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        var toasts = document.querySelectorAll('.toast');
        toasts.forEach(function(toast) {
            setTimeout(function() {
                var bsToast = new bootstrap.Toast(toast);
                bsToast.hide();
            }, 5000);
        });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
<?php $conn->close(); ?>