<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Lolong Adventure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2a9d8f;
            --secondary-color: #264653;
            --accent-color: #e9c46a;
            --sidebar-width: 250px;
        }

        body.dashboard-body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        #sidebar {
            min-width: var(--sidebar-width);
            max-width: var(--sidebar-width);
            background: var(--secondary-color);
            color: white;
            transition: all 0.3s;
            min-height: 100vh;
        }

        #sidebar.active {
            margin-left: -250px;
        }

        #content {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 20px;
            background: var(--primary-color);
        }

        .sidebar-header h3 {
            color: white;
            margin-bottom: 0;
        }

        #sidebar ul.components {
            padding: 20px 0;
        }

        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1em;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            border-left: 3px solid transparent;
        }

        #sidebar ul li a:hover {
            color: white;
            background: rgba(0, 0, 0, 0.2);
            border-left: 3px solid var(--accent-color);
        }

        #sidebar ul li.active>a {
            color: white;
            background: rgba(0, 0, 0, 0.2);
            border-left: 3px solid var(--accent-color);
        }

        #sidebar ul li a i {
            margin-right: 10px;
        }

        .navbar {
            padding: 15px 20px;
            background: white !important;
            border-bottom: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        #sidebarCollapse {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        #sidebarCollapse:hover {
            background: #21867a;
        }

        .navbar-text {
            font-weight: 500;
        }

        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }

            #sidebar.active {
                margin-left: 0;
            }

            #content {
                width: 100%;
            }
        }
    </style>
</head>

<body class="dashboard-body">
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3><i class="bi bi-compass me-2"></i> Lolong Adventure</h3>
            </div>

            <ul class="list-unstyled components">
                <li class="active">
                    <a href="../pages/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li>
                    <a href="../pages/destinations/index.php"><i class="bi bi-map"></i> Destinasi Wisata</a>
                </li>
                <li>
                    <a href="../pages/bookings/index.php"><i class="bi bi-calendar-check"></i> Pemesanan</a>
                </li>
                <li>
                    <a href="testimonials.php"><i class="bi bi-chat-square-quote"></i> Testimoni</a>
                </li>
                <li>
                    <a href="../pages/contacs/index.php"><i class="bi bi-envelope"></i> Pesan</a>
                </li>
                <li>
                    <a href="../pages/Kelola_user/index.php"><i class="bi bi-people"></i> Pengguna</a>
                </li>
                <li>
                    <a href="settings.php"><i class="bi bi-gear"></i> Pengaturan</a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="d-flex align-items-center ms-auto">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst($_SESSION['role']); ?></span>
                        </span>
                        <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content Will Be Here -->
            <div class="container-fluid p-4">
                <!-- Konten dashboard akan dimasukkan di sini -->
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Active menu item
            var current = location.pathname.split('/').pop();
            $('#sidebar ul li a').each(function() {
                var $this = $(this);
                if ($this.attr('href').indexOf(current) !== -1) {
                    $this.parent().addClass('active');
                }
            });
        });
    </script>
</body>

</html>