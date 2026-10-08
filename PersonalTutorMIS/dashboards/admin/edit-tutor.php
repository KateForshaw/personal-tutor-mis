<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$userID = (int)$_GET['id'];

// Fetch tutor data
$tutor = $conn->query("
    SELECT 
        Tutor.TutorID,
        Tutor.TutorName,
        Tutor.StaffEmail,
        Tutor.DepartmentID,
        Users.Username
    FROM Tutor
    INNER JOIN Users ON Tutor.UserID = Users.UserID
    WHERE Tutor.UserID = $userID
")->fetch_assoc();

if (!$tutor) {
    header("Location: manage-users.php");
    exit;
}

// Fetch departments
$departments = $conn->query("SELECT DepartmentID, DepartmentName FROM Department ORDER BY DepartmentName");

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $conn->real_escape_string($_POST['tutor_name']);
    $email = $conn->real_escape_string($_POST['staff_email']);
    $dept = (int)$_POST['department'];

    $conn->query("
        UPDATE Tutor SET
            TutorName = '$name',
            StaffEmail = '$email',
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
    <title>Edit Tutor</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-users.php" class="logout">Back to Users</a>

<div class="dashboard-container">

    <h1>Edit Tutor</h1>

    <div class="form-container">
    <form method="POST">

        <label for="tutor_name">Tutor Name</label>
        <input type="text" id="tutor_name" name="tutor_name" value="<?= $tutor['TutorName'] ?>" required>

        <label for="staff_email">Staff Email</label>
        <input type="email" id="staff_email" name="staff_email" value="<?= $tutor['StaffEmail'] ?>" required>

        <label for="department">Department</label>
        <select id="department" name="department" required>
            <?php while ($d = $departments->fetch_assoc()): ?>
                <option value="<?= $d['DepartmentID'] ?>" 
                    <?= $d['DepartmentID'] == $tutor['DepartmentID'] ? 'selected' : '' ?>>
                    <?= $d['DepartmentName'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button class="button-primary" type="submit">Save Changes</button>

    </form>
</div>


</div>

</body>
</html>
