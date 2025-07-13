<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Check user authentication and role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../../landingpage.php');
    exit;
}

// Initialize variables
$booking_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : null;
$booking = null;
$destinations = [];
$error_message = '';
$success_message = '';

// Fetch booking details
if ($booking_id) {
    try {
        $stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, u.full_name as customer_name 
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
} else {
    $_SESSION['error_message'] = 'ID Booking tidak valid';
    header('Location: index.php');
    exit();
}

// Fetch destinations for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM destinations WHERE status = 'active'");
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Gagal mengambil data destinasi: ' . htmlspecialchars($e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destination_id = isset($_POST['destination_id']) ? filter_var($_POST['destination_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $booking_date = isset($_POST['booking_date']) ? trim($_POST['booking_date']) : '';
    $participants = isset($_POST['participants']) ? filter_var($_POST['participants'], FILTER_SANITIZE_NUMBER_INT) : null;
    $total_price = isset($_POST['total_price']) ? filter_var($_POST['total_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

    if (empty($destination_id) || empty($booking_date) || empty($participants) || empty($total_price) || empty($status)) {
        $error_message = 'Semua field wajib diisi kecuali catatan';
    } elseif (!in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'])) {
        $error_message = 'Status tidak valid';
    } elseif ($participants <= 0) {
        $error_message = 'Jumlah peserta harus lebih dari 0';
    } elseif ($total_price <= 0) {
        $error_message = 'Total harga harus lebih dari 0';
    } elseif (!strtotime($booking_date)) {
        $error_message = 'Format tanggal tidak valid';
    } else {
        try {
            $pdo->beginTransaction();
            $formatted_date = date('Y-m-d', strtotime($booking_date));

            $stmt = $pdo->prepare("UPDATE bookings SET 
                                  destination_id = ?, 
                                  booking_date = ?, 
                                  participants = ?, 
                                  total_price = ?, 
                                  status = ?, 
                                  notes = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $success = $stmt->execute([
                $destination_id,
                $formatted_date,
                $participants,
                $total_price,
                $status,
                $notes,
                $booking_id
            ]);

            if ($success) {
                $pdo->commit();
                $_SESSION['success_message'] = 'Booking berhasil diperbarui';
                header('Location: index.php');
                exit();
            } else {
                $pdo->rollBack();
                $error_message = 'Gagal memperbarui booking';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Gagal memperbarui booking: ' . htmlspecialchars($e->getMessage());
            error_log('Error updating booking: ' . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
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

        .card-header-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px 8px 0 0;
        }

        .form-label span.text-danger {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .main-content {
                width: 100%;
                margin-left: 0;
                padding: 10px;
            }

            .row>* {
                margin-bottom: 10px;
            }

            .status-badge {
                display: block;
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

            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-pencil-square me-2"></i>Edit Booking</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Form Edit Booking</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="customer_name" class="form-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customer_name"
                                        value="<?php echo htmlspecialchars($booking['customer_name']); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="destination_id" class="form-label">Destinasi Wisata <span class="text-danger">*</span></label>
                                    <select class="form-select" id="destination_id" name="destination_id" required>
                                        <option value="">Pilih Destinasi</option>
                                        <?php foreach ($destinations as $destination): ?>
                                            <option value="<?php echo $destination['id']; ?>"
                                                <?php echo $destination['id'] == $booking['destination_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($destination['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="booking_date" class="form-label">Tanggal Booking <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control datepicker" id="booking_date" name="booking_date"
                                        value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($booking['booking_date']))); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="participants" class="form-label">Jumlah Peserta <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="participants" name="participants"
                                        value="<?php echo htmlspecialchars($booking['participants']); ?>" min="1" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="total_price" class="form-label">Total Harga (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" id="total_price" name="total_price"
                                        value="<?php echo htmlspecialchars($booking['total_price']); ?>" min="0" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="notes" class="form-label">Catatan</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo htmlspecialchars($booking['notes']); ?></textarea>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize date picker
            $('.datepicker').flatpickr({
                dateFormat: 'Y-m-d',
                allowInput: true,
                minDate: 'today',
                disableMobile: true
            });

            // Sidebar toggle with localStorage
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
                localStorage.setItem('sidebarState', $('#sidebar').hasClass('active') ? 'closed' : 'open');
            });

            // Close alert after 5 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        });
    </script>
</body>

</html>