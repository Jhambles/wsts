<?php
session_start();
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signIn'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, fName, lName, password, verified, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password'])) {
            echo "❌ Incorrect password.";
            exit;
        }

        if (!$user['verified']) {
            echo "⚠️ Please verify your email before logging in.";
            exit;
        }

        // ✅ Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['fName'];
        $_SESSION['user_role'] = $user['role'];

        // ✅ Role-based redirect
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        echo "❌ No account found with that email.";
    }
} else {
    echo "Invalid request.";
}
?>
