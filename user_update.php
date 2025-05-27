<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $fName = $_POST['fName'];
    $lName = $_POST['lName'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $xml = simplexml_load_file('includes/users.xml');

    foreach ($xml->user as $user) {
        if ((string)$user['id'] === $id) {
            $user->fName = $fName;
            $user->lName = $lName;
            $user->email = $email;
            $user->role = $role;
            break;
        }
    }

    $xml->asXML('includes/users.xml');
    echo "✅ User updated successfully.";
}
?>
