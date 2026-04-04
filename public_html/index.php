<?php
session_start();
require_once 'track_visitor.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] : '';
$userRole = $isLoggedIn ? $_SESSION['role'] : '';

if (isset($_SESSION['profile_updated'])) {
    $profile_success_msg = 'Profile updated successfully!';
    unset($_SESSION['profile_updated']);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Discover Iloilo City - The City of Love. Explore cultural heritage, delicious food, festivals, and more.">
    <meta name="author" content="Iloilo City Info App Team">
    <title>Discover Iloilo City - The City of Love</title>

    
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
        --light-color: #f8f4e3;
        --dark-color: #2c2f48;
        --text-color: #333;
        --text-light: #f8f9fa;
        --transition: all 0.3s ease;
      }

      body {
        font-family: 'Open Sans', sans-serif;
        font-size: 1rem;
        line-height: 1.6;
        color: var(--text-color);
        background: url('img/bg.png') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      h1, h2, h3, h4, h5, h6 {
        font-family: 'Playfair Display', serif;
        font-weight: 600;
        line-height: 1.3;
        margin-bottom: 1rem;
        color: var(--dark-color);
      }

      h1 {
        font-size: 2.5rem;
      }

      h2 {
        font-size: 2rem;
      }

      h3 {
        font-size: 1.75rem;
      }

      p {
        margin-bottom: 1.25rem;
              font-size: 1.05rem;
            }
      /* User Profile Styles */
            .user-profile {
              display: flex;
              align-items: center;
              gap: 10px;
              padding: 8px 15px;
              border-radius: 25px;
              background: rgba(255, 255, 255, 0.9);
              margin-left: 15px;
              transition: var(--transition);
            }

            .user-profile:hover {
              background: rgba(255, 255, 255, 1);
              transform: translateY(-2px);
            }

            .user-avatar {
              width: 35px;
              height: 35px;
              border-radius: 50%;
              background: var(--primary-color);
              display: flex;
              align-items: center;
              justify-content: center;
              color: white;
              font-weight: bold;
              font-size: 14px;
              overflow: hidden;
            }

            .user-avatar img {
              width: 100%;
              height: 100%;
              object-fit: cover;
            }

            .user-info {
              display: flex;
              flex-direction: column;
            }

            .user-name {
              font-weight: 600;
              font-size: 14px;
              color: var(--dark-color);
              margin: 0;
            }

            .user-role {
              font-size: 11px;
              color: #666;
              margin: 0;
            }

            .dropdown-menu {
              border: none;
              box-shadow: 0 5px 15px rgba(0,0,0,0.1);
              border-radius: 10px;
            }

            .dropdown-item {
              padding: 8px 15px;
              font-size: 14px;
              transition: var(--transition);
            }

            .dropdown-item:hover {
              background-color: #f8f9fa;
            }

            .dropdown-divider {
              margin: 5px 0;
            }


            /* Accessibility */
            .skip-link {
              position: absolute;
              left: -1000px;
              top: 5px;
              z-index: 999;
              background: var(--primary-color);
              color: white;
              padding: 8px 16px;
              border-radius: 4px;
              transition: var(--transition);
            }

            .skip-link:focus {
              left: 10px;
            }

            /* Loading Animation */
            .loader-wrapper {
              position: fixed;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background: rgba(255, 255, 255, 0.9);
              display: flex;
              justify-content: center;
              align-items: center;
              z-index: 9999;
              transition: opacity 0.5s ease;
            }

            .loader {
              width: 48px;
              height: 48px;
              border: 5px solid var(--primary-color);
              border-bottom-color: transparent;
              border-radius: 50%;
              display: inline-block;
              box-sizing: border-box;
              animation: rotation 1s linear infinite;
            }

            @keyframes rotation {
              0% { transform: rotate(0deg); }
              100% { transform: rotate(360deg); }
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

            /* Hero Section */
            .hero-section {
              padding: 120px 0 80px;
              text-align: center;
              color: var(--text-light);
              min-height: 80vh;
              display: flex;
              align-items: center;
              position: relative;
              overflow: hidden;
            }

            .hero-content {
              transform: translateY(50px);
              opacity: 0;
              transition: all 0.8s ease-out;
            }

            .hero-content.animate {
              transform: translateY(0);
              opacity: 1;
            }

        

          

            /* Enhanced Icon Cards Section */
            .icon-section {
              padding: 60px 0;
              background-color: rgba(255, 255, 255, 0.95);
            }

            .icon-card-container {
              position: relative;
              height: 100%;
              perspective: 1000px;
            }

            .icon-card {
              transition: all 0.5s ease;
              border: none;
              border-radius: 10px;
              box-shadow: 0 4px 8px rgba(0,0,0,0.1);
              background-color: white;
              height: 100%;
              overflow: hidden;
              border: 1px solid rgba(0,0,0,0.05);
              position: relative;
              transform-style: preserve-3d;
              cursor: pointer;
            }

            .icon-card:hover,
            .icon-card:focus-within {
              transform: translateY(-10px) scale(1.02);
              box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            }

            .icon-card .card-body {
              padding: 2rem 1rem;
              text-align: center;
              transition: all 0.3s ease;
            }

            .icon-card i {
              color: #545454;
              font-size: 2.5rem;
              margin-bottom: 15px;
              transition: all 0.3s ease;
            }

            .icon-card .card-title {
              color: var(--dark-color);
              font-weight: 600;
              margin-bottom: 0;
              transition: all 0.3s ease;
              font-size: 1.1rem;
            }

            /* Hover effects for icon cards */
            .icon-card:hover .card-body,
            .icon-card:focus-within .card-body {
              background-color: #848484;
              color: white;
            }

            .icon-card:hover i,
            .icon-card:focus-within i {
              color: white;
              transform: scale(1.2);
            }

            .icon-card:hover .card-title,
            .icon-card:focus-within .card-title {
              color: white;
            }

            /* Pulse animation on hover */
            @keyframes pulse {
              0% { transform: scale(1); }
              50% { transform: scale(1.05); }
              100% { transform: scale(1); }
            }

            .icon-card:hover {
              animation: pulse 1.5s infinite;
            }

            /* Focus styles for accessibility */
            .icon-card:focus {
              outline: 3px solid var(--primary-dark);
              outline-offset: 3px;
            }

            /* About Section */
            .about-section {
              padding: 80px 0;
              background-color: #034752;
            }

            .about-text {
              max-width: 800px;
              margin: 0 auto;
              text-align: center;
              font-size: 1.1rem;
              color: var(--text-color);
              margin-bottom: 30px;
              line-height: 1.7;
            }

            /* Featurettes */
            .featurette {
              padding: 80px 0;
              background-color: rgba(248, 249, 251, 0.95);
            }

            .featurette:nth-child(even) {
              background-color: #e1dedeff;
            }

            .featurette-img {
              border-radius: 10px;
              box-shadow: 0 10px 30px rgba(0,0,0,0.1);
              transition: var(--transition);
              max-width: 70%;
              height: auto;
            }

            .featurette-img:hover,
            .featurette-img:focus {
              transform: scale(1.03);
            }

            .featurette-heading {
              font-family: 'Playfair Display', serif;
              font-size: clamp(1.5rem, 3vw, 2rem);
              color: var(--dark-color);
              margin-bottom: 20px;
              line-height: 1.3;
            }

            .featurette-text {
              font-size: 1.1rem;
              margin-bottom: 25px;
              line-height: 1.7;
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

            /* Back to Top Button */
            .back-to-top {
              position: fixed;
              bottom: 30px;
              right: 30px;
              width: 50px;
              height: 50px;
              border-radius: 50%;
              background-color: var(--primary-color);
              color: white;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 20px;
              z-index: 99;
              opacity: 0;
              visibility: hidden;
              transition: var(--transition);
              text-decoration: none;
              border: none;
              cursor: pointer;
            }

            .back-to-top.active {
              opacity: 1;
              visibility: visible;
            }

            .back-to-top:hover,
            .back-to-top:focus {
              background-color: var(--primary-dark);
              transform: translateY(-5px);
              outline: none;
            }

            /* Animations */
            @keyframes fadeInUp {
              from {
                opacity: 0;
                transform: translateY(30px);
              }
              to {
                opacity: 1;
                transform: translateY(0);
              }
            }

            /* Slider Styles */
            .slider-container {
              position: relative;
              height: 500px;
              overflow: hidden;
              background-color: rgba(0, 0, 0, 0.8);
              display: flex;
              align-items: center;
              justify-content: center;
            }

            .slider {
              width: 100%;
              height: 100%;
              overflow: hidden;
              mask-image: linear-gradient(to right, transparent, #000 10% 90%, transparent);
            }

            .slider .list {
              display: flex;
              width: 100%;
              min-width: calc(var(--width) * var(--quantity));
              position: relative;
            }

            .slider .list .item {
              width: var(--width);
              height: var(--height);
              position: absolute;
              left: 100%;
              animation: autoRun 20s linear infinite;
              transition: filter 0.5s;
              animation-delay: calc(
                (20s / var(--quantity)) * (var(--position) - 1) - 20s
              ) !important;
            }

            .slider .list .item img {
              width: 100%;
              height: 100%;
              object-fit: cover;
              border-radius: 8px;
              box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            }

            @keyframes autoRun {
              from {
                left: 100%;
              }
              to {
                left: calc(var(--width) * -1);
              }
            }

            .slider:hover .item {
              animation-play-state: paused !important;
              filter: grayscale(1);
            }

            .slider .item:hover {
              filter: grayscale(0);
              transform: scale(1.05);
              z-index: 10;
            }

            .slider-content {
              position: absolute;
              z-index: 1;
              color: white;
              text-align: center;
              padding: 40px 20px;
              max-width: 800px;
              margin: 0 auto;
            }

            /* New Search Bar Styles - Responsive version */
            .search-container {
              position: relative;
              max-width: 800px;
              margin: 0 auto;
              width: 100%;
              padding: 0 15px;
              transform: translateY(50px);
              opacity: 0;
              transition: all 0.8s ease-out 0.2s;
            }

            .search-container.animate {
              transform: translateY(0);
              opacity: 1;
            }

            #poda {
              display: flex;
              align-items: center;
              justify-content: center;
              position: relative;
              width: 100%;
              max-width: 800px;
              margin: 0 auto;
            }

            #main {
              position: relative;
              width: 100%;
            }

            .grid {
              height: 100%;
              width: 100%;
              background-image: linear-gradient(to right, #0f0f10 1px, transparent 1px),
                linear-gradient(to bottom, #0f0f10 1px, transparent 1px);
              background-size: 1rem 1rem;
              background-position: center center;
              position: absolute;
              z-index: -1;
              filter: blur(1px);
            }

            .white,
            .border,
            .darkBorderBg,
            .glow {
              height: 70px;
              width: 100%;
              position: absolute;
              overflow: hidden;
              z-index: -1;
              border-radius: 12px;
              filter: blur(3px);
            }

            .input {
              background-color: #010201;
              border: none;
              width: 100%;
              height: 70px;
              border-radius: 20px;
              color: white;
              padding-inline: 59px;
              font-size: 18px;
            }

            .input::placeholder {
              color: #c0b9c0;
            }

            .input:focus {
              outline: none;
            }

            #input-mask {
              pointer-events: none;
              width: 100px;
              height: 20px;
              position: absolute;
              top: 18px;
              left: 70px;
            }

            #pink-mask {
              pointer-events: none;
              width: 30px;
              height: 20px;
              position: absolute;
              background: #ffffffff;
              top: 10px;
              left: 5px;
              filter: blur(20px);
              opacity: 0.8;
              transition: all 2s;
            }

            #main:hover > #pink-mask {
              opacity: 0;
            }

            .white {
              height: 63px;
              border-radius: 10px;
              filter: blur(2px);
            }

            .white::before {
              content: "";
              z-index: -2;
              text-align: center;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(83deg);
              position: absolute;
              width: 600px;
              height: 600px;
              background-repeat: no-repeat;
              background-position: 0 0;
              filter: brightness(1.4);
              background-image: conic-gradient(
                rgba(0, 0, 0, 0) 0%,
                #ffffffff,
                rgba(0, 0, 0, 0) 8%,
                rgba(0, 0, 0, 0) 50%,
                #ffffffff,
                rgba(0, 0, 0, 0) 58%
              );
              transition: all 2s;
            }

            .border {
              height: 59px;
              border-radius: 11px;
              filter: blur(0.5px);
            }

            .border::before {
              content: "";
              z-index: -2;
              text-align: center;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(70deg);
              position: absolute;
              width: 600px;
              height: 600px;
              filter: brightness(1.3);
              background-repeat: no-repeat;
              background-position: 0 0;
              background-image: conic-gradient(
                #1c191c,
                #ffffffff 5%,
                #ffffffff 14%,
                #1c191c 50%,
                #ffffffff 60%,
                #1c191c 64%
              );
              transition: all 2s;
            }

            .darkBorderBg {
              height: 65px;
            }

            .darkBorderBg::before {
              content: "";
              z-index: -2;
              text-align: center;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(82deg);
              position: absolute;
              width: 600px;
              height: 600px;
              background-repeat: no-repeat;
              background-position: 0 0;
              background-image: conic-gradient(
                rgba(0, 0, 0, 0),
                #ffffffff,
                rgba(0, 0, 0, 0) 10%,
                rgba(0, 0, 0, 0) 50%,
                #ffffffff,
                rgba(0, 0, 0, 0) 60%
              );
              transition: all 2s;
            }

            #poda:hover > .darkBorderBg::before {
              transform: translate(-50%, -50%) rotate(-98deg);
            }

            #poda:hover > .glow::before {
              transform: translate(-50%, -50%) rotate(-120deg);
            }

            #poda:hover > .white::before {
              transform: translate(-50%, -50%) rotate(-97deg);
            }

            #poda:hover > .border::before {
              transform: translate(-50%, -50%) rotate(-110deg);
            }

            #poda:focus-within > .darkBorderBg::before {
              transform: translate(-50%, -50%) rotate(442deg);
              transition: all 4s;
            }

            #poda:focus-within > .glow::before {
              transform: translate(-50%, -50%) rotate(420deg);
              transition: all 4s;
            }

            #poda:focus-within > .white::before {
              transform: translate(-50%, -50%) rotate(443deg);
              transition: all 4s;
            }

            #poda:focus-within > .border::before {
              transform: translate(-50%, -50%) rotate(430deg);
              transition: all 4s;
            }

            .glow {
              overflow: hidden;
              filter: blur(30px);
              opacity: 0.4;
              height: 130px;
            }

            .glow:before {
              content: "";
              z-index: -2;
              text-align: center;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(60deg);
              position: absolute;
              width: 999px;
              height: 999px;
              background-repeat: no-repeat;
              background-position: 0 0;
              background-image: conic-gradient(
                #000,
                #ffffffff 5%,
                #000 38%,
                #000 50%,
                #ffffffff 60%,
                #000 87%
              );
              transition: all 2s;
            }

            @keyframes rotate {
              100% {
                transform: translate(-50%, -50%) rotate(450deg);
              }
            }

            @keyframes leftright {
              0% {
                transform: translate(0px, 0px);
                opacity: 1;
              }

              49% {
                transform: translate(250px, 0px);
                opacity: 0;
              }
              80% {
                transform: translate(-40px, 0px);
                opacity: 0;
              }

              100% {
                transform: translate(0px, 0px);
                opacity: 1;
              }
            }

            #filter-icon {
              position: absolute;
              top: 8px;
              right: 8px;
              display: flex;
              align-items: center;
              justify-content: center;
              z-index: 2;
              height: 40px;
              width: 38px;
              isolation: isolate;
              overflow: hidden;
              border-radius: 10px;
              background: linear-gradient(180deg, #161329, black, #1d1b4b);
              border: 1px solid transparent;
            }

            .filterBorder {
              height: 42px;
              width: 40px;
              position: absolute;
              overflow: hidden;
              top: 7px;
              right: 7px;
              border-radius: 10px;
            }

            .filterBorder::before {
              content: "";
              text-align: center;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(90deg);
              position: absolute;
              width: 600px;
              height: 600px;
              background-repeat: no-repeat;
              background-position: 0 0;
              filter: brightness(1.35);
              background-image: conic-gradient(
                rgba(0, 0, 0, 0),
                #3d3a4f,
                rgba(0, 0, 0, 0) 50%,
                rgba(0, 0, 0, 0) 50%,
                #3d3a4f,
                rgba(0, 0, 0, 0) 100%
              );
              animation: rotate 4s linear infinite;
            }

            #search-icon {
              position: absolute;
              left: 20px;
              top: 15px;
            }

            

            .popular-searches {
              margin-top: 20px;
              text-align: center;
              transform: translateY(50px);
              opacity: 0;
              transition: all 0.8s ease-out 0.4s;
            }

            .popular-searches.animate {
              transform: translateY(0);
              opacity: 1;
            }

            .popular-tags {
              display: flex;
              flex-wrap: wrap;
              justify-content: center;
              gap: 10px;
              margin-top: 15px;
            }

            .popular-tag {
              background: rgba(255, 255, 255, 0.2);
              border: 1px solid rgba(255, 255, 255, 0.3);
              padding: 6px 15px;
              border-radius: 20px;
              font-size: 0.9rem;
              cursor: pointer;
              transition: all 0.3s;
            }

            .popular-tag:hover {
              background: rgba(255, 255, 255, 0.3);
              transform: translateY(-2px);
            }

            /* Button styles */
            .btn {
              font-family: 'Open Sans', sans-serif;
              font-weight: 600;
              padding: 0.6rem 1.25rem;
              border-radius: 50px;
              transition: all 0.3s ease;
              color: white;
            }

            .btn-primary {
              background-color: var(--primary-color);
              border-color: var(--primary-color);
            }

            .btn-primary:hover,
            .btn-primary:focus {
              background-color: var(--primary-dark);
              border-color: var(--primary-dark);
              transform: translateY(-2px);
            }

            .btn-outline-primary {
              color: var(--primary-color);
              border-color: var(--primary-color);
            }

            .btn-outline-primary:hover,
            .btn-outline-primary:focus {
              background-color: var(--primary-color);
              border-color: var(--primary-color);
              color: white;
            }

            /* Responsive Adjustments */
            @media (max-width: 992px) {
              .hero-section {
                padding: 100px 0 60px;
              }
              
              .featurette {
                padding: 60px 0;
              }
              
              .slider-container {
                height: 400px;
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
              
              .featurette {
                text-align: center;
              }
              
              .featurette-img {
                margin-bottom: 30px;
              }
              
              .footer .col-md-3 {
                margin-bottom: 30px;
              }
              .slider-container {
                height: 1000px;
              }
              .slider {
                --width: 180px;
                --height: 500px;
              }
                .slider .list .item img {
              width: 100%;
              height: 60%;
              object-fit: cover;
              border-radius: 8px;
              box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            }
            
              /* Adjust search bar for mobile */
              .input {
                height: 50px;
                font-size: 16px;
                padding-inline: 50px 40px;
              }
              
              #search-icon {
                left: 15px;
                top: 13px;
              }
              
              #filter-icon {
                height: 36px;
                width: 34px;
              }
              
              .filterBorder {
                height: 38px;
                width: 36px;
                top: 6px;
                right: 6px;
              }
              

            }

            @media (max-width: 576px) {
              body {
                font-size: 0.95rem;
              }
              
              .hero-section {
                padding: 80px 0 40px;
              }
              
              .icon-card i {
                font-size: 2rem;
              }
              
              .footer {
                padding: 40px 0 20px;
              }
              
              .slider-container {
                height: 300px;
              }
              
              .slider {
                --width: 150px;
                --height: 300px;
              }
              
              .featurette-text {
                font-size: 1rem;
              }
              
              /* Further adjust search bar for small screens */
              .input {
                height: 46px;
                font-size: 15px;
                padding-inline: 45px 35px;
              }
              
              #search-icon {
                left: 12px;
                top: 11px;
              }
              
              #search-icon svg {
                width: 20px;
                height: 20px;
              }
              
              #filter-icon {
                height: 34px;
                width: 32px;
              }
              
              .filterBorder {
                height: 36px;
                width: 34px;
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
              
              .hero-section {
                padding: 20px 0 !important;
                color: #000 !important;
                background: none !important;
              }
              
              .icon-section,
              .featurette {
                background: none !important;
                padding: 20px 0 !important;
              }
              
              .footer {
                background: none !important;
                color: #000 !important;
                padding: 20px 0 !important;
              }
              
              a {
                color: #000 !important;
                text-decoration: underline !important;
              }
              
              .btn {
                display: none !important;
              }
            }
            
      .facts-section {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        padding: 2rem;
        margin: 3rem 0;
      }

      .fact-category {
        border-left: 4px solid var(--primary-color);
        padding-left: 1rem;
        margin-bottom: 1.5rem;
      }
      .header-title {
          font-family: 'Playfair Display', serif;
          text-align: center;
          font-size: 2.5rem;
          color: #ffffffff;
          margin-top: 40px;
          padding-top: 20px;
          animation: heartbeat 1.5s infinite;
      }

      @keyframes heartbeat {
          0% { transform: scale(1); }
          25% { transform: scale(1.05); }
          50% { transform: scale(1); }
          75% { transform: scale(1.05); }
          100% { transform: scale(1); }
      }
      @media (max-width: 768px) {
        .header-title {
          font-size: 1.5rem !important;
        }
      }

      @media (max-width: 576px) {
        .header-title {
          font-size: 1.2rem !important;
        }
      }
    </style>
  </head>
  <body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <div class="loader-wrapper">
      <div class="loader" aria-hidden="true"></div>
      <span class="visually-hidden">Loading...</span>
    </div>

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
      <!-- User Profile  -->
            <?php if ($isLoggedIn): ?>
              <li class="nav-item dropdown">
                <div class="user-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" role="button" style="cursor: pointer;">
                  <div class="user-avatar">
                    <?php 
                    if (isset($_SESSION['avatar']) && $_SESSION['avatar'] && file_exists($_SESSION['avatar']) && $_SESSION['avatar'] !== 'default-avatar.jpg') {
                        echo '<img src="' . htmlspecialchars($_SESSION['avatar']) . '" alt="Profile Avatar">';
                    } else {
                        echo strtoupper(substr($_SESSION['first_name'], 0, 1));
                    }
                    ?>
                  </div>
                  <div class="user-info">
                    <p class="user-name"><?php echo htmlspecialchars($_SESSION['first_name']); ?></p>
                    <p class="user-role"><?php echo ucfirst($_SESSION['role']); ?></p>
                  </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                  <?php if ($userRole === 'admin'): ?>
                    <li><a class="dropdown-item" href="dashboard_content.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                  <?php endif; ?>
                  <li><hr class="dropdown-divider"></li>
                  <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
              </li>
            <?php else: ?>
              <li class="nav-item">
                <a class="nav-link" href="login.php"></a>
              </li>
            <?php endif; ?>
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
      <?php if ($isLoggedIn): ?>
        <a href="profile.php">👤  My Profile</a>
        <?php if ($userRole === 'admin'): ?>
          <a href="dashboard.php">📊  Dashboard</a>
        <?php endif; ?>
        <a href="logout.php">🚪  Logout</a>
      <?php else: ?>
        <a href="login.php"></a>
      <?php endif; ?>
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
    <main id="main-content">
      <section class="hero-section">
        <div class="container search-content">
          <div class="hero-content">
            <h1 class="header-title  text-white">"Iloilo Awaits You – Explore, Experience, Enjoy" </h1>
            <p class="header-title text-white ">"Explore Iloilo City, One Click at a Time"</p>
          </div>
          <br>
          <br>
          <br>
          <div class="search-container">
            <div id="poda">
              <div class="grid"></div>
              <div class="glow"></div>
              <div class="darkBorderBg"></div>
              <div class="darkBorderBg"></div>
              <div class="darkBorderBg"></div>
              <div class="white"></div>
              <div class="border"></div>

              <div id="main">
                <form action="search.php" method="POST" id="searchForm" role="search">
                  <label for="search-box" class="visually-hidden">Search Iloilo City</label>
                  <input type="text" 
                         name="query" 
                         id="search-box" 
                         class="input"
                         placeholder="Search for places, food, events..." 
                         required 
                         aria-label="Search Iloilo City"
                         autocomplete="off"
                         aria-autocomplete="list"
                         aria-haspopup="true">
                  <div id="input-mask"></div>
                  <div id="pink-mask"></div>
                  <div class="filterBorder"></div>
                  <button type="submit" id="filter-icon" aria-label="Filter search">
                    <svg preserveAspectRatio="none" height="27" width="27" viewBox="4.8 4.56 14.832 15.408" fill="none">
                      <path d="M8.16 6.65002H15.83C16.47 6.65002 16.99 7.17002 16.99 7.81002V9.09002C16.99 9.56002 16.7 10.14 16.41 10.43L13.91 12.64C13.56 12.93 13.33
                       13.51 13.33 13.98V16.48C13.33 16.83 13.1 17.29 13.81 17.47L12 17.98C11.24 18.45 10.2 17.92 10.2 16.99V13.91C10.2 13.5 9.97 12.98 9.73 12.69L7.52 
                       10.36C7.23 10.08 7 9.55002 7 9.20002V7.87002C7 7.17002 7.52 6.65002 8.16 6.65002Z" stroke="#d6d6e6" stroke-width="1" stroke-miterlimit="10"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                  </button>
                  <div id="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" 
                    height="24" fill="none" class="feather feather-search">
                      <circle stroke="url(#search)" r="8" cy="11" cx="11"></circle>
                      <line stroke="url(#searchl)" y2="16.65" y1="22" x2="16.65" x1="22"></line>
                      <defs>
                        <linearGradient gradientTransform="rotate(50)" id="search">
                          <stop stop-color="#f8e7f8" offset="0%"></stop>
                          <stop stop-color="#b6a9b7" offset="50%"></stop>
                        </linearGradient>
                        <linearGradient id="searchl">
                          <stop stop-color="#b6a9b7" offset="0%"></stop>
                          <stop stop-color="#837484" offset="50%"></stop>
                        </linearGradient>
                      </defs>
                    </svg>
                  </div>
                </form>
              </div>
            </div>
            
            <div class="search-suggestions" id="searchSuggestions" role="listbox">
            </div>
          </div>
          
          <div class="popular-searches text-white">
            <p class="mb-2">Popular searches:</p>
            <div class="popular-tags">
              <button type="button" class="popular-tag text-white" onclick="setSearch('Jaro Cathedral')">Jaro Cathedral</button>
              <button type="button" class="popular-tag text-white" onclick="setSearch('La Paz Batchoy')">La Paz Batchoy</button>
              <button type="button" class="popular-tag text-white" onclick="setSearch('Dinagyang Festival')">Dinagyang Festival</button>
              <button type="button" class="popular-tag text-white" onclick="setSearch('Esplanade')">Esplanade</button>
              <button type="button" class="popular-tag text-white" onclick="setSearch('Calle Real')">Calle Real</button>
            </div>
          </div>
        </div>
      </section>
<br>
<br>
<br>
      <section class="icon-section" aria-labelledby="quick-access-heading">
        <div class="container">
          <h2 id="quick-access-heading" class="text-center mb-5">Quick Access to Iloilo City Information</h2>
          <div class="row justify-content-center g-4">
            <?php
            include 'conn.php';
            
            $iconCardsQuery = "SELECT * FROM icon_cards ORDER BY display_order ASC";
            $iconCardsResult = $conn->query($iconCardsQuery);
            
            if ($iconCardsResult->num_rows > 0) {
                while ($row = $iconCardsResult->fetch_assoc()) {
                    echo '
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="icon-card-container">
                            <a href="' . htmlspecialchars($row['link']) . '" class="text-decoration-none" aria-label="' . htmlspecialchars($row['title']) . '">
                                <div class="card icon-card" tabindex="0">
                                    <div class="card-body">
                                        <i class="fas ' . htmlspecialchars($row['icon']) . ' mb-3" aria-hidden="true"></i>
                                        <h3 class="card-title h6">' . htmlspecialchars($row['title']) . '</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12 text-center"><p>No icon cards found.</p></div>';
            }
            
            $conn->close();
            ?>
          </div>
        </div>
      </section>
      
      <section class="slider-container" aria-label="Iloilo City attractions">
        <div class="slider-content">
          <h2 class="text-white">Explore Iloilo's Beauty</h2>
          <p class="text-white">Discover the stunning landmarks and attractions that make Iloilo City special</p>
          <a href="popular.php" class="btn btn-primary mt-3">View All Attractions</a>
        </div>
        <div 
          class="slider"
          style="--width: 500px;
          --height: 500px;
          --quantity: 10;"
        >
          <div class="list">
            <div class="item" style="--position: 1">
              <img src="img/molochurch.jpg" alt="Molo Church" loading="lazy">
            </div>
            <div class="item" style="--position: 2">
              <img src="img/jaro.jpg" alt="jaro cathedral" loading="lazy">
            </div>
            <div class="item" style="--position: 3">
              <img src="img/about1.jpg" alt="Calle Real Heritage Buildings" loading="lazy">
            </div> 
            <div class="item" style="--position: 3">
              <img src="img/place2.webp" alt="" loading="lazy">
            </div>
            <div class="item" style="--position: 4">
              <img src="img/about3.jpg" alt="Dinagyang Festival Performers" loading="lazy">
            </div>
            <div class="item" style="--position: 5">
              <img src="img/city6.jpg" alt="Iloilo City Skyline" loading="lazy">
            </div>
            <div class="item" style="--position: 6">
              <img src="img/cover6.jpg" alt="La Paz Batchoy Dish" loading="lazy">
            </div>
            <div class="item" style="--position: 7">
              <img src="img/pop2.png" alt="Paraw Regatta Festival" loading="lazy">
            </div>
            <div class="item" style="--position: 8">
              <img src="img/city5.jpg" alt="Jaro Cathedral" loading="lazy">
            </div>
            <div class="item" style="--position: 9">
              <img src="img/food.png" alt="Iloilo Seafood" loading="lazy">
            </div>
            <div class="item" style="--position: 10">
              <img src="img/molo.jpg" alt="Molo mansion" loading="lazy">
            </div>
          </div>
        </div>
      </section>
      
      <section class="about-section" aria-labelledby="about-heading">
        <div class="container">
          <h2 id="about-heading" class="visually-hidden">About Iloilo City</h2>
          <div class="about-text">
            <p class="text-white">
              Iloilo City, the "City of Love," is a vibrant and modern city in Western Visayas. Known for its rich history, 
              beautiful landmarks like Molo Church and Calle Real, and the colorful Dinagyang Festival, the city offers a 
              perfect blend of culture and progress. Enjoy scenic spots like the Iloilo River Esplanade and delicious local 
              dishes such as La Paz Batchoy while experiencing the warmth of Ilonggo hospitality.
            </p>
            <a href="about.php" class="btn btn-primary btn-lg mt-3">Learn More About Iloilo</a>
          </div>
        </div>
      </section>
      
      <section class="featurette" aria-labelledby="history-heading">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-6">
              <img src="img/history.png" alt="History of Iloilo" class="img-fluid featurette-img" loading="lazy">
            </div>
            <div class="col-md-6">
              <h2 id="history-heading">History of Iloilo</h2>
              <p class="featurette-text">
                The name "Iloilo" originated from "Irong-Irong," referring to the nose-like shape of the city's geography. 
                Discover European and American-inspired architecture in stately mansions, majestic century-old churches, 
                and heritage commercial buildings that tell the story of Iloilo's rich past.
              </p>
              <a href="cultural_heritage.php" class="btn btn-outline-primary">Explore History</a>
            </div>
          </div>
        </div>
      </section>
      
      <section class="featurette bg-light" aria-labelledby="food-heading">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-6 order-md-2">
              <img src="img/food.png" alt="Iloilo Cuisine" class="img-fluid featurette-img" loading="lazy">
            </div>
            <div class="col-md-6 order-md-1">
              <h2 id="food-heading">Fun, Foodie, Friendly Iloilo</h2>
              <p class="featurette-text">
                Experience the tantalizing flavors of Iloilo's gastronomic delights, from fresh seafood to mouthwatering 
                local delicacies. As the 'Queen City of the South,' Iloilo has perfected dishes that have become benchmarks 
                for Filipino cuisine, satisfying even the most discerning palates.
              </p>
              <a href="famous_food.php" class="btn btn-outline-primary">Discover Food</a>
            </div>
          </div>
        </div>
      </section>
      
      <section class="featurette" aria-labelledby="festivals-heading">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-6">
              <img src="img/place.PNG" alt="Festivals in Iloilo" class="img-fluid featurette-img" loading="lazy">
            </div>
            <div class="col-md-6">
              <h2 id="festivals-heading">Festivals & Events</h2>
              <p class="featurette-text">
                Iloilo's vibrant festivals feature colorful warriors, dance performances, and exciting competitions. 
                The famous Dinagyang Festival and the Paraw Regatta sailboat races showcase the city's cultural richness 
                and seafaring heritage. The Iloilo River Esplanade provides a perfect venue to enjoy these celebrations 
                against a beautiful waterfront backdrop.
              </p>
              <a href="festivals.php" class="btn btn-outline-primary">View Events</a>
            </div>
          </div>
        </div>
      </section>
    <section class="container">
      <div class="facts-section">
        <h2 class="text-center mb-5">Did You Know? Iloilo City Facts</h2>
        
        <div class="row">
          <div class="col-md-6">
            <div class="fact-category">
              <h4><i class="fas fa-landmark me-2"></i> Historical & Cultural</h4>
              <ul>
                <li>Known as the "City of Love" for the Ilonggos' gentle manner of speaking</li>
                <li>Strong Spanish influence is evident in the city's architecture and traditions</li>
                <li>Formerly the "Queen's City of the South" due to its economic importance</li>
              </ul>
            </div>
            
            <div class="fact-category">
              <h4><i class="fas fa-church me-2"></i> Religious</h4>
              <ul>
                <li>Jaro Cathedral is home to the only Marian image in the Philippines crowned by a Pope</li>
              </ul>
            </div>
            
            <div class="fact-category">
              <h4><i class="fas fa-music me-2"></i> Festival</h4>
              <ul>
                <li>Home of the world-famous Dinagyang Festival held every January</li>
              </ul>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="fact-category">
              <h4><i class="fas fa-briefcase me-2"></i> Economic & Development</h4>
              <ul>
                <li>One of the fastest-growing cities in the Philippines with major business hubs</li>
                <li>Features the Iloilo Business Park, a major business and leisure hub</li>
              </ul>
            </div>
            
            <div class="fact-category">
              <h4><i class="fas fa-bicycle me-2"></i> Transportation</h4>
              <ul>
                <li>Known as the "Bike Capital of the Philippines" with dedicated bike lanes</li>
              </ul>
            </div>
            
            <div class="fact-category">
              <h4><i class="fas fa-utensils me-2"></i> Culinary</h4>
              <ul>
                <li>Birthplace of La Paz Batchoy and Pancit Molo</li>
                <li>Famous for fresh seafood, including oysters and angel wing clams</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
    </main>
    
    <footer class="footer mt-5 py-4  text-white">
      <div class="container">
        <div class="row row-cols-3 g-3 text-center text-sm-start">
         
          <div class="col">
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">About</h3>
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
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">Links</h3>
            <ul class="list-unstyled mb-0 small">
              <li class="mb-1"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
              <li class="mb-1"><a href="about.php" class="text-white text-decoration-none">About</a></li>
              <li class="mb-1"><a href="categories.php" class="text-white text-decoration-none">Categories</a></li>
              <li class="mb-1"><a href="login.php" class="text-white text-decoration-none">Login</a></li>
            </ul>
          </div>


          <div class="col">
            <h3 class="text-uppercase text-white mb-2 fw-semibold small">Contact</h3>
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
 
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
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
        document.querySelector('.navbar-toggler').focus();
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

      function setSearch(value) {
        document.getElementById('search-box').value = value;
        document.getElementById('searchForm').submit();
      }

      document.getElementById('search-box').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const suggestions = document.getElementById('searchSuggestions');
        
        if (searchTerm.length > 2) {
          const mockSuggestions = [
            { name: 'Jaro Cathedral', category: 'Historical' },
            { name: 'La Paz Batchoy', category: 'Food' },
            { name: 'Dinagyang Festival', category: 'Event' },
            { name: 'Iloilo River Esplanade', category: 'Attraction' },
            { name: 'Calle Real', category: 'Heritage Site' }
          ];
          
          const filtered = mockSuggestions.filter(item => 
            item.name.toLowerCase().includes(searchTerm)
          ).slice(0, 5);
          
          if (filtered.length > 0) {
            suggestions.innerHTML = filtered.map(item => `
              <div class="suggestion-item" onclick="setSearch('${item.name}')" role="option">
                <i class="fas fa-${item.category === 'Food' ? 'utensils' : 
                  item.category === 'Event' ? 'calendar-alt' : 
                  item.category === 'Attraction' ? 'camera' : 'landmark'}" aria-hidden="true"></i>
                ${item.name}
                <span class="suggestion-category">${item.category}</span>
              </div>
            `).join('');
            suggestions.classList.add('active');
            suggestions.setAttribute('aria-expanded', 'true');
          } else {
            suggestions.innerHTML = '<div class="suggestion-item" role="option"></div>';
            suggestions.classList.add('active');
            suggestions.setAttribute('aria-expanded', 'true');
          }
        } else {
          suggestions.classList.remove('active');
          suggestions.setAttribute('aria-expanded', 'false');
        }
      });

      document.addEventListener('click', function(event) {
        const searchBox = document.getElementById('search-box');
        const suggestions = document.getElementById('searchSuggestions');
        
        if (event.target !== searchBox && !suggestions.contains(event.target)) {
          suggestions.classList.remove('active');
          suggestions.setAttribute('aria-expanded', 'false');
        }
      });

      window.addEventListener('load', function() {
        document.querySelector('.loader-wrapper').style.opacity = '0';
        setTimeout(function() {
          document.querySelector('.loader-wrapper').style.display = 'none';
          
          document.querySelector('.hero-content').classList.add('animate');
          document.querySelector('.search-container').classList.add('animate');
          document.querySelector('.popular-searches').classList.add('animate');
        }, 500);
      });
      
     
      document.getElementById('search-box').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          document.getElementById('searchForm').submit();
        }
      });
      
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
      var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
      });
    </script>
  </body>
</html>