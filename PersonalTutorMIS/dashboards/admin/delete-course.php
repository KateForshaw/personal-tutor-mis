<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-courses.php");
    exit;
}

$courseID = (int)$_GET['id'];

// Check if students are enrolled in this course
$students = $conn->query("
    SELECT StudentID FROM Student WHERE CourseID = $courseID
");

// If confirmed and safe to delete
if (isset($_POST['confirm']) && $students->num_rows == 0) {

    // Delete the course
    $conn->query("DELETE FROM Course WHERE CourseID = $courseID");

    header("Location: manage-courses.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Course</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-courses.php" class="logout">Cancel</a>

<div class="dashboard-container">

    <h1>Delete Course</h1>

    <div class="card">

        <?php if ($students->num_rows > 0): ?>

            <p><strong>This course cannot be deleted.</strong></p>
            <p>There are students enrolled in this course.</p>
            <p>Please reassign or delete those students first.</p>

        <?php else: ?>

            <p>Are you sure you want to delete this course?</p>
            <p><strong>This action cannot be undone.</strong></p>

            <form method="POST">
                <button class="button-green" type="submit" name="confirm">Yes, Delete Course</button>
            </form>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
