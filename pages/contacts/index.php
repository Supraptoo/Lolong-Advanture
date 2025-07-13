<?php
session_start();
require_once(__DIR__ . '/../../config/database.php');

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header('Location: ' . dirname(dirname(dirname($_SERVER['PHP_SELF']))) . '/login.php');
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

// Pagination settings
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchCondition = '';
$params = [];
$queryParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE name LIKE :search OR email LIKE :search OR subject LIKE :search OR message LIKE :search";
    $params[':search'] = "%$search%";
    $queryParams['search'] = $search;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM contacts $searchCondition";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalContacts = $stmt->fetchColumn();
$totalPages = ceil($totalContacts / $perPage);

// Query untuk mendapatkan pesan kontak dengan pagination
$query = "SELECT * FROM contacts $searchCondition ORDER BY is_read ASC, created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($query);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tandai pesan sebagai dibaca
if (isset($_GET['mark_as_read']) && is_numeric($_GET['mark_as_read'])) {
    $contactId = (int)$_GET['mark_as_read'];
    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->execute([$contactId]);
    $_SESSION['success_message'] = "Pesan telah ditandai sebagai dibaca";
    header("Location: index.php?" . http_build_query($queryParams));
    exit();
}

// Tandai semua sebagai dibaca
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE is_read = 0");
    $stmt->execute();
    $_SESSION['success_message'] = "Semua pesan telah ditandai sebagai dibaca";
    header("Location: index.php?" . http_build_query($queryParams));
    exit();
}

// Hapus pesan
if (isset($_POST['delete_contact'])) {
    $contactId = (int)$_POST['contact_id'];
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$contactId]);
    $_SESSION['success_message'] = "Pesan telah dihapus";
    header("Location: index.php?" . http_build_query($queryParams));
    exit();
}

// Hapus semua pesan yang sudah dibaca
if (isset($_POST['delete_all_read'])) {
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE is_read = 1");
    $stmt->execute();
    $_SESSION['success_message'] = "Semua pesan yang sudah dibaca telah dihapus";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesan - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            transition: margin-left 0.3s ease;
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
        }

        .main-content.full-width {
            margin-left: 0;
            width: 100%;
        }

        .unread-message {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }

        .message-card {
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .message-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .message-card:hover .message-actions {
            opacity: 1;
        }

        .badge-unread {
            background-color: #0d6efd;
        }

        .badge-read {
            background-color: #6c757d;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .search-box {
            max-width: 300px;
        }

        .navbar {
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .search-box {
                max-width: 100%;
            }

            .text-md-end {
                text-align: left !important;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Include Sidebar -->
        <?php include(__DIR__ . '/../../includes/sidebar.php'); ?>

        <!-- Main Content -->
        <div id="content" class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </span>
                        <a href="<?php echo getBaseUrl(); ?>logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="container-fluid">
                <div class="content-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-envelope me-2"></i> Kelola Pesan</h2>
                        <div>
                            <span class="badge bg-primary me-2">
                                <i class="bi bi-envelope"></i> <?php echo count(array_filter($contacts, fn($c) => !$c['is_read'])); ?> Belum dibaca
                            </span>
                            <span class="badge bg-secondary">
                                <i class="bi bi-envelope-open"></i> <?php echo count(array_filter($contacts, fn($c) => $c['is_read'])); ?> Terbaca
                            </span>
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message'];
                            unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Search and Action Buttons -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <form method="get" class="mb-3 mb-md-0">
                                        <div class="input-group search-box">
                                            <input type="text" class="form-control" name="search" placeholder="Cari pesan..." value="<?php echo htmlspecialchars($search); ?>">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <?php if (!empty($search)): ?>
                                                <a href="index.php" class="btn btn-outline-secondary">
                                                    <i class="bi bi-x"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="btn-group">
                                        <a href="index.php?mark_all_read=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-success">
                                            <i class="bi bi-check-all me-1"></i> Tandai Semua Dibaca
                                        </a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua pesan yang sudah dibaca?')">
                                            <button type="submit" name="delete_all_read" class="btn btn-danger ms-2">
                                                <i class="bi bi-trash me-1"></i> Hapus Semua Terbaca
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contacts Table -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-table me-2"></i> Daftar Pesan Masuk</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($contacts)): ?>
                                <div class="alert alert-info">Tidak ada pesan yang ditemukan.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="contactsTable">
                                        <thead>
                                            <tr>
                                                <th width="50px">#</th>
                                                <th>Pengirim</th>
                                                <th>Subjek</th>
                                                <th>Pesan</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th width="120px">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($contacts as $index => $contact): ?>
                                                <tr class="<?php echo !$contact['is_read'] ? 'unread-message' : ''; ?>">
                                                    <td><?php echo $index + 1 + $offset; ?></td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <strong><?php echo htmlspecialchars($contact['name']); ?></strong>
                                                            <small class="text-muted"><?php echo htmlspecialchars($contact['email']); ?></small>
                                                            <?php if ($contact['phone']): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($contact['phone']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                                    <td>
                                                        <div class="message-preview" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            <?php echo htmlspecialchars($contact['message']); ?>
                                                        </div>
                                                    </td>
                                                    <td><?php echo date('d M Y H:i', strtotime($contact['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill bg-<?php echo $contact['is_read'] ? 'secondary' : 'primary'; ?>">
                                                            <?php echo $contact['is_read'] ? 'Terbaca' : 'Belum dibaca'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="message-actions">
                                                        <div class="d-flex">
                                                            <a href="#" class="btn btn-sm btn-primary me-1 view-message"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#messageModal"
                                                                data-name="<?php echo htmlspecialchars($contact['name']); ?>"
                                                                data-email="<?php echo htmlspecialchars($contact['email']); ?>"
                                                                data-phone="<?php echo htmlspecialchars($contact['phone'] ?? '-'); ?>"
                                                                data-subject="<?php echo htmlspecialchars($contact['subject']); ?>"
                                                                data-message="<?php echo htmlspecialchars($contact['message']); ?>"
                                                                data-date="<?php echo date('d M Y H:i', strtotime($contact['created_at'])); ?>">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="index.php?mark_as_read=<?php echo $contact['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-sm btn-success me-1">
                                                                <i class="bi bi-check-lg"></i>
                                                            </a>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                                                <button type="submit" name="delete_contact" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?')">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Detail Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="messageModalLabel">Detail Pesan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Nama:</strong> <span id="modal-name"></span></p>
                            <p><strong>Email:</strong> <span id="modal-email"></span></p>
                            <p><strong>Telepon:</strong> <span id="modal-phone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Subjek:</strong> <span id="modal-subject"></span></p>
                            <p><strong>Tanggal:</strong> <span id="modal-date"></span></p>
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <p><strong>Pesan:</strong></p>
                        <div id="modal-message" class="bg-light p-3 rounded"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });

            // Initialize DataTable
            $('#contactsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                searching: false,
                paging: false,
                info: false,
                columnDefs: [{
                    orderable: false,
                    targets: [6]
                }]
            });

            // Handle modal view
            $('.view-message').on('click', function() {
                $('#modal-name').text($(this).data('name'));
                $('#modal-email').text($(this).data('email'));
                $('#modal-phone').text($(this).data('phone'));
                $('#modal-subject').text($(this).data('subject'));
                $('#modal-date').text($(this).data('date'));
                $('#modal-message').text($(this).data('message'));
            });
        });
    </script>
</body>

</html>