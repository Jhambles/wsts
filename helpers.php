<?php
// includes/helpers.php

function updateUsersXML($conn) {
    $result = $conn->query("SELECT id, fName, lName, email FROM users");

    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users></users>');

    while ($row = $result->fetch_assoc()) {
        $user = $xml->addChild('user');
        $user->addChild('id', $row['id']);
        $user->addChild('fName', htmlspecialchars($row['fName']));
        $user->addChild('lName', htmlspecialchars($row['lName']));
        $user->addChild('email', htmlspecialchars($row['email']));
    }

    $xml->asXML(__DIR__ . '/../data/users.xml');
}
