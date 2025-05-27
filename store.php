<?php
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_products': getProducts(); break;
    case 'upload_product': uploadProduct(); break;
    case 'get_cart': getCart(); break;
    case 'add_to_cart': addToCart(); break;
    case 'update_quantity': updateQuantity(); break;
    case 'remove_from_cart': removeFromCart(); break;
    case 'view_users': viewUsers(); break;
    case 'delete_user': deleteUser(); break;
    case 'update_product': updateProduct(); break;
    case 'view_transactions': viewTransactions(); break;
    case 'delete_product': deleteProduct(); break;
    case 'delete_category': deleteCategory(); break;
    default:
        echo json_encode(["error" => "Invalid action"]);
        break;
}

function getProducts() {
    $category = $_GET['category'] ?? '';
    $search = strtolower($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 5;

    if (!file_exists('data/products.xml')) {
        echo json_encode(['error' => 'Products file not found']);
        return;
    }

    $xml = simplexml_load_file('data/products.xml');
    if ($xml === false) {
        echo json_encode(['error' => 'Failed to parse products file']);
        return;
    }

    $filtered = [];
    foreach ($xml->product as $p) {
        if ($category && strtolower($p->category) !== strtolower($category)) continue;
        if ($search && strpos(strtolower($p->tags), $search) === false) continue;
        $filtered[] = $p;
    }

    $total = count($filtered);
    $offset = ($page - 1) * $perPage;
    $paginated = array_slice($filtered, $offset, $perPage);

    $output = array_map(function($p) {
        return [
            'id' => (string)$p['id'],
            'name' => (string)$p->name,
            'category' => (string)$p->category,
            'price' => (float)$p->price,
            'image' => (string)$p->image,
            'description' => (string)$p->description,
            'quantity' => (int)$p->quantity,
            'tags' => explode(',', (string)$p->tags)
        ];
    }, $paginated);

    echo json_encode(['products' => $output, 'total' => $total]);
}

function getCart() {
    if (!file_exists('data/products.xml')) {
        echo '<p>No products found.</p>';
        return;
    }

    $products = simplexml_load_file('data/products.xml');

    if (empty($_SESSION['cart'])) {
        echo '<p>Your cart is empty.</p>';
        return;
    }

    foreach ($_SESSION['cart'] as $id => $qty) {
        foreach ($products->product as $p) {
            if ((string)$p['id'] === $id) {
                $price = number_format((float)$p->price, 2);
                $image = htmlspecialchars($p->image);
                $name = htmlspecialchars($p->name);

                echo '<div class="cart-item" data-id="' . htmlspecialchars($id) . '">';
                echo '<button class="remove-item" title="Remove">&times;</button>';
                echo '<img src="' . $image . '" class="cart-image" alt="' . $name . '">';
                echo '<div class="cart-info">';
                echo '<strong>' . $name . '</strong><br>';
                echo '<div class="quantity-control">';
                echo '<button class="decrease">-</button> ';
                echo '<span class="quantity">' . intval($qty) . '</span> ';
                echo '<button class="increase">+</button>';
                echo '</div>';
                echo '<small>$' . $price . '</small>';
                echo '</div>';
                echo '</div>';
                break;
            }
        }
    }
}

function addToCart() {
    $id = $_POST['id'] ?? '';
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if ($id) {
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 0;
        }
        $_SESSION['cart'][$id] += $qty;
        echo json_encode(['status' => true]);
    } else {
        echo json_encode(['status' => false, 'error' => 'Invalid product ID']);
    }
}

function updateQuantity() {
    $id = $_POST['id'] ?? '';
    $qty = (int)($_POST['quantity'] ?? 0);

    if ($id) {
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
        echo json_encode(['status' => true]);
    } else {
        echo json_encode(['status' => false, 'error' => 'Invalid product ID']);
    }
}

function removeFromCart() {
    $id = $_POST['id'] ?? '';
    if ($id !== '') {
        unset($_SESSION['cart'][$id]);
        echo json_encode(['status' => 'Product removed from cart']);
    } else {
        echo json_encode(['error' => 'Invalid product ID']);
    }
}

function uploadProduct() {
    $file = 'data/products.xml';
    if (!file_exists($file)) {
        echo json_encode(['error' => 'Products file not found']);
        return;
    }

    $xml = simplexml_load_file($file);
    if ($xml === false) {
        echo json_encode(['error' => 'Failed to load products XML']);
        return;
    }

    $id = uniqid();
    $p = $xml->addChild('product');
    $p->addAttribute('id', $id);
    $p->addChild('name', htmlspecialchars($_POST['name'] ?? ''));
    $p->addChild('category', htmlspecialchars($_POST['category'] ?? ''));
    $p->addChild('price', floatval($_POST['price'] ?? 0));
    $p->addChild('description', htmlspecialchars($_POST['description'] ?? ''));
    $p->addChild('quantity', intval($_POST['quantity'] ?? 0));
    $p->addChild('tags', htmlspecialchars($_POST['tags'] ?? ''));

    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $safeExt = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
        $imagePath = $uploadDir . $id . '.' . $safeExt;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            echo json_encode(["error" => "Failed to upload image"]);
            return;
        }
    }

    $p->addChild('image', $imagePath);

    if ($xml->asXML($file)) {
        echo json_encode(["status" => "Product uploaded successfully"]);
    } else {
        echo json_encode(["error" => "Failed to save the product data to XML"]);
    }
}

function updateProduct() {
    $file = 'data/products.xml';
    if (!file_exists($file)) {
        echo json_encode(['error' => 'Products file not found']);
        return;
    }

    $xml = simplexml_load_file($file);
    $id = $_POST['id'] ?? '';

    if (!$id) {
        echo json_encode(['error' => 'Product ID is required']);
        return;
    }

    foreach ($xml->product as $p) {
        if ((string)$p['id'] === $id) {
            if (isset($_POST['price']) && !is_numeric($_POST['price'])) {
                echo json_encode(['error' => 'Price must be a number']);
                return;
            }
            if (isset($_POST['quantity']) && !is_numeric($_POST['quantity'])) {
                echo json_encode(['error' => 'Quantity must be a number']);
                return;
            }

            if (!empty($_POST['name'])) $p->name = htmlspecialchars($_POST['name']);
            if (!empty($_POST['category'])) $p->category = htmlspecialchars($_POST['category']);
            if (!empty($_POST['price'])) $p->price = floatval($_POST['price']);
            if (!empty($_POST['description'])) $p->description = htmlspecialchars($_POST['description']);
            if (!empty($_POST['quantity'])) $p->quantity = intval($_POST['quantity']);
            if (!empty($_POST['tags'])) $p->tags = htmlspecialchars($_POST['tags']);

            if (isset($_FILES['image']) && $_FILES['image']['tmp_name']) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $safeExt = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));
                $imagePath = $uploadDir . $id . '.' . $safeExt;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                    echo json_encode(["error" => "Failed to upload new image"]);
                    return;
                }

                $p->image = $imagePath;
            }

            // Backup before saving
            $backupPath = 'data/products_backup_' . date('Ymd_His') . '.xml';
            copy($file, $backupPath);

            if (!$xml->asXML($file)) {
                echo json_encode(["error" => "Failed to save product data"]);
                return;
            }

            $logEntry = date('Y-m-d H:i:s') . " - Product ID $id updated\n";
            file_put_contents('data/update_log.txt', $logEntry, FILE_APPEND);

            echo json_encode([
                "status" => "Product updated",
                "product" => [
                    'id' => (string)$p['id'],
                    'name' => (string)$p->name,
                    'category' => (string)$p->category,
                    'price' => (float)$p->price,
                    'description' => (string)$p->description,
                    'quantity' => (int)$p->quantity,
                    'tags' => (string)$p->tags,
                    'image' => (string)$p->image
                ]
            ]);
            return;
        }
    }

    echo json_encode(["error" => "Product not found"]);
}

function viewUsers() {
    if (!file_exists('data/users.xml')) {
        echo json_encode(['error' => 'Users file not found']);
        return;
    }
    $xml = simplexml_load_file('data/users.xml');
    $users = [];
    foreach ($xml->user as $u) {
        $users[] = (string)$u->username;
    }
    echo json_encode($users);
}

function deleteUser() {
    $username = $_POST['username'] ?? '';
    $adminPass = $_POST['admin_pass'] ?? '';
    if (!file_exists('data/admin_pass.txt')) {
        echo json_encode(['error' => 'Admin password file not found']);
        return;
    }
    $storedHash = file_get_contents('data/admin_pass.txt');

    if (!password_verify($adminPass, $storedHash)) {
        http_response_code(403);
        echo json_encode(["error" => "Wrong admin password"]);
        return;
    }

    if (!file_exists('data/users.xml')) {
        echo json_encode(['error' => 'Users file not found']);
        return;
    }

    $xml = simplexml_load_file('data/users.xml');

    $found = false;
    foreach ($xml->user as $key => $u) {
        if ((string)$u->username === $username) {
            unset($xml->user[$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(["error" => "User not found"]);
        return;
    }

    if ($xml->asXML('data/users.xml')) {
        echo json_encode(["status" => "User deleted"]);
    } else {
        echo json_encode(["error" => "Failed to delete user"]);
    }
}

function viewTransactions() {
    if (!file_exists('data/transactions.xml')) {
        echo json_encode(['error' => 'Transactions file not found']);
        return;
    }
    $xml = simplexml_load_file('data/transactions.xml');
    $transactions = [];
    foreach ($xml->transaction as $t) {
        $transactions[] = [
            'id' => (string)$t['id'],
            'user' => (string)$t->user,
            'items' => (int)$t->items,
            'total' => (float)$t->total,
            'date' => (string)$t->date
        ];
    }
    echo json_encode($transactions);
}

function deleteProduct() {
    $id = $_POST['id'] ?? '';
    if (!file_exists('data/products.xml')) {
        echo json_encode(['error' => 'Products file not found']);
        return;
    }
    $xml = simplexml_load_file('data/products.xml');

    $found = false;
    foreach ($xml->product as $key => $p) {
        if ((string)$p['id'] === $id) {
            unset($xml->product[$key]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        echo json_encode(["error" => "Product not found"]);
        return;
    }

    if ($xml->asXML('data/products.xml')) {
        echo json_encode(["status" => "Product deleted"]);
    } else {
        echo json_encode(["error" => "Failed to delete product"]);
    }
}

function deleteCategory() {
    $category = $_POST['category'] ?? '';
    if (!$category) {
        echo json_encode(['error' => 'Category required']);
        return;
    }
    if (!file_exists('data/categories.xml')) {
        echo json_encode(['error' => 'Categories file not found']);
        return;
    }
    $xml = simplexml_load_file('data/categories.xml');

    $found = false;
    foreach ($xml->category as $key => $c) {
        if ((string)$c == $category) {
            unset($xml->category[$key]);
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo json_encode(['error' => 'Category not found']);
        return;
    }
    if ($xml->asXML('data/categories.xml')) {
        echo json_encode(['status' => 'Category deleted']);
    } else {
        echo json_encode(['error' => 'Failed to delete category']);
    }
}

?>
