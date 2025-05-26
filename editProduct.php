<?php
session_start();
$xmlFile = 'data/products.xml';
$productsXml = simplexml_load_file($xmlFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['save'])) {
    $id = $_POST['id'];
    foreach ($productsXml->product as $product) {
        if ((string)$product['id'] === $id) {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Edit Product</title>
                <link rel="stylesheet" href="data/admin.css">
            </head>
            <body>
            <h2>Edit Product: <?= htmlspecialchars($id) ?></h2>
            <form method="post" action="editProduct.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <label>Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product->name) ?>" required>
                <label>Category:</label>
                <input type="text" name="category" value="<?= htmlspecialchars($product->category) ?>" required>
                <label>Description:</label>
                <textarea name="description"><?= htmlspecialchars($product->description) ?></textarea>
                <label>Price:</label>
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($product->price) ?>" required>
                <label>Tags:</label>
                <input type="text" name="tags" value="<?= htmlspecialchars($product->tags) ?>">
                <label>Image Filename:</label>
                <input type="text" name="image" value="<?= basename($product->image) ?>">
                <button type="submit" name="save">Save Changes</button>
            </form>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

if (isset($_POST['save'])) {
    foreach ($productsXml->product as $product) {
        if ((string)$product['id'] === $_POST['id']) {
            $product->name = $_POST['name'];
            $product->category = $_POST['category'];
            $product->description = $_POST['description'];
            $product->price = $_POST['price'];
            $product->tags = $_POST['tags'];
            $product->image = 'images/' . $_POST['image'];
            $productsXml->asXML($xmlFile);
            $_SESSION['flashMessage'] = "Product updated successfully.";
            break;
        }
    }
    header("Location: adminPanel.php");
    exit;
}
