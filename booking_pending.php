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

// Simpan status pembayaran
$_SESSION['booking_data']['payment_status'] = 'pending';

// Hitung waktu tersisa untuk pembayaran (24 jam dari waktu pemesanan)
$booking_time = strtotime($_SESSION['booking_data']['created_at'] ?? time());
$expiration_time = strtotime('+24 hours', $booking_time);
$time_left = $expiration_time - time();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Tertunda - Lolong Adventure</title>
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
            color: var(--pending-color);
            margin-bottom: 25px;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .status-message h2 {
            margin-bottom: 15px;
            color: var(--pending-color);
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

        .timer {
            font-size: 18px;
            color: var(--pending-color);
            font-weight: 600;
            margin-bottom: 20px;
        }

        .payment-instructions {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3e0;
            border-radius: 8px;
            border-left: 4px solid var(--pending-color);
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

            .timer {
                font-size: 16px;
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
            <i class="fas fa-clock"></i>
            <h2>Pembayaran Tertunda</h2>
            <p>Pembayaran Anda sedang menunggu konfirmasi. Silakan selesaikan pembayaran dalam waktu 24 jam untuk mengkonfirmasi pemesanan Anda. Instruksi pembayaran telah dikirim ke email <strong><?= htmlspecialchars($_SESSION['booking_data']['email']) ?></strong>.</p>
            <?php if ($time_left > 0): ?>
                <div class="timer" id="timer">Waktu Tersisa: <span id="time-left"><?= gmdate("H:i:s", $time_left) ?></span></div>
            <?php else: ?>
                <div class="timer" style="color: var(--error-color);">Waktu Pembayaran Sudah Habis</div>
            <?php endif; ?>
            <div class="payment-instructions">
                <p><strong>Instruksi:</strong></p>
                <ul>
                    <li>Cek email Anda untuk detail pembayaran</li>
                    <li>Lakukan pembayaran melalui metode yang dipilih</li>
                    <li>Pembayaran akan dikonfirmasi dalam 1-2 jam</li>
                    <li>Simpan bukti pembayaran Anda</li>
                </ul>
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
                    <div class="detail-value">Menunggu Pembayaran</div>
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

    <script>
        // Timer countdown
        function startTimer(duration, display) {
            let timer = duration,
                hours, minutes, seconds;
            const interval = setInterval(() => {
                hours = Math.floor(timer / 3600);
                minutes = Math.floor((timer % 3600) / 60);
                seconds = timer % 60;

                hours = hours < 10 ? "0" + hours : hours;
                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = `${hours}:${minutes}:${seconds}`;

                if (--timer < 0) {
                    clearInterval(interval);
                    display.parentElement.style.color = 'var(--error-color)';
                    display.textContent = '00:00:00';
                    alert('Waktu pembayaran telah habis. Pemesanan Anda akan dibatalkan.');
                    window.location.href = 'booking.php';
                }
            }, 1000);
        }

        // Mulai timer jika waktu tersisa
        const timeLeftElement = document.getElementById('time-left');
        if (timeLeftElement) {
            const timeLeft = <?= $time_left ?>;
            if (timeLeft > 0) {
                startTimer(timeLeft, timeLeftElement);
            }
        }
    </script>
</body>

</html>