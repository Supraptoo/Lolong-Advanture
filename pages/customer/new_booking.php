<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get user-specific data
$user_id = $_SESSION['user_id'];

// Get destinations for dropdown
$stmt = $pdo->query("SELECT id, name, location, price FROM destinations ORDER BY name ASC");
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get upcoming bookings count (for sidebar)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_date >= CURDATE()");
$stmt->execute([$user_id]);
$upcoming_count = $stmt->fetchColumn();

// Get all bookings count (for sidebar)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination_id = $_POST['destination_id'];
    $booking_date = $_POST['booking_date'];
    $participants = $_POST['participants'];
    $special_requests = $_POST['special_requests'] ?? '';

    // Validate inputs
    $errors = [];

    if (empty($destination_id)) {
        $errors[] = "Pilih destinasi wisata";
    }

    if (empty($booking_date)) {
        $errors[] = "Masukkan tanggal pemesanan";
    } elseif (strtotime($booking_date) < strtotime('today')) {
        $errors[] = "Tanggal pemesanan tidak boleh di masa lalu";
    }

    if (empty($participants) || $participants < 1) {
        $errors[] = "Jumlah peserta minimal 1";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert booking
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, destination_id, booking_date, participants, special_requests, status) 
                                  VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$user_id, $destination_id, $booking_date, $participants, $special_requests]);
            $booking_id = $pdo->lastInsertId();

            $pdo->commit();

            // Redirect to payment page
            header("Location: payment.php?booking_id=$booking_id");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Get selected destination details if coming from destinations page
$selected_destination = null;
if (isset($_GET['destination_id'])) {
    $destination_id = $_GET['destination_id'];
    $stmt = $pdo->prepare("SELECT id, name, location, price, image_url FROM destinations WHERE id = ?");
    $stmt->execute([$destination_id]);
    $selected_destination = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket Baru - Lolong Adventure</title>
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

        /* Custom Colors */
        .bg-primary-custom {
            background-color: var(--primary-color) !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        /* Destination Preview Card */
        .destination-preview {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            height: 100%;
            transition: all 0.3s;
        }

        .destination-preview:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .destination-img {
            height: 200px;
            object-fit: cover;
            width: 100%;
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

        /* Booking Form */
        .booking-form-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }

        .form-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
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
            .destination-img {
                height: 160px;
            }
        }

        @media (max-width: 575.98px) {
            .destination-img {
                height: 140px;
            }

            .booking-form-container {
                padding: 15px;
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
                <li class="active">
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

            <!-- Booking Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-plus-circle me-2"></i> Pesan Tiket Baru</h3>
                            <p class="mb-0">Isi formulir di bawah ini untuk membuat pemesanan baru</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-inline-block bg-white rounded-pill px-3 py-2">
                                <i class="bi bi-calendar-check text-primary-custom me-2"></i>
                                <span class="fw-bold"><?php echo date('l, d F Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Destination Preview -->
                    <?php if ($selected_destination): ?>
                        <div class="col-lg-5 mb-4">
                            <div class="destination-preview">
                                <div class="position-relative">
                                    <img src="<?php echo $selected_destination['image_url'] ?? 'https://via.placeholder.com/600x400?text=Destinasi'; ?>"
                                        class="destination-img"
                                        alt="<?php echo $selected_destination['name']; ?>">
                                    <div class="price-tag">
                                        Rp <?php echo number_format($selected_destination['price'], 0, ',', '.'); ?> / orang
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h4><?php echo $selected_destination['name']; ?></h4>
                                    <p class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?php echo $selected_destination['location']; ?>
                                    </p>
                                    <div class="d-grid">
                                        <a href="destinations.php" class="btn btn-outline-primary">
                                            <i class="bi bi-arrow-left-circle me-1"></i> Pilih Destinasi Lain
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Booking Form -->
                    <div class="<?php echo $selected_destination ? 'col-lg-7' : 'col-12'; ?>">
                        <div class="booking-form-container">
                            <div class="form-header">
                                <h4><i class="bi bi-calendar-plus me-2"></i> Formulir Pemesanan</h4>
                                <p class="text-muted mb-0">Isi semua informasi yang diperlukan</p>
                            </div>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="new_booking.php">
                                <!-- Destination Selection -->
                                <div class="mb-3">
                                    <label for="destination_id" class="form-label">Destinasi Wisata</label>
                                    <select class="form-select" id="destination_id" name="destination_id" required <?php echo $selected_destination ? 'disabled' : ''; ?>>
                                        <option value="">-- Pilih Destinasi --</option>
                                        <?php foreach ($destinations as $destination): ?>
                                            <option value="<?php echo $destination['id']; ?>"
                                                <?php echo ($selected_destination && $selected_destination['id'] == $destination['id']) ? 'selected' : ''; ?>>
                                                <?php echo $destination['name']; ?> (Rp <?php echo number_format($destination['price'], 0, ',', '.'); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($selected_destination): ?>
                                        <input type="hidden" name="destination_id" value="<?php echo $selected_destination['id']; ?>">
                                    <?php endif; ?>
                                </div>

                                <!-- Booking Date -->
                                <div class="mb-3">
                                    <label for="booking_date" class="form-label">Tanggal Kunjungan</label>
                                    <input type="date" class="form-control" id="booking_date" name="booking_date"
                                        min="<?php echo date('Y-m-d'); ?>" required
                                        value="<?php echo isset($_POST['booking_date']) ? $_POST['booking_date'] : ''; ?>">
                                </div>

                                <!-- Number of Participants -->
                                <div class="mb-3">
                                    <label for="participants" class="form-label">Jumlah Peserta</label>
                                    <input type="number" class="form-control" id="participants" name="participants"
                                        min="1" max="20" required
                                        value="<?php echo isset($_POST['participants']) ? $_POST['participants'] : '1'; ?>">
                                    <div class="form-text">Maksimal 20 peserta per pemesanan</div>
                                </div>

                                <!-- Special Requests -->
                                <div class="mb-4">
                                    <label for="special_requests" class="form-label">Permintaan Khusus (Opsional)</label>
                                    <textarea class="form-control" id="special_requests" name="special_requests" rows="3"><?php echo isset($_POST['special_requests']) ? $_POST['special_requests'] : ''; ?></textarea>
                                    <div class="form-text">Contoh: Kursi roda, makanan vegetarian, dll.</div>
                                </div>

                                <!-- Price Calculation -->
                                <div class="mb-4 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Harga per orang:</span>
                                        <span class="fw-bold" id="price-per-person">
                                            <?php if ($selected_destination): ?>
                                                Rp <?php echo number_format($selected_destination['price'], 0, ',', '.'); ?>
                                            <?php else: ?>
                                                Rp 0
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Jumlah peserta:</span>
                                        <span class="fw-bold" id="participants-count">
                                            <?php echo isset($_POST['participants']) ? $_POST['participants'] : '1'; ?>
                                        </span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold">Total Pembayaran:</span>
                                        <span class="fw-bold text-primary" id="total-price">
                                            <?php if ($selected_destination): ?>
                                                Rp <?php
                                                    $participants = isset($_POST['participants']) ? (int)$_POST['participants'] : 1;
                                                    echo number_format($selected_destination['price'] * $participants, 0, ',', '.');
                                                    ?>
                                            <?php else: ?>
                                                Rp 0
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary-custom btn-lg">
                                        <i class="bi bi-check-circle me-1"></i> Lanjutkan ke Pembayaran
                                    </button>
                                </div>
                            </form>
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

            // Price calculation when destination or participants change
            const destinations = <?php echo json_encode($destinations); ?>;

            $('#destination_id, #participants').on('change', function() {
                calculateTotalPrice();
            });

            function calculateTotalPrice() {
                const destinationId = $('#destination_id').val();
                const participants = parseInt($('#participants').val()) || 1;

                // Update participants count display
                $('#participants-count').text(participants);

                if (destinationId) {
                    const destination = destinations.find(d => d.id == destinationId);
                    if (destination) {
                        const pricePerPerson = parseInt(destination.price);
                        const totalPrice = pricePerPerson * participants;

                        // Update displays
                        $('#price-per-person').text('Rp ' + pricePerPerson.toLocaleString('id-ID'));
                        $('#total-price').text('Rp ' + totalPrice.toLocaleString('id-ID'));
                        return;
                    }
                }

                // Default if no destination selected
                $('#price-per-person').text('Rp 0');
                $('#total-price').text('Rp 0');
            }

            // Initial calculation
            calculateTotalPrice();
        });
    </script>
</body>

</html>