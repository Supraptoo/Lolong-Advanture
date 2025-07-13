<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username'])) {
    header('Location: ../../login.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing destination data
$destination = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$destination) {
    $_SESSION['error'] = 'Destinasi tidak ditemukan';
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Sanitize and validate inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
    $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
    $duration = trim(filter_input(INPUT_POST, 'duration', FILTER_SANITIZE_STRING));
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $difficulty = filter_input(INPUT_POST, 'difficulty', FILTER_SANITIZE_STRING);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $current_image = $destination['image_url'];

    // Validate inputs
    if (empty($name)) $errors[] = "Nama destinasi tidak boleh kosong";
    if (empty($location)) $errors[] = "Lokasi tidak boleh kosong";
    if (empty($description)) $errors[] = "Deskripsi tidak boleh kosong";
    if (empty($duration)) $errors[] = "Durasi tidak boleh kosong";
    if ($price === false || $price < 0) $errors[] = "Harga tidak valid";
    if (!in_array($difficulty, ['easy', 'medium', 'hard'])) $errors[] = "Tingkat kesulitan tidak valid";

    // Handle image upload if new image is provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/images/destinations/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = "Tipe file tidak diizinkan. Gunakan JPG, PNG, atau WEBP";
        }
        if ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Ukuran file terlalu besar. Maksimal 2MB";
        }

        if (empty($errors)) {
            $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $image_url = uniqid('dest_') . '.' . $file_ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_url)) {
                // Delete old image if it's not the default
                if ($current_image !== 'default.jpg' && file_exists($upload_dir . $current_image)) {
                    unlink($upload_dir . $current_image);
                }
                $current_image = $image_url;
            } else {
                $errors[] = "Gagal mengupload gambar";
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE destinations SET 
                                  name = ?, 
                                  location = ?, 
                                  description = ?, 
                                  duration = ?, 
                                  price = ?, 
                                  difficulty = ?, 
                                  image_url = ?, 
                                  is_featured = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([
                $name,
                $location,
                $description,
                $duration,
                $price,
                $difficulty,
                $current_image,
                $is_featured,
                $id
            ]);

            $_SESSION['success'] = 'Destinasi berhasil diperbarui';
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Gagal menyimpan data: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST;
        header("Location: edit.php?id=$id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Destinasi - Lolong Adventure</title>
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
        }

        .form-control,
        .form-select {
            border-radius: 6px;
        }

        .img-thumbnail {
            max-height: 200px;
            object-fit: cover;
        }

        .current-image {
            max-width: 200px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
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
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm">
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
                    <h2><i class="bi bi-pencil-square me-2"></i> Edit Destinasi</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="destinationForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nama Destinasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= isset($_SESSION['form_data']['name']) ? htmlspecialchars($_SESSION['form_data']['name']) : htmlspecialchars($destination['name']) ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Lokasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="location" name="location"
                                        value="<?= isset($_SESSION['form_data']['location']) ? htmlspecialchars($_SESSION['form_data']['location']) : htmlspecialchars($destination['location']) ?>"
                                        required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?= isset($_SESSION['form_data']['description']) ? htmlspecialchars($_SESSION['form_data']['description']) : htmlspecialchars($destination['description']) ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="duration" class="form-label">Durasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="duration" name="duration"
                                        value="<?= isset($_SESSION['form_data']['duration']) ? htmlspecialchars($_SESSION['form_data']['duration']) : htmlspecialchars($destination['duration']) ?>"
                                        placeholder="Contoh: 3 Hari 2 Malam" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="price" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?= isset($_SESSION['form_data']['price']) ? htmlspecialchars($_SESSION['form_data']['price']) : htmlspecialchars($destination['price']) ?>"
                                        min="0" step="1000" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="difficulty" class="form-label">Tingkat Kesulitan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="difficulty" name="difficulty" required>
                                        <option value="easy" <?= ((isset($_SESSION['form_data']['difficulty']) && $_SESSION['form_data']['difficulty'] === 'easy') || $destination['difficulty'] === 'easy') ? 'selected' : '' ?>>Mudah</option>
                                        <option value="medium" <?= ((isset($_SESSION['form_data']['difficulty']) && $_SESSION['form_data']['difficulty'] === 'medium') || $destination['difficulty'] === 'medium') ? 'selected' : '' ?>>Sedang</option>
                                        <option value="hard" <?= ((isset($_SESSION['form_data']['difficulty']) && $_SESSION['form_data']['difficulty'] === 'hard') || $destination['difficulty'] === 'hard') ? 'selected' : '' ?>>Sulit</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Featured</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured"
                                            <?= ((isset($_SESSION['form_data']['is_featured'])) || $destination['is_featured'] == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_featured">Tampilkan sebagai featured</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label">Gambar Destinasi</label>
                                <div class="mb-2">
                                    <p class="mb-1">Gambar saat ini:</p>
                                    <img src="../../assets/images/destinations/<?= htmlspecialchars($destination['image_url']) ?>"
                                        alt="Current Image"
                                        class="current-image mb-2">
                                </div>
                                <input class="form-control" type="file" id="image" name="image" accept="image/jpeg, image/png, image/jpg, image/webp">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar. Format: JPG, PNG, WEBP. Maksimal 2MB</small>
                                <div id="imagePreview" class="mt-2" style="display:none;">
                                    <p class="mb-1">Preview gambar baru:</p>
                                    <img id="previewImage" src="#" alt="Preview" class="img-thumbnail">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-lg me-1"></i> Reset
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

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            const file = e.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.style.display = 'block';
                }

                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Form submission loading state
        document.getElementById('destinationForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass me-1"></i> Menyimpan...';
        });

        // Sidebar toggle
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('active');
        });
    </script>
</body>

</html>
<?php
// Clear form data from session
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>