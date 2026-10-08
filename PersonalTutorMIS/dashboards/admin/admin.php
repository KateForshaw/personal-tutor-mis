<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Dashboard counts
$countUsers = $conn->query("SELECT COUNT(*) AS total FROM Users")->fetch_assoc()['total'];
$countTutors = $conn->query("SELECT COUNT(*) AS total FROM Tutor")->fetch_assoc()['total'];
$countStudents = $conn->query("SELECT COUNT(*) AS total FROM Student")->fetch_assoc()['total'];
$countManagers = $conn->query("SELECT COUNT(*) AS total FROM DepartmentManager")->fetch_assoc()['total'];
$countDepartments = $conn->query("SELECT COUNT(*) AS total FROM Department")->fetch_assoc()['total'];
$countCourses = $conn->query("SELECT COUNT(*) AS total FROM Course")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="../../logout.php" class="logout">Logout</a>

<div class="dashboard-container">

    <h1>Admin Panel</h1>

    <!-- system overview -->
    <div class="card">
    <h2>System Overview</h2>

    <div class="overview-columns">

        <!-- LEFT COLUMN -->
        <div class="overview-column">
            <div class="overview-item">
                <h3>Total Users</h3>
                <p><?= $countUsers ?></p>
            </div>

            <div class="overview-item">
                <h3>Total Tutors</h3>
                <p><?= $countTutors ?></p>
            </div>

            <div class="overview-item">
                <h3>Total Students</h3>
                <p><?= $countStudents ?></p>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="overview-column">
            <div class="overview-item">
                <h3>Total Managers</h3>
                <p><?= $countManagers ?></p>
            </div>

            <div class="overview-item">
                <h3>Departments</h3>
                <p><?= $countDepartments ?></p>
            </div>

            <div class="overview-item">
                <h3>Courses</h3>
                <p><?= $countCourses ?></p>
            </div>
        </div>

    </div>
</div>

    <!-- ADMIN CONTROLS -->
    <div class="card">
        <h2>Administrative Controls</h2>

        <button class="button-primary" onclick="window.location.href='manage-users.php'">
            Manage Users
        </button>

        <button class="button-primary" onclick="window.location.href='manage-departments.php'">
            Manage Departments
        </button>

        <button class="button-primary" onclick="window.location.href='manage-courses.php'">
            Manage Courses
        </button>

        <button class="button-green" onclick="window.location.href='system-settings.php'">
            System Settings
        </button>
    </div>

</div>

</body>
</html>
