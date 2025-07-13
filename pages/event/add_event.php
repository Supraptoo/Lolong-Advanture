<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $event_date = filter_input(INPUT_POST, 'event_date', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validate inputs
    if (empty($title) || empty($description) || empty($event_date) || empty($location) || $max_participants === false || $price === false) {
        $error = 'Semua field wajib diisi dengan data yang valid!';
    } elseif (strtotime($event_date) < strtotime('today')) {
        $error = 'Tanggal event tidak boleh lebih awal dari hari ini!';
    } elseif ($max_participants <= 0) {
        $error = 'Jumlah peserta maksimal harus lebih dari 0!';
    } elseif ($price < 0) {
        $error = 'Harga tidak boleh negatif!';
    } else {
        try {
            // Handle file upload
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/images/events/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $file_name = uniqid('event_', true) . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                        $image_url = $file_name;
                    } else {
                        throw new Exception('Gagal mengupload gambar. Silakan coba lagi.');
                    }
                } else {
                    throw new Exception('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.');
                }
            }

            // Insert event into database
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, max_participants, price, image_url, is_active) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt->execute([$title, $description, $event_date, $location, $max_participants, $price, $image_url, $is_active])) {
                $_SESSION['success'] = 'Event berhasil ditambahkan!';
                header('Location: index.php');
                exit();
            } else {
                throw new Exception('Gagal menambahkan event. Silakan coba lagi.');
            }
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            $error = 'Terjadi kesalahan database. Silakan coba lagi.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Event - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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

        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .preview-image {
            max-width: 300px;
            max-height: 200px;
            display: block;
            margin-top: 10px;
            border-radius: 4px;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: #21867a;
            color: white;
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
                <div class="row">
                    <div class="col-12">
                        <div class="card form-container">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-calendar-plus me-2"></i> Tambah Event Baru</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo $error; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="title" class="form-label required-field">Judul Event</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="event_date" class="form-label required-field">Tanggal Event</label>
                                            <input type="date" class="form-control" id="event_date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="location" class="form-label required-field">Lokasi</label>
                                            <input type="text" class="form-control" id="location" name="location" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="max_participants" class="form-label required-field">Jumlah Peserta Maksimal</label>
                                            <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="price" class="form-label required-field">Harga (Rp)</label>
                                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="description" class="form-label required-field">Deskripsi Event</label>
                                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="image" class="form-label">Gambar Event</label>
                                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF (Maks. 2MB)</small>
                                            <div id="imagePreview" class="mt-2"></div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                                <label class="form-check-label" for="is_active">Event Aktif</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mt-4">
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="bi bi-arrow-left me-1"></i> Kembali
                                        </a>
                                        <button type="submit" class="btn btn-submit">
                                            <i class="bi bi-save me-1"></i> Simpan Event
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarCollapse').click(function() {
                $('#sidebar').toggleClass('active');
                $('.main-content').toggleClass('active');
            });

            // Image preview
            $('#image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#imagePreview').html(`<img src="${e.target.result}" class="preview-image" alt="Preview">`);
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#imagePreview').html('');
                }
            });

            // Set minimum date for event date
            const today = new Date().toISOString().split('T')[0];
            $('#event_date').attr('min', today);
        });
    </script>
</body>

</html>