
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$student_id = 1;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

$days = [
    "Monday", "Tuesday", "Wednesday",
    "Thursday", "Friday", "Saturday", "Sunday"
];

// Fetch subjects
$stmt = $conn->prepare(
    "SELECT id, subject_name FROM subjects
     WHERE student_id = ? ORDER BY subject_name"
);
$stmt->execute([$student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers
$stmt = $conn->prepare(
    "SELECT id, teacher_name FROM teachers
     WHERE student_id = ? ORDER BY teacher_name"
);
$stmt->execute([$student_id]);
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid request. Please refresh the page.";

    } else {

        $day = $_POST["day"] ?? "";

        $subject_id = filter_input(
            INPUT_POST,
            "subject_id",
            FILTER_VALIDATE_INT
        );

        $teacher_value = $_POST["teacher_id"] ?? "";

        $start_time = $_POST["start_time"] ?? "";
        $end_time = $_POST["end_time"] ?? "";

        $teacher_id = null;

        if ($teacher_value !== "") {
            $teacher_id = filter_var(
                $teacher_value,
                FILTER_VALIDATE_INT
            );
        }

        // Validate day
        if (!in_array($day, $days, true)) {
            $error = "Please select a valid day.";

        // Validate subject
        } elseif (!$subject_id || $subject_id <= 0) {
            $error = "Please select a valid subject.";

        // Validate teacher
        } elseif (
            $teacher_value !== "" &&
            (!$teacher_id || $teacher_id <= 0)
        ) {
            $error = "Please select a valid teacher.";

        // Validate time format
        } elseif (
            !is_string($start_time) ||
            !is_string($end_time) ||
            !preg_match('/^\d{2}:\d{2}$/', $start_time) ||
            !preg_match('/^\d{2}:\d{2}$/', $end_time)
        ) {
            $error = "Please enter valid times.";

        // Validate time order
        } elseif ($start_time >= $end_time) {
            $error = "End time must be later than start time.";

        } else {

            // Check subject ownership
            $check = $conn->prepare(
                "SELECT id FROM subjects
                 WHERE id = ? AND student_id = ?"
            );

            $check->execute([$subject_id, $student_id]);

            if (!$check->fetch()) {
                $error = "Selected subject was not found.";
            }

            // Check teacher ownership
            if ($error === "" && $teacher_id !== null) {

                $check = $conn->prepare(
                    "SELECT id FROM teachers
                     WHERE id = ? AND student_id = ?"
                );

                $check->execute([$teacher_id, $student_id]);

                if (!$check->fetch()) {
                    $error = "Selected teacher was not found.";
                }
            }

            // Check for timetable overlap
            if ($error === "") {

                $overlap = $conn->prepare(
                    "SELECT id
                     FROM timetable
                     WHERE student_id = ?
                     AND day = ?
                     AND start_time < ?
                     AND end_time > ?
                     LIMIT 1"
                );

                $overlap->execute([
                    $student_id,
                    $day,
                    $end_time,
                    $start_time
                ]);

                if ($overlap->fetch()) {
                    $error = "This class overlaps with an existing class on " . $day . ". Please choose another time.";
                }
            }

            // Insert only if all validations pass
            if ($error === "") {

                $sql = "INSERT INTO timetable
                        (
                            student_id,
                            subject_id,
                            teacher_id,
                            day,
                            start_time,
                            end_time
                        )
                        VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $student_id,
                    $subject_id,
                    $teacher_id,
                    $day,
                    $start_time,
                    $end_time
                ]);

                header("Location: display_timetable.php?added=1");
                exit();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Add New Class</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            padding: 30px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px #ddd;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .error {
            color: red;
            text-align: center;
            background: #fee2e2;
            padding: 10px;
            border-radius: 5px;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 18px;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>Add New Class</h2>

    <?php if ($error !== ""): ?>

        <p class="error">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </p>

    <?php endif; ?>

    <?php if (count($subjects) === 0): ?>

        <p class="error">
            No subjects found. Add a subject first.
        </p>

    <?php else: ?>

        <form method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["csrf_token"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <label>Day</label>

            <select name="day" required>

                <option value="">Select Day</option>

                <?php foreach ($days as $day): ?>

                    <option value="<?= htmlspecialchars($day) ?>">
                        <?= htmlspecialchars($day) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label>Subject</label>

            <select name="subject_id" required>

                <option value="">Select Subject</option>

                <?php foreach ($subjects as $subject): ?>

                    <option value="<?= (int)$subject["id"] ?>">
                        <?= htmlspecialchars(
                            $subject["subject_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label>Teacher (Optional)</label>

            <select name="teacher_id">

                <option value="">Select Teacher</option>

                <?php foreach ($teachers as $teacher): ?>

                    <option value="<?= (int)$teacher["id"] ?>">
                        <?= htmlspecialchars(
                            $teacher["teacher_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label>Start Time</label>

            <input
                type="time"
                name="start_time"
                required
            >

            <label>End Time</label>

            <input
                type="time"
                name="end_time"
                required
            >

            <button type="submit">
                Add Class
            </button>

        </form>

    <?php endif; ?>

    <a href="display_timetable.php">
        Back to Weekly Timetable
    </a>

</div>

</body>
</html>
