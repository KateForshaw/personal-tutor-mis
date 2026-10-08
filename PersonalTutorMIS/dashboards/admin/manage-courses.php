<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "";
if ($search !== "") {
    $where = "
        WHERE (
            Course.CourseName LIKE '%$search%' OR
            Course.CourseLevel LIKE '%$search%' OR
            Department.DepartmentName LIKE '%$search%'
        )
    ";
}

// Fetch courses with department names
$courses = $conn->query("
    SELECT 
        Course.CourseID,
        Course.CourseName,
        Course.CourseLevel,
        Department.DepartmentName
    FROM Course
    LEFT JOIN Department 
        ON Course.DepartmentID = Department.DepartmentID
    $where
    ORDER BY Course.CourseName ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="admin.php" class="logout">Return to Dashboard</a>

<div class="dashboard-container">

    <h1>Manage Courses</h1>

    <!-- SEARCH BAR -->
    <div class="card">
        <form method="GET" style="display:flex; gap:10px;">
            <input 
                type="text" 
                name="search" 
                placeholder="Search courses..."
                value="<?= htmlspecialchars($search) ?>"
                style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;"
            >
            <button class="button-green" type="submit">Search</button>
        </form>
    </div>

    <!-- ADD COURSE BUTTON -->
    <div class="card">
        <button class="button-primary" onclick="window.location.href='add-course.php'">
            Add New Course
        </button>
    </div>

    <!-- COURSE LIST -->
    <div class="card">
        <h2>All Courses</h2>

        <table style="width:100%; border-collapse: collapse;">
            <tr style="background: var(--purple-light);">
                <th style="padding: 10px;">Course Name</th>
                <th style="padding: 10px;">Level</th>
                <th style="padding: 10px;">Department</th>
                <th style="padding: 10px;">Actions</th>
            </tr>

            <?php while ($c = $courses->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?= $c['CourseName'] ?></td>
                    <td style="padding: 10px;"><?= $c['CourseLevel'] ?></td>
                    <td style="padding: 10px;"><?= $c['DepartmentName'] ?></td>

                    <td style="padding: 10px;">
                        <button class="button-primary" onclick="window.location.href='edit-course.php?id=<?= $c['CourseID'] ?>'">
                            Edit
                        </button>

                        <button class="button-green" onclick="window.location.href='delete-course.php?id=<?= $c['CourseID'] ?>'">
                            Delete
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>

        </table>
    </div>

</div>

</body>
</html>
