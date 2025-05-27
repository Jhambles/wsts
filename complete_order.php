<?php
session_start();

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<p>Your cart is empty. <a href='index.php'>Go back to shop</a></p>";
    exit;
}

// Optionally save transaction data here
$cart = $_SESSION['cart'];
$productsXml = simplexml_load_file('data/products.xml');
$total = 0;

$orderItems = [];

foreach ($cart as $id => $qty) {
    foreach ($productsXml->product as $p) {
        if ((string)$p['id'] === $id) {
            $price = floatval($p->price);
            $subtotal = $price * $qty;
            $total += $subtotal;

            $orderItems[] = [
                'name' => (string)$p->name,
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $subtotal
            ];
            break;
        }
    }
}

// OPTIONAL: Save transaction to XML
$transactionsFile = 'data/transactions.xml';
if (!file_exists($transactionsFile)) {
    $transactionsXml = new SimpleXMLElement('<transactions></transactions>');
} else {
    $transactionsXml = simplexml_load_file($transactionsFile);
}

$transaction = $transactionsXml->addChild('transaction');
$transaction->addAttribute('id', uniqid());
$transaction->addChild('user', 'guest'); // Replace with actual username if available
$transaction->addChild('items', count($orderItems));
$transaction->addChild('total', $total);
$transaction->addChild('date', date('Y-m-d H:i:s'));

$transactionsXml->asXML($transactionsFile);

// Clear the cart
unset($_SESSION['cart']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Complete</title>
</head>
<body>
    <h1>Thank you for your purchase!</h1>
    <p>Your order has been placed successfully.</p>
    <p><a href="index.php">Back to Shop</a></p>
</body>
</html>
