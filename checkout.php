<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    echo "<p>Your cart is empty. <a href='index.php'>Go back to shop</a></p>";
    exit;
}
$productsXml = simplexml_load_file('data/products.xml');
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            gap: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        .item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<body>

<!-- Back button -->
<a href="index.php" style="
    display: inline-block;
    margin-bottom: 20px;
    padding: 8px 15px;
    background-color: #0070ba;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: bold;
    font-family: Arial, sans-serif;
">
    ← Back to Store
</a>

<h1>Checkout</h1>
<!-- rest of your code -->

<h1>Checkout</h1>

<?php foreach ($cart as $id => $qty): ?>
    <?php
    foreach ($productsXml->product as $p) {
        if ((string)$p['id'] === $id):
            $subtotal = floatval($p->price) * $qty;
            $total += $subtotal;
            ?>
            <div class="item">
                <img src="<?= htmlspecialchars($p->image) ?>" alt="<?= htmlspecialchars($p->name) ?>">
                <div>
                    <strong><?= htmlspecialchars($p->name) ?></strong><br>
                    <?= intval($qty) ?> × $<?= number_format((float)$p->price, 2) ?> = 
                    <strong>$<?= number_format($subtotal, 2) ?></strong>
                </div>
            </div>
        <?php break; endif;
    } ?>
<?php endforeach; ?>

<h3>Total: $<?= number_format($total, 2) ?></h3>

<form action="complete_order.php" method="post">
    <button type="submit">Confirm Order</button>
</form>
<div id="paypal-button-container"></div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD"></script>
<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($total, 2, '.', '') ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                // Optionally send data to your backend
                alert('Transaction completed by ' + details.payer.name.given_name + '!');
                window.location.href = 'complete_order.php';
            });
        }
    }).render('#paypal-button-container');
</script>

</body>
</html>
