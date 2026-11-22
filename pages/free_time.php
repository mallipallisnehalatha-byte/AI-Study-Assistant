
<?php
require_once "../config/database.php";

$student_id = 1;

// Get timetable records
$sql = "SELECT t.day, t.start_time, t.end_time,
               s.subject_name AS subject
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        WHERE t.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);

$records = $stmt->fetchAll();

// Arrange days in weekly order
$days = [
    "Monday", "Tuesday", "Wednesday",
    "Thursday", "Friday", "Saturday"
];

$weekly_timetable = [];

foreach ($days as $day) {
    $weekly_timetable[$day] = [];
}

// Group timetable records by day
foreach ($records as $record) {
    if (isset($weekly_timetable[$record['day']])) {
        $weekly_timetable[$record['day']][] = $record;
    }
}

$free_slots = [];

// Find gaps between classes
foreach ($weekly_timetable as $day => $classes) {

    // Sort classes by starting time
    usort($classes, function ($a, $b) {
        return strtotime($a['start_time'])
             <=> strtotime($b['start_time']);
    });

    for ($i = 0; $i < count($classes) - 1; $i++) {

        $current_end = strtotime($classes[$i]['end_time']);
        $next_start = strtotime($classes[$i + 1]['start_time']);

        // Calculate gap in minutes
        $gap = ($next_start - $current_end) / 60;

        // Consider gaps of 30 minutes or more
        if ($gap >= 30) {
            $free_slots[] = [
                "day" => $day,
                "start_time" => $classes[$i]['end_time'],
                "end_time" => $classes[$i + 1]['start_time'],
                "duration" => $gap
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Free Time Detection</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 30px;
        }

        h1 {
            color: #263b60;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
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

<h1>Free-Time Detection</h1>

<p>Available study gaps between your classes:</p>

<table>
    <tr>
        <th>Day</th>
        <th>Free Time Starts</th>
        <th>Free Time Ends</th>
        <th>Duration (Minutes)</th>
    </tr>

    <?php foreach ($free_slots as $slot): ?>
    <tr>
        <td><?= htmlspecialchars($slot['day']) ?></td>
        <td><?= htmlspecialchars($slot['start_time']) ?></td>
        <td><?= htmlspecialchars($slot['end_time']) ?></td>
        <td><?= (int)$slot['duration'] ?></td>
    </tr>
    <?php endforeach; ?>

</table>

<h3>Total Free Slots: <?= count($free_slots) ?></h3>

<?php if (count($free_slots) == 0): ?>
    <p>No free gaps of 30 minutes or more were found between classes.</p>
<?php endif; ?>

</body>
</html>
