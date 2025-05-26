<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<p>Your cart is empty.</p>";
} else {
    require 'includes/db.php';
    $total = 0;

    echo "<ul>";
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $subtotal = $result['price'] * $quantity;
            $total += $subtotal;
            echo "<li>" . htmlspecialchars($result['name']) . " - $quantity x $" . htmlspecialchars($result['price']) . " = $" . number_format($subtotal, 2) . "</li>";
        }
    }
    echo "</ul>";
    echo "<p><strong>Total: $" . number_format($total, 2) . "</strong></p>";
}
?>
