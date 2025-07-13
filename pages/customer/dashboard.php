<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get user-specific data
$user_id = $_SESSION['user_id'];

// Get user bookings count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();

// Get upcoming bookings
$stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, d.image_url 
                      FROM bookings b 
                      JOIN destinations d ON b.destination_id = d.id 
                      WHERE b.user_id = ? AND b.booking_date >= CURDATE()
                      ORDER BY b.booking_date ASC LIMIT 3");
$stmt->execute([$user_id]);
$upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular destinations
$stmt = $pdo->query("SELECT d.id, d.name, d.location, d.price, d.image_url, COUNT(b.id) as booking_count 
                     FROM destinations d 
                     LEFT JOIN bookings b ON d.id = b.destination_id 
                     GROUP BY d.id 
                     ORDER BY booking_count DESC 
                     LIMIT 4");
$popular_destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Customer - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --sidebar-width: 280px;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #2ecc71;
        }

        body {
            background-color: var(--light-color);
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
        }

        /* Sidebar */
        #sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            background: var(--dark-color);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            transform: translateX(0);
        }

        #sidebar.collapsed {
            transform: translateX(calc(var(--sidebar-width) * -1));
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #sidebar .sidebar-header h3 {
            margin-bottom: 0;
            font-weight: 600;
            white-space: nowrap;
        }

        #sidebar ul.components {
            padding: 20px 0;
            overflow-y: auto;
            height: calc(100vh - 120px);
        }

        #sidebar ul li {
            margin: 5px 0;
        }

        #sidebar ul li a {
            padding: 12px 25px;
            color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
            white-space: nowrap;
        }

        #sidebar ul li a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.05);
            border-left: 4px solid var(--accent-color);
        }

        #sidebar ul li.active>a {
            color: white;
            background: rgba(67, 97, 238, 0.2);
            border-left: 4px solid var(--primary-color);
        }

        #sidebar ul li a i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        #sidebar .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }

        .main-content.full-width {
            margin-left: 0;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            background: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(67, 97, 238, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Custom Colors */
        .bg-primary-custom {
            background-color: var(--primary-color) !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        /* Badges */
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-confirmed {
            background-color: var(--success-color);
            color: white;
        }

        .badge-completed {
            background-color: #17a2b8;
            color: white;
        }

        .badge-cancelled {
            background-color: #dc3545;
            color: white;
        }

        /* Destination Cards */
        .destination-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
        }

        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .destination-img {
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .destination-card:hover .destination-img {
            transform: scale(1.05);
        }

        /* Price Tag */
        .price-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }

        /* Greeting Section */
        .greeting-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Navbar */
        .navbar {
            padding: 15px 20px;
            background: white !important;
            border-bottom: 1px solid #eee;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* Booking Cards */
        .booking-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .booking-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            border-color: rgba(67, 97, 238, 0.2);
        }

        .booking-img {
            height: 140px;
            object-fit: cover;
        }

        /* Payment Button */
        .btn-payment {
            background-color: var(--success-color);
            color: white;
            border: none;
        }

        .btn-payment:hover {
            background-color: #27ae60;
            color: white;
        }

        /* Responsive Adjustments */
        @media (max-width: 1199.98px) {
            :root {
                --sidebar-width: 250px;
            }
        }

        @media (max-width: 991.98px) {
            #sidebar {
                transform: translateX(calc(var(--sidebar-width) * -1));
                z-index: 1050;
            }

            #sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .overlay {
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

            .overlay.active {
                opacity: 1;
                visibility: visible;
            }
        }

        @media (max-width: 767.98px) {
            .greeting-section {
                padding: 20px;
            }

            .destination-img,
            .booking-img {
                height: 160px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
            }

            .stat-icon i {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 575.98px) {
            .greeting-section .col-md-8 h3 {
                font-size: 1.5rem;
            }

            .card-header h5 {
                font-size: 1.25rem;
            }

            .destination-img,
            .booking-img {
                height: 140px;
            }

            .price-tag {
                font-size: 0.8rem;
                padding: 3px 8px;
            }
        }

        /* Animation for sidebar toggle */
        @keyframes slideIn {
            from {
                transform: translateX(calc(var(--sidebar-width) * -1));
            }

            to {
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
            }

            to {
                transform: translateX(calc(var(--sidebar-width) * -1));
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Overlay for mobile -->
        <div class="overlay"></div>

        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-compass me-2"></i> Lolong Adventure</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="destinations.php">
                        <i class="bi bi-map"></i>
                        <span class="nav-text">Destinasi Wisata</span>
                    </a>
                </li>
                <li>
                    <a href="bookings.php">
                        <i class="bi bi-calendar-check"></i>
                        <span class="nav-text">Pemesanan Saya</span>
                        <span class="badge bg-primary ms-auto"><?php echo $bookings_count; ?></span>
                    </a>
                </li>
                <li>
                    <a href="new_booking.php">
                        <i class="bi bi-plus-circle"></i>
                        <span class="nav-text">Pesan Tiket Baru</span>
                    </a>
                </li>
                <li>
                    <a href="payment.php">
                        <i class="bi bi-credit-card"></i>
                        <span class="nav-text">Pembayaran</span>
                    </a>
                </li>
                <li>
                    <a href="testimonials.php">
                        <i class="bi bi-chat-square-quote"></i>
                        <span class="nav-text">Testimoni</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <i class="bi bi-person-circle"></i>
                        <span class="nav-text">Profil Saya</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="../../logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div id="content" class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary-custom">
                        <i class="bi bi-list"></i>
                        <span class="ms-2 d-none d-sm-inline">Menu</span>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i> Profil</a></li>
                                <li><a class="dropdown-item" href="bookings.php"><i class="bi bi-calendar-check me-2"></i> Pemesanan</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="../../logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-compass me-2"></i> Selamat Datang, <?php echo $_SESSION['full_name']; ?>!</h3>
                            <p class="mb-0">Mulai petualangan Anda bersama Lolong Adventure</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-inline-block bg-white rounded-pill px-3 py-2">
                                <i class="bi bi-calendar-check text-primary-custom me-2"></i>
                                <span class="fw-bold"><?php echo date('l, d F Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4 g-3">
                    <div class="col-md-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Total Pemesanan</h5>
                                        <h3 class="mb-0"><?php echo $bookings_count; ?></h3>
                                    </div>
                                </div>
                                <a href="bookings.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-map"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Destinasi Tersedia</h5>
                                        <h3 class="mb-0"><?php echo count($popular_destinations); ?></h3>
                                    </div>
                                </div>
                                <a href="destination.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title">Pembayaran</h5>
                                        <h3 class="mb-0"><?php echo $bookings_count; ?></h3>
                                    </div>
                                </div>
                                <a href="payments.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Bookings -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Pemesanan Mendatang</h5>
                                <a href="bookings.php" class="btn btn-sm btn-light">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <?php if (count($upcoming_bookings) > 0): ?>
                                    <div class="row g-3">
                                        <?php foreach ($upcoming_bookings as $booking): ?>
                                            <div class="col-md-4">
                                                <div class="booking-card">
                                                    <img src="<?php echo $booking['image_url'] ?? 'https://via.placeholder.com/300x200?text=Destinasi'; ?>"
                                                        class="card-img-top booking-img"
                                                        alt="<?php echo $booking['destination_name']; ?>">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo $booking['destination_name']; ?></h5>
                                                        <p class="card-text">
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?>
                                                            </small>
                                                        </p>
                                                        <p class="card-text">
                                                            <i class="bi bi-people"></i> <?php echo $booking['participants']; ?> Peserta
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge rounded-pill bg-<?php echo strtolower($booking['status']); ?>">
                                                                <?php echo ucfirst($booking['status']); ?>
                                                            </span>
                                                            <?php if ($booking['status'] == 'pending'): ?>
                                                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>"
                                                                    class="btn btn-payment btn-sm">
                                                                    <i class="bi bi-credit-card me-1"></i> Bayar
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x" style="font-size: 3rem; color: #6c757d;"></i>
                                        <h5 class="mt-3">Tidak ada pemesanan mendatang</h5>
                                        <p class="text-muted">Anda belum memiliki pemesanan yang akan datang</p>
                                        <a href="new_booking.php" class="btn btn-primary-custom">
                                            <i class="bi bi-plus-circle me-1"></i> Buat Pemesanan Baru
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Destinations -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-star-fill me-2"></i> Destinasi Populer</h5>
                                <a href="destination.php" class="btn btn-sm btn-light">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php foreach ($popular_destinations as $destination): ?>
                                        <div class="col-xl-3 col-lg-4 col-md-6">
                                            <div class="card destination-card">
                                                <div class="position-relative overflow-hidden">
                                                    <img src="<?php echo $destination['image_url'] ?? 'https://via.placeholder.com/300x200?text=Destinasi'; ?>"
                                                        class="card-img-top destination-img"
                                                        alt="<?php echo $destination['name']; ?>">
                                                    <div class="price-tag">
                                                        Rp <?php echo number_format($destination['price'], 0, ',', '.'); ?>
                                                    </div>
                                                    <?php if ($destination['booking_count'] > 10): ?>
                                                        <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                                            <i class="bi bi-star-fill"></i> Populer
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo $destination['name']; ?></h5>
                                                    <p class="card-text text-muted">
                                                        <i class="bi bi-geo-alt"></i> <?php echo $destination['location']; ?>
                                                    </p>
                                                    <div class="d-grid gap-2">
                                                        <a href="destination_detail.php?id=<?php echo $destination['id']; ?>"
                                                            class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-eye me-1"></i> Detail
                                                        </a>
                                                        <a href="new_booking.php?destination_id=<?php echo $destination['id']; ?>"
                                                            class="btn btn-primary-custom btn-sm">
                                                            <i class="bi bi-calendar-plus me-1"></i> Pesan
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar toggle with smooth animation
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('show');
                $('.overlay').toggleClass('active');

                // Store sidebar state in localStorage
                if ($('#sidebar').hasClass('show')) {
                    localStorage.setItem('sidebarState', 'expanded');
                } else {
                    localStorage.setItem('sidebarState', 'collapsed');
                }
            });

            // Close sidebar when clicking on overlay (mobile)
            $('.overlay').on('click', function() {
                $('#sidebar').removeClass('show');
                $('.overlay').removeClass('active');
                localStorage.setItem('sidebarState', 'collapsed');
            });

            // Check saved sidebar state
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'expanded' && $(window).width() < 992) {
                $('#sidebar').addClass('show');
                $('.overlay').addClass('active');
            }

            // Highlight active menu item
            var current = location.pathname.split('/').pop();
            $('#sidebar ul li a').each(function() {
                var $this = $(this);
                if ($this.attr('href') === current) {
                    $this.parent().addClass('active');
                }
            });

            // Auto-close sidebar on mobile when clicking a link
            $('#sidebar ul li a').on('click', function() {
                if ($(window).width() < 992) {
                    $('#sidebar').removeClass('show');
                    $('.overlay').removeClass('active');
                    localStorage.setItem('sidebarState', 'collapsed');
                }
            });

            // Responsive adjustments
            function handleResponsive() {
                if ($(window).width() < 992) {
                    // Mobile view
                    if (!$('#sidebar').hasClass('show')) {
                        $('#sidebar').removeClass('show');
                        $('.overlay').removeClass('active');
                    }
                } else {
                    // Desktop view
                    $('#sidebar').removeClass('show');
                    $('.overlay').removeClass('active');
                }
            }

            // Run on load and resize
            handleResponsive();
            $(window).on('resize', handleResponsive);
        });
    </script>
</body>

</html>