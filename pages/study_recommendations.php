
<?php
require_once "../config/database.php";

$student_id = 1;

// Get subject-wise study progress
$stmt = $conn->prepare(
    "SELECT subject,
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM (
        SELECT subject, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT subject, status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS all_sessions
     GROUP BY subject
     ORDER BY subject"
);

$stmt->execute([$student_id, $student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Generate study recommendations
$recommendations = [];

foreach ($subjects as $subject) {

    $name = htmlspecialchars($subject['subject']);
    $total = (int)$subject['total'];
    $completed = (int)$subject['completed'];
    $pending = $total - $completed;

    $percentage = $total > 0
        ? round(($completed / $total) * 100)
        : 0;

    if ($percentage >= 75) {

        $priority = "Good Progress";
        $message = "Excellent work! Continue maintaining your study routine.";

    } elseif ($percentage >= 40) {

        $priority = "Medium Priority";
        $message = "You are making progress. Complete your remaining sessions.";

    } else {

        $priority = "High Priority";
        $message = "Focus more on this subject and prioritize its pending sessions.";

    }

    $recommendations[] = [
        'subject' => $name,
        'total' => $total,
        'completed' => $completed,
        'pending' => $pending,
        'percentage' => $percentage,
        'priority' => $priority,
        'message' => $message
    ];
}
// Generate daily study suggestions

$top_subject = null;
$highest_pending = 0;

foreach ($recommendations as $item) {

    if ($item['pending'] > $highest_pending) {

        $highest_pending = $item['pending'];

        $top_subject = $item['subject'];
    }
}

if ($top_subject !== null) {

    $daily_suggestion = "Start your study session with "
        . $top_subject
        . " because it has the most pending sessions.";

} else {

    $daily_suggestion = "Great work! You have no pending study sessions.";
}

$daily_tip = "Follow your timetable and take short breaks between study sessions.";

?>

<!DOCTYPE html>
<html>
<head>
    <title>AI Study Recommendations</title>
</head>

<body>

<h1>AI Study Recommendations</h1>

<p>Personalized suggestions based on your study progress.</p>
<div style="background:#e3f2fd;
            padding:20px;
            margin:20px 0;
            border-radius:10px;">

    <h2>Today's Study Suggestions</h2>

    <h3>Recommended Subject</h3>

    <p><?= htmlspecialchars($daily_suggestion) ?></p>

    <h3>Daily Study Tip</h3>

    <p><?= htmlspecialchars($daily_tip) ?></p>

</div>

<?php if (empty($recommendations)): ?>

    <p>No study data available yet. Generate your study schedule first.</p>

<?php else: ?>

    <?php foreach ($recommendations as $item): ?>

        <div style="border:1px solid #ccc;
                    padding:20px;
                    margin:15px 0;
                    border-radius:10px;">

            <h2><?= $item['subject'] ?></h2>
            <p>
    <strong>Priority:</strong>
    <?= htmlspecialchars($item['priority']) ?>
</p>

            <p>Total Sessions: <?= $item['total'] ?></p>

            <p>Completed: <?= $item['completed'] ?></p>

            <p>Pending: <?= $item['pending'] ?></p>

            <p>Progress: <?= $item['percentage'] ?>%</p>

            <h3>Recommendation</h3>

            <p><?= htmlspecialchars($item['message']) ?></p>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
