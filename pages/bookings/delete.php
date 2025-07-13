<?php
session_start();
require_once('../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "ID Booking tidak valid";
    header('Location: index.php');
    exit();
}

$booking_id = $_GET['id'];

// Cek apakah booking ada
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error_message'] = "Booking tidak ditemukan";
    header('Location: index.php');
    exit();
}

// Hapus booking
try {
    $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);

    $_SESSION['success_message'] = "Booking berhasil dihapus";
    header('Location: index.php');
    exit();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Gagal menghapus booking: " . $e->getMessage();
    header('Location: index.php');
    exit();
}
