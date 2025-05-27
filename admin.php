<?php
session_start();

if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$categoriesFile = 'data/categories.xml';
$productsFile = 'data/products.xml';
$usersFile = 'data/users.xml';
$transactionsFile = 'data/transactions.xml';

if (!file_exists($categoriesFile) || filesize($categoriesFile) === 0) {
    file_put_contents($categoriesFile, '<categories></categories>');
}
if (!file_exists($productsFile)) {
    file_put_contents($productsFile, '<products></products>');
}
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, '<users></users>');
}
if (!file_exists($transactionsFile) || filesize($transactionsFile) === 0) {
    file_put_contents($transactionsFile, '<transactions></transactions>');
}

$categoriesXml = simplexml_load_file($categoriesFile);
$productsXml = simplexml_load_file($productsFile);
$usersXml = simplexml_load_file($usersFile);
$transactionsXml = simplexml_load_file($transactionsFile);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Category
    if (isset($_POST['add_category'])) {
        $newCatName = trim($_POST['category_name'] ?? '');
        $newCatDesc = trim($_POST['category_desc'] ?? '');
        if ($newCatName !== '') {
            $exists = false;
            foreach ($categoriesXml->category as $existingCat) {
                if (strcasecmp((string)$existingCat->name, $newCatName) === 0) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $newCategory = $categoriesXml->addChild('category');
                $newCategory->addChild('name', htmlspecialchars($newCatName));
                $newCategory->addChild('description', htmlspecialchars($newCatDesc));
                $categoriesXml->asXML($categoriesFile);
            }
        }
        header('Location: admin.php?tab=category');
        exit;
    }

    // Delete Category
    if (isset($_POST['delete_category'])) {
        $delCategoryName = trim($_POST['delete_category_name'] ?? '');
        if ($delCategoryName !== '') {
            $indexToRemove = null;
            foreach ($categoriesXml->category as $idx => $category) {
                if (strcasecmp((string)$category->name, $delCategoryName) === 0) {
                    $indexToRemove = $idx;
                    break;
                }
            }
            if ($indexToRemove !== null) {
                unset($categoriesXml->category[$indexToRemove]);
                $categoriesXml->asXML($categoriesFile);

                // Also delete products in that category
                $productsChanged = false;
                for ($i = count($productsXml->product) - 1; $i >= 0; $i--) {
                    $product = $productsXml->product[$i];
                    if (strcasecmp((string)$product->category, $delCategoryName) === 0) {
                        unset($productsXml->product[$i]);
                        $productsChanged = true;
                    }
                }
                if ($productsChanged) {
                    $productsXml->asXML($productsFile);
                }
            }
        }
        header('Location: admin.php?tab=category');
        exit;
    }

    // Delete Product
    if (isset($_POST['delete_product'])) {
        $delProductId = trim($_POST['delete_product_id'] ?? '');
        if ($delProductId !== '') {
            $indexToRemove = null;
            foreach ($productsXml->product as $idx => $product) {
                if ((string)$product['id'] === $delProductId) {
                    $indexToRemove = $idx;
                    break;
                }
            }
            if ($indexToRemove !== null) {
                unset($productsXml->product[$indexToRemove]);
                $productsXml->asXML($productsFile);
            }
        }
        header('Location: admin.php?tab=update_product');
        exit;
    }
}

$tab = $_GET['tab'] ?? 'category';
$searchQuery = $_GET['search'] ?? '';
$selectedTag = $_GET['tag'] ?? '';

$filteredProducts = [];
foreach ($productsXml->product as $product) {
    $tags = array_map('trim', explode(',', (string) $product->tags));
    $productName = (string) $product->name;
    $productCategory = (string) $product->category;

    if (
        (empty($searchQuery) || stripos($productName, $searchQuery) !== false || stripos($productCategory, $searchQuery) !== false)
        && (empty($selectedTag) || in_array($selectedTag, $tags))
    ) {
        $filteredProducts[] = $product;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="data/styles.css">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Panel</title>
</head>
<body>
<div class="container">

    <nav class="sidebar">
        <a href="?tab=category" <?= $tab === 'category' ? 'class="active"' : '' ?>>Category</a>
        <a href="?tab=product" <?= $tab === 'product' ? 'class="active"' : '' ?>>Product</a>
        <a href="?tab=update_product" <?= $tab === 'update_product' ? 'class="active"' : '' ?>>Update Product</a>
        <a href="?tab=transactions" <?= $tab === 'transactions' ? 'class="active"' : '' ?>>Transactions</a>
        <a href="?tab=users" <?= $tab === 'users' ? 'class="active"' : '' ?>>Users</a>
        <a href="?tab=logout" <?= $tab === 'logout' ? 'class="active"' : '' ?>>Logout</a>
    </nav>

    <main class="content">

        <?php if ($tab === 'category'): ?>
            <header><h1>Categories</h1></header>
            <form method="post" action="admin.php?tab=category">
                <input type="text" name="category_name" placeholder="Category Name" required />
                <textarea name="category_desc" placeholder="Category Description"></textarea>
                <button name="add_category" type="submit">Add Category</button>
            </form>

            <h2>Existing Categories</h2>
            <ul>
                <?php foreach ($categoriesXml->category as $cat): ?>
                    <li>
                        <strong><?= htmlspecialchars($cat->name) ?></strong>: <?= htmlspecialchars($cat->description) ?>
                        <form method="post" class="delete-form" action="admin.php?tab=category" onsubmit="return confirm('Delete category <?= htmlspecialchars(addslashes($cat->name)) ?> and all its products?');">
                            <input type="hidden" name="delete_category_name" value="<?= htmlspecialchars($cat->name) ?>" />
                            <button type="submit" name="delete_category">Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>

        <?php elseif ($tab === 'product'): ?>
            <header><h1>Upload New Product</h1></header>
            <form method="post" action="store.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_product" />
                <select name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categoriesXml->category as $cat): ?>
                        <option value="<?= htmlspecialchars($cat->name) ?>"><?= htmlspecialchars($cat->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="name" placeholder="Product Name" required />
                <input type="text" name="price" placeholder="Price" required />
                <textarea name="description" placeholder="Description"></textarea>
                <input type="number" name="quantity" placeholder="Stock Quantity" />
                <input type="text" name="tags" placeholder="Tags (comma-separated)" />
                <input type="file" name="image" required />
                <button type="submit">Upload Product</button>
            </form>

        <?php elseif ($tab === 'update_product'): ?>
            <header><h1>Update Product</h1></header>

            <section class="search-filter">
                <form method="get" action="" style="display: flex; align-items: center;">
                    <input type="hidden" name="tab" value="update_product" />
                    <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search Products" />
                    <select name="tag">
                        <option value="">Select a Tag</option>
                        <?php
                        $tags = [];
                        foreach ($productsXml->product as $product) {
                            $productTags = explode(',', (string) $product->tags);
                            foreach ($productTags as $tag) {
                                $tagTrimmed = trim($tag);
                                if ($tagTrimmed !== '') {
                                    $tags[] = $tagTrimmed;
                                }
                            }
                        }
                        $tags = array_unique($tags);
                        foreach ($tags as $tag): ?>
                            <option value="<?= htmlspecialchars($tag) ?>" <?= $selectedTag === $tag ? 'selected' : '' ?>><?= htmlspecialchars($tag) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Filter</button>
                </form>
            </section>

            <h2>Filtered Products</h2>
            <ul>
                <?php foreach ($filteredProducts as $product): ?>
                    <li>
                        <strong><?= htmlspecialchars($product->name) ?></strong><br />
                        Category: <?= htmlspecialchars($product->category) ?><br />
                        Tags: <?= htmlspecialchars($product->tags) ?><br />
                        Price: $<?= number_format((float)$product->price, 2) ?><br />
                        Stock: <?= htmlspecialchars($product->quantity) ?>

                        <div style="margin-top: 10px;">
                            <button type="button" onclick='openUpdateModal(<?= json_encode([
                                'id' => (string)$product['id'],
                                'name' => (string)$product->name,
                                'category' => (string)$product->category,
                                'price' => (string)$product->price,
                                'quantity' => (string)$product->quantity,
                                'description' => (string)$product->description,
                                'tags' => (string)$product->tags,
                            ]) ?>)'>Edit</button>

                            <form method="post" action="admin.php?tab=update_product" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                <input type="hidden" name="delete_product_id" value="<?= htmlspecialchars($product['id']) ?>" />
                                <button type="submit" name="delete_product">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Modal for product update -->
            <div id="updateModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
                 background:rgba(0,0,0,0.7); justify-content:center; align-items:center;">
                <div style="background:#fff; padding:20px; border-radius:8px; width:400px; max-width:90%;">
                    <h2>Edit Product</h2>
                    <form id="updateForm" method="post" action="store.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_product" />
                        <input type="hidden" name="id" id="productId" />
                        <label>Category:</label>
                        <select name="category" id="productCategory" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categoriesXml->category as $cat): ?>
                                <option value="<?= htmlspecialchars($cat->name) ?>"><?= htmlspecialchars($cat->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>Name:</label>
                        <input type="text" name="name" id="productName" required />
                        <label>Price:</label>
                        <input type="text" name="price" id="productPrice" required />
                        <label>Quantity:</label>
                        <input type="number" name="quantity" id="productQuantity" />
                        <label>Description:</label>
                        <textarea name="description" id="productDescription"></textarea>
                        <label>Tags:</label>
                        <input type="text" name="tags" id="productTags" placeholder="Comma-separated" />
                        <label>Image (leave empty to keep current):</label>
                        <input type="file" name="image" />
                        <br /><br />
                        <button type="submit">Save Changes</button>
                        <button type="button" onclick="closeUpdateModal()">Cancel</button>
                    </form>
                </div>
            </div>

        <?php elseif ($tab === 'transactions'): ?>
            <header><h1>Transactions</h1></header>
            <?php if (count($transactionsXml->transaction) === 0): ?>
                <p>No transactions available.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($transactionsXml->transaction as $transaction): ?>
                        <li>
                            <strong>Transaction ID:</strong> <?= htmlspecialchars($transaction['id']) ?><br />
                            <strong>User ID:</strong> <?= htmlspecialchars($transaction->user_id) ?><br />
                            <strong>Products:</strong>
                            <ul>
                                <?php foreach ($transaction->products->product as $prod): ?>
                                    <li><?= htmlspecialchars($prod->name) ?> x<?= htmlspecialchars($prod->quantity) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <strong>Total:</strong> $<?= number_format((float)$transaction->total, 2) ?><br />
                            <strong>Date:</strong> <?= htmlspecialchars($transaction->date) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        <?php elseif ($tab === 'users'): ?>
            <header><h1>Users</h1></header>
            <div id="usersTableContainer">
                <!-- Users data will be loaded here via AJAX or dynamically -->
            </div>

        <?php endif; ?>

    </main>
</div>

<script>
function openUpdateModal(product) {
    document.getElementById('updateModal').style.display = 'flex';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productCategory').value = product.category;
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productQuantity').value = product.quantity;
    document.getElementById('productDescription').value = product.description;
    document.getElementById('productTags').value = product.tags;
}

function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    // Load users dynamically for the users tab
    if ('<?= $tab ?>' === 'users') {
        fetch('user_fetch.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('usersTableContainer').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('usersTableContainer').innerHTML = '<p>Failed to load users.</p>';
            });
    }
});
</script>

</body>
</html>
