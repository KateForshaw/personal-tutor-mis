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

    $tutorId = $_POST['tutor_id'];
    $studentIds = $_POST['student_ids'] ?? [];

    if (empty($studentIds)) {
        $message = "<p class='error'>Please select at least one student.</p>";
    } else {

        // Format: "1, 2, 3"
        $studentListString = implode(", ", $studentIds);

        // Insert into GroupAllocation
        $insert = $conn->prepare("
            INSERT INTO GroupAllocation (TutorID, StudentIDs)
            VALUES (?, ?)
        ");
        $insert->bind_param("is", $tutorId, $studentListString);
        $insert->execute();

        $newGroupId = $conn->insert_id;

        // Insert into Allocation
        foreach ($studentIds as $sid) {
            $alloc = $conn->prepare("
                INSERT INTO Allocation (TutorID, StudentID, GroupID)
                VALUES (?, ?, ?)
            ");
            $alloc->bind_param("iii", $tutorId, $sid, $newGroupId);
            $alloc->execute();
        }

        $message = "<p class='success'>Group $newGroupId created successfully.</p>";
    }
}

/* =========================
   LOAD TUTORS + STUDENTS
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Group</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-container">

    <h1>Create Group</h1>
    <a href="management.php" class="button-secondary">← Back to Dashboard</a>

    <?= $message ?>

    <div class="form-container">
        <form method="POST">

            <label>Select Tutor:</label>
            <select name="tutor_id" required>
                <option value="">-- Choose Tutor --</option>
                <?php while ($t = $tutorList->fetch_assoc()): ?>
                    <option value="<?= $t['TutorID'] ?>"><?= htmlspecialchars($t['TutorName']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Select Students:</label>
            <select name="student_ids[]" multiple size="10" required>
                <?php while ($s = $studentList->fetch_assoc()): ?>
                    <option value="<?= $s['StudentID'] ?>">
                        <?= htmlspecialchars($s['Forename'] . ' ' . $s['Surname']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <button type="submit" class="button-primary">Create Group</button>
        </form>
    </div>

</div>

</body>
</html>
