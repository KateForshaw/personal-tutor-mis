<?php
$host = "localhost";
$user = "root";
$pass = "root"; 
$db   = "personal_tutor_mis";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
