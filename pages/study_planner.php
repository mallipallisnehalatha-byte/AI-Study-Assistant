
<?php
session_start();

include "../config/database.php";

date_default_timezone_set("Asia/Kolkata");

$student_id = 1;

$days = [
    "Monday" => 1,
    "Tuesday" => 2,
    "Wednesday" => 3,
    "Thursday" => 4,
    "Friday" => 5,
    "Saturday" => 6,
    "Sunday" => 7
];

// Activities that should not become study subjects
$excludedSubjects = [
    "SPORTS",
    "SHORT BREAK",
    "LIBRARY",
    "OFFICE",
    "MENTOR"
];

// Fetch timetable
$sql = "SELECT t.day, t.start_time, t.end_time,
               s.subject_name AS subject
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        WHERE t.student_id = :student_id
        ORDER BY t.start_time";

$stmt = $conn->prepare($sql);
$stmt->execute([':student_id' => $student_id]);

$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$timetable = [];

foreach ($records as $row) {
    $timetable[$row['day']][] = $row;
}

// Prepare queries
$check = $conn->prepare(
    "SELECT COUNT(*) FROM study_sessions
     WHERE student_id = :student_id
     AND study_date = :study_date
     AND start_time = :start_time
     AND end_time = :end_time"
);

$insert = $conn->prepare(
    "INSERT INTO study_sessions
    (student_id, subject, study_date,
     start_time, end_time, status)
     VALUES
    (:student_id, :subject, :study_date,
     :start_time, :end_time, 'Pending')"
);

$saved = 0;
$skipped = 0;

$now = new DateTimeImmutable('now');

foreach ($days as $day => $day_number) {

    if (!isset($timetable[$day])) {
        continue;
    }

    $classes = $timetable[$day];

    // Sort classes by starting time
    usort($classes, function ($a, $b) {
        return strcmp($a['start_time'], $b['start_time']);
    });

    // Merge overlapping timetable periods
    $blocks = [];

    foreach ($classes as $class) {

        $start = $class['start_time'];
        $end = $class['end_time'];

        if (empty($blocks)) {
            $blocks[] = [
                'start' => $start,
                'end' => $end
            ];
            continue;
        }

        $last = count($blocks) - 1;

        if ($start <= $blocks[$last]['end']) {

            if ($end > $blocks[$last]['end']) {
                $blocks[$last]['end'] = $end;
            }

        } else {

            $blocks[] = [
                'start' => $start,
                'end' => $end
            ];
        }
    }

    // Find gaps between merged class blocks
    for ($i = 0; $i < count($blocks) - 1; $i++) {

        $gapStart = $blocks[$i]['end'];
        $gapEnd = $blocks[$i + 1]['start'];

        if ($gapEnd <= $gapStart) {
            continue;
        }

        // Ignore gaps shorter than 20 minutes
        $startTime = strtotime($gapStart);
        $endTime = strtotime($gapEnd);

        $minutes = ($endTime - $startTime) / 60;

        if ($minutes < 20) {
            continue;
        }

        // Choose the next class subject as study focus
        $subject = "";

        foreach ($classes as $class) {

            if ($class['start_time'] >= $gapEnd) {

                $candidate = trim($class['subject']);

                if (!in_array(
                    strtoupper($candidate),
                    $excludedSubjects,
                    true
                )) {
                    $subject = $candidate;
                    break;
                }
            }
        }

        if ($subject === "") {
            continue;
        }

        // Find next occurrence of weekday
        $offset = (
            $day_number - (int)$now->format('N') + 7
        ) % 7;

        $study_date = $now->modify(
            '+' . $offset . ' days'
        )->format('Y-m-d');

        $start_datetime = new DateTimeImmutable(
            $study_date . ' ' . $gapStart
        );

        // If the time has passed, schedule next week
        if ($start_datetime <= $now) {

            $study_date = $now->modify(
                '+' . ($offset + 7) . ' days'
            )->format('Y-m-d');
        }

        // Check exact duplicate
        $check->execute([
            ':student_id' => $student_id,
            ':study_date' => $study_date,
            ':start_time' => $gapStart,
            ':end_time' => $gapEnd
        ]);

        if ($check->fetchColumn() > 0) {
            $skipped++;
            continue;
        }

        // Prevent overlap with existing sessions
        $overlap = $conn->prepare(
            "SELECT COUNT(*) FROM study_sessions
             WHERE student_id = :student_id
             AND study_date = :study_date
             AND start_time < :end_time
             AND end_time > :start_time"
        );

        $overlap->execute([
            ':student_id' => $student_id,
            ':study_date' => $study_date,
            ':start_time' => $gapStart,
            ':end_time' => $gapEnd
        ]);

        if ($overlap->fetchColumn() > 0) {
            $skipped++;
            continue;
        }

        // Insert new study session
        $insert->execute([
            ':student_id' => $student_id,
            ':subject' => $subject,
            ':study_date' => $study_date,
            ':start_time' => $gapStart,
            ':end_time' => $gapEnd
        ]);

        $saved++;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Personalized Study Planner</title>
</head>

<body>

<h1>AI Personalized Study Planner</h1>

<h2>Study Sessions Generated</h2>

<p>New sessions saved: <?php echo $saved; ?></p>

<p>Duplicate or overlapping sessions skipped:
<?php echo $skipped; ?></p>

<h2>Your Weekly Study Plan</h2>

<?php

$query = $conn->prepare(
    "SELECT id, subject, study_date, start_time,
            end_time, status
     FROM study_sessions
     WHERE student_id = :student_id
     ORDER BY study_date, start_time"
);

$query->execute([
    ':student_id' => $student_id
]);

$sessions = $query->fetchAll(PDO::FETCH_ASSOC);


if (count($sessions) > 0) {

    foreach ($sessions as $session) {

        echo "<hr>";

        echo "<h3>" .
            htmlspecialchars($session['subject']) .
            "</h3>";

        echo "<p>Date: " .
            htmlspecialchars($session['study_date']) .
            "</p>";

        echo "<p>Time: " .
            htmlspecialchars($session['start_time']) .
            " - " .
            htmlspecialchars($session['end_time']) .
            "</p>";
            if ($session['status'] === 'Pending') {

    echo '<form action="complete_session.php" method="POST">';

    echo '<input type="hidden" name="session_id"
          value="' . (int)$session['id'] . '">';

    echo '<button type="submit">
          Mark as Completed
          </button>';

    echo '</form>';

} else {

    echo "<p>Study session completed!</p>";

}

        echo "<p>Status: " .
            htmlspecialchars($session['status']) .
            "</p>";
    }

} else {

    echo "<p>No study sessions available yet.</p>";
}

?>

</body>
</html>
