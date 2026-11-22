
<?php
require_once "../config/database.php";

$student_id = 1;

// 1. Total Sessions (regular + evening, excluding breaks)
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM (
        SELECT id FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT id FROM evening_study_sessions
        WHERE student_id = ? AND subject != 'Short Break'
    ) AS all_sessions"
);
$stmt->execute([$student_id, $student_id]);
$total = (int)$stmt->fetchColumn();

// 2. Completed Sessions
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM (
        SELECT id FROM study_sessions
        WHERE student_id = ? AND status = 'Completed'

        UNION ALL

        SELECT id FROM evening_study_sessions
        WHERE student_id = ?
        AND status = 'Completed'
        AND subject != 'Short Break'
    ) AS completed_sessions"
);
$stmt->execute([$student_id, $student_id]);
$completed = (int)$stmt->fetchColumn();

// 3. Pending Sessions
$pending = $total - $completed;

// 4. Completion Percentage
$percentage = $total > 0
    ? round(($completed / $total) * 100)
    : 0;

// 5. Total Study Time
$stmt = $conn->prepare(
    "SELECT COALESCE(SUM(minutes_spent), 0)
     FROM progress
     WHERE student_id = ?"
);
$stmt->execute([$student_id]);

$total_minutes = (int)$stmt->fetchColumn();

$total_hours = floor($total_minutes / 60);
$remaining_minutes = $total_minutes % 60;

// 6. Subject-wise Progress
$stmt = $conn->prepare(
    "SELECT
        subject,
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM (
        SELECT subject, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT
            subject,
            status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS all_subjects
     GROUP BY subject
     ORDER BY subject"
);

$stmt->execute([$student_id, $student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 7. Daily Study Progress
$stmt = $conn->prepare(
    "SELECT
        study_date,
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END)
            AS completed_sessions
     FROM (
        SELECT study_date, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT study_date, status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS daily_sessions
     GROUP BY study_date
     ORDER BY study_date"
);

$stmt->execute([$student_id, $student_id]);

$daily_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 8. Weekly Study Performance
$stmt = $conn->prepare(
    "SELECT
        YEARWEEK(study_date, 1) AS week_number,
        MIN(study_date) AS week_start,
        MAX(study_date) AS week_end,
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END)
            AS completed_sessions,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END)
            AS pending_sessions
     FROM (
        SELECT study_date, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT study_date, status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS weekly_sessions
     GROUP BY YEARWEEK(study_date, 1)
     ORDER BY week_number"
);

$stmt->execute([$student_id, $student_id]);

$weekly_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
// 9. Study Performance Summary

// Find the best-performing day
$best_day = "No data yet";
$best_day_completed = 0;

foreach ($daily_progress as $day) {

    $completed_count = (int)$day['completed_sessions'];

    if ($completed_count > $best_day_completed) {
        $best_day_completed = $completed_count;
        $best_day = $day['study_date'];
    }
}

// Calculate weekly completion percentage
$weekly_summary = [];

foreach ($weekly_progress as $week) {

    $week_total = (int)$week['total_sessions'];

    $week_completed = (int)$week['completed_sessions'];

    $week_percentage = $week_total > 0
        ? round(($week_completed / $week_total) * 100)
        : 0;

    $weekly_summary[] = [
        'week_start' => $week['week_start'],
        'week_end' => $week['week_end'],
        'completion_percentage' => $week_percentage
    ];
}
// 10. Monthly Study Report

$stmt = $conn->prepare(
    "SELECT
        DATE_FORMAT(study_date, '%Y-%m') AS month_name,
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END)
            AS completed_sessions,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END)
            AS pending_sessions
     FROM (
        SELECT study_date, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT study_date, status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS monthly_sessions
     GROUP BY DATE_FORMAT(study_date, '%Y-%m')
     ORDER BY month_name"
);

$stmt->execute([$student_id, $student_id]);

$monthly_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html>
<head>
    <title>Student Progress Dashboard</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 30px;
        }

        h1 {
            color: #263b60;
        }

        .container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 25px;
            width: 200px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .card h2 {
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

        .progress {
            background: #ddd;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            background: #28a745;
            height: 20px;
        }
        .subject-progress {
    background: #ddd;
    height: 15px;
    width: 180px;
    border-radius: 10px;
    overflow: hidden;
    margin: auto;
}

.subject-progress-bar {
    background: #28a745;
    height: 15px;
    border-radius: 10px;
}
    </style>
</head>

<body>

<h1>Student Progress Dashboard</h1>
<a href="download_report.php"
   style="display: inline-block;
          padding: 12px 20px;
          background: #263b63;
          color: white;
          text-decoration: none;
          border-radius: 6px;
          margin: 15px 0;">
    Download Study Report
</a>

<div class="container">


    <div class="card">
        <h3>Total Sessions</h3>
        <h2><?= $total ?></h2>
    </div>

    <div class="card">
        <h3>Completed</h3>
        <h2><?= $completed ?></h2>
    </div>

    <div class="card">
        <h3>Pending</h3>
        <h2><?= $pending ?></h2>
    </div>

    <div class="card">
        <h3>Completion</h3>
        <h2><?= $percentage ?>%</h2>
    </div>
    <div class="card">
    <h3>Total Study Time</h3>
    <h2>
        <?= $total_hours ?> hrs
        <?= $remaining_minutes ?> mins
    </h2>
</div>

</div>

<h2>Overall Study Progress</h2>

<div class="progress">
    <div class="progress-bar"
         style="width: <?= $percentage ?>%;">
    </div>
</div>
<h2>Weekly Study Performance</h2>

<div style="width: 100%; height: 350px; position: relative;">
    <canvas id="weeklyProgressChart"></canvas>
</div>
<h2>Study Performance Summary</h2>

<div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">

    <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; flex: 1;">
        <h3>Best Performing Day</h3>
        <h2><?= htmlspecialchars($best_day) ?></h2>
        <p>Completed Sessions: <?= $best_day_completed ?></p>
    </div>

    <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; flex: 1;">
        <h3>Weekly Completion</h3>

        <?php foreach ($weekly_summary as $week): ?>

            <p>
                <?= htmlspecialchars($week['week_start']) ?>
                to
                <?= htmlspecialchars($week['week_end']) ?>:
                <strong><?= $week['completion_percentage'] ?>%</strong>
            </p>

        <?php endforeach; ?>

    </div>

</div>
<h2>Daily Study Progress</h2>

<div class="progress-chart" style="width: 100%; height: 350px; position: relative;">
    <canvas id="dailyProgressChart"></canvas>
</div>
<h2>Monthly Study Report</h2>

<div style="display: flex; gap: 20px; flex-wrap: wrap; margin: 20px 0;">

    <?php foreach ($monthly_progress as $month): ?>

        <?php
            $total = (int)$month['total_sessions'];
            $completed = (int)$month['completed_sessions'];
            $pending = (int)$month['pending_sessions'];

            $percentage = $total > 0
                ? round(($completed / $total) * 100)
                : 0;
        ?>

        <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; flex: 1; min-width: 220px;">

            <h3><?= htmlspecialchars($month['month_name']) ?></h3>

            <p>Total Sessions: <?= $total ?></p>

            <p>Completed: <?= $completed ?></p>

            <p>Pending: <?= $pending ?></p>

            <h3>Completion: <?= $percentage ?>%</h3>

        </div>

    <?php endforeach; ?>

</div>

<h2>Subject-wise Progress</h2>
<div style="width: 100%; height: 350px; position: relative; margin-bottom: 30px;">
    <canvas id="subjectProgressChart"></canvas>
</div>
<table>
    <tr>
        <th>Subject</th>
        <th>Total Sessions</th>
        <th>Completed</th>
        <th>Progress</th>
    </tr>

    <?php foreach ($subjects as $subject): ?>

<?php
$subject_percentage = $subject['total'] > 0
    ? round(($subject['completed'] / $subject['total']) * 100)
    : 0;
?>

<tr>
    <td><?= htmlspecialchars($subject['subject']) ?></td>

    <td><?= (int)$subject['total'] ?></td>

    <td><?= (int)$subject['completed'] ?></td>

    <td>
        <div class="subject-progress">
            <div class="subject-progress-bar"
                 style="width: <?= $subject_percentage ?>%;">
            </div>
        </div>

        <?= $subject_percentage ?>%
    </td>
</tr>

<?php endforeach; ?>

</table>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dailyData = <?= json_encode($daily_progress) ?>;

const dates = dailyData.map(item => item.study_date);

const completed = dailyData.map(
    item => Number(item.completed_sessions)
);

const pending = dailyData.map(
    item => Number(item.total_sessions) -
            Number(item.completed_sessions)
);

const chartCanvas = document.getElementById("dailyProgressChart");

new Chart(chartCanvas, {
    type: "bar",
    data: {
        labels: dates,
        datasets: [
            {
                label: "Completed Sessions",
                data: completed
            },
            {
                label: "Pending Sessions",
                data: pending
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
<script>
const weeklyData = <?= json_encode($weekly_progress) ?>;

const weeklyLabels = weeklyData.map(item =>
    item.week_start + " to " + item.week_end
);

const weeklyCompleted = weeklyData.map(item =>
    Number(item.completed_sessions)
);

const weeklyPending = weeklyData.map(item =>
    Number(item.pending_sessions)
);

const weeklyCanvas = document.getElementById("weeklyProgressChart");

new Chart(weeklyCanvas, {
    type: "bar",
    data: {
        labels: weeklyLabels,
        datasets: [
            {
                label: "Completed Sessions",
                data: weeklyCompleted
            },
            {
                label: "Pending Sessions",
                data: weeklyPending
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
<script>
const subjectData = <?= json_encode($subjects) ?>;

const subjectLabels = subjectData.map(item => item.subject);

const subjectCompleted = subjectData.map(item =>
    Number(item.completed)
);

const subjectPending = subjectData.map(item =>
    Number(item.total) - Number(item.completed)
);

const subjectCanvas = document.getElementById("subjectProgressChart");

new Chart(subjectCanvas, {
    type: "bar",
    data: {
        labels: subjectLabels,
        datasets: [
            {
                label: "Completed",
                data: subjectCompleted
            },
            {
                label: "Pending",
                data: subjectPending
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            x: {
                stacked: true
            },
            y: {
                stacked: true,
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
</body>
</html>
