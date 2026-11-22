<!DOCTYPE html>
<html>
<head>
    <title>Student Assistant</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 40px 20px;
            color: #263b60;
        }
        .container {
            max-width: 900px;
            margin: auto;
        }
        h1 {
            text-align: center;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 30px;
        }
        a.card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
            text-decoration: none;
            color: #263b60;
            font-weight: bold;
            transition: transform 0.15s ease;
        }
        a.card:hover {
            transform: translateY(-3px);
        }
        a.card span {
            display: block;
            font-weight: normal;
            font-size: 13px;
            color: #66788f;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Student Assistant</h1>
    <p style="text-align:center;">
        Your study planner, timetable, reminders, progress and AI assistant.
    </p>

    <div class="grid">
        <a class="card" href="pages/progress_dashboard.php">
            Progress Dashboard
            <span>Completion, study time and charts</span>
        </a>
        <a class="card" href="pages/display_timetable.php">
            Weekly Timetable
            <span>Classes by day, subject and teacher</span>
        </a>
        <a class="card" href="pages/verify_timetable.php">
            Verify Timetable
            <span>Check extracted timetable entries</span>
        </a>
        <a class="card" href="save_timetable.php">
            Import Timetable
            <span>Load verified timetable into the database</span>
        </a>
        <a class="card" href="pages/generate_study_plan.php">
            Generate Study Plan
            <span>Create 30-minute sessions in free gaps</span>
        </a>
        <a class="card" href="pages/study_planner.php">
            AI Study Planner
            <span>Schedule study around your classes</span>
        </a>
        <a class="card" href="pages/view_study_plan.php">
            View Study Plan
            <span>All scheduled study sessions</span>
        </a>
        <a class="card" href="pages/free_time.php">
            Free Time
            <span>Find gaps between classes</span>
        </a>
        <a class="card" href="pages/evening_study.php">
            Evening Study
            <span>Generate and track evening schedule</span>
        </a>
        <a class="card" href="pages/generate_reminders.php">
            Generate Reminders
            <span>Create reminders for pending sessions</span>
        </a>
        <a class="card" href="pages/view_reminders.php">
            View Reminders
            <span>Active and dismissed reminders</span>
        </a>
        <a class="card" href="pages/study_recommendations.php">
            Recommendations
            <span>Subjects that need more attention</span>
        </a>
        <a class="card" href="pages/study_history.php">
            Study History
            <span>Completed sessions and minutes spent</span>
        </a>
        <a class="card" href="pages/study_chatbot.php">
            Study Assistant
            <span>Ask about progress and DBMS topics</span>
        </a>
        <a class="card" href="pages/download_report.php">
            Study Report
            <span>Printable progress report</span>
        </a>
    </div>
</div>
</body>
</html>
