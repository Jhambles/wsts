<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/PHPMailer/PHPMailer.php';
require 'PHPMailer-master/PHPMailer/SMTP.php';
require 'PHPMailer-master/PHPMailer/Exception.php';
require 'includes/db.php';

header('Content-Type: application/json');

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jam.sacristia@gmail.com';
        $mail->Password = 'ockk apur mcfu ayzs'; // Use Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('jam.sacristia@gmail.com', 'WST Store');
        $mail->addAddress($email);

        $verifyLink = "http://localhost/wsts/verify.php?email=" . urlencode($email) . "&token=" . urlencode($token);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your email address';
        $mail->Body = "Click the link below to verify your email:<br><a href='$verifyLink'>$verifyLink</a>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        file_put_contents("email_error.log", "Mailer Error: " . $mail->ErrorInfo . PHP_EOL, FILE_APPEND);
        return false;
    }
}

// Handle AJAX POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = trim($_POST['fName'] ?? '');
    $lastName = trim($_POST['lName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    // Basic Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
        exit;
    }

    if (strlen($passwordRaw) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));
    $role = 'user';

    // Check if email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult && $checkResult->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
        exit;
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO users (fName, lName, email, password, token, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $password, $token, $role);

    if ($stmt->execute()) {
        if (sendVerificationEmail($email, $token)) {
            echo json_encode(['status' => 'success', 'message' => 'Registration successful! Please check your email to verify your account.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Verification email failed to send.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
}
?>

