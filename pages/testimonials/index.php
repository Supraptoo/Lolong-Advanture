<?php
session_start();
require_once('../../config/database.php');

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Fungsi untuk mendapatkan base URL
function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
    return $protocol . $domain . str_replace('//', '/', $path . '/');
}

// Query untuk mendapatkan data testimoni
try {
    $query = "SELECT t.*, 
                     b.user_id,
                     u.full_name as author_name,
                     u.role as author_role,
                     d.name as destination_name,
                     b.booking_date
              FROM testimonials t
              LEFT JOIN bookings b ON t.booking_id = b.id
              LEFT JOIN users u ON b.user_id = u.id
              LEFT JOIN destinations d ON b.destination_id = d.id
              ORDER BY t.created_at DESC";
    $testimonials = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error mengambil data testimoni: " . $e->getMessage());
}

// Fungsi untuk mengubah status approval
if (isset($_POST['action']) && !empty($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $action = $_POST['action'];

    try {
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE testimonials SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Testimoni telah disetujui";
        } elseif ($action == 'reject') {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Testimoni telah ditolak";
        }

        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Terjadi kesalahan: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Testimoni - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
            --sidebar-width: 250px;
        }

        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Card Styling */
        .card-testimoni {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .card-testimoni:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .testimoni-pending {
            border-left-color: #ffc107;
        }

        .testimoni-approved {
            border-left-color: #28a745;
        }

        .rating {
            color: #e9c46a;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Navbar */
        .navbar {
            background-color: white !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Custom Colors */
        .bg-primary-custom {
            background-color: var(--primary-color) !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(42, 157, 143, 0.05);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Include Sidebar -->
        <?php include('../../includes/sidebar.php'); ?>

        <!-- Main Content -->
        <div id="content" class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary-custom">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <?php if (isset($_SESSION['full_name']) && isset($_SESSION['role'])): ?>
                            <span class="navbar-text me-3">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <span class="badge bg-primary ms-2"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></span>
                            </span>
                        <?php endif; ?>
                        <a href="<?php echo getBaseUrl(); ?>logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="container-fluid p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-chat-square-quote me-2"></i> Kelola Testimoni</h2>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']);
                        unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Testimonials Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary-custom text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Daftar Testimoni</h5>
                            <div>
                                <span class="badge bg-warning me-2">Pending: <?php echo count(array_filter($testimonials, function ($t) {
                                                                                    return !$t['is_approved'];
                                                                                })); ?></span>
                                <span class="badge bg-success">Approved: <?php echo count(array_filter($testimonials, function ($t) {
                                                                                return $t['is_approved'];
                                                                            })); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($testimonials)): ?>
                            <div class="empty-state">
                                <i class="bi bi-info-circle-fill"></i>
                                <h4>Tidak ada data yang tersedia</h4>
                                <p class="text-muted">Belum ada testimoni yang diterima</p>
                                <a href="#" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Testimoni
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover" id="testimonialsTable">
                                    <thead>
                                        <tr>
                                            <th width="50px">#</th>
                                            <th>Pengguna</th>
                                            <th>Destinasi</th>
                                            <th>Testimoni</th>
                                            <th>Rating</th>
                                            <th>Tanggal Booking</th>
                                            <th>Status</th>
                                            <th width="120px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testimonials as $index => $testimonial): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="<?php echo getBaseUrl(); ?>assets/img/default-avatar.png"
                                                            class="user-avatar me-2" alt="User Avatar">
                                                        <div>
                                                            <?php echo htmlspecialchars($testimonial['author_name']); ?>
                                                            <?php if (!empty($testimonial['author_role'])): ?>
                                                                <small class="text-muted d-block"><?php echo htmlspecialchars($testimonial['author_role']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($testimonial['destination_name'] ?? 'Tidak spesifik'); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($testimonial['content'])); ?></td>
                                                <td>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="bi bi-star<?php echo $i <= $testimonial['rating'] ? '-fill' : ''; ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($testimonial['booking_date'])); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?php echo $testimonial['is_approved'] ? 'success' : 'warning'; ?>">
                                                        <?php echo $testimonial['is_approved'] ? 'Disetujui' : 'Menunggu'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!$testimonial['is_approved']): ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Setujui">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                                                            <input type="hidden" name="action" value="reject">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Tolak">
                                                                <i class="bi bi-x-lg"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable if table exists
            if ($('#testimonialsTable').length) {
                $('#testimonialsTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    },
                    columnDefs: [{
                        orderable: false,
                        targets: [0, 7]
                    }]
                });
            }

            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });
        });
    </script>
</body>

</html>