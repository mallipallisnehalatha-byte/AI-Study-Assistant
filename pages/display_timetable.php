
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

$student_id = 1;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$sql = "SELECT t.id, t.day, s.subject_name AS subject,
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
 // Detect Existing Timetable Conflicts

$conflict_ids = [];
$conflicts = [];

for ($i = 0; $i < count($rows); $i++) {

    for ($j = $i + 1; $j < count($rows); $j++) {

        if (
            $rows[$i]['day'] === $rows[$j]['day'] &&
            $rows[$i]['start_time'] < $rows[$j]['end_time'] &&
            $rows[$i]['end_time'] > $rows[$j]['start_time']
        ) {

            // Store IDs of conflicting classes
            $conflict_ids[] = $rows[$i]['id'];
            $conflict_ids[] = $rows[$j]['id'];

            // Store conflict details
            $conflicts[] = [
                'day' => $rows[$i]['day'],
                'subject1' => $rows[$i]['subject'],
                'start1' => $rows[$i]['start_time'],
                'end1' => $rows[$i]['end_time'],
                'subject2' => $rows[$j]['subject'],
                'start2' => $rows[$j]['start_time'],
                'end2' => $rows[$j]['end_time']
            ];
        }
    }
}

// Remove duplicate timetable IDs
$conflict_ids = array_unique($conflict_ids);     
// Timetable Statistics

$total_classes = count($rows);

$subjects_list = [];
$total_minutes = 0;
$daily_classes = [];

foreach ($rows as $row) {

    // Count unique subjects
    $subjects_list[] = $row['subject'];

    // Calculate class duration
    $start = new DateTime($row['start_time']);
    $end = new DateTime($row['end_time']);

    $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;

    $total_minutes += $duration;

    // Count classes for each day
    $day = $row['day'];

    if (!isset($daily_classes[$day])) {
        $daily_classes[$day] = 0;
    }

    $daily_classes[$day]++;
}

$total_subjects = count(array_unique($subjects_list));
// Subject-wise Class Statistics

$subject_counts = array_count_values($subjects_list);

ksort($subject_counts);


// Subject-wise Total Duration

$subject_durations = [];

foreach ($rows as $row) {

    $subject = $row['subject'];

    $start = new DateTime($row['start_time']);
    $end = new DateTime($row['end_time']);

    $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;

    if (!isset($subject_durations[$subject])) {
        $subject_durations[$subject] = 0;
    }

    $subject_durations[$subject] += $duration;
}

// Subject-wise Total Duration

$subject_durations = [];

foreach ($rows as $row) {

    $subject = $row['subject'];

    $start = new DateTime($row['start_time']);
    $end = new DateTime($row['end_time']);

    $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;

    if (!isset($subject_durations[$subject])) {
        $subject_durations[$subject] = 0;
    }

    $subject_durations[$subject] += $duration;
}
// Daily Class Statistics

$week_days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday"
];

foreach ($week_days as $day) {
    if (!isset($daily_classes[$day])) {
        $daily_classes[$day] = 0;
    }
}

$total_hours = floor($total_minutes / 60);
$remaining_minutes = $total_minutes % 60;

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

        .edit-btn, .delete-btn {
            display: inline-block;
            padding: 8px 13px;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .edit-btn {
            background: #243b64;
        }

        .delete-btn {
            background: #c0392b;
        }

        .edit-btn:hover {
            background: #36598c;
        }

        .delete-btn:hover {
            background: #922b21;
        }

        .success {
            background: #dff5e5;
            color: #17643b;
            padding: 12px;
            border-radius: 5px;
        }

        .error {
            background: #ffe5e5;
            color: #a00000;
            padding: 12px;
            border-radius: 5px;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-container {
            background: white;
            padding: 18px;
            margin-bottom: 18px;
            border-radius: 10px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-container input,
        .filter-container select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-container input {
            flex: 1;
            min-width: 180px;
        }

        .filter-container select {
            min-width: 150px;
        }

        .clear-btn {
            padding: 10px 16px;
            background: #243b64;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #a00000;
        }
        .print-btn {
    padding: 10px 16px;
    background: #17643b;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.print-btn:hover {
    background: #124d2e;
}
@media print {
th:last-child,
td:last-child {
    display: none;
}
    body {
        background: white;
        padding: 0;
    }

    .back,
    .filter-container,
    .actions,
    .success,
    .error {
        display: none !important;
    }

    .table-container {
        padding: 0;
    }

    table {
        width: 100%;
    }

    th {
        background: #243b64 !important;
        color: white !important;
        print-color-adjust: exact;
    }
}
.statistics-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.stat-card h3 {
    color: #243b64;
    font-size: 16px;
}

.stat-card p {
    color: #17643b;
    font-size: 24px;
    font-weight: bold;
}

@media (max-width: 650px) {
    .statistics-container {
        grid-template-columns: 1fr;
    }
}
.daily-statistics {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.daily-statistics h3 {
    color: #243b64;
    text-align: center;
    margin-bottom: 20px;
}

.daily-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.daily-card {
    background: #f1f4fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.daily-card h4 {
    color: #243b64;
}

.daily-card p {
    color: #17643b;
    font-weight: bold;
}

@media (max-width: 650px) {
    .daily-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
/* Subject-wise Class Statistics */

.subject-statistics {
    background: white;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.subject-statistics h3 {
    color: #243b64;
    text-align: center;
    margin-bottom: 20px;
}

.subject-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.subject-card {
    background: #f1f4fa;
    padding: 18px;
    border-radius: 10px;
    text-align: center;
    border: 1px solid #e0e6f0;
    transition: transform 0.2s, box-shadow 0.2s;
}

.subject-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 5px 14px rgba(0, 0, 0, 0.12);
}

.subject-card h4 {
    color: #243b64;
    margin-bottom: 10px;
    font-size: 16px;
}

.subject-card p {
    color: #17643b;
    font-weight: bold;
    margin: 8px 0;
}

@media (max-width: 650px) {
    .subject-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
/* Timetable Conflict Warning */

.conflict-warning {
    background: #fff3f3;
    border: 1px solid #e5a1a1;
    border-left: 5px solid #d93025;
    padding: 20px;
    margin: 20px 0;
    border-radius: 10px;
}

.conflict-warning h3 {
    color: #b42318;
    margin-bottom: 10px;
}

.conflict-warning > p {
    color: #7f1d1d;
    font-weight: bold;
}

.conflict-item {
    background: white;
    padding: 12px;
    margin-top: 12px;
    border-radius: 7px;
}

.conflict-item strong {
    color: #243b64;
}

.conflict-item p {
    margin: 6px 0;
    color: #7f1d1d;
}

.no-conflicts {
    background: #eaf7ee;
    border: 1px solid #a8d5b5;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    text-align: center;
    color: #17643b;
    font-weight: bold;
}
    </style>
</head>

<body>

<a class="back" href="../index.php">&larr; Home</a>

<h2>My Weekly Timetable</h2>
<!-- Timetable Conflict Warning -->

<?php if (!empty($conflicts)): ?>

    <div class="conflict-warning">

        <h3>⚠ Timetable Conflict Warning</h3>

        <p>
            <?= count($conflicts) ?>
            timetable conflict(s) detected.
            Please review the following classes.
        </p>

        <?php foreach ($conflicts as $conflict): ?>

            <div class="conflict-item">

                <strong><?= safe($conflict['day']) ?></strong>

                <p>
                    <?= safe($conflict['subject1']) ?>
                    (<?= safe($conflict['start1']) ?> -
                    <?= safe($conflict['end1']) ?>)
                </p>

                <p>
                    overlaps with
                </p>

                <p>
                    <?= safe($conflict['subject2']) ?>
                    (<?= safe($conflict['start2']) ?> -
                    <?= safe($conflict['end2']) ?>)
                </p>

            </div>

        <?php endforeach; ?>

    </div>

<?php else: ?>

    <div class="no-conflicts">
        <p>✓ No timetable conflicts found!</p>
    </div>

<?php endif; ?>
<!-- Timetable Statistics -->

<div class="statistics-container">

    <div class="stat-card">
        <h3>Total Classes</h3>
        <p><?= $total_classes ?></p>
    </div>

    <div class="stat-card">
        <h3>Total Subjects</h3>
        <p><?= $total_subjects ?></p>
    </div>

    <div class="stat-card">
        <h3>Total Class Hours</h3>
        <p>
            <?= $total_hours ?> hrs <?= $remaining_minutes ?> mins
        </p>
    </div>

</div>

<!-- Daily Class Statistics -->

<div class="daily-statistics">

    <h3>Daily Class Statistics</h3>

    <div class="daily-grid">

        <?php foreach ($week_days as $day): ?>

            <div class="daily-card">

                <h4><?= safe($day) ?></h4>

                <p>
                    <?= (int)$daily_classes[$day] ?>
                    Classes
                </p>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<!-- Subject-wise Class Statistics -->

<div class="subject-statistics">

    <h3>Subject-wise Class Statistics</h3>

    <div class="subject-grid">

        <?php foreach ($subject_counts as $subject => $count): ?>

            <div class="subject-card">

                <h4><?= safe($subject) ?></h4>

             <p><?= (int)$count ?> Classes</p>

<p>
    <?php
        $minutes = $subject_durations[$subject];
        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;
    ?>

    <?= $hours ?> hrs <?= $remaining ?> mins
</p> 

            </div>

        <?php endforeach; ?>

    </div>

        </div>

<?php if (isset($_GET["deleted"])): ?>
    <p class="success">Timetable entry deleted successfully!</p>
<?php endif; ?>

<?php if (isset($_GET["updated"])): ?>
    <p class="success">Timetable entry updated successfully!</p>
<?php endif; ?>

<?php if (isset($_GET["error"])): ?>
    <p class="error">Timetable entry could not be deleted.</p>
<?php endif; ?>

<!-- Search and Filter -->
<div class="filter-container">

    <input
        type="text"
        id="searchInput"
        placeholder="Search subject or teacher..."
    >

    <select id="dayFilter">
        <option value="">All Days</option>
        <option value="Monday">Monday</option>
        <option value="Tuesday">Tuesday</option>
        <option value="Wednesday">Wednesday</option>
        <option value="Thursday">Thursday</option>
        <option value="Friday">Friday</option>
        <option value="Saturday">Saturday</option>
        <option value="Sunday">Sunday</option>
    </select>

    <button type="button" class="clear-btn" onclick="clearFilters()">
        Clear
    </button>
    <button type="button" class="print-btn" onclick="window.print()">
    Print / Download PDF
</button>

</div>

<div class="table-container">

<table id="timetable">

    <thead>
        <tr>
            <th>Day</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>

    <?php if (count($rows) > 0): ?>

        <?php foreach ($rows as $row): ?>

        <tr class="timetable-row">

            <td><?= safe($row['day']) ?></td>
            <td><?= safe($row['subject']) ?></td>
            <td><?= safe($row['teacher_name'] ?: '-') ?></td>
            <td><?= safe($row['start_time']) ?></td>
            <td><?= safe($row['end_time']) ?></td>

            <td>
                <div class="actions">

                    <a class="edit-btn"
                       href="edit_timetable.php?id=<?= (int)$row['id'] ?>">
                        Edit
                    </a>

                    <form method="POST"
                          action="delete_timetable.php"
                          onsubmit="return confirm('Are you sure you want to delete this timetable entry?');">

                        <input type="hidden"
                               name="id"
                               value="<?= (int)$row['id'] ?>">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= safe($_SESSION['csrf_token']) ?>">

                        <button type="submit" class="delete-btn">
                            Delete
                        </button>

                    </form>

                </div>
            </td>

        </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>
            <td colspan="6">No timetable records found.</td>
        </tr>

    <?php endif; ?>

    <tr id="noResults" style="display:none;">
        <td colspan="6" class="no-results">
            No matching timetable records found.
        </td>
    </tr>

    </tbody>

</table>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const dayFilter = document.getElementById("dayFilter");
const timetableRows = document.querySelectorAll(".timetable-row");
const noResults = document.getElementById("noResults");

function filterTimetable() {

    const searchText = searchInput.value.toLowerCase().trim();
    const selectedDay = dayFilter.value;

    let visibleCount = 0;

    timetableRows.forEach(row => {

        const day = row.cells[0].textContent.trim();
        const subject = row.cells[1].textContent.toLowerCase();
        const teacher = row.cells[2].textContent.toLowerCase();

        const matchesSearch =
            subject.includes(searchText) ||
            teacher.includes(searchText);

        const matchesDay =
            selectedDay === "" || day === selectedDay;

        if (matchesSearch && matchesDay) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }

    });

    noResults.style.display =
        visibleCount === 0 ? "" : "none";
}

function clearFilters() {
    searchInput.value = "";
    dayFilter.value = "";
    filterTimetable();
}

searchInput.addEventListener("input", filterTimetable);
dayFilter.addEventListener("change", filterTimetable);
</script>

</body>
</html>
