<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$profile_pic = $_SESSION['profile_pic'] ?? 'https://via.placeholder.com/150?text=User';

// Get user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $full_name = $user['full_name'] ?? $full_name;
    $email = $user['email'] ?? $email;
    $phone = $user['phone'] ?? '';
    $address = $user['address'] ?? '';

    // Use profile picture from database if available
    if (!empty($user['profile_pic'])) {
        $profile_pic = $user['profile_pic'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_full_name = $_POST['full_name'] ?? '';
    $new_email = $_POST['email'] ?? '';
    $new_phone = $_POST['phone'] ?? '';
    $new_address = $_POST['address'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate email
    if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    // Password change validation
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = "Masukkan password saat ini";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Password saat ini salah";
        } elseif (empty($new_password)) {
            $errors[] = "Masukkan password baru";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password minimal 6 karakter";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Konfirmasi password tidak cocok";
        }
    }

    // Handle profile picture upload
    $profile_pic_updated = false;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_pic']['type'];

        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../../uploads/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $profile_pic = 'uploads/profiles/' . $new_filename;
                $profile_pic_updated = true;

                // Delete old profile picture if it's not the default
                if (
                    !empty($user['profile_pic']) && $user['profile_pic'] !== $profile_pic &&
                    strpos($user['profile_pic'], 'via.placeholder.com') === false
                ) {
                    @unlink('../../' . $user['profile_pic']);
                }
            } else {
                $errors[] = "Gagal mengupload foto profil";
            }
        } else {
            $errors[] = "Format file tidak didukung. Gunakan JPEG, PNG, atau GIF";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Prepare update query
            $update_data = [
                'full_name' => $new_full_name,
                'email' => $new_email,
                'phone' => $new_phone,
                'address' => $new_address,
                'id' => $user_id
            ];

            $update_query = "UPDATE users SET full_name = :full_name, email = :email, 
                            phone = :phone, address = :address";

            // Add profile pic to update if changed
            if ($profile_pic_updated) {
                $update_query .= ", profile_pic = :profile_pic";
                $update_data['profile_pic'] = $profile_pic;
            }

            // Add password to update if changed
            if (!empty($new_password)) {
                $update_query .= ", password = :password";
                $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $update_query .= " WHERE id = :id";

            $stmt = $pdo->prepare($update_query);
            $stmt->execute($update_data);

            $pdo->commit();

            // Update session data
            $_SESSION['full_name'] = $new_full_name;
            $_SESSION['email'] = $new_email;
            if ($profile_pic_updated) {
                $_SESSION['profile_pic'] = $profile_pic;
            }

            // Refresh page to show updated data
            header("Location: profile.php");
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

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Lolong Adventure</title>
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

        /* Profile Section */
        .profile-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
        }

        .profile-img-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary-color);
        }

        .profile-img-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .profile-img-upload input {
            display: none;
        }

        /* Responsive Adjustments */
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
            .profile-section {
                padding: 20px;
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
                <li>
                    <a href="testimonials.php">
                        <i class="bi bi-chat-square-quote"></i>
                        <span class="nav-text">Testimoni</span>
                    </a>
                </li>
                <li class="active">
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
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($full_name); ?>
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

            <!-- Profile Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-person-circle me-2"></i> Profil Saya</h3>
                            <p class="mb-0">Kelola informasi profil dan akun Anda</p>
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
                    <div class="col-lg-8 mx-auto">
                        <div class="profile-section">
                            <?php if (isset($errors) && !empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php elseif (isset($_GET['success']) && $_GET['success'] == '1'): ?>
                                <div class="alert alert-success">
                                    Profil berhasil diperbarui!
                                </div>
                            <?php endif; ?>

                            <form method="POST" enctype="multipart/form-data">
                                <div class="text-center mb-4">
                                    <div class="profile-img-container">
                                        <img src="<?php echo htmlspecialchars($profile_pic); ?>"
                                            class="profile-img"
                                            alt="Foto Profil"
                                            id="profileImagePreview">
                                        <label class="profile-img-upload" for="profilePicInput">
                                            <i class="bi bi-camera"></i>
                                            <input type="file" id="profilePicInput" name="profile_pic" accept="image/*">
                                        </label>
                                    </div>
                                    <h4><?php echo htmlspecialchars($full_name); ?></h4>
                                    <p class="text-muted">@<?php echo htmlspecialchars($username); ?></p>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name"
                                                value="<?php echo htmlspecialchars($full_name); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Nomor Telepon</label>
                                            <input type="tel" class="form-control" id="phone" name="phone"
                                                value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Alamat</label>
                                            <input type="text" class="form-control" id="address" name="address"
                                                value="<?php echo htmlspecialchars($address ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3"><i class="bi bi-shield-lock me-2"></i> Ganti Password</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Password Saat Ini</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password">
                                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Password Baru</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary-custom">
                                        <i class="bi bi-save me-1"></i> Simpan Perubahan
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
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('show');
                $('.overlay').toggleClass('active');

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

            // Profile image preview
            $('#profilePicInput').change(function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $('#profileImagePreview').attr('src', event.target.result);
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Responsive adjustments
            function handleResponsive() {
                if ($(window).width() < 992) {
                    if (!$('#sidebar').hasClass('show')) {
                        $('#sidebar').removeClass('show');
                        $('.overlay').removeClass('active');
                    }
                } else {
                    $('#sidebar').removeClass('show');
                    $('.overlay').removeClass('active');
                }
            }

            handleResponsive();
            $(window).on('resize', handleResponsive);
        });
    </script>
</body>

</html>