
<?php
require_once "../config/database.php";

$student_id = 1;

// Get student reminders
$sql = "SELECT subject, reminder_time, status
        FROM reminders
        WHERE student_id = ?
        ORDER BY reminder_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Study Reminders</title>

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
            color: #28a745;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
    </style>
</head>

<body>

<h1>Study Reminders</h1>
<button onclick="enableNotifications()">
    Enable Notifications
</button>
<button onclick="testNotification()">
    Test Notification (1 Minute)
</button>

<?php if (count($reminders) > 0): ?>

    <?php foreach ($reminders as $reminder): ?>

        <div class="reminder">

            <h3>
                <?= htmlspecialchars($reminder['subject']) ?>
            </h3>


            <p>
                Reminder Time:
                <?= htmlspecialchars($reminder['reminder_time']) ?>
            </p>

            <p class="status">
                Status:
                <?= htmlspecialchars($reminder['status']) ?>
            </p>

        </div>

    <?php endforeach; ?>

<?php else: ?>

    <div class="empty">
        No reminders available.
    </div>

<?php endif; ?>

<script>
function enableNotifications() {

    if (!("Notification" in window)) {
        alert("Your browser does not support notifications.");
        return;
    }

    Notification.requestPermission().then(function(permission) {

        alert("Notification permission: " + permission);

    });
}

function testNotification() {

    if (!("Notification" in window)) {
        alert("Notifications are not supported.");
        return;
    }

    alert("Permission: " + Notification.permission);

    if (Notification.permission !== "granted") {
        alert("Please enable notifications first.");
        return;
    }

    new Notification("Study Planner Test", {
        body: "Your browser notification is working successfully!"
    });
}

const studyReminders = <?php
echo json_encode(
    $reminders,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
?>;

const notifiedReminders = new Set();

function checkUpcomingReminders() {

    if (Notification.permission !== "granted") {
        return;
    }

    const now = new Date();

    studyReminders.forEach(function(reminder) {

        if (reminder.status !== "Active") {
            return;
        }

        const reminderTime = new Date(
            reminder.reminder_time.replace(" ", "T")
        );

        const difference = now - reminderTime;

        if (
            difference >= 0 &&
            difference <= 5 * 60 * 1000 &&
            !notifiedReminders.has(reminder.reminder_time)
        ) {

            new Notification("Study Reminder", {
                body: reminder.subject + " study session starts in 10 minutes."
            });

            notifiedReminders.add(reminder.reminder_time);
        }

    });
}

checkUpcomingReminders();
setInterval(checkUpcomingReminders, 15000);

</script>

</body>
</html>
