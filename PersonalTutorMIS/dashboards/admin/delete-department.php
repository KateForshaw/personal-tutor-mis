<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-departments.php");
    exit;
}

$deptID = (int)$_GET['id'];

// OPTIONAL SAFETY CHECK — prevent deleting a department with tutors
$checkTutors = $conn->query("
    SELECT TutorID FROM Tutor WHERE DepartmentID = $deptID
");

if (isset($_POST['confirm'])) {

    // Delete manager assignment
    $conn->query("DELETE FROM DepartmentManager WHERE DepartmentID = $deptID");

    // Delete department
    $conn->query("DELETE FROM Department WHERE DepartmentID = $deptID");

    header("Location: manage-departments.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Department</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-departments.php" class="logout">Cancel</a>

<div class="dashboard-container">

    <h1>Delete Department</h1>

    <div class="card">

        <?php if ($checkTutors->num_rows > 0): ?>

            <p><strong>This department cannot be deleted.</strong></p>
            <p>There are tutors assigned to this department.</p>
            <p>Please reassign or delete those tutors first.</p>

        <?php else: ?>

            <p>Are you sure you want to delete this department?</p>
            <p><strong>This action cannot be undone.</strong></p>

            <form method="POST">
                <button class="button-green" type="submit" name="confirm">Yes, Delete Department</button>
            </form>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
