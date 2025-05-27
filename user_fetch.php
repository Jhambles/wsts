<?php
session_start();
require 'includes/db.php'; // Include the database connection

// Check if the user is an admin, you might want to ensure they are authorized to view the users
if ($_SESSION['user_role'] == 'admin') {
    echo "You are not authorized to view this page.";
    exit;
}

// Fetch users from the database
$query = "SELECT id, fName, lName, email, role FROM users";
$result = $conn->query($query);

// Check if the query was successful
if ($result && $result->num_rows > 0) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<thead><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody>';

    // Loop through the users and display their details
    while ($user = $result->fetch_assoc()) {
        $id = $user['id'];
        $fName = htmlspecialchars($user['fName']);
        $lName = htmlspecialchars($user['lName']);
        $email = htmlspecialchars($user['email']);
        $role = htmlspecialchars($user['role']);

        echo "<tr id='user-$id'>";
        echo "<td class='view-only'>$id</td>";
        echo "<td class='view-only'>$fName</td>";
        echo "<td class='view-only'>$lName</td>";
        echo "<td class='view-only'>$email</td>";
        echo "<td class='view-only'>$role</td>";

        // Action buttons for editing or deleting
        echo "<td>
                <button onclick='enableEdit(\"user-$id\")'>Edit</button>
                <button onclick='deleteUser(\"$id\")'>Delete</button>
              </td>";

        echo "</tr>";
    }

    echo '</tbody></table>';
} else {
    echo '<p>No users found.</p>';
}

?>
