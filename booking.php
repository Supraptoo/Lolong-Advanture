<?php
session_start();
require_once('config/database.php');

// Google OAuth configuration
require_once __DIR__ . '/vendor/autoload.php'; // Pastikan composer sudah diinstall untuk google/apiclient
$client = new Google_Client();
$client->setClientId('YOUR_GOOGLE_CLIENT_ID');
$client->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');
$client->setRedirectUri('http://yourdomain.com/landingpage.php');
$client->addScope('email profile');
$client->setAccessType('offline');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email = $google_account_info->email;
    $name = $google_account_info->name;

    // Cek apakah user sudah ada di database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Daftar user baru
        $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, phone, role, is_active, created_at) VALUES (?, ?, ?, '', 'user', 1, NOW())");
        $username = 'user_' . str_replace(['.', '@'], ['', ''], $email);
        $stmt->execute([$username, $name, $email]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
    } else {
        $_SESSION['user_id'] = $user['id'];
    }

    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch visitor statistics
try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT SUM(participants) as total FROM bookings WHERE DATE(booking_date) = ? AND status = 'confirmed'");
    $stmt->execute([$today]);
    $daily_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $stmt = $pdo->prepare("SELECT SUM(participants) as total FROM bookings WHERE booking_date >= ? AND booking_date <= ? AND status = 'confirmed'");
    $stmt->execute([$week_ago, $today]);
    $weekly_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    $month_ago = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("SELECT SUM(participants) as total FROM bookings WHERE booking_date >= ? AND booking_date <= ? AND status = 'confirmed'");
    $stmt->execute([$month_ago, $today]);
    $monthly_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Fetch booking status for logged-in user
    $booking_status = [];
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT booking_code, status FROM bookings WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($booking) {
            $booking_status = [
                'code' => $booking['booking_code'],
                'status' => $booking['status']
            ];
        }
    }
} catch (PDOException $e) {
    $daily_visitors = 0;
    $weekly_visitors = 0;
    $monthly_visitors = 0;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lolong Adventure - Wisata Alam Premium Pekalongan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700&display=swap" rel="stylesheet" />
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f8f9fa;
            --accent: #ff7e33;
            --text: #333333;
            --text-light: #666666;
            --white: #FFFFFF;
            --border-color: #ddd;
            --success-color: #4caf50;
            --error-color: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background-color: var(--white);
            overflow-x: hidden;
            line-height: 1.6;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            line-height: 1.3;
        }

        /* Navbar Modern */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.5s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        }

        .navbar.scrolled {
            padding: 15px 5%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo img {
            width: 40px;
            height: 40px;
        }

        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }

        .logo-text span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary);
            cursor: pointer;
            z-index: 1001;
        }

        /* Button Styles */
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
            box-shadow: 0 4px 10px rgba(42, 127, 98, 0.3);
        }

        .btn-primary:hover {
            background: #1f6b53;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(42, 127, 98, 0.4);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-ghost {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-ghost:hover {
            background: var(--primary);
            color: var(--white);
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            min-height: 700px;
            display: flex;
            align-items: center;
            padding: 0 5%;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary), #1f6b53);
            color: var(--white);
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('./assets/images/landingpage/bg_landingpage.jpg') center/cover no-repeat;
            opacity: 0.4;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 20px;
            max-width: 600px;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        /* Visitor Stats */
        .visitor-stats {
            display: flex;
            gap: 30px;
            margin-top: 30px;
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(5px);
            min-width: 150px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            backdrop-filter: blur(5px);
            animation: float 15s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
            }

            100% {
                transform: translateY(0) rotate(360deg);
            }
        }

        /* Section Styles */
        .section {
            padding: 100px 5%;
            position: relative;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .section-title p {
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
            font-size: 1.1rem;
        }

        /* About Section */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .about-text h3 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .about-text p {
            margin-bottom: 20px;
            color: var(--text-light);
        }

        .about-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.5s ease;
        }

        .about-image:hover {
            transform: scale(1.02);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .about-image:hover img {
            transform: scale(1.05);
        }

        /* Destination Cards */
        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .destination-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
        }

        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
        }

        .destination-card:hover .card-image img {
            transform: scale(1.1);
        }

        .card-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--white);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .card-content {
            padding: 25px;
        }

        .card-content h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .card-content p {
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            color: var(--text-light);
            font-size: 14px;
        }

        .card-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-meta i {
            color: var(--primary);
        }

        /* Map Section */
        .map-section {
            background: var(--secondary);
            padding: 100px 5%;
        }

        .map-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .map-wrapper {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            height: 500px;
        }

        .map-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Nearby Attractions */
        .nearby-attractions {
            margin-top: 80px;
        }

        .attractions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .attraction-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
        }

        .attraction-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .attraction-image {
            height: 200px;
            overflow: hidden;
        }

        .attraction-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .attraction-card:hover .attraction-image img {
            transform: scale(1.1);
        }

        .attraction-content {
            padding: 20px;
        }

        .attraction-content h4 {
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .attraction-content p {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .attraction-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .attraction-distance {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .attraction-distance i {
            color: var(--primary);
        }

        /* Events Section */
        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .event-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .event-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s ease;
        }

        .event-card:hover .event-image img {
            transform: scale(1.1);
        }

        .event-date {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            padding: 12px 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .event-card:hover .event-date {
            background: var(--primary);
            color: var(--white);
        }

        .event-day {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            transition: color 0.3s ease;
        }

        .event-card:hover .event-day {
            color: var(--white);
        }

        .event-month {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            margin-top: 2px;
            transition: color 0.3s ease;
        }

        .event-card:hover .event-month {
            color: var(--white);
        }

        .event-category {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: var(--white);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .event-card:hover .event-category {
            background: var(--white);
            color: var(--primary);
        }

        .event-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-content h3 {
            margin: 0 0 15px;
            font-size: 1.4rem;
            color: var(--primary);
        }

        .event-excerpt {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
            flex: 1;
        }

        .event-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-light);
        }

        .event-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .event-meta i {
            color: var(--primary);
        }

        .view-all-events {
            text-align: center;
            margin-top: 50px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary), #1f6b53);
            color: var(--white);
            padding: 100px 5%;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('./assets/images/landingpage/bg-bawah.jpg') center/cover no-repeat;
            opacity: 0.20;
            z-index: 1;
        }

        .cta-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        /* Footer and Sidebar */
        footer {
            background: #1a3a5f;
            color: var(--white);
            padding: 80px 5% 30px;
            position: relative;
        }

        .sidebar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 600px;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar.hidden {
            transform: translateX(-50%) translateY(100%);
        }

        .sidebar-item {
            text-align: center;
            cursor: pointer;
        }

        .sidebar-item a {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar-item i {
            font-size: 20px;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .sidebar-item span {
            font-size: 12px;
        }

        .booking-status {
            margin-top: 10px;
            text-align: center;
            color: var(--text-light);
            font-size: 14px;
        }

        .booking-status.pending {
            color: #ff9800;
        }

        .booking-status.confirmed {
            color: var(--success-color);
        }

        .booking-status.completed {
            color: var(--primary);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            margin-bottom: 25px;
            color: var(--white);
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--accent);
        }

        .footer-column p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--accent);
            color: #1a3a5f;
            transform: translateY(-3px);
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--accent);
            transform: translateX(5px);
        }

        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .newsletter-form input {
            padding: 12px 15px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            font-family: 'Poppins', sans-serif;
        }

        .newsletter-form input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .footer-column i {
            margin-right: 10px;
            color: var(--accent);
            width: 20px;
            text-align: center;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* Floating Particles */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float-particle 15s infinite linear;
            opacity: 0.3;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(0) rotate(0deg);
            }

            100% {
                transform: translateY(-1000px) rotate(720deg);
            }
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .hero h1 {
                font-size: 3rem;
            }

            .section {
                padding: 80px 5%;
            }

            .visitor-stats {
                gap: 20px;
            }

            .stat-item {
                min-width: 120px;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-links {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                background: var(--white);
                flex-direction: column;
                justify-content: center;
                gap: 30px;
                transition: right 0.5s ease;
                box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            }

            .nav-links.active {
                right: 0;
            }

            .hero {
                min-height: 600px;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .cta-buttons {
                flex-direction: column;
                gap: 15px;
            }

            .visitor-stats {
                justify-content: center;
                gap: 15px;
            }

            .about-content {
                grid-template-columns: 1fr;
            }

            .about-image {
                order: -1;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .destination-grid {
                grid-template-columns: 1fr;
            }

            .events-container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                flex-direction: column;
                padding: 15px;
            }

            .sidebar-item {
                margin-bottom: 10px;
            }
        }

        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2rem;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }

            .card-badge,
            .event-category {
                font-size: 10px;
                padding: 5px 10px;
            }

            .event-date {
                padding: 8px 12px;
            }

            .event-day {
                font-size: 24px;
            }

            .event-month {
                font-size: 12px;
            }

            .stat-number {
                font-size: 1.8rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .sidebar {
                width: 95%;
            }
        }
    </style>
</head>

<body>
    <!-- Floating Particles Background -->
    <div class="particles" id="particles"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <img src="https://w7.pngwing.com/pngs/987/739/png-transparent-logo-cv-wisata-outbond-indonesia-thumbnail.png" alt="Lolong Adventure Logo" />
            <div class="logo-text">Lolong <span>Adventure</span></div>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="#home">Beranda</a>
            <a href="#about">Tentang Kami</a>
            <a href="#destinations">Destinasi</a>
            <a href="#events">Event</a>
            <a href="#location">Lokasi</a>
            <a href="#contact">Kontak</a>
            <?php if (isset($_SESSION['user_email'])): ?>
                <a href="?logout=1" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary"><i class="fab fa-google"></i> Login with Google</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="floating-elements">
            <div class="floating-element" style="width: 100px; height: 100px; top: 20%; left: 10%; animation-delay: 0s;"></div>
            <div class="floating-element" style="width: 150px; height: 150px; top: 60%; left: 80%; animation-delay: 2s;"></div>
            <div class="floating-element" style="width: 80px; height: 80px; top: 80%; left: 20%; animation-delay: 4s;"></div>
            <div class="floating-element" style="width: 120px; height: 120px; top: 30%; left: 70%; animation-delay: 6s;"></div>
        </div>
        <div class="hero-content">
            <h1 data-aos="fade-up" data-aos-delay="200">
                Petualangan Tak Terlupakan di Pekalongan
            </h1>
            <p data-aos="fade-up" data-aos-delay="400">
                Temukan pengalaman wisata alam premium dengan pemandu profesional kami.
                Rafting, camping, dan outbound dengan standar keselamatan tertinggi.
            </p>
            <div class="visitor-stats" data-aos="fade-up" data-aos-delay="500">
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $daily_visitors; ?>">0</div>
                    <div class="stat-label">Pengunjung/Hari</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $weekly_visitors; ?>">0</div>
                    <div class="stat-label">Pengunjung/Minggu</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" data-count="<?php echo $monthly_visitors; ?>">0</div>
                    <div class="stat-label">Pengunjung/Bulan</div>
                </div>
            </div>
            <div class="cta-buttons" data-aos="fade-up" data-aos-delay="600">
                <?php if (isset($_SESSION['user_email'])): ?>
                    <a href="booking.php" class="btn btn-primary"><i class="fas fa-ticket-alt"></i> Pesan Sekarang</a>
                <?php else: ?>
                    <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary"><i class="fab fa-google"></i> Login untuk Pesan</a>
                <?php endif; ?>
                <a href="#events" class="btn btn-secondary">Event Terbaru</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="section-title" data-aos="fade-up">
            <h2>Tentang Lolong Adventure</h2>
            <p>
                Penyedia jasa wisata alam profesional dengan pengalaman lebih dari 10 tahun
            </p>
        </div>
        <div class="about-content">
            <div class="about-text" data-aos="fade-right">
                <h3>Membawa Anda Menjelajahi Keindahan Alam</h3>
                <p>
                    Lolong Adventure adalah pionir dalam wisata petualangan di Pekalongan.
                    Didirikan pada tahun 2010, kami telah membawa ribuan petualang mengeksplorasi
                    keindahan alam Jawa Tengah.
                </p>
                <p>
                    Dengan tim pemandu bersertifikat dan peralatan standar internasional,
                    kami menjamin pengalaman wisata yang aman namun tetap menantang bagi
                    semua tingkatan, dari pemula hingga profesional.
                </p>
                <p>
                    Kami berkomitmen untuk memberikan pelayanan terbaik sambil tetap
                    menjaga kelestarian alam dan mendukung masyarakat lokal.
                </p>
                <div class="cta-buttons" style="justify-content: flex-start; margin-top: 30px;">
                    <a href="#contact" class="btn btn-primary">Hubungi Kami</a>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php" class="btn btn-ghost">Pesan Tiket</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-ghost">Login untuk Pesan</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="about-image" data-aos="fade-left" data-aos-delay="200">
                <img src="./assets/images/landingpage/bg_landingpage.jpg" alt="Tim Lolong Adventure" />
            </div>
        </div>
    </section>

    <!-- Destinations Section -->
    <section class="section destinations" id="destinations" style="background-color: var(--secondary);">
        <div class="section-title" data-aos="fade-up">
            <h2>Destinasi Wisata</h2>
            <p>
                Temukan pengalaman menakjubkan yang bisa Anda nikmati bersama kami
            </p>
        </div>
        <div class="destination-grid">
            <!-- Arung Jeram -->
            <div class="destination-card" data-aos="fade-up" data-aos-delay="100">
                <div class="card-image">
                    <img src="./assets/images/landingpage/arung-jeram.jpg" alt="Arung Jeram" />
                    <span class="card-badge">Populer</span>
                </div>
                <div class="card-content">
                    <h3>Arung Jeram</h3>
                    <p>
                        Nikmati sensasi arung jeram di Sungai Sengkarang dengan pemandangan alam
                        yang memukau. Cocok untuk pemula dan berpengalaman.
                    </p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                        <span><i class="fas fa-clock"></i> 3-4 Jam</span>
                    </div>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?package=rafting" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Pesan Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Login untuk Pesan</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Camp Area -->
            <div class="destination-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-image">
                    <img src="./assets/images/landingpage/camp-area.jpg" alt="Camp-Area" />
                    <span class="card-badge">Favorit Keluarga</span>
                </div>
                <div class="card-content">
                    <h3>Camp Area</h3>
                    <p>
                        Kegiatan berkemah atau outbound dengan fasilitas lengkap dan juga luas . Cocok untuk keluarga,sekolah ataupun kelompok.
                    </p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                        <span><i class="fas fa-user-friends"></i> Max 50 Orang</span>
                    </div>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?package=outbound" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Pesan Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Login untuk Pesan</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- River Coffe -->
            <div class="destination-card" data-aos="fade-up" data-aos-delay="300">
                <div class="card-image">
                    <img src="./assets/images/landingpage/river-cafe.jpg" alt="Camping" />
                    <span class="card-badge">Favorit Pengunjung</span>
                </div>
                <div class="card-content">
                    <h3>River Coffe</h3>
                    <p> Sebuah kafe dengan nuansa santai dan vibes yang asyik. Cocok banget buat ngopi dan quality time bareng bestie, keluarga, atau pasangan tersayang.</p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                        <span><i class="fas fa-user-friends"></i> Max 50 Orang</span>
                    </div>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?package=camping" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Pesan Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Login untuk Pesan</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="section" id="events">
        <div class="section-title" data-aos="fade-up">
            <h2>Event & Kegiatan</h2>
            <p>
                Ikuti event seru kami atau buat acara khusus untuk kelompok Anda
            </p>
        </div>
        <div class="events-container">
            <!-- Event 1 -->
            <div class="event-card" data-aos="fade-up" data-aos-delay="100">
                <div class="event-image">
                    <img src="./assets/images/events/Festival-durian.jpg" alt="Festival Durian Lolong" />
                    <div class="event-date">
                        <span class="event-day">15</span>
                        <span class="event-month">Juli</span>
                    </div>
                    <span class="event-category">Umum</span>
                </div>
                <div class="event-content">
                    <h3>Festival Durian Lolong</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Basecamp Lolong</span>
                        <span><i class="fas fa-clock"></i> 07.00 - 15.00</span>
                    </div>
                    <p class="event-excerpt">
                        Festival Durian Lolong merupakan bagian dari Lolong Culture Festival. Tujuan dari festival ini adalah untuk mempromosikan durian Kabupaten Pekalongan dan memperkenalkan potensi wisata daerah. Selain itu, festival ini juga menjadi ajang untuk bersyukur atas hasil panen durian yang melimpah.
                    </p>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?event=family-gathering" class="btn btn-primary">Daftar Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary">Login untuk Daftar</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event 2 -->
            <div class="event-card" data-aos="fade-up" data-aos-delay="200">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1527525443983-6e60c75fff46?auto=format&fit=crop&w=2065&q=80" alt="Corporate Outbound" />
                    <div class="event-date">
                        <span class="event-day">22</span>
                        <span class="event-month">Juli</span>
                    </div>
                    <span class="event-category">Corporate</span>
                </div>
                <div class="event-content">
                    <h3>Corporate Outbound Training</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Area Outbound</span>
                        <span><i class="fas fa-clock"></i> 08.00 - 17.00</span>
                    </div>
                    <p class="event-excerpt">
                        Program untuk meningkatkan teamwork dan leadership di alam.
                    </p>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?event=corporate-outbound" class="btn btn-primary">Daftar Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary">Login untuk Daftar</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event 3 -->
            <div class="event-card" data-aos="fade-up" data-aos-delay="300">
                <div class="event-image">
                    <img src="https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=2070&q=80" alt="Night Camping" />
                    <div class="event-date">
                        <span class="event-day">29</span>
                        <span class="event-month">Juli</span>
                    </div>
                    <span class="event-category">Adventure</span>
                </div>
                <div class="event-content">
                    <h3>Night Camping Adventure</h3>
                    <div class="event-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Camping Ground</span>
                        <span><i class="fas fa-clock"></i> 16.00 - 10.00</span>
                    </div>
                    <p class="event-excerpt">
                        Camping malam dengan api unggun, observasi bintang, dan trekking.
                    </p>
                    <?php if (isset($_SESSION['user_email'])): ?>
                        <a href="booking.php?event=night-camping" class="btn btn-primary">Daftar Sekarang</a>
                    <?php else: ?>
                        <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary">Login untuk Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="view-all-events" data-aos="fade-up">
            <a href="" class="btn btn-secondary">Lihat Semua Event</a>
        </div>
    </section>

    <!-- Map & Location Section -->
    <section class="map-section" id="location">
        <div class="section-title" data-aos="fade-up">
            <h2>Lokasi Kami</h2>
            <p>
                Temukan jalur menuju basecamp Lolong Adventure dengan mudah
            </p>
        </div>
        <div class="map-container">
            <div class="map-wrapper" data-aos="fade-up" data-aos-delay="200">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63362.0374630634!2d109.6119104!3d-7.0688584!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e701ec04b9c8b5b%3A0xd2f6294441946c79!2sLolong%20Adventure!5e0!3m2!1sid!2sid!4v1717130623456!5m2!1sid!2sid" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <div class="nearby-attractions" data-aos="fade-up">
            <h3 style="text-align: center; margin-bottom: 30px; color: var(--primary);">Tempat Menarik di Sekitar</h3>
            <div class="attractions-grid">
                <!-- Attraction 1 -->
                <div class="attraction-card">
                    <div class="attraction-image">
                        <img src="./assets/images/landingpage/sipare.webp" alt="Sipare Green Park" />
                    </div>
                    <div class="attraction-content">
                        <h4>Sipare Green Park</h4>
                        <p>Wisata alam yang asri dengan udara sejuk, melalui perkebunan belimbing dan karet.</p>
                        <div class="attraction-meta">
                            <span class="attraction-distance"><i class="fas fa-location-arrow"></i> 3.5 km dari basecamp</span>
                        </div>
                    </div>
                </div>

                <!-- Attraction 2 -->
                <div class="attraction-card">
                    <div class="attraction-image">
                        <img src="./assets/images/landingpage/sigarung.jpg" alt="Sigarung" />
                    </div>
                    <div class="attraction-content">
                        <h4>Sigarung</h4>
                        <p>Kedai kopi khas Lolong dengan cita rasa unik dan berbagai jenis durian.</p>
                        <div class="attraction-meta">
                            <span class="attraction-distance"><i class="fas fa-location-arrow"></i> 8 km dari basecamp</span>
                        </div>
                    </div>
                </div>

                <!-- Attraction 3 -->
                <div class="attraction-card">
                    <div class="attraction-image">
                        <img src="./assets/images/landingpage/sokolangit.webp" alt="Soko Langit" />
                    </div>
                    <div class="attraction-content">
                        <h4>Soko Langit</h4>
                        <p>Destinasi wisata edukasi dengan Dino Park untuk anak-anak.</p>
                        <div class="attraction-meta">
                            <span class="attraction-distance"><i class="fas fa-location-arrow"></i> 12 km dari basecamp</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2 data-aos="fade-UP">Siap Untuk Petualangan?</h2>
            <p data-aos="fade-up" data-aos-delay="200">
                Pesan tiket Anda sekarang dan nikmati pengalaman wisata alam terbaik di Pekalongan.
            </p>
            <div class="cta-buttons" data-aos="fade-up" data-aos-delay="400">
                <?php if (isset($_SESSION['user_email'])): ?>
                    <a href="booking.php" class="btn btn-primary"><i class="fas fa-ticket-alt"></i> Pesan Tiket</a>
                <?php else: ?>
                    <a href="<?php echo $client->createAuthUrl(); ?>" class="btn btn-primary"><i class="fab fa-google"></i> Login untuk Pesan</a>
                <?php endif; ?>
                <a href="https://wa.me/6281229952175" class="btn btn-secondary"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-column" data-aos="fade-up">
                <h3>Tentang Kami</h3>
                <p>
                    Lolong Adventure adalah penyedia jasa wisata alam profesional di Pekalongan
                    dengan pengalaman lebih dari 10 tahun melayani ribuan pelanggan.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <div class="footer-column" data-aos="fade-up" data-aos-delay="100">
                <h3>Link Cepat</h3>
                <ul class="footer-links">
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#destinations">Destinasi</a></li>
                    <li><a href="#events">Event</a></li>
                    <li><a href="#location">Lokasi</a></li>
                    <li><a href="booking.php">Pesan Tiket</a></li>
                </ul>
            </div>

            <div class="footer-column" data-aos="fade-up" data-aos-delay="200">
                <h3>Kontak Kami</h3>
                <p><i class="fas fa-map-marker-alt"></i> Jl. Raya Lolong No. 123, Pekalongan, Jawa Tengah</p>
                <p><i class="fas fa-phone-alt"></i> +62 812 3456 7890</p>
                <p><i class="fas fa-envelope"></i> info@lolongadventure.com</p>
                <p><i class="fas fa-clock"></i> Buka setiap hari 08.00 - 17.00 WIB</p>
            </div>

            <div class="footer-column" data-aos="fade-up" data-aos-delay="300">
                <h3>Newsletter</h3>
                <p>
                    Daftar untuk mendapatkan informasi promo dan event terbaru dari kami.
                </p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Alamat Email Anda" required />
                    <button type="submit" class="btn btn-primary">Berlangganan</button>
                </form>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Lolong Adventure. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <?php if (isset($_SESSION['user_email'])): ?>
            <div class="sidebar-item">
                <a href="javascript:void(0);">
                    <i class="fas fa-ticket"></i>
                    <span>Status Pemesanan</span>
                </a>
                <?php if (!empty($booking_status)): ?>
                    <div class="booking-status <?php echo $booking_status['status']; ?>">
                        Kode: <?php echo $booking_status['code']; ?><br>
                        Status: <?php echo ucfirst($booking_status['status']); ?>
                    </div>
                <?php else: ?>
                    <div class="booking-status">Belum ada pemesanan</div>
                <?php endif; ?>
            </div>
            <div class="sidebar-item">
                <a href="?logout=1">
                    <i class="fas fa-user"></i>
                    <span>Profil (Logout)</span>
                </a>
            </div>
        <?php else: ?>
            <div class="sidebar-item">
                <a href="<?php echo $client->createAuthUrl(); ?>">
                    <i class="fab fa-google"></i>
                    <span>Login</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS animation
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Navbar scroll effect
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navLinks = document.getElementById('navLinks');

        mobileMenuBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ?
                '<i class="fas fa-times"></i>' :
                '<i class="fas fa-bars"></i>';
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            });
        });

        // Create floating particles
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');

            const size = Math.random() * 10 + 5;
            const posX = Math.random() * 100;
            const posY = Math.random() * 100;
            const delay = Math.random() * 15;
            const duration = Math.random() * 20 + 10;

            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${posX}%`;
            particle.style.top = `${posY}%`;
            particle.style.animationDelay = `${delay}s`;
            particle.style.animationDuration = `${duration}s`;

            particlesContainer.appendChild(particle);
        }

        // Counter animation for visitor stats
        function animateCounter(element, target, duration) {
            let start = 0;
            const increment = target / (duration / 16);
            const updateCounter = () => {
                start += increment;
                if (start >= target) {
                    element.textContent = target;
                    return;
                }
                element.textContent = Math.floor(start);
                requestAnimationFrame(updateCounter);
            };
            requestAnimationFrame(updateCounter);
        }

        document.querySelectorAll('.stat-number').forEach(stat => {
            const target = parseInt(stat.getAttribute('data-count'));
            animateCounter(stat, target, 2000);
        });

        // Sidebar toggle on scroll
        const sidebar = document.getElementById('sidebar');
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            if (currentScroll > lastScroll && currentScroll > 200) {
                sidebar.classList.add('hidden');
            } else {
                sidebar.classList.remove('hidden');
            }
            lastScroll = currentScroll;
        });
    </script>
</body>

</html>