<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['department_name']);
    $building = $conn->real_escape_string($_POST['building']);

    $conn->query("
        INSERT INTO Department (DepartmentName, Building)
        VALUES ('$name', '$building')
    ");

    header("Location: manage-departments.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Department</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-departments.php" class="logout">Back to Departments</a>

<div class="dashboard-container">

    <h1>Add Department</h1>

    <div class="form-container">
        <form method="POST">

            <label>Department Name</label>
            <input type="text" name="department_name" required>

            <label>Building</label>
            <input type="text" name="building" required>

            <br>
            <button class="button-primary" type="submit">Add Department</button>

        </form>
    </div>

</div>

</body>
</html>
