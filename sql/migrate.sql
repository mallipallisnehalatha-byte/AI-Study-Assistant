USE student_assistant;

SET FOREIGN_KEY_CHECKS = 0;

RENAME TABLE
    students TO legacy_students,
    timetable TO legacy_timetable,
    study_sessions TO legacy_study_sessions,
    evening_study_sessions TO legacy_evening_study_sessions,
    reminders TO legacy_reminders,
    progress TO legacy_progress;

SOURCE C:/xampp/htdocs/student_assistant/sql/schema.sql;

UPDATE students s
JOIN legacy_students l ON l.id = s.id
SET s.name = l.name,
    s.email = l.email,
    s.password = l.password,
    s.created_at = COALESCE(l.created_at, s.created_at);

INSERT INTO students (id, name, email, password, created_at)
SELECT l.id, l.name, l.email, l.password, l.created_at
FROM legacy_students l
LEFT JOIN students s ON s.id = l.id
WHERE s.id IS NULL;

INSERT INTO study_sessions
    (id, student_id, subject, study_date, start_time, end_time, status, created_at)
SELECT
    id,
    student_id,
    subject,
    study_date,
    start_time,
    end_time,
    IF(status IN ('Pending', 'Completed', 'Missed'), status, 'Pending'),
    created_at
FROM legacy_study_sessions;

INSERT INTO evening_study_sessions
    (id, student_id, subject, study_date, start_time, end_time, status)
SELECT
    id,
    student_id,
    COALESCE(subject, activity),
    study_date,
    start_time,
    end_time,
    IF(status IN ('Pending', 'Completed', 'Missed'), status, 'Pending')
FROM legacy_evening_study_sessions;

INSERT INTO reminders (id, student_id, subject, reminder_time, status)
SELECT
    id,
    student_id,
    SUBSTRING_INDEX(SUBSTRING_INDEX(reminder_text, ' ', 2), ' ', -1),
    reminder_time,
    IF(status = 'Sent', 'Dismissed', 'Active')
FROM legacy_reminders;

INSERT INTO progress
    (id, student_id, session_id, subject, minutes_spent, activity, recorded_at)
SELECT
    id,
    student_id,
    session_id,
    subject,
    minutes_spent,
    activity,
    recorded_at
FROM legacy_progress;

UPDATE study_sessions
SET subject = TRIM(SUBSTRING_INDEX(
        SUBSTRING_INDEX(REPLACE(subject, '\\/', '/'), ' / ', 1),
        ' - ', 1))
WHERE subject LIKE '% / %' OR subject LIKE '% - %';

UPDATE reminders
SET subject = TRIM(SUBSTRING_INDEX(
        SUBSTRING_INDEX(REPLACE(subject, '\\/', '/'), ' / ', 1),
        ' - ', 1))
WHERE subject LIKE '% / %' OR subject LIKE '% - %';

SET FOREIGN_KEY_CHECKS = 1;
