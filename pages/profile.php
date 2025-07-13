<?php
session_start();
require_once('../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Get admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$_SESSION['username']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Set default profile photo
$admin['profile_photo'] = $admin['profile_photo'] ?? 'default-avatar.png';

// Update profile if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Handle password change
    $password_sql = "";
    $params = [$full_name, $email, $phone, $_SESSION['username']];

    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $password_sql = ", password = ?";
            array_unshift($params, $hashed_password);
        } else {
            $_SESSION['error'] = "Password baru dan konfirmasi password tidak cocok!";
            header('Location: profile.php');
            exit();
        }
    }

    $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? $password_sql WHERE username = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        $_SESSION['success'] = "Profil berhasil diperbarui!";
        $_SESSION['full_name'] = $full_name;
    } else {
        $_SESSION['error'] = "Gagal memperbarui profil!";
    }
    header('Location: profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #1e7d6b;
            --light-green: #e8f5e9;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            transition: all 0.3s;
            z-index: 1000;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: all 0.3s;
        }

        .profile-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            background-color: white;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .photo-upload-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: all 0.3s;
        }

        .photo-upload-btn:hover {
            background: #166652;
            transform: scale(1.1);
        }

        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 157, 143, 0.25);
        }

        @media (max-width: 992px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.active {
                margin-left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include('../includes/sidebar.php'); ?>

        <!-- Main Content -->
        <div class="main-content w-100">
            <div class="container-fluid">
                <div class="row g-4">
                    <!-- Profile Overview -->
                    <div class="col-lg-4">
                        <div class="card profile-card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i> Profil Admin</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-column align-items-center text-center">
                                    <div class="position-relative mb-4">
                                        <img src="../assets/images/profiles/<?= htmlspecialchars($admin['profile_photo']) ?>?t=<?= time() ?>"
                                            class="profile-avatar"
                                            id="profilePhotoPreview">
                                        <label for="photoUpload" class="photo-upload-btn" title="Ubah Foto Profil">
                                            <i class="bi bi-camera"></i>
                                            <input type="file" id="photoUpload" accept="image/*" style="display:none;">
                                        </label>
                                    </div>

                                    <div class="w-100">
                                        <div class="detail-item">
                                            <span class="detail-label"><i class="bi bi-person me-2"></i> Nama:</span>
                                            <p class="mb-0"><?= htmlspecialchars($admin['full_name']) ?></p>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label"><i class="bi bi-person-vcard me-2"></i> Username:</span>
                                            <p class="mb-0"><?= htmlspecialchars($admin['username']) ?></p>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label"><i class="bi bi-envelope me-2"></i> Email:</span>
                                            <p class="mb-0"><?= htmlspecialchars($admin['email']) ?></p>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label"><i class="bi bi-telephone me-2"></i> Telepon:</span>
                                            <p class="mb-0"><?= htmlspecialchars($admin['phone'] ?? '-') ?></p>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label"><i class="bi bi-calendar-check me-2"></i> Bergabung:</span>
                                            <p class="mb-0"><?= date('d F Y', strtotime($admin['created_at'])) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Profile Form -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Edit Profil</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php" id="profileForm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($admin['username']) ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Telepon</label>
                                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password Baru</label>
                                            <input type="password" name="new_password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Konfirmasi Password</label>
                                            <input type="password" name="confirm_password" class="form-control" placeholder="Konfirmasi password baru">
                                        </div>
                                        <div class="col-12 mt-3">
                                            <button type="submit" name="update_profile" class="btn btn-primary px-4">
                                                <i class="bi bi-save me-2"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        $(document).ready(function() {
            // Show notifications using SweetAlert
            <?php if (isset($_SESSION['success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses',
                    text: '<?= $_SESSION['success'] ?>',
                    timer: 3000,
                    showConfirmButton: false,
                    position: 'top-end',
                    background: '#f8f9fa'
                });
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?= $_SESSION['error'] ?>',
                    timer: 3000,
                    showConfirmButton: false,
                    position: 'top-end',
                    background: '#f8f9fa'
                });
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Avatar upload handling
            $('#photoUpload').change(function() {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('avatar', this.files[0]);

                    // Show loading
                    $('#profilePhotoPreview').css('opacity', '0.5');
                    $('.photo-upload-btn i').removeClass('bi-camera').addClass('bi-arrow-clockwise bi-spin');

                    $.ajax({
                        url: 'upload_avatar.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#profilePhotoPreview').attr('src', '../assets/images/profiles/' + response.filename + '?t=' + Date.now());
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sukses',
                                    text: 'Foto profil berhasil diperbarui!',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    position: 'top-end'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.error || 'Gagal mengupload foto',
                                    timer: 3000,
                                    showConfirmButton: false,
                                    position: 'top-end'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Terjadi kesalahan saat mengupload foto',
                                timer: 3000,
                                showConfirmButton: false,
                                position: 'top-end'
                            });
                        },
                        complete: function() {
                            $('#profilePhotoPreview').css('opacity', '1');
                            $('.photo-upload-btn i').removeClass('bi-arrow-clockwise bi-spin').addClass('bi-camera');
                            $('#photoUpload').val('');
                        }
                    });
                }
            });

            // Form submission handling
            $('#profileForm').on('submit', function(e) {
                const newPassword = $('input[name="new_password"]').val();
                const confirmPassword = $('input[name="confirm_password"]').val();

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Password baru dan konfirmasi password tidak cocok!',
                        timer: 3000,
                        showConfirmButton: false,
                        position: 'top-end'
                    });
                }
            });

            // Sidebar toggle for mobile
            $('[data-toggle="sidebar"]').click(function() {
                $('.sidebar').toggleClass('active');
                $('.main-content').toggleClass('active');
            });
        });
    </script>
</body>

</html>