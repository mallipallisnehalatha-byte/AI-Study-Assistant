<?php
require_once "../config/database.php";

$student_id = 1;

// Get overall study statistics
$stmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END)
            AS completed_sessions,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END)
            AS pending_sessions
     FROM (
        SELECT status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS all_sessions"
);

$stmt->execute([$student_id, $student_id]);

$report = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate completion percentage
$total = (int)$report['total_sessions'];
$completed = (int)$report['completed_sessions'];
$pending = (int)$report['pending_sessions'];

$percentage = $total > 0
    ? round(($completed / $total) * 100)
    : 0;

// Get total study time
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(minutes_spent), 0)
     FROM progress
     WHERE student_id = ?"
);

$stmt->execute([$student_id]);

$total_minutes = (int)$stmt->fetchColumn();

$hours = floor($total_minutes / 60);
$minutes = $total_minutes % 60;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Study Progress Report</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            color: #333;
        }

        h1, h2 {
            color: #263b63;
        }

        .report-box {
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin-top: 20px;
        }

        .stat {
            margin: 15px 0;
            font-size: 18px;
        }

        button {
            padding: 12px 20px;
            background: #263b63;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>

    <h1>Student Study Progress Report</h1>

    <p>Report Date: <?= date("d-m-Y") ?></p>

    <div class="report-box">

        <h2>Overall Study Performance</h2>

        <div class="stat">
            Total Sessions: <?= $total ?>
        </div>

        <div class="stat">
            Completed Sessions: <?= $completed ?>
        </div>

        <div class="stat">
            Pending Sessions: <?= $pending ?>
        </div>

        <div class="stat">
            Completion Percentage: <?= $percentage ?>%
        </div>

        <div class="stat">
            Total Study Time: <?= $hours ?> hrs <?= $minutes ?> mins
        </div>

    </div>

    <br>

    <button onclick="window.print()">
        Download PDF Report
    </button>

</body>
</html>