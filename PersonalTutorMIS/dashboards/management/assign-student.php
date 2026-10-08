<?php
include '../../auth.php';
requireRole('management');
include '../../db.php';

// Get manager department
$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT dm.DepartmentID
    FROM DepartmentManager dm
    WHERE dm.UserID = ?
    LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$dept = $stmt->get_result()->fetch_assoc();
$departmentId = $dept['DepartmentID'];

$message = "";

/* =========================
   HANDLE FORM SUBMISSION
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = $_POST['student_id'];
    $tutorId = $_POST['tutor_id'];
    $groupId = $_POST['group_id'];

    // Insert into Allocation
    $insert = $conn->prepare("
        INSERT INTO Allocation (TutorID, StudentID, GroupID)
        VALUES (?, ?, ?)
    ");
    $insert->bind_param("iii", $tutorId, $studentId, $groupId);
    $insert->execute();

    // Update GroupAllocation
    $get = $conn->prepare("
        SELECT StudentIDs
        FROM GroupAllocation
        WHERE GroupID = ?
    ");
    $get->bind_param("i", $groupId);
    $get->execute();
    $row = $get->get_result()->fetch_assoc();

    $students = array_filter(array_map('trim', explode(",", $row['StudentIDs'])));
    if (!in_array($studentId, $students)) {
        $students[] = $studentId;
    }

    $newList = implode(", ", $students);

    $update = $conn->prepare("
        UPDATE GroupAllocation
        SET StudentIDs = ?
        WHERE GroupID = ?
    ");
    $update->bind_param("si", $newList, $groupId);
    $update->execute();

    $message = "<p class='success'>Student assigned successfully.</p>";
}

/* =========================
   LOAD TUTORS, STUDENTS, GROUPS
   ========================= */

$tutors = $conn->prepare("
    SELECT TutorID, TutorName
    FROM Tutor
    WHERE DepartmentID = ?
    ORDER BY TutorName
");
$tutors->bind_param("i", $departmentId);
$tutors->execute();
$tutorList = $tutors->get_result();

$students = $conn->prepare("
    SELECT s.StudentID, s.Forename, s.Surname
    FROM Student s
    JOIN Course c ON s.CourseID = c.CourseID
    WHERE c.DepartmentID = ?
    ORDER BY s.Surname
");
$students->bind_param("i", $departmentId);
$students->execute();
$studentList = $students->get_result();

$groups = $conn->prepare("
    SELECT GroupID
    FROM GroupAllocation
    ORDER BY GroupID
");
$groups->execute();
$groupList = $groups->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Student</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <h1>Assign Student to Tutor</h1>
    <a href="management.php" class="button-secondary">← Back to Dashboard</a>

    <?= $message ?>

    <div class="form-container">
        <form method="POST">

            <label>Select Student:</label>
            <select name="student_id" required>
                <option value="">-- Choose Student --</option>
                <?php while ($s = $studentList->fetch_assoc()): ?>
                    <option value="<?= $s['StudentID'] ?>">
                        <?= htmlspecialchars($s['Forename'] . ' ' . $s['Surname']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Select Tutor:</label>
            <select name="tutor_id" required>
                <option value="">-- Choose Tutor --</option>
                <?php while ($t = $tutorList->fetch_assoc()): ?>
                    <option value="<?= $t['TutorID'] ?>"><?= htmlspecialchars($t['TutorName']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Select Group:</label>
            <select name="group_id" required>
                <option value="">-- Choose Group --</option>
                <?php while ($g = $groupList->fetch_assoc()): ?>
                    <option value="<?= $g['GroupID'] ?>">Group <?= $g['GroupID'] ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" class="button-primary">Assign Student</button>
        </form>
    </div>

</div>

</body>
</html>
