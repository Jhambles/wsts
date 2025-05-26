<?php
session_start();

$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$cartTotal = 0;

$productsXml = simplexml_load_file('data/products.xml');

foreach ($cart as $id => $qty) {
    foreach ($productsXml->product as $p) {
        if ((string)$p['id'] === $id) {
            $price = (float)$p->price;
            $subtotal = $price * $qty;
            $cartItems[] = [
                'id' => $id,
                'name' => (string)$p->name,
                'image' => (string)$p->image,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
            $cartTotal += $subtotal;
            break;
        }
    }
}
?>

<div class="cart-dropdown">
    <?php if (empty($cartItems)): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($cartItems as $item): ?>
            <div class="cart-item" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 50px; height: 50px; object-fit: cover;">
                <div style="flex: 1;">
                    <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                    $<?= number_format($item['price'], 2) ?> × 
                    <input type="number" class="cart-qty" data-id="<?= $item['id'] ?>" value="<?= $item['qty'] ?>" min="1" style="width: 50px;">
                    = $<?= number_format($item['subtotal'], 2) ?>
                </div>
                <button class="remove-item" data-id="<?= $item['id'] ?>" style="background: none; border: none; color: red; font-size: 18px; cursor: pointer;">❌</button>
            </div>
        <?php endforeach; ?>
        <hr>
        <p><strong>Total: $<?= number_format($cartTotal, 2) ?></strong></p>
    <?php endif; ?>
</div>
