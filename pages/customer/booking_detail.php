<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get booking details with proper image paths
$stmt = $pdo->prepare("SELECT b.*, 
                      d.name as destination_name, 
                      CASE 
                        WHEN d.name = 'Camp Area' THEN '../../assets/images/destinations/camparea.jpg'
                        WHEN d.name = 'Arung Jeram' THEN '../../assets/images/destinations/arungjeram.jpg'
                        ELSE d.image_url
                      END as image_url,
                      d.price as destination_price,
                      d.description as destination_desc, 
                      d.location,
                      u.full_name, u.email, u.phone, u.address
                      FROM bookings b 
                      JOIN destinations d ON b.destination_id = d.id
                      JOIN users u ON b.user_id = u.id
                      WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// If booking not found
if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Format dates
$booking_date = date('d F Y H:i', strtotime($booking['booking_date']));
$visit_date = date('d F Y', strtotime($booking['booking_date']));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pemesanan - <?php echo htmlspecialchars($booking['booking_code']); ?> - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #e6e9ff;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --sidebar-width: 280px;
            --border-radius: 12px;
            --box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        /* Sidebar Styles */
        #sidebar {
            width: var(--sidebar-width);
            background: var(--dark-color);
            color: white;
            transition: var(--transition);
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-components a {
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            border-left: 4px solid transparent;
            transition: var(--transition);
        }

        .sidebar-components a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--accent-color);
        }

        .sidebar-components .active>a {
            background: rgba(67, 97, 238, 0.2);
            color: white;
            border-left-color: var(--primary-color);
        }

        /* Main Content */
        .main-content {
            background-color: #f5f7ff;
            min-height: 100vh;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
        }

        /* Navbar */
        .navbar-custom {
            background: white !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            padding: 0.8rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        /* Booking Detail Card */
        .booking-detail-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            background: white;
        }

        .booking-detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .booking-detail-card .card-header {
            background: var(--primary-color);
            color: white;
            padding: 1.25rem;
            border-bottom: none;
        }

        .booking-image-container {
            height: 280px;
            overflow: hidden;
            position: relative;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .booking-image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .booking-image-container:hover .booking-image {
            transform: scale(1.05);
        }

        /* Info Items */
        .info-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pending {
            background-color: var(--warning-color);
            color: #212529;
        }

        .badge-confirmed {
            background-color: var(--success-color);
            color: white;
        }

        .badge-completed {
            background-color: var(--info-color);
            color: white;
        }

        .badge-cancelled {
            background-color: var(--danger-color);
            color: white;
        }

        /* Price Display */
        .price-display {
            font-size: 1.25rem;
            color: var(--primary-color);
            font-weight: 700;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-payment {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .btn-payment:hover {
            background: #218838;
            border-color: #218838;
            color: white;
        }

        /* Floating Action Button for Mobile */
        .floating-action-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99;
            display: none;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Responsive Adjustments */
        @media (max-width: 1200px) {
            :root {
                --sidebar-width: 250px;
            }
        }

        @media (max-width: 992px) {
            .booking-image-container {
                height: 220px;
            }
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -280px;
            }

            #sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .booking-image-container {
                height: 200px;
            }

            .floating-action-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .action-buttons .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .booking-image-container {
                height: 180px;
            }

            .info-item .row>div {
                margin-bottom: 0.5rem;
            }

            .info-label,
            .info-value {
                width: 100%;
                display: block;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                margin-right: 0 !important;
                margin-bottom: 0.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade {
            animation: fadeIn 0.5s ease-out;
        }

        /* Hover effects */
        .hover-effect {
            transition: var(--transition);
        }

        .hover-effect:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="wrapper d-flex">
        <!-- Sidebar -->
        <nav id="sidebar" class="vh-100">
            <div class="sidebar-header">
                <h3><i class="bi bi-compass me-2"></i> Lolong Adventure</h3>
            </div>

            <ul class="nav flex-column sidebar-components px-2">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="destinations.php">
                        <i class="bi bi-map me-2"></i> Destinasi Wisata
                    </a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="bookings.php">
                        <i class="bi bi-calendar-check me-2"></i> Pemesanan Saya
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="new_booking.php">
                        <i class="bi bi-plus-circle me-2"></i> Pesan Tiket Baru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="payment.php">
                        <i class="bi bi-credit-card me-2"></i> Pembayaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="testimonials.php">
                        <i class="bi bi-chat-square-quote me-2"></i> Testimoni
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person-circle me-2"></i> Profil Saya
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer position-absolute bottom-0 w-100 p-3 text-center">
                <a href="../../logout.php" class="btn btn-sm btn-outline-light w-75">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Floating Action Button for Mobile -->
        <button class="btn btn-primary floating-action-btn" id="mobileMenuBtn">
            <i class="bi bi-list" style="font-size: 1.5rem;"></i>
        </button>

        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary d-none d-md-block">
                        <i class="bi bi-list"></i>
                        <span class="ms-2">Menu</span>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
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

            <!-- Booking Detail Content -->
            <div class="container-fluid p-3 p-md-4 animate__animated animate__fadeIn">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i></a></li>
                        <li class="breadcrumb-item"><a href="bookings.php">Pemesanan Saya</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detail Pemesanan</li>
                    </ol>
                </nav>

                <!-- Booking Header -->
                <div class="booking-detail-card card mb-4 hover-effect">
                    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <div class="mb-2 mb-md-0">
                            <h4 class="mb-0"><i class="bi bi-ticket-detailed me-2"></i> Detail Pemesanan</h4>
                            <p class="mb-0 text-white-50">Kode Booking: <?php echo htmlspecialchars($booking['booking_code']); ?></p>
                        </div>
                        <span class="status-badge badge-<?php echo strtolower($booking['status']); ?> mt-2 mt-md-0">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <!-- Destination Info -->
                            <div class="col-lg-5 mb-4 mb-lg-0">
                                <div class="booking-image-container mb-3">
                                    <img src="<?php echo htmlspecialchars($booking['image_url']); ?>"
                                        class="booking-image"
                                        alt="<?php echo htmlspecialchars($booking['destination_name']); ?>">
                                </div>
                                <div class="mt-3">
                                    <h4><?php echo htmlspecialchars($booking['destination_name']); ?></h4>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-geo-alt-fill me-1"></i>
                                        <?php echo htmlspecialchars($booking['location']); ?>
                                    </p>
                                    <p class="text-muted"><?php echo htmlspecialchars($booking['destination_desc']); ?></p>
                                </div>
                            </div>

                            <!-- Booking Details -->
                            <div class="col-lg-7">
                                <div class="booking-detail-card card mb-4 hover-effect">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Informasi Pemesanan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Tanggal Pemesanan</div>
                                                <div class="col-md-8 info-value"><?php echo $booking_date; ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Tanggal Kunjungan</div>
                                                <div class="col-md-8 info-value"><?php echo $visit_date; ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Jumlah Peserta</div>
                                                <div class="col-md-8 info-value"><?php echo htmlspecialchars($booking['participants']); ?> orang</div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Harga Tiket</div>
                                                <div class="col-md-8 info-value">Rp <?php echo number_format($booking['destination_price'], 0, ',', '.'); ?> /orang</div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Total Pembayaran</div>
                                                <div class="col-md-8 info-value">
                                                    <span class="price-display">Rp <?php echo number_format($booking['destination_price'] * $booking['participants'], 0, ',', '.'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['notes'])): ?>
                                            <div class="info-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4 info-label">Catatan</div>
                                                    <div class="col-md-8 info-value"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Customer Information -->
                                <div class="booking-detail-card card mb-4 hover-effect">
                                    <div class="card-header bg-white border-bottom">
                                        <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i> Informasi Pelanggan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Nama Lengkap</div>
                                                <div class="col-md-8 info-value"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Email</div>
                                                <div class="col-md-8 info-value"><?php echo htmlspecialchars($booking['email']); ?></div>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-4 info-label">Telepon</div>
                                                <div class="col-md-8 info-value"><?php echo htmlspecialchars($booking['phone']); ?></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['address'])): ?>
                                            <div class="info-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4 info-label">Alamat</div>
                                                    <div class="col-md-8 info-value"><?php echo (htmlspecialchars($booking['address'])); ?></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-4 action-buttons">
                                    <a href="bookings.php" class="btn btn-outline-secondary mb-3 mb-md-0 action-btn">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                                    </a>

                                    <div class="d-flex flex-column flex-md-row">
                                        <?php if (strtolower($booking['status']) == 'pending'): ?>
                                            <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success me-md-2 mb-2 mb-md-0 action-btn">
                                                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
                                            </a>
                                            <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-danger action-btn" onclick="return confirm('Yakin ingin membatalkan pemesanan?')">
                                                <i class="bi bi-x-circle me-1"></i> Batalkan
                                            </a>
                                        <?php elseif (strtolower($booking['status']) == 'confirmed' && strtotime($booking['booking_date']) >= strtotime('today')): ?>
                                            <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-danger action-btn" onclick="return confirm('Yakin ingin membatalkan pemesanan?')">
                                                <i class="bi bi-x-circle me-1"></i> Batalkan
                                            </a>
                                        <?php endif; ?>

                                        <?php if (in_array(strtolower($booking['status']), ['completed', 'confirmed']) && strtotime($booking['booking_date']) < strtotime('today')): ?>
                                            <a href="testimonials.php?destination_id=<?php echo $booking['destination_id']; ?>" class="btn btn-primary ms-md-2 action-btn">
                                                <i class="bi bi-chat-square-text me-1"></i> Beri Testimoni
                                            </a>
                                        <?php endif; ?>
                                    </div>
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
            // Sidebar toggle for desktop
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('active');
            });

            // Sidebar toggle for mobile
            $('#mobileMenuBtn').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Highlight active menu item
            var current = location.pathname.split('/').pop();
            $('#sidebar .nav-link').each(function() {
                var $this = $(this);
                if ($this.attr('href') === current) {
                    $this.addClass('active');
                    $this.parent().addClass('active');
                }
            });

            // Add loading spinner to buttons when clicked
            $('.action-btn').on('click', function() {
                var btn = $(this);
                btn.prop('disabled', true);
                btn.prepend('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>');
            });

            // Adjust sidebar height on mobile
            function adjustSidebarHeight() {
                if ($(window).width() < 768) {
                    $('#sidebar').css('height', '100vh');
                } else {
                    $('#sidebar').css('height', '');
                }
            }

            // Run on load and resize
            adjustSidebarHeight();
            $(window).resize(adjustSidebarHeight);
        });
    </script>
</body>

</html>