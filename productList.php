<?php
$xmlFile = 'data/products.xml';

if (!file_exists($xmlFile)) {
    echo "<h2>No products uploaded yet.</h2>";
    exit;
}

$products = simplexml_load_file($xmlFile);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
    <link rel="stylesheet" href="data/plist.css">
</head>
<body>
    <h1>Product List</h1>

    <?php foreach ($products->product as $p): ?>
        <div class="product">
            <?php if (!empty($p->image)): ?>
                <img src="uploads/<?php echo htmlspecialchars($p->image); ?>" alt="Product Image">
            <?php endif; ?>
            <div class="info">
                <h2><?php echo htmlspecialchars($p->name); ?></h2>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($p->category); ?></p>
                <p><strong>Price:</strong> ₱<?php echo htmlspecialchars($p->price); ?></p>
                <p><strong>Quantity:</strong> <?php echo htmlspecialchars($p->quantity); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($p->description); ?></p>
                <p><strong>Tags:</strong>
                    <?php 
                        $tags = explode(",", $p->tags);
                        foreach ($tags as $tag) {
                            echo "<span class='tag'>" . htmlspecialchars(trim($tag)) . "</span>";
                        }
                    ?>
                </p>
                <button onclick="addToCart('<?php echo $p['id']; ?>', 1)">Add to Cart</button>
            </div>
        </div>
    <?php endforeach; ?>
</body>
</html>
