<?php
include '../../auth.php';
requireRole('tutor');
include '../../db.php';

// Fetch all support services
$services = $conn->query("
    SELECT ServiceID, ServiceName, ServiceDescription, ServiceEmail, OnCampus
    FROM SupportService
    ORDER BY ServiceName
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Support Services</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="form-container">
    <h1>University Support Services</h1>
    <a href="tutor.php" class="button-secondary">← Back to Dashboard</a>

    <p style="margin-bottom: 20px;">
        Below is a list of all support services available for referrals.
        Click a service email to contact them directly.
    </p>

    <?php if ($services->num_rows > 0): ?>
        <?php while ($s = $services->fetch_assoc()): ?>
            <div class="card" style="margin-bottom: 20px;">
                <h2><?= htmlspecialchars($s['ServiceName']) ?></h2>

                <p><strong>Description:</strong><br>
                    <?= nl2br(htmlspecialchars($s['ServiceDescription'])) ?>
                </p>

                <p><strong>Email:</strong><br>
                    <a href="mailto:<?= htmlspecialchars($s['ServiceEmail']) ?>" 
                       style="color: var(--primary-purple);">
                        <?= htmlspecialchars($s['ServiceEmail']) ?>
                    </a>
                </p>

                <p><strong>Location:</strong><br>
                    <?= $s['OnCampus'] ? "On Campus" : "Off Campus" ?>
                </p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No support services found in the system.</p>
    <?php endif; ?>

    <button class="button-primary" onclick="window.location.href='tutor.php'">
        Back to Dashboard
    </button>
</div>

</body>
</html>
