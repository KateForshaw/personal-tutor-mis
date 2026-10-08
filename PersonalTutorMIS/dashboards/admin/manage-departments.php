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
            Department.DepartmentName LIKE '%$search%' OR
            Department.Building LIKE '%$search%' OR
            DepartmentManager.ManagerName LIKE '%$search%'
        )
    ";
}

// Fetch departments + manager names
$departments = $conn->query("
    SELECT 
        Department.DepartmentID,
        Department.DepartmentName,
        Department.Building,
        DepartmentManager.ManagerName
    FROM Department
    LEFT JOIN DepartmentManager 
        ON Department.DepartmentID = DepartmentManager.DepartmentID
    $where
    ORDER BY Department.DepartmentName ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Departments</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="admin.php" class="logout">Return to Dashboard</a>

<div class="dashboard-container">

    <h1>Manage Departments</h1>

    <!-- SEARCH BAR -->
    <div class="card">
        <form method="GET" style="display:flex; gap:10px;">
            <input 
                type="text" 
                name="search" 
                placeholder="Search departments..."
                value="<?= htmlspecialchars($search) ?>"
                style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px;"
            >
            <button class="button-green" type="submit">Search</button>
        </form>
    </div>

    <!-- ADD DEPARTMENT BUTTON -->
    <div class="card">
        <button class="button-primary" onclick="window.location.href='add-department.php'">
            Add New Department
        </button>
    </div>

    <!-- DEPARTMENT LIST -->
    <div class="card">
        <h2>All Departments</h2>

        <table style="width:100%; border-collapse: collapse;">
            <tr style="background: var(--purple-light);">
                <th style="padding: 10px;">Department Name</th>
                <th style="padding: 10px;">Building</th>
                <th style="padding: 10px;">Manager</th>
                <th style="padding: 10px;">Actions</th>
            </tr>

            <?php while ($d = $departments->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="padding: 10px;"><?= $d['DepartmentName'] ?></td>
                    <td style="padding: 10px;"><?= $d['Building'] ?></td>
                    <td style="padding: 10px;"><?= $d['ManagerName'] ?: '— None Assigned —' ?></td>

                    <td style="padding: 10px;">
                        <button class="button-primary" onclick="window.location.href='edit-department.php?id=<?= $d['DepartmentID'] ?>'">
                            Edit
                        </button>

                        <button class="button-green" onclick="window.location.href='delete-department.php?id=<?= $d['DepartmentID'] ?>'">
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
