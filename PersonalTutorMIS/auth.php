<?php
session_start();

// Timeout in seconds (1800 = 30 minutes)
$timeout_duration = 1800;

// Check if user has been inactive too long
if (isset($_SESSION['LAST_ACTIVITY']) &&
    (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {

    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit;
}

// Update last activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();

// Role-based access control
function requireRole($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}
