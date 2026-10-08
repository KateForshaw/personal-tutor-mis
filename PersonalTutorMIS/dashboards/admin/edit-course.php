<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-courses.php");
    exit;
}

$courseID = (int)$_GET['id'];

// Fetch course info
$course = $conn->query("
    SELECT 
        Course.CourseName,
        Course.CourseLevel,
        Course.DepartmentID
    FROM Course
    WHERE CourseID = $courseID
")->fetch_assoc();

if (!$course) {
    header("Location: manage-courses.php");
    exit;
}

// Fetch departments for dropdown
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['course_name']);
    $level = $conn->real_escape_string($_POST['course_level']);
    $dept = (int)$_POST['department'];

    $conn->query("
        UPDATE Course SET
            CourseName = '$name',
            CourseLevel = '$level',
            DepartmentID = $dept
        WHERE CourseID = $courseID
    ");

    header("Location: manage-courses.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-courses.php" class="logout">Back to Courses</a>

<div class="dashboard-container">

    <h1>Edit Course</h1>

    <div class="form-container">
        <form method="POST">

            <label>Course Name</label>
            <input type="text" name="course_name" value="<?= $course['CourseName'] ?>" required>

            <label>Course Level</label>
            <input type="text" name="course_level" value="<?= $course['CourseLevel'] ?>" required>

            <label>Department</label>
            <select name="department" required>
                <?php while ($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['DepartmentID'] ?>" 
                        <?= $d['DepartmentID'] == $course['DepartmentID'] ? 'selected' : '' ?>>
                        <?= $d['DepartmentName'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br>
            <button class="button-primary" type="submit">Save Changes</button>

        </form>
    </div>

</div>

</body>
</html>
