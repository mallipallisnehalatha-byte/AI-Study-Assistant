
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$student_id = 1;

// Allow POST requests only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: display_timetable.php");
    exit();
}

// Verify CSRF token
$token = $_POST["csrf_token"] ?? "";

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    die("Invalid request. Please go back and try again.");
}

// Validate timetable ID
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Invalid timetable entry.");
}

try {

    // Check whether the timetable entry exists
    $check = $conn->prepare(
        "SELECT id
         FROM timetable
         WHERE id = ? AND student_id = ?"
    );

    $check->execute([$id, $student_id]);

    if (!$check->fetch()) {
        header("Location: display_timetable.php?error=notfound");
        exit();
    }

    // Delete only the selected student's timetable entry
    $delete = $conn->prepare(
        "DELETE FROM timetable
         WHERE id = ? AND student_id = ?"
    );

    $delete->execute([$id, $student_id]);

    if ($delete->rowCount() === 1) {
        header("Location: display_timetable.php?deleted=1");
        exit();
    }

    header("Location: display_timetable.php?error=notfound");
    exit();

} catch (PDOException $e) {

    error_log("Timetable deletion error: " . $e->getMessage());

    die("Unable to delete the timetable entry. Please try again.");
}
