<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer/PHPMailer.php';
require 'PHPMailer-master/PHPMailer/SMTP.php';
require 'PHPMailer-master/PHPMailer/Exception.php';
require 'includes/db.php';

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Server Settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // Set your Gmail address and app password
        $mail->Username = 'jam.sacristia@gmail.com'; // Gmail Address
        $mail->Password = 'ockk apur mcfu ayzs';      // App Password

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // From & Recipient
        $mail->setFrom('jam.sacristia@gmail.com', 'WST Store');
        $mail->addAddress($email);

        // Email Content
        $verifyLink = "http://localhost/wsts/verify.php?email=" . urlencode($email) . "&token=" . urlencode($token);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your email address';
        $mail->Body = "Click the link below to verify your email:<br><a href='$verifyLink'>$verifyLink</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Save error to log
        file_put_contents("email_error.log", "Mailer Error: " . $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
        return false;
    }
}

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signUp'])) {
    $firstName = trim($_POST['fName']);
    $lastName = trim($_POST['lName']);
    $email = trim($_POST['email']);
    $passwordRaw = $_POST['password'];

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Invalid email format.";
        exit;
    }

    // Validate Password
    if (strlen($passwordRaw) < 6) {
        echo "❌ Password must be at least 6 characters.";
        exit;
    }

    // Secure Password and Token
    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $role = 'user';

    // Check for Existing Email
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        echo "❌ Email already exists.";
        exit;
    }

    // Insert New User
    $stmt = $conn->prepare("INSERT INTO users (fName, lName, email, password, token, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $token, $role);

    if ($stmt->execute()) {
        if (sendVerificationEmail($email, $token)) {
            echo "✅ Registration successful! Please check your email to verify your account.";
        } else {
            echo "❌ Verification email failed to send. Check `email_error.log`.";
        }
    } else {
        echo "❌ Registration failed. Database error.";
    }
}
?>
