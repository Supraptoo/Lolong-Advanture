<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Hanya file JPG, PNG, atau GIF yang diizinkan']);
        exit();
    }

    if ($_FILES['avatar']['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'Ukuran file maksimal 2MB']);
        exit();
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../assets/images/profiles/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $_SESSION['username'] . '_' . time() . '.' . $extension;
    $upload_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE username = ?");
        if ($stmt->execute([$filename, $_SESSION['username']])) {
            // Update session
            $_SESSION['profile_photo'] = $filename;
            echo json_encode(['success' => true, 'filename' => $filename]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menyimpan ke database']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Gagal mengupload file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
