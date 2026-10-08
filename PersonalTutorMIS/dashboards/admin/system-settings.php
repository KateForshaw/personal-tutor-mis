<?php
include '../../auth.php';
requireRole('admin');
include '../../db.php';

// Fetch settings
$settings = $conn->query("SELECT * FROM SystemSettings LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Settings</title>
    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        .settings-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #ddd;
        }
        .settings-row label {
            font-weight: bold;
            width: 40%;
        }
        .settings-row input,
        .settings-row textarea,
        .settings-row select {
            width: 55%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<a href="admin.php" class="logout">Back to Dashboard</a>

<div class="dashboard-container">

    <h1>System Settings</h1>

    <form method="POST" action="update-settings.php">

        <!-- GENERAL SETTINGS CARD -->
        <div class="card">
            <h2>General Settings</h2>

            <div class="settings-row">
                <label>System Name</label>
                <input type="text" name="system_name" value="<?= $settings['SystemName'] ?>" required>
            </div>

            <div class="settings-row">
                <label>Academic Year</label>
                <input type="text" name="academic_year" value="<?= $settings['AcademicYear'] ?>" required>
            </div>

            <div class="settings-row">
                <label>Dashboard Message</label>
                <textarea name="dashboard_message" rows="2"><?= $settings['DashboardMessage'] ?></textarea>
            </div>

            <div class="settings-row">
                <label>Contact Email</label>
                <input type="email" name="contact_email" value="<?= $settings['ContactEmail'] ?>">
            </div>
        </div>

        <!-- ALLOCATION SETTINGS CARD -->
        <div class="card">
            <h2>Allocation Settings</h2>

            <div class="settings-row">
                <label>Max Students Per Tutor</label>
                <input type="number" name="max_students" min="1" value="<?= $settings['MaxStudentsPerTutor'] ?>">
            </div>

            <div class="settings-row">
                <label>Max Students Per Group</label>
                <input type="number" name="max_group" min="1" value="<?= $settings['MaxStudentsPerGroup'] ?? 10 ?>">
            </div>
        </div>

        <!-- BRANDING SETTINGS CARD -->
        <div class="card">
            <h2>Branding</h2>

            <div class="settings-row">
                <label>Primary Colour</label>
                <input type="color" name="primary_colour" value="<?= $settings['PrimaryColour'] ?>">
            </div>

            <div class="settings-row">
                <label>Secondary Colour</label>
                <input type="color" name="secondary_colour" value="<?= $settings['SecondaryColour'] ?>">
            </div>
        </div>

        <br>
        <button class="button-primary" type="submit">Save Settings</button>

    </form>

</div>

</body>
</html>
