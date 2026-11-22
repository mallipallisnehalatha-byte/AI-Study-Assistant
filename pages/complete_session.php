
<?php
session_start();

include "../config/database.php";

$student_id = 1;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: study_planner.php");
    exit();
}

$session_id = (int)($_POST["session_id"] ?? 0);

// Find the selected study session
$stmt = $conn->prepare(
    "SELECT * FROM study_sessions
     WHERE id = :id AND student_id = :student_id"
);

$stmt->execute([
    ":id" => $session_id,
    ":student_id" => $student_id
]);

$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session || $session["status"] === "Completed") {
    header("Location: study_planner.php");
    exit();
}

// Calculate study duration
$minutes = (
    strtotime($session["end_time"]) -
    strtotime($session["start_time"])
) / 60;

try {
    $conn->beginTransaction();

    // Update session status
    $update = $conn->prepare(
        "UPDATE study_sessions
         SET status = 'Completed'
         WHERE id = :id AND student_id = :student_id"
    );

    $update->execute([
        ":id" => $session_id,
        ":student_id" => $student_id
    ]);

    // Check whether progress is already recorded
    $check = $conn->prepare(
        "SELECT COUNT(*) FROM progress
         WHERE session_id = :session_id
         AND student_id = :student_id"
    );

    $check->execute([
        ":session_id" => $session_id,
        ":student_id" => $student_id
    ]);

    if ($check->fetchColumn() == 0) {

        // Save study progress
        $insert = $conn->prepare(
            "INSERT INTO progress
             (student_id, session_id, subject,
              minutes_spent, activity)
             VALUES
             (:student_id, :session_id, :subject,
              :minutes_spent, 'Study Session Completed')"
        );

        $insert->execute([
            ":student_id" => $student_id,
            ":session_id" => $session_id,
            ":subject" => $session["subject"],
            ":minutes_spent" => $minutes
        ]);
    }

    $conn->commit();

} catch (Exception $e) {

    $conn->rollBack();
    die("Error updating progress: " . $e->getMessage());
}

header("Location: study_planner.php");
exit();
?>
