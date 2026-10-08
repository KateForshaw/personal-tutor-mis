<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Fetch departments for dropdown
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['course_name']);
    $level = $conn->real_escape_string($_POST['course_level']);
    $dept = (int)$_POST['department'];

    $conn->query("
        INSERT INTO Course (DepartmentID, CourseName, CourseLevel)
        VALUES ($dept, '$name', '$level')
    ");

    header("Location: manage-courses.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Course</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-courses.php" class="logout">Back to Courses</a>

<div class="dashboard-container">

    <h1>Add Course</h1>

    <div class="form-container">
        <form method="POST">

            <label>Course Name</label>
            <input type="text" name="course_name" required>

            <label>Course Level</label>
            <input type="text" name="course_level" required>

            <label>Department</label>
            <select name="department" required>
                <?php while ($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['DepartmentID'] ?>"><?= $d['DepartmentName'] ?></option>
                <?php endwhile; ?>
            </select>

            <br>
            <button class="button-primary" type="submit">Add Course</button>

        </form>
    </div>

</div>

</body>
</html>
