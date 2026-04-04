<?php
session_start();
include "conn.php";

$query = "SELECT * FROM hardware WHERE id = 9";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$hardware = $result->fetch_assoc();

$transport_routes = array();
if (!empty($hardware['transportation_routes'])) {
    $routes_data = json_decode($hardware['transportation_routes'], true);
    if (is_array($routes_data)) {
        foreach ($routes_data as $route) {
            $transport_type = $route['type'];
            if (!isset($transport_routes[$transport_type])) {
                $transport_routes[$transport_type] = array();
            }
            $transport_routes[$transport_type][] = array(
                'name' => $route['name'],
                'link' => $route['link'],
                'description' => $route['description'] ?? ''
            );
        }
    }
}

if (empty($transport_routes) && !empty($hardware['transportation_options'])) {
    $transport_options = explode("\n", $hardware['transportation_options']);
    foreach ($transport_options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $parts = explode(':', $option, 2);
            $type = trim($parts[0]);
            $name = trim($parts[1] ?? $parts[0]);
            
            if (stripos($type, 'jeepney') !== false) $transport_type = 'jeepney';
            elseif (stripos($type, 'bus') !== false) $transport_type = 'bus';
            elseif (stripos($type, 'taxi') !== false) $transport_type = 'taxi';
            elseif (stripos($type, 'tricycle') !== false || stripos($type, 'motorcycle') !== false) $transport_type = 'tricycle';
            else $transport_type = 'other';
            
            if (!isset($transport_routes[$transport_type])) {
                $transport_routes[$transport_type] = array();
            }
            $transport_routes[$transport_type][] = array(
                'name' => $name,
                'link' => '#',
                'description' => ''
            );
        }
    }
}

function convertToEmbedURL($googleMapsLink) {
    if (strpos($googleMapsLink, 'goo.gl/maps') !== false || strpos($googleMapsLink, 'google.com/maps') !== false) {
        return str_replace("maps/place/", "maps/embed?pb=", $googleMapsLink);
    }
    return $googleMapsLink;
}

$nearby_places = [];
if (!empty($hardware['nearby_places'])) {
    $nearby_places = json_decode($hardware['nearby_places'], true);
    if (!is_array($nearby_places)) {
        $nearby_places = [];
    }
}

$detail_images = array_filter(explode(',', $hardware['image_detail'] ?? ''));
$social_links = array_filter(array_map('trim', explode("\n", $hardware['social_media'] ?? '')));

$hours = [];
if (!empty($hardware['hours'])) {
    $lines = explode("\n", $hardware['hours']);
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($day, $times) = explode(':', $line, 2);
            $hours[trim($day)] = array_map('trim', explode(',', $times));
        }
    }
}

$product_lines = !empty($hardware['product_line'])
    ? array_filter(array_map('trim', explode("\n", $hardware['product_line'])))
    : [];

$branches = [];
if (!empty($hardware['branches'])) {
    $branches = array_filter(array_map('trim', explode("\n", $hardware['branches'])));
}

$brands = [];
if (!empty($hardware['brands'])) {
    $brands = array_filter(array_map('trim', explode("\n", $hardware['brands'])));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hardware['name'] ?? 'Hardware Store') ?> - Iloilo City Hardware</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e30613; 
            --secondary-color: white; 
            --accent-color: #f8b400; 
            --dark-color: #212529;
            --light-color: #f8f9fa;
            --gray-light: #e9ecef;
            --transition: all 0.3s ease;
        }
        
        body {
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
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?= htmlspecialchars($hardware['image'] ?? '') ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
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
            background-color: #004a8d;
            color: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 15px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .info-card .card-body {
            padding: 18px;
        }
        
        /* Quick Facts Card */
        .quick-facts-card {
            position: sticky;
            top: 80px;
        }
        
        .quick-facts-card .card-header {
            background-color: #004a8d;
            color: white;
        }
        
        /* Slideshow */
       .slideshow-container {
            position: relative;
            width: 50%;
            max-width: 700px;
            margin: 25px auto;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            height: 400px;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
        }

        .slide.active {
            opacity: 1;
        }

        .slide img {
            width: 100%;
            height: 400px;
            object-position: center;
        }
        
        .slide-caption {
            position: absolute;
            bottom: 12px;
            left: 12px;
            color: white;
            background-color: rgba(0,0,0,0.7);
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }
        
        .slideshow-controls {
            position: absolute;
            bottom: 12px;
            right: 12px;
            display: flex;
            gap: 6px;
            z-index: 10;
        }
        
        .slideshow-controls button {
            background-color: rgba(14, 12, 12, 0.73);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            backdrop-filter: blur(5px);
            font-size: 0.8rem;
        }
        
        .slideshow-controls button:hover {
            background-color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .slide-indicators {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 10;
        }
        
        .slide-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .slide-indicator.active {
            background-color: white;
            transform: scale(1.2);
        }  
        
        /* Map */
        .map-container {
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        /* Badges */
        .badge-custom {
            background-color: #004a8d;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.75rem;
            transition: var(--transition);
        }
        
        .badge-custom:hover {
            background-color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        /* Brand and Category Badges */
        .brand-badge, .category-badge {
            background-color: #004a8d;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.75rem;
            transition: var(--transition);
            display: inline-block;
        }
        
        .brand-badge:hover, .category-badge:hover {
            background-color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        /* Transport Options - Exactly like apartment1.php */
        .transport-option {
            margin-bottom: 10px;
        }
        
        .transport-header {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 6px;
            background-color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 5px;
        }
        
        .transport-header:hover {
            transform: translateX(2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }
        
        .transport-header.active {
            background-color: #e9ecef;
        }
        
        .transport-icon {
            font-size: 1rem;
            color: var(--primary-color);
            margin-right: 10px;
            width: 30px;
            height: 30px;
            background-color: rgba(106, 90, 205, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .transport-arrow {
            margin-left: auto;
            transition: var(--transition);
        }
        
        .transport-arrow.rotated {
            transform: rotate(180deg);
        }
        
        .route-list {
            background-color: #f8f9fa;
            border-radius: 0 0 6px 6px;
            padding: 15px;
            margin-top: -5px;
            display: none;
            animation: slideDown 0.3s ease-out;
            border: 1px solid #e9ecef;
            border-top: none;
        }
        
        .route-list.active {
            display: block;
        }
        
        .route-list a {
            text-decoration: none;
            color: inherit;
        }
        
        .route-item {
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 5px;
        }
        
        .route-item:last-child {
            margin-bottom: 0;
        }
        
        .route-item:hover {
            background-color: white;
            transform: translateX(5px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .route-item.active {
            background-color: white;
            color: var(--primary-color);
            font-weight: 600;
            border-left: 3px solid var(--primary-color);
        }
        
        .route-number {
            font-weight: 600;
            color: var(--secondary-color);
            margin-right: 8px;
        }
        
        .route-destination {
            color: #666;
        }
        
        .route-description {
            font-size: 0.85rem;
            color: #888;
            margin-top: 3px;
            margin-left: 20px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Social Media */
        .social-btn {
            border-radius: 5px;
            padding: 5px 10px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            margin-right: 6px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        
        .social-btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }
        
        .social-btn.facebook {
            background-color: #1877f2;
            color: white;
        }
        
        .social-btn.twitter {
            background-color: #1da1f2;
            color: white;
        }
        
        .social-btn.instagram {
            background: linear-gradient(45deg, #405de6, #5851db, #833ab4, #c13584, #e1306c, #fd1d1d);
            color: white;
        }
        
        .social-btn.website {
            background-color: #004a8d;
            color: white;
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
            
            .slideshow-container {
                width: 90%;
                height: 60px;
                margin: 20px auto;
            }
             .slide img {
            width: 100%;
            height: 220px;
        }
            
            .section-title {
                font-size: 1.3rem;
                margin-bottom: 15px;
            }
            
            .content-section {
                margin-bottom: 30px;
            }
            
            .map-container {
                height: 250px;
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
            
            .mobile-side-by-side .info-card .card-body p {
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }
            
            .mobile-side-by-side .info-card .card-body h6 {
                font-size: 0.9rem;
                margin-bottom: 0.3rem;
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
            
            .slideshow-container {
                height: 220px;
            }
            
            .section-title {
                font-size: 1.2rem;
            }
            
            .info-card .card-header {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            
            .info-card .card-body {
                padding: 15px;
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
        }
        
        @media (max-width: 400px) {
            .hero-section h1 {
                font-size: 1.2rem;
            }
            
            .slideshow-container {
                height: 180px;
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

        /* Ensure consistent spacing and proportions */
        .container {
            max-width: 1200px;
        }
        
        .row {
            margin-left: -10px;
            margin-right: -10px;
        }
        
        .col, [class*="col-"] {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .btn {
            padding: 6px 12px;
            font-size: 0.85rem;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        
        .lead {
            font-size: 1rem;
        }
        
        .display-4 {
            font-size: 2rem;
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
  <section class="hero-section" style="background:linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?= htmlspecialchars($hardware['image'] ?? '') ?>') center/cover no-repeat;">
    <div class="container">
      <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($hardware['name'] ?? '') ?></h1>
      <p class="lead"><?= htmlspecialchars($hardware['location'] ?? '') ?></p>
    </div>
  </section>

  <main class="container mb-4">
    <section class="content-section">
      <h2 class="section-title">About <?= htmlspecialchars($hardware['name'] ?? '') ?></h2>
      <div class="lead mb-4">
        <?= nl2br(htmlspecialchars($hardware['description'] ?? '')) ?>
      </div>

      <?php if (!empty($detail_images)): ?>
        <div class="slideshow-container">
          <?php foreach ($detail_images as $index => $image): ?>
            <div class="slide <?= $index === 0 ? 'active' : '' ?>">
              <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($hardware['name']) ?> Image <?= $index + 1 ?>">
              <div class="slide-caption">Image <?= $index + 1 ?> of <?= count($detail_images) ?></div>
            </div>
          <?php endforeach; ?>
          <div class="slideshow-controls">
            <button id="prev-slide"><i class="fas fa-chevron-left"></i></button>
            <button id="next-slide"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="slide-indicators">
            <?php foreach ($detail_images as $index => $image): ?>
              <div class="slide-indicator <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <section class="content-section">
        <h2 class="section-title">🛠️ Hardware Store Information</h2>
        
        <!-- Desktop-->
        <div class="row d-none d-md-flex">
            <div class="col-md-6 mb-3">
                <div class="card info-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">🏬 Store Branches</h5>
                    </div>
                     <div class="card-body">
                        <?php if (!empty($branches)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($branches as $branch): ?>
                                    <li class="list-group-item border-0 px-0 py-1">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-map-marker-alt text-danger mt-1 me-2"></i>
                                            <span class="small"><?= htmlspecialchars($branch) ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="mb-0 small">No branch information available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card info-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">🔧 Services</h5>
                    </div>
                    <div class="card-body">
                                <p class="mb-2 "><?= htmlspecialchars($hardware['services'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile -->
        <div class="d-md-none mobile-side-by-side">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card info-card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">🏬 Branches</h6>
                        </div>
                        <div class="card-body small">
                            <?php if (!empty($branches)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($branches as $branch): ?>
                                        <li class="list-group-item border-0 px-0 py-1">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-map-marker-alt text-danger mt-1 me-2"></i>
                                                <span class="small"><?= htmlspecialchars($branch) ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="mb-0 small">No branch information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card info-card h-100">
                       <div class="card-header">
                        <h6 class="mb-0">🔧 Services</h6>
                    </div>
                    <div class="card-body small">
                                <p class="mb-2 small"><?= htmlspecialchars($hardware['services'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content-section">
        <h2 class="section-title">ℹ️ Practical Information</h2>
        
        <!-- Desktop -->
        <div class="row d-none d-md-flex">
            <div class="col-lg-7 mb-3">
                <div class="card info-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Visitor Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5 mb-2">
                                <h6 class="fw-bold">🕒 Hours</h6>
                             <?= nl2br(htmlspecialchars($hardware['hours'])) ?>

                            </div>
                            
                            <div class="col-md-7 mb-2">
                            <p class="mb-2">📲 Phone: <?= htmlspecialchars($hardware['contact'] ?? '') ?></p>
                            <?php if (!empty($hardware['email'])): ?>
                                <p class="mb-2">📧 Email: <?= htmlspecialchars($hardware['email']) ?> </p>
                            <?php endif; ?>
                            <?php if (!empty($hardware['website'])): ?>
                                <p class="mb-2">🌐 Website: <a href="<?= htmlspecialchars($hardware['website']) ?>" target="_blank" class="text-decoration-none"> Visit Website</a></p>
                            <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-5 mb-3">
                <div class="card info-card h-100">
                      <div class="card-header">
                        <h5 class="mb-0">💡 Facts</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2 small"><strong>📅 Established:</strong> <?= htmlspecialchars($hardware['established'] ?? '') ?></p>
                                <p class="mb-2 small"><strong>🔩 Product Lines:</strong> <?= htmlspecialchars($hardware['product_line'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2 small"><strong>💰 Price Range:</strong> <?= htmlspecialchars($hardware['price_range'] ?? '') ?></p>
                                <p class="mb-2 small"><strong>💳 Payment Options:</strong> <?= htmlspecialchars($hardware['payment_options'] ?? '') ?></p>
                                <p class="mb-2 small"><strong>🚚 Delivery Options:</strong> <?= htmlspecialchars($hardware['delivery_options'] ?? '') ?></p>
                                <p class="mb-2 small"><strong>🔧 Warranty Info:</strong> <?= htmlspecialchars($hardware['warranty'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile  -->
        <div class="d-md-none mobile-side-by-side">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card info-card h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i> Details</h6>
                        </div>
                        <div class="card-body small">
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <h6 class="fw-bold small">🕒 Hours</h6>
                                    <?= nl2br(htmlspecialchars($hardware['hours'])) ?>
                                </div>
                                <br>
                                <p class="mb-2">📲 Phone: <?= htmlspecialchars($hardware['contact'] ?? '') ?></p>
                                <?php if (!empty($hardware['email'])): ?>
                                    <p class="mb-2 small">📧 Email: <?= htmlspecialchars($hardware['email']) ?> </p>
                                <?php endif; ?>
                                <?php if (!empty($hardware['website'])): ?>
                                    <p class="mb-2 small">🌐 Website: <a href="<?= htmlspecialchars($hardware['website']) ?>" target="_blank" class="text-decoration-none"> Visit Website</a></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card info-card h-100">
                         <div class="card-header">
                            <h6 class="mb-0">💡 Facts</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <p class="mb-1 small"><strong>📅 Established:</strong> <?= htmlspecialchars($hardware['established'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>🔩 Product Lines:</strong> <?= htmlspecialchars($hardware['product_line'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>💰 Price Range:</strong> <?= htmlspecialchars($hardware['price_range'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>💳 Payment Options:</strong> <?= htmlspecialchars($hardware['payment_options'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>🚚 Delivery Options:</strong> <?= htmlspecialchars($hardware['delivery_options'] ?? '') ?></p>
                                    <p class="mb-1 small"><strong>🔧 Warranty Info:</strong> <?= htmlspecialchars($hardware['warranty'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </section>

    <section class="content-section">
        <h2 class="section-title">📍 Location & Directions</h2>
        
        <div class="row">
            <div class="col-lg-8 mb-3">
                <?php if (!empty($hardware['maps_embed'])): ?>
                    <div class="map-container">
                        <iframe src="<?= htmlspecialchars(convertToEmbedURL($hardware['maps_embed'])) ?>" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info py-2 small">Map not available</div>
                <?php endif; ?>
            </div>
            
            <!-- Nearby Places Card -->
            <div class="col-lg-4">
                <div class="card info-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">🗺️ Nearby Places</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($nearby_places)): ?>
                            <p class="mb-2 small">Explore these places near <?= htmlspecialchars($hardware['name'] ?? '') ?>:</p>
                            <div class="d-flex flex-wrap">
                                <?php foreach ($nearby_places as $place): ?>
                                    <span class="badge bg-danger badge-custom"><?= htmlspecialchars($place) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info py-2 small">No nearby places listed.</div>
                        <?php endif; ?>
                        
                        <hr class="my-3">
                        
                        <h5 class="mb-2 small fw-bold">🔗 Links</h5>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (!empty($hardware['maps_link'])): ?>
                                <a href="<?= htmlspecialchars($hardware['maps_link']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                                    <i class="fas fa-map-marked-alt me-1"></i> Maps
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($hardware['directions'])): ?>
                                <a href="<?= htmlspecialchars($hardware['directions']) ?>" class="btn btn-primary btn-sm" target="_blank">
                                    <i class="fas fa-directions me-1"></i> Directions
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="card info-card">
                    <div class="card-header">
                        <h5 class="mb-0">🚗 Transportation Information</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $transport_types = array(
                            'jeepney' => array('icon' => 'fas fa-bus', 'title' => 'Jeepney'),
                            'bus' => array('icon' => 'fas fa-bus-alt', 'title' => 'Bus'),
                            'taxi' => array('icon' => 'fas fa-taxi', 'title' => 'Taxi'),
                            'tricycle' => array('icon' => 'fas fa-motorcycle', 'title' => 'Tricycle')
                        );
                        
                        foreach ($transport_types as $type => $type_info): 
                            $routes = isset($transport_routes[$type]) ? $transport_routes[$type] : array();
                        ?>
                        <div class="transport-option">
                            <div class="transport-header" onclick="toggleTransport('<?php echo $type; ?>')" id="header-<?php echo $type; ?>">
                                <div class="transport-icon">
                                    <i class="<?php echo $type_info['icon']; ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 small fw-bold"><?php echo $type_info['title']; ?></h6>
                                    <p class="mb-0 small text-muted">Click to see available routes</p>
                                </div>
                                <div class="transport-arrow" id="arrow-<?php echo $type; ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                            <div class="route-list" id="route-<?php echo $type; ?>">
                                <?php if (!empty($routes)): ?>
                                    <?php foreach ($routes as $index => $route): ?>
                                        <a href="<?php echo htmlspecialchars($route['link']); ?>" target="_blank" class="text-decoration-none">
                                            <div class="route-item" onclick="selectRoute(this, '<?php echo htmlspecialchars($route['name']); ?>')">
                                                <span class="route-destination"><?php echo htmlspecialchars($route['name']); ?></span>
                                                <?php if (!empty($route['description'])): ?>
                                                    <div class="route-description">
                                                        <i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars($route['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info py-2 small">No routes available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="content-section">
        <div class="row">
            <div class="col-12">
                <?php if (!empty($social_links)): ?>
                    <div class="card info-card">
                        <div class="card-header">
                            <h5 class="mb-0">📱 Connect With Us</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap">
                                <?php foreach ($social_links as $link): ?>
                                    <?php
                                        if (strpos($link, 'facebook.com') !== false) {
                                            $class = 'facebook';
                                            $icon = 'facebook-f';
                                            $text = 'Facebook';
                                        } elseif (strpos($link, 'instagram.com') !== false) {
                                            $class = 'instagram';
                                            $icon = 'instagram';
                                            $text = 'Instagram';
                                        } elseif (strpos($link, 'twitter.com') !== false) {
                                            $class = 'twitter';
                                            $icon = 'twitter';
                                            $text = 'Twitter';
                                        } else {
                                            $class = 'website';
                                            $icon = 'globe';
                                            $text = 'Website';
                                        }
                                    ?>
                                    <a href="<?= htmlspecialchars($link) ?>" class="social-btn <?= $class ?>" target="_blank">
                                        <i class="fab fa-<?= $icon ?> me-1"></i> <?= $text ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
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
        <p class="small text-white mb-0">&copy; Iloilo City Tourism Office. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>
   
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.slide');
    const indicators = document.querySelectorAll('.slide-indicator');
    const prevBtn = document.getElementById('prev-slide');
    const nextBtn = document.getElementById('next-slide');
    
    if (slides.length === 0) return;
    
    let currentSlide = 0;
    let slideInterval;
    const slideDuration = 5000; 
    
    function showSlide(index) {
        if (index >= slides.length) index = 0;
        if (index < 0) index = slides.length - 1;
        
        slides.forEach(slide => slide.classList.remove('active'));
        indicators.forEach(indicator => indicator.classList.remove('active'));
        
        slides[index].classList.add('active');
        indicators[index].classList.add('active');
        currentSlide = index;
    }
    
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    function startSlideShow() {
        slideInterval = setInterval(nextSlide, slideDuration);
    }
    
    function stopSlideShow() {
        clearInterval(slideInterval);
    }
    
    showSlide(0);
    startSlideShow();
    
    nextBtn.addEventListener('click', function() {
        stopSlideShow();
        nextSlide();
        startSlideShow();
    });
    
    prevBtn.addEventListener('click', function() {
        stopSlideShow();
        showSlide(currentSlide - 1);
        startSlideShow();
    });
    
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            stopSlideShow();
            showSlide(index);
            startSlideShow();
        });
    });
    
    const slideshowContainer = document.querySelector('.slideshow-container');
    if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', stopSlideShow);
        slideshowContainer.addEventListener('mouseleave', startSlideShow);
    }
});

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

function toggleTransport(type) {
    const header = document.getElementById('header-' + type);
    const arrow = document.getElementById('arrow-' + type);
    const routeList = document.getElementById('route-' + type);
    
    header.classList.toggle('active');
    
    arrow.classList.toggle('rotated');
    
    routeList.classList.toggle('active');
    
    const allTypes = ['jeepney', 'bus', 'taxi', 'tricycle', 'other'];
    allTypes.forEach(otherType => {
        if (otherType !== type) {
            const otherHeader = document.getElementById('header-' + otherType);
            const otherArrow = document.getElementById('arrow-' + otherType);
            const otherRouteList = document.getElementById('route-' + otherType);
            
            if (otherHeader && otherArrow && otherRouteList) {
                otherHeader.classList.remove('active');
                otherArrow.classList.remove('rotated');
                otherRouteList.classList.remove('active');
            }
        }
    });
}

function selectRoute(element, routeName) {
    const list = element.parentElement.parentElement;
    const items = list.querySelectorAll('.route-item');
    items.forEach(item => item.classList.remove('active'));
    
    element.classList.add('active');
}

document.addEventListener('click', function(event) {
    const transportHeaders = document.querySelectorAll('.transport-header');
    
    transportHeaders.forEach(header => {
        if (!header.contains(event.target)) {
            const type = header.id.replace('header-', '');
            const arrow = document.getElementById('arrow-' + type);
            const routeList = document.getElementById('route-' + type);
            
            if (routeList && routeList.classList.contains('active')) {
                header.classList.remove('active');
                if (arrow) arrow.classList.remove('rotated');
                routeList.classList.remove('active');
            }
        }
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>