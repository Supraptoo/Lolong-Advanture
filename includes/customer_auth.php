<?php
require_once __DIR__ . '../config/database.php';

function authenticateCustomer($email, $password)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function registerCustomer($name, $email, $password)
{
    global $pdo;

    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, password) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $email, $hashedPassword]);
    } catch (PDOException $e) {
        return false;
    }
}
