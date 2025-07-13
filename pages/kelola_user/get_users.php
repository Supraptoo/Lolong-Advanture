<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/database.php';

$search = isset($_GET['search']) ? filter_var(trim($_GET['search']), FILTER_SANITIZE_STRING) : '';
$page = isset($_GET['page']) ? max(1, filter_var($_GET['page'], FILTER_SANITIZE_NUMBER_INT)) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $query = "SELECT id, username, full_name, email, phone, created_at 
              FROM users 
              WHERE role = 'user'";
    $params = [];

    if ($search) {
        $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%"];
    }

    // Count total users
    $count_query = str_replace("SELECT id, username, full_name, email, phone, created_at", "SELECT COUNT(*)", $query);
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_users = $stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);

    // Fetch users
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate pagination HTML
    $pagination = '<ul class="pagination">';
    if ($page > 1) {
        $pagination .= "<li class='page-item'><a class='page-link' href='?page=" . ($page - 1) . "&search=" . urlencode($search) . "'>Sebelumnya</a></li>";
    }
    for ($i = 1; $i <= $total_pages; $i++) {
        $pagination .= "<li class='page-item " . ($i === $page ? 'active' : '') . "'>";
        $pagination .= "<a class='page-link' href='?page=$i&search=" . urlencode($search) . "'>$i</a></li>";
    }
    if ($page < $total_pages) {
        $pagination .= "<li class='page-item'><a class='page-link' href='?page=" . ($page + 1) . "&search=" . urlencode($search) . "'>Selanjutnya</a></li>";
    }
    $pagination .= '</ul>';

    echo json_encode([
        'users' => $users,
        'pagination' => $pagination
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Failed to fetch users']);
}
