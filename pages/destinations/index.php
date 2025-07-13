<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../../login.php');
    exit();
}

// Fetch all destinations (removed is_active from query since column doesn't exist)
$stmt = $pdo->query("SELECT d.*, COUNT(b.id) as booking_count 
                     FROM destinations d 
                     LEFT JOIN bookings b ON d.id = b.destination_id 
                     GROUP BY d.id 
                     ORDER BY d.created_at DESC");
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Destinasi - Lolong Adventure</title>
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

        .navbar {
            padding: 15px 20px;
            background: white !important;
            border-bottom: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .destination-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .badge-featured {
            background-color: var(--accent-color);
            color: var(--secondary-color);
        }

        .badge-active {
            background-color: var(--primary-color);
            color: white;
        }

        .badge-inactive {
            background-color: #6c757d;
            color: white;
        }

        .action-buttons .btn {
            padding: 0.3rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 5px;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .status-toggle {
            cursor: pointer;
        }

        .price-column {
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('../../includes/sidebar.php'); ?>

        <div id="content" class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </span>
                        <a href="../../logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="container-fluid p-4">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-map me-2"></i> Kelola Destinasi Wisata</h2>
                    <div>
                        <a href="add.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Destinasi
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i> Daftar Destinasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="destinationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">ID</th>
                                        <th width="100">Gambar</th>
                                        <th>Nama Destinasi</th>
                                        <th>Lokasi</th>
                                        <th width="100">Durasi</th>
                                        <th width="120">Harga</th>
                                        <th width="120">Status</th>
                                        <th width="120">Pemesanan</th>
                                        <th width="140">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($destinations as $destination): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $destination['id']; ?></td>
                                            <td class="text-center">
                                                <img src="../../assets/images/destinations/<?php echo !empty($destination['image_url']) ? htmlspecialchars($destination['image_url']) : 'default.jpg'; ?>"
                                                    alt="<?php echo htmlspecialchars($destination['name']); ?>"
                                                    class="destination-img"
                                                    onerror="this.onerror=null;this.src='../../assets/images/destinations/default.jpg';">
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($destination['name']); ?>
                                                <?php if (!empty($destination['is_featured']) && $destination['is_featured'] == 1): ?>
                                                    <span class="badge badge-featured ms-2">
                                                        <i class="bi bi-star-fill me-1"></i>Featured
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($destination['location']); ?></td>
                                            <td><?php echo htmlspecialchars($destination['duration']); ?> hari</td>
                                            <td class="price-column">Rp <?php echo number_format($destination['price'], 0, ',', '.'); ?></td>
                                            <td class="text-center">
                                                <!-- Assuming all destinations are active since is_active column doesn't exist -->
                                                <span class="badge badge-active">
                                                    Aktif
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <?php echo $destination['booking_count']; ?> pemesanan
                                                </span>
                                            </td>
                                            <td class="text-center action-buttons">
                                                <a href="edit.php?id=<?php echo $destination['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <!-- Removed status toggle since is_active column doesn't exist -->
                                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $destination['id']; ?>)" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus destinasi ini? Data yang dihapus tidak dapat dikembalikan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a id="deleteConfirmBtn" href="#" class="btn btn-danger">Hapus</a>
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
            // Initialize DataTable with row numbering
            var table = $('#destinationsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                columnDefs: [{
                        targets: 0,
                        render: function(data, type, row, meta) {
                            // Mengembalikan nomor urut berdasarkan halaman
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        orderable: false,
                        targets: [1, 6, 8] // Disable sorting on image, status, and action columns
                    },
                    {
                        className: "text-center",
                        targets: [0, 1, 6, 7, 8] // Center align these columns
                    }
                ],
                order: [
                    [0, 'asc'] // Default sort by the row number ascending
                ],
                createdRow: function(row, data, dataIndex) {
                    // Menambahkan data-id untuk referensi ID asli
                    $(row).attr('data-id', data.id);
                }
            });

            // Update row numbers when page changes
            table.on('order.dt search.dt page.dt', function() {
                table.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            }).draw();

            // Sidebar toggle
            $('#sidebarCollapse').click(function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('active');
            });
        });

        function confirmDelete(id) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('deleteConfirmBtn').href = 'delete.php?id=' + id;
            deleteModal.show();
        }
    </script>
</body>

</html>