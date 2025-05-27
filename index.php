<?php
session_start();

$productsXml = simplexml_load_file('data/products.xml') or die('Error loading products.xml');
$categoriesFile = 'data/categories.xml';
if (!file_exists($categoriesFile)) file_put_contents($categoriesFile, '<categories></categories>');
$categoriesXml = simplexml_load_file($categoriesFile) or die('Error loading categories.xml');

$category = $_GET['category'] ?? '';
$search = strtolower($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1);
$perPage = 5;

$filtered = [];
foreach ($productsXml->product as $p) {
    if ($category && strtolower($p->category) !== strtolower($category)) continue;
    if ($search && strpos(strtolower($p->tags), $search) === false) continue;
    $filtered[] = $p;
}

$totalItems = count($filtered);
$totalPages = ceil($totalItems / $perPage);
$offset = ($page - 1) * $perPage;
$paginated = array_slice($filtered, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fictional Store</title>
    <link rel="stylesheet" href="data/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .cart-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: none;
            z-index: 9999;
        }
        .cart-panel {
            position: fixed;
            top: 0;
            right: 0;
            width: 300px;
            background: #f9f9f9;
            height: 100%;
            border-left: 1px solid #ccc;
            padding: 20px;
            overflow-y: auto;
        }
        .cart-item {
            margin-bottom: 15px;
            position: relative;
        }
        .remove-item {
            position: absolute;
            top: 0;
            right: 0;
            background: none;
            border: none;
            color: red;
            font-size: 18px;
            cursor: pointer;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .quantity-control button {
            width: 25px;
            height: 25px;
        }
        .cart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #ddd;
    padding: 10px;
}

.cart-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-info {
    display: flex;
    flex-direction: column;
}

    </style>
</head>
<body>

<div class="header">
    <img src="images/mm.jpg" alt="Logo" class="logo">
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
        <button type="button" id="toggle-cart">🛒</button>
    </form>
</div>

<div class="cart-alert" id="cart-alert">Product added to cart</div>

<div class="cart-panel" id="cart-panel" style="display:none;">
    <button id="close-cart" style="position: absolute; top: 10px; right: 10px; font-size: 20px; background: none; border: none; cursor: pointer;">&times;</button>
    <h3>Your Cart</h3>
    <div id="cart-items"></div>
<button id="checkout-button" style="margin-top: 20px; padding: 10px; width: 100%; background-color: #4caf50; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
    Proceed to Checkout
</button>

</div>

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
                <p>Price: $<?= htmlspecialchars($product->price) ?></p>
                <button class="add-to-cart" data-id="<?= htmlspecialchars($product['id']) ?>">Add to Cart</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; endforeach; ?>

<?php if (!$anyProductsShown): ?>
    <p>No products found matching your criteria.</p>
<?php endif; ?>

<div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&category=<?= urlencode($category) ?>&search=<?= urlencode($search) ?>" <?= $i === $page ? 'class="active"' : '' ?>>
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

<script>
$(document).ready(function() {
    $('#toggle-cart').click(function() {
        $('#cart-panel').toggle();
        loadCart();
    });

    $('.add-to-cart').click(function() {
        var productId = $(this).data('id');
        $.post('store.php?action=add_to_cart', { id: productId, quantity: 1 }, function(response) {
            if (response.status) {
                $('#cart-alert').fadeIn().delay(1500).fadeOut();
                loadCart();
            } else {
                alert('Error: ' + response.error);
            }
        }, 'json');
    });

    function loadCart() {
        $.get('store.php?action=get_cart', function(html) {
            $('#cart-items').html(html);
        });
    }

    $('#cart-items').on('click', '.increase, .decrease', function() {
        let parent = $(this).closest('.cart-item');
        let id = parent.data('id');
        let qtyElem = parent.find('.quantity');
        let qty = parseInt(qtyElem.text());

        if ($(this).hasClass('increase')) {
            qty++;
        } else if (qty > 1) {
            qty--;
        }

        $.post('store.php?action=update_quantity', { id: id, quantity: qty }, function(res) {
            if (res.status) {
                qtyElem.text(qty);
            } else {
                alert('Failed to update cart.');
            }
        }, 'json');
    });

    // Handle Remove Item
    $('#cart-items').on('click', '.remove-item', function() {
        let parent = $(this).closest('.cart-item');
        let id = parent.data('id');

        $.post('store.php?action=remove_from_cart', { id: id }, function(res) {
            if (res.status || res.status === 'Product removed from cart') {
                loadCart();
            } else {
                alert('Failed to remove item.');
            }
        }, 'json');
    });

    $('#close-cart').click(function() {
        $('#cart-panel').hide();
    });
});

$('#checkout-button').click(function () {
    window.location.href = 'checkout.php';
});

</script>

</body>
</html>
