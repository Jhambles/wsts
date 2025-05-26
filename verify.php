<?php
// Include database connection
require 'includes/db.php';

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    // Prepare and execute the query securely
    $stmt = $conn->prepare("SELECT id, verified FROM users WHERE email = ? AND token = ?");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['verified'] == 0) {
            // Update user to set verified = 1 and clear the token
            $updateStmt = $conn->prepare("UPDATE users SET verified = 1, token = NULL WHERE email = ?");
            $updateStmt->bind_param("s", $email);
            if ($updateStmt->execute()) {
                echo "✅ Your email has been successfully verified. You may now log in.";
            } else {
                echo "❌ Failed to update verification status. Please try again.";
            }
        } else {
            echo "✅ Your email is already verified.";
        }
    } else {
        echo "❌ Invalid verification link or user not found.";
    }
} else {
    echo "❌ Invalid request. Missing email or token.";
}
?>
