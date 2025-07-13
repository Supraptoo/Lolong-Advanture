<?php
session_start();
require_once('config/database.php');

// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data pemesanan pengguna
try {
    $stmt = $pdo->prepare("
    SELECT id, order_id, ticket_type, visit_date, participants, total_price, payment_method, payment_status, status, created_at 
    FROM bookings 
    WHERE user_id = ? 
    ORDER BY created_at DESC
  ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error_message = "Gagal memuat data pemesanan: " . $e->getMessage();
}

// Fungsi untuk memeriksa status pembayaran dengan Midtrans (contoh)
function checkMidtransStatus($order_id)
{
    // Ganti dengan konfigurasi Midtrans Anda
    $server_key = 'YOUR_MIDTRANS_SERVER_KEY'; // Ganti dengan kunci server Midtrans Anda
    $url = "https://api.sandbox.midtrans.com/v2/$order_id/status"; // Gunakan URL produksi untuk live

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode($server_key . ':'),
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['transaction_status'] ?? 'unknown';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Status Pemesanan - Wisata Sungai Sengkarang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
    <style>
        :root {
            --primary: #2a7f62;
            --secondary: #f8f9fa;
            --accent: #ff7e33;
            --text: #333333;
            --text-light: #666666;
            --white: #FFFFFF;
            --border-color: #ddd;
            --success-color: #4caf50;
            --error-color: #f44336;
            --pending-color: #ff9800;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background-color: var(--secondary);
            line-height: 1.6;
            padding-bottom: 60px;
        }

        h1,
        h2,
        h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .section-title p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .table-container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
        }

        tr:hover {
            background: var(--secondary);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-success {
            background: var(--success-color);
            color: var(--white);
        }

        .status-pending {
            background: var(--pending-color);
            color: var(--white);
        }

        .status-failed {
            background: var(--error-color);
            color: var(--white);
        }

        .status-cancelled {
            background: var(--text-light);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #1f6b53;
        }

        .btn-danger {
            background: var(--error-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #d32f2f;
        }

        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-container input {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            width: 200px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .alert-error {
            background: var(--error-color);
            color: var(--white);
        }

        .alert-success {
            background: var(--success-color);
            color: var(--white);
        }

        .bottom-sidebar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: var(--white);
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
        }

        .sidebar-menu {
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            width: 100%;
        }

        .sidebar-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--text);
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .sidebar-item:hover {
            color: var(--primary);
        }

        .sidebar-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .section-title h2 {
                font-size: 2rem;
            }

            .filter-container {
                flex-direction: column;
            }

            .filter-container input {
                width: 100%;
            }

            th,
            td {
                font-size: 0.9rem;
                padding: 10px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .sidebar-item {
                font-size: 0.8rem;
            }

            .sidebar-item i {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .section-title h2 {
                font-size: 1.8rem;
            }

            .table-container {
                padding: 10px;
            }

            th,
            td {
                font-size: 0.8rem;
                padding: 8px;
            }

            .btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Bottom Sidebar -->
    <div class="bottom-sidebar">
        <div class="sidebar-menu">
            <a href="index.php" class="sidebar-item">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            <a href="status.php" class="sidebar-item">
                <i class="fas fa-clipboard-list"></i>
                <span>Status Pemesanan</span>
            </a>
            <a href="profile.php" class="sidebar-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
            <a href="logout.php" class="sidebar-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title" data-aos="fade-up">
            <h2>Status Pemesanan Anda</h2>
            <p>Lihat riwayat dan status pemesanan tiket wisata Sungai Sengkarang</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="filter-container" data-aos="fade-up" data-aos-delay="200">
            <input type="text" id="searchOrder" placeholder="Cari Nomor Pesanan...">
            <input type="date" id="filterDate">
        </div>

        <div class="table-container" data-aos="fade-up" data-aos-delay="300">
            <table id="bookingsTable" class="display">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Tanggal Kunjungan</th>
                        <th>Jenis Tiket</th>
                        <th>Jumlah</th>
                        <th>Total Harga</th>
                        <th>Status Pembayaran</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        // Cek status pembayaran dengan Midtrans
                        $payment_status = $booking['payment_status'];
                        if ($payment_status === 'pending') {
                            $midtrans_status = checkMidtransStatus($booking['order_id']);
                            if ($midtrans_status === 'settlement' || $midtrans_status === 'capture') {
                                $payment_status = 'success';
                                // Update status di database
                                $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'success' WHERE id = ?");
                                $stmt->execute([$booking['id']]);
                            } elseif ($midtrans_status === 'expire' || $midtrans_status === 'cancel') {
                                $payment_status = 'failed';
                                $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'failed', status = 'cancelled' WHERE id = ?");
                                $stmt->execute([$booking['id']]);
                            }
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['order_id']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($booking['visit_date'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['ticket_type']); ?></td>
                            <td><?php echo htmlspecialchars($booking['participants']); ?> orang</td>
                            <td>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $payment_status; ?>">
                                    <?php
                                    echo match ($payment_status) {
                                        'success' => 'Sukses',
                                        'pending' => 'Menunggu',
                                        'failed' => 'Gagal',
                                        default => 'Tidak Diketahui'
                                    };
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php
                                    echo match ($booking['status']) {
                                        'confirmed' => 'Terkonfirmasi',
                                        'pending' => 'Menunggu Konfirmasi',
                                        'cancelled' => 'Dibatalkan',
                                        default => 'Tidak Diketahui'
                                    };
                                    ?>
                            </td>
                            <td class="action-buttons">
                                <a href="#detail-<?php echo $booking['id']; ?>" class="btn btn-primary view-details"
                                    data-id="<?php echo $booking['id']; ?>">Detail</a>
                                <?php if ($payment_status === 'pending' && $booking['status'] === 'pending'): ?>
                                    <a href="#" class="btn btn-danger cancel-booking"
                                        data-id="<?php echo $booking['id']; ?>"
                                        data-order-id="<?php echo $booking['order_id']; ?>">Batalkan</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Initialize DataTable
        $(document).ready(function() {
            const table = $('#bookingsTable').DataTable({
                responsive: true,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ entri",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    paginate: {
                        previous: "Sebelumnya",
                        next: "Selanjutnya"
                    }
                },
                order: [
                    [0, 'desc']
                ]
            });

            // Filter by order ID
            $('#searchOrder').on('keyup', function() {
                table.column(1).search(this.value).draw();
            });

            // Filter by date
            $('#filterDate').on('change', function() {
                const date = this.value ? moment(this.value).format('DD/MM/YYYY') : '';
                table.column(1).search(date).draw();
            });

            // Handle cancel booking
            $('.cancel-booking').on('click', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('id');
                const orderId = $(this).data('order-id');

                if (confirm('Apakah Anda yakin ingin membatalkan pemesanan ini?')) {
                    $.ajax({
                        url: 'cancel_booking.php',
                        method: 'POST',
                        data: {
                            booking_id: bookingId,
                            order_id: orderId
                        },
                        success: function(response) {
                            const res = JSON.parse(response);
                            if (res.success) {
                                alert('Pemesanan berhasil dibatalkan.');
                                location.reload();
                            } else {
                                alert('Gagal membatalkan pemesanan: ' + res.message);
                            }
                        },
                        error: function() {
                            alert('Terjadi kesalahan saat membatalkan pemesanan.');
                        }
                    });
                }
            });

            // Handle view details (placeholder)
            $('.view-details').on('click', function(e) {
                e.preventDefault();
                const bookingId = $(this).data('id');
                alert('Menampilkan detail untuk pemesanan ID: ' + bookingId);
                // Tambahkan logika untuk menampilkan detail, misalnya modal
            });
        });
    </script>
</body>

</html>