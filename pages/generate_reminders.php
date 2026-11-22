<?php
require_once "../config/database.php";

$student_id = 1;

$stmt = $conn->prepare(
    "SELECT id, subject, study_date, start_time
     FROM study_sessions
     WHERE student_id = ? AND status = 'Pending'"
);
$stmt->execute([$student_id]);
$sessions = $stmt->fetchAll();

$check = $conn->prepare(
    "SELECT id FROM reminders
     WHERE student_id = ? AND subject = ? AND reminder_time = ?"
);
$insert = $conn->prepare(
    "INSERT INTO reminders (student_id, subject, reminder_time, status)
     VALUES (?, ?, ?, 'Active')"
);

$created = 0;

foreach ($sessions as $session) {
    $reminder_time = date(
        "Y-m-d H:i:s",
        strtotime($session['study_date'] . " " . $session['start_time']) - 600
    );

    $check->execute([
        $student_id,
        $session['subject'],
        $reminder_time
    ]);

    if ($check->fetch()) {
        continue;
    }

    $insert->execute([
        $student_id,
        $session['subject'],
        $reminder_time
    ]);
    $created++;
}

echo "<h2>Reminder Generation Successful!</h2>";
echo "<p>New reminders created: " . $created . "</p>";
echo "<p>Total pending study sessions checked: " . count($sessions) . "</p>";
echo '<p><a href="view_reminders.php">View My Reminders</a></p>';
