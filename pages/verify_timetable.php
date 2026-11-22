
<?php

// Step 1: Read structured timetable JSON
$jsonFile = __DIR__ . "/../uploads/structured_timetable.json";

if (!file_exists($jsonFile)) {
    die("Structured timetable file not found!");
}

$jsonData = file_get_contents($jsonFile);
$timetable = json_decode($jsonData, true);

if (!is_array($timetable)) {
    die("Invalid timetable JSON file!");
}

$message = "";

// Step 2: Save corrected timetable
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $updatedTimetable = $_POST["timetable"] ?? [];

    $verified = [];

    foreach ($updatedTimetable as $item) {

        $day = trim($item["day"] ?? "");
        $subject = trim($item["subject"] ?? "");
        $start = trim($item["start_time"] ?? "");
        $end = trim($item["end_time"] ?? "");
        $room = trim($item["room"] ?? "");
        $period = trim($item["period"] ?? "");

        // Basic validation
        if (
            $day === "" ||
            $subject === "" ||
            !preg_match('/^\d{2}:\d{2}$/', $start) ||
            !preg_match('/^\d{2}:\d{2}$/', $end) ||
            $start >= $end
        ) {
            die("Invalid timetable details. Please go back and correct them.");
        }

        $verified[] = [
            "day" => $day,
            "period" => $period,
            "start_time" => $start,
            "end_time" => $end,
            "subject" => $subject,
            "room" => $room
        ];
    }

    // Save verified timetable separately
    $outputFile = __DIR__ . "/../uploads/verified_timetable.json";

    $saved = file_put_contents(
        $outputFile,
        json_encode($verified, JSON_PRETTY_PRINT),
        LOCK_EX
    );

    if ($saved !== false) {
        $message = "Timetable verified and saved successfully!";
        $timetable = $verified;
    } else {
        $message = "Unable to save timetable. Check folder permissions.";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Timetable</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 25px;
        }

        h1 {
            text-align: center;
            color: #243b55;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #243b55;
            color: white;
        }

        input {
            width: 95%;
            padding: 8px;
            box-sizing: border-box;
        }

        button {
            background: #1769aa;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .message {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Student Timetable Verification</h1>

    <p>
        Check your extracted timetable.
        Correct any mistakes before confirming.
    </p>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <table>

            <tr>
                <th>Day</th>
                <th>Period</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Subject</th>
                <th>Room</th>
            </tr>

            <?php foreach ($timetable as $index => $item): ?>

            <tr>

                <td>
                    <input
                        name="timetable[<?php echo $index; ?>][day]"
                        value="<?php echo htmlspecialchars($item["day"] ?? ""); ?>"
                        required>
                </td>

                <td>
                    <input
                        name="timetable[<?php echo $index; ?>][period]"
                        value="<?php echo htmlspecialchars($item["period"] ?? ""); ?>">
                </td>

                <td>
                    <input
                        type="time"
                        name="timetable[<?php echo $index; ?>][start_time]"
                        value="<?php echo htmlspecialchars($item["start_time"] ?? ""); ?>"
                        required>
                </td>

                <td>
                    <input
                        type="time"
                        name="timetable[<?php echo $index; ?>][end_time]"
                        value="<?php echo htmlspecialchars($item["end_time"] ?? ""); ?>"
                        required>
                </td>

                <td>
                    <input
                        name="timetable[<?php echo $index; ?>][subject]"
                        value="<?php echo htmlspecialchars($item["subject"] ?? ""); ?>"
                        required>
                </td>

                <td>
                    <input
                        name="timetable[<?php echo $index; ?>][room]"
                        value="<?php echo htmlspecialchars($item["room"] ?? ""); ?>">
                </td>

            </tr>

            <?php endforeach; ?>

        </table>

        <button type="submit">
            Confirm and Save Timetable
        </button>

    </form>

</div>

</body>
</html>
