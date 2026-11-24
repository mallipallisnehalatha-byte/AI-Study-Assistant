<?php
require_once __DIR__ . "/../config/database.php";

$student_id = 1;

$sql = "SELECT t.day, s.subject_name AS subject,
               tc.teacher_name, t.start_time, t.end_time
        FROM timetable t
        JOIN subjects s ON s.id = t.subject_id
        LEFT JOIN teachers tc ON tc.id = t.teacher_id
        WHERE t.student_id = ?
        ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday',
                       'Thursday', 'Friday', 'Saturday', 'Sunday'),
                 t.start_time";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$rows = $stmt->fetchAll();

function safe($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Weekly Timetable</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            padding: 25px;
        }
        h2 {
            text-align: center;
            color: #243b64;
        }
        .table-container {
            overflow-x: auto;
            background: white;
            padding: 15px;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 13px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #243b64;
            color: white;
        }
        tr:nth-child(even) {
            background: #f1f4fa;
        }
        .back {
            display: inline-block;
            margin-bottom: 15px;
            color: #243b64;
        }
    </style>
</head>
<body>
<a class="back" href="../index.php">&larr; Home</a>
<h2>My Weekly Timetable</h2>
<div class="table-container">
<table>
    <tr>
        <th>Day</th>
        <th>Subject</th>
        <th>Teacher</th>
        <th>Start Time</th>
        <th>End Time</th>
    </tr>
    <?php if (count($rows) > 0): ?>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= safe($row['day']) ?></td>
            <td><?= safe($row['subject']) ?></td>
            <td><?= safe($row['teacher_name'] ?: '-') ?></td>
            <td><?= safe($row['start_time']) ?></td>
            <td><?= safe($row['end_time']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="5">No timetable records found.</td>
        </tr>
    <?php endif; ?>
</table>
</div>
</body>
</html>
