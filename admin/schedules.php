<?php

require "../admin_auth.php";
require "../config/db.php";

/* =========================================================
   SCHEDULE FILE
========================================================= */

$data_directory = dirname(__DIR__) . "/data";

$schedule_file = $data_directory . "/schedules.json";


/* =========================================================
   CREATE DATA DIRECTORY IF NEEDED
========================================================= */

if (!is_dir($data_directory)) {

    mkdir(
        $data_directory,
        0777,
        true
    );

}


/* =========================================================
   CREATE SCHEDULE FILE IF NEEDED
========================================================= */

if (!file_exists($schedule_file)) {

    file_put_contents(
        $schedule_file,
        json_encode([], JSON_PRETTY_PRINT)
    );

}


/* =========================================================
   READ SCHEDULES
========================================================= */

$schedules = [];

$json = file_get_contents($schedule_file);


if (
    $json !== false &&
    trim($json) !== ""
) {

    $decoded = json_decode(
        $json,
        true
    );


    if (is_array($decoded)) {

        $schedules = $decoded;

    }

}


/* =========================================================
   GET TEACHERS
========================================================= */

try {

    $teacher_stmt = $pdo->query("

        SELECT
            teacher_id,
            teacher_name

        FROM teachers

        ORDER BY teacher_name ASC

    ");

    $teachers = $teacher_stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    $teachers = [];

}


/* =========================================================
   GET BOOKINGS
========================================================= */

try {

    $booking_stmt = $pdo->query("

        SELECT
            id,
            booking_reference,
            student_name,
            email,
            subjects,
            curriculum,
            class_year,
            payment_status,
            teacher_id,
            teacher_name

        FROM bookings

        ORDER BY id DESC

    ");

    $bookings = $booking_stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    $bookings = [];

}


/* =========================================================
   SCHEDULE ASSIGNMENT ACTIONS
   Restored controls:
   - Assign Teacher
   - Assign Time
   - Assign Classroom
   - Unassign
========================================================= */

$schedule_message = '';
$schedule_message_type = '';

function saveSchedulesJson($schedule_file, $schedules)
{
    return file_put_contents(
        $schedule_file,
        json_encode(
            $schedules,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ),
        LOCK_EX
    ) !== false;
}

function bookingColumnExists($pdo, $column)
{
    try {
        $stmt = $pdo->prepare(
            "SHOW COLUMNS FROM bookings LIKE ?"
        );

        $stmt->execute([$column]);

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        return false;

    }
}

function findTeacherById($pdo, $teacher_id)
{
    $stmt = $pdo->prepare("
        SELECT teacher_id, teacher_name
        FROM teachers
        WHERE teacher_id = ?
        LIMIT 1
    ");

    $stmt->execute([$teacher_id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateScheduleLesson(&$schedules, $booking_id, $lesson_number, $changes)
{
    $updated = false;

    foreach ($schedules as &$lesson) {

        if (
            (string)($lesson['booking_id'] ?? '') ===
                (string)$booking_id
            &&
            (int)($lesson['lesson_number'] ?? 0) ===
                (int)$lesson_number
        ) {

            foreach ($changes as $key => $value) {
                $lesson[$key] = $value;
            }

            $updated = true;
            break;
        }
    }

    unset($lesson);

    return $updated;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['schedule_action'])
) {

    $action = trim(
        $_POST['schedule_action']
    );

    $booking_id = (int)(
        $_POST['booking_id'] ?? 0
    );

    $lesson_number = (int)(
        $_POST['lesson_number'] ?? 0
    );

    if (
        $booking_id <= 0
        ||
        $lesson_number <= 0
    ) {

        $schedule_message =
            'Invalid booking or lesson selected.';

        $schedule_message_type = 'error';

    } else {

        try {

            /* =====================================================
               ASSIGN TEACHER
            ===================================================== */

            if ($action === 'assign_teacher') {

                $teacher_id = trim(
                    $_POST['teacher_id'] ?? ''
                );

                if ($teacher_id === '') {

                    throw new RuntimeException(
                        'Please select a teacher.'
                    );

                }

                $teacher = findTeacherById(
                    $pdo,
                    $teacher_id
                );

                if (!$teacher) {

                    throw new RuntimeException(
                        'The selected teacher was not found.'
                    );

                }

                $updated = updateScheduleLesson(
                    $schedules,
                    $booking_id,
                    $lesson_number,
                    [
                        'teacher_id' =>
                            $teacher['teacher_id'],

                        'teacher_name' =>
                            $teacher['teacher_name']
                    ]
                );

                if (!$updated) {

                    throw new RuntimeException(
                        'The lesson could not be found in schedules.json.'
                    );

                }

                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET
                        teacher_id = ?,
                        teacher_name = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $teacher['teacher_id'],
                    $teacher['teacher_name'],
                    $booking_id
                ]);

                if (
                    !saveSchedulesJson(
                        $schedule_file,
                        $schedules
                    )
                ) {

                    throw new RuntimeException(
                        'The schedule file could not be saved.'
                    );

                }

                $schedule_message =
                    'Teacher assigned successfully.';

                $schedule_message_type = 'success';
            }


            /* =====================================================
               ASSIGN TIME
            ===================================================== */

            elseif ($action === 'assign_time') {

                $lesson_time = trim(
                    $_POST['lesson_time'] ?? ''
                );

                if ($lesson_time === '') {

                    throw new RuntimeException(
                        'Please select a lesson time.'
                    );

                }

                if (
                    !preg_match(
                        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                        $lesson_time
                    )
                ) {

                    throw new RuntimeException(
                        'Please enter a valid time.'
                    );

                }

                $updated = updateScheduleLesson(
                    $schedules,
                    $booking_id,
                    $lesson_number,
                    [
                        'lesson_time' =>
                            $lesson_time
                    ]
                );

                if (!$updated) {

                    throw new RuntimeException(
                        'The lesson could not be found in schedules.json.'
                    );

                }

                /*
                 * If the bookings table also has lesson_time,
                 * keep the database synchronized. This is optional
                 * so the page remains compatible with existing schemas.
                 */
                if (
                    bookingColumnExists(
                        $pdo,
                        'lesson_time'
                    )
                ) {

                    $stmt = $pdo->prepare("
                        UPDATE bookings
                        SET lesson_time = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $lesson_time,
                        $booking_id
                    ]);
                }

                if (
                    !saveSchedulesJson(
                        $schedule_file,
                        $schedules
                    )
                ) {

                    throw new RuntimeException(
                        'The schedule file could not be saved.'
                    );

                }

                $schedule_message =
                    'Lesson time assigned successfully.';

                $schedule_message_type = 'success';
            }


            /* =====================================================
               ASSIGN CLASSROOM
            ===================================================== */

            elseif ($action === 'assign_classroom') {

                $existing_room_code = '';

                foreach ($schedules as $lesson) {

                    if (
                        (string)($lesson['booking_id'] ?? '') ===
                            (string)$booking_id
                        &&
                        (int)($lesson['lesson_number'] ?? 0) ===
                            (int)$lesson_number
                    ) {

                        $existing_room_code =
                            trim(
                                $lesson['live_room_code']
                                ?? ''
                            );

                        break;
                    }
                }

                if ($existing_room_code === '') {

                    $existing_room_code =
                        'NISEL-'
                        . $booking_id
                        . '-'
                        . strtoupper(
                            bin2hex(
                                random_bytes(4)
                            )
                        );
                }

                $updated = updateScheduleLesson(
                    $schedules,
                    $booking_id,
                    $lesson_number,
                    [
                        'live_room_code' =>
                            $existing_room_code,

                        'classroom_status' =>
                            'waiting',

                        'classroom_started_at' =>
                            null,

                        'classroom_ended_at' =>
                            null
                    ]
                );

                if (!$updated) {

                    throw new RuntimeException(
                        'The lesson could not be found in schedules.json.'
                    );

                }

                /*
                 * These are the classroom fields already used by the
                 * working NISEL Virtual Classroom.
                 */
                $classroom_fields = [];

                if (
                    bookingColumnExists(
                        $pdo,
                        'live_room_code'
                    )
                ) {

                    $classroom_fields[] =
                        'live_room_code = ?';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_status'
                    )
                ) {

                    $classroom_fields[] =
                        'classroom_status = ?';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_started_at'
                    )
                ) {

                    $classroom_fields[] =
                        'classroom_started_at = NULL';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_ended_at'
                    )
                ) {

                    $classroom_fields[] =
                        'classroom_ended_at = NULL';
                }

                if (count($classroom_fields) > 0) {

                    $sql = "
                        UPDATE bookings
                        SET "
                        . implode(
                            ', ',
                            $classroom_fields
                        )
                        . "
                        WHERE id = ?
                    ";

                    $params = [];

                    if (
                        bookingColumnExists(
                            $pdo,
                            'live_room_code'
                        )
                    ) {

                        $params[] =
                            $existing_room_code;
                    }

                    if (
                        bookingColumnExists(
                            $pdo,
                            'classroom_status'
                        )
                    ) {

                        $params[] =
                            'waiting';
                    }

                    $params[] = $booking_id;

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute($params);
                }

                if (
                    !saveSchedulesJson(
                        $schedule_file,
                        $schedules
                    )
                ) {

                    throw new RuntimeException(
                        'The schedule file could not be saved.'
                    );

                }

                $schedule_message =
                    'NISEL Virtual Classroom assigned successfully.';

                $schedule_message_type = 'success';
            }


            /* =====================================================
               UNASSIGN
               Clears teacher, time and virtual classroom assignment.
            ===================================================== */

            elseif ($action === 'unassign') {

                $updated = updateScheduleLesson(
                    $schedules,
                    $booking_id,
                    $lesson_number,
                    [
                        'teacher_id' =>
                            null,

                        'teacher_name' =>
                            null,

                        'lesson_time' =>
                            null,

                        'live_room_code' =>
                            null,

                        'classroom_status' =>
                            null,

                        'classroom_started_at' =>
                            null,

                        'classroom_ended_at' =>
                            null
                    ]
                );

                if (!$updated) {

                    throw new RuntimeException(
                        'The lesson could not be found in schedules.json.'
                    );

                }

                /*
                 * Always clear teacher assignment because these columns
                 * are present in the current schedules page query.
                 */
                $stmt = $pdo->prepare("
                    UPDATE bookings
                    SET
                        teacher_id = NULL,
                        teacher_name = NULL
                    WHERE id = ?
                ");

                $stmt->execute([
                    $booking_id
                ]);

                /*
                 * Clear optional classroom/time columns only when
                 * they exist in the current database.
                 */
                $optional_sets = [];

                if (
                    bookingColumnExists(
                        $pdo,
                        'lesson_time'
                    )
                ) {

                    $optional_sets[] =
                        'lesson_time = NULL';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'live_room_code'
                    )
                ) {

                    $optional_sets[] =
                        'live_room_code = NULL';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_status'
                    )
                ) {

                    $optional_sets[] =
                        'classroom_status = NULL';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_started_at'
                    )
                ) {

                    $optional_sets[] =
                        'classroom_started_at = NULL';
                }

                if (
                    bookingColumnExists(
                        $pdo,
                        'classroom_ended_at'
                    )
                ) {

                    $optional_sets[] =
                        'classroom_ended_at = NULL';
                }

                if (count($optional_sets) > 0) {

                    $stmt = $pdo->prepare("
                        UPDATE bookings
                        SET "
                        . implode(
                            ', ',
                            $optional_sets
                        )
                        . "
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $booking_id
                    ]);
                }

                if (
                    !saveSchedulesJson(
                        $schedule_file,
                        $schedules
                    )
                ) {

                    throw new RuntimeException(
                        'The schedule file could not be saved.'
                    );

                }

                $schedule_message =
                    'Teacher, time and classroom assignment removed.';

                $schedule_message_type = 'success';
            }


            else {

                throw new RuntimeException(
                    'Unknown schedule action.'
                );
            }

        } catch (
            Throwable $e
        ) {

            $schedule_message =
                $e->getMessage();

            $schedule_message_type =
                'error';
        }

        /*
         * Redirect after a successful/failed POST so refreshing the page
         * does not submit the action again.
         */
        $redirect_url =
            'schedules.php';

        if (
            $search !== ''
            ||
            isset($_GET['search'])
        ) {
            $redirect_url .=
                '?search='
                .
                urlencode(
                    $_GET['search'] ?? ''
                );
        }

        if (
            $schedule_message !== ''
        ) {

            $separator =
                strpos(
                    $redirect_url,
                    '?'
                ) === false
                ? '?'
                : '&';

            $redirect_url .=
                $separator
                . 'message='
                . urlencode(
                    $schedule_message
                )
                . '&message_type='
                . urlencode(
                    $schedule_message_type
                );
        }

        header(
            'Location: '
            . $redirect_url
        );

        exit;
    }
}


/* =========================================================
   DISPLAY ACTION MESSAGE
========================================================= */

if (
    isset($_GET['message'])
) {

    $schedule_message =
        trim(
            $_GET['message']
        );

    $schedule_message_type =
        $_GET['message_type']
        ?? 'success';
}

/* =========================================================
   FILTER VALUES
========================================================= */

$teacher_filter =
    trim(
        $_GET['teacher'] ?? ''
    );


$status_filter =
    trim(
        $_GET['status'] ?? ''
    );


$search =
    trim(
        $_GET['search'] ?? ''
    );


/* =========================================================
   FILTER SCHEDULES
========================================================= */

$filtered_schedules = [];


foreach (
    $schedules as $lesson
) {

    /* =====================================================
       TEACHER FILTER
    ===================================================== */

    if (
        $teacher_filter !== ''
        &&
        (string)(
            $lesson['teacher_id']
            ?? ''
        )
        !==
        (string)$teacher_filter
    ) {

        continue;

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (
        $status_filter !== ''
        &&
        strtolower(
            $lesson['lesson_status']
            ?? ''
        )
        !==
        strtolower(
            $status_filter
        )
    ) {

        continue;

    }


    /* =====================================================
       SEARCH
    ===================================================== */

    if ($search !== '') {

        $search_text = strtolower(

            ($lesson['student_name'] ?? '')
            . ' '

            .
            ($lesson['teacher_name'] ?? '')
            . ' '

            .
            ($lesson['subjects'] ?? '')
            . ' '

            .
            ($lesson['booking_reference'] ?? '')

        );


        if (
            strpos(
                $search_text,
                strtolower($search)
            ) === false
        ) {

            continue;

        }

    }


    $filtered_schedules[] =
        $lesson;

}


/* =========================================================
   SORT BY DATE AND TIME
========================================================= */

usort(

    $filtered_schedules,

    function (
        $a,
        $b
    ) {

        $date_a =
            ($a['lesson_date'] ?? '')
            . ' '
            .
            ($a['lesson_time'] ?? '');

        $date_b =
            ($b['lesson_date'] ?? '')
            . ' '
            .
            ($b['lesson_time'] ?? '');

        return strcmp(
            $date_a,
            $date_b
        );

    }

);


/* =========================================================
   STATISTICS
========================================================= */

$total_lessons =
    count($schedules);


$scheduled_lessons = 0;

$completed_lessons = 0;

$cancelled_lessons = 0;


foreach (
    $schedules as $lesson
) {

    $status =
        strtolower(
            $lesson['lesson_status']
            ?? 'scheduled'
        );


    if (
        $status === 'completed'
    ) {

        $completed_lessons++;

    }

    elseif (
        $status === 'cancelled'
    ) {

        $cancelled_lessons++;

    }

    else {

        $scheduled_lessons++;

    }

}


/* =========================================================
   TODAY
========================================================= */

$today =
    date("Y-m-d");


$today_lessons = 0;


foreach (
    $schedules as $lesson
) {

    if (
        ($lesson['lesson_date'] ?? '')
        === $today
    ) {

        if (
            strtolower(
                $lesson['lesson_status']
                ?? 'scheduled'
            )
            !== 'cancelled'
        ) {

            $today_lessons++;

        }

    }

}


/* =========================================================
   UPCOMING
========================================================= */

$upcoming_lessons = 0;


$current_datetime =
    date("Y-m-d H:i");


foreach (
    $schedules as $lesson
) {

    $status =
        strtolower(
            $lesson['lesson_status']
            ?? 'scheduled'
        );


    $lesson_datetime =
        ($lesson['lesson_date'] ?? '')
        . ' '
        .
        ($lesson['lesson_time'] ?? '00:00');


    if (
        $status === 'scheduled'
        &&
        $lesson_datetime >=
        $current_datetime
    ) {

        $upcoming_lessons++;

    }

}


/* =========================================================
   TOTAL STUDENTS WITH SCHEDULES
========================================================= */

$scheduled_students = [];


foreach (
    $schedules as $lesson
) {

    if (
        !empty(
            $lesson['booking_id']
        )
    ) {

        $scheduled_students[
            $lesson['booking_id']
        ] = true;

    }

}


$total_scheduled_students =
    count(
        $scheduled_students
    );

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Schedules | NISEL ONLINE EDUCATION
</title>


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

    color: #333;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 250px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70 0%,
            #002b55 100%
        );

    color: white;

    padding: 24px 15px;

    z-index: 1000;

    overflow-y: auto;

}


.logo {

    padding:
        10px 8px 28px;

    text-align: center;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

    margin-bottom: 22px;

}


.logo-icon {

    width: 55px;

    height: 55px;

    margin:
        0 auto 12px;

    border-radius: 15px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

}


.logo h2 {

    font-size: 19px;

    line-height: 1.25;

    letter-spacing: .3px;

}


.logo p {

    margin-top: 6px;

    font-size: 9px;

    letter-spacing: 2px;

    opacity: .65;

}


.menu-title {

    color:
        rgba(
            255,
            255,
            255,
            .42
        );

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    padding:
        0 13px 10px;

}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 12px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration: none;

    padding:
        12px 13px;

    margin-bottom: 5px;

    border-radius: 9px;

    font-size: 13px;

    transition:
        .2s ease;

}


.sidebar a:hover {

    background:
        rgba(
            255,
            255,
            255,
            .10
        );

    color: white;

    transform:
        translateX(2px);

}


.sidebar a.active {

    background:
        rgba(
            255,
            255,
            255,
            .16
        );

    color: white;

    font-weight: 700;

    box-shadow:
        inset 3px 0 #4db8ff;

}


.menu-icon {

    width: 23px;

    text-align: center;

    font-size: 16px;

}


.logout {

    margin-top: 25px !important;

    background:
        rgba(
            220,
            53,
            69,
            .95
        ) !important;

    color: white !important;

}


.logout:hover {

    background:
        #c82333 !important;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* =====================================================
   HEADER
===================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

}


.topbar h1 {

    margin: 0;

    color: #003366;

}


.topbar p {

    color: #666;

}


/* =====================================================
   STATS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.stat {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.stat h2 {

    margin: 0;

    color: #003366;

    font-size: 30px;

}


.stat p {

    margin: 8px 0 0;

    color: #777;

}


/* =====================================================
   FILTER
===================================================== */

.filter-box {

    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 25px;

}


.filter-box h2 {

    margin-top: 0;

    color: #003366;

}


.filters {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 12px;

}


.filters input,

.filters select {

    width: 100%;

    padding: 11px;

    border: 1px solid #ccc;

    border-radius: 6px;

}


.filter-button {

    padding: 11px 18px;

    border: none;

    border-radius: 6px;

    background: #003366;

    color: white;

    cursor: pointer;

}


.clear-button {

    display: inline-block;

    padding: 11px 18px;

    background: #777;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}


/* =====================================================
   TABLE
===================================================== */

.schedule-box {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.schedule-box h2 {

    margin-top: 0;

    color: #003366;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1200px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 11px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.scheduled {

    background: #cfe2ff;

    color: #084298;

}


.completed {

    background: #d4edda;

    color: #155724;

}


.cancelled {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   ASSIGNMENT ACTIONS
===================================================== */

.actions-cell {
    min-width: 245px;
    width: 245px;
    background: #fbfdff;
}

.assignment-actions {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.action-row {
    display: flex;
    align-items: center;
    gap: 6px;
}

.action-row select,
.action-row input[type="time"] {
    flex: 1;
    min-width: 0;
    height: 34px;
    padding: 6px 8px;
    border: 1px solid #d5dee8;
    border-radius: 7px;
    background: #fff;
    color: #26384a;
    font-size: 11px;
    outline: none;
}

.action-row select:focus,
.action-row input[type="time"]:focus {
    border-color: #1685cf;
    box-shadow: 0 0 0 2px rgba(22,133,207,.10);
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-height: 34px;
    padding: 7px 9px;
    border: 0;
    border-radius: 7px;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: .18s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
}

.btn-teacher {
    background: #0b65b7;
}

.btn-time {
    background: #7a4db8;
}

.btn-classroom {
    background: #078447;
}

.btn-unassign {
    width: 100%;
    background: #c53030;
}

.assignment-current {
    padding: 8px 9px;
    border-radius: 7px;
    background: #eef6ff;
    border: 1px solid #dcecf9;
    color: #31536f;
    font-size: 10px;
    line-height: 1.45;
}

.assignment-current strong {
    color: #003b70;
}

.room-code {
    display: block;
    margin-top: 3px;
    padding: 3px 5px;
    border-radius: 5px;
    background: #e9f8ef;
    color: #08743e;
    font-family: Consolas, monospace;
    font-size: 9px;
    font-weight: 700;
    word-break: break-all;
}

.assignment-message {
    margin-bottom: 18px;
    padding: 12px 15px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 600;
}

.assignment-message.success {
    background: #eaf8ef;
    border: 1px solid #ccebd8;
    color: #14743b;
}

.assignment-message.error {
    background: #fff0f0;
    border: 1px solid #f0cccc;
    color: #b42323;
}

/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 50px;

    color: #777;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">


        <div class="logo-icon">

            🎓

        </div>


        <h2>

            NISEL ONLINE

        </h2>


        <p>

            EDUCATION

        </p>


    </div>



    <div class="menu-title">

        Main Menu

    </div>



    <a href="dashboard.php">

        <span class="menu-icon">
            🏠
        </span>

        <span class="text">
            Dashboard
        </span>

    </a>



    <a href="students.php">

        <span class="menu-icon">
            👨‍🎓
        </span>

        <span class="text">
            Students
        </span>

    </a>



    <a href="teachers.php">

        <span class="menu-icon">
            👨‍🏫
        </span>

        <span class="text">
            Teachers
        </span>

    </a>



    <a href="teacher_applications.php">

        <span class="menu-icon">
            📋
        </span>

        <span class="text">
            Teacher Applications
        </span>

    </a>



    <a href="bookings.php">

        <span class="menu-icon">
            📚
        </span>

        <span class="text">
            Bookings
        </span>

    </a>



    <a href="payments.php">

        <span class="menu-icon">
            💳
        </span>

        <span class="text">
            Payments
        </span>

    </a>



    <a href="reports.php">

        <span class="menu-icon">
            📊
        </span>

        <span class="text">
            Reports
        </span>

    </a>



    <a
        href="schedules.php"
        class="active"
    >

        <span class="menu-icon">
            📅
        </span>

        <span class="text">
            Schedules
        </span>

    </a>

    
    <a href="settings.php">

        <span class="menu-icon">
            ⚙️
        </span>

        <span class="text">
            Settings
        </span>

    </a>



    <a
        href="logout.php"
        class="logout"
    >

        <span class="menu-icon">
            🚪
        </span>

        <span class="text">
            Logout
        </span>

    </a>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <div class="topbar">

        <h1>

            📅 Lesson Schedules

        </h1>


        <p>

            Monitor all teacher and student
            lesson schedules from one place.

        </p>

    </div>


    <?php if ($schedule_message !== ''): ?>

        <div class="assignment-message <?php
            echo htmlspecialchars(
                $schedule_message_type
            );
        ?>">

            <?php
            echo htmlspecialchars(
                $schedule_message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats">


        <div class="stat">

            <h2>

                <?php

                echo $total_scheduled_students;

                ?>

            </h2>

            <p>

                Students Scheduled

            </p>

        </div>


        <div class="stat">

            <h2>

                <?php

                echo $total_lessons;

                ?>

            </h2>

            <p>

                Total Lessons

            </p>

        </div>


        <div class="stat">

            <h2>

                <?php

                echo $scheduled_lessons;

                ?>

            </h2>

            <p>

                Scheduled

            </p>

        </div>


        <div class="stat">

            <h2>

                <?php

                echo $completed_lessons;

                ?>

            </h2>

            <p>

                Completed

            </p>

        </div>


        <div class="stat">

            <h2>

                <?php

                echo $today_lessons;

                ?>

            </h2>

            <p>

                Today's Lessons

            </p>

        </div>


        <div class="stat">

            <h2>

                <?php

                echo $upcoming_lessons;

                ?>

            </h2>

            <p>

                Upcoming

            </p>

        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================= -->

    <div class="filter-box">


        <h2>

            🔎 Find Schedule

        </h2>


        <form method="GET">


            <div class="filters">


                <input
                    type="text"
                    name="search"
                    placeholder="Student, teacher, subject..."
                    value="<?php
                        echo htmlspecialchars(
                            $search
                        );
                    ?>"
                >


                <select name="teacher">


                    <option value="">

                        All Teachers

                    </option>


                    <?php foreach (
                        $teachers
                        as $teacher
                    ): ?>


                        <option
                            value="<?php
                                echo htmlspecialchars(
                                    $teacher[
                                        'teacher_id'
                                    ]
                                );
                            ?>"
                            <?php

                            if (
                                (string)(
                                    $teacher[
                                        'teacher_id'
                                    ]
                                )
                                ===
                                (string)$teacher_filter
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $teacher[
                                    'teacher_name'
                                ]
                            );

                            ?>

                        </option>


                    <?php endforeach; ?>


                </select>


                <select name="status">


                    <option value="">

                        All Statuses

                    </option>


                    <option
                        value="Scheduled"
                        <?php

                        if (
                            strtolower(
                                $status_filter
                            )
                            ===
                            'scheduled'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Scheduled

                    </option>


                    <option
                        value="Completed"
                        <?php

                        if (
                            strtolower(
                                $status_filter
                            )
                            ===
                            'completed'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Completed

                    </option>


                    <option
                        value="Cancelled"
                        <?php

                        if (
                            strtolower(
                                $status_filter
                            )
                            ===
                            'cancelled'
                        ) {

                            echo "selected";

                        }

                        ?>
                    >

                        Cancelled

                    </option>


                </select>


                <button
                    type="submit"
                    class="filter-button"
                >

                    Search

                </button>


                <a
                    href="schedules.php"
                    class="clear-button"
                >

                    Clear

                </a>


            </div>


        </form>


    </div>



    <!-- =================================================
         SCHEDULE TABLE
    ================================================= -->

    <div class="schedule-box">


        <h2>

            📚 All Lesson Schedules

        </h2>


        <?php if (
            count(
                $filtered_schedules
            ) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>


                        <th>
                            Lesson
                        </th>


                        <th>
                            Student
                        </th>


                        <th>
                            Teacher
                        </th>


                        <th>
                            Subject
                        </th>


                        <th>
                            Curriculum
                        </th>


                        <th>
                            Class
                        </th>


                        <th>
                            Date
                        </th>


                        <th>
                            Time
                        </th>


                        <th>
                            Status
                        </th>


                        <th>
                            Booking
                        </th>


                        <th>
                            Assignment Actions
                        </th>


                    </tr>


                    <?php foreach (
                        $filtered_schedules
                        as $lesson
                    ): ?>


                        <?php

                        $lesson_number =
                            (int)(
                                $lesson[
                                    'lesson_number'
                                ]
                                ?? 0
                            );


                        $week =
                            $lesson_number > 0
                            ?
                            ceil(
                                $lesson_number / 2
                            )
                            :
                            1;


                        $status =
                            $lesson[
                                'lesson_status'
                            ]
                            ?? 'Scheduled';

                        ?>


                        <tr>


                            <td>

                                <strong>

                                    Lesson

                                    <?php

                                    echo $lesson_number;

                                    ?>

                                </strong>

                                <br>

                                <small>

                                    Week

                                    <?php

                                    echo $week;

                                    ?>

                                    of 4

                                </small>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $lesson[
                                            'student_name'
                                        ]
                                    );

                                    ?>

                                </strong>


                                <br>


                                <small>

                                    <?php

                                    echo htmlspecialchars(
                                        $lesson[
                                            'student_email'
                                        ]
                                        ?? ''
                                    );

                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'teacher_name'
                                    ]
                                    ?? 'Not Assigned'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                    ?? 'N/A'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'curriculum'
                                    ]
                                    ?? 'N/A'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'class_year'
                                    ]
                                    ?? 'N/A'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    !empty(
                                        $lesson[
                                            'lesson_date'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $lesson[
                                                'lesson_date'
                                            ]
                                        )
                                    );

                                    echo "<br>";

                                    echo "<small>";

                                    echo date(
                                        "l",
                                        strtotime(
                                            $lesson[
                                                'lesson_date'
                                            ]
                                        )
                                    );

                                    echo "</small>";

                                } else {

                                    echo "N/A";

                                }

                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    !empty(
                                        $lesson[
                                            'lesson_time'
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $lesson[
                                                'lesson_time'
                                            ]
                                        )
                                    );

                                } else {

                                    echo "N/A";

                                }

                                ?>

                            </td>


                            <td>


                                <?php

                                if (
                                    strtolower(
                                        $status
                                    )
                                    ===
                                    'completed'
                                ) {

                                    echo '

                                    <span
                                        class="badge completed"
                                    >

                                        Completed

                                    </span>

                                    ';

                                }

                                elseif (
                                    strtolower(
                                        $status
                                    )
                                    ===
                                    'cancelled'
                                ) {

                                    echo '

                                    <span
                                        class="badge cancelled"
                                    >

                                        Cancelled

                                    </span>

                                    ';

                                }

                                else {

                                    echo '

                                    <span
                                        class="badge scheduled"
                                    >

                                        Scheduled

                                    </span>

                                    ';

                                }

                                ?>


                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'booking_reference'
                                    ]
                                    ?? 'N/A'
                                );

                                ?>

                            <td class="actions-cell">

                                <?php
                                $current_teacher_id =
                                    $lesson['teacher_id']
                                    ?? '';

                                $current_teacher_name =
                                    $lesson['teacher_name']
                                    ?? '';

                                $current_time =
                                    $lesson['lesson_time']
                                    ?? '';

                                $current_room =
                                    $lesson['live_room_code']
                                    ?? '';

                                $current_classroom_status =
                                    $lesson['classroom_status']
                                    ?? '';
                                ?>


                                <div class="assignment-actions">

                                    <?php if (
                                        $current_teacher_name !== ''
                                    ): ?>

                                        <div class="assignment-current">

                                            <strong>
                                                👨‍🏫 Teacher
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars(
                                                $current_teacher_name
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        $current_time !== ''
                                    ): ?>

                                        <div class="assignment-current">

                                            <strong>
                                                🕐 Time
                                            </strong>

                                            <br>

                                            <?= htmlspecialchars(
                                                date(
                                                    "h:i A",
                                                    strtotime(
                                                        $current_time
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <?php if (
                                        $current_room !== ''
                                    ): ?>

                                        <div class="assignment-current">

                                            <strong>
                                                🎥 Classroom
                                            </strong>

                                            <span class="room-code">
                                                <?= htmlspecialchars(
                                                    $current_room
                                                ) ?>
                                            </span>

                                            <?php if (
                                                $current_classroom_status !== ''
                                            ): ?>

                                                <small>
                                                    Status:
                                                    <?= htmlspecialchars(
                                                        $current_classroom_status
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    <?php endif; ?>


                                    <!-- ASSIGN TEACHER -->

                                    <form
                                        method="POST"
                                        class="action-row"
                                    >

                                        <input
                                            type="hidden"
                                            name="schedule_action"
                                            value="assign_teacher"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)(
                                                $lesson['booking_id']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="lesson_number"
                                            value="<?= (int)(
                                                $lesson['lesson_number']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <select
                                            name="teacher_id"
                                            required
                                        >

                                            <option value="">
                                                🧑‍🏫 Assign Teacher
                                            </option>

                                            <?php foreach (
                                                $teachers
                                                as $teacher
                                            ): ?>

                                                <option
                                                    value="<?= htmlspecialchars(
                                                        $teacher['teacher_id']
                                                    ) ?>"
                                                    <?= (
                                                        (string)(
                                                            $teacher['teacher_id']
                                                        )
                                                        ===
                                                        (string)$current_teacher_id
                                                    )
                                                    ? 'selected'
                                                    : ''
                                                    ?>
                                                >
                                                    <?= htmlspecialchars(
                                                        $teacher['teacher_name']
                                                    ) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button
                                            type="submit"
                                            class="action-btn btn-teacher"
                                        >
                                            Assign
                                        </button>

                                    </form>


                                    <!-- ASSIGN TIME -->

                                    <form
                                        method="POST"
                                        class="action-row"
                                    >

                                        <input
                                            type="hidden"
                                            name="schedule_action"
                                            value="assign_time"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)(
                                                $lesson['booking_id']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="lesson_number"
                                            value="<?= (int)(
                                                $lesson['lesson_number']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <input
                                            type="time"
                                            name="lesson_time"
                                            value="<?= htmlspecialchars(
                                                substr(
                                                    $current_time,
                                                    0,
                                                    5
                                                )
                                            ) ?>"
                                            required
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn btn-time"
                                        >
                                            🕐 Set
                                        </button>

                                    </form>


                                    <!-- ASSIGN CLASSROOM -->

                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="schedule_action"
                                            value="assign_classroom"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)(
                                                $lesson['booking_id']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="lesson_number"
                                            value="<?= (int)(
                                                $lesson['lesson_number']
                                                ?? 0
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn btn-classroom"
                                            style="width:100%;"
                                        >
                                            🎥
                                            <?= $current_room !== ''
                                                ? 'Classroom Ready'
                                                : 'Assign Classroom'
                                            ?>
                                        </button>

                                    </form>


                                    <!-- UNASSIGN -->

                                    <?php if (
                                        $current_teacher_name !== ''
                                        ||
                                        $current_time !== ''
                                        ||
                                        $current_room !== ''
                                    ): ?>

                                        <form
                                            method="POST"
                                            onsubmit="return confirm(
                                                'Unassign the teacher, time and classroom from this lesson?'
                                            );"
                                        >

                                            <input
                                                type="hidden"
                                                name="schedule_action"
                                                value="unassign"
                                            >

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?= (int)(
                                                    $lesson['booking_id']
                                                    ?? 0
                                                ) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="lesson_number"
                                                value="<?= (int)(
                                                    $lesson['lesson_number']
                                                    ?? 0
                                                ) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="action-btn btn-unassign"
                                            >
                                                ❌ Unassign
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <div
                    style="
                        font-size:50px;
                    "
                >

                    📅

                </div>


                <h3>

                    No Schedules Found

                </h3>


                <p>

                    No lesson schedules match
                    your current search.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
