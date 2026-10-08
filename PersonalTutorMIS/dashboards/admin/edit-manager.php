<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$userID = (int)$_GET['id'];

// Fetch manager data
$manager = $conn->query("
    SELECT 
        DepartmentManager.ManagerID,
        DepartmentManager.ManagerName,
        DepartmentManager.ManagerEmail,
        DepartmentManager.DepartmentID,
        Users.Username
    FROM DepartmentManager
    INNER JOIN Users ON DepartmentManager.UserID = Users.UserID
    WHERE DepartmentManager.UserID = $userID
")->fetch_assoc();

if (!$manager) {
    header("Location: manage-users.php");
    exit;
}

// Fetch departments
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['manager_name']);
    $email = $conn->real_escape_string($_POST['manager_email']);
    $dept = (int)$_POST['department'];

    $conn->query("
        UPDATE DepartmentManager SET
            ManagerName = '$name',
            ManagerEmail = '$email',
            DepartmentID = $dept
        WHERE UserID = $userID
    ");

    header("Location: manage-users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Manager</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-users.php" class="logout">Back to Users</a>

<div class="dashboard-container">

    <h1>Edit Manager</h1>

    <div class="form-container">
        <form method="POST">

            <label>Manager Name</label>
            <input type="text" name="manager_name" value="<?= $manager['ManagerName'] ?>" required>

            <label>Manager Email</label>
            <input type="email" name="manager_email" value="<?= $manager['ManagerEmail'] ?>" required>

            <label>Department</label>
            <select name="department">
                <?php while ($d = $departments->fetch_assoc()): ?>
                    <option value="<?= $d['DepartmentID'] ?>" 
                        <?= $d['DepartmentID'] == $manager['DepartmentID'] ? 'selected' : '' ?>>
                        <?= $d['DepartmentName'] ?>
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
