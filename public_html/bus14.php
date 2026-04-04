<?php
session_start();
include 'conn.php';

if (isset($_SESSION['search_trigger'])) {
    $searchData = $_SESSION['search_trigger'];
    unset($_SESSION['search_trigger']);
}

$query = "SELECT * FROM bus WHERE id = 14"; 
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: transportations.php");
    exit();
}

$bus = $result->fetch_assoc();

function parseBusOperators($operators_data) {
    $operators = [];
    if (!$operators_data) return $operators;
    
    $lines = explode("\n", $operators_data);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) continue;
        
        if (strpos($line, '|') !== false) {
            $parts = explode('|', $line);
            
            if (count($parts) >= 5) {
                $company_name = str_replace('_', ' ', $parts[0]);
                $operating_hours = $parts[1];
                $contact_number = $parts[2];
                $website = $parts[3];
                $address = str_replace('_', ' ', $parts[4]);
                
                $social_media = [];
                for ($i = 5; $i < count($parts); $i++) {
                    $social_link = $parts[$i];
                    if ($social_link) {
                        $social_media[] = $social_link;
                    }
                }
                
                if ($company_name) {
                    $operators[] = [
                        'name' => $company_name,
                        'operating_hours' => $operating_hours,
                        'contact' => $contact_number,
                        'website' => $website,
                        'address' => $address,
                        'social_media' => $social_media
                    ];
                }
            }
        } else {
            $parts = explode(' ', $line);
            
            if (count($parts) >= 7) {
                $company_name = str_replace('_', ' ', $parts[0]);
                $operating_hours = $parts[1];
                $contact_number = $parts[2];
                $website = $parts[3];
                $address = str_replace('_', ' ', $parts[4]);
                $peak_hours = $parts[5];
                $off_hours = $parts[6];
                
                $social_media = [];
                for ($i = 7; $i < count($parts); $i++) {
                    $social_link = $parts[$i];
                    if ($social_link) {
                        $social_media[] = $social_link;
                    }
                }
                
                if ($company_name) {
                    $operators[] = [
                        'name' => $company_name,
                        'operating_hours' => $operating_hours,
                        'contact' => $contact_number,
                        'website' => $website,
                        'address' => $address,
                        'social_media' => $social_media
                    ];
                }
            }
        }
    }
    
    return $operators;
}

$bus_operators = parseBusOperators($bus['bus_operators']);

$detail_images = $bus['image_detail'] ? explode(',', $bus['image_detail']) : [];
$routes = $bus['routes'] ? explode("\n", $bus['routes']) : [];

$total_stops = count($routes);
$total_operators = count($bus_operators);

if (empty($bus['image'])) {
    $bus['image'] = 'img/default-bus.jpg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($bus['name'] ?? 'Bus Route') ?> - Iloilo Transportation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('<?= htmlspecialchars($bus['image']) ?>');
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
            color: var(--secondary-color);
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
        
        /* Operator Companies Card */
        .operator-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        
        .operator-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .operator-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #004a8d, #0066cc);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .operator-icon i {
            font-size: 1rem;
            color: white;
        }
        
        .operator-name {
            font-weight: 600;
            color: #004a8d;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .operator-address {
            font-size: 0.85rem;
            color: #666;
        }
        
        .operator-info {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .operator-info i {
            width: 20px;
            color: #e30613;
            margin-right: 10px;
        }
        
        .operator-info-label {
            font-weight: 500;
            color: #555;
            min-width: 100px;
        }
        
        /* Peak/Off Hours Styles - CENTERED */
        .hours-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .hours-badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .peak-hours-badge {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .peak-hours-badge i {
            color: #e74c3c;
            margin-right: 5px;
        }
        
        .off-hours-badge {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .off-hours-badge i {
            color: #2ecc71;
            margin-right: 5px;
        }
        
        /* Social Media Styles */
        .social-buttons-mobile {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .social-btn-mobile {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 0.65rem;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .social-btn-mobile:hover {
            transform: translateY(-1px);
        }
        
        .social-btn-mobile.facebook:hover {
            background: #1877f2;
            color: white;
            border-color: #1877f2;
        }
        
        .social-btn-mobile.instagram:hover {
            background: linear-gradient(45deg, #405de6, #833ab4);
            color: white;
            border-color: transparent;
        }
        
        .social-btn-mobile.twitter:hover {
            background: #1da1f2;
            color: white;
            border-color: #1da1f2;
        }
        
        /* Website Link */
        .website-link-mobile {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 12px;
            font-size: 0.65rem;
            color: #555;
            text-decoration: none;
            margin-top: 6px;
            transition: all 0.2s;
        }
        
        .website-link-mobile:hover {
            background: #004a8d;
            color: white;
            border-color: #004a8d;
            transform: translateY(-1px);
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
            object-fit: cover;
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
        
        /* Route List */
        .route-list {
            list-style-type: none;
            padding-left: 0;
            max-height: 700px;
            overflow-y: auto;
        }
        
        .route-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: flex-start;
        }
        
        .route-list li:last-child {
            border-bottom: none;
        }
        
        .route-list li i {
            color: var(--primary-color);
            margin-right: 15px;
            margin-top: 3px;
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
            background-color: var(--secondary-color);
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
        
        @media (max-width: 768px) {
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
            
            .mobile-side-by-side .info-card {
                margin-bottom: 8px;
            }
            
            .mobile-side-by-side .info-card .card-header {
                padding: 8px 10px;
                font-size: 0.85rem;
                min-height: 40px;
                display: flex;
                align-items: center;
            }
            
            .mobile-side-by-side .info-card .card-body {
                padding: 10px;
            }
            
            .mobile-side-by-side .route-list {
                max-height: 500px;
            }
            
            .mobile-side-by-side .route-list li {
                padding: 6px 0;
                font-size: 0.75rem; 
                line-height: 1.3;
            }
            
            .mobile-side-by-side .route-list li i {
                font-size: 0.6rem;
                margin-right: 8px;
                margin-top: 2px;
            }
            
            .mobile-side-by-side .operator-card {
                padding: 6px; 
                margin-bottom: 8px;
            }
            
            .mobile-side-by-side .operator-header {
                margin-bottom: 6px;
                padding-bottom: 6px;
            }
            
            .mobile-side-by-side .operator-icon {
                width: 25px; 
                height: 25px;
                margin-right: 8px;
            }
            
            .mobile-side-by-side .operator-icon i {
                font-size: 0.7rem;
            }
            
            .mobile-side-by-side .operator-name {
                font-size: 0.8rem; 
                line-height: 1.2;
                margin-bottom: 2px;
            }
            
            .mobile-side-by-side .operator-address {
                font-size: 0.7rem;
                line-height: 1.1;
            }
            
            .mobile-side-by-side .operator-info {
                margin-bottom: 4px;
                font-size: 0.7rem; 
                line-height: 1.1;
            }
            
            .mobile-side-by-side .operator-info i {
                font-size: 0.6rem;
                margin-right: 6px;
                width: 14px;
            }
            
            .mobile-side-by-side .operator-info-label {
                min-width: 60px; 
                font-size: 0.7rem; 
            }
            
            .mobile-side-by-side .operator-info span {
                font-size: 0.7rem; 
            }
            
            /* Mobile Peak/Off Hours - CENTERED */
            .mobile-side-by-side .hours-container {
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 4px;
                margin-bottom: 8px;
            }
            
            .mobile-side-by-side .hours-badge {
                padding: 2px 6px;
                font-size: 0.55rem;
            }
            
            .mobile-side-by-side .hours-badge i {
                font-size: 0.5rem;
                margin-right: 3px;
            }
            
            .mobile-side-by-side .website-link-mobile {
                padding: 2px 6px;
                font-size: 0.6rem; 
                margin-top: 4px;
            }
            
            .mobile-side-by-side .website-link-mobile i {
                font-size: 0.6rem;
                margin-right: 4px;
            }
            
            .mobile-side-by-side .social-buttons-mobile {
                gap: 3px;
                margin-top: 5px;
            }
            
            .mobile-side-by-side .social-btn-mobile {
                padding: 2px 5px;
                font-size: 0.55rem; 
            }
            
            .mobile-side-by-side .social-btn-mobile i {
                font-size: 0.55rem;
                margin-right: 3px;
            }
            
            .mobile-side-by-side .btn {
                padding: 4px 8px;
                font-size: 0.7rem; 
            }
            
            .mobile-side-by-side .info-card .card-body p {
                font-size: 0.75rem; 
                margin-bottom: 0.4rem;
                line-height: 1.3;
            }
            
            .mobile-side-by-side .info-card .card-body h6 {
                font-size: 0.8rem; 
                margin-bottom: 0.3rem;
            }
            
            .slideshow-container {
                height: 500px;
                width: 400px;
            }
            
            .slide img {
                height: 200px;
                width: 400px;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            body {
                font-size: 13px;
            }
            
            .hero-section {
                padding: 30px 0;
            }
            
            .hero-section h1 {
                font-size: 1.2rem;
            }
            
            .hero-section p {
                font-size: 0.8rem;
            }
            
            .slideshow-container {
                height: 200px;
            }
            
            .slide img {
                height: 200px;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .info-card .card-header {
                padding: 10px 12px;
                font-size: 0.85rem;
            }
            
            .info-card .card-body {
                padding: 12px;
            }
            
            .hours-badge {
                padding: 3px 8px;
                font-size: 0.7rem;
            }
            
            .footer-logo {
                width: 100px;
            }
            
            .mobile-side-by-side .info-card .card-header {
                font-size: 0.75rem;
                padding: 6px 8px;
            }
            
            .mobile-side-by-side .route-list li {
                font-size: 0.7rem; 
                padding: 5px 0;
            }
            
            .mobile-side-by-side .operator-name {
                font-size: 0.75rem; 
            }
            
            .mobile-side-by-side .operator-address {
                font-size: 0.65rem;
            }
            
            .mobile-side-by-side .operator-info {
                font-size: 0.65rem;
            }
            
            .mobile-side-by-side .operator-info-label {
                font-size: 0.65rem; 
            }
            
            .mobile-side-by-side .operator-info span {
                font-size: 0.65rem; 
            }
            
            .mobile-side-by-side .hours-badge {
                padding: 1px 4px;
                font-size: 0.5rem;
            }
            
            .mobile-side-by-side .website-link-mobile {
                font-size: 0.55rem; 
            }
            
            .mobile-side-by-side .social-btn-mobile {
                font-size: 0.5rem; 
            }
        }
        
        @media (max-width: 400px) {
            body {
                font-size: 12px;
            }
            
            .hero-section h1 {
                font-size: 1.1rem;
            }
            
            .slideshow-container {
                height: 180px;
            }
            
            .slide img {
                height: 180px;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            .sidebar.open {
                width: 250px;
            }
            
            .mobile-side-by-side .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .mobile-side-by-side .info-card .card-header {
                font-size: 0.7rem;
            }
            
            .mobile-side-by-side .route-list li {
                font-size: 0.65rem; 
            }
            
            .mobile-side-by-side .operator-name {
                font-size: 0.7rem; 
            }
            
            .mobile-side-by-side .operator-address {
                font-size: 0.6rem; 
            }
            
            .mobile-side-by-side .operator-info {
                font-size: 0.6rem;
            }
            
            .mobile-side-by-side .hours-badge {
                font-size: 0.45rem;
                padding: 1px 3px;
            }
        }

        @media (min-width: 769px) {
            .mobile-side-by-side {
                display: none;
            }
        }

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

    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($bus['name']) ?></h1>
            <p class="lead"><?= htmlspecialchars($bus['location']) ?></p>
        </div>
    </section>

    <main class="container mb-4">
        <section class="content-section">
            <h2 class="section-title">About <?= htmlspecialchars($bus['name']) ?></h2>
            <div class="lead mb-4">
                <?= nl2br(htmlspecialchars($bus['description'])) ?>
            </div>

            <?php if (!empty($detail_images)): ?>
                <div class="slideshow-container">
                    <?php foreach ($detail_images as $index => $image): ?>
                        <div class="slide <?= $index === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars(trim($image)) ?>" alt="Bus Route Detail <?= $index + 1 ?>" loading="lazy">
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
            <h2 class="section-title">🚌 Route & Operator Information</h2>
            
            <?php if ($bus['peak_hours'] || $bus['off_hours']): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card info-card">
                        <div class="card-header">
                            <h5 class="mb-0">⏰ Bus Schedule Hours</h5>
                        </div>
                        <div class="card-body">
                            <div class="hours-container">
                                <?php if ($bus['peak_hours']): ?>
                                    <div class="hours-badge peak-hours-badge">
                                        <i class="fas fa-users"></i>
                                        Peak Hours: <?= htmlspecialchars($bus['peak_hours']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($bus['off_hours']): ?>
                                    <div class="hours-badge off-hours-badge">
                                        <i class="fas fa-user-friends"></i>
                                        Off Hours: <?= htmlspecialchars($bus['off_hours']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Desktop -->
            <div class="row d-none d-md-flex">
                <div class="col-lg-6 mb-4">
                    <div class="card info-card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">🛣️ Route Details</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($routes)): ?>
                                <ul class="route-list">
                                    <?php foreach ($routes as $route): ?>
                                        <li><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(trim($route)) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No route information available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 mb-4">
                    <div class="card info-card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">🏢 Operator Companies (<?= $total_operators ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($bus_operators)): ?>
                                <?php foreach ($bus_operators as $company): ?>
                                    <div class="operator-card">
                                        <div class="operator-header">
                                            <div class="operator-icon">
                                                <i class="fas fa-bus"></i>
                                            </div>
                                            <div>
                                                <div class="operator-name"><?= htmlspecialchars($company['name']) ?></div>
                                                <div class="operator-address">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars(substr($company['address'], 0, 50) . (strlen($company['address']) > 50 ? '...' : '')) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="operator-info">
                                            <i class="fas fa-clock"></i>
                                            <span class="operator-info-label">Operating Hours: </span>
                                            <span><?= htmlspecialchars($company['operating_hours']) ?></span>
                                        </div>

                                        <div class="operator-info">
                                            <i class="fas fa-phone"></i>
                                            <span class="operator-info-label">Contact:</span>
                                            <span><?= htmlspecialchars($company['contact']) ?></span>
                                        </div>

                                        <?php if ($company['website']): ?>
                                        <div class="operator-info">
                                            <i class="fas fa-globe"></i>
                                            <span class="operator-info-label">Website:</span>
                                            <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" 
                                               class="text-decoration-none text-primary">
                                                Visit Site
                                            </a>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($company['social_media']): ?>
                                        <div class="social-buttons-mobile">
                                            <?php foreach ($company['social_media'] as $social): 
                                                $platform = '';
                                                $btn_class = '';
                                                if (strpos($social, 'facebook') !== false) {
                                                    $platform = 'Facebook';
                                                    $btn_class = 'facebook';
                                                } elseif (strpos($social, 'instagram') !== false) {
                                                    $platform = 'Instagram';
                                                    $btn_class = 'instagram';
                                                } elseif (strpos($social, 'twitter') !== false) {
                                                    $platform = 'Twitter';
                                                    $btn_class = 'twitter';
                                                } else {
                                                    $platform = 'Social';
                                                    $btn_class = '';
                                                }
                                            ?>
                                            <a href="https://<?= htmlspecialchars($social) ?>" 
                                               target="_blank"
                                               class="social-btn-mobile <?= $btn_class ?>">
                                                <i class="fab fa-<?= strtolower($platform) == 'facebook' ? 'facebook-f' : (strtolower($platform) == 'instagram' ? 'instagram' : (strtolower($platform) == 'twitter' ? 'twitter' : 'link')) ?> me-1"></i>
                                                <?= htmlspecialchars($platform) ?>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No operator information available.</p>
                            <?php endif; ?>
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
                                <h6 class="mb-0">🛣️ Route Details</h6>
                            </div>
                            <div class="card-body small">
                                <?php if (!empty($routes)): ?>
                                    <ul class="route-list">
                                        <?php foreach ($routes as $route): ?>
                                            <li><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars(trim($route)) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="small">No route information available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card info-card h-100">
                            <div class="card-header">
                                <h6 class="mb-0">🏢 Operators (<?= $total_operators ?>)</h6>
                            </div>
                            <div class="card-body small">
                                <?php if (!empty($bus_operators)): ?>
                                    <?php foreach ($bus_operators as $company): ?>
                                        <div class="operator-card">
                                            <div class="operator-header">
                                                <div class="operator-icon">
                                                    <i class="fas fa-bus"></i>
                                                </div>
                                                <div>
                                                    <div class="operator-name"><?= htmlspecialchars(substr($company['name'], 0, 25) . (strlen($company['name']) > 25 ? '...' : '')) ?></div>
                                                    <div class="operator-address">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= htmlspecialchars(substr($company['address'], 0, 30) . (strlen($company['address']) > 30 ? '...' : '')) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="operator-info">
                                                <i class="fas fa-clock"></i>
                                                <span class="operator-info-label">Hours: </span>
                                                <span><?= htmlspecialchars($company['operating_hours']) ?></span>
                                            </div>

                                            <div class="operator-info">
                                                <i class="fas fa-phone"></i>
                                                <span class="operator-info-label">Contact:</span>
                                                <span><?= htmlspecialchars($company['contact']) ?></span>
                                            </div>

                                            <?php if ($company['website']): ?>
                                            <div>
                                                <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" 
                                                   class="website-link-mobile">
                                                    <i class="fas fa-globe me-1"></i>
                                                    Website
                                                </a>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($company['social_media']): ?>
                                            <div class="social-buttons-mobile">
                                                <?php foreach ($company['social_media'] as $social): 
                                                    $platform = '';
                                                    $btn_class = '';
                                                    if (strpos($social, 'facebook') !== false) {
                                                        $platform = 'Facebook';
                                                        $btn_class = 'facebook';
                                                    } elseif (strpos($social, 'instagram') !== false) {
                                                        $platform = 'Instagram';
                                                        $btn_class = 'instagram';
                                                    } elseif (strpos($social, 'twitter') !== false) {
                                                        $platform = 'Twitter';
                                                        $btn_class = 'twitter';
                                                    } else {
                                                        $platform = 'Social';
                                                        $btn_class = '';
                                                    }
                                                ?>
                                                <a href="https://<?= htmlspecialchars($social) ?>" 
                                                   target="_blank"
                                                   class="social-btn-mobile <?= $btn_class ?>">
                                                    <i class="fab fa-<?= strtolower($platform) == 'facebook' ? 'facebook-f' : (strtolower($platform) == 'instagram' ? 'instagram' : (strtolower($platform) == 'twitter' ? 'twitter' : 'link')) ?> me-1"></i>
                                                    <?= htmlspecialchars($platform) ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="small">No operators available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Map Section -->
        <section class="content-section">
            <h2 class="section-title">📍 Route Map & Directions</h2>
            
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <?php if (!empty($bus['maps_embed'])): ?>
                        <div class="map-container">
                            <iframe src="<?= htmlspecialchars($bus['maps_embed']) ?>" width="100%" height="100%" style="border:0;" allowfullscreen loading="lazy"></iframe>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">Map not available</div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="card info-card quick-facts-card">
                        <div class="card-header">
                            <h5 class="mb-0">🔗 Useful Links</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-1">
                                <?php if (!empty($bus['maps_link'])): ?>
                                    <a href="<?= htmlspecialchars($bus['maps_link']) ?>" class="btn btn-outline-primary btn-lg" target="_blank">
                                        <i class="fas fa-map-marked-alt me-1"></i> Maps
                                    </a>
                                <?php endif; ?>
                                <a href="transportations.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openSidebar() {
            document.getElementById("mySidebar").classList.add("open");
            document.getElementById("sidebarOverlay").classList.add("active");
        }

        function closeSidebar() {
            document.getElementById("mySidebar").classList.remove("open");
            document.getElementById("sidebarOverlay").classList.remove("active");
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            const indicators = document.querySelectorAll('.slide-indicator');
            const prevBtn = document.getElementById('prev-slide');
            const nextBtn = document.getElementById('next-slide');
            
            let currentSlide = 0;
            
            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                indicators.forEach(indicator => indicator.classList.remove('active'));
                
                currentSlide = (index + slides.length) % slides.length;
                slides[currentSlide].classList.add('active');
                indicators[currentSlide].classList.add('active');
            }
            
            if (prevBtn && nextBtn) {
                prevBtn.addEventListener('click', () => showSlide(currentSlide - 1));
                nextBtn.addEventListener('click', () => showSlide(currentSlide + 1));
            }
            
            indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => showSlide(index));
            });
            
            if (slides.length > 1) {
                setInterval(() => showSlide(currentSlide + 1), 5000);
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>