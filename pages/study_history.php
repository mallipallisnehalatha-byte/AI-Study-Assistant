
<?php
require_once "../config/database.php";

$student_id = 1;

// Get completed study sessions
$sql = "SELECT
            s.subject,
            s.study_date,
            s.start_time,
            s.end_time,
            p.minutes_spent,
            p.activity,
            p.recorded_at
        FROM study_sessions s
        LEFT JOIN progress p
            ON s.id = p.session_id
            AND p.student_id = s.student_id
        WHERE s.student_id = ?
        AND s.status = 'Completed'
        ORDER BY s.study_date DESC, s.start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Study History</title>
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
            margin-top: 25px;
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

        .empty {
            background: white;
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<h1>Study History</h1>

<?php if (count($sessions) > 0): ?>

<table>
    <tr>
        <th>Subject</th>
        <th>Study Date</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Minutes Spent</th>
        <th>Activity</th>
    </tr>

    <?php foreach ($sessions as $session): ?>
    <tr>
        <td><?= htmlspecialchars($session['subject']) ?></td>

        <td><?= htmlspecialchars($session['study_date']) ?></td>

        <td><?= htmlspecialchars($session['start_time']) ?></td>

        <td><?= htmlspecialchars($session['end_time']) ?></td>

        <td>
            <?= $session['minutes_spent'] !== null
                ? (int)$session['minutes_spent']
                : 'Not recorded' ?>
        </td>

        <td>
            <?= htmlspecialchars(
                $session['activity'] ?? 'Completed'
            ) ?>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

<?php else: ?>

<div class="empty">
    No completed study sessions found.
</div>

<?php endif; ?>

</body>
</html>
