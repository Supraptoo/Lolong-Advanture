<?php
// Fungsi untuk mendapatkan base URL yang benar
function getCorrectBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];

    // Hitung kedalaman direktori
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    $projectPath = '/project_wisata/'; // Sesuaikan dengan nama folder project Anda

    return $protocol . $domain . $projectPath;
}

$baseUrl = getCorrectBaseUrl();
?>

<div class="sidebar bg-dark text-white" id="sidebar">
    <!-- Sidebar Header dengan Logo -->
    <div class="sidebar-header text-center py-4 position-relative">
        <div class="d-flex align-items-center justify-content-center">
            <i class="bi bi-compass fs-3 me-2" style="color: #e9c46a;"></i>
            <h3 class="mb-0">Lolong <span style="color: #e9c46a;">Adventure</span></h3>
        </div>
        <div class="sidebar-toggle d-lg-none position-absolute end-0 top-50 translate-middle-y me-3">
            <button class="btn btn-sm btn-outline-light" id="sidebarCollapseMobile">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <!-- Menu Sidebar -->
    <ul class="list-unstyled components ps-0 mb-0">
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/dashboard.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-speedometer2 me-3"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'destinations') !== false ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/destinations/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-geo-alt me-3"></i>
                <span>Destinasi</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'destinations') !== false ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/event/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-map me-3"></i>
                <span>Event</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'bookings') !== false ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/bookings/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-calendar-check me-3"></i>
                <span>Pemesanan</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'testimonials') !== false ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/testimonials/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-chat-square-quote me-3"></i>
                <span>Testimoni</span>
            </a>
        </li>
        <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'contacts') !== false ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/contacts/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-envelope me-3"></i>
                <span>Pesan</span>
            </a>
        </li>
        <?php if ($_SESSION['role'] == 'admin'): ?>
            <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : ''; ?>">
                <a href="<?php echo $baseUrl; ?>pages/kelola_user/index.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                    <i class="bi bi-people me-3"></i>
                    <span>Kelola Pelanggan</span>
                </a>
            </li>
        <?php endif; ?>
        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <a href="<?php echo $baseUrl; ?>pages/profile.php" class="d-flex align-items-center px-4 py-3 sidebar-link">
                <i class="bi bi-person me-3"></i>
                <span>Profil</span>
            </a>
        </li>
    </ul>
</div>

<style>
    /* Animasi dan Style Sidebar */
    .sidebar {
        width: 250px;
        min-height: 100vh;
        transition: all 0.3s ease-in-out;
        background: linear-gradient(180deg, #264653 0%, #2a9d8f 100%);
        position: fixed;
        z-index: 1000;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .sidebar-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .sidebar-link {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }

    .sidebar-link:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
        border-left: 3px solid #e9c46a;
    }

    .sidebar li.active .sidebar-link {
        color: white;
        background: rgba(255, 255, 255, 0.15);
        border-left: 3px solid #e9c46a;
    }

    .sidebar-toggle {
        transition: all 0.3s ease;
    }

    /* Untuk tampilan mobile */
    @media (max-width: 992px) {
        .sidebar {
            margin-left: -250px;
        }

        .sidebar.active {
            margin-left: 0;
        }
    }
</style>

<script>
    // Script untuk toggle sidebar di mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarCollapseMobile = document.getElementById('sidebarCollapseMobile');

        // Toggle sidebar di mobile
        if (sidebarCollapseMobile) {
            sidebarCollapseMobile.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }

        // Smooth transition untuk semua link
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Jika di mobile, tutup sidebar setelah memilih menu
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                }

                // Animasi active state
                document.querySelectorAll('.sidebar li').forEach(item => {
                    item.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });
    });
</script>