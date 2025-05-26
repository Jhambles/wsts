<?php
$conn = new mysqli("localhost", "root", "", "wst");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
