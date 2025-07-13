<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $destination_id = $_GET['id'];

    try {
        $pdo->beginTransaction();

        // 1. Dapatkan semua booking yang terkait dengan destinasi ini
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE destination_id = ?");
        $stmt->execute([$destination_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($bookings)) {
            // 2. Hapus semua pembayaran yang terkait dengan booking tersebut
            $placeholders = rtrim(str_repeat('?,', count($bookings)), ',');
            $pdo->prepare("DELETE FROM payments WHERE booking_id IN ($placeholders)")->execute($bookings);

            // 3. Hapus semua booking yang terkait
            $pdo->prepare("DELETE FROM bookings WHERE destination_id = ?")->execute([$destination_id]);
        }

        // 4. Hapus destinasi
        $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
        $stmt->execute([$destination_id]);

        $pdo->commit();

        $_SESSION['success'] = "Destinasi berhasil dihapus beserta semua data terkait.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal menghapus destinasi: " . $e->getMessage();
    }

    header('Location: index.php');
    exit();
}
