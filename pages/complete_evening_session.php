
<?php
session_start();

require_once "../config/database.php";

$student_id = 1;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: evening_study.php");
    exit();
}

$session_id = (int)($_POST["session_id"] ?? 0);

$stmt = $conn->prepare(
    "SELECT * FROM evening_study_sessions
     WHERE id = ? AND student_id = ?"
);
$stmt->execute([$session_id, $student_id]);

$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session || $session["status"] === "Completed") {
    header("Location: evening_study.php");
    exit();
}

if ($session["subject"] === "Short Break") {
    header("Location: evening_study.php");
    exit();
}

$minutes = (
    strtotime($session["end_time"]) -
    strtotime($session["start_time"])
) / 60;

try {
    $conn->beginTransaction();

    $update = $conn->prepare(
        "UPDATE evening_study_sessions
         SET status = 'Completed'
         WHERE id = ? AND student_id = ?"
    );

    $update->execute([$session_id, $student_id]);

    $check = $conn->prepare(
        "SELECT COUNT(*) FROM progress
         WHERE student_id = ?
         AND session_id = ?"
    );

    $check->execute([$student_id, $session_id]);

    if ($check->fetchColumn() == 0) {

        $insert = $conn->prepare(
            "INSERT INTO progress
            (student_id, session_id, subject,
             minutes_spent, activity)
            VALUES (?, ?, ?, ?, ?)"
        );

        $insert->execute([
            $student_id,
            $session_id,
            $session["subject"],
            $minutes,
            "Evening Study Session Completed"
        ]);
    }

    $conn->commit();

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    die("Error updating evening progress: " . $e->getMessage());
}

header("Location: evening_study.php");
exit();
?>
