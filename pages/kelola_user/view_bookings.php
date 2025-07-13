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

// Handle booking ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = 'ID Booking tidak valid';
    header('Location: index.php');
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

if ($booking_id === false || $booking_id <= 0) {
    $_SESSION['error_message'] = 'ID Booking tidak valid';
    header('Location: index.php');
    exit;
}

// Query untuk mendapatkan detail booking
try {
    $stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, d.location, d.description, d.image_url, d.latitude, d.longitude,
                          u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
                          FROM bookings b 
                          JOIN destinations d ON b.destination_id = d.id 
                          JOIN users u ON b.user_id = u.id 
                          WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $_SESSION['error_message'] = 'Booking tidak ditemukan';
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Gagal mengambil data booking: ' . htmlspecialchars($e->getMessage());
    header('Location: index.php');
    exit();
}

// Format tanggal dan harga
$booking_date = date('d F Y', strtotime($booking['booking_date']));
$created_at = date('d F Y H:i', strtotime($booking['created_at']));
$updated_at = isset($booking['updated_at']) ? date('d F Y H:i', strtotime($booking['updated_at'])) : 'Belum diperbarui';
$total_price = number_format($booking['total_price'], 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Booking - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            background-color: #f8f9fa;
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

        .booking-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .detail-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            height: 100%;
        }

        .detail-card .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .destination-img {
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            width: 100%;
        }

        #map-container {
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        #map {
            height: 100%;
            width: 100%;
        }

        .badge-status {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            text-transform: uppercase;
            font-weight: 500;
        }

        .bg-pending {
            background-color: var(--warning-color) !important;
            color: #212529;
        }

        .bg-confirmed {
            background-color: var(--success-color) !important;
            color: white;
        }

        .bg-cancelled {
            background-color: var(--danger-color) !important;
            color: white;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 1.05rem;
            margin-bottom: 1rem;
        }

        .destination-info {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .destination-content {
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }

            .destination-img {
                height: 180px;
            }

            #map-container {
                height: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper d-flex">
        <?php include('../../includes/sidebar.php'); ?>

        <div id="content" class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
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
                    <h2 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Detail Booking</h2>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
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

                <!-- Header Booking -->
                <div class="booking-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3 class="mb-2">Booking #<?php echo htmlspecialchars($booking['id']); ?></h3>
                            <p class="mb-0"><i class="bi bi-calendar3 me-1"></i> Dibuat: <?php echo $created_at; ?></p>
                            <?php if ($updated_at !== 'Belum diperbarui'): ?>
                                <p class="mb-0"><i class="bi bi-clock-history me-1"></i> Diperbarui: <?php echo $updated_at; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <span class="badge rounded-pill badge-status bg-<?php echo strtolower($booking['status']); ?>" id="status-badge-<?php echo $booking['id']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Kolom Kiri - Informasi Booking dan Pelanggan -->
                    <div class="col-lg-6">
                        <div class="row g-4">
                            <!-- Informasi Booking -->
                            <div class="col-12">
                                <div class="card detail-card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Informasi Booking</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">ID Booking</div>
                                                    <div class="info-value">#<?php echo htmlspecialchars($booking['id']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">Tanggal Booking</div>
                                                    <div class="info-value"><?php echo $booking_date; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">Jumlah Peserta</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($booking['participants']); ?> orang</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">Total Harga</div>
                                                    <div class="info-value">Rp <?php echo $total_price; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <div class="info-label">Catatan Tambahan</div>
                                            <div class="info-value"><?php echo $booking['notes'] ? nl2br(htmlspecialchars($booking['notes'])) : '-'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi Pelanggan -->
                            <div class="col-12">
                                <div class="card detail-card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-person me-2"></i> Informasi Pelanggan</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="info-label">Nama Lengkap</div>
                                            <div class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">Email</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="info-label">Telepon</div>
                                                    <div class="info-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan - Informasi Destinasi dan Aksi -->
                    <div class="col-lg-6">
                        <div class="row g-4">
                            <!-- Informasi Destinasi -->
                            <div class="col-12">
                                <div class="card detail-card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-map me-2"></i> Informasi Destinasi</h5>
                                    </div>
                                    <div class="card-body destination-info">
                                        <div class="destination-content">
                                            <img src="<?php echo htmlspecialchars($booking['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($booking['destination_name']); ?>"
                                                class="img-fluid destination-img mb-3"
                                                onerror="this.src='https://via.placeholder.com/800x500?text=Gambar+Tidak+Tersedia'">
                                            <h4 class="mb-2"><?php echo htmlspecialchars($booking['destination_name']); ?></h4>
                                            <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill me-1"></i> <?php echo htmlspecialchars($booking['location']); ?></p>
                                            <p class="mb-3"><?php echo nl2br(htmlspecialchars($booking['description'])); ?></p>

                                            <!-- Google Maps Container -->
                                            <div id="map-container">
                                                <div id="map"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aksi -->
                            <div class="col-12">
                                <div class="card detail-card h-100">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i> Aksi</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <?php if ($booking['status'] == 'pending'): ?>
                                                <a href="confirm.php?id=<?php echo $booking['id']; ?>" class="btn btn-success">
                                                    <i class="bi bi-check-circle me-1"></i> Konfirmasi Booking
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($booking['status'] != 'cancelled'): ?>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                                    <i class="bi bi-x-circle me-1"></i> Batalkan Booking
                                                </button>
                                            <?php endif; ?>

                                            <a href="edit.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil me-1"></i> Edit Booking
                                            </a>
                                            <button class="btn btn-primary" title="Ubah Status"
                                                onclick="showStatusModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                                <i class="bi bi-arrow-repeat"></i> Ubah Status
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">Konfirmasi Pembatalan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membatalkan booking ini?</p>
                    <p class="fw-bold">Booking #<?php echo htmlspecialchars($booking['id']); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="cancel.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger">Ya, Batalkan</a>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });

            // Handle image error
            $('.destination-img').on('error', function() {
                $(this).attr('src', 'https://via.placeholder.com/800x500?text=Gambar+Tidak+Tersedia');
            });

            // Close alert after 5 seconds (if any)
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);

            // Initialize Google Maps
            function initMap() {
                <?php if (!empty($booking['latitude']) && !empty($booking['longitude'])): ?>
                    const location = {
                        lat: <?php echo $booking['latitude']; ?>,
                        lng: <?php echo $booking['longitude']; ?>
                    };
                    const map = new google.maps.Map(document.getElementById("map"), {
                        zoom: 14,
                        center: location,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: true,
                    });
                    new google.maps.Marker({
                        position: location,
                        map: map,
                        title: "<?php echo htmlspecialchars($booking['destination_name']); ?>"
                    });
                <?php else: ?>
                    document.getElementById('map-container').innerHTML = '<div class="alert alert-warning text-center py-4">Peta tidak tersedia untuk lokasi ini</div>';
                <?php endif; ?>
            }

            // Show status modal
            function showStatusModal(bookingId, currentStatus) {
                $('#booking_id').val(bookingId);
                $('#statusSelect').val(currentStatus);
                $('#statusModal').modal('show');
            }

            // Handle status update via AJAX
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
                            badge.removeClass('bg-pending bg-confirmed bg-cancelled')
                                .addClass(`bg-${data.status}`)
                                .text(data.status_text);

                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            $('#statusModal').modal('hide');
                            // Optionally reload page to reflect changes
                            setTimeout(() => location.reload(), 2000);
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
        });
    </script>
</body>

</html>