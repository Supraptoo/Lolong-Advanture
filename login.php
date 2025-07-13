<?php
session_start();
ob_start();

require_once __DIR__ . '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] === 'admin') ? './pages/dashboard.php' : './pages/customer/dashboard.php';
    header("Location: $redirect");
    exit();
}

$error = '';
$login_attempt = false;
$success_message = '';

// Handle Remember Me Cookie
if (isset($_COOKIE['remember_email'])) {
    $remember_email = $_COOKIE['remember_email'];
    $remember_checked = 'checked';
} else {
    $remember_email = '';
    $remember_checked = '';
}

// Handle Form Login Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_attempt = true;
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        try {
            // Cek di database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Verifikasi admin
                if ($email === 'admin@lolongadventure.com' && $password === 'admin12345') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = 'admin';
                    $_SESSION['full_name'] = $user['name'] ?? 'Administrator';
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'admin';
                    $_SESSION['login_method'] = 'manual';
                    $_SESSION['last_activity'] = time();

                    // Set cookie remember me jika dipilih
                    if ($remember) {
                        setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
                    } else {
                        setcookie('remember_email', '', time() - 3600, '/');
                    }

                    ob_end_clean();
                    header('Location: ./pages/dashboard.php');
                    exit();
                }
                // Verifikasi user biasa
                elseif ($email === 'user@example.com' && $password === 'user12345') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = 'user';
                    $_SESSION['full_name'] = $user['name'] ?? 'User Biasa';
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'customer';
                    $_SESSION['login_method'] = 'manual';
                    $_SESSION['last_activity'] = time();

                    // Set cookie remember me jika dipilih
                    if ($remember) {
                        setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/');
                    } else {
                        setcookie('remember_email', '', time() - 3600, '/');
                    }

                    ob_end_clean();
                    header('Location: ./pages/customer/dashboard.php');
                    exit();
                } else {
                    $error = 'Email atau password salah!';
                }
            } else {
                $error = 'Email belum terdaftar. Silakan daftar terlebih dahulu.';
            }
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }
}

// Tampilkan pesan sukses dari session jika ada
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2a7f62;
            /* Forest green */
            --secondary: #f8f9fa;
            /* Light gray */
            --accent: #ff7e33;
            /* Vibrant orange */
            --text: #333333;
            /* Dark gray */
            --text-light: #666666;
            /* Medium gray */
            --white: #FFFFFF;
            /* White */
            --success-color: #4caf50;
            /* Green */
            --error-color: #f44336;
            /* Red */
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), #1f6b53);
            margin: 0;
            padding: 1.5rem;
            color: var(--text);
            overflow-x: hidden;
        }

        h1,
        h5 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .login-container {
            max-width: 460px;
            width: 100%;
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary), #1f6b53);
            padding: 2rem;
            text-align: center;
            color: var(--white);
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 30%, rgba(255, 255, 255, 0.15), transparent 70%);
            z-index: 0;
        }

        .login-header h1,
        .login-header h5 {
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: 2rem;
            background: var(--secondary);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(255, 126, 51, 0.2);
            outline: none;
        }

        .input-group-text {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 8px 0 0 8px;
            color: var(--primary);
            padding: 0.8rem 1rem;
        }

        .btn-login {
            background: var(--primary);
            border: none;
            padding: 0.9rem;
            border-radius: 8px;
            color: var(--white);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: #1f6b53;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(42, 127, 98, 0.4);
        }

        .btn-login::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }

        .btn-login:hover::after {
            left: 100%;
        }

        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.2rem;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .logo-text span {
            color: var(--accent);
        }

        .alert {
            border-radius: 8px;
            border-left: 5px solid;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            border-left-color: var(--success-color);
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            border-left-color: var(--error-color);
            background: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .remember-me input {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .remember-me label {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .back-to-home {
            display: block;
            text-align: center;
            padding: 0.9rem;
            border-radius: 8px;
            background: var(--white);
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid var(--primary);
        }

        .back-to-home:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(42, 127, 98, 0.3);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }

        .copyright {
            color: var(--white);
            font-size: 0.85rem;
            text-align: center;
            margin-top: 1.5rem;
            opacity: 0.9;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
                padding: 1rem;
            }

            .login-header {
                padding: 1.5rem;
            }

            .login-body {
                padding: 1.5rem;
            }

            .logo-text {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="d-flex justify-content-center align-items-center mb-3 pulse">
                    <img src="https://w7.pngwing.com/pngs/987/739/png-transparent-logo-cv-wisata-outbond-indonesia-thumbnail.png" alt="logo" class="logo" width="60" height="60" style="border-radius: 50%; margin-right: 12px;" loading="lazy">
                    <h1 class="logo-text">Lolong <span>Adventure</span></h1>
                </div>
                <h5>Selamat Datang Kembali</h5>
            </div>
            <div class="login-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-medium">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($remember_email); ?>" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" <?php echo $remember_checked; ?>>
                        <label for="remember">Ingat email saya</label>
                    </div>
                    <button type="submit" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Sekarang
                    </button>
                </form>

                <a href="landingpage.php" class="back-to-home">
                    <i class="bi bi-house-door me-2"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
        <div class="copyright">
            © <?php echo date('Y'); ?> Lolong Adventure. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const emailField = document.getElementById('email');
            if (emailField.value === '') {
                emailField.focus();
            } else {
                document.getElementById('password').focus();
            }

            const buttons = document.querySelectorAll('.btn-login, .back-to-home');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    button.style.transition = 'all 0.3s ease';
                });
            });
        });
    </script>
</body>

</html>