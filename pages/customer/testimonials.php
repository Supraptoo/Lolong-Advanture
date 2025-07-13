<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's completed bookings that can be reviewed
$stmt = $pdo->prepare("SELECT b.id, d.name as destination_name, d.image_url, b.booking_date 
                      FROM bookings b
                      JOIN destinations d ON b.destination_id = d.id
                      WHERE b.user_id = ? AND b.status = 'completed' 
                      AND NOT EXISTS (SELECT 1 FROM testimonials t WHERE t.booking_id = b.id)
                      ORDER BY b.booking_date DESC");
$stmt->execute([$user_id]);
$reviewable_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Process testimonial submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = $_POST['booking_id'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];

    // Validate inputs
    $errors = [];

    if (empty($booking_id)) {
        $errors[] = "Pilih pemesanan yang akan direview";
    }

    if (empty($rating) || $rating < 1 || $rating > 5) {
        $errors[] = "Berikan rating antara 1-5 bintang";
    }

    if (empty($comment) || strlen($comment) < 10) {
        $errors[] = "Komentar minimal 10 karakter";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert testimonial
            $stmt = $pdo->prepare("INSERT INTO testimonials (user_id, booking_id, rating, comment) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $booking_id, $rating, $comment]);

            $pdo->commit();

            // Refresh page to show new testimonial
            header("Location: testimonials.php");
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}

// Get bookings count for sidebar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();
?>

<!-- Bagian HTML tetap sama, tapi perbaiki bagian yang menampilkan avatar -->
<!DOCTYPE html>
<html lang="id">
<!-- ... (head dan bagian awal HTML tetap sama) ... -->

<!-- Di bagian testimoni, perbaiki tampilan avatar -->
<div class="d-flex align-items-center">
    <!-- Gunakan placeholder karena kolom profile_pic tidak ada -->
    <img src="https://via.placeholder.com/50?text=User"
        class="user-avatar me-3" alt="User Avatar">
    <div>
        <h5 class="mb-0"><?php echo $testimonial['full_name']; ?></h5>
        <small class="text-muted"><?php echo date('d M Y', strtotime($testimonial['created_at'])); ?></small>
    </div>
</div>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni - Lolong Adventure</title>
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

        /* Testimonial Cards */
        .testimonial-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .testimonial-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }

        .destination-img {
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            width: 100%;
        }

        /* Review Form */
        .review-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        /* Star Rating Input */
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            margin-right: 5px;
        }

        .rating-input input:checked~label,
        .rating-input input:hover~label,
        .rating-input label:hover,
        .rating-input label:hover~label {
            color: #ffc107;
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
                height: 100px;
            }
        }

        @media (max-width: 575.98px) {
            .destination-img {
                height: 80px;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
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
                <li>
                    <a href="payment.php">
                        <i class="bi bi-credit-card"></i>
                        <span class="nav-text">Pembayaran</span>
                    </a>
                </li>
                <li class="active">
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

            <!-- Testimonials Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-chat-square-quote me-2"></i> Testimoni</h3>
                            <p class="mb-0">Bagikan pengalaman Anda dan baca testimoni dari pelanggan lain</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-inline-block bg-white rounded-pill px-3 py-2">
                                <i class="bi bi-calendar-check text-primary-custom me-2"></i>
                                <span class="fw-bold"><?php echo date('l, d F Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Testimonial Form -->
                <?php if (count($reviewable_bookings) > 0): ?>
                    <div class="review-form">
                        <h4 class="mb-4"><i class="bi bi-pencil-square me-2"></i> Tulis Testimoni Baru</h4>

                        <?php if (isset($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="booking_id" class="form-label">Pilih Pemesanan</label>
                                <select class="form-select" id="booking_id" name="booking_id" required>
                                    <option value="">-- Pilih Pemesanan --</option>
                                    <?php foreach ($reviewable_bookings as $booking): ?>
                                        <option value="<?php echo $booking['id']; ?>">
                                            <?php echo $booking['destination_name']; ?> (<?php echo date('d M Y', strtotime($booking['booking_date'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating-input">
                                    <input type="radio" id="star5" name="rating" value="5" required>
                                    <label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4">
                                    <label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3">
                                    <label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2">
                                    <label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1">
                                    <label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="comment" class="form-label">Komentar</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" required
                                    placeholder="Bagikan pengalaman Anda..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-custom">
                                    <i class="bi bi-send me-1"></i> Kirim Testimoni
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- User's Testimonials -->
                <div class="mb-5">
                    <h4 class="mb-4"><i class="bi bi-person-circle me-2"></i> Testimoni Saya</h4>

                    <?php if (count($user_testimonials) > 0): ?>
                        <div class="row">
                            <?php foreach ($user_testimonials as $testimonial): ?>
                                <div class="col-md-6">
                                    <div class="testimonial-card">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $_SESSION['profile_pic'] ?? 'https://via.placeholder.com/50?text=User'; ?>"
                                                    class="user-avatar me-3" alt="User Avatar">
                                                <div>
                                                    <h5 class="mb-0"><?php echo $_SESSION['full_name']; ?></h5>
                                                    <small class="text-muted"><?php echo date('d M Y', strtotime($testimonial['created_at'])); ?></small>
                                                </div>
                                            </div>
                                            <div class="rating-stars">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo ($i < $testimonial['rating']) ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6><?php echo $testimonial['destination_name']; ?></h6>
                                        </div>

                                        <p><?php echo htmlspecialchars($testimonial['comment']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mt-3">Anda Belum Memberikan Testimoni</h5>
                            <p class="text-muted">Setelah menyelesaikan perjalanan, Anda dapat memberikan testimoni di sini</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- All Testimonials -->
                <div>
                    <h4 class="mb-4"><i class="bi bi-people-fill me-2"></i> Testimoni Pelanggan</h4>

                    <?php if (count($testimonials) > 0): ?>
                        <div class="row">
                            <?php foreach ($testimonials as $testimonial): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="testimonial-card h-100">
                                        <div class="d-flex justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo $testimonial['profile_pic'] ?? 'https://via.placeholder.com/50?text=User'; ?>"
                                                    class="user-avatar me-3" alt="User Avatar">
                                                <div>
                                                    <h5 class="mb-0"><?php echo $testimonial['full_name']; ?></h5>
                                                    <small class="text-muted"><?php echo date('d M Y', strtotime($testimonial['created_at'])); ?></small>
                                                </div>
                                            </div>
                                            <div class="rating-stars">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo ($i < $testimonial['rating']) ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6><?php echo $testimonial['destination_name']; ?></h6>
                                        </div>

                                        <p><?php echo htmlspecialchars($testimonial['comment']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mt-3">Belum Ada Testimoni</h5>
                            <p class="text-muted">Jadilah yang pertama memberikan testimoni</p>
                        </div>
                    <?php endif; ?>
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
        });
    </script>
</body>

</html>