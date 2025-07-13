<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $event_date = $_POST['event_date'];
    $max_participants = $_POST['max_participants'];

    // Handle image upload
    $image_url = 'default.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/images/events/';
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('event_') . '.' . $fileExt;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $image_url = $filename;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO events (name, location, description, duration, price, image_url, is_featured, event_date, max_participants, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $location, $description, $duration, $price, $image_url, $is_featured, $event_date, $max_participants]);

        $_SESSION['success'] = 'Event berhasil ditambahkan!';
        header('Location: events.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Gagal menambahkan event: ' . $e->getMessage();
        header('Location: add_event.php');
        exit();
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

        .preview-image {
            max-width: 300px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            margin-top: 10px;
            display: none;
        }

        .required-field::after {
            content: " *";
            color: red;
        }

        .form-label {
            font-weight: 500;
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

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="bi bi-calendar-event me-2"></i> Tambah Event Terbaru</h2>
                    <div>
                        <a href="events.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Form Tambah Event</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_event.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label required-field">Nama Event</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="location" class="form-label required-field">Lokasi</label>
                                        <input type="text" class="form-control" id="location" name="location" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="event_date" class="form-label required-field">Tanggal Event</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="duration" class="form-label required-field">Durasi (hari)</label>
                                        <input type="number" class="form-control" id="duration" name="duration" min="1" value="1" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label required-field">Harga</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" class="form-control" id="price" name="price" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max_participants" class="form-label">Maksimal Peserta</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" min="1">
                                    </div>

                                    <div class="mb-3">
                                        <label for="image" class="form-label">Gambar Event</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <img id="imagePreview" class="preview-image" src="#" alt="Preview Gambar">
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                                        <label class="form-check-label" for="is_featured">Featured Event</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label required-field">Deskripsi Event</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i>Simpan Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Sidebar toggle
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });
    </script>
</body>

</html>