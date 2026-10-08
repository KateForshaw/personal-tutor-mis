<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $systemName = $conn->real_escape_string($_POST['system_name']);
    $academicYear = $conn->real_escape_string($_POST['academic_year']);
    $dashboardMessage = $conn->real_escape_string($_POST['dashboard_message']);
    $contactEmail = $conn->real_escape_string($_POST['contact_email']);
    $maxStudents = (int)$_POST['max_students'];
    $maxGroup = (int)$_POST['max_group'];
    $primaryColour = $conn->real_escape_string($_POST['primary_colour']);
    $secondaryColour = $conn->real_escape_string($_POST['secondary_colour']);

    $conn->query("
        UPDATE SystemSettings SET
            SystemName = '$systemName',
            AcademicYear = '$academicYear',
            DashboardMessage = '$dashboardMessage',
            ContactEmail = '$contactEmail',
            MaxStudentsPerTutor = $maxStudents,
            MaxStudentsPerGroup = $maxGroup,
            PrimaryColour = '$primaryColour',
            SecondaryColour = '$secondaryColour'
        WHERE SettingID = 1
    ");

    header("Location: system-settings.php");
    exit;
}
?>
