
<?php
require_once "../config/database.php";

$student_id = 1;
$message = "";
$generated = [];

// Get timetable
$sql = "SELECT t.day, t.start_time, t.end_time,
               s.subject_name AS subject
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        WHERE t.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$records = $stmt->fetchAll();

// Weekly days
$days = [
    "Monday", "Tuesday", "Wednesday",
    "Thursday", "Friday", "Saturday", "Sunday"
];

$weekly = [];

foreach ($days as $day) {
    $weekly[$day] = [];
}

// Group classes by day
foreach ($records as $record) {
    if (isset($weekly[$record['day']])) {
        $weekly[$record['day']][] = $record;
    }
}

// Get unique subjects
$subjects = [];

foreach ($records as $record) {
    $subject = trim($record['subject']);

    if ($subject !== "" && !in_array($subject, $subjects)) {
        $subjects[] = $subject;
    }
}

if (isset($_POST['generate'])) {

    if (empty($subjects)) {
        $message = "No subjects found in your timetable.";
    } else {

        $subject_index = 0;

        $insert = $conn->prepare(
            "INSERT INTO study_sessions
            (student_id, subject, study_date,
             start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, 'Pending')"
        );

        $check = $conn->prepare(
            "SELECT COUNT(*) FROM study_sessions
             WHERE student_id = ?
             AND study_date = ?
             AND start_time = ?
             AND end_time = ?"
        );

        foreach ($weekly as $day => $classes) {

            // Sort classes by starting time
            usort($classes, function ($a, $b) {
                return strtotime($a['start_time'])
                     <=> strtotime($b['start_time']);
            });

            // Find next occurrence of this weekday
            $today = new DateTime('today');

            $target_day = array_search($day, $days) + 1;
            $current_day = (int)$today->format('N');

            $days_ahead = ($target_day - $current_day + 7) % 7;

            // Schedule for the next occurrence, not today
            if ($days_ahead === 0) {
                $days_ahead = 7;
            }

            $study_date = clone $today;
            $study_date->modify("+$days_ahead days");
            $date_value = $study_date->format('Y-m-d');

            // Check gaps between classes
            for ($i = 0; $i < count($classes) - 1; $i++) {

                $gap_start = strtotime($classes[$i]['end_time']);
                $gap_end = strtotime($classes[$i + 1]['start_time']);

                $gap_minutes = ($gap_end - $gap_start) / 60;

                // Only use gaps of 30 minutes or more
                if ($gap_minutes >= 30) {

                    $session_start = date('H:i:s', $gap_start);
                    $session_end = date('H:i:s', $gap_start + 1800);

                    $subject = $subjects[
                        $subject_index % count($subjects)
                    ];

                    // Check for an existing session
                    $check->execute([
                        $student_id,
                        $date_value,
                        $session_start,
                        $session_end
                    ]);

                    $exists = $check->fetchColumn();

                    if (!$exists) {

                        $insert->execute([
                            $student_id,
                            $subject,
                            $date_value,
                            $session_start,
                            $session_end
                        ]);

                        $generated[] = [
                            "day" => $day,
                            "date" => $date_value,
                            "subject" => $subject,
                            "start" => $session_start,
                            "end" => $session_end
                        ];

                        $subject_index++;
                    }
                }
            }
        }

        $message = count($generated) .
                   " new study sessions generated successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Study Plan</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 30px;
        }

        h1 {
            color: #263b60;
        }

        button {
            background: #263b60;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #263b60;
            color: white;
        }
    </style>
</head>

<body>

<h1>Personalized Study Plan Generator</h1>

<p>
This system creates 30-minute study sessions
during available gaps between your classes.
</p>

<form method="POST">
    <button type="submit" name="generate">
        Generate My Study Plan
    </button>
</form>

<h3>
    <?= htmlspecialchars($message) ?>
</h3>

<?php if (!empty($generated)): ?>

<table>
    <tr>
        <th>Day</th>
        <th>Date</th>
        <th>Subject</th>
        <th>Start Time</th>
        <th>End Time</th>
    </tr>

    <?php foreach ($generated as $session): ?>
    <tr>
        <td><?= htmlspecialchars($session['day']) ?></td>
        <td><?= htmlspecialchars($session['date']) ?></td>
        <td><?= htmlspecialchars($session['subject']) ?></td>
        <td><?= htmlspecialchars($session['start']) ?></td>
        <td><?= htmlspecialchars($session['end']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php endif; ?>

</body>
</html>
