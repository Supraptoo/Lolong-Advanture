<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    header('Location: bookings.php');
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, d.price as destination_price, d.image_url 
                      FROM bookings b 
                      JOIN destinations d ON b.destination_id = d.id 
                      WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Check if booking is already paid
if (strtolower($booking['status']) !== 'pending') {
    header("Location: booking_detail.php?id=$booking_id");
    exit();
}

// Get payment methods
$payment_methods = [
    ['id' => 'bca', 'name' => 'BCA Virtual Account', 'icon' => 'bi-bank'],
    ['id' => 'bni', 'name' => 'BNI Virtual Account', 'icon' => 'bi-bank'],
    ['id' => 'bri', 'name' => 'BRI Virtual Account', 'icon' => 'bi-bank'],
    ['id' => 'mandiri', 'name' => 'Mandiri Virtual Account', 'icon' => 'bi-bank'],
    ['id' => 'gopay', 'name' => 'GoPay', 'icon' => 'bi-phone'],
    ['id' => 'ovo', 'name' => 'OVO', 'icon' => 'bi-phone'],
    ['id' => 'dana', 'name' => 'DANA', 'icon' => 'bi-phone'],
    ['id' => 'shopee', 'name' => 'ShopeePay', 'icon' => 'bi-phone']
];

// Process payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];

    // Validate payment method
    $valid_methods = array_column($payment_methods, 'id');
    if (!in_array($payment_method, $valid_methods)) {
        $error = "Pilih metode pembayaran yang valid";
    } else {
        try {
            $pdo->beginTransaction();

            // Update booking status to processing payment
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'processing_payment' WHERE id = ?");
            $stmt->execute([$booking_id]);

            // Create payment record (in a real app, you would integrate with payment gateway here)
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_method, status) 
                                  VALUES (?, ?, ?, 'pending')");
            $total_amount = $booking['destination_price'] * $booking['participants'];
            $stmt->execute([$booking_id, $total_amount, $payment_method]);

            $pdo->commit();

            // Redirect to payment instructions page
            header("Location: payment_instructions.php?booking_id=$booking_id");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Get bookings count for sidebar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Lolong Adventure</title>
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

        /* Booking Summary */
        .booking-summary {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .booking-img {
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            width: 100%;
        }

        /* Payment Methods */
        .payment-method {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-method:hover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.1);
        }

        .payment-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 10px;
        }

        /* Price Summary */
        .price-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
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
            .booking-img {
                height: 150px;
            }
        }

        @media (max-width: 575.98px) {
            .booking-img {
                height: 120px;
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
                <li>
                    <a href="new_booking.php">
                        <i class="bi bi-plus-circle"></i>
                        <span class="nav-text">Pesan Tiket Baru</span>
                    </a>
                </li>
                <li class="active">
                    <a href="payments.php">
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

            <!-- Payment Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-credit-card me-2"></i> Pembayaran</h3>
                            <p class="mb-0">Lanjutkan pembayaran untuk menyelesaikan pemesanan Anda</p>
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
                    <!-- Booking Summary -->
                    <div class="col-lg-5 mb-4">
                        <div class="booking-summary">
                            <h4 class="mb-4"><i class="bi bi-ticket-detailed me-2"></i> Ringkasan Pemesanan</h4>

                            <div class="mb-4">
                                <img src="<?php echo $booking['image_url'] ?? 'https://via.placeholder.com/600x400?text=Destinasi'; ?>"
                                    class="booking-img mb-3"
                                    alt="<?php echo $booking['destination_name']; ?>">
                                <h5><?php echo $booking['destination_name']; ?></h5>
                                <p class="text-muted">
                                    <i class="bi bi-calendar"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?>
                                </p>
                                <p>
                                    <i class="bi bi-people"></i> <?php echo $booking['participants']; ?> Peserta
                                </p>
                            </div>

                            <div class="price-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Harga per orang:</span>
                                    <span class="fw-bold">Rp <?php echo number_format($booking['destination_price'], 0, ',', '.'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Jumlah peserta:</span>
                                    <span class="fw-bold"><?php echo $booking['participants']; ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Total Pembayaran:</span>
                                    <span class="fw-bold text-primary">
                                        Rp <?php echo number_format($booking['destination_price'] * $booking['participants'], 0, ',', '.'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="col-lg-7">
                        <div class="booking-summary">
                            <h4 class="mb-4"><i class="bi bi-credit-card me-2"></i> Metode Pembayaran</h4>

                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="paymentForm">
                                <div class="mb-4">
                                    <h5 class="mb-3">Pilih Metode Pembayaran</h5>

                                    <div class="row">
                                        <?php foreach ($payment_methods as $method): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="payment-method" onclick="selectPaymentMethod('<?php echo $method['id']; ?>')">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="payment_method"
                                                            id="<?php echo $method['id']; ?>" value="<?php echo $method['id']; ?>" required>
                                                        <label class="form-check-label d-flex align-items-center" for="<?php echo $method['id']; ?>">
                                                            <i class="<?php echo $method['icon']; ?> payment-icon"></i>
                                                            <?php echo $method['name']; ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                    <label class="form-check-label" for="termsCheck">
                                        Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat dan Ketentuan</a> Lolong Adventure
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary-custom btn-lg">
                                        <i class="bi bi-credit-card me-1"></i> Lanjutkan Pembayaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Syarat dan Ketentuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Pembayaran</h6>
                    <p>Pembayaran harus dilakukan dalam waktu 24 jam setelah pemesanan dibuat. Jika tidak, pemesanan akan otomatis dibatalkan.</p>

                    <h6>2. Pembatalan</h6>
                    <p>Pembatalan dapat dilakukan maksimal 7 hari sebelum tanggal kunjungan untuk mendapatkan refund 50%. Pembatalan kurang dari 7 hari tidak mendapatkan refund.</p>

                    <h6>3. Perubahan Jadwal</h6>
                    <p>Perubahan jadwal dapat dilakukan maksimal 3 hari sebelum tanggal kunjungan, dengan ketersediaan destinasi yang berlaku.</p>

                    <h6>4. Ketentuan Lainnya</h6>
                    <p>Lolong Adventure berhak menolak peserta yang tidak memenuhi persyaratan kesehatan atau membawa barang-barang terlarang.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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

            // Payment method selection
            window.selectPaymentMethod = function(methodId) {
                $(`#${methodId}`).prop('checked', true);
                $('.payment-method').removeClass('selected');
                $(`#${methodId}`).closest('.payment-method').addClass('selected');
            };

            // Form validation
            $('#paymentForm').on('submit', function(e) {
                if (!$('#termsCheck').is(':checked')) {
                    e.preventDefault();
                    alert('Anda harus menyetujui Syarat dan Ketentuan');
                }
            });
        });
    </script>
</body>

</html>