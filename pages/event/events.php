<?php
require_once('../../config/database.php');

// Fetch active events (limit to 3)
$stmt = $pdo->query("SELECT * FROM events WHERE is_active = 1 ORDER BY event_date DESC LIMIT 3");
$latest_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bulan dalam Bahasa Indonesia
$bulan = array(
    'January' => 'JAN',
    'February' => 'FEB',
    'March' => 'MAR',
    'April' => 'APR',
    'May' => 'MEI',
    'June' => 'JUN',
    'July' => 'JUL',
    'August' => 'AGU',
    'September' => 'SEP',
    'October' => 'OKT',
    'November' => 'NOV',
    'December' => 'DES'
);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lolong Adventure - Wisata Alam Eksklusif</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        /* CSS Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            height: auto;
        }

        /* Navbar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 0.5rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo img {
            height: 40px;
        }

        .logo-text {
            font-weight: 700;
            font-size: 1.2rem;
        }

        .logo-text span {
            color: #2a7f62;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #2a7f62;
        }

        .btn {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #2a7f62;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1f5e48;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            color: #2a7f62;
            border: 1px solid #2a7f62;
        }

        .btn-secondary:hover {
            background-color: #2a7f62;
            color: white;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('image/landingpage/hero-bg.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding-top: 80px;
        }

        .hero-content {
            max-width: 800px;
            padding: 0 1.5rem;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        /* Section Styles */
        .section {
            padding: 5rem 1.5rem;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: #2a7f62;
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }

        /* About Section */
        .about-content {
            display: flex;
            align-items: center;
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .about-text {
            flex: 1;
        }

        .about-text h3 {
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            color: #2a7f62;
        }

        .about-text p {
            margin-bottom: 1rem;
        }

        .about-image {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Destinations Section */
        .destinations {
            background-color: #f9f9f9;
        }

        .destination-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .destination-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .destination-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .card-image {
            height: 200px;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .destination-card:hover .card-image img {
            transform: scale(1.1);
        }

        .card-content {
            padding: 1.5rem;
        }

        .card-content h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #2a7f62;
        }

        .card-meta {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .card-meta i {
            color: #2a7f62;
        }

        /* Events Section */
        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin: 40px auto;
            max-width: 1200px;
            padding: 0 20px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .event-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }

        .event-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .event-card:hover .event-image img {
            transform: scale(1.1);
        }

        .event-date {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            z-index: 2;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .event-day {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: #2a7f62;
            line-height: 1;
        }

        .event-month {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .event-category {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #2a7f62;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
        }

        .event-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .event-content h3 {
            margin: 0 0 15px;
            font-size: 22px;
            color: #333;
        }

        .event-excerpt {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
            flex: 1;
        }

        .event-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
            color: #777;
        }

        .event-meta span {
            display: flex;
            align-items: center;
        }

        .event-meta i {
            margin-right: 5px;
            color: #2a7f62;
        }

        .event-price {
            font-weight: 600;
            color: #2a7f62;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .event-price i {
            margin-right: 5px;
        }

        .view-all-events {
            text-align: center;
            margin-top: 40px;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #2a7f62 0%, #1f5e48 100%);
            color: white;
            text-align: center;
            padding: 5rem 1.5rem;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        /* Footer */
        footer {
            background-color: #222;
            color: #ddd;
            padding: 4rem 1.5rem 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.8rem;
        }

        .footer-links a {
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #2a7f62;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #333;
            color: white;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background-color: #2a7f62;
            transform: translateY(-3px);
        }

        .newsletter-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.6rem;
            border: none;
            border-radius: 4px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #444;
            font-size: 0.9rem;
            color: #aaa;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .about-content {
                flex-direction: column;
            }

            .about-image {
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-links {
                position: fixed;
                top: 80px;
                left: 0;
                width: 100%;
                background-color: white;
                flex-direction: column;
                align-items: center;
                padding: 1rem 0;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
                transform: translateY(-150%);
                transition: transform 0.3s ease;
            }

            .nav-links.active {
                transform: translateY(0);
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .events-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 1.8rem;
            }

            .section-title h2 {
                font-size: 2rem;
            }

            .newsletter-form {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <img src="assets/images/logo.png" alt="Lolong Adventure Logo" />
            <div class="logo-text">Lolong <span>Adventure</span></div>
        </div>
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="fas fa-bars"></i>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="#home">Beranda</a>
            <a href="#about">Tentang Kami</a>
            <a href="#destinations">Destinasi</a>
            <a href="#events">Event</a>
            <a href="#contact">Kontak</a>
            <a href="login.php" class="btn btn-primary">Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <h1 data-aos="fade-up">
                Jelajahi Keindahan Alam Dengan Lolong Adventure
            </h1>
            <p data-aos="fade-up" data-aos-delay="200">
                Temukan pengalaman wisata alam tak terlupakan dengan pemandu
                profesional kami.
            </p>
            <div class="cta-buttons" data-aos="fade-up" data-aos-delay="400">
                <button type="submit" class="btn btn-primary">Pesan Tiket</button>
                <a href="#events" class="btn btn-secondary">Lihat Event Terbaru</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="section-title" data-aos="fade-up">
            <h2>Tentang Lolong Adventure</h2>
            <p>
                Kami menyediakan pengalaman wisata alam yang aman, menyenangkan, dan
                berkesan
            </p>
        </div>
        <div class="about-content">
            <div class="about-text" data-aos="fade-right">
                <h3>Pengalaman Wisata Terbaik Sejak 2010</h3>
                <p>
                    Lolong Adventure adalah penyedia jasa wisata alam profesional yang
                    telah berpengalaman lebih dari 10 tahun dalam membawa petualang
                    mengeksplorasi keindahan alam Indonesia.
                </p>
                <p>
                    Dengan tim pemandu yang bersertifikat dan peralatan standar
                    internasional, kami menjamin pengalaman wisata yang aman namun tetap
                    menantang bagi semua tingkatan.
                </p>
                <p>
                    Kami berkomitmen untuk memberikan pelayanan terbaik sambil tetap
                    menjaga kelestarian alam dan mendukung masyarakat lokal.
                </p>
                <a href="#contact" class="btn btn-primary">Pelajari Lebih Lanjut</a>
            </div>
            <div class="about-image" data-aos="fade-left">
                <img src="image/landingpage/about.jpg" alt="Tim Lolong Adventure" />
            </div>
        </div>
    </section>

    <!-- Destinations Section -->
    <section class="section destinations" id="destinations">
        <div class="section-title" data-aos="fade-up">
            <h2>Destinasi Wisata</h2>
            <p>
                Temukan tempat-tempat menakjubkan yang bisa anda kunjungi bersama kami
            </p>
        </div>
        <div class="destination-grid">
            <div class="destination-card" data-aos="fade-up" data-aos-delay="100">
                <div class="card-image">
                    <img src="image/landingpage/rafting.jpg" alt="Arung Jeram" />
                </div>
                <div class="card-content">
                    <h3>Arung Jeram</h3>
                    <p>
                        Salah satu daya tarik utama Lolong Adventure adalah arung jeram di
                        Sungai Sengkarang. Dengan aliran sungai yang cukup menantang namun
                        tetap aman bagi pemula, rafting di Lolong menawarkan kombinasi
                        antara petualangan dan keindahan alam.
                    </p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                    </div>
                </div>
            </div>
            <div class="destination-card" data-aos="fade-up" data-aos-delay="200">
                <div class="card-image">
                    <img src="image/landingpage/camping.jpg" alt="Camping" />
                </div>
                <div class="card-content">
                    <h3>Camping Area</h3>
                    <p>
                        Bagi pecinta alam, Lolong Adventure menyediakan area camping yang
                        nyaman dan aman. Berada di tepi sungai dengan suasana alami yang
                        asri, area ini cocok untuk kemah keluarga, komunitas, atau
                        kegiatan pramuka.
                    </p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                    </div>
                </div>
            </div>
            <div class="destination-card" data-aos="fade-up" data-aos-delay="300">
                <div class="card-image">
                    <img src="image/landingpage/outbound.jpg" alt="Outbound" />
                </div>
                <div class="card-content">
                    <h3>Outbound</h3>
                    <p>
                        Lolong Adventure menawarkan pengalaman outbound yang seru dan
                        menantang di tengah alam. Dengan pemandangan hijau dan udara segar
                        dari hutan sekitar, tempat ini cocok untuk kegiatan tim building,
                        pelatihan, dan rekreasi kelompok.
                    </p>
                    <div class="card-meta">
                        <span><i class="fas fa-map-marker-alt"></i> Lolong, Pekalongan</span>
                        <span><i class="fas fa-clock"></i> 2 Hari</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="section events" id="events">
        <div class="section-title" data-aos="fade-up">
            <h2>Event Terbaru</h2>
            <p>
                Ikuti kegiatan seru dan spesial yang kami selenggarakan secara berkala
            </p>
        </div>
        <div class="events-container">
            <?php if (empty($latest_events)): ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Tidak ada event yang tersedia saat ini</p>
                </div>
            <?php else: ?>
                <?php
                $delay = 100;
                foreach ($latest_events as $event):
                    // Format tanggal
                    $event_date = strtotime($event['event_date']);
                    $day = date('d', $event_date);
                    $month = $bulan[date('F', $event_date)];

                    // Format waktu
                    $start_time = date('H.i', strtotime($event['start_time']));
                    $end_time = date('H.i', strtotime($event['end_time']));

                    // Default image fallback
                    $image_url = !empty($event['image_url']) ? '../../assets/images/events/' . $event['image_url'] : 'image/landingpage/default-event.jpg';
                ?>
                    <div class="event-card" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="event-date">
                            <span class="event-day"><?php echo $day; ?></span>
                            <span class="event-month"><?php echo $month; ?></span>
                        </div>
                        <div class="event-image">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>"
                                onerror="this.onerror=null;this.src='image/landingpage/default-event.jpg';">
                            <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                        </div>
                        <div class="event-content">
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p class="event-excerpt"><?php echo substr(htmlspecialchars($event['description']), 0, 150); ?>...</p>
                            <div class="event-meta">
                                <span><i class="fas fa-clock"></i> <?php echo $start_time; ?> - <?php echo $end_time; ?> WIB</span>
                                <span><i class="fas fa-users"></i> <?php echo $event['max_participants']; ?> Peserta</span>
                            </div>
                            <div class="event-price">
                                <i class="fas fa-tag"></i> Rp <?php echo number_format($event['price'], 0, ',', '.'); ?>
                            </div>
                            <a href="pages/customer/event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">Daftar Sekarang</a>
                        </div>
                    </div>
                <?php
                    $delay += 100;
                endforeach;
                ?>
            <?php endif; ?>
        </div>

        <div class="view-all-events" data-aos="fade-up">
            <a href="pages/customer/events.php" class="btn btn-secondary">Lihat Semua Event <i class="fas fa-arrow-right"></i></a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="cta-content" data-aos="fade-up">
            <h2>Siap untuk Petualangan Anda?</h2>
            <p>
                Dapatkan tiket secara online sekarang juga dan dapatkan penawaran
                spesial untuk paket wisata pertama Anda bersama Lolong Adventure.
            </p>
            <div class="cta-buttons">
                <a href="#contact" class="btn btn-primary">Dapatkan Tiket</a>
                <a href="#events" class="btn btn-secondary">Lihat Event Spesial</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Tentang Kami</h3>
                <p>
                    Lolong Adventure adalah penyedia jasa wisata alam profesional yang
                    berkomitmen untuk memberikan pengalaman petualangan terbaik dengan
                    standar keselamatan tinggi.
                </p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Link Cepat</h3>
                <ul class="footer-links">
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#destinations">Destinasi</a></li>
                    <li><a href="#events">Event</a></li>
                    <li><a href="#contact">Kontak</a></li>
                    <li><a href="#">Blog</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Newsletter</h3>
                <p>Dapatkan info event dan promo terbaru langsung ke email Anda</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Alamat Email Anda" required>
                    <button type="submit" class="btn btn-primary">Berlangganan</button>
                </form>
            </div>
            <div class="footer-column">
                <h3>Kontak Kami</h3>
                <p>
                    <i class="fas fa-map-marker-alt"></i> Jl. Lolong, Karanganyar,
                    Pekalongan
                </p>
                <p><i class="fas fa-phone"></i> +62 812 3456 7890</p>
                <p><i class="fas fa-envelope"></i> info@lolongadventure.com</p>
                <p><i class="fas fa-clock"></i> Buka setiap hari 08.00 - 17.00 WIB</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Lolong Adventure. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS animation
        AOS.init({
            duration: 800,
            easing: "ease-in-out",
            once: true,
        });

        // Navbar scroll effect
        window.addEventListener("scroll", function() {
            const navbar = document.querySelector(".navbar");
            if (window.scrollY > 50) {
                navbar.classList.add("scrolled");
            } else {
                navbar.classList.remove("scrolled");
            }
        });

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById("mobileMenuBtn");
        const navLinks = document.getElementById("navLinks");

        mobileMenuBtn.addEventListener("click", function() {
            navLinks.classList.toggle("active");
            this.innerHTML = navLinks.classList.contains("active") ?
                '<i class="fas fa-times"></i>' :
                '<i class="fas fa-bars"></i>';
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener("click", function(e) {
                e.preventDefault();

                if (navLinks.classList.contains("active")) {
                    navLinks.classList.remove("active");
                    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                }

                document.querySelector(this.getAttribute("href")).scrollIntoView({
                    behavior: "smooth",
                });
            });
        });

        // Enhanced event card animations
        const eventCards = document.querySelectorAll('.event-card');
        eventCards.forEach(card => {
            // Scale animation on hover
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
                card.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.12)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
                card.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.08)';
            });

            // Click animation
            card.addEventListener('mousedown', () => {
                card.style.transform = 'translateY(-5px) scale(0.98)';
            });

            card.addEventListener('mouseup', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
            });
        });
    </script>
</body>

</html>