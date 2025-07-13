<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check user authentication and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../landingpage.php');
    exit;
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $delete_id = filter_var($_POST['delete_id'], FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['role'] !== 'admin') {
            $pdo->beginTransaction();
            // Delete related bookings first
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt->execute([$delete_id]);
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$delete_id]);
            $pdo->commit();
            $_SESSION['success_message'] = "Pengguna dan pemesanan terkait berhasil dihapus.";
        } else {
            $_SESSION['error_message'] = "Tidak dapat menghapus admin atau pengguna tidak valid.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Gagal menghapus pengguna: " . htmlspecialchars($e->getMessage());
    }
    header('Location: index.php');
    exit;
}

// Handle pagination and search
$search = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_STRING)) : '';
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $query = "SELECT u.id, u.username, u.full_name, u.email, u.phone, u.created_at,
              (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'confirmed') as confirmed_bookings,
              (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'pending') as pending_bookings,
              (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'cancelled') as cancelled_bookings,
              (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'completed') as completed_bookings,
              (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = 'failed') as failed_bookings
              FROM users u WHERE (role IS NULL OR role != 'admin')";
    $params = [];

    if ($search) {
        $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
    }

    // Count total users
    $count_query = "SELECT COUNT(*) FROM users WHERE (role IS NULL OR role != 'admin')";
    if ($search) {
        $count_query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    }
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);

    // Fetch users
    $query .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Gagal mengambil data pengguna: " . htmlspecialchars($e->getMessage());
    $users = [];
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pelanggan - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            transition: all 0.3s;
            width: calc(100% - 250px);
            margin-left: 250px;
            padding: 20px;
        }

        .main-content.full-width {
            width: 100%;
            margin-left: 0;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            text-transform: capitalize;
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .status-confirmed {
            background-color: #28a745;
            color: white;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }

        .status-completed {
            background-color: #17a2b8;
            color: white;
        }

        .status-failed {
            background-color: #6c757d;
            color: white;
        }

        .status-no-bookings {
            background-color: #ced4da;
            color: #212529;
        }

        .filter-card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 5px;
        }

        .pagination {
            justify-content: center;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }

            .table-responsive {
                overflow-x: auto;
            }

            .action-btns {
                white-space: nowrap;
            }

            .status-badge {
                display: block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('../../includes/sidebar.php'); ?>

        <div id="content" class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role'] ?? 'staff'); ?></span>
                        </span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <div class="admin-container">
                <div class="admin-header">
                    <h1 class="mb-4">Kelola Pelanggan</h1>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error_message'];
                                                                        unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success_message'];
                                                                    unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="card filter-card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0">Filter Pelanggan</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row g-3">
                            <div class="col-12 col-md-8">
                                <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau telepon..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Cari</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Tanggal Dibuat</th>
                                <th>Status Pemesanan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="booking-summary">
                                            <?php if ($user['confirmed_bookings'] > 0): ?>
                                                <span class="status-badge status-confirmed">Confirmed: <?php echo $user['confirmed_bookings']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($user['pending_bookings'] > 0): ?>
                                                <span class="status-badge status-pending">Pending: <?php echo $user['pending_bookings']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($user['cancelled_bookings'] > 0): ?>
                                                <span class="status-badge status-cancelled">Cancelled: <?php echo $user['cancelled_bookings']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($user['completed_bookings'] > 0): ?>
                                                <span class="status-badge status-completed">Completed: <?php echo $user['completed_bookings']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($user['failed_bookings'] > 0): ?>
                                                <span class="status-badge status-failed">Failed: <?php echo $user['failed_bookings']; ?></span>
                                            <?php endif; ?>
                                            <?php if ($user['confirmed_bookings'] + $user['pending_bookings'] + $user['cancelled_bookings'] + $user['completed_bookings'] + $user['failed_bookings'] == 0): ?>
                                                <span class="status-badge status-no-bookings">No Bookings</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="view-bookings mt-2">
                                            <a href="../kelola_user/view_bookings.php?user_id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm">Lihat Detail</a>
                                        </div>
                                    </td>
                                    <td class="action-btns">
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" action="index.php" style="display:inline;"
                                            onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">Tidak ada data pelanggan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                                    <span aria-hidden="true">« Sebelumnya</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                                    <span aria-hidden="true">Selanjutnya »</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#user-table').DataTable({
                paging: false,
                ordering: false,
                info: false,
                searching: false,
                language: {
                    emptyTable: "Tidak ada data pelanggan.",
                    zeroRecords: "Tidak ada data pelanggan yang cocok."
                }
            });

            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });
        });
    </script>
</body>

</html>