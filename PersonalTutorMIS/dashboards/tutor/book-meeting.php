<?php
include '../../auth.php';
requireRole('tutor');
include '../../db.php';

$userId = $_SESSION['user_id'] ?? null;

// Get tutor ID
$tutorStmt = $conn->prepare("SELECT TutorID FROM Tutor WHERE UserID = ?");
$tutorStmt->bind_param("i", $userId);
$tutorStmt->execute();
$tutorId = $tutorStmt->get_result()->fetch_assoc()['TutorID'];

// Get assigned students WITH AllocationID
$students = $conn->query("
    SELECT a.AllocationID, s.Forename, s.Surname
    FROM Allocation a
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = $tutorId
    ORDER BY s.Surname
");

// Get assigned groups
$groups = $conn->query("
    SELECT DISTINCT GroupID
    FROM Allocation
    WHERE TutorID = $tutorId AND GroupID IS NOT NULL
    ORDER BY GroupID
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meetingType = $_POST['meetingType'];
    $allocationId = $_POST['allocationId'] ?? null;
    $groupId = $_POST['groupId'] ?? null;
    $datetime = $_POST['datetime'];
    $inPerson = isset($_POST['inPerson']) ? 1 : 0;
    $required = isset($_POST['required']) ? 1 : 0;

    // Insert meeting
    $stmt = $conn->prepare("
        INSERT INTO ScheduledMeeting (AllocationID, GroupID, MeetingType, ScheduledDateTime, InPerson, Required)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iissii", $allocationId, $groupId, $meetingType, $datetime, $inPerson, $required);
    $stmt->execute();

    header("Location: tutor.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Meeting</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="form-container">

    <h1>Book a Meeting</h1>
    <a href="tutor.php" class="button-secondary">← Back to Dashboard</a>

    <form method="POST">

        <label>Meeting Type</label>
        <select name="meetingType" required>
            <option value="individual">1:1 Meeting</option>
            <option value="group">Group Meeting</option>
        </select>

        <label>Student (for 1:1)</label>
        <select name="allocationId">
            <option value="">-- Select Student --</option>
            <?php while ($s = $students->fetch_assoc()): ?>
                <option value="<?= $s['AllocationID'] ?>">
                    <?= htmlspecialchars($s['Forename'] . ' ' . $s['Surname']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Group (for Group Meeting)</label>
        <select name="groupId">
            <option value="">-- Select Group --</option>
            <?php while ($g = $groups->fetch_assoc()): ?>
                <option value="<?= $g['GroupID'] ?>">Group <?= $g['GroupID'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Date & Time</label>
        <input type="datetime-local" name="datetime" required>

        <label><input type="checkbox" name="inPerson"> In Person Meeting</label>
        <label><input type="checkbox" name="required"> Required Meeting</label>

        <button class="button-primary">Create Meeting</button>
    </form>
</div>

</body>
</html>
