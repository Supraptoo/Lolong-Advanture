<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to dashboard
    header('Location: ./pages/dashboard.php');
    exit();
} else {
    // If not logged in, redirect to login page
    header('Location: ./login.php');
    exit();
}

// This code will only execute if headers fail
die('Redirect failed. Please click <a href="login.php">here</a> to continue.');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lolong Adventure - Wisata Alam Eksklusif</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
            --white: #ffffff;
            --text-color: #333;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--white);
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .btn-custom {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: var(--accent-color);
            color: var(--secondary-color);
            border: 2px solid var(--accent-color);
        }

        .btn-primary-custom:hover {
            background: transparent;
            color: var(--accent-color);
        }

        .admin-login-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .navbar {
            background: rgba(38, 70, 83, 0.9);
            backdrop-filter: blur(10px);
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-compass me-2"></i>
                <span>Lolong <span class="text-warning">Adventure</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#destinations">Destinasi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang Kami</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Jelajahi Keindahan Alam dengan Lolong Adventure</h1>
                <p>Temukan pengalaman wisata alam tak terlupakan dengan pemandu profesional kami</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="#destinations" class="btn btn-primary-custom btn-custom">Jelajahi Destinasi</a>
                    <a href="#contact" class="btn btn-outline-light btn-custom">Hubungi Kami</a>
                </div>
            </div>
        </div>

        <!-- Admin Login Button (Floating) -->
        <a href="login.php" class="btn btn-warning admin-login-btn">
            <i class="bi bi-lock-fill me-1"></i> Admin Login
        </a>
    </section>

    <!-- Destinations Section -->
    <section id="destinations" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Destinasi Populer</h2>
                <p class="text-muted">Temukan petualangan menarik yang menanti Anda</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" class="card-img-top" alt="Gunung Rinjani">
                        <div class="card-body">
                            <h5 class="card-title">Pendakian Gunung Rinjani</h5>
                            <p class="card-text">Jelajahi keindahan gunung berapi aktif kedua tertinggi di Indonesia.</p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-geo-alt"></i> Lombok, NTB</span>
                                <span><i class="bi bi-clock"></i> 4 Hari</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="https://images.unsplash.com/photo-1505228395891-9a51e7e86bf6?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" class="card-img-top" alt="Raja Ampat">
                        <div class="card-body">
                            <h5 class="card-title">Snorkeling Raja Ampat</h5>
                            <p class="card-text">Temukan keindahan bawah laut terbaik dunia dengan beragam biota laut.</p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-geo-alt"></i> Papua Barat</span>
                                <span><i class="bi bi-clock"></i> 7 Hari</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <img src="https://images.unsplash.com/photo-1506197603052-3cc9c3a201bd?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" class="card-img-top" alt="Green Canyon">
                        <div class="card-body">
                            <h5 class="card-title">Arung Jeram Green Canyon</h5>
                            <p class="card-text">Petualangan seru menyusuri sungai dengan tebing-tebing hijau yang menjulang.</p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-geo-alt"></i> Pangandaran, Jabar</span>
                                <span><i class="bi bi-clock"></i> 2 Hari</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" class="img-fluid rounded shadow" alt="About Us">
                </div>
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">Tentang Lolong Adventure</h2>
                    <p class="lead">Kami menyediakan pengalaman wisata alam yang aman, menyenangkan, dan berkesan sejak 2010.</p>
                    <p>Lolong Adventure adalah penyedia jasa wisata alam profesional yang telah berpengalaman lebih dari 10 tahun dalam membawa petualang mengeksplorasi keindahan alam Indonesia.</p>
                    <p>Dengan tim pemandu yang bersertifikat dan peralatan standar internasional, kami menjamin pengalaman wisata yang aman namun tetap menantang bagi semua tingkatan.</p>
                    <a href="#contact" class="btn btn-primary-custom mt-3">Hubungi Kami</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Hubungi Kami</h2>
                <p class="text-muted">Kirim pesan untuk informasi lebih lanjut</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" id="name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="subject" class="form-label">Subjek</label>
                                        <input type="text" class="form-control" id="subject" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Pesan</label>
                                        <textarea class="form-control" id="message" rows="4" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary-custom">Kirim Pesan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3">Lolong Adventure</h5>
                    <p>Penyedia jasa wisata alam profesional dengan standar keselamatan tinggi dan pengalaman tak terlupakan.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3">Link Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Beranda</a></li>
                        <li class="mb-2"><a href="#destinations" class="text-white text-decoration-none">Destinasi</a></li>
                        <li class="mb-2"><a href="#about" class="text-white text-decoration-none">Tentang Kami</a></li>
                        <li><a href="#contact" class="text-white text-decoration-none">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold mb-3">Kontak</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i> Jl. Petualangan No. 123, Jakarta</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i> +62 812 3456 7890</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@lolongadventure.com</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Lolong Adventure. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>