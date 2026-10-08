<link rel="stylesheet" href="assets/css/style.css">

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM Users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {

        if (password_verify($password, $user['Password'])) {

            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];

            switch ($user['Role']) {
                case 'admin':
                    header("Location: dashboards/admin/admin.php");
                    break;
                case 'management':
                    header("Location: dashboards/management/management.php");
                    break;
                case 'tutor':
                    header("Location: dashboards/tutor/tutor.php");
                    break;
                case 'student':
                    header("Location: dashboards/student.php");
                    break;
                default:
                    header("Location: dashboard.php");
            }
            exit;
        }
    }

    $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<div class="dashboard-container">

    <h1>Personal Tutor MIS</h1>

    <div class="card" style="max-width: 400px; margin: 40px auto;">

        <h2 style="text-align:center;">Login</h2>

        <?php if (isset($_GET['timeout'])): ?>
            <p class="empty">Your session expired due to inactivity. Please log in again.</p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color: #c0392b; font-weight: bold;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <p><strong>Username</strong></p>
            <input type="text" name="username" required
                   style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">

            <p><strong>Password</strong></p>
            <input type="password" name="password" required
                   style="width:100%; padding:10px; border-radius:6px; border:1px solid #ccc;">

            <button type="submit"
                style="
                    width:100%;
                    margin-top:20px;
                    padding:12px;
                    background: var(--primary-purple);
                    color:white;
                    border:none;
                    border-radius:6px;
                    font-size:16px;
                    cursor:pointer;
                ">
                Login
            </button>

        </form>
    </div>

</div>

</body>
</html>

