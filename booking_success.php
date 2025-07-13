<?php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: landingpage.php');
    exit;
}

// Cek apakah order_id tersedia dan sesuai dengan sesi
if (!isset($_GET['order_id']) || !isset($_SESSION['booking_data']) || $_SESSION['booking_data']['order_id'] !== $_GET['order_id']) {
    header('Location: booking.php');
    exit;
}

// Simpan status pembayaran sebagai 'confirmed'
$_SESSION['booking_data']['payment_status'] = 'confirmed';

// Ambil data waktu saat ini
$current_time = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$current_datetime = $current_time->format('d F Y, H:i') . ' WIB';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Berhasil - Lolong Adventure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a7f62;
            --secondary-color: #f8f9fa;
            --accent-color: #ff7e33;
            --text-color: #333;
            --light-text: #666;
            --border-color: #ddd;
            --success-color: #4caf50;
            --pending-color: #ff9800;
            --error-color: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            color: var(--light-text);
            font-size: 16px;
        }

        .status-message {
            text-align: center;
            padding: 40px 20px;
        }

        .status-message i {
            font-size: 80px;
            color: var(--success-color);
            margin-bottom: 25px;
            animation: bounce 1.5s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .status-message h2 {
            margin-bottom: 15px;
            color: var(--success-color);
            font-size: 28px;
        }

        .status-message p {
            margin-bottom: 20px;
            color: var(--light-text);
            font-size: 16px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .confirmation-details {
            margin-top: 20px;
            padding: 15px;
            background-color: #e8f5e9;
            border-radius: 8px;
            border-left: 4px solid var(--success-color);
        }

        .booking-details {
            background-color: var(--secondary-color);
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
            text-align: left;
            border: 1px solid var(--border-color);
        }

        .booking-details h3 {
            margin-bottom: 20px;
            color: var(--primary-color);
            font-size: 22px;
            text-align: center;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }

        .detail-label {
            flex: 1;
            font-weight: 500;
            color: var(--text-color);
        }

        .detail-value {
            flex: 2;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(42, 127, 98, 0.3);
        }

        .btn-primary:hover {
            background-color: #1f6b53;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(42, 127, 98, 0.4);
        }

        .btn-secondary {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: rgba(42, 127, 98, 0.1);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px;
                padding: 15px;
            }

            .header h1 {
                font-size: 24px;
            }

            .status-message h2 {
                font-size: 24px;
            }

            .booking-details h3 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>e-Ticketing Lolong Adventure</h1>
            <p>Status Pemesanan Tiket Anda</p>
        </div>
        <div class="status-message">
            <i class="fas fa-check-circle"></i>
            <h2>Pemesanan Berhasil!</h2>
            <p>Pemesanan Anda dengan kode <strong><?= htmlspecialchars($_SESSION['booking_data']['order_id']) ?></strong> telah berhasil dikonfirmasi pada <?= $current_datetime ?>. Tiket telah dikirim ke email <strong><?= htmlspecialchars($_SESSION['booking_data']['email']) ?></strong>. Selamat menikmati petualangan Anda!</p>
            <div class="confirmation-details">
                <p><strong>Catatan:</strong> Mohon simpan kode booking dan bukti pembayaran untuk proses check-in. Hubungi kami jika ada pertanyaan.</p>
            </div>
            <div class="booking-details">
                <h3>Detail Pemesanan</h3>
                <div class="detail-row">
                    <div class="detail-label">Kode Booking:</div>
                    <div class="detail-value"><?= htmlspecialchars($_SESSION['booking_data']['order_id']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Nama:</div>
                    <div class="detail-value"><?= htmlspecialchars($_SESSION['booking_data']['name']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Jenis Tiket:</div>
                    <div class="detail-value"><?= ucfirst($_SESSION['booking_data']['ticket_type']) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Jumlah:</div>
                    <div class="detail-value"><?= $_SESSION['booking_data']['quantity'] ?> orang</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Tanggal Kunjungan:</div>
                    <div class="detail-value"><?= date('d F Y', strtotime($_SESSION['booking_data']['date'])) ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Total Pembayaran:</div>
                    <div class="detail-value">Rp<?= number_format($_SESSION['booking_data']['total_price'], 0, ',', '.') ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">Terkonfirmasi</div>
                </div>
            </div>
            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak
                </button>
                <button class="btn btn-primary" onclick="window.location.href='landingpage.php'">
                    <i class="fas fa-home"></i> Beranda
                </button>
            </div>
        </div>
    </div>
</body>

</html>