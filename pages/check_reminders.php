<?php
require_once "../config/database.php";

header("Content-Type: application/json");

$student_id = 1;

$sql = "SELECT id, subject
        FROM reminders
        WHERE student_id = ?
        AND status = 'Active'
        AND reminder_time <= NOW()";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$reminders = $stmt->fetchAll();

$due_reminders = [];

foreach ($reminders as $reminder) {
    $due_reminders[] = [
        "id" => $reminder["id"],
        "reminder_text" =>
            $reminder["subject"] . " study session starts in 10 minutes."
    ];

    $update = $conn->prepare(
        "UPDATE reminders
         SET status = 'Dismissed'
         WHERE id = ? AND student_id = ?"
    );
    $update->execute([$reminder["id"], $student_id]);
}

echo json_encode(["reminders" => $due_reminders]);
