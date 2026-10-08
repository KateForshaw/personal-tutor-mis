<?php
include 'db.php';

// Get all users
$result = $conn->query("SELECT UserID FROM Users");

while ($row = $result->fetch_assoc()) {
    $userID = $row['UserID'];

    // Build the original password
    $plainPassword = "test" . $userID;

    // Hash it
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Update the database
    $stmt = $conn->prepare("UPDATE Users SET Password = ? WHERE UserID = ?");
    $stmt->bind_param("si", $hashedPassword, $userID);
    $stmt->execute();
}

echo "All passwords updated successfully.";
?>
