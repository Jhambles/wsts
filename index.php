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
</div>

<!-- Cart Alert -->
<div class="cart-alert" id="cart-alert">Product added to cart</div>

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
                <p>Price: $<?= htmlspecialchars($product->price) ?></p>
                <button class="add-to-cart" data-id="<?= htmlspecialchars($product['id']) ?>">Add to Cart</button>
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

<!-- AJAX Cart Script -->
<script>
$(document).ready(function() {
    $(document).on('click', '.add-to-cart', function() {
        var productId = $(this).data('id');
        $.ajax({
            url: 'store.php?action=add_to_cart',
            type: 'POST',
            data: { id: productId, quantity: 1 },
            success: function(response) {
                if (response.status) {
                    $('#cart-alert').fadeIn().delay(1500).fadeOut();
                } else {
                    alert('Error: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('AJAX Error: ' + error);
            }
        });
    });
});
</script>

</body>
</html>
