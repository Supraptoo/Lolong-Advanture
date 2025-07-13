<?php
session_start();
require_once('../../config/database.php');

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get all destinations with booking counts
try {
    $stmt = $pdo->query("SELECT d.*, COUNT(b.id) as booking_count 
                         FROM destinations d 
                         LEFT JOIN bookings b ON d.id = b.destination_id 
                         GROUP BY d.id 
                         ORDER BY d.name ASC");
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update image URLs to use local assets
    foreach ($destinations as &$dest) {
        if ($dest['name'] === 'Camp Area') {
            $dest['image_url'] = '../../assets/images/destinations/camparea.jpg';
        } elseif ($dest['name'] === 'Arung Jeram') {
            $dest['image_url'] = '../../assets/images/destinations/arungjeram.jpg';
        }
    }
    unset($dest); // Break the reference

} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database error: " . $e->getMessage());
    $destinations = [];
}

// Get popular destinations for the sidebar
try {
    $popular_stmt = $pdo->query("SELECT d.id, d.name, d.image_url, d.location, COUNT(b.id) as booking_count 
                                FROM destinations d 
                                LEFT JOIN bookings b ON d.id = b.destination_id 
                                GROUP BY d.id 
                                ORDER BY booking_count DESC 
                                LIMIT 4");
    $popular_destinations = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update image URLs for popular destinations
    foreach ($popular_destinations as &$dest) {
        if ($dest['name'] === 'Camp Area') {
            $dest['image_url'] = '../../assets/images/destinations/camparea.jpg';
        } elseif ($dest['name'] === 'Arung Jeram') {
            $dest['image_url'] = '../../assets/images/destinations/arungjeram.jpg';
        }
    }
    unset($dest); // Break the reference

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $popular_destinations = [];
}

// Get unique locations for filter dropdown
$locations = [];
foreach ($destinations as $dest) {
    if (!empty($dest['location'])) {
        $locations[$dest['location']] = $dest['location'];
    }
}
sort($locations);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinasi Wisata - Lolong Adventure</title>
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

        /* Sidebar styles */
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

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }

        /* Destination Cards */
        .destination-card {
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            border: none;
        }

        .destination-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .destination-img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s;
            width: 100%;
        }

        .destination-card:hover .destination-img {
            transform: scale(1.03);
        }

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

        .popular-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--primary-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .rating-badge {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* Responsive Adjustments */
        @media (max-width: 1199.98px) {
            :root {
                --sidebar-width: 250px;
            }

            .destination-img {
                height: 180px;
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
            .destination-img {
                height: 160px;
            }
        }

        @media (max-width: 575.98px) {
            .destination-img {
                height: 140px;
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
                <li>
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="destinations.php">
                        <i class="bi bi-map"></i>
                        <span class="nav-text">Destinasi Wisata</span>
                    </a>
                </li>
                <li>
                    <a href="bookings.php">
                        <i class="bi bi-calendar-check"></i>
                        <span class="nav-text">Pemesanan Saya</span>
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
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                        <span class="ms-2 d-none d-sm-inline">Menu</span>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
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

            <!-- Destinations Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="bi bi-map me-2"></i> Destinasi Wisata</h2>
                    <div>
                        <a href="new_booking.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Pesan Tiket Baru
                        </a>
                    </div>
                </div>

                <!-- Popular Destinations -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-star-fill me-2"></i> Destinasi Populer</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <?php if (!empty($popular_destinations)): ?>
                                        <?php foreach ($popular_destinations as $destination): ?>
                                            <div class="col-xl-3 col-lg-4 col-md-6">
                                                <div class="card destination-card">
                                                    <div class="position-relative overflow-hidden">
                                                        <img src="<?php echo htmlspecialchars($destination['image_url']); ?>"
                                                            class="card-img-top destination-img"
                                                            alt="<?php echo htmlspecialchars($destination['name']); ?>">
                                                        <div class="price-tag">
                                                            Rp <?php echo number_format($destination['price'] ?? rand(500000, 3000000), 0, ',', '.'); ?>
                                                        </div>
                                                        <span class="popular-badge">
                                                            <i class="bi bi-star-fill me-1"></i> Populer
                                                        </span>
                                                    </div>
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($destination['name']); ?></h5>
                                                        <p class="card-text text-muted">
                                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($destination['location'] ?? 'Bali, Indonesia'); ?>
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="rating-badge">
                                                                <i class="bi bi-star-fill"></i> <?php echo number_format(rand(35, 50) / 10, 1); ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                <i class="bi bi-people"></i> <?php echo htmlspecialchars($destination['booking_count']); ?> pemesanan
                                                            </small>
                                                        </div>
                                                        <div class="d-grid gap-2">
                                                            <a href="destination_detail.php?id=<?php echo htmlspecialchars($destination['id']); ?>"
                                                                class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-eye me-1"></i> Detail
                                                            </a>
                                                            <a href="new_booking.php?destination_id=<?php echo htmlspecialchars($destination['id']); ?>"
                                                                class="btn btn-primary btn-sm">
                                                                <i class="bi bi-calendar-plus me-1"></i> Pesan
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12 text-center py-4">
                                            <i class="bi bi-exclamation-circle" style="font-size: 3rem; color: #6c757d;"></i>
                                            <h5 class="mt-3">Tidak ada destinasi populer</h5>
                                            <p class="text-muted">Belum ada data destinasi populer yang tersedia</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Destinations -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-compass me-2"></i> Semua Destinasi</h5>
                                <small><?php echo count($destinations); ?> destinasi tersedia</small>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($destinations)): ?>
                                    <div class="row g-3" id="destinationsContainer">
                                        <?php foreach ($destinations as $destination): ?>
                                            <?php
                                            $price = $destination['price'] ?? rand(500000, 3000000);
                                            $isPopular = ($destination['booking_count'] ?? 0) > 10;
                                            ?>
                                            <div class="col-xl-3 col-lg-4 col-md-6 destination-item"
                                                data-name="<?php echo strtolower(htmlspecialchars($destination['name'])); ?>"
                                                data-location="<?php echo strtolower(htmlspecialchars($destination['location'] ?? '')); ?>"
                                                data-price="<?php echo $price; ?>">
                                                <div class="card destination-card">
                                                    <div class="position-relative overflow-hidden">
                                                        <img src="<?php echo htmlspecialchars($destination['image_url']); ?>"
                                                            class="card-img-top destination-img"
                                                            alt="<?php echo htmlspecialchars($destination['name']); ?>">
                                                        <div class="price-tag">
                                                            Rp <?php echo number_format($price, 0, ',', '.'); ?>
                                                        </div>
                                                        <?php if ($isPopular): ?>
                                                            <span class="popular-badge">
                                                                <i class="bi bi-star-fill me-1"></i> Populer
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($destination['name']); ?></h5>
                                                        <p class="card-text text-muted">
                                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($destination['location'] ?? 'Bali, Indonesia'); ?>
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <span class="rating-badge">
                                                                <i class="bi bi-star-fill"></i> <?php echo number_format(rand(35, 50) / 10, 1); ?>
                                                            </span>
                                                            <small class="text-muted">
                                                                <i class="bi bi-people"></i> <?php echo htmlspecialchars($destination['booking_count'] ?? rand(5, 50)); ?> pemesanan
                                                            </small>
                                                        </div>
                                                        <div class="d-grid gap-2">
                                                            <a href="destination_detail.php?id=<?php echo htmlspecialchars($destination['id']); ?>"
                                                                class="btn btn-outline-primary btn-sm">
                                                                <i class="bi bi-eye me-1"></i> Detail
                                                            </a>
                                                            <a href="new_booking.php?destination_id=<?php echo htmlspecialchars($destination['id']); ?>"
                                                                class="btn btn-primary btn-sm">
                                                                <i class="bi bi-calendar-plus me-1"></i> Pesan
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-map" style="font-size: 3rem; color: #6c757d;"></i>
                                        <h5 class="mt-3">Tidak ada destinasi tersedia</h5>
                                        <p class="text-muted">Maaf, saat ini tidak ada destinasi yang dapat ditampilkan</p>
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
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('show');
                $('.overlay').toggleClass('active');
            });

            // Close sidebar when clicking on overlay (mobile)
            $('.overlay').on('click', function() {
                $('#sidebar').removeClass('show');
                $('.overlay').removeClass('active');
            });
        });
    </script>
</body>

</html>