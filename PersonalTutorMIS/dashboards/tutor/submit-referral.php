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

// Get meeting logs belonging to this tutor
$logs = $conn->query("
    SELECT ml.LogID, ml.MeetingTopic, ml.MeetingStatus,
           s.Forename, s.Surname, sm.GroupID
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    LEFT JOIN Allocation a ON sm.AllocationID = a.AllocationID
    LEFT JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = $tutorId
       OR sm.GroupID IN (SELECT GroupID FROM Allocation WHERE TutorID = $tutorId)
    ORDER BY ml.LogID DESC
");

// Get support services
$services = $conn->query("
    SELECT ServiceID, ServiceName
    FROM SupportService
    ORDER BY ServiceName
");

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logId = $_POST['logId'];
    $serviceId = $_POST['serviceId'];
    $type = $_POST['type'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("
        INSERT INTO Referral (LogID, ServiceID, ReferralType, ReferralReason, ReferralStatus)
        VALUES (?, ?, ?, ?, 'Open')
    ");
    $stmt->bind_param("iiss", $logId, $serviceId, $type, $reason);
    $stmt->execute();

    header("Location: tutor.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Referral</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="form-container">
    <h1>Submit a Referral</h1>
    <a href="tutor.php" class="button-secondary">← Back to Dashboard</a>

    <form method="POST">

        <label>Select Meeting Log</label>
        <select name="logId" required>
            <option value="">-- Select Log --</option>
            <?php while ($l = $logs->fetch_assoc()): ?>
                <option value="<?= $l['LogID'] ?>">
                    <?php if ($l['Forename']): ?>
                        <?= htmlspecialchars($l['Forename'] . ' ' . $l['Surname']) ?>
                    <?php else: ?>
                        Group <?= $l['GroupID'] ?>
                    <?php endif; ?>
                    — <?= htmlspecialchars($l['MeetingTopic']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Support Service</label>
        <select name="serviceId" required>
            <option value="">-- Select Service --</option>
            <?php while ($s = $services->fetch_assoc()): ?>
                <option value="<?= $s['ServiceID'] ?>">
                    <?= htmlspecialchars($s['ServiceName']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Referral Type</label>
        <input type="text" name="type" required>

        <label>Referral Reason</label>
        <textarea name="reason" rows="5" required></textarea>

        <button class="button-primary">Submit Referral</button>
    </form>
</div>

</body>
</html>
