
<?php
require_once "../config/database.php";

$student_id = 1;

$sql = "SELECT * FROM study_sessions
        WHERE student_id = ?
        ORDER BY study_date, start_time";

$stmt = $conn->prepare($sql);
$stmt->execute([$student_id]);

$sessions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Study Plan</title>

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

<h1>My Personalized Study Plan</h1>

<table>
    <tr>
        <th>Subject</th>
        <th>Date</th>
        <th>Start Time</th>
        <th>End Time</th>
        <th>Status</th>
    </tr>

    <?php foreach ($sessions as $session): ?>
    <tr>
        <td><?= htmlspecialchars($session['subject']) ?></td>
        <td><?= htmlspecialchars($session['study_date']) ?></td>
        <td><?= htmlspecialchars($session['start_time']) ?></td>
        <td><?= htmlspecialchars($session['end_time']) ?></td>
        <td>
    <?= htmlspecialchars($session['status']) ?>

    <?php if ($session['status'] === 'Pending'): ?>
        <form method="POST" action="update_progress.php">
            <input type="hidden" name="session_id"
                   value="<?= (int)$session['id'] ?>">

            <button type="submit">
                Mark Completed
            </button>
        </form>
    <?php endif; ?>
</td>
    </tr>
    <?php endforeach; ?>

</table>

<h3>Total Study Sessions: <?= count($sessions) ?></h3>

</body>
</html>
