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

// Get meetings belonging to this tutor
$meetings = $conn->query("
    SELECT sm.MeetingID, sm.MeetingType, sm.ScheduledDateTime,
           s.Forename, s.Surname, sm.GroupID
    FROM ScheduledMeeting sm
    LEFT JOIN Allocation a ON sm.AllocationID = a.AllocationID
    LEFT JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = $tutorId
       OR sm.GroupID IN (SELECT GroupID FROM Allocation WHERE TutorID = $tutorId)
    ORDER BY sm.ScheduledDateTime DESC
");

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meetingId = $_POST['meetingId'];
    $status = $_POST['status'];
    $topic = $_POST['topic'];
    $notes = $_POST['notes'];
    $duration = $_POST['duration'];
    $referralMade = isset($_POST['referralMade']) ? 1 : 0;

    $stmt = $conn->prepare("
        INSERT INTO MeetingLog (MeetingID, MeetingStatus, MeetingTopic, MeetingNotes, DurationMinutes, ReferralMade)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssii", $meetingId, $status, $topic, $notes, $duration, $referralMade);
    $stmt->execute();

    header("Location: tutor.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Log Meeting</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="form-container">
    <h1>Log a Meeting</h1>
    <a href="tutor.php" class="button-secondary">← Back to Dashboard</a>

    <form method="POST">

        <label>Select Meeting</label>
        <select name="meetingId" required>
            <option value="">-- Select Meeting --</option>
            <?php while ($m = $meetings->fetch_assoc()): ?>
                <option value="<?= $m['MeetingID'] ?>">
                    <?= htmlspecialchars($m['MeetingType']) ?> —
                    <?php if ($m['Forename']): ?>
                        <?= htmlspecialchars($m['Forename'] . ' ' . $m['Surname']) ?>
                    <?php else: ?>
                        Group <?= $m['GroupID'] ?>
                    <?php endif; ?>
                    (<?= $m['ScheduledDateTime'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label>Status</label>
        <select name="status" required>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Rescheduled">Rescheduled</option>
        </select>

        <label>Meeting Topic</label>
        <input type="text" name="topic" required>

        <label>Notes</label>
        <textarea name="notes" rows="5" required></textarea>

        <label>Duration (minutes)</label>
        <input type="number" name="duration" min="1" required>

        <label>
            <input type="checkbox" name="referralMade"> Referral Made
        </label>

        <button class="button-green">Save Log</button>
    </form>
</div>

</body>
</html>
