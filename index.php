<?php
session_start();

// Load products
$productsXml = simplexml_load_file('data/products.xml') or die('Error loading products.xml');

// Load categories
$categoriesFile = 'data/categories.xml';
if (!file_exists($categoriesFile)) file_put_contents($categoriesFile, '<categories></categories>');
$categoriesXml = simplexml_load_file($categoriesFile) or die('Error loading categories.xml');

// Filters
$category = $_GET['category'] ?? '';
$search = strtolower($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$perPage = 5;

// Filtered products
$filtered = [];
foreach ($productsXml->product as $p) {
    if ($category && strtolower($p->category) !== strtolower($category)) continue;
    if ($search && strpos(strtolower($p->tags), $search) === false) continue;
    $filtered[] = $p;
}

// Pagination
$totalItems = count($filtered);
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;
$paginated = array_slice($filtered, $offset, $perPage);

// Load cart
$cart = $_SESSION['cart'] ?? [];
$cartItems = [];
$cartTotal = 0;
foreach ($cart as $id => $qty) {
    foreach ($productsXml->product as $p) {
        if ((string)$p['id'] === $id) {
            $price = (float)$p->price * $qty;
            $cartItems[] = [
                'id' => $id,
                'name' => (string)$p->name,
                'qty' => $qty,
                'price' => (float)$p->price,
                'subtotal' => $price
            ];
            $cartTotal += $price;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fictional Store</title>
    <link rel="stylesheet" href="data/style.css">
    <style>
        .cart-popup {
            position: absolute;
            top: 40px;
            right: 10px;
            background: #fff;
            border: 1px solid #ccc;
            width: 300px;
            padding: 10px;
            z-index: 1000;
            display: none;
        }
        .cart-item { margin-bottom: 10px; }
        .cart-qty { width: 40px; }
        .remove-item {
            background: none;
            border: none;
            color: red;
            cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <img src="images/mm.jpg" alt="Logo" class="logo">

    <!-- Search Bar -->
    <form method="get" class="search-bar">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categoriesXml->category as $cat): 
                $catName = (string)$cat->name;
            ?>
                <option value="<?= htmlspecialchars($catName) ?>" <?= strtolower($category) === strtolower($catName) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($catName) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search MM CULTURE">
        <button type="submit">🔍</button>
    </form>

    <!-- Cart Icon -->
    <div class="cart" id="cart-icon">
        🛒 Cart (<?= array_sum($cart) ?>)
        <div id="cart-content" class="cart-popup"></div>
    </div>
</div>

<!-- Products -->
<?php
$anyProductsShown = false;
foreach ($categoriesXml->category as $cat): 
    $catName = (string)$cat->name;

    $hasProducts = false;
    foreach ($paginated as $product) {
        if (strtolower($product->category) === strtolower($catName)) {
            $hasProducts = true;
            break;
        }
    }

    if ($hasProducts):
        $anyProductsShown = true;
?>
    <div class="section-title"><?= htmlspecialchars($catName) ?></div>
    <div class="product-grid">
        <?php foreach ($paginated as $product): 
            if (strtolower($product->category) !== strtolower($catName)) continue;
        ?>
            <div class="product-card">
                <img src="<?= htmlspecialchars($product->image) ?>" alt="<?= htmlspecialchars($product->name) ?>">
                <h3><?= htmlspecialchars($product->name) ?></h3>
                <p><?= htmlspecialchars($product->description) ?></p>
                <form class="add-to-cart-form" data-id="<?= htmlspecialchars($product['id']) ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                    <button type="submit">Add to Cart</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; endforeach; ?>

<?php if (!$anyProductsShown): ?>
    <p>No products found matching your criteria.</p>
<?php endif; ?>

<!-- Pagination -->
<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" <?= $i === $page ? 'class="active"' : '' ?>>
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle cart popup
    $('#cart-icon').on('click', function() {
        $('#cart-content').toggle();
        loadCart();
    });

    // Load cart content
    function loadCart() {
        $.get('cart.php', function(data) {
            $('#cart-content').html(data).show();
        });
    }

    // AJAX Add to Cart
    $(document).on('submit', '.add-to-cart-form', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.post('addCart.php', { id: id, action: 'add', quantity: 1 }, function() {
            loadCart(); // Show updated cart
        });
    });

    // Quantity change in cart
    $(document).on('change', '.cart-qty', function() {
        const id = $(this).data('id');
        const qty = $(this).val();
        $.post('addCart.php', { id: id, quantity: qty, action: 'update' }, loadCart);
    });

    // Remove item
    $(document).on('click', '.remove-item', function() {
        const id = $(this).data('id');
        $.post('addCart.php', { id: id, action: 'remove' }, loadCart);
    });
});
</script>

</body>
</html>
