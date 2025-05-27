<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $xml = simplexml_load_file('includes/users.xml');
    $index = 0;

    foreach ($xml->user as $user) {
        if ((string)$user['id'] === $id) {
            unset($xml->user[$index]);
            $xml->asXML('includes/users.xml');
            echo "✅ User deleted.";
            exit;
        }
        $index++;
    }

    echo "❌ User not found.";
}
?>
