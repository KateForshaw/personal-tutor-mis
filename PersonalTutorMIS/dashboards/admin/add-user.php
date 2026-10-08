<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($_POST['role']);

    // Insert into Users table
    $conn->query("
        INSERT INTO Users (Username, Password, Role)
        VALUES ('$username', '$password', '$role')
    ");

    $newUserID = $conn->insert_id;

    // Insert into role-specific table
    if ($role === 'student') {

        $forename = $conn->real_escape_string($_POST['forename']);
        $surname = $conn->real_escape_string($_POST['surname']);
        $email = $conn->real_escape_string($_POST['student_email']);
        $course = (int)$_POST['course'];
        $year = (int)$_POST['year'];
        $dob = $conn->real_escape_string($_POST['dob']);

        $conn->query("
            INSERT INTO Student (UserID, CourseID, Forename, Surname, StudentEmail, DateOfBirth, YearOfStudy)
            VALUES ($newUserID, $course, '$forename', '$surname', '$email', '$dob', $year)
        ");

    } elseif ($role === 'tutor') {

        $tutorName = $conn->real_escape_string($_POST['tutor_name']);
        $email = $conn->real_escape_string($_POST['staff_email']);
        $dept = (int)$_POST['department'];

        $conn->query("
            INSERT INTO Tutor (UserID, DepartmentID, TutorName, StaffEmail)
            VALUES ($newUserID, $dept, '$tutorName', '$email')
        ");

    } elseif ($role === 'management') {

        $managerName = $conn->real_escape_string($_POST['manager_name']);
        $email = $conn->real_escape_string($_POST['manager_email']);
        $dept = (int)$_POST['department'];

        $conn->query("
            INSERT INTO DepartmentManager (UserID, DepartmentID, ManagerName, ManagerEmail)
            VALUES ($newUserID, $dept, '$managerName', '$email')
        ");
    }

    header("Location: manage-users.php");
    exit;
}

// Fetch departments + courses for dropdowns
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");
$courses = $conn->query("SELECT CourseID, CourseName FROM Course ORDER BY CourseName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script>
        function showRoleFields() {
            let role = document.getElementById('role').value;

            document.getElementById('student-fields').style.display = (role === 'student') ? 'block' : 'none';
            document.getElementById('tutor-fields').style.display = (role === 'tutor') ? 'block' : 'none';
            document.getElementById('manager-fields').style.display = (role === 'management') ? 'block' : 'none';
        }
    </script>
</head>
<body>

<a href="manage-users.php" class="logout">Back to Users</a>

<div class="dashboard-container">

    <h1>Add New User</h1>

    <div class="form-container">
        <form method="POST">

            <h3>Account Details</h3>

            <label>Username</label>
            <input type="text" name="username" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <label>Role</label>
            <select name="role" id="role" onchange="showRoleFields()" required>
                <option value="">Select role...</option>
                <option value="student">Student</option>
                <option value="tutor">Tutor</option>
                <option value="management">Management</option>
                <option value="admin">Admin</option>
            </select>

            <!-- STUDENT FIELDS -->
            <div id="student-fields" style="display:none; margin-top:20px;">
                <h3>Student Details</h3>

                <label>Forename</label>
                <input type="text" name="forename">

                <label>Surname</label>
                <input type="text" name="surname">

                <label>Email</label>
                <input type="email" name="student_email">

                <label>Date of Birth</label>
                <input type="date" name="dob">

                <label>Year of Study</label>
                <input type="number" name="year" min="1" max="5">

                <label>Course</label>
                <select name="course">
                    <?php while ($c = $courses->fetch_assoc()): ?>
                        <option value="<?= $c['CourseID'] ?>"><?= $c['CourseName'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- TUTOR FIELDS -->
            <div id="tutor-fields" style="display:none; margin-top:20px;">
                <h3>Tutor Details</h3>

                <label>Tutor Name</label>
                <input type="text" name="tutor_name">

                <label>Staff Email</label>
                <input type="email" name="staff_email">

                <label>Department</label>
                <select name="department">
                    <?php while ($d = $departments->fetch_assoc()): ?>
                        <option value="<?= $d['DepartmentID'] ?>"><?= $d['DepartmentName'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- MANAGER FIELDS -->
            <div id="manager-fields" style="display:none; margin-top:20px;">
                <h3>Manager Details</h3>

                <label>Manager Name</label>
                <input type="text" name="manager_name">

                <label>Manager Email</label>
                <input type="email" name="manager_email">

                <label>Department</label>
                <select name="department">
                    <?php
                    // reload departments for this block
                    $departments2 = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");
                    while ($d = $departments2->fetch_assoc()):
                    ?>
                        <option value="<?= $d['DepartmentID'] ?>"><?= $d['DepartmentName'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <br>
            <button class="button-primary" type="submit">Create User</button>

        </form>
    </div>

</div>

</body>
</html>
