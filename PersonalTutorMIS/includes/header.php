<?php if ($_SESSION['role'] === 'admin'): ?>
    <a href="admin.php">Admin Panel</a>
<?php endif; ?>

<?php if ($_SESSION['role'] === 'tutor'): ?>
    <a href="tutor.php">Tutor Portal</a>
<?php endif; ?>

<?php if ($_SESSION['role'] === 'student'): ?>
    <a href="student.php">Student Portal</a>
<?php endif; ?>

<?php if ($_SESSION['role'] === 'management'): ?>
    <a href="manageement.php">Management Dashboard</a>
<?php endif; ?>