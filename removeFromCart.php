<?php
session_start();
header('Content-Type: application/json');

$id = $_POST['id'] ?? '';

if ($id === '') {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

if (!isset($_SESSION['cart'][$id])) {
    echo json_encode(['error' => 'Product not found in cart']);
    exit;
}

// Remove the product from the cart
unset($_SESSION['cart'][$id]);

// Optionally, recalculate the total price for the cart
$total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    // Retrieve product details from XML or other storage for price info
    $xml = simplexml_load_file('data/products.xml');
    foreach ($xml->product as $product) {
        if ((string)$product['id'] === $id) {
            $price = (float)$product->price;
            $total += $price * $qty;
            break;
        }
    }
}

echo json_encode(['status' => 'Product removed', 'total' => $total]);
