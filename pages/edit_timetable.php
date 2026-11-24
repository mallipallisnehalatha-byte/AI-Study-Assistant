
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$student_id = 1;

$days = [
    "Monday", "Tuesday", "Wednesday", "Thursday",
    "Friday", "Saturday", "Sunday"
];

$error = "";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

// Get timetable ID
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    die("Invalid timetable ID.");
}

// Fetch existing timetable entry
$sql = "SELECT t.id, t.day, t.subject_id, t.teacher_id,
               t.start_time, t.end_time
        FROM timetable t
        WHERE t.id = ? AND t.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$id, $student_id]);

$timetable = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$timetable) {
    die("Timetable entry not found.");
}

// Fetch subjects
$stmt = $conn->prepare(
    "SELECT id, subject_name
     FROM subjects
     WHERE student_id = ?
     ORDER BY subject_name"
);

$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers
$stmt = $conn->prepare(
    "SELECT id, teacher_name
     FROM teachers
     WHERE student_id = ?
     ORDER BY teacher_name"
);

$stmt->execute([$student_id]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $posted_token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($posted_token) ||
        !hash_equals($csrf_token, $posted_token)
    ) {
        $error = "Invalid request. Please refresh the page.";
    } else {

        $day = trim($_POST["day"] ?? "");

        $subject_id = filter_var(
            $_POST["subject_id"] ?? "",
            FILTER_VALIDATE_INT
        );

        $teacher_value = $_POST["teacher_id"] ?? "";
        $teacher_id = null;

        if ($teacher_value !== "") {
            $teacher_id = filter_var(
                $teacher_value,
                FILTER_VALIDATE_INT
            );
        }

        $start_time = trim($_POST["start_time"] ?? "");
        $end_time = trim($_POST["end_time"] ?? "");

        // Validate day
        if (!in_array($day, $days, true)) {
            $error = "Please select a valid day.";
        }

        // Validate subject
        elseif (!$subject_id || $subject_id <= 0) {
            $error = "Please select a valid subject.";
        }

        // Validate teacher
        elseif (
            $teacher_value !== "" &&
            (!$teacher_id || $teacher_id <= 0)
        ) {
            $error = "Please select a valid teacher.";
        }

        // Validate time format
        elseif (
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start_time) ||
            !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end_time)
        ) {
            $error = "Please enter valid start and end times.";
        }

        // Check time order
        elseif (strtotime($end_time) <= strtotime($start_time)) {
            $error = "End time must be later than start time.";
        }

        else {

            // Confirm subject belongs to this student
            $stmt = $conn->prepare(
                "SELECT id FROM subjects
                 WHERE id = ? AND student_id = ?"
            );

            $stmt->execute([$subject_id, $student_id]);

            if (!$stmt->fetch()) {
                $error = "Selected subject is invalid.";
            }

            // Confirm teacher belongs to this student
            if ($error === "" && $teacher_id !== null) {

                $stmt = $conn->prepare(
                    "SELECT id FROM teachers
                     WHERE id = ? AND student_id = ?"
                );

                $stmt->execute([$teacher_id, $student_id]);

                if (!$stmt->fetch()) {
                    $error = "Selected teacher is invalid.";
                }
            }

            // Check for overlapping classes
            if ($error === "") {

                $overlap = $conn->prepare(
                    "SELECT id
                     FROM timetable
                     WHERE student_id = ?
                     AND day = ?
                     AND start_time < ?
                     AND end_time > ?
                     AND id != ?
                     LIMIT 1"
                );

                $overlap->execute([
                    $student_id,
                    $day,
                    $end_time,
                    $start_time,
                    $id
                ]);

                if ($overlap->fetch()) {
                    $error = "This class overlaps with another existing class on " .
                             $day . ". Please choose another time.";
                }
            }

            // Save changes only if all checks pass
            if ($error === "") {

                $update = "UPDATE timetable
                           SET day = ?,
                               subject_id = ?,
                               teacher_id = ?,
                               start_time = ?,
                               end_time = ?
                           WHERE id = ? AND student_id = ?";

                $stmt = $conn->prepare($update);

                $stmt->execute([
                    $day,
                    $subject_id,
                    $teacher_id,
                    $start_time,
                    $end_time,
                    $id,
                    $student_id
                ]);

                header("Location: display_timetable.php?updated=1");
                exit();
            }
        }
    }

    // Preserve submitted values after validation errors
    if ($error !== "") {

        $timetable["day"] = $day ?? $timetable["day"];

        $timetable["subject_id"] =
            $subject_id ?: $timetable["subject_id"];

        $timetable["teacher_id"] = $teacher_id ?? null;

        $timetable["start_time"] =
            $start_time ?? $timetable["start_time"];

        $timetable["end_time"] =
            $end_time ?? $timetable["end_time"];
    }
}

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Timetable</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            padding: 25px;
        }

        .container {
            max-width: 550px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        h2 {
            text-align: center;
            color: #243b64;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            background: #243b64;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #36598c;
        }

        .error {
            background: #ffe5e5;
            color: #a00000;
            padding: 12px;
            border-radius: 5px;
        }

        .back {
            display: block;
            margin-top: 18px;
            text-align: center;
            color: #243b64;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Edit Timetable</h2>

    <?php if ($error !== ""): ?>
        <p class="error"><?= safe($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= safe($csrf_token) ?>"
        >

        <label>Day</label>

        <select name="day" required>
            <?php foreach ($days as $day_option): ?>
                <option
                    value="<?= safe($day_option) ?>"
                    <?= $timetable["day"] === $day_option
                        ? "selected" : "" ?>
                >
                    <?= safe($day_option) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Subject</label>

        <select name="subject_id" required>
            <?php foreach ($subjects as $subject): ?>
                <option
                    value="<?= (int)$subject["id"] ?>"
                    <?= (string)$timetable["subject_id"] ===
                        (string)$subject["id"] ? "selected" : "" ?>
                >
                    <?= safe($subject["subject_name"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Teacher</label>

        <select name="teacher_id">
            <option value="">No teacher selected</option>

            <?php foreach ($teachers as $teacher): ?>
                <option
                    value="<?= (int)$teacher["id"] ?>"
                    <?= (string)$timetable["teacher_id"] ===
                        (string)$teacher["id"] ? "selected" : "" ?>
                >
                    <?= safe($teacher["teacher_name"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Start Time</label>

        <input
            type="time"
            name="start_time"
            value="<?= safe(substr($timetable["start_time"], 0, 5)) ?>"
            required
        >

        <label>End Time</label>

        <input
            type="time"
            name="end_time"
            value="<?= safe(substr($timetable["end_time"], 0, 5)) ?>"
            required
        >

        <button type="submit">Save Changes</button>

    </form>

    <a class="back" href="display_timetable.php">
        Back to Weekly Timetable
    </a>

</div>

</body>
</html>
