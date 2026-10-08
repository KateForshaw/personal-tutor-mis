<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-departments.php");
    exit;
}

$deptID = (int)$_GET['id'];

// Fetch department info
$dept = $conn->query("
    SELECT 
        Department.DepartmentName,
        Department.Building,
        DepartmentManager.ManagerName
    FROM Department
    LEFT JOIN DepartmentManager 
        ON Department.DepartmentID = DepartmentManager.DepartmentID
    WHERE Department.DepartmentID = $deptID
")->fetch_assoc();

if (!$dept) {
    header("Location: manage-departments.php");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['department_name']);
    $building = $conn->real_escape_string($_POST['building']);

    $conn->query("
        UPDATE Department SET
            DepartmentName = '$name',
            Building = '$building'
        WHERE DepartmentID = $deptID
    ");

    header("Location: manage-departments.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Department</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-departments.php" class="logout">Back to Departments</a>

<div class="dashboard-container">

    <h1>Edit Department</h1>

    <div class="form-container">
        <form method="POST">

            <label>Department Name</label>
            <input type="text" name="department_name" value="<?= $dept['DepartmentName'] ?>" required>

            <label>Building</label>
            <input type="text" name="building" value="<?= $dept['Building'] ?>" required>

            <p><strong>Current Manager:</strong> <?= $dept['ManagerName'] ?: '— None Assigned —' ?></p>

            <br>
            <button class="button-primary" type="submit">Save Changes</button>

        </form>
    </div>

</div>

</body>
</html>
