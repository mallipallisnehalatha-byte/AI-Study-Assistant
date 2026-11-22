
<?php
require_once "../config/database.php";

header("Content-Type: application/json");

$student_id = 1;

$message = strtolower(trim($_POST['message'] ?? ""));

if ($message === "") {
    echo json_encode([
        "reply" => "Please enter a question."
    ]);
    exit;
}

// Get total and completed sessions
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed
     FROM (
        SELECT id, status
        FROM study_sessions
        WHERE student_id = ?

        UNION ALL

        SELECT id, status
        FROM evening_study_sessions
        WHERE student_id = ?
        AND subject != 'Short Break'
     ) AS all_sessions"
);

$stmt->execute([$student_id, $student_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

$total = (int)$progress['total'];
$completed = (int)$progress['completed'];
$pending = $total - $completed;

$percentage = $total > 0
    ? round(($completed / $total) * 100)
    : 0;

// Get subject-wise progress
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
     ) AS all_subjects
     GROUP BY subject
     ORDER BY subject"
);

$stmt->execute([$student_id, $student_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Prepare chatbot response
$reply = "";

if (
    strpos($message, "pending") !== false ||
    strpos($message, "remaining") !== false
) {
    $reply = "You have $pending pending study sessions.";
}
elseif (
    strpos($message, "completed") !== false ||
    strpos($message, "finished") !== false
) {
    $reply = "You have completed $completed study sessions.";
}
elseif (
    strpos($message, "percentage") !== false ||
    strpos($message, "progress") !== false
) {
    $reply = "Your overall study completion is $percentage%.";
}
elseif (
    strpos($message, "total") !== false ||
    strpos($message, "sessions") !== false
) {
    $reply = "You have $total total study sessions.";
}
elseif (
    strpos($message, "subject") !== false ||
    strpos($message, "study today") !== false
) {
    $top_subject = "";
    $highest_pending = 0;

    foreach ($subjects as $subject) {
        $subject_pending =
            (int)$subject['total'] - (int)$subject['completed'];

        if ($subject_pending > $highest_pending) {
            $highest_pending = $subject_pending;
            $top_subject = $subject['subject'];
        }
    }

    if ($top_subject !== "") {
        $reply = "You should prioritize $top_subject. It has $highest_pending pending sessions.";
    } else {
        $reply = "You have no pending sessions. Great work!";
    }
}
elseif (strpos($message, "normalization") !== false) {
    $reply = "Normalization is a DBMS process used to organize database tables and reduce data redundancy.";
}
elseif (strpos($message, "primary key") !== false) {
    $reply = "A Primary Key is a column that uniquely identifies each record in a database table.";
}
elseif (strpos($message, "foreign key") !== false) {
    $reply = "A Foreign Key is a column that connects two tables and maintains referential integrity.";
}
elseif (strpos($message, "sql") !== false) {
    $reply = "SQL stands for Structured Query Language. It is used to create, retrieve, update and delete data in databases.";
}
elseif (strpos($message, "tip") !== false) {
    $reply = "Study one subject at a time, follow your timetable, and take short breaks.";
}
elseif (strpos($message, "dbms") !== false) {
    $reply = "DBMS stands for Database Management System. It is software used to store, organize, retrieve, and manage data in databases. Important topics include SQL, keys, normalization, relationships, and transactions.";
}
else {
    $reply = "I can help with study progress, pending sessions, completed sessions, subject recommendations, DBMS basics, and study tips. Please ask me one of these questions.";
}


$log = $conn->prepare(
    "INSERT INTO chat_history (student_id, user_message, ai_response)
     VALUES (?, ?, ?)"
);
$log->execute([$student_id, $_POST['message'], $reply]);

echo json_encode([
    "reply" => $reply
]);
?>
