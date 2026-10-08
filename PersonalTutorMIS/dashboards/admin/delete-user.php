<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if (!isset($_GET['id'])) {
    header("Location: manage-users.php");
    exit;
}

$userID = (int)$_GET['id'];

// Fetch the user's role
$user = $conn->query("SELECT Role FROM Users WHERE UserID = $userID")->fetch_assoc();

if (!$user) {
    header("Location: manage-users.php");
    exit;
}

$role = $user['Role'];

// If the admin confirms deletion
if (isset($_POST['confirm'])) {

    // ============================
    // DELETE STUDENT + ALLOCATIONS
    // ============================
    if ($role === 'student') {

        // Delete allocations involving this student
        $conn->query("
            DELETE FROM Allocation 
            WHERE StudentID IN (
                SELECT StudentID FROM Student WHERE UserID = $userID
            )
        ");

        // Delete student record
        $conn->query("DELETE FROM Student WHERE UserID = $userID");
    }

    // ============================
    // DELETE TUTOR + ALLOCATIONS
    // ============================
    elseif ($role === 'tutor') {

        // Delete allocations involving this tutor
        $conn->query("
            DELETE FROM Allocation 
            WHERE TutorID IN (
                SELECT TutorID FROM Tutor WHERE UserID = $userID
            )
        ");

        // Delete tutor record
        $conn->query("DELETE FROM Tutor WHERE UserID = $userID");
    }

    // ============================
    // DELETE MANAGER
    // ============================
    elseif ($role === 'management') {

        $conn->query("DELETE FROM DepartmentManager WHERE UserID = $userID");
    }

    // ============================
    // DELETE USER ACCOUNT
    // ============================
    $conn->query("DELETE FROM Users WHERE UserID = $userID");

    header("Location: manage-users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete User</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="manage-users.php" class="logout">Cancel</a>

<div class="dashboard-container">

    <h1>Delete User</h1>

    <div class="card">
        <p>Are you sure you want to delete this user?</p>
        <p><strong>This action cannot be undone.</strong></p>

        <form method="POST">
            <button class="button-green" type="submit" name="confirm">Yes, Delete User</button>
        </form>
    </div>

</div>

</body>
</html>
