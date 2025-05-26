<?php
session_start();

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
            // Remove category node
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
    <style>
        .search-filter {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-filter input[type="text"], 
        .search-filter select, 
        .search-filter button {
            padding: 10px;
            margin-right: 10px;
        }
        form.delete-form {
            display: inline;
            margin-left: 10px;
        }
    </style>
</head>
<body>
<div class="container">

    <nav class="sidebar">
        <a href="?tab=category" <?= $tab === 'category' ? 'class="active"' : '' ?>>Category</a>
        <a href="?tab=product" <?= $tab === 'product' ? 'class="active"' : '' ?>>Product</a>
        <a href="?tab=update_product" <?= $tab === 'update_product' ? 'class="active"' : '' ?>>Update Product</a>
        <a href="?tab=transactions" <?= $tab === 'transactions' ? 'class="active"' : '' ?>>Transactions</a>
        <a href="?tab=users" <?= $tab === 'users' ? 'class="active"' : '' ?>>Users</a>
        <a href="logout.php">Logout</a>
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
                        $tags[] = trim($tag);
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
]) ?>)'>Update</button>


    <form method="post" class="delete-form" action="admin.php?tab=update_product" onsubmit="return confirm('Delete product <?= htmlspecialchars(addslashes($product->name)) ?>?');">
        <input type="hidden" name="delete_product_id" value="<?= htmlspecialchars($product['id']) ?>" />
        <button type="submit" name="delete_product">Delete</button>
    </form>
</div>

            </li>
        <?php endforeach; ?>
    </ul>

  

    <script>
        const productData = <?= json_encode(array_map(function($p) {
            return [
                'id' => (string) $p['id'],
                'name' => (string) $p->name,
                'category' => (string) $p->category,
                'price' => (string) $p->price,
                'description' => (string) $p->description,
                'quantity' => (string) $p->quantity,
                'tags' => (string) $p->tags,
                'image' => (string) $p->image
            ];
        }, iterator_to_array($productsXml->product))) ?>;

        const select = document.getElementById('productSelect');
        const fields = {
            name: document.getElementById('name'),
            category: document.getElementById('category'),
            price: document.getElementById('price'),
            description: document.getElementById('description'),
            quantity: document.getElementById('quantity'),
            tags: document.getElementById('tags'),
            image: document.getElementById('currentImage')
        };

        select.addEventListener('change', () => {
            const selected = productData.find(p => p.id === select.value);
            if (selected) {
                fields.name.value = selected.name;
                fields.category.value = selected.category;
                fields.price.value = selected.price;
                fields.description.value = selected.description;
                fields.quantity.value = selected.quantity;
                fields.tags.value = selected.tags;
                if (selected.image) {
                    fields.image.src = 'data/uploads/' + selected.image;
                    fields.image.style.display = 'block';
                } else {
                    fields.image.style.display = 'none';
                }
            }
        });
        <script>
    function loadProductData(productId) {
        const selected = productData.find(p => p.id === productId);
        if (selected) {
            document.getElementById('productSelect').value = selected.id;
            fields.name.value = selected.name;
            fields.category.value = selected.category;
            fields.price.value = selected.price;
            fields.description.value = selected.description;
            fields.quantity.value = selected.quantity;
            fields.tags.value = selected.tags;

            if (selected.image) {
                fields.image.src = 'data/uploads/' + selected.image;
                fields.image.style.display = 'block';
            } else {
                fields.image.style.display = 'none';
            }

            // Scroll to the form
            document.querySelector('form[action="store.php"]').scrollIntoView({ behavior: 'smooth' });
        }
    }
</script>

    </script>
    <script>
    // Open modal and populate form fields
    function openUpdateModal(product) {
        document.getElementById('updateId').value = product.id || '';
        document.getElementById('updateName').value = product.name || '';
        document.getElementById('updateCategory').value = product.category || '';
        document.getElementById('updatePrice').value = product.price || '';
        document.getElementById('updateDescription').value = product.description || '';
        document.getElementById('updateQuantity').value = product.quantity || '';
        document.getElementById('updateTags').value = product.tags || '';
        // Clear image input
        document.getElementById('updateImage').value = '';

        document.getElementById('updateModal').style.display = 'block';
    }

    // Close modal and reset form
    function closeUpdateModal() {
        document.getElementById('updateModal').style.display = 'none';
        document.getElementById('updateProductForm').reset();
    }

    // Close modal if clicking outside modal content
    window.onclick = function(event) {
        const modal = document.getElementById('updateModal');
        if (event.target === modal) {
            closeUpdateModal();
        }
    };

    // Submit update form with AJAX
    document.getElementById('updateProductForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_product');

        fetch('store.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.status || data.error);
            if (data.status) {
                closeUpdateModal();
                // Reload product list or page to reflect updates
                location.reload();
            }
        })
        .catch(err => {
            alert('Error updating product.');
            console.error(err);
        });
    });
</script>

<?php endif; ?>


    </main>
</div>
<!-- Update Product Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUpdateModal()">&times;</span>
        <h2>Update Product Form</h2>
        <form id="updateProductForm" method="post" action="store.php" enctype="multipart/form-data" class="form">
            <input type="hidden" name="action" value="update_product" />
            <input type="hidden" name="id" id="updateId">

            <div class="form-group">
                <label for="productSelect">Select Product</label>
                <select name="id" id="productSelect" required onchange="loadProductDetails(this.value)">
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
                <input type="text" id="updateCategory" name="category" required>
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
                <label for="updateQuantity">Quantity</label>
                <input type="number" id="updateQuantity" name="quantity" required>
            </div>

            <div class="form-group">
                <label for="updateTags">Tags</label>
                <input type="text" id="updateTags" name="tags">
            </div>

            <div class="form-group">
                <label>Current Image</label><br>
                <img id="currentImage" src="" alt="Product Image" style="max-width: 200px; display: none;" />
            </div>

            <div class="form-group">
                <label for="updateImage">New Image (optional)</label>
                <input type="file" id="updateImage" name="image">
            </div>

            <button type="submit" class="btn">Update Product</button>
        </form>
    </div>
</div>

</div>

</body>
</html>
