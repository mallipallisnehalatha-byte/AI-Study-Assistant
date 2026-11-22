
<?php
require_once "../config/database.php";

$student_id = 1;

$sql = "SELECT subject, reminder_time, status
        FROM reminders
        WHERE student_id = ?
        ORDER BY reminder_time ASC";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);

$reminders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Study Reminders</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 30px;
        }

        h1 {
            color: #263b60;
        }

        .reminder {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .status {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>My Study Reminders</h1>

<?php if (count($reminders) > 0): ?>

    <?php foreach ($reminders as $reminder): ?>

        <div class="reminder">

            <h3>
                <?= htmlspecialchars($reminder['subject']) ?>
            </h3>

            <p>
                Reminder Time:
                <?= date("d-m-Y h:i A",
                    strtotime($reminder['reminder_time'])) ?>
            </p>

            <p class="status">
                Status:
                <?= htmlspecialchars($reminder['status']) ?>
            </p>

        </div>

    <?php endforeach; ?>

<?php else: ?>

    <p>No reminders available.</p>

<?php endif; ?>
<script src="../assets/js/notifications.js"></script>
</body>
</html>
