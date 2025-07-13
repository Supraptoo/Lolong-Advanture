<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check user authentication and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../landingpage.php');
    exit;
}

// Handle status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    header('Content-Type: application/json');

    $booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    // Validate status
    $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $booking_id]);

        // Get updated booking data
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $updated_booking = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Status booking berhasil diperbarui',
            'status' => $updated_booking['status'],
            'status_text' => ucfirst($updated_booking['status'])
        ]);
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status: ' . htmlspecialchars($e->getMessage())]);
        exit;
    }
}

// Handle search and filtering
$search = isset($_GET['search']) ? trim(filter_var($_GET['search'], FILTER_SANITIZE_STRING)) : '';
$status_filter = isset($_GET['status']) ? trim(filter_var($_GET['status'], FILTER_SANITIZE_STRING)) : '';

$query = "SELECT b.*, d.name as destination_name, u.full_name as customer_name, u.email as customer_email 
          FROM bookings b 
          JOIN destinations d ON b.destination_id = d.id 
          JOIN users u ON b.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (d.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if (!empty($status_filter) && in_array($status_filter, ['pending', 'confirmed', 'cancelled', 'completed'])) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY b.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Gagal mengambil data booking: ' . htmlspecialchars($e->getMessage());
    $bookings = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bookings - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-confirmed {
            background-color: #28a745;
            color: white;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }

        .status-completed {
            background-color: #17a2b8;
            color: white;
        }

        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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

        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .action-btns {
                white-space: nowrap;
            }
        }

        .customer-info {
            line-height: 1.2;
        }

        .customer-name {
            font-weight: 500;
        }

        .customer-email {
            font-size: 0.8rem;
            color: #6c757d;
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

            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-calendar-check me-2"></i> Kelola Bookings</h2>
                    <a href="../bookings/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Booking
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="card filter-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-funnel me-2"></i> Filter Bookings</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Cari</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    placeholder="Cari destinasi atau pelanggan..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter me-1"></i> Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-table me-2"></i> Daftar Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="bookingsTable" class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th width="70">ID</th>
                                        <th>Destinasi</th>
                                        <th>Pelanggan</th>
                                        <th width="120">Tanggal</th>
                                        <th width="100">Peserta</th>
                                        <th width="120">Total</th>
                                        <th width="120">Status</th>
                                        <th width="140">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr data-booking-id="<?php echo $booking['id']; ?>">
                                            <td><?php echo htmlspecialchars($booking['id']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['destination_name']); ?></td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-name"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                                    <div class="customer-email"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($booking['participants']); ?></td>
                                            <td>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>" id="status-badge-<?php echo $booking['id']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td class="action-btns">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-info" title="Lihat Detail">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $booking['id']; ?>" class="btn btn-warning" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-primary" title="Ubah Status"
                                                        onclick="showStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada data booking.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Status Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="statusForm" method="POST">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="booking_id" id="booking_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="statusSelect" class="form-label">Pilih Status Baru</label>
                            <select class="form-select" id="statusSelect" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="saveStatusBtn">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable with enhanced features
            var table = $('#bookingsTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                },
                columnDefs: [{
                        targets: 0,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        },
                        className: "text-center",
                        orderable: false,
                        searchable: false
                    },
                    {
                        orderable: false,
                        targets: [7] // Action column
                    },
                    {
                        searchable: false,
                        targets: [0, 4, 5, 6, 7] // Columns that shouldn't be searchable
                    }
                ],
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                initComplete: function() {
                    $('.dataTables_filter input').attr('placeholder', 'Cari...');
                }
            });

            // Update row numbers when table changes (paging, sorting, etc)
            table.on('order.dt search.dt page.dt', function() {
                table.column(0, {
                    search: 'applied',
                    order: 'applied'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = i + 1;
                });
            }).draw();

            // Sidebar toggle with localStorage
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
                localStorage.setItem('sidebarState', $('#sidebar').hasClass('active') ? 'closed' : 'open');
            });

            // Initialize date picker if you have date filters (optional, remove if not used)
            $('.datepicker').flatpickr({
                dateFormat: 'Y-m-d',
                allowInput: true
            });

            // Close alert after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);

            // Show status modal
            function showStatusModal(bookingId, currentStatus) {
                $('#booking_id').val(bookingId);
                $('#statusSelect').val(currentStatus);
                $('#statusModal').modal('show');
            }

            // Handle form submission with AJAX
            $('#statusForm').on('submit', function(e) {
                e.preventDefault();

                const bookingId = $('#booking_id').val();
                const newStatus = $('#statusSelect').val();
                const saveBtn = $('#saveStatusBtn');

                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memproses...');

                $.ajax({
                    url: window.location.href,
                    method: 'POST',
                    data: {
                        update_status: 1,
                        booking_id: bookingId,
                        status: newStatus
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            const badge = $(`#status-badge-${bookingId}`);
                            badge.removeClass('status-pending status-confirmed status-cancelled status-completed')
                                .addClass(`status-${data.status}`)
                                .text(data.status_text);

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            $('#statusModal').modal('hide');
                            table.draw(); // Refresh table if needed
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: data.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan saat mengubah status: ' + error
                        });
                    },
                    complete: function() {
                        saveBtn.prop('disabled', false).text('Simpan Perubahan');
                    }
                });
            });

            // Confirm delete (if implemented)
            function confirmDelete(bookingId, bookingName) {
                Swal.fire({
                    title: 'Konfirmasi Penghapusan',
                    html: `Apakah Anda yakin ingin menghapus booking <b>${bookingName}</b>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `delete.php?id=${bookingId}`;
                    }
                });
            }
        });
    </script>
</body>

</html>