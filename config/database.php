<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'lolong_adventure');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Set timeout untuk koneksi database
define('DB_CONNECTION_TIMEOUT', 5);

try {
    // Create PDO instance dengan timeout
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::ATTR_TIMEOUT            => DB_CONNECTION_TIMEOUT,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Optimasi koneksi
    $pdo->exec("SET SESSION wait_timeout = 30");
    $pdo->exec("SET SESSION interactive_timeout = 30");
} catch (PDOException $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Database connection error: " . $e->getMessage());
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

// Fungsi untuk query yang lebih aman
function dbQuery($sql, $params = [])
{
    global $pdo;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] SQL Error: " . $e->getMessage() . " | Query: " . $sql);
        throw new Exception("Database operation failed");
    }
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    session_start();
    session_regenerate_id(true);
}
