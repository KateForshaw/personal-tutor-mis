<?php
include '../../auth.php';
requireRole('management');
include '../../db.php';

// Session already started in auth.php

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: ../../login.php");
    exit;
}

/* =========================
   GET MANAGER DEPARTMENT
   ========================= */

$stmt = $conn->prepare("
    SELECT dm.DepartmentID, d.DepartmentName
    FROM DepartmentManager dm
    JOIN Department d ON dm.DepartmentID = d.DepartmentID
    WHERE dm.UserID = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$deptRow = $stmt->get_result()->fetch_assoc();

if (!$deptRow) {
    echo "No department assigned for this manager.";
    exit;
}

$departmentId = (int)$deptRow['DepartmentID'];
$departmentName = $deptRow['DepartmentName'];

/* =========================
   COMPLIANCE OVERVIEW
   ========================= */

// TUTOR required meetings (ALL types)
$tutorReqStmt = $conn->prepare("
    SELECT 
        ROUND(
            SUM(CASE WHEN ml.MeetingStatus = 'completed' THEN 1 ELSE 0 END)
            / COUNT(sm.MeetingID) * 100, 1
        ) AS TutorRequiredCompletion
    FROM ScheduledMeeting sm
    LEFT JOIN MeetingLog ml ON sm.MeetingID = ml.MeetingID
    LEFT JOIN Allocation a ON sm.AllocationID = a.AllocationID
    LEFT JOIN GroupAllocation ga ON sm.GroupID = ga.GroupID
    LEFT JOIN Tutor t 
        ON t.TutorID = COALESCE(a.TutorID, ga.TutorID)
    WHERE t.DepartmentID = ?
      AND sm.Required = 1
");

$tutorReqStmt->bind_param("i", $departmentId);
$tutorReqStmt->execute();
$tutorRequiredCompletion = $tutorReqStmt->get_result()->fetch_assoc()['TutorRequiredCompletion'] ?? 0;

// STUDENT required meetings (INDIVIDUAL only)
$studentReqStmt = $conn->prepare("
    SELECT 
        ROUND(
            SUM(CASE WHEN ml.MeetingStatus = 'completed' THEN 1 ELSE 0 END)
            / COUNT(sm.MeetingID) * 100, 1
        ) AS StudentRequiredCompletion
    FROM ScheduledMeeting sm
    LEFT JOIN MeetingLog ml ON sm.MeetingID = ml.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
      AND sm.Required = 1
      AND sm.GroupID IS NULL
      AND sm.MeetingType = 'individual'
");
$studentReqStmt->bind_param("i", $departmentId);
$studentReqStmt->execute();
$studentRequiredCompletion = $studentReqStmt->get_result()->fetch_assoc()['StudentRequiredCompletion'] ?? 0;

// GROUP required meetings (GROUP only)
$groupReqStmt = $conn->prepare("
    SELECT 
        ROUND(
            SUM(CASE WHEN ml.MeetingStatus = 'completed' THEN 1 ELSE 0 END)
            / COUNT(sm.MeetingID) * 100, 1
        ) AS GroupRequiredCompletion
    FROM ScheduledMeeting sm
    LEFT JOIN MeetingLog ml ON sm.MeetingID = ml.MeetingID
    JOIN GroupAllocation ga ON sm.GroupID = ga.GroupID
    JOIN Tutor t ON ga.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
      AND sm.Required = 1
      AND sm.GroupID IS NOT NULL
      AND sm.MeetingType = 'group'
");
$groupReqStmt->bind_param("i", $departmentId);
$groupReqStmt->execute();
$groupRequiredCompletion = $groupReqStmt->get_result()->fetch_assoc()['GroupRequiredCompletion'] ?? 0;

// Overdue meetings
$overdueStmt = $conn->prepare("
    SELECT COUNT(*) AS OverdueMeetings
    FROM ScheduledMeeting sm
    LEFT JOIN MeetingLog ml ON sm.MeetingID = ml.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
      AND sm.ScheduledDateTime < NOW()
      AND (ml.MeetingStatus IS NULL OR ml.MeetingStatus <> 'completed')
");
$overdueStmt->bind_param("i", $departmentId);
$overdueStmt->execute();
$overdueMeetings = $overdueStmt->get_result()->fetch_assoc()['OverdueMeetings'] ?? 0;

/* =========================
   STUDENTS AT RISK
   ========================= */

$missedStmt = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname, COUNT(*) AS MissedCount
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Student s ON a.StudentID = s.StudentID
    JOIN Course c ON s.CourseID = c.CourseID
    WHERE c.DepartmentID = ?
      AND ml.MeetingStatus IN ('cancelled', 'rescheduled')
    GROUP BY s.StudentID
    HAVING COUNT(*) > 1
");
$missedStmt->bind_param("i", $departmentId);
$missedStmt->execute();
$studentsMissedMultiple = $missedStmt->get_result();

$repeatRefStmt = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname, COUNT(*) AS ReferralCount
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Student s ON a.StudentID = s.StudentID
    JOIN Course c ON s.CourseID = c.CourseID
    WHERE c.DepartmentID = ?
    GROUP BY s.StudentID
    HAVING COUNT(*) > 1
");
$repeatRefStmt->bind_param("i", $departmentId);
$repeatRefStmt->execute();
$studentsRepeatedReferrals = $repeatRefStmt->get_result();

/* =========================
   REFERRAL SUMMARY
   ========================= */

$refTypeStmt = $conn->prepare("
    SELECT r.ReferralType, COUNT(*) AS Total
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
    GROUP BY r.ReferralType
");
$refTypeStmt->bind_param("i", $departmentId);
$refTypeStmt->execute();
$referralsByType = $refTypeStmt->get_result();

// Required referrals
$requiredRefTotalStmt = $conn->prepare("
    SELECT COUNT(*) AS RequiredReferrals
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
      AND ml.ReferralMade = 1
");
$requiredRefTotalStmt->bind_param("i", $departmentId);
$requiredRefTotalStmt->execute();
$requiredRefTotal = $requiredRefTotalStmt->get_result()->fetch_assoc()['RequiredReferrals'] ?? 0;

$requiredRefMadeStmt = $conn->prepare("
    SELECT COUNT(*) AS RequiredReferralsSubmitted
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    WHERE t.DepartmentID = ?
      AND ml.ReferralMade = 1
");
$requiredRefMadeStmt->bind_param("i", $departmentId);
$requiredRefMadeStmt->execute();
$requiredRefSubmitted = $requiredRefMadeStmt->get_result()->fetch_assoc()['RequiredReferralsSubmitted'] ?? 0;

$requiredRefPercent = ($requiredRefTotal > 0)
    ? round(($requiredRefSubmitted / $requiredRefTotal) * 100, 1)
    : 0;

/* =========================
   TUTOR CONTACT DETAILS
   ========================= */

$tutorContactStmt = $conn->prepare("
    SELECT TutorName, StaffEmail
    FROM Tutor
    WHERE DepartmentID = ?
    ORDER BY TutorName
");
$tutorContactStmt->bind_param("i", $departmentId);
$tutorContactStmt->execute();
$tutorContacts = $tutorContactStmt->get_result();

/* =========================
   TUTOR ANALYTICS
   ========================= */

$tutorStudentsStmt = $conn->prepare("
    SELECT 
        t.TutorID,
        t.TutorName,
        s.StudentID,
        s.Forename,
        s.Surname
    FROM Tutor t
    LEFT JOIN Allocation a ON t.TutorID = a.TutorID
    LEFT JOIN Student s ON a.StudentID = s.StudentID
    WHERE t.DepartmentID = ?
    ORDER BY t.TutorName, s.Surname
");
$tutorStudentsStmt->bind_param("i", $departmentId);
$tutorStudentsStmt->execute();
$tutorStudentsRes = $tutorStudentsStmt->get_result();

$tutorStudents = [];
while ($row = $tutorStudentsRes->fetch_assoc()) {
    $tid = $row['TutorID'];
    if (!isset($tutorStudents[$tid])) {
        $tutorStudents[$tid] = [
            'TutorName' => $row['TutorName'],
            'Students' => [],
            'Groups' => []
        ];
    }
    if (!empty($row['StudentID'])) {
        $tutorStudents[$tid]['Students'][] = [
            'StudentID' => $row['StudentID'],
            'Name' => $row['Forename'] . ' ' . $row['Surname']
        ];
    }
}

// Completed meetings per student
$completedPerStudentStmt = $conn->prepare("
    SELECT 
        t.TutorID,
        s.StudentID,
        COUNT(*) AS CompletedMeetings
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Tutor t ON a.TutorID = t.TutorID
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE t.DepartmentID = ?
      AND ml.MeetingStatus = 'completed'
    GROUP BY t.TutorID, s.StudentID
");
$completedPerStudentStmt->bind_param("i", $departmentId);
$completedPerStudentStmt->execute();
$completedPerStudentRes = $completedPerStudentStmt->get_result();

$completedPerStudent = [];
while ($row = $completedPerStudentRes->fetch_assoc()) {
    $completedPerStudent[$row['TutorID']][$row['StudentID']] = (int)$row['CompletedMeetings'];
}

// Completed group meetings
$completedGroupStmt = $conn->prepare("
    SELECT 
        t.TutorID,
        sm.GroupID,
        COUNT(*) AS CompletedGroupMeetings
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Tutor t ON sm.GroupID IN (
        SELECT GroupID FROM Allocation WHERE TutorID = t.TutorID
    )
    WHERE t.DepartmentID = ?
      AND sm.GroupID IS NOT NULL
      AND ml.MeetingStatus = 'completed'
    GROUP BY t.TutorID, sm.GroupID
");
$completedGroupStmt->bind_param("i", $departmentId);
$completedGroupStmt->execute();
$completedGroupRes = $completedGroupStmt->get_result();

$completedGroupMeetings = [];
while ($row = $completedGroupRes->fetch_assoc()) {
    $completedGroupMeetings[$row['TutorID']][$row['GroupID']] = (int)$row['CompletedGroupMeetings'];
}

// Assigned groups
$groupsStmt = $conn->prepare("
    SELECT DISTINCT t.TutorID, a.GroupID
    FROM Tutor t
    JOIN Allocation a ON t.TutorID = a.TutorID
    WHERE t.DepartmentID = ?
      AND a.GroupID IS NOT NULL
");
$groupsStmt->bind_param("i", $departmentId);
$groupsStmt->execute();
$groupsRes = $groupsStmt->get_result();

while ($row = $groupsRes->fetch_assoc()) {
    $tid = $row['TutorID'];
    $tutorStudents[$tid]['Groups'][] = $row['GroupID'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Department Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="../../logout.php" class="logout">Logout</a>

<div class="dashboard-container">
    <h1>Department Dashboard – <?= htmlspecialchars($departmentName) ?></h1>

    <div class="dashboard-columns">

        <!-- LEFT COLUMN -->
        <div class="dashboard-left">

            <!-- COMPLIANCE OVERVIEW -->
            <div class="card">
                <h2>Compliance Overview</h2>
                <p><strong>Tutor Required Meetings Completed:</strong> <?= $tutorRequiredCompletion ?>%</p>
                <p><strong>Student Required Meetings Completed:</strong> <?= $studentRequiredCompletion ?>%</p>
                <p><strong>Group Required Meetings Completed:</strong> <?= $groupRequiredCompletion ?>%</p>
                <p><strong>Overdue Meetings:</strong> <?= $overdueMeetings ?></p>
            </div>

            <!-- STUDENTS AT RISK -->
            <div class="card">
                <h2>Students at Risk</h2>

                <h3>Missed Multiple Sessions</h3>
                <?php if ($studentsMissedMultiple->num_rows > 0): ?>
                    <?php while ($row = $studentsMissedMultiple->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?></strong>
                            — Missed <?= $row['MissedCount'] ?> sessions
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No students with multiple missed sessions.</p>
                <?php endif; ?>

                <h3>Repeated Referrals</h3>
                <?php if ($studentsRepeatedReferrals->num_rows > 0): ?>
                    <?php while ($row = $studentsRepeatedReferrals->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?></strong>
                            — <?= $row['ReferralCount'] ?> referrals
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No students with repeated referrals.</p>
                <?php endif; ?>
            </div>

            <!-- REFERRAL SUMMARY -->
            <div class="card">
                <h2>Referral Summary</h2>

                <h3>Referrals by Type</h3>
                <?php if ($referralsByType->num_rows > 0): ?>
                    <?php while ($row = $referralsByType->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['ReferralType']) ?>:</strong>
                            <?= $row['Total'] ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No referrals recorded.</p>
                <?php endif; ?>

                <h3>Required Referral Compliance</h3>
                <p><strong>Required Referrals Flagged:</strong> <?= $requiredRefTotal ?></p>
                <p><strong>Required Referrals Submitted:</strong> <?= $requiredRefSubmitted ?></p>
                <p><strong>Compliance:</strong> <?= $requiredRefPercent ?>%</p>
            </div>

            <!-- TUTOR CONTACT DETAILS -->
            <div class="card">
                <h2>Tutor Contact Details</h2>

                <?php if ($tutorContacts->num_rows > 0): ?>
                    <?php while ($row = $tutorContacts->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['TutorName']) ?></strong><br>
                            <?= htmlspecialchars($row['StaffEmail']) ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No tutors found.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- RIGHT COLUMN -->
        <div class="dashboard-right">

            <!-- ADMIN CONTROL -->
            <div class="card">
                <h2>Administrative Control</h2>

                <button class="button-green" onclick="window.location.href='create-group.php'">
                     Create Group
                </button>

                <button class="button-primary" onclick="window.location.href='assign-student.php'">
                     Assign Student
                </button>

                <button class="button-primary" onclick="window.location.href='reassign-student.php'">
                   Reassign Student
                </button>
            </div>

            <!-- TUTOR ANALYTICS -->
            <div class="card">
                <h2>Tutor Analytics</h2>

                <?php foreach ($tutorStudents as $tid => $data): ?>
                    <div class="meeting-item">
                        <h3><?= htmlspecialchars($data['TutorName']) ?></h3>

                        <strong>Assigned Students:</strong><br>
                        <?php if (!empty($data['Students'])): ?>
                            <?php foreach ($data['Students'] as $stu): ?>
                                <?php $count = $completedPerStudent[$tid][$stu['StudentID']] ?? 0; ?>
                                • <?= htmlspecialchars($stu['Name']) ?> (<?= $count ?> meetings)<br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="empty">No assigned students.</span><br>
                        <?php endif; ?>

                        <br><strong>Assigned Groups:</strong><br>
                        <?php if (!empty($data['Groups'])): ?>
                            <?php foreach ($data['Groups'] as $gid): ?>
                                <?php $gCount = $completedGroupMeetings[$tid][$gid] ?? 0; ?>
                                • Group <?= $gid ?> (<?= $gCount ?> meetings)<br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="empty">No assigned groups.</span><br>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>

