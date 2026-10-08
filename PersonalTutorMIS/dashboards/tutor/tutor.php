<?php
include '../../auth.php';
requireRole('tutor');

include '../../db.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header("Location: ../../login.php");
    exit;
}

// 1. Get tutor record
$tutorStmt = $conn->prepare("
    SELECT TutorID, TutorName, StaffEmail
    FROM Tutor
    WHERE UserID = ?
");
$tutorStmt->bind_param("i", $userId);
$tutorStmt->execute();
$tutor = $tutorStmt->get_result()->fetch_assoc();

if (!$tutor) {
    echo "Tutor record not found.";
    exit;
}

$tutorId = $tutor['TutorID'];

// 2. Tutor schedule
$scheduleStmt = $conn->prepare("
    SELECT MicrosoftCalendarURL, DaysOnCampus
    FROM TutorSchedule
    WHERE TutorID = ?
");
$scheduleStmt->bind_param("i", $tutorId);
$scheduleStmt->execute();
$schedule = $scheduleStmt->get_result()->fetch_assoc();

// 3. Assigned students
$assignedStudentsStmt = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname, s.StudentEmail
    FROM Allocation a
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
    ORDER BY s.Surname, s.Forename
");
$assignedStudentsStmt->bind_param("i", $tutorId);
$assignedStudentsStmt->execute();
$assignedStudents = $assignedStudentsStmt->get_result();

// 4. Assigned groups
$assignedGroupsStmt = $conn->prepare("
    SELECT a.GroupID, s.Forename, s.Surname
    FROM Allocation a
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
      AND a.GroupID IS NOT NULL
    ORDER BY a.GroupID, s.Surname, s.Forename
");
$assignedGroupsStmt->bind_param("i", $tutorId);
$assignedGroupsStmt->execute();
$assignedGroupsRaw = $assignedGroupsStmt->get_result();

$assignedGroups = [];
while ($row = $assignedGroupsRaw->fetch_assoc()) {
    $assignedGroups[$row['GroupID']][] = $row['Forename'] . " " . $row['Surname'];
}

// 5. Overview counts
$studentCount = $assignedStudents->num_rows;

$groupCountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT GroupID) AS GroupCount
    FROM Allocation
    WHERE TutorID = ? AND GroupID IS NOT NULL
");
$groupCountStmt->bind_param("i", $tutorId);
$groupCountStmt->execute();
$groupCount = $groupCountStmt->get_result()->fetch_assoc()['GroupCount'] ?? 0;

// 6. Upcoming 1:1 meetings
$upcomingOneToOneStmt = $conn->prepare("
    SELECT sm.MeetingID, sm.MeetingType, sm.ScheduledDateTime, sm.InPerson,
           s.Forename, s.Surname
    FROM ScheduledMeeting sm
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
      AND sm.ScheduledDateTime >= NOW()
    ORDER BY sm.ScheduledDateTime ASC
    LIMIT 5
");
$upcomingOneToOneStmt->bind_param("i", $tutorId);
$upcomingOneToOneStmt->execute();
$upcomingOneToOne = $upcomingOneToOneStmt->get_result();

// 7. Upcoming group meetings
$upcomingGroupStmt = $conn->prepare("
    SELECT sm.MeetingID, sm.MeetingType, sm.ScheduledDateTime, sm.InPerson, sm.GroupID
    FROM ScheduledMeeting sm
    WHERE sm.GroupID IN (
        SELECT DISTINCT GroupID FROM Allocation WHERE TutorID = ?
    )
      AND sm.ScheduledDateTime >= NOW()
    ORDER BY sm.ScheduledDateTime ASC
    LIMIT 5
");
$upcomingGroupStmt->bind_param("i", $tutorId);
$upcomingGroupStmt->execute();
$upcomingGroupMeetings = $upcomingGroupStmt->get_result();

// Department & Manager info
$managerStmt = $conn->prepare("
    SELECT 
        d.DepartmentName,
        d.Building,
        dm.ManagerName,
        dm.ManagerEmail
    FROM Tutor t
    JOIN Department d ON t.DepartmentID = d.DepartmentID
    JOIN DepartmentManager dm ON d.DepartmentID = dm.DepartmentID
    WHERE t.TutorID = ?
");
$managerStmt->bind_param("i", $tutorId);
$managerStmt->execute();
$managerInfo = $managerStmt->get_result()->fetch_assoc();

// 8. Meeting history (individual + group)
$historyStmt = $conn->prepare("
    SELECT ml.LogID, ml.MeetingStatus, ml.MeetingTopic, ml.MeetingNotes,
           ml.DurationMinutes, ml.ReferralMade,
           sm.MeetingType, sm.ScheduledDateTime,
           s.Forename, s.Surname, sm.GroupID
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    LEFT JOIN Allocation a ON sm.AllocationID = a.AllocationID
    LEFT JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
       OR sm.GroupID IN (SELECT GroupID FROM Allocation WHERE TutorID = ?)
    ORDER BY sm.ScheduledDateTime DESC
    LIMIT 10
");
$historyStmt->bind_param("ii", $tutorId, $tutorId);
$historyStmt->execute();
$meetingHistory = $historyStmt->get_result();

// 9. Referrals (individual + group)
$referralStmt = $conn->prepare("
    SELECT r.ReferralID, r.ReferralType, r.ReferralReason, r.ReferralStatus,
           ss.ServiceName, ss.ServiceEmail,
           s.Forename, s.Surname, sm.GroupID
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    LEFT JOIN Allocation a ON sm.AllocationID = a.AllocationID
    LEFT JOIN Student s ON a.StudentID = s.StudentID
    JOIN SupportService ss ON r.ServiceID = ss.ServiceID
    WHERE a.TutorID = ?
       OR sm.GroupID IN (SELECT GroupID FROM Allocation WHERE TutorID = ?)
    ORDER BY r.ReferralID DESC
    LIMIT 10
");
$referralStmt->bind_param("ii", $tutorId, $tutorId);
$referralStmt->execute();
$referrals = $referralStmt->get_result();

// 10. Students requiring attention – multiple referrals
$attentionReferralsStmt = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname, COUNT(r.ReferralID) AS ReferralCount
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
    GROUP BY s.StudentID, s.Forename, s.Surname
    HAVING COUNT(r.ReferralID) > 1
");
$attentionReferralsStmt->bind_param("i", $tutorId);
$attentionReferralsStmt->execute();
$studentsMultipleReferrals = $attentionReferralsStmt->get_result();

// 11. Students requiring attention – missed meetings
$missedMeetingsStmt = $conn->prepare("
    SELECT DISTINCT s.StudentID, s.Forename, s.Surname, ml.MeetingStatus
    FROM MeetingLog ml
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID
    JOIN Allocation a ON sm.AllocationID = a.AllocationID
    JOIN Student s ON a.StudentID = s.StudentID
    WHERE a.TutorID = ?
      AND ml.MeetingStatus IN ('Cancelled', 'Rescheduled')
");
$missedMeetingsStmt->bind_param("i", $tutorId);
$missedMeetingsStmt->execute();
$studentsMissedMeetings = $missedMeetingsStmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tutor Portal</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<a href="../../logout.php" class="logout">Logout</a>

<div class="dashboard-container">

    <h1>Tutor Portal</h1>

    <div class="dashboard-columns">

        <!-- LEFT COLUMN -->
        <div class="dashboard-left">

            <!-- OVERVIEW -->
            <div class="card">
                <h2>Overview</h2>
                <p><strong>Tutor Name:</strong> <?= htmlspecialchars($tutor['TutorName']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($tutor['StaffEmail']) ?></p>
                <p><strong>Assigned Students:</strong> <?= (int)$studentCount ?></p>
                <p><strong>Assigned Groups:</strong> <?= (int)$groupCount ?></p>
                <?php if ($schedule): ?>
                    <p><strong>Days on Campus:</strong> <?= htmlspecialchars($schedule['DaysOnCampus']) ?></p>
                    <p><strong>Microsoft Calendar:</strong>
                        <a href="<?= htmlspecialchars($schedule['MicrosoftCalendarURL']) ?>" target="_blank">
                            View Calendar
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="card">
                <h2>Quick Actions</h2>

                <button class="button-primary" onclick="window.location.href='book-meeting.php'">
                    Book Meeting
                </button>

                <button class="button-green" onclick="window.location.href='log-meeting.php'">
                    Log Meeting
                </button>

                <button class="button-primary" onclick="window.location.href='submit-referral.php'">
                    Submit Referral
                </button>
            </div>

            <!-- ASSIGNED STUDENTS -->
            <div class="card">
                <h2>Assigned Students</h2>
                <?php if ($assignedStudents->num_rows > 0): ?>
                    <?php while ($s = $assignedStudents->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($s['Forename'] . ' ' . $s['Surname']) ?></strong><br>
                            <?= htmlspecialchars($s['StudentEmail']) ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No assigned students.</p>
                <?php endif; ?>
            </div>

            <!-- ASSIGNED GROUPS -->
            <div class="card">
                <h2>Assigned Groups</h2>
                <?php if (!empty($assignedGroups)): ?>
                    <?php foreach ($assignedGroups as $groupId => $students): ?>
                        <div class="meeting-item">
                            <strong>Group <?= (int)$groupId ?></strong><br>
                            <?php foreach ($students as $name): ?>
                                • <?= htmlspecialchars($name) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty">No assigned groups.</p>
                <?php endif; ?>
            </div>

            <!-- STUDENTS REQUIRING ATTENTION -->
            <div class="card">
                <h2>Students Requiring Attention</h2>

                <h3>Multiple Referrals</h3>
                <?php if ($studentsMultipleReferrals->num_rows > 0): ?>
                    <?php while ($row = $studentsMultipleReferrals->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?></strong><br>
                            Referrals: <?= (int)$row['ReferralCount'] ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No students with multiple referrals.</p>
                <?php endif; ?>

                <h3>Missed Meetings</h3>
                <?php if ($studentsMissedMeetings->num_rows > 0): ?>
                    <?php while ($row = $studentsMissedMeetings->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?></strong><br>
                            Status: <?= htmlspecialchars($row['MeetingStatus']) ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No missed meetings.</p>
                <?php endif; ?>
            </div>

            <!-- UPCOMING MEETINGS -->
            <div class="card">
                <h2>Upcoming Meetings</h2>

                <h3>Scheduled 1:1</h3>
                <?php if ($upcomingOneToOne->num_rows > 0): ?>
                    <?php while ($row = $upcomingOneToOne->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['MeetingType']) ?></strong><br>
                            <?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?><br>
                            <?= htmlspecialchars($row['ScheduledDateTime']) ?><br>
                            <?= $row['InPerson'] ? "In Person" : "Online" ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No upcoming 1:1 meetings.</p>
                <?php endif; ?>

                <h3>Scheduled Group</h3>
                <?php if ($upcomingGroupMeetings->num_rows > 0): ?>
                    <?php while ($row = $upcomingGroupMeetings->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['MeetingType']) ?></strong><br>
                            Group ID: <?= (int)$row['GroupID'] ?><br>
                            <?= htmlspecialchars($row['ScheduledDateTime']) ?><br>
                            <?= $row['InPerson'] ? "In Person" : "Online" ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No upcoming group meetings.</p>
                <?php endif; ?>
            </div>

            <!-- DEPARTMENT MANAGER INFO -->
            <div class="card">
                <h2>Department Information</h2>

                <p><strong>Department:</strong> <?= htmlspecialchars($managerInfo['DepartmentName']) ?></p>
                <p><strong>Building:</strong> <?= htmlspecialchars($managerInfo['Building']) ?></p>
                <p><strong>Manager Name:</strong> <?= htmlspecialchars($managerInfo['ManagerName']) ?></p>
                <p><strong>Manager Email:</strong>
                    <a href="mailto:<?= htmlspecialchars($managerInfo['ManagerEmail']) ?>"
                        style="color: var(--primary-purple);">
                        <?= htmlspecialchars($managerInfo['ManagerEmail']) ?>
                    </a>
                </p>
            </div>

        </div> <!-- END LEFT COLUMN -->



        <!-- RIGHT COLUMN -->
        <div class="dashboard-right">

            <!-- MEETING HISTORY -->
            <div class="card">
                <h2>Meeting History</h2>

                <?php if ($meetingHistory->num_rows > 0): ?>
                    <?php while ($row = $meetingHistory->fetch_assoc()): ?>
                        <div class="meeting-item">
                            <strong><?= htmlspecialchars($row['MeetingType']) ?></strong>
                            — <?= htmlspecialchars($row['MeetingStatus']) ?><br>

                            <?php if (!empty($row['Forename'])): ?>
                                <?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?><br>
                            <?php elseif (!empty($row['GroupID'])): ?>
                                Group ID: <?= (int)$row['GroupID'] ?><br>
                            <?php endif; ?>

                            <?= htmlspecialchars($row['ScheduledDateTime']) ?><br>
                            <strong>Topic:</strong> <?= htmlspecialchars($row['MeetingTopic']) ?><br>
                            <strong>Notes:</strong> <?= nl2br(htmlspecialchars($row['MeetingNotes'])) ?><br>
                            <strong>Duration:</strong> <?= (int)$row['DurationMinutes'] ?> minutes<br>
                            <strong>Referral Made:</strong> <?= $row['ReferralMade'] ? "Yes" : "No" ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No meeting history available.</p>
                <?php endif; ?>
            </div>

            <!-- REFERRAL MANAGEMENT -->
            <div class="card">
                <h2>Referral Management</h2>

                <?php if ($referrals->num_rows > 0): ?>
                    <?php while ($row = $referrals->fetch_assoc()): ?>
                        <div class="referral-item">
                            <?php if (!empty($row['Forename'])): ?>
                                <strong>Student:</strong> <?= htmlspecialchars($row['Forename'] . ' ' . $row['Surname']) ?><br>
                            <?php elseif (!empty($row['GroupID'])): ?>
                                <strong>Group ID:</strong> <?= (int)$row['GroupID'] ?><br>
                            <?php endif; ?>

                            <strong>Service:</strong> <?= htmlspecialchars($row['ServiceName']) ?><br>
                            <strong>Email:</strong> <?= htmlspecialchars($row['ServiceEmail']) ?><br>
                            <strong>Type:</strong> <?= htmlspecialchars($row['ReferralType']) ?><br>
                            <strong>Status:</strong> <?= htmlspecialchars($row['ReferralStatus']) ?><br>
                            <strong>Reason:</strong> <?= nl2br(htmlspecialchars($row['ReferralReason'])) ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty">No referrals recorded.</p>
                <?php endif; ?>
            </div>

            <!-- RESOURCES & POLICIES -->
            <div class="card">
                <h2>Resources & Policies</h2>

                <div class="referral-item">
                    <strong>Safeguarding Policy</strong><br>
                    <a href="https://www.edgehill.ac.uk/document/safeguarding-policy/safeguarding-policy-2024-2027/"
                       target="_blank"
                       style="color: var(--primary-purple);">
                        View Safeguarding Policy (PDF)
                    </a>
                </div>

                <div class="referral-item">
                    <strong>Serious Incident Reporting Policy</strong><br>
                    <a href="https://www.edgehill.ac.uk/wp-content/uploads/documents/serious-incident-reporting-policy.pdf"
                       target="_blank"
                       style="color: var(--primary-purple);">
                        View Serious Incident Reporting Policy (PDF)
                    </a>
                </div>

                <div class="referral-item">
                    <strong>University Support Services</strong><br>
                    <a href="view-services.php" style="color: var(--primary-purple);">View Services</a>
                </div>
            </div>

        </div> <!-- END RIGHT COLUMN -->

    </div> <!-- END TWO-COLUMN WRAPPER -->

</div>

</body>
</html>
