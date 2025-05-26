<?php
session_start();

$_SESSION['cart'] = []; // 🚨 DEVELOPMENT USE ONLY — clears cart every time

$id = $_POST['id'] ?? '';
$action = $_POST['action'] ?? '';
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if (!$id) {
    echo json_encode(['error' => 'Missing product ID']);
    exit;
}

switch ($action) {
    case 'add':
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $quantity;
        echo json_encode(['status' => 'added']);
        break;
    case 'remove':
        unset($_SESSION['cart'][$id]);
        echo json_encode(['status' => 'removed']);
        break;
    case 'update':
        $_SESSION['cart'][$id] = $quantity;
        echo json_encode(['status' => 'updated']);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
