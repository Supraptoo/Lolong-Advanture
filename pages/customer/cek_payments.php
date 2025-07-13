<?php
session_start();
require_once('../../config/database.php');

if (!isset($_SESSION['username']) || !isset($_POST['booking_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$booking_id = $_POST['booking_id'];
$user_id = $_SESSION['user_id'];

// Check payment status
$stmt = $pdo->prepare("SELECT p.status 
                      FROM payments p
                      JOIN bookings b ON p.booking_id = b.id
                      WHERE p.booking_id = ? AND b.user_id = ?
                      ORDER BY p.payment_date DESC LIMIT 1");
$stmt->execute([$booking_id, $user_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if ($payment) {
    echo json_encode(['status' => $payment['status']]);
} else {
    echo json_encode(['status' => 'pending']);
}
