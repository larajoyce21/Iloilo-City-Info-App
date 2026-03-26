<?php
session_start();
include "conn.php";

$result = $conn->query("SELECT * FROM popular");

$query = "SELECT * FROM popular";
$popular_exist = false;

if ($stmt = $conn->prepare($query)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $popular_exist = $result->num_rows > 0;
    $stmt->close();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Popular Tourist Attractions in Iloilo City">
    <meta name="author" content="Iloilo City Info App">
    <title>Popular - Iloilo City</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" href="img/favicon.ico" type="image/x-icon">

    <style>
        :root {
            --primary-color: #508acb;
            --secondary-color: #545454;
            --light-color: #ffffff;
            --transition: all 0.3s ease;
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
        /* Page Header */
        .page-header {
            padding: 1.5rem 0 1rem 0;
            position: relative;
            overflow: hidden;
        }

        .page-header-container {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .page-title-section {
            flex: 1;
            min-width: 300px;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
            margin-bottom: 0.25rem;
            position: relative;
            padding-bottom: 0;
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            line-height: 1.1;
        }

        .page-subtitle {
            font-family: 'Playfair Display', serif;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            margin-bottom: 0.5rem;
            font-weight: 400;
        }

        .page-description {
            color: rgba(255, 255, 255, 0.9);
            font-size: clamp(0.9rem, 2vw, 1.2rem);
            max-width: 600px;
            margin-top: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), #fff);
        }

        /* Itinerary Container on the Right Side - Image Size */
        .itinerary-side-container {
            flex: 0 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-left: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
            border: 3px solid #7D0A0A;
            width: 250px;
            height: 200px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .itinerary-side-container:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .itinerary-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(125, 10, 10, 0.9), rgba(84, 84, 84, 0.9));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: var(--transition);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .itinerary-side-container:hover .itinerary-overlay {
            opacity: 1;
        }

        .itinerary-header {
            text-align: center;
        }

        .itinerary-title {
            font-family: 'Playfair Display', serif;
            color: #7D0A0A;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .itinerary-subtitle {
            font-family: 'Poppins', sans-serif;
            color: #555;
            font-size: 0.9rem;
            font-style: italic;
        }

        .overlay-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .overlay-text {
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .click-icon {
            font-size: 2rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Mobile adjustments - Itinerary ALWAYS on right side */
        @media (max-width: 992px) {
            .page-header {
                padding: 1.2rem 0 0.8rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: flex-start;
            }
            
            .itinerary-side-container {
                width: 300px;
                height: 250px;
                margin-left: 1rem;
            }
            
            .page-title-section {
                padding-right: 1rem;
            }
            
            .itinerary-title {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1rem 0 0.5rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: flex-start;
                justify-content: space-between;
            }
            
            .itinerary-side-container {
                width: 250px;
                height: 200px;
                margin-left: 1rem;
                padding: 15px;
            }
            
            .page-title {
                font-size: 1.6rem;
                margin-bottom: 0.1rem;
            }
            
            .page-subtitle {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }
            
            .page-description {
                font-size: 0.9rem;
                margin-top: 0.3rem;
            }
            
            .itinerary-title {
                font-size: 1.4rem;
            }
            
            .itinerary-subtitle {
                font-size: 0.85rem;
            }
            
            /* Itinerary Overlay - Mobile Adjustments */
            .itinerary-overlay {
                padding: 15px;
            }
            
            .overlay-title {
                font-size: 1.1rem;
                margin-bottom: 8px;
            }
            
            .overlay-text {
                font-size: 0.85rem;
                margin-bottom: 10px;
            }
            
            .click-icon {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 0.8rem 0 0.3rem 0;
            }
            
            .page-header-container {
                flex-direction: row;
                align-items: flex-start;
            }
            
            .itinerary-side-container {
                width: 180px;
                height: 150px;
                margin-left: 0.5rem;
                padding: 10px;
            }
            
            .page-title {
                font-size: 1.4rem;
                line-height: 1;
            }
            
            .page-subtitle {
                font-size: 1.1rem;
                line-height: 1.1;
            }
            
            .page-description {
                font-size: 0.8rem;
                line-height: 1.3;
            }
            
            .page-title-section {
                min-width: 200px;
            }
            
            .itinerary-title {
                font-size: 1rem;
            }
            
            .itinerary-subtitle {
                font-size: 0.75rem;
            }
            
            /* Itinerary Overlay - Smaller Mobile Adjustments */
            .itinerary-overlay {
                padding: 10px;
            }
            
            .overlay-title {
                font-size: 0.9rem;
                margin-bottom: 5px;
            }
            
            .overlay-text {
                font-size: 0.75rem;
                margin-bottom: 8px;
            }
            
            .click-icon {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 400px) {
            .page-header {
                padding: 0.6rem 0 0.2rem 0;
            }
            
            .itinerary-side-container {
                width: 140px;
                height: 120px;
                margin-left: 0.5rem;
                padding: 8px;
            }
            
            .page-title {
                font-size: 1.2rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
            
            .page-description {
                font-size: 0.75rem;
            }
            
            .itinerary-title {
                font-size: 0.9rem;
            }
            
            .itinerary-subtitle {
                font-size: 0.7rem;
            }
            
            /* Itinerary Overlay - Extra Small Mobile Adjustments */
            .itinerary-overlay {
                padding: 8px;
            }
            
            .overlay-title {
                font-size: 0.8rem;
                margin-bottom: 4px;
            }
            
            .overlay-text {
                font-size: 0.65rem;
                margin-bottom: 6px;
                line-height: 1.2;
            }
            
            .click-icon {
                font-size: 1rem;
            }
        }

        /* Main Content */
        main {
            margin-top: 0 !important;
            padding-top: 0;
        }

        .container.mt-4 {
            margin-top: 0.5rem !important;
            padding-top: 0;
        }

        /* Card Grid Layout */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Card Styling */
        .card {
            width: 90%;
            height: 254px;
            border-radius: 40px;
            background: #f5f5f5;
            position: relative;
            padding: 1.8rem;
            border: 2px solid #c3c6ce;
            transition: all 0.5s ease-out;
            overflow: visible;
            transform: translateY(0);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: #7D0A0A;
        }

        .card-img-top {
            width: 100%;
            height: 80%;
            object-fit: fixed;
            border-radius: 15px 15px;
        }

        .card-body {
            padding: 1rem;
            text-align: center;
        }

        .card-title {
            font-size: .8rem;
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

        /* Footer */
        .footer {
            background-color: var(--secondary-color);
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

        /* Itinerary Modal - DESKTOP */
        .itinerary-modal .modal-dialog {
            max-width: 900px;
        }
        
        .itinerary-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: 3px solid #7D0A0A;
        }
        
        .itinerary-modal .modal-header {
            background: linear-gradient(135deg, #7D0A0A, #545454);
            color: white;
            border-bottom: none;
            padding: 20px 30px;
        }
        
        .itinerary-modal .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .itinerary-modal .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .itinerary-modal .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 15px 30px;
        }
        
        /* Itinerary Container inside Modal - DESKTOP */
        .itinerary-full-container {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(125, 10, 10, 0.1);
        }

        .header-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #7D0A0A;
            text-align: center;
            margin-bottom: 10px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }

        .itinerary-subtitle-full {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            color: #555;
            margin-bottom: 20px;
            font-style: italic;
            font-size: 0.9rem;
        }

        .toggle-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .toggle-btn {
            background: linear-gradient(135deg, #545454, #7D0A0A);
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(125, 10, 10, 0.2);
            border-radius: 8px;
        }

        .toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(125, 10, 10, 0.3);
            color: white;
        }

        .toggle-btn.collapsed .show-text {
            display: inline;
        }
        .toggle-btn.collapsed .hide-text {
            display: none;
        }
        .toggle-btn:not(.collapsed) .show-text {
            display: none;
        }
        .toggle-btn:not(.collapsed) .hide-text {
            display: inline;
        }

        .time-block {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #7D0A0A;
            position: relative;
            overflow: hidden;
        }

        .time-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .time-block:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #7D0A0A, #C38154);
        }

        /* Desktop Image Size */
        .time-img {
            width: 300px;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
            border: 2px solid #e0e0e0;
            flex-shrink: 0;
        }

        .time-text {
            flex: 1;
        }

        .time {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #7D0A0A;
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }

        .time:before {
            content: '🕒';
            margin-right: 6px;
            font-size: 0.8em;
        }

        .location-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .time-block p {
            font-family: 'Poppins', sans-serif;
            color: #555;
            line-height: 1.5;
            margin-bottom: 0;
            font-size: 0.85rem;
        }

        .free-entry {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 6px;
        }

        .itinerary-footerr {
            font-family: 'Playfair Display', serif;
            text-align: center;
            font-size: 1.2rem;
            color: #7D0A0A;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px dashed #7D0A0A;
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {
            0% { transform: scale(1); }
            25% { transform: scale(1.02); }
            50% { transform: scale(1); }
            75% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .time-block ul {
            padding-left: 15px;
            margin-top: 8px;
            margin-bottom: 0;
        }

        .time-block li {
            margin-bottom: 5px;
            position: relative;
            padding-left: 12px;
            font-size: 0.85rem;
        }

        .time-block li:before {
            content: '•';
            color: #7D0A0A;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .time-block li strong {
            color: #333;
        }

        /* MOBILE SCREENS - Itinerary Modal Adjustments */
        @media (max-width: 768px) {
            /* Modal adjustments */
            .itinerary-modal .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
            }
            
            .itinerary-modal .modal-content {
                border-radius: 15px;
                border-width: 2px;
            }
            
            .itinerary-modal .modal-header {
                padding: 15px 20px;
            }
            
            .itinerary-modal .modal-title {
                font-size: 1.3rem;
            }
            
            .itinerary-modal .modal-body {
                padding: 20px 15px;
                max-height: 80vh;
            }
            
            /* Container adjustments */
            .itinerary-full-container {
                padding: 15px 10px;
                border-radius: 12px;
            }
            
            .header-title {
                font-size: 1.4rem;
                padding-bottom: 10px;
                margin-bottom: 8px;
            }
            
            .itinerary-subtitle-full {
                font-size: 0.8rem;
                margin-bottom: 15px;
            }
            
            .toggle-btn {
                padding: 8px 15px;
                font-size: 0.8rem;
            }
            
            /* Time block adjustments - Image on LEFT, Text on RIGHT for mobile */
            .time-block {
                flex-direction: row !important; /* Changed from column to row */
                padding: 12px;
                margin-bottom: 12px;
                border-left-width: 3px;
            }
            
            /* Smaller images on mobile - Image on LEFT */
            .time-img {
                width: 120px !important; /* Reduced width for mobile */
                height: 120px !important; /* Reduced height for mobile */
                margin-right: 12px !important; /* Margin on right side */
                margin-bottom: 0 !important; /* Remove bottom margin */
                border-radius: 6px;
                flex-shrink: 0; /* Prevent image from shrinking */
            }
            
            .time-text {
                flex: 1; /* Take remaining space */
            }
            
            .time {
                font-size: 0.8rem;
                margin-bottom: 4px;
            }
            
            .location-title {
                font-size: 1rem;
                margin-bottom: 6px;
            }
            
            .time-block p {
                font-size: 0.75rem;
                line-height: 1.4;
            }
            
            .free-entry {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
            
            .time-block ul {
                padding-left: 12px;
                margin-top: 6px;
            }
            
            .time-block li {
                font-size: 0.75rem;
                margin-bottom: 4px;
                padding-left: 10px;
            }
            
            .itinerary-footerr {
                font-size: 1rem;
                margin-top: 20px;
                padding-top: 12px;
            }
        }

        /* Extra small screens */
        @media (max-width: 480px) {
            .itinerary-modal .modal-dialog {
                max-width: 98%;
                margin: 5px auto;
            }
            
            .itinerary-modal .modal-header {
                padding: 12px 15px;
            }
            
            .itinerary-modal .modal-title {
                font-size: 1.1rem;
            }
            
            .itinerary-modal .modal-body {
                padding: 15px 10px;
                max-height: 85vh;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
            
            .itinerary-subtitle-full {
                font-size: 0.75rem;
            }
            
            .time-block {
                padding: 10px;
                margin-bottom: 10px;
            }
            
            /* Even smaller images on very small screens */
            .time-img {
                width: 100px !important;
                height: 100px !important;
                margin-right: 10px !important;
            }
            
            .location-title {
                font-size: 0.9rem;
            }
            
            .time-block p {
                font-size: 0.7rem;
            }
            
            .time-block li {
                font-size: 0.7rem;
            }
            
            .itinerary-footerr {
                font-size: 0.9rem;
                margin-top: 15px;
                padding-top: 10px;
            }
        }

        /* Card responsive adjustments */
        @media (max-width: 1200px) {
            .card-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 10px;
            }
            
            .card {
                height: 250px;
                padding: 1.2rem;
                border-radius: 30px;
            }
            
            .card-title {
                font-size: .7rem;
                font-weight: bold;
            }
            .card-img-top{
                width: 100%;
                height: 70%;
            }
            .card-button {
                font-size: .7rem;
                width: 70%;
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
    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <div class="page-header-container">
                <div class="page-title-section">
                    <h1 class="page-title">Popular Tourist Attractions</h1>
                    <h2 class="page-subtitle">Iloilo City</h2>
                    <p class="page-description">Explore the most popular tourist destinations and attractions in the heart of Iloilo City</p>
                </div>
                <div class="itinerary-side-container" data-bs-toggle="modal" data-bs-target="#itineraryModal">
                    <div class="itinerary-header">
                        <div class="itinerary-title">ILOILO CITY TOUR ITINERARY</div>
                        <div class="itinerary-subtitle">By: Iloilo City Department Of Tourism</div>
                    </div>
                    
                    <div class="itinerary-overlay">
                        <div class="overlay-title">View Full Itinerary</div>
                        <div class="overlay-text">Click to see complete tour details</div>
                        <div class="click-icon">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow-1">
        <div class="container mt-4">
            <div class="card-container">
                <?php if ($popular_exist): ?>
                    <?php while ($popular = $result->fetch_assoc()): ?>
                        <div class="card shadow-sm">
                            <img src="<?= htmlspecialchars($popular['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($popular['name']) ?>">
                            <div class="card-body text-center">
                                <h6 class="card-title"><?= htmlspecialchars($popular['name']) ?></h6>
                                <?php if (!empty($popular['details_link'])): ?>
                                    <a href="<?= htmlspecialchars($popular['details_link']) ?>" class="card-button">
                                        View Details
                                    </a>
                                <?php else: ?>
                                    <span class="card-button">Details Not Available</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">No popular landmarks found.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Itinerary Modal -->
    <div class="modal fade itinerary-modal" id="itineraryModal" tabindex="-1" aria-labelledby="itineraryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="itineraryModalLabel">ILOILO CITY TOUR ITINERARY</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="itinerary-full-container">
                        <div class="header-title">ILOILO CITY TOUR ITINERARY</div>
                        <p class="itinerary-subtitle-full">By: Iloilo City Department Of Tourism</p>
                        
                        <!-- Toggle Button -->
                        <div class="toggle-btn-container">
                            <button class="btn toggle-btn" type="button" data-bs-toggle="collapse" data-bs-target="#itineraryContent" aria-expanded="false" aria-controls="itineraryContent">
                                <span class="show-text">Show Itinerary Details</span>
                                <span class="hide-text">Hide Itinerary Details</span>
                                <i class="fas fa-chevron-down ms-2"></i>
                            </button>
                        </div>

                        <!-- Collapsible Itinerary -->
                        <div class="collapse" id="itineraryContent">
                            <div class="time-block">
                                <img src="img/new tourist spots/convention-center.jpg" class="time-img" alt="Iloilo Convention Center">
                                <div class="time-text">
                                    <div class="time">8:30 AM</div>
                                    <div class="location-title">Departure from Iloilo Convention Center</div>
                                    <p>The Old Iloilo Airport site was transformed into Iloilo Business Park, featuring the Iloilo Convention Center, the Iloilo Museum of Contemporary Arts, Brandy Museum, and the Gen. Martin Delgado Monument.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/jaro.jpg" class="time-img" alt="Jaro Cathedral">
                                <div class="time-text">
                                    <div class="time">8:40 AM</div>
                                    <div class="location-title">Jaro Plaza and Cathedral</div>
                                    <p>Features the Jaro Belfry, monuments of Graciano Lopez Jaena and Patrocino Gamboa. The Belfry now has carillon bells. Jaro Cathedral houses the miraculous Our Lady of Candles, crowned by Pope John Paul II in 1981. Senator Grace Poe was left here as an infant.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/cover6.jpg" class="time-img" alt="Millionaire's Row">
                                <div class="time-text">
                                    <div class="time">9:10 AM</div>
                                    <div class="location-title">Rolling Tour: Millionaire's Row, Lapaz District, Calle Real</div>
                                    <p>See sugar mansions like Nelly Garden and Sanson-Montinola House. Pass by St. Clement's Church and Calle Real Heritage District with its art deco buildings. Visit the Arroyo Fountain and Filipino-Chinese Friendship Arch.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/libertad.jpg" class="time-img" alt="Museum Stop">
                                <div class="time-text">
                                    <div class="time">9:45 AM</div>
                                    <div class="location-title">Museum Tour & Plaza Libertad</div>
                                    <p>Explore the Museum of Philippine Maritime History and Economic History, then walk to Plaza Libertad, where Gen. Martin Delgado led the surrender of Spanish officials. <span class="free-entry">Free Entrance</span></p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/new tourist spots/fortsanpedro.jpg" class="time-img" alt="Fort San Pedro">
                                <div class="time-text">
                                    <div class="time">10:20 AM</div>
                                    <div class="location-title">Rolling Tour: Fort San Pedro & Muelle Loney</div>
                                    <p>View Guimaras from Fort San Pedro. Travel along Iloilo River and Muelle Loney, then head to General Luna and Iloilo's University Belt.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/economic.jpg" class="time-img" alt="National Museum">
                                <div class="time-text">
                                    <div class="time">10:45 AM</div>
                                    <div class="location-title">National Museum of the Philippines - Iloilo</div>
                                    <p>Located in the restored Old Iloilo Prison (1911). Discover four galleries featuring Western Visayas culture. <span class="free-entry">Free Entrance</span></p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/breakthrough.jpg" class="time-img" alt="Lunch">
                                <div class="time-text">
                                    <div class="time">12:00 NN</div>
                                    <div class="location-title">Lunch: Tatoy's Manokan or Breakthrough</div>
                                    <p>Enjoy Ilonggo seafood specialties and grilled native chicken at seaside restaurants established in the 1980s.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/new tourist spots/caminabalayngabato.jpg" class="time-img" alt="Balay na Bato">
                                <div class="time-text">
                                    <div class="time">1:30 PM</div>
                                    <div class="location-title">Camiña Balay na Bato & Tsokolate de Baterol</div>
                                    <p>Tour the Avanceña Ancestral House filled with antiques and a Hablon weaving demo. Try their hot chocolate. ₱180/person</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/rosario-museum.jpg" class="time-img" alt="Rosario Arroyo Museum">
                                <div class="time-text">
                                    <div class="time">2:15 PM</div>
                                    <div class="location-title">Venerable Mother Rosario Arroyo Museum</div>
                                    <p>Visit the museum of the founder of Dominican Sisters of the Most Holy Rosary. <span class="free-entry">Free Entrance</span></p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/molochurch.jpg" class="time-img" alt="Molo Church">
                                <div class="time-text">
                                    <div class="time">2:45 PM</div>
                                    <div class="location-title">Molo Church, Plaza & Mansion</div>
                                    <p>Molo is known as the "Athens of the Philippines." Visit the Molo Mansion, Church, and Plaza—featuring feminist heritage and Pancit Molo cuisine.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/cover12.jpg" class="time-img" alt="Esplanade">
                                <div class="time-text">
                                    <div class="time">3:30 PM</div>
                                    <div class="location-title">Iloilo Esplanade 1 (Diversion Road)</div>
                                    <p>Stroll along the award-winning 9km linear river park. Capture photos at the Dinagyang Mural and Datu Paiburong statue.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/contemporary.jpg" class="time-img" alt="ILOMOCA">
                                <div class="time-text">
                                    <div class="time">4:00 PM</div>
                                    <div class="location-title">ILOMOCA & Brandy Museum</div>
                                    <p>View curated art at the Iloilo Museum of Contemporary Art and learn the history of Brandy. Nearby is the vibrant Festive Walk Lantern display. ₱150 for ILOMOCA</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/convention-center.jpg" class="time-img" alt="Return to Convention Center">
                                <div class="time-text">
                                    <div class="time">4:30 PM</div>
                                    <div class="location-title">Return to Convention Center / Pasalubong Shopping</div>
                                    <p>Shop at Balai Ilonggo, Biscocho Haus, Deocampo's, Fiesta Souvenirs, Panaderia de Molo, and more.</p>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/pasalubong.jpg" class="time-img" alt="Pasalubong Shopping">
                                <div class="time-text">
                                    <div class="location-title">Pasalubong Shopping</div>
                                    <ul>
                                        <li><strong>Balai Ilonggo</strong> – Located at the fountain area of Festive Walk Mall. Features products from various Iloilo communities, including handwoven textile Hablon.</li>
                                        <li><strong>Biscocho Haus</strong> – Main shop is in Jaro near the plaza. Popular for biscocho, butterscotch, barquillos, and more.</li>
                                        <li><strong>Deocampo's Barquillos</strong> – The original makers of rolled wafers (barquillos).</li>
                                        <li><strong>Fiesta Souvenirs</strong> – Located at Festive Walk Mall and SM City Iloilo. Sells souvenir shirts, local handwoven textiles, and Dinagyang dolls.</li>
                                        <li><strong>Panaderia de Molo</strong> – Iloilo's oldest pasalubong shop offering baked goods like Galletas and Hojaldres.</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="time-block">
                                <img src="img/other-attractions.jpg" class="time-img" alt="Other Attractions">
                                <div class="time-text">
                                    <div class="location-title">Other Tourism Attractions and Activities</div>
                                    <p>You may be interested to visit or experience:</p>
                                    <ul>
                                        <li><strong>Roberto's Stopao</strong> – JM Basa Street</li>
                                        <li><strong>Iloilo City Garden of Love</strong> – Esplanade 3, Lapaz</li>
                                        <li><strong>Lapaz Batchoy</strong> – Netong's, Ted's, Deco's, Inggo's</li>
                                        <li><strong>Pancit Molo</strong> – Kap Ising's at Atria</li>
                                        <li><strong>Native Coffee</strong> – Local cafés in Iloilo</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="itinerary-footerr">My Heart Beats in Iloilo City ❤️</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5 py-4 text-white">
        <div class="container">
            <div class="row row-cols-3 g-3 text-center text-sm-start">
                <div class="col">
                    <h3 class="text-uppercase mb-2 fw-semibold small">About</h3>
                    <img src="img/logo4.png" alt="Iloilo City Logo" class="img-fluid mb-2" style="max-width: 100px;">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    
    <script>
        // Sidebar functions
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

        // Close sidebar on ESC key
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
                !toggleButton.contains(event.target) &&
                event.target !== overlay) {
                closeSidebar();
            }
        });

        // Auto open modal if URL has parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('itinerary') === 'true') {
                const modal = new bootstrap.Modal(document.getElementById('itineraryModal'));
                modal.show();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>