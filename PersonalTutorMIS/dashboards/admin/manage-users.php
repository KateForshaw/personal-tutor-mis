<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

$where = "";
if ($search !== "") {
    $where = "
        WHERE(
            Users.Username LIKE '%$search%' OR
            Student.Forename LIKE '%$search%' OR
            Student.Surname LIKE '%$search%' OR
            Student.StudentEmail LIKE '%$search%' OR
            Tutor.TutorName LIKE '%$search%' OR
            Tutor.StaffEmail LIKE '%$search%' OR
            DepartmentManager.ManagerName LIKE '%$search%' OR
            DepartmentManager.ManagerEmail LIKE '%$search%'
            )
    ";
}

// Fetch all users with role-specific details
$users = $conn->query("
    SELECT 
        Users.UserID,
        Users.Username,
        Users.Role,

        Student.Forename,
        Student.Surname,
        Student.StudentEmail,

        Tutor.TutorName,
        Tutor.StaffEmail,

        DepartmentManager.ManagerName,
        DepartmentManager.ManagerEmail

    FROM Users
    LEFT JOIN Student ON Users.UserID = Student.UserID
    LEFT JOIN Tutor ON Users.UserID = Tutor.UserID
    LEFT JOIN DepartmentManager ON Users.UserID = DepartmentManager.UserID

    $where
    ORDER BY Users.Username ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="admin.php" class="logout">Return to Dashboard</a>

<div class="dashboard-container">

    <h1>Manage Users</h1>

    <!-- SEARCH BAR -->
    <div class="card">
        <form method="GET" style="display:flex; gap:10px;">
            <input 
                type="text" 
                name="search" 
                placeholder="Search users by name, email, or username..."
                value="<?= htmlspecialchars($search) ?>"
                style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;"
            >
            <button class="button-green" type="submit">Search</button>
        </form>
    </div>

    <!-- ADD USER BUTTON -->
    <div class="card">
        <button class="button-primary" onclick="window.location.href='add-user.php'">
            Add New User
        </button>
    </div>

    <!-- USER LIST -->
    <div class="card">
        <h2>All Users</h2>

        <table style="width:100%; border-collapse: collapse;">
            <tr style="background: var(--purple-light);">
                <th style="padding: 10px;">Name</th>
                <th style="padding: 10px;">Email</th>
                <th style="padding: 10px;">Role</th>
                <th style="padding: 10px;">Actions</th>
            </tr>

            <?php while ($u = $users->fetch_assoc()): ?>

                <?php
                // Determine name + email based on role
                if ($u['Role'] === 'student') {
                    $name = $u['Forename'] . " " . $u['Surname'];
                    $email = $u['StudentEmail'];
                }
                elseif ($u['Role'] === 'tutor') {
                    $name = $u['TutorName'];
                    $email = $u['StaffEmail'];
                }
                elseif ($u['Role'] === 'management') {
                    $name = $u['ManagerName'];
                    $email = $u['ManagerEmail'];
                }
                else {
                    $name = $u['Username'];
                    $email = "N/A";
                }
                ?>

                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?= $name ?></td>
                    <td style="padding: 10px;"><?= $email ?></td>
                    <td style="padding: 10px;"><?= $u['Role'] ?></td>

                    <td style="padding: 10px;">

                        <?php if ($u['Role'] === 'student'): ?>
                            <button class="button-primary" onclick="window.location.href='edit-student.php?id=<?= $u['UserID'] ?>'">
                                Edit Student
                            </button>

                        <?php elseif ($u['Role'] === 'tutor'): ?>
                            <button class="button-primary" onclick="window.location.href='edit-tutor.php?id=<?= $u['UserID'] ?>'">
                                Edit Tutor
                            </button>

                        <?php elseif ($u['Role'] === 'management'): ?>
                            <button class="button-primary" onclick="window.location.href='edit-manager.php?id=<?= $u['UserID'] ?>'">
                                Edit Manager
                            </button>

                        <?php else: ?>
                            <button class="button-primary" disabled>No Edit Form</button>
                        <?php endif; ?>

                        <button class="button-green" onclick="window.location.href='delete-user.php?id=<?= $u['UserID'] ?>'">
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
