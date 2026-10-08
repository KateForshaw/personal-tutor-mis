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
    $newTutorId = $_POST['tutor_id'];
    $newGroupId = $_POST['group_id'];

    // 1. Remove old allocation
    $deleteAlloc = $conn->prepare("DELETE FROM Allocation WHERE StudentID = ?");
    $deleteAlloc->bind_param("i", $studentId);
    $deleteAlloc->execute();

    // 2. Remove student from old group
    $findOldGroup = $conn->prepare("
        SELECT GroupID, StudentIDs
        FROM GroupAllocation
        WHERE FIND_IN_SET(?, REPLACE(StudentIDs, ' ', ''))
    ");
    $findOldGroup->bind_param("i", $studentId);
    $findOldGroup->execute();
    $oldGroup = $findOldGroup->get_result()->fetch_assoc();

    if ($oldGroup) {
        $oldGroupId = $oldGroup['GroupID'];
        $oldList = array_filter(array_map('trim', explode(",", $oldGroup['StudentIDs'])));
        $updatedList = array_diff($oldList, [$studentId]);
        $updatedString = implode(", ", $updatedList);

        $updateOld = $conn->prepare("
            UPDATE GroupAllocation
            SET StudentIDs = ?
            WHERE GroupID = ?
        ");
        $updateOld->bind_param("si", $updatedString, $oldGroupId);
        $updateOld->execute();
    }

    // 3. Add student to new group
    $getNewGroup = $conn->prepare("SELECT StudentIDs FROM GroupAllocation WHERE GroupID = ?");
    $getNewGroup->bind_param("i", $newGroupId);
    $getNewGroup->execute();
    $newGroup = $getNewGroup->get_result()->fetch_assoc();

    $newList = array_filter(array_map('trim', explode(",", $newGroup['StudentIDs'])));
    if (!in_array($studentId, $newList)) {
        $newList[] = $studentId;
    }
    $newString = implode(", ", $newList);

    $updateNew = $conn->prepare("
        UPDATE GroupAllocation
        SET StudentIDs = ?
        WHERE GroupID = ?
    ");
    $updateNew->bind_param("si", $newString, $newGroupId);
    $updateNew->execute();

    // 4. Insert new allocation
    $insertAlloc = $conn->prepare("
        INSERT INTO Allocation (TutorID, StudentID, GroupID)
        VALUES (?, ?, ?)
    ");
    $insertAlloc->bind_param("iii", $newTutorId, $studentId, $newGroupId);
    $insertAlloc->execute();

    $message = "<p class='success'>Student reassigned successfully.</p>";
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
    <title>Reassign Student</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <h1>Reassign Student</h1>
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

            <label>Select New Tutor:</label>
            <select name="tutor_id" required>
                <option value="">-- Choose Tutor --</option>
                <?php while ($t = $tutorList->fetch_assoc()): ?>
                    <option value="<?= $t['TutorID'] ?>"><?= htmlspecialchars($t['TutorName']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Select New Group:</label>
            <select name="group_id" required>
                <option value="">-- Choose Group --</option>
                <?php while ($g = $groupList->fetch_assoc()): ?>
                    <option value="<?= $g['GroupID'] ?>">Group <?= $g['GroupID'] ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" class="button-primary">Reassign Student</button>
        </form>
    </div>

</div>

</body>
</html>
