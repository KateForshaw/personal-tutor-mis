<link rel="stylesheet" href="../assets/css/style.css">

<?php
include '../auth.php';
requireRole('student');

include '../db.php'; // query the database

// get the logged-in user's StudentID
$userID = $_SESSION['user_id'];

// query student info & course name
$studentQuery = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname, s.YearOfStudy, c.CourseName, a.TutorID
    FROM Student s
    JOIN Course c ON s.CourseID = c.CourseID
    JOIN Allocation a ON a.StudentID = s.StudentID
    WHERE s.UserID = ?
");
$studentQuery->bind_param("i", $userID);
$studentQuery->execute();
$student = $studentQuery->get_result()->fetch_assoc();

$studentID = $student['StudentID'];
$tutorID = $student['TutorID'];

// query tutor info & schedule
$tutorQuery = $conn->prepare("
    SELECT t.TutorName, t.StaffEmail, ts.MicrosoftCalendarURL, ts.DaysOnCampus
    FROM Tutor t
    LEFT JOIN TutorSchedule ts ON t.TutorID = ts.TutorID
    WHERE t.TutorID = ?
");
$tutorQuery->bind_param("i", $tutorID);
$tutorQuery->execute();
$tutor = $tutorQuery->get_result()->fetch_assoc();

// query upcoming meetings 
$upcomingQuery = $conn->prepare("
    SELECT 
        sm.MeetingID,
        sm.MeetingType,
        sm.ScheduledDateTime,
        sm.InPerson
    FROM ScheduledMeeting sm

    LEFT JOIN Allocation a 
        ON sm.AllocationID = a.AllocationID 
        AND a.StudentID = ?

    LEFT JOIN GroupAllocation ga 
        ON sm.GroupID = ga.GroupID
        AND FIND_IN_SET(?, REPLACE(ga.StudentIDs, ' ', ''))

    WHERE sm.ScheduledDateTime >= NOW()
      AND (a.StudentID IS NOT NULL OR ga.StudentIDs IS NOT NULL)
    ORDER BY sm.ScheduledDateTime ASC
");
$upcomingQuery->bind_param("ii", $studentID, $studentID);
$upcomingQuery->execute();
$upcomingMeetings = $upcomingQuery->get_result();

// query meeting history
$historyQuery = $conn->prepare("
    SELECT 
        sm.MeetingType,
        sm.ScheduledDateTime,
        ml.MeetingStatus,
        ml.DurationMinutes,
        ml.MeetingTopic,
        ml.MeetingNotes
    FROM (
        SELECT MeetingID, MAX(LogID) AS LatestLogID
        FROM MeetingLog
        GROUP BY MeetingID
    ) latest
    JOIN MeetingLog ml ON ml.LogID = latest.LatestLogID
    JOIN ScheduledMeeting sm 
        ON ml.MeetingID = sm.MeetingID

    LEFT JOIN Allocation a 
        ON sm.AllocationID = a.AllocationID 
        AND a.StudentID = ?

    LEFT JOIN GroupAllocation ga 
        ON sm.GroupID = ga.GroupID
        AND FIND_IN_SET(?, REPLACE(ga.StudentIDs, ' ', ''))

    WHERE (a.StudentID IS NOT NULL OR ga.StudentIDs IS NOT NULL)
    ORDER BY sm.ScheduledDateTime DESC
    ");
$historyQuery->bind_param("ii", $studentID, $studentID);
$historyQuery->execute();
$meetingHistory = $historyQuery->get_result();

// query referral information
$referralQuery = $conn->prepare("
    SELECT 
        r.ReferralType,
        r.ReferralReason,
        r.ReferralStatus,
        ss.ServiceName,
        ss.ServiceEmail,
        ss.OnCampus
    FROM Referral r
    JOIN MeetingLog ml ON r.LogID = ml.LogID
    JOIN ScheduledMeeting sm ON ml.MeetingID = sm.MeetingID

    LEFT JOIN Allocation a 
        ON sm.AllocationID = a.AllocationID 
        AND a.StudentID = ?

    LEFT JOIN GroupAllocation ga 
        ON sm.GroupID = ga.GroupID
        AND FIND_IN_SET(?, REPLACE(ga.StudentIDs, ' ', ''))

    JOIN SupportService ss ON r.ServiceID = ss.ServiceID

    WHERE (a.StudentID IS NOT NULL OR ga.StudentIDs IS NOT NULL)
");
$referralQuery->bind_param("ii", $studentID, $studentID);
$referralQuery->execute();
$referrals = $referralQuery->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<a href="../logout.php" class="logout">Logout</a>

<div class="dashboard-container">

    <h1>Student Portal</h1>

    <div class="card">
        <h2>Student Information</h2>
        <p><strong>Name:</strong> <?= $student['Forename'] . ' ' . $student['Surname'] ?></p>
        <p><strong>Student ID:</strong> <?= $student['StudentID'] ?></p>
        <p><strong>Course:</strong> <?= $student['CourseName'] ?></p>
        <p><strong>Year of Study:</strong> <?= $student['YearOfStudy'] ?></p>
    </div>

    <div class="card">
        <h2>Your Personal Tutor</h2>
        <p><strong>Name:</strong> <?= $tutor['TutorName'] ?></p>
        <p><strong>Email:</strong> <?= $tutor['StaffEmail'] ?></p>
        <p><strong>Microsoft Calendar:</strong>
                        <a href="<?= htmlspecialchars($schedule['MicrosoftCalendarURL']) ?>" target="_blank">
                            View Calendar
                        </a>
                    </p>
        <p><strong>On Campus:</strong> <?= $tutor['DaysOnCampus'] ?></p>
    </div>

    <div class="card">
        <h2>Upcoming Meetings</h2>
        <?php if ($upcomingMeetings->num_rows > 0): ?>
            <?php while ($row = $upcomingMeetings->fetch_assoc()): ?>
                <div class="meeting-item">
                    <strong><?= $row['MeetingType'] ?></strong><br>
                    <?= $row['ScheduledDateTime'] ?><br>
                    <?= $row['InPerson'] ? "In Person" : "Online" ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No upcoming meetings.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Meeting History</h2>
        <?php if ($meetingHistory->num_rows > 0): ?>
            <?php while ($row = $meetingHistory->fetch_assoc()): ?>
                <div class="meeting-item">
                    <strong><?= $row['MeetingType'] ?></strong> — <?= $row['MeetingStatus'] ?><br>
                    <?= $row['ScheduledDateTime'] ?><br>
                    <strong>Topic:</strong> <?= $row['MeetingTopic'] ?><br>
                    <strong>Notes:</strong> <?= $row['MeetingNotes'] ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No meeting history available.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Referral Information</h2>
        <?php if ($referrals->num_rows > 0): ?>
            <?php while ($row = $referrals->fetch_assoc()): ?>
                <div class="referral-item">
                    <strong>Service:</strong> <?= $row['ServiceName'] ?><br>
                    <strong>Email:</strong> <?= $row['ServiceEmail'] ?><br>
                    <strong>Status:</strong> <?= $row['ReferralStatus'] ?><br>
                    <strong>Reason:</strong> <?= $row['ReferralReason'] ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">No referrals recorded.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
