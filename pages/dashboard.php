<?php
session_start();
require_once('../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get counts for dashboard
$stmt = $pdo->query("SELECT COUNT(*) FROM destinations");
$destinations_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
$active_bookings = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE is_approved = 1");
$testimonials_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0");
$unread_messages = $stmt->fetchColumn();

// Get recent bookings with user info
$stmt = $pdo->query("SELECT b.*, d.name as destination_name, u.full_name as customer_name 
                     FROM bookings b 
                     JOIN destinations d ON b.destination_id = d.id 
                     JOIN users u ON b.user_id = u.id 
                     ORDER BY b.created_at DESC LIMIT 5");
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular destinations
$stmt = $pdo->query("SELECT d.id, d.name, d.location, d.image_url, COUNT(b.id) as booking_count 
                     FROM destinations d 
                     LEFT JOIN bookings b ON d.id = b.destination_id 
                     GROUP BY d.id 
                     ORDER BY booking_count DESC 
                     LIMIT 3");
$popular_destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
            --sidebar-width: 250px;
        }

        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(42, 157, 143, 0.1);
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
            background-color: #28a745;
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

        /* Activity Feed */
        .activity-feed .feed-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-feed .feed-item:last-child {
            border-bottom: none;
        }

        .feed-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
        }

        .feed-content {
            flex: 1;
        }

        .feed-content .date {
            font-size: 0.8rem;
            color: #6c757d;
        }

        /* Destination Cards */
        .destination-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

        /* Greeting Section */
        .greeting-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(42, 157, 143, 0.05);
        }

        /* Navbar */
        .navbar {
            padding: 15px 20px;
            background: white !important;
            border-bottom: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Profile Link */
        .profile-link {
            transition: all 0.3s;
        }

        .profile-link:hover {
            color: var(--primary-color) !important;
            transform: scale(1.05);
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 15px;
            }

            .destination-img {
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Include Sidebar -->
        <?php include('../includes/sidebar.php'); ?>

        <!-- Main Content -->
        <div id="content" class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary-custom">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <a href="profil.php" class="profile-link text-decoration-none text-dark me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </a>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Dashboard Content -->
            <div class="container-fluid p-4">
                <!-- Greeting Section -->
                <div class="greeting-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3><i class="bi bi-compass me-2"></i> Selamat Datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
                            <p class="mb-0">Berikut ringkasan aktivitas terbaru Lolong Adventure</p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0"><i class="bi bi-calendar-check me-2"></i> <?php echo date('l, d F Y'); ?></p>
                            <p class="mb-0"><i class="bi bi-clock me-2"></i> <?php echo date('H:i'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="bi bi-map"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">Destinasi Wisata</h5>
                                    <h3 class="mb-0"><?php echo $destinations_count; ?></h3>
                                    <p class="text-muted mb-0"><i class="bi bi-arrow-up text-success"></i> 5 dari bulan lalu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">Pemesanan Aktif</h5>
                                    <h3 class="mb-0"><?php echo $active_bookings; ?></h3>
                                    <p class="text-muted mb-0"><i class="bi bi-arrow-up text-success"></i> 12% dari minggu lalu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="bi bi-chat-square-quote"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">Testimoni</h5>
                                    <h3 class="mb-0"><?php echo $testimonials_count; ?></h3>
                                    <p class="text-muted mb-0"><i class="bi bi-star-fill text-warning"></i> 4.8 Rating rata-rata</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="stat-icon">
                                    <i class="bi bi-envelope"></i>
                                </div>
                                <div>
                                    <h5 class="card-title">Pesan Baru</h5>
                                    <h3 class="mb-0"><?php echo $unread_messages; ?></h3>
                                    <p class="text-muted mb-0"><i class="bi bi-clock text-danger"></i> Belum dibaca</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Bookings -->
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary-custom text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Pemesanan Terbaru</h5>
                                <a href="../pages/bookings/index.php" class="btn btn-sm btn-light">Lihat Semua</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Destinasi</th>
                                                <th>Pelanggan</th>
                                                <th>Tanggal</th>
                                                <th>Peserta</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_bookings as $booking): ?>
                                                <tr>
                                                    <td><?php echo $booking['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($booking['destination_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                                    <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                                    <td><?php echo $booking['participants']; ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill 
                                                            <?php
                                                            switch (strtolower($booking['status'])) {
                                                                case 'confirmed':
                                                                    echo 'bg-success';
                                                                    break;
                                                                case 'pending':
                                                                    echo 'bg-warning text-dark';
                                                                    break;
                                                                case 'completed':
                                                                    echo 'bg-info';
                                                                    break;
                                                                case 'cancelled':
                                                                    echo 'bg-danger';
                                                                    break;
                                                                default:
                                                                    echo 'bg-secondary';
                                                            }
                                                            ?>">
                                                            <?php echo ucfirst($booking['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary-custom text-white">
                                <h5 class="mb-0"><i class="bi bi-activity me-2"></i> Aktivitas Terkini</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed">
                                    <?php if (count($recent_bookings) > 0): ?>
                                        <div class="feed-item">
                                            <div class="feed-icon bg-success">
                                                <i class="bi bi-calendar-check"></i>
                                            </div>
                                            <div class="feed-content">
                                                <span class="date">Baru saja</span>
                                                <p>Pemesanan baru #<?php echo $recent_bookings[0]['id']; ?> untuk <?php echo htmlspecialchars($recent_bookings[0]['destination_name']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="feed-item">
                                        <div class="feed-icon bg-info">
                                            <i class="bi bi-chat-square-text"></i>
                                        </div>
                                        <div class="feed-content">
                                            <span class="date">Hari ini</span>
                                            <p><?php echo $testimonials_count; ?> testimoni baru diterima</p>
                                        </div>
                                    </div>

                                    <div class="feed-item">
                                        <div class="feed-icon bg-warning">
                                            <i class="bi bi-envelope"></i>
                                        </div>
                                        <div class="feed-content">
                                            <span class="date">Hari ini</span>
                                            <p><?php echo $unread_messages; ?> pesan baru belum dibaca</p>
                                        </div>
                                    </div>

                                    <div class="feed-item">
                                        <div class="feed-icon bg-primary">
                                            <i class="bi bi-map"></i>
                                        </div>
                                        <div class="feed-content">
                                            <span class="date">Minggu ini</span>
                                            <p><?php echo $active_bookings; ?> pemesanan aktif</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Destinations -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary-custom text-white">
                                <h5 class="mb-0"><i class="bi bi-star-fill me-2"></i> Destinasi Populer</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($popular_destinations as $destination): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card destination-card border-0">
                                                <div class="position-relative overflow-hidden">
                                                    <img src="../assets/images/destinations/arungjeram.jpg?php echo !empty($destination['image_url']) ? htmlspecialchars(basename($destination['image_url'])) : 'default.jpg'; ?>"
                                                        class="card-img-top destination-img"
                                                        alt="<?php echo htmlspecialchars($destination['name']); ?>"
                                                        onerror="this.onerror=null;this.src='../assets/images/destinations/default.jpg';">
                                                    <span class="badge bg-success position-absolute top-0 end-0 m-2">
                                                        <i class="bi bi-people"></i> <?php echo $destination['booking_count']; ?> Pemesanan
                                                    </span>
                                                </div>
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($destination['name']); ?></h5>
                                                    <p class="card-text text-muted">
                                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($destination['location']); ?>
                                                    </p>
                                                    <div class="d-grid">
                                                        <a href="destinations.php?id=<?php echo $destination['id']; ?>"
                                                            class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-eye me-1"></i> Lihat Detail
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });

            // Initialize DataTable
            $('.table').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                columnDefs: [{
                    orderable: false,
                    targets: [5] // Disable sorting on status column
                }]
            });

            // Highlight active menu item
            var current = location.pathname.split('/').pop();
            $('#sidebar ul li a').each(function() {
                var $this = $(this);
                if ($this.attr('href').indexOf(current) !== -1) {
                    $this.parent().addClass('active');
                }
            });
        });
    </script>
</body>

</html>