
<?php
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $session_id = (int)($_POST["session_id"] ?? 0);
    $student_id = 1;

    $sql = "UPDATE study_sessions
            SET status = 'Completed'
            WHERE id = ? AND student_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$session_id, $student_id]);
}

header("Location: view_study_plan.php");
exit;
?>
