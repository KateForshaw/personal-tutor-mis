<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$userID = (int)$_GET['id'];

// Fetch student data
$student = $conn->query("
    SELECT 
        Student.StudentID,
        Student.Forename,
        Student.Surname,
        Student.StudentEmail,
        Student.DateOfBirth,
        Student.YearOfStudy,
        Student.CourseID,
        Users.Username
    FROM Student
    INNER JOIN Users ON Student.UserID = Users.UserID
    WHERE Student.UserID = $userID
")->fetch_assoc();

if (!$student) {
    header("Location: manage-users.php");
    exit;
}

// Fetch courses
$courses = $conn->query("SELECT CourseID, CourseName FROM Course ORDER BY CourseName");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $forename = $conn->real_escape_string($_POST['forename']);
    $surname = $conn->real_escape_string($_POST['surname']);
    $email = $conn->real_escape_string($_POST['student_email']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $year = (int)$_POST['year'];
    $course = (int)$_POST['course'];

    $conn->query("
        UPDATE Student SET
            Forename = '$forename',
            Surname = '$surname',
            StudentEmail = '$email',
            DateOfBirth = '$dob',
            YearOfStudy = $year,
            CourseID = $course
        WHERE UserID = $userID
    ");

    header("Location: manage-users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-users.php" class="logout">Back to Users</a>

<div class="dashboard-container">

    <h1>Edit Student</h1>

    <div class="form-container">
        <form method="POST">

            <label>Forename</label>
            <input type="text" name="forename" value="<?= $student['Forename'] ?>" required>

            <label>Surname</label>
            <input type="text" name="surname" value="<?= $student['Surname'] ?>" required>

            <label>Email</label>
            <input type="email" name="student_email" value="<?= $student['StudentEmail'] ?>" required>

            <label>Date of Birth</label>
            <input type="date" name="dob" value="<?= $student['DateOfBirth'] ?>" required>

            <label>Year of Study</label>
            <input type="number" name="year" min="1" max="5" value="<?= $student['YearOfStudy'] ?>" required>

            <label>Course</label>
            <select name="course">
                <?php while ($c = $courses->fetch_assoc()): ?>
                    <option value="<?= $c['CourseID'] ?>" 
                        <?= $c['CourseID'] == $student['CourseID'] ? 'selected' : '' ?>>
                        <?= $c['CourseName'] ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <br><br>
            <button class="button-primary" type="submit">Save Changes</button>

        </form>
    </div>

</div>

</body>
</html>
