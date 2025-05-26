<?php
session_start();
require 'includes/db.php';

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>WST Store - Home</title>
  <link rel="stylesheet" href="styles/style.css">
  <link rel="stylesheet" href="styles/smain.css">
  <script src="main.js" defer></script>
</head>
<body>

<!-- Shop by Icons -->
<section class="shop-icons">
  <h2>Shop by Icons</h2>
  <div class="icon-grid">
    <div class="icon-card">
      <img src="images/v2k.jpg" alt="V2K">
      <h3>V2K</h3>
    </div>
    <div class="icon-card">
      <img src="images/pegasus41.jpg" alt="Pegasus 41">
      <h3>PEGASUS 41</h3>
    </div>
    <div class="icon-card">
      <img src="images/vomero5.jpg" alt="Vomero 5">
      <h3>VOMERO 5</h3>
    </div>
  </div>
</section>

<!-- Search and Menu -->
<div class="search-bar">
  <form method="GET">
    <input type="text" name="search" placeholder="Search by tags..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" />
    <button type="submit">Search</button>
  </form>
</div>

<div class="menu">
  <?php
  $categoryQuery = $conn->query("SELECT DISTINCT category FROM products");
  while ($cat = $categoryQuery->fetch_assoc()) {
    echo "<a href='?category=" . urlencode($cat['category']) . "'>" . htmlspecialchars($cat['category']) . "</a> ";
  }
  ?>
</div>

<!-- Product Display -->
<div id="product-list">
<?php
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM products WHERE 1";
$countSql = "SELECT COUNT(*) as total FROM products WHERE 1";
$bindParams = [];
$types = "";

// Filtering
if ($category) {
    $sql .= " AND category = ?";
    $countSql .= " AND category = ?";
    $bindParams[] = $category;
    $types .= "s";
}

if ($search) {
    $sql .= " AND tags LIKE ?";
    $countSql .= " AND tags LIKE ?";
    $bindParams[] = "%$search%";
    $types .= "s";
}

// Pagination
$sql .= " LIMIT ? OFFSET ?";
$bindParams[] = $limit;
$bindParams[] = $offset;
$typesWithPagination = $types . "ii";

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!empty($typesWithPagination)) {
    $stmt->bind_param($typesWithPagination, ...$bindParams);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<div class='product'>
            <img src='" . htmlspecialchars($row['image']) . "' alt='Product image' />
            <h3>" . htmlspecialchars($row['name']) . "</h3>
            <p>$" . htmlspecialchars($row['price']) . "</p>
            <p>" . htmlspecialchars($row['description']) . "</p>
            <form method='post' action='cart.php'>
                <input type='hidden' name='product_id' value='{$row['id']}'>
                <input type='submit' name='add_to_cart' value='Add to Cart'>
            </form>
          </div>";
}
?>
</div>

<!-- Pagination -->
<div class="pagination">
  <?php
  $countStmt = $conn->prepare($countSql);

  $countTypes = '';
  $countParams = [];

  if ($category) {
      $countTypes .= 's';
      $countParams[] = $category;
  }

  if ($search) {
      $countTypes .= 's';
      $countParams[] = "%$search%";
  }

  if (!empty($countTypes)) {
      $countStmt->bind_param($countTypes, ...$countParams);
  }

  $countStmt->execute();
  $total = $countStmt->get_result()->fetch_assoc()['total'];
  $pages = ceil($total / $limit);

  for ($i = 1; $i <= $pages; $i++) {
    echo "<a href='?page=$i&category=" . urlencode($category) . "&search=" . urlencode($search) . "'>$i</a> ";
  }
  ?>
</div>

<!-- Cart Summary -->
<div class="cart-summary">
  <h2>Your Cart</h2>
  <?php include 'cart_summary.php'; ?>
</div>

<!-- Admin Panel -->
<?php if ($isAdmin): ?>
  <div class="admin-panel">
    <h2>Admin Dashboard</h2>
    <ul>
      <li><a href="admin/add_product.php">Add Product</a></li>
      <li><a href="admin/manage_products.php">Manage Products</a></li>
      <li><a href="admin/users.php">View/Delete Users</a></li>
      <li><a href="admin/transactions.php">View Payments</a></li>
    </ul>
  </div>
<?php endif; ?>

</body>
</html>
