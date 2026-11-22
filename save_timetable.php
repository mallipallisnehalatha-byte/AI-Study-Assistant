<?php

require_once __DIR__ . "/config/database.php";

$student_id = 1;
$jsonFile = __DIR__ . "/uploads/verified_timetable.json";

function normalizeSubject(string $value): string
{
    $value = str_replace('\\/', '/', trim($value));
    $value = trim(SUBSTR($value, 0, (int) strpos($value . ' / ', ' / ')));
    $value = trim(SUBSTR($value, 0, (int) strpos($value . ' - ', ' - ')));
    return $value;
}

function normalizeTime(string $value): string
{
    $value = trim($value);
    if (preg_match('/^\d{2}:\d{2}$/', $value)) {
        return $value . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
        return $value;
    }
    throw new Exception("Invalid time value: " . $value);
}

if (!file_exists($jsonFile)) {
    die("Error: Verified timetable file not found.");
}

$timetable = json_decode(file_get_contents($jsonFile), true);

if (!is_array($timetable) || empty($timetable)) {
    die("Error: Invalid or empty timetable JSON.");
}

$validDays = [
    'Monday', 'Tuesday', 'Wednesday', 'Thursday',
    'Friday', 'Saturday', 'Sunday'
];

try {
    $checkStudent = $conn->prepare("SELECT id FROM students WHERE id = ?");
    $checkStudent->execute([$student_id]);
    if (!$checkStudent->fetch()) {
        die("Error: Student ID not found.");
    }

    $findSubject = $conn->prepare(
        "SELECT id FROM subjects
         WHERE student_id = ? AND subject_name = ? LIMIT 1"
    );
    $insertSubject = $conn->prepare(
        "INSERT INTO subjects (student_id, subject_name, difficulty_level)
         VALUES (?, ?, 'Medium')"
    );
    $findTeacher = $conn->prepare(
        "SELECT id FROM teachers
         WHERE student_id = ? AND subject_id = ? LIMIT 1"
    );

    $subjectIds = [];

    foreach ($timetable as $entry) {
        $subjectName = normalizeSubject($entry['subject'] ?? '');
        if ($subjectName === '') {
            throw new Exception("Missing subject name.");
        }
        if (isset($subjectIds[$subjectName])) {
            continue;
        }

        $findSubject->execute([$student_id, $subjectName]);
        $subjectId = $findSubject->fetchColumn();

        if (!$subjectId) {
            $insertSubject->execute([$student_id, $subjectName]);
            $subjectId = (int) $conn->lastInsertId();
        }

        $subjectIds[$subjectName] = (int) $subjectId;
    }

    $conn->beginTransaction();

    $conn->prepare("DELETE FROM timetable WHERE student_id = ?")
        ->execute([$student_id]);

    $insert = $conn->prepare(
        "INSERT INTO timetable
         (student_id, subject_id, teacher_id, day, start_time, end_time)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $count = 0;

    foreach ($timetable as $entry) {
        $day = trim($entry['day'] ?? '');
        if (!in_array($day, $validDays, true)) {
            throw new Exception("Invalid day: " . $day);
        }

        $subjectName = normalizeSubject($entry['subject'] ?? '');
        $start = normalizeTime($entry['start_time'] ?? '');
        $end = normalizeTime($entry['end_time'] ?? '');

        if (strtotime($start) >= strtotime($end)) {
            throw new Exception("Start time must be before end time.");
        }

        $subjectId = $subjectIds[$subjectName];

        $findTeacher->execute([$student_id, $subjectId]);
        $teacherId = $findTeacher->fetchColumn() ?: null;

        $insert->execute([
            $student_id,
            $subjectId,
            $teacherId,
            $day,
            $start,
            $end
        ]);

        $count++;
    }

    $upload = $conn->prepare(
        "INSERT INTO uploaded_timetables
         (student_id, file_name, file_path, extracted_text)
         VALUES (?, ?, ?, ?)"
    );
    $upload->execute([
        $student_id,
        'verified_timetable.json',
        'uploads/verified_timetable.json',
        file_get_contents($jsonFile)
    ]);

    $conn->commit();

    echo "<h2>Timetable saved successfully!</h2>";
    echo "<p>Total records saved: " . $count . "</p>";
    echo "<p>Student ID: " . $student_id . "</p>";
    echo '<p><a href="pages/display_timetable.php">View timetable</a></p>';

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error: " . htmlspecialchars($e->getMessage());
}
