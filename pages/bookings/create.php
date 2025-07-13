<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../../login.php');
    exit();
}

// Initialize variables
$errors = [];
$formData = [
    'destination_id' => '',
    'user_id' => '',
    'booking_date' => '',
    'participants' => 1,
    'status' => 'pending',
    'notes' => ''
];

// Get all destinations (removed status filter since column doesn't exist)
$destinations = $pdo->query("SELECT id, name, price FROM destinations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get active users
$users = $pdo->query("SELECT id, full_name, email FROM users WHERE is_active = 1 ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $formData['destination_id'] = filter_input(INPUT_POST, 'destination_id', FILTER_VALIDATE_INT);
    $formData['user_id'] = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $formData['booking_date'] = htmlspecialchars($_POST['booking_date'] ?? '');
    $formData['participants'] = filter_input(INPUT_POST, 'participants', FILTER_VALIDATE_INT);
    $formData['status'] = in_array($_POST['status'] ?? '', ['pending', 'confirmed', 'cancelled', 'completed'])
        ? $_POST['status']
        : 'pending';
    $formData['notes'] = htmlspecialchars($_POST['notes'] ?? '');

    // Validate inputs
    if (empty($formData['destination_id'])) {
        $errors['destination_id'] = 'Destinasi wajib dipilih';
    }
    if (empty($formData['user_id'])) {
        $errors['user_id'] = 'Pelanggan wajib dipilih';
    }
    if (empty($formData['booking_date'])) {
        $errors['booking_date'] = 'Tanggal booking wajib diisi';
    } elseif (strtotime($formData['booking_date']) < strtotime('today')) {
        $errors['booking_date'] = 'Tanggal booking tidak boleh di masa lalu';
    }
    if (empty($formData['participants']) || $formData['participants'] < 1) {
        $errors['participants'] = 'Jumlah peserta harus angka dan minimal 1';
    }

    // If no errors, proceed with saving
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Get destination price (removed status check)
            $stmt = $pdo->prepare("SELECT price FROM destinations WHERE id = ?");
            $stmt->execute([$formData['destination_id']]);
            $destination = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$destination) {
                throw new Exception("Destinasi tidak ditemukan");
            }

            $total_price = $destination['price'] * $formData['participants'];

            // Insert booking
            $stmt = $pdo->prepare("INSERT INTO bookings 
                                  (destination_id, user_id, booking_date, participants, total_price, status, notes, created_at) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $formData['destination_id'],
                $formData['user_id'],
                $formData['booking_date'],
                $formData['participants'],
                $total_price,
                $formData['status'],
                $formData['notes']
            ]);

            $pdo->commit();

            $_SESSION['success_message'] = 'Booking berhasil ditambahkan!';
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = 'Terjadi kesalahan database: ' . $e->getMessage();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['database'] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Booking - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --primary-light: #e8f1f0;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 280px;
            transition: all 0.3s;
            padding: 20px;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-header {
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .form-title {
            color: var(--secondary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .form-title i {
            margin-right: 12px;
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 20px;
            text-transform: capitalize;
            font-weight: 600;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .price-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            background-color: var(--primary-light);
            border-radius: 8px;
            border: 1px solid rgba(42, 157, 143, 0.3);
            display: inline-flex;
            align-items: center;
        }

        .price-display i {
            margin-right: 8px;
        }

        .select2-container--bootstrap-5 .select2-selection {
            height: auto;
            min-height: 42px;
            padding: 8px 12px;
            border-radius: 8px;
        }

        .customer-option {
            display: flex;
            align-items: center;
            padding: 6px 0;
        }

        .customer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .destination-card {
            display: flex;
            align-items: center;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .destination-card:hover {
            background-color: var(--primary-light);
        }

        .destination-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #eee;
        }

        .destination-info {
            flex-grow: 1;
        }

        .destination-name {
            font-weight: 600;
            margin-bottom: 2px;
            color: var(--secondary-color);
        }

        .destination-location {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .destination-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 8px;
        }

        .required-field::after {
            content: " *";
            color: #dc3545;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #22867a;
            border-color: #22867a;
        }

        .btn-outline-secondary {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(42, 157, 143, 0.25);
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 600;
            border: none;
            padding: 12px 20px;
            margin-right: 5px;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }

        .nav-tabs .nav-link:hover {
            border-color: transparent;
            color: var(--primary-color);
        }

        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .alert {
            border-radius: 10px;
        }

        .flatpickr-input {
            background-color: white;
        }

        .total-price-container {
            background-color: var(--primary-light);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border: 1px dashed var(--primary-color);
        }

        .total-price-label {
            font-size: 0.9rem;
            color: var(--secondary-color);
            font-weight: 600;
        }

        .total-price-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('../../includes/sidebar.php'); ?>

        <div id="content" class="main-content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-primary">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </span>
                        <a href="../logout.php" class="btn btn-outline-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <div class="container-fluid p-0">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h4 mb-1"><i class="bi bi-calendar-plus me-2"></i> Tambah Booking Baru</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="index.php">Manajemen Booking</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Tambah Booking</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Terjadi kesalahan!</h5>
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <div class="form-header mb-4">
                        <h3 class="form-title"><i class="bi bi-pencil-square"></i> Formulir Booking Baru</h3>
                        <p class="text-muted mb-0">Isi formulir berikut untuk membuat booking baru</p>
                    </div>

                    <form method="POST" id="bookingForm">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="destination_id" class="form-label required-field">Destinasi Wisata</label>
                                <select class="form-select select2 <?php echo isset($errors['destination_id']) ? 'is-invalid' : ''; ?>"
                                    id="destination_id" name="destination_id" required>
                                    <option value="">Pilih Destinasi Wisata</option>
                                    <?php foreach ($destinations as $destination): ?>
                                        <option value="<?php echo $destination['id']; ?>"
                                            data-price="<?php echo $destination['price']; ?>"
                                            <?php echo ($formData['destination_id'] == $destination['id'] ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($destination['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['destination_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo htmlspecialchars($errors['destination_id']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Pilih destinasi wisata yang ingin dipesan</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label required-field">Data Pelanggan</label>
                                <select class="form-select select2 <?php echo isset($errors['user_id']) ? 'is-invalid' : ''; ?>"
                                    id="user_id" name="user_id" required>
                                    <option value="">Pilih Pelanggan</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($formData['user_id'] == $user['id'] ? 'selected' : ''); ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['user_id'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo htmlspecialchars($errors['user_id']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Pilih pelanggan yang melakukan booking</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="booking_date" class="form-label required-field">Tanggal Booking</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                    <input type="text" class="form-control datepicker <?php echo isset($errors['booking_date']) ? 'is-invalid' : ''; ?>"
                                        id="booking_date" name="booking_date"
                                        value="<?php echo htmlspecialchars($formData['booking_date']); ?>" required>
                                </div>
                                <?php if (isset($errors['booking_date'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo htmlspecialchars($errors['booking_date']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Pilih tanggal untuk kegiatan wisata</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="participants" class="form-label required-field">Jumlah Peserta</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-people"></i></span>
                                    <input type="number" class="form-control <?php echo isset($errors['participants']) ? 'is-invalid' : ''; ?>"
                                        id="participants" name="participants"
                                        value="<?php echo htmlspecialchars($formData['participants']); ?>" min="1" required>
                                </div>
                                <?php if (isset($errors['participants'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo htmlspecialchars($errors['participants']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Masukkan jumlah peserta (minimal 1 orang)</small>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status Booking</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo ($formData['status'] == 'pending' ? 'selected' : ''); ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($formData['status'] == 'confirmed' ? 'selected' : ''); ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo ($formData['status'] == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                                    <option value="completed" <?php echo ($formData['status'] == 'completed' ? 'selected' : ''); ?>>Completed</option>
                                </select>
                                <small class="text-muted">Tentukan status booking</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control" id="notes" name="notes" rows="1"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                                <small class="text-muted">Masukkan catatan khusus jika ada</small>
                            </div>
                        </div>

                        <div class="total-price-container text-center mb-4">
                            <div class="total-price-label">Total Harga</div>
                            <div class="total-price-value" id="totalPriceDisplay">Rp 0</div>
                            <input type="hidden" id="total_price" name="total_price" value="">
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">
                                <i class="bi bi-eraser me-1"></i> Reset Form
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('full-width');
            });

            // Initialize date picker
            $('.datepicker').flatpickr({
                dateFormat: "Y-m-d",
                minDate: "today",
                allowInput: true,
                locale: "id"
            });

            // Initialize select2 with custom templates
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder'),
                allowClear: true,
                templateResult: formatDestinationOption,
                templateSelection: formatDestinationSelection
            });

            // Format options in dropdown
            function formatDestinationOption(option) {
                if (!option.id) return option.text;

                if (option.element && option.element.className === 'customer-option') {
                    return $(
                        '<div class="customer-option">' +
                        '<span class="customer-avatar me-2">' + option.text.charAt(0).toUpperCase() + '</span>' +
                        option.text +
                        '</div>'
                    );
                }

                // For destinations
                var $destination = $(option.element);
                var price = $destination.data('price');
                var name = option.text;
                var location = $destination.data('location');
                var image = $destination.data('image');

                if (!price) return option.text;

                var priceFormatted = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0
                }).format(price);

                var $container = $(
                    '<div class="destination-card">' +
                    (image ? '<img src="' + image + '" class="destination-image">' :
                        '<div class="destination-image bg-light d-flex align-items-center justify-content-center">' +
                        '<i class="bi bi-image text-muted"></i></div>') +
                    '<div class="destination-info">' +
                    '<div class="destination-name">' + name + '</div>' +
                    (location ? '<div class="destination-location"><i class="bi bi-geo-alt me-1"></i>' + location + '</div>' : '') +
                    '<div class="destination-price">' + priceFormatted + '</div>' +
                    '</div>' +
                    '</div>'
                );

                return $container;
            }

            // Format selected option
            function formatDestinationSelection(option) {
                if (!option.id) return option.text;
                return option.text;
            }

            // Calculate total price
            function calculateTotalPrice() {
                const destinationId = $('#destination_id').val();
                const participants = parseInt($('#participants').val()) || 0;

                if (destinationId && participants > 0) {
                    const price = parseFloat($('#destination_id option:selected').data('price'));
                    const totalPrice = price * participants;

                    const formattedPrice = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(totalPrice);

                    $('#totalPriceDisplay').html('<i class="bi bi-tag"></i> ' + formattedPrice);
                    $('#total_price').val(totalPrice);
                } else {
                    $('#totalPriceDisplay').html('<i class="bi bi-tag"></i> Rp 0');
                    $('#total_price').val('');
                }
            }

            // Recalculate when destination or participants change
            $('#destination_id, #participants').on('change keyup', calculateTotalPrice);

            // Initial calculation
            calculateTotalPrice();

            // Form submission handling
            $('#bookingForm').on('submit', function(e) {
                if ($('#total_price').val() === '') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Silakan pilih destinasi dan masukkan jumlah peserta yang valid',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#2a9d8f'
                    });
                }
            });

            // Add animation to form submission
            $('form').on('submit', function() {
                $('button[type="submit"]').html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Menyimpan...');
            });
        });
    </script>
</body>

</html>