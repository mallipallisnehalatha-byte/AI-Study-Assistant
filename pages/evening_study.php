<?php
require_once "../config/database.php";

$student_id = 1;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject1 = trim($_POST["subject1"] ?? "");
    $subject2 = trim($_POST["subject2"] ?? "");

    if ($subject1 === "" || $subject2 === "") {
        $message = "Please enter both subjects.";
    } else {
        $schedule = [
            [$subject1, "18:00:00", "18:45:00"],
            ["Short Break", "18:45:00", "19:00:00"],
            [$subject2, "19:00:00", "19:45:00"],
            ["Short Break", "19:45:00", "20:00:00"],
            [$subject1, "20:00:00", "20:45:00"],
            ["Assignments and Pending Tasks", "20:45:00", "21:30:00"]
        ];

        $check = $conn->prepare(
            "SELECT COUNT(*) FROM evening_study_sessions
             WHERE student_id = ? AND study_date = ?
             AND subject = ? AND start_time = ? AND end_time = ?"
        );
        $insert = $conn->prepare(
            "INSERT INTO evening_study_sessions
             (student_id, subject, study_date, start_time, end_time)
             VALUES (?, ?, ?, ?, ?)"
        );

        $inserted = 0;

        for ($i = 0; $i < 7; $i++) {
            $date = date("Y-m-d", strtotime("+$i days"));

            foreach ($schedule as $slot) {
                $check->execute([
                    $student_id, $date, $slot[0], $slot[1], $slot[2]
                ]);

                if ((int) $check->fetchColumn() > 0) {
                    continue;
                }

                $insert->execute([
                    $student_id, $slot[0], $date, $slot[1], $slot[2]
                ]);
                $inserted += $insert->rowCount();
            }
        }

        $message = "$inserted evening schedule entries saved successfully!";
    }
}

$stmt = $conn->prepare(
    "SELECT * FROM evening_study_sessions
     WHERE student_id = ?
     ORDER BY study_date, start_time"
);
$stmt->execute([$student_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Evening Study Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 25px;
        }
        .container {
            max-width: 950px;
            margin: auto;
        }
        h1 {
            color: #26345c;
        }
        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }
        input {
            width: 95%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            background: #3949ab;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #3949ab;
            color: white;
        }
        .message {
            color: green;
            font-weight: bold;
        }
        .back {
            color: #26345c;
        }
    </style>
</head>
<body>
<div class="container">
    <a class="back" href="../index.php">&larr; Home</a>
    <h1>Evening Study Management</h1>
    <p>Daily Study Time: 6:00 PM – 9:30 PM</p>
    <p>Schedule: Monday to Sunday</p>
    <button type="button" id="enableEveningNotifications">
        Enable Evening Notifications
    </button>
    <p id="notificationStatus"></p>

    <div class="card">
        <h2>Generate Evening Study Plan</h2>
        <?php if ($message !== ""): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="POST">
            <label>First Subject</label>
            <input type="text" name="subject1"
                   placeholder="Example: DBMS" required>
            <label>Second Subject</label>
            <input type="text" name="subject2"
                   placeholder="Example: DLD" required>
            <button type="submit">Generate Evening Schedule</button>
        </form>
    </div>

    <div class="card">
        <h2>Your Evening Study Schedule</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Subject / Activity</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($sessions as $session): ?>
            <tr>
                <td><?= htmlspecialchars($session["study_date"]) ?></td>
                <td><?= htmlspecialchars($session["subject"]) ?></td>
                <td><?= date("h:i A", strtotime($session["start_time"])) ?></td>
                <td><?= date("h:i A", strtotime($session["end_time"])) ?></td>
                <td>
                    <?php if ($session["subject"] === "Short Break"): ?>
                        Break
                    <?php else: ?>
                        <?= htmlspecialchars($session["status"]) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (
                        $session["status"] === "Pending" &&
                        $session["subject"] !== "Short Break"
                    ): ?>
                        <form action="complete_evening_session.php" method="POST">
                            <input type="hidden" name="session_id"
                                   value="<?= (int)$session["id"] ?>">
                            <button type="submit">Mark as Completed</button>
                        </form>
                    <?php elseif ($session["status"] === "Completed"): ?>
                        Completed!
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<script>
const eveningSessions = <?= json_encode($sessions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const notificationButton = document.getElementById("enableEveningNotifications");
const notificationStatus = document.getElementById("notificationStatus");

notificationButton.addEventListener("click", async function () {
    if (!("Notification" in window)) {
        notificationStatus.textContent = "Your browser does not support notifications.";
        return;
    }
    const permission = await Notification.requestPermission();
    notificationStatus.textContent = permission === "granted"
        ? "Evening notifications enabled successfully!"
        : "Please allow notifications in your browser.";
    if (permission === "granted") {
        checkEveningNotifications();
    }
});

function checkEveningNotifications() {
    if (Notification.permission !== "granted") {
        return;
    }
    const now = new Date();
    eveningSessions.forEach(function (session) {
        if (
            session.status !== "Pending" ||
            session.subject === "Short Break"
        ) {
            return;
        }
        const dateParts = session.study_date.split("-");
        const timeParts = session.start_time.split(":");
        const startTime = new Date(
            Number(dateParts[0]),
            Number(dateParts[1]) - 1,
            Number(dateParts[2]),
            Number(timeParts[0]),
            Number(timeParts[1]),
            0
        );
        const reminderTime = new Date(startTime.getTime() - 10 * 60 * 1000);
        const difference = now - reminderTime;
        const notificationKey = "evening_" + session.id;

        if (
            difference >= 0 && difference < 60000 &&
            !sessionStorage.getItem(notificationKey)
        ) {
            new Notification("Evening Study Reminder", {
                body: session.subject + " starts in 10 minutes!"
            });
            sessionStorage.setItem(notificationKey, "sent");
        }
    });
}

setInterval(checkEveningNotifications, 15000);
checkEveningNotifications();
</script>
</body>
</html>
