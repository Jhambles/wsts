<?php
session_start();

if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// ... rest of your code ...


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

// Handle category addition POST request
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

                // Also delete all products in that category
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
                                'description' => (string)$product->description,
                                'quantity' => (string)$product->quantity,
                                'tags' => (string)$product->tags,
                                'image' => (string)$product->image ?? '',
                            ]) ?>)'>Update</button>

                            <form method="post" class="delete-form" action="admin.php?tab=update_product" onsubmit="return confirm('Delete product <?= htmlspecialchars(addslashes($product->name)) ?>?');" style="display:inline;">
                                <input type="hidden" name="delete_product_id" value="<?= htmlspecialchars($product['id']) ?>" />
                                <button type="submit" name="delete_product">Delete</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Update Product Modal -->
            <div id="updateModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="updateModalTitle" aria-modal="true">
                <div class="modal-content">
                    <span class="close" onclick="closeUpdateModal()" role="button" aria-label="Close modal">&times;</span>
                    <h2 id="updateModalTitle">Update Product Form</h2>
                    <form id="updateProductForm" method="post" action="store.php" enctype="multipart/form-data" class="form">
                        <input type="hidden" name="action" value="update_product" />
                        <input type="hidden" name="id" id="updateId">

                        <div class="form-group">
                            <label for="productSelect">Select Product</label>
                            <select id="productSelect" required onchange="loadProductDetails(this.value)">
                                <option value="">-- Select Product --</option>
                                <?php foreach ($productsXml->product as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="updateName">Name</label>
                            <input type="text" id="updateName" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="updateCategory">Category</label>
                            <select id="updateCategory" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categoriesXml->category as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat->name) ?>"><?= htmlspecialchars($cat->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="updatePrice">Price</label>
                            <input type="number" step="0.01" id="updatePrice" name="price" required>
                        </div>

                        <div class="form-group">
                            <label for="updateDescription">Description</label>
                            <textarea id="updateDescription" name="description" rows="4" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="updateQuantity">Stock Quantity</label>
                            <input type="number" id="updateQuantity" name="quantity" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="updateTags">Tags (comma-separated)</label>
                            <input type="text" id="updateTags" name="tags" placeholder="tag1, tag2">
                        </div>

                        <div class="form-group">
                            <label for="updateImage">Image (leave empty to keep existing)</label>
                            <input type="file" id="updateImage" name="image" accept="image/*">
                        </div>

                        <button type="submit" class="btn">Update Product</button>
                    </form>
                </div>
            </div>

            <script>
                const products = <?= json_encode(array_map(function($p) {
                    return [
                        'id' => (string)$p['id'],
                        'name' => (string)$p->name,
                        'category' => (string)$p->category,
                        'price' => (string)$p->price,
                        'description' => (string)$p->description,
                        'quantity' => (string)$p->quantity,
                        'tags' => (string)$p->tags,
                        'image' => (string)$p->image ?? '',
                    ];
                }, iterator_to_array($productsXml->product))) ?>;
                function openUpdateModal(productData) {
                    const modal = document.getElementById('updateModal');
                    modal.style.display = 'block';
                    modal.setAttribute('aria-hidden', 'false');

                    // Pre-fill form with selected product data
                    document.getElementById('updateId').value = productData.id || '';
                    document.getElementById('updateName').value = productData.name || '';
                    document.getElementById('updateCategory').value = productData.category || '';
                    document.getElementById('updatePrice').value = productData.price || '';
                    document.getElementById('updateDescription').value = productData.description || '';
                    document.getElementById('updateQuantity').value = productData.quantity || '';
                    document.getElementById('updateTags').value = productData.tags || '';
                    // Reset file input
                    document.getElementById('updateImage').value = '';
                    // Set product select to current product
                    const select = document.getElementById('productSelect');
                    select.value = productData.id;
                }
                function closeUpdateModal() {
                    const modal = document.getElementById('updateModal');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }
                // Close modal when clicking outside modal content
                window.onclick = function(event) {
                    const modal = document.getElementById('updateModal');
                    if (event.target === modal) {
                        closeUpdateModal();
                    }
                };

                // Load product details in form when selecting product from dropdown
                function loadProductDetails(productId) {
                    const product = products.find(p => p.id === productId);
                    if (!product) return;

                    document.getElementById('updateId').value = product.id;
                    document.getElementById('updateName').value = product.name;
                    document.getElementById('updateCategory').value = product.category;
                    document.getElementById('updatePrice').value = product.price;
                    document.getElementById('updateDescription').value = product.description;
                    document.getElementById('updateQuantity').value = product.quantity;
                    document.getElementById('updateTags').value = product.tags;
                    document.getElementById('updateImage').value = '';
                }
            </script>
        <?php elseif ($tab === 'transactions'): ?>
            <header><h1>Transactions</h1></header>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactionsXml->transaction as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id']) ?></td>
                            <td><?= htmlspecialchars($transaction->user) ?></td>
                            <td><?= htmlspecialchars($transaction->product) ?></td>
                            <td><?= htmlspecialchars($transaction->quantity) ?></td>
                            <td>$<?= number_format((float)$transaction->price, 2) ?></td>
                            <td><?= htmlspecialchars($transaction->date) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($tab === 'users'): ?>
            <header><h1>Users</h1></header>
            <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersXml->user as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user->username) ?></td>
                            <td><?= htmlspecialchars($user->email) ?></td>
                            <td><?= htmlspecialchars($user->role) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Invalid tab selection.</p>
        <?php endif; ?>
    </main>
</div>
</body>

</html>
