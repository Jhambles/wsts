<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access Denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $adminPass = $_POST['admin_password'];

    if (password_verify($adminPass, $_SESSION['admin_hashed_password'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    } else {
        echo "Invalid admin password.";
    }
}

$result = $conn->query("SELECT id, fName, lName, email FROM users");
while ($row = $result->fetch_assoc()) {
    echo "<form method='post'>
        <input type='hidden' name='user_id' value='{$row['id']}'>
        {$row['fName']} {$row['lName']} ({$row['email']})
        <input type='password' name='admin_password' placeholder='Admin Password'>
        <input type='submit' value='Delete'>
    </form><hr>";
}
?>