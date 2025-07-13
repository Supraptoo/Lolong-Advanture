<?php
session_start();
require_once('../../config/database.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../login.php');
    exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
    header('Location: bookings.php');
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking and payment details
$stmt = $pdo->prepare("SELECT b.*, d.name as destination_name, d.price as destination_price, 
                      p.payment_method, p.amount, p.status as payment_status
                      FROM bookings b
                      JOIN destinations d ON b.destination_id = d.id
                      LEFT JOIN payments p ON p.booking_id = b.id
                      WHERE b.id = ? AND b.user_id = ?
                      ORDER BY p.payment_date DESC LIMIT 1");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: bookings.php');
    exit();
}

// Set default payment method if not set
$payment_method = isset($booking['payment_method']) ? strtolower($booking['payment_method']) : '';

// Payment instructions based on method
$payment_instructions = [];

switch ($payment_method) {
    case 'bca':
        $payment_instructions = [
            'title' => 'BCA Virtual Account',
            'steps' => [
                'Masuk ke aplikasi m-BCA atau ATM BCA',
                'Pilih menu "Transfer"',
                'Pilih "Ke Rekening BCA Virtual Account"',
                'Masukkan nomor Virtual Account: <strong>' . generateVirtualAccount($booking_id, 'bca') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 5-10 menit',
            'logo' => 'assets/img/payments/bca.png'
        ];
        break;
    case 'bni':
        $payment_instructions = [
            'title' => 'BNI Virtual Account',
            'steps' => [
                'Masuk ke aplikasi BNI Mobile Banking atau ATM BNI',
                'Pilih menu "Transfer"',
                'Pilih "Virtual Account Billing"',
                'Masukkan nomor Virtual Account: <strong>' . generateVirtualAccount($booking_id, 'bni') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 5-10 menit',
            'logo' => 'assets/img/payments/bni.png'
        ];
        break;
    case 'bri':
        $payment_instructions = [
            'title' => 'BRI Virtual Account',
            'steps' => [
                'Masuk ke aplikasi BRI Mobile Banking atau ATM BRI',
                'Pilih menu "Pembayaran"',
                'Pilih "BRIVA"',
                'Masukkan nomor Virtual Account: <strong>' . generateVirtualAccount($booking_id, 'bri') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 5-10 menit',
            'logo' => 'assets/img/payments/bri.png'
        ];
        break;
    case 'mandiri':
        $payment_instructions = [
            'title' => 'Mandiri Virtual Account',
            'steps' => [
                'Masuk ke aplikasi Livin\' by Mandiri atau ATM Mandiri',
                'Pilih menu "Bayar"',
                'Pilih "Multi Payment"',
                'Masukkan kode perusahaan: <strong>88888</strong>',
                'Masukkan nomor Virtual Account: <strong>' . generateVirtualAccount($booking_id, 'mandiri') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 5-10 menit',
            'logo' => 'assets/img/payments/mandiri.png'
        ];
        break;
    case 'gopay':
        $payment_instructions = [
            'title' => 'GoPay',
            'steps' => [
                'Buka aplikasi Gojek',
                'Pilih menu "Bayar"',
                'Scan QR code berikut:',
                '<img src="' . generateQRCode('gopay_' . $booking_id . '_' . $booking['amount']) . '" alt="QR Code GoPay" style="max-width: 200px; display: block; margin: 10px 0;">',
                'Atau masukkan kode pembayaran: <strong>' . generatePaymentCode($booking_id, 'gopay') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 1-2 menit',
            'logo' => 'assets/img/payments/gopay.png'
        ];
        break;
    case 'ovo':
        $payment_instructions = [
            'title' => 'OVO',
            'steps' => [
                'Buka aplikasi OVO',
                'Pilih menu "Transfer"',
                'Pilih "Lainnya"',
                'Masukkan kode pembayaran: <strong>' . generatePaymentCode($booking_id, 'ovo') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 1-2 menit',
            'logo' => 'assets/img/payments/ovo.png'
        ];
        break;
    case 'dana':
        $payment_instructions = [
            'title' => 'DANA',
            'steps' => [
                'Buka aplikasi DANA',
                'Pilih menu "Bayar"',
                'Scan QR code berikut:',
                '<img src="' . generateQRCode('dana_' . $booking_id . '_' . $booking['amount']) . '" alt="QR Code DANA" style="max-width: 200px; display: block; margin: 10px 0;">',
                'Atau masukkan kode pembayaran: <strong>' . generatePaymentCode($booking_id, 'dana') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 1-2 menit',
            'logo' => 'assets/img/payments/dana.png'
        ];
        break;
    case 'shopee':
        $payment_instructions = [
            'title' => 'ShopeePay',
            'steps' => [
                'Buka aplikasi Shopee',
                'Pilih menu "ShopeePay"',
                'Scan QR code berikut:',
                '<img src="' . generateQRCode('shopee_' . $booking_id . '_' . $booking['amount']) . '" alt="QR Code ShopeePay" style="max-width: 200px; display: block; margin: 10px 0;">',
                'Atau masukkan kode pembayaran: <strong>' . generatePaymentCode($booking_id, 'shopee') . '</strong>',
                'Masukkan jumlah pembayaran: <strong>Rp ' . number_format($booking['amount'], 0, ',', '.') . '</strong>',
                'Konfirmasi pembayaran'
            ],
            'note' => 'Pembayaran akan diproses otomatis dalam waktu 1-2 menit',
            'logo' => 'assets/img/payments/shopeepay.png'
        ];
        break;
    default:
        $payment_instructions = [
            'title' => 'Instruksi Pembayaran',
            'steps' => [
                'Silakan selesaikan pembayaran sesuai metode yang dipilih',
                'Pembayaran Anda sedang diproses'
            ],
            'note' => 'Status pembayaran akan diperbarui secara otomatis',
            'logo' => 'assets/img/payments/default.png'
        ];
}

// Get bookings count for sidebar
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$bookings_count = $stmt->fetchColumn();

// Helper functions
function generateVirtualAccount($booking_id, $bank)
{
    $prefix = '';
    switch ($bank) {
        case 'bca':
            $prefix = '8108';
            break;
        case 'bni':
            $prefix = '8800';
            break;
        case 'bri':
            $prefix = '8000';
            break;
        case 'mandiri':
            $prefix = '8888';
            break;
        default:
            $prefix = '8000';
    }
    return $prefix . str_pad($booking_id, 12, '0', STR_PAD_LEFT);
}

function generatePaymentCode($booking_id, $method)
{
    $prefix = '';
    switch ($method) {
        case 'gopay':
            $prefix = 'GOPAY';
            break;
        case 'ovo':
            $prefix = 'OVO';
            break;
        case 'dana':
            $prefix = 'DANA';
            break;
        case 'shopee':
            $prefix = 'SHOPEE';
            break;
        default:
            $prefix = 'PAY';
    }
    return $prefix . str_pad($booking_id, 8, '0', STR_PAD_LEFT);
}

function generateQRCode($data)
{
    // In a real implementation, you would generate a QR code image
    // This is a placeholder that would use a QR code generation library
    return 'assets/img/qr-placeholder.png?data=' . urlencode($data);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruksi Pembayaran - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #4cc9f0;
            --sidebar-width: 280px;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #2ecc71;
        }

        body {
            background-color: var(--light-color);
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
        }

        /* Payment Instructions */
        .payment-instruction-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            padding: 25px;
            margin-bottom: 20px;
        }

        .payment-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .payment-logo {
            width: 60px;
            height: 40px;
            object-fit: contain;
            margin-right: 20px;
        }

        .payment-steps {
            list-style-type: none;
            padding-left: 0;
        }

        .payment-steps li {
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
        }

        .payment-steps li:last-child {
            border-bottom: none;
        }

        .step-number {
            background-color: var(--primary-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .payment-note {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 0 5px 5px 0;
            margin-top: 20px;
        }

        .countdown-timer {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary-color);
            text-align: center;
            margin: 20px 0;
        }

        .booking-summary {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .booking-img {
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            width: 100%;
        }

        /* Responsive Adjustments */
        @media (max-width: 767.98px) {
            .payment-header {
                flex-direction: column;
                text-align: center;
            }

            .payment-logo {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Overlay for mobile -->
        <div class="overlay"></div>

        <!-- Main Content -->
        <div id="content" class="main-content">
            <!-- Payment Content -->
            <div class="container-fluid p-3 p-md-4">
                <!-- Greeting Section -->
                <div class="greeting-section mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="bi bi-credit-card me-2"></i> Instruksi Pembayaran</h3>
                            <p class="mb-0">Selesaikan pembayaran Anda sesuai dengan instruksi berikut</p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="d-inline-block bg-white rounded-pill px-3 py-2">
                                <i class="bi bi-calendar-check text-primary-custom me-2"></i>
                                <span class="fw-bold"><?php echo date('l, d F Y'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Booking Summary -->
                    <div class="col-lg-5 mb-4">
                        <div class="booking-summary">
                            <h4 class="mb-4"><i class="bi bi-ticket-detailed me-2"></i> Ringkasan Pemesanan</h4>

                            <div class="mb-4">
                                <img src="<?php echo isset($booking['image_url']) ? $booking['image_url'] : 'assets/img/default-destination.jpg'; ?>"
                                    class="booking-img mb-3"
                                    alt="<?php echo isset($booking['destination_name']) ? $booking['destination_name'] : 'Destinasi'; ?>">
                                <h5><?php echo isset($booking['destination_name']) ? $booking['destination_name'] : ''; ?></h5>
                                <p class="text-muted">
                                    <i class="bi bi-calendar"></i> <?php echo isset($booking['booking_date']) ? date('d M Y', strtotime($booking['booking_date'])) : ''; ?>
                                </p>
                                <p>
                                    <i class="bi bi-people"></i> <?php echo isset($booking['participants']) ? $booking['participants'] : ''; ?> Peserta
                                </p>
                            </div>

                            <div class="price-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Metode Pembayaran:</span>
                                    <span class="fw-bold"><?php echo $payment_instructions['title']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Status Pembayaran:</span>
                                    <span class="badge bg-<?php echo (isset($booking['payment_status']) && $booking['payment_status'] === 'paid') ? 'success' : 'warning'; ?>">
                                        <?php echo isset($booking['payment_status']) ? ucfirst($booking['payment_status']) : 'Pending'; ?>
                                    </span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold">Total Pembayaran:</span>
                                    <span class="fw-bold text-primary">
                                        Rp <?php echo isset($booking['amount']) ? number_format($booking['amount'], 0, ',', '.') : '0'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Penting:</strong> Simpan bukti pembayaran Anda untuk referensi
                        </div>
                    </div>

                    <!-- Payment Instructions -->
                    <div class="col-lg-7">
                        <div class="payment-instruction-card">
                            <div class="payment-header">
                                <img src="<?php echo $payment_instructions['logo']; ?>" alt="<?php echo $payment_instructions['title']; ?>" class="payment-logo">
                                <div>
                                    <h4><?php echo $payment_instructions['title']; ?></h4>
                                    <p class="text-muted mb-0">Ikuti langkah-langkah berikut untuk menyelesaikan pembayaran</p>
                                </div>
                            </div>

                            <div class="countdown-timer">
                                <i class="bi bi-clock me-2"></i>
                                Selesaikan pembayaran dalam: <span id="countdown">30:00</span>
                            </div>

                            <ol class="payment-steps">
                                <?php foreach ($payment_instructions['steps'] as $index => $step): ?>
                                    <li>
                                        <span class="step-number"><?php echo $index + 1; ?></span>
                                        <div><?php echo $step; ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>

                            <div class="payment-note">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo $payment_instructions['note']; ?>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                            <a href="bookings.php" class="btn btn-outline-secondary me-md-2">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pemesanan
                            </a>
                            <button class="btn btn-primary-custom" id="checkPaymentBtn">
                                <i class="bi bi-arrow-repeat me-1"></i> Cek Status Pembayaran
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Success Modal -->
    <div class="modal fade" id="paymentSuccessModal" tabindex="-1" aria-labelledby="paymentSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="paymentSuccessModalLabel"><i class="bi bi-check-circle me-2"></i> Pembayaran Berhasil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Pembayaran Anda Berhasil!</h4>
                    <p>Tiket Anda telah diproses dan akan dikirimkan ke email Anda.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <a href="booking_detail.php?id=<?php echo $booking_id; ?>" class="btn btn-success">
                        <i class="bi bi-ticket-detailed me-1"></i> Lihat Detail Pemesanan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Countdown timer (30 minutes)
            let minutes = 30;
            let seconds = 0;

            function updateCountdown() {
                const countdownElement = $('#countdown');
                countdownElement.text(`${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`);

                if (seconds === 0) {
                    if (minutes === 0) {
                        // Time's up
                        clearInterval(countdownInterval);
                        countdownElement.text('Waktu habis');
                        countdownElement.addClass('text-danger');
                        return;
                    }
                    minutes--;
                    seconds = 59;
                } else {
                    seconds--;
                }
            }

            // Update countdown every second
            const countdownInterval = setInterval(updateCountdown, 1000);
            updateCountdown();

            // Check payment status
            $('#checkPaymentBtn').click(function() {
                checkPaymentStatus();
            });

            // Auto-check payment status every 30 seconds
            setInterval(checkPaymentStatus, 30000);

            function checkPaymentStatus() {
                $.ajax({
                    url: 'check_payment_status.php',
                    method: 'POST',
                    data: {
                        booking_id: <?php echo $booking_id; ?>
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'paid') {
                            clearInterval(countdownInterval);
                            $('#countdown').text('Pembayaran berhasil').removeClass('text-danger').addClass('text-success');

                            // Show success modal
                            const modal = new bootstrap.Modal(document.getElementById('paymentSuccessModal'));
                            modal.show();

                            // Update status badge
                            $('.badge').removeClass('bg-warning').addClass('bg-success').text('Paid');
                        } else {
                            // Show alert if still pending
                            alert('Pembayaran Anda masih dalam proses. Silakan coba lagi nanti.');
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat memeriksa status pembayaran.');
                    }
                });
            }
        });
    </script>
</body>

</html>