<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get user-specific data
$user_id = $_SESSION['user_id'];

// Get all user bookings with updated image paths
$stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, d.price as destination_price,
                      CASE 
                        WHEN d.name = 'Camp Area' THEN '../../assets/images/destinations/camparea.jpg'
                        WHEN d.name = 'Arung Jeram' THEN '../../assets/images/destinations/arungjeram.jpg'
                        ELSE d.image_url
                      END as image_url
                      FROM bookings b 
                      JOIN destinations d ON b.destination_id = d.id 
                      WHERE b.user_id = ? 
                      ORDER BY b.booking_date DESC");
$stmt->execute([$user_id]);
$all_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count bookings by status
$status_counts = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($all_bookings as $booking) {
    $status = strtolower($booking['status'] ?? '');
    if (array_key_exists($status, $status_counts)) {
        $status_counts[$status]++;
    }
}

// Get upcoming bookings (for sidebar count)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_date >= CURDATE() AND status IN ('pending', 'confirmed')");
$stmt->execute([$user_id]);
$upcoming_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Saya - Lolong Adventure</title>
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

        /* Sidebar */
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

        /* Stat Cards */
        .stat-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            background: white;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        /* Booking Cards */
        .booking-card {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            overflow: hidden;
            background: white;
            height: 100%;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .booking-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .booking-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .booking-card:hover .booking-img {
            transform: scale(1.05);
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
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
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

        /* Empty State */
        .empty-state {
            padding: 3rem;
            text-align: center;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state-icon {
            font-size: 3.5rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
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
            .booking-img-container {
                height: 180px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
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

            .booking-img-container {
                height: 160px;
            }

            .floating-action-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .booking-img-container {
                height: 140px;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .empty-state {
                padding: 2rem 1rem;
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
                        <span class="badge bg-primary ms-auto"><?php echo count($all_bookings); ?></span>
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

            <!-- Booking Content -->
            <div class="container-fluid p-3 p-md-4 animate__animated animate__fadeIn">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bi bi-house-door"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pemesanan Saya</li>
                    </ol>
                </nav>

                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="mb-1"><i class="bi bi-calendar-check me-2"></i> Pemesanan Saya</h3>
                        <p class="text-muted mb-0">Kelola semua pemesanan Anda di Lolong Adventure</p>
                    </div>
                    <div class="d-flex">
                        <span class="badge bg-primary rounded-pill px-3 py-2">
                            <i class="bi bi-calendar me-1"></i> <?php echo date('d F Y'); ?>
                        </span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card hover-effect">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-hourglass"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Pending</h6>
                                        <h3 class="mb-0"><?php echo $status_counts['pending']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card hover-effect">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Dikonfirmasi</h6>
                                        <h3 class="mb-0"><?php echo $status_counts['confirmed']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card hover-effect">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-check-all"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Selesai</h6>
                                        <h3 class="mb-0"><?php echo $status_counts['completed']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-card hover-effect">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon me-3">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Dibatalkan</h6>
                                        <h3 class="mb-0"><?php echo $status_counts['cancelled']; ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Section -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i> Daftar Pemesanan</h5>
                        <a href="new_booking.php" class="btn btn-sm btn-light">
                            <i class="bi bi-plus-circle me-1"></i> Pesan Baru
                        </a>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                    Semua (<?php echo count($all_bookings); ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab">
                                    Mendatang (<?php echo $upcoming_count; ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                    Pending (<?php echo $status_counts['pending']; ?>)
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="bookingTabsContent">
                            <!-- All Bookings Tab -->
                            <div class="tab-pane fade show active" id="all" role="tabpanel">
                                <?php if (count($all_bookings) > 0): ?>
                                    <div class="row g-3">
                                        <?php foreach ($all_bookings as $booking): ?>
                                            <div class="col-lg-6">
                                                <div class="booking-card hover-effect">
                                                    <div class="row g-0">
                                                        <div class="col-md-4">
                                                            <div class="booking-img-container">
                                                                <img src="<?php echo htmlspecialchars($booking['image_url']); ?>"
                                                                    class="booking-img"
                                                                    alt="<?php echo htmlspecialchars($booking['destination_name']); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($booking['destination_name']); ?></h5>
                                                                    <span class="status-badge badge-<?php echo strtolower($booking['status']); ?>">
                                                                        <?php echo ucfirst($booking['status']); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-calendar me-1"></i> <?php echo date('d F Y', strtotime($booking['booking_date'])); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <i class="bi bi-people me-1"></i> <?php echo htmlspecialchars($booking['participants']); ?> Peserta
                                                                </div>
                                                                <div class="mb-3">
                                                                    <span class="price-display">
                                                                        <i class="bi bi-cash me-1"></i> Rp <?php echo number_format($booking['destination_price'] * $booking['participants'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary action-btn">
                                                                        <i class="bi bi-eye me-1"></i> Detail
                                                                    </a>
                                                                    <?php if (strtolower($booking['status']) == 'pending'): ?>
                                                                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success action-btn">
                                                                            <i class="bi bi-credit-card me-1"></i> Bayar
                                                                        </a>
                                                                    <?php elseif (strtolower($booking['status']) == 'confirmed' && strtotime($booking['booking_date']) >= strtotime('today')): ?>
                                                                        <a href="#" class="btn btn-outline-danger action-btn" onclick="return confirm('Yakin ingin membatalkan pemesanan?')">
                                                                            <i class="bi bi-x-circle me-1"></i> Batalkan
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-calendar-x"></i>
                                        </div>
                                        <h4 class="mt-3">Belum Ada Pemesanan</h4>
                                        <p class="text-muted mb-4">Anda belum membuat pemesanan apapun</p>
                                        <a href="new_booking.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Buat Pemesanan Baru
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Upcoming Bookings Tab -->
                            <div class="tab-pane fade" id="upcoming" role="tabpanel">
                                <?php
                                $upcoming_bookings = array_filter($all_bookings, function ($booking) {
                                    return strtotime($booking['booking_date']) >= strtotime('today') &&
                                        in_array(strtolower($booking['status']), ['pending', 'confirmed']);
                                });
                                ?>
                                <?php if (count($upcoming_bookings) > 0): ?>
                                    <div class="row g-3">
                                        <?php foreach ($upcoming_bookings as $booking): ?>
                                            <div class="col-lg-6">
                                                <div class="booking-card hover-effect">
                                                    <div class="row g-0">
                                                        <div class="col-md-4">
                                                            <div class="booking-img-container">
                                                                <img src="<?php echo htmlspecialchars($booking['image_url']); ?>"
                                                                    class="booking-img"
                                                                    alt="<?php echo htmlspecialchars($booking['destination_name']); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($booking['destination_name']); ?></h5>
                                                                    <span class="status-badge badge-<?php echo strtolower($booking['status']); ?>">
                                                                        <?php echo ucfirst($booking['status']); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-calendar me-1"></i> <?php echo date('d F Y', strtotime($booking['booking_date'])); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <i class="bi bi-people me-1"></i> <?php echo htmlspecialchars($booking['participants']); ?> Peserta
                                                                </div>
                                                                <div class="mb-3">
                                                                    <span class="price-display">
                                                                        <i class="bi bi-cash me-1"></i> Rp <?php echo number_format($booking['destination_price'] * $booking['participants'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary action-btn">
                                                                        <i class="bi bi-eye me-1"></i> Detail
                                                                    </a>
                                                                    <?php if (strtolower($booking['status']) == 'pending'): ?>
                                                                        <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success action-btn">
                                                                            <i class="bi bi-credit-card me-1"></i> Bayar
                                                                        </a>
                                                                    <?php elseif (strtolower($booking['status']) == 'confirmed'): ?>
                                                                        <a href="#" class="btn btn-outline-danger action-btn" onclick="return confirm('Yakin ingin membatalkan pemesanan?')">
                                                                            <i class="bi bi-x-circle me-1"></i> Batalkan
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <h4 class="mt-3">Tidak Ada Pemesanan Mendatang</h4>
                                        <p class="text-muted mb-4">Anda belum memiliki pemesanan yang akan datang</p>
                                        <a href="new_booking.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Buat Pemesanan Baru
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Pending Bookings Tab -->
                            <div class="tab-pane fade" id="pending" role="tabpanel">
                                <?php
                                $pending_bookings = array_filter($all_bookings, function ($booking) {
                                    return strtolower($booking['status']) == 'pending';
                                });
                                ?>
                                <?php if (count($pending_bookings) > 0): ?>
                                    <div class="row g-3">
                                        <?php foreach ($pending_bookings as $booking): ?>
                                            <div class="col-lg-6">
                                                <div class="booking-card hover-effect">
                                                    <div class="row g-0">
                                                        <div class="col-md-4">
                                                            <div class="booking-img-container">
                                                                <img src="<?php echo htmlspecialchars($booking['image_url']); ?>"
                                                                    class="booking-img"
                                                                    alt="<?php echo htmlspecialchars($booking['destination_name']); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($booking['destination_name']); ?></h5>
                                                                    <span class="status-badge badge-pending">
                                                                        Pending
                                                                    </span>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <small class="text-muted">
                                                                        <i class="bi bi-calendar me-1"></i> <?php echo date('d F Y', strtotime($booking['booking_date'])); ?>
                                                                    </small>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <i class="bi bi-people me-1"></i> <?php echo htmlspecialchars($booking['participants']); ?> Peserta
                                                                </div>
                                                                <div class="mb-3">
                                                                    <span class="price-display">
                                                                        <i class="bi bi-cash me-1"></i> Rp <?php echo number_format($booking['destination_price'] * $booking['participants'], 0, ',', '.'); ?>
                                                                    </span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary action-btn">
                                                                        <i class="bi bi-eye me-1"></i> Detail
                                                                    </a>
                                                                    <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-success action-btn">
                                                                        <i class="bi bi-credit-card me-1"></i> Bayar
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                        <h4 class="mt-3">Tidak Ada Pemesanan Pending</h4>
                                        <p class="text-muted mb-4">Semua pemesanan Anda telah diproses</p>
                                        <a href="new_booking.php" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-1"></i> Buat Pemesanan Baru
                                        </a>
                                    </div>
                                <?php endif; ?>
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