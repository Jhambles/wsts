<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_products': getProducts(); break;
    case 'upload_product': uploadProduct(); break;
    case 'get_cart': getCart(); break;
    case 'add_to_cart': addToCart(); break;
    case 'remove_from_cart': removeFromCart(); break;
    case 'view_users': viewUsers(); break;
    case 'delete_user': deleteUser(); break;
    case 'update_product': updateProduct(); break;
    case 'view_transactions': viewTransactions(); break;
    case 'delete_product': deleteProduct(); break;
    case 'delete_category': deleteCategory(); break;
    default:
        echo json_encode(["error" => "Invalid action"]);
}

function getProducts() {
    $category = $_GET['category'] ?? '';
    $search = strtolower($_GET['search'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));  // Ensure page is a positive integer
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
        // Filter by category
        if ($category && strtolower($p->category) !== strtolower($category)) continue;
        // Filter by search term
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
            'tags' => explode(',', (string)$p->tags)  // Split tags into an array
        ];
    }, $paginated);

    echo json_encode(['products' => $output, 'total' => $total]);
}
function getCart() {
    // Cart logic, similar to your previous version
    $cart = $_SESSION['cart'] ?? [];
    $cartItems = [];
    $cartTotal = 0;

    if (!file_exists('data/products.xml')) {
        echo json_encode(['error' => 'Products file not found']);
        return;
    }

    $xml = simplexml_load_file('data/products.xml');
    foreach ($cart as $id => $qty) {
        foreach ($xml->product as $p) {
            if ((string)$p['id'] === $id) {
                $price = (float)$p->price;
                $cartItems[] = [
                    'id' => $id,
                    'name' => (string)$p->name,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $price * $qty
                ];
                $cartTotal += $price * $qty;
                break;
            }
        }
    }

    echo json_encode(['items' => $cartItems, 'total' => $cartTotal]);
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

    // Image upload logic
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
            // Input validation
            if (isset($_POST['price']) && !is_numeric($_POST['price'])) {
                echo json_encode(['error' => 'Price must be a number']);
                return;
            }
            if (isset($_POST['quantity']) && !is_numeric($_POST['quantity'])) {
                echo json_encode(['error' => 'Quantity must be a number']);
                return;
            }

            // Update values
            if (!empty($_POST['name'])) $p->name = htmlspecialchars($_POST['name']);
            if (!empty($_POST['category'])) $p->category = htmlspecialchars($_POST['category']);
            if (!empty($_POST['price'])) $p->price = floatval($_POST['price']);
            if (!empty($_POST['description'])) $p->description = htmlspecialchars($_POST['description']);
            if (!empty($_POST['quantity'])) $p->quantity = intval($_POST['quantity']);
            if (!empty($_POST['tags'])) $p->tags = htmlspecialchars($_POST['tags']);

            // Handle image upload if present
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

            // Backup current products.xml
            $backupPath = 'data/products_backup_' . date('Ymd_His') . '.xml';
            copy($file, $backupPath);

            // Save updated XML
            if (!$xml->asXML($file)) {
                echo json_encode(["error" => "Failed to save product data"]);
                return;
            }

            // Log the update
            $logEntry = date('Y-m-d H:i:s') . " - Product ID $id updated\n";
            file_put_contents('data/update_log.txt', $logEntry, FILE_APPEND);

            // Return full product data
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



function addToCart() {
    $id = $_POST['id'] ?? '';
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if ($id) {
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
        echo json_encode(['status' => 'Product added to cart']);
    } else {
        echo json_encode(['error' => 'Invalid product ID']);
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

function viewUsers() {
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
    $storedHash = file_get_contents('data/admin_pass.txt');

    if (!password_verify($adminPass, $storedHash)) {
        http_response_code(403);
        echo json_encode(["error" => "Wrong admin password"]);
        return;
    }

    $xml = simplexml_load_file('data/users.xml');
    $index = 0;
    foreach ($xml->user as $u) {
        if ((string)$u->username === $username) {
            unset($xml->user[$index]);
            $xml->asXML('data/users.xml');
            echo json_encode(["status" => "User deleted"]);
            return;
        }
        $index++;
    }

    echo json_encode(["error" => "User not found"]);
}

function viewTransactions() {
    $xml = simplexml_load_file('data/transactions.xml');
    $transactions = [];
    $total = 0;

    foreach ($xml->transaction as $t) {
        $amount = (float)$t->amount;
        $transactions[] = [
            'user' => (string)$t->user,
            'amount' => $amount,
            'date' => (string)$t->date
        ];
        $total += $amount;
    }

    echo json_encode(['transactions' => $transactions, 'total_payments' => $total]);
}

function deleteProduct() {
    $id = $_POST['id'] ?? '';
    if (!$id) {
        echo json_encode(['error' => 'Product ID required']);
        return;
    }

    $file = 'data/products.xml';
    $xml = simplexml_load_file($file);

    $index = 0;
    $found = false;
    foreach ($xml->product as $p) {
        if ((string)$p['id'] === $id) {
            unset($xml->product[$index]);
            $found = true;
            break;
        }
        $index++;
    }

    if ($found) {
        $xml->asXML($file);
        echo json_encode(['status' => 'Product deleted']);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }
}

function deleteCategory() {
    $categoryName = $_POST['category'] ?? '';
    if (!$categoryName) {
        echo json_encode(['error' => 'Category name required']);
        return;
    }

    $file = 'data/products.xml';
    $xml = simplexml_load_file($file);

    $removedAny = false;
    for ($i = count($xml->product) - 1; $i >= 0; $i--) {
        $p = $xml->product[$i];
        if (strtolower((string)$p->category) === strtolower($categoryName)) {
            unset($xml->product[$i]);
            $removedAny = true;
        }
    }

    if ($removedAny) {
        $xml->asXML($file);
        echo json_encode(['status' => "Category '$categoryName' and its products deleted"]);
    } else {
        echo json_encode(['error' => 'Category not found or no products in this category']);
    }
}
?>
