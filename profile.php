<?php
session_start();
require_once('config/database.php');

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Ambil data pengguna
try {
    $stmt = $pdo->prepare("SELECT name, email, phone_number, address FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error_message = "Data pengguna tidak ditemukan.";
    }
} catch (PDOException $e) {
    $error_message = "Gagal memuat data pengguna: " . $e->getMessage();
}

// Proses pembaruan profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validasi input
    if (empty($name) || empty($email) || empty($phone_number)) {
        $error_message = "Nama, email, dan nomor telepon wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format email tidak valid.";
    } else {
        try {
            $stmt = $pdo->prepare("
        UPDATE users 
        SET name = ?, email = ?, phone_number = ?, address = ?
        WHERE id = ?
      ");
            $stmt->execute([$name, $email, $phone_number, $address, $user_id]);
            $success_message = "Profil berhasil diperbarui.";

            // Perbarui data untuk ditampilkan
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone_number'] = $phone_number;
            $user['address'] = $address;
        } catch (PDOException $e) {
            $error_message = "Gagal memperbarui profil: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profil - Wisata Sungai Sengkarang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f8f9fa;
            --accent: #ff7e33;
            --text: #333333;
            --text-light: #666666;
            --white: #FFFFFF;
            --border-color: #ddd;
            --success-color: #4caf50;
            --error-color: #f44336;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background-color: var(--secondary);
            line-height: 1.6;
            padding-bottom: 60px;
        }

        h1,
        h2,
        h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .section-title p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .profile-card {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .profile-info {
            margin-bottom: 20px;
        }

        .profile-info h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .profile-info p {
            margin-bottom: 10px;
            font-size: 1rem;
            color: var(--text-light);
        }

        .profile-info p strong {
            color: var(--text);
            width: 120px;
            display: inline-block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text);
            margin-bottom: 5px;
            display: block;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #1f6b53;
        }

        .btn-danger {
            background: var(--error-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .alert-error {
            background: var(--error-color);
            color: var(--white);
        }

        .alert-success {
            background: var(--success-color);
            color: var(--white);
        }

        .bottom-sidebar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: var(--white);
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
        }

        .sidebar-menu {
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            width: 100%;
        }

        .sidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text);
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .sidebar-item:hover {
            color: var(--primary);
        }

        .sidebar-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .section-title h2 {
                font-size: 2rem;
            }

            .profile-card {
                padding: 20px;
            }

            .sidebar-item {
                font-size: 0.8rem;
            }

            .sidebar-item i {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .section-title h2 {
                font-size: 1.8rem;
            }

            .profile-info h3 {
                font-size: 1.3rem;
            }

            .profile-info p {
                font-size: 0.9rem;
            }

            .btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Bottom Sidebar -->
    <div class="bottom-sidebar">
        <div class="sidebar-menu">
            <a href="index.php" class="sidebar-item">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="status.php" class="sidebar-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Status Pemesanan</span>
            </a>
            <a href="profile.php" class="sidebar-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
            <a href="logout.php" class="sidebar-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Profil Anda</h2>
            <p>Kelola informasi pribadi Anda untuk pengalaman wisata yang lebih baik</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="profile-card" data-aos="fade-up" data-aos-delay="200">
            <div class="profile-info">
                <h3>Data Pelanggan</h3>
                <?php if ($user): ?>
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>No. Telepon:</strong> <?php echo htmlspecialchars($user['phone_number'] ?? '-'); ?></p>
                    <p><strong>Alamat:</strong> <?php echo htmlspecialchars($user['address'] ?? '-'); ?></p>
                <?php endif; ?>
            </div>

            <h3>Edit Profil</h3>
            <form method="POST" action="profile.php">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone_number">Nomor Telepon</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>

</html>