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
   ADMIN: ASSIGN TEACHER + CREATE VIRTUAL CLASSROOM
========================================================= */
$assignment_message = '';
$assignment_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_classroom'])) {

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $selected_teacher_id = trim((string)($_POST['teacher_id'] ?? ''));

    if ($booking_id <= 0) {
        $assignment_message = 'Invalid booking selected.';
        $assignment_type = 'error';
    } elseif ($selected_teacher_id === '') {
        $assignment_message = 'Please select a teacher.';
        $assignment_type = 'error';
    } else {
        try {
            /* Get the selected teacher. */
            $teacher_lookup = $pdo->prepare(''
                . 'SELECT teacher_id, teacher_name '
                . 'FROM teachers '
                . 'WHERE teacher_id = ? LIMIT 1'
            );
            $teacher_lookup->execute([$selected_teacher_id]);
            $selected_teacher = $teacher_lookup->fetch(PDO::FETCH_ASSOC);

            if (!$selected_teacher) {
                throw new RuntimeException('The selected teacher could not be found.');
            }

            /* Make sure the booking exists. */
            $booking_lookup = $pdo->prepare(''
                . 'SELECT id, booking_reference, student_name '
                . 'FROM bookings WHERE id = ? LIMIT 1'
            );
            $booking_lookup->execute([$booking_id]);
            $selected_booking = $booking_lookup->fetch(PDO::FETCH_ASSOC);

            if (!$selected_booking) {
                throw new RuntimeException('The selected booking could not be found.');
            }

            /* Generate a fresh NISEL virtual classroom room code. */
            $room_code = 'NISEL-' . $booking_id . '-' . strtoupper(
                substr(
                    hash(
                        'sha256',
                        $booking_id . microtime(true) . bin2hex(random_bytes(8))
                    ),
                    0,
                    8
                )
            );

            /*
             * Keep the classroom state in bookings because the working
             * teacher/student classroom reads live_room_code from there.
             */
            $update = $pdo->prepare(''
                . 'UPDATE bookings SET '
                . 'teacher_id = ?, '
                . 'teacher_name = ?, '
                . 'live_room_code = ?, '
                . 'live_status = ?, '
                . 'live_started_at = NULL, '
                . 'live_ended_at = NULL '
                . 'WHERE id = ?'
            );

            $update->execute([
                $selected_teacher['teacher_id'],
                $selected_teacher['teacher_name'],
                $room_code,
                'waiting',
                $booking_id
            ]);

            /*
             * Also update schedules.json so this schedule page remains
             * consistent with the booking table used by the classroom.
             */
            $json_changed = false;

            foreach ($schedules as &$scheduled_lesson) {
                $lesson_booking_id = (int)($scheduled_lesson['booking_id'] ?? 0);
                $lesson_reference = (string)($scheduled_lesson['booking_reference'] ?? '');

                if (
                    $lesson_booking_id === $booking_id ||
                    ($lesson_booking_id === 0 && $lesson_reference !== '' &&
                     $lesson_reference === (string)$selected_booking['booking_reference'])
                ) {
                    $scheduled_lesson['booking_id'] = $booking_id;
                    $scheduled_lesson['teacher_id'] = $selected_teacher['teacher_id'];
                    $scheduled_lesson['teacher_name'] = $selected_teacher['teacher_name'];
                    $scheduled_lesson['live_room_code'] = $room_code;
                    $scheduled_lesson['live_status'] = 'waiting';
                    $json_changed = true;
                }
            }
            unset($scheduled_lesson);

            if ($json_changed) {
                file_put_contents(
                    $schedule_file,
                    json_encode($schedules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    LOCK_EX
                );
            }

            $assignment_message = 'Teacher assigned and NISEL Virtual Classroom created successfully for ' .
                ($selected_booking['student_name'] ?? 'this booking') .
                '. Room: ' . $room_code;
            $assignment_type = 'success';

        } catch (Throwable $e) {
            $assignment_message = 'Unable to assign teacher/classroom: ' . $e->getMessage();
            $assignment_type = 'error';
        }
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
            teacher_name,
            live_room_code

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
   ASSIGNMENT / CLASSROOM
===================================================== */

.assignment-message {
    padding: 14px 17px;
    margin-bottom: 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
}

.assignment-message.success {
    background: #e8f7ed;
    color: #176b37;
    border: 1px solid #b9e7c8;
}

.assignment-message.error {
    background: #fff0f0;
    color: #a52828;
    border: 1px solid #f1c1c1;
}

.classroom-cell {
    min-width: 235px;
}

.room-code {
    display: inline-block;
    max-width: 220px;
    margin-bottom: 7px;
    padding: 5px 8px;
    border-radius: 6px;
    background: #eef6ff;
    color: #075a9f;
    font-family: Consolas, monospace;
    font-size: 10px;
    font-weight: 700;
    word-break: break-all;
}

.room-empty {
    display: block;
    margin-bottom: 8px;
    color: #999;
    font-size: 11px;
}

.assign-form {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 230px;
}

.assign-form select {
    min-width: 145px;
    flex: 1;
    padding: 8px 9px;
    border: 1px solid #ccd7e2;
    border-radius: 7px;
    background: #fff;
    color: #333;
    font-size: 11px;
}

.assign-button {
    flex: 0 0 auto;
    padding: 8px 10px;
    border: 0;
    border-radius: 7px;
    background: linear-gradient(135deg, #003366, #0877c9);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
}

.assign-button:hover {
    background: #0055a5;
}

.classroom-ready {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 3px;
    color: #16803d;
    font-size: 10px;
    font-weight: 700;
}

.classroom-ready::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #20a65a;
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



    <a
        href="dashboard.php"
        class="active"
    >

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



    <a href="schedules.php">

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


    <?php if ($assignment_message !== ''): ?>

        <div class="assignment-message <?= htmlspecialchars($assignment_type, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($assignment_message, ENT_QUOTES, 'UTF-8') ?>
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
                            Virtual Classroom
                        </th>

                        <th>
                            Assign Teacher
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

                            </td>


                            <?php

                            /* Resolve the real booking ID for this schedule row. */
                            $row_booking_id = (int)(
                                $lesson['booking_id']
                                ?? 0
                            );

                            if ($row_booking_id <= 0 && !empty($lesson['booking_reference'])) {
                                foreach ($bookings as $booking_row) {
                                    if (
                                        (string)($booking_row['booking_reference'] ?? '') ===
                                        (string)$lesson['booking_reference']
                                    ) {
                                        $row_booking_id = (int)$booking_row['id'];
                                        break;
                                    }
                                }
                            }

                            $row_room_code = trim((string)(
                                $lesson['live_room_code']
                                ?? ''
                            ));

                            if ($row_room_code === '' && $row_booking_id > 0) {
                                foreach ($bookings as $booking_row) {
                                    if ((int)$booking_row['id'] === $row_booking_id) {
                                        $row_room_code = trim((string)(
                                            $booking_row['live_room_code'] ?? ''
                                        ));
                                        break;
                                    }
                                }
                            }

                            ?>

                            <td class="classroom-cell">

                                <?php if ($row_room_code !== ''): ?>

                                    <span class="room-code">
                                        <?= htmlspecialchars($row_room_code, ENT_QUOTES, 'UTF-8') ?>
                                    </span>

                                    <span class="classroom-ready">
                                        Classroom Ready
                                    </span>

                                <?php else: ?>

                                    <span class="room-empty">
                                        No classroom assigned yet
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if ($row_booking_id > 0): ?>

                                    <form method="POST" class="assign-form">

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= $row_booking_id ?>"
                                        >

                                        <select name="teacher_id" required>
                                            <option value="">Select teacher</option>

                                            <?php foreach ($teachers as $teacher): ?>

                                                <option
                                                    value="<?= htmlspecialchars($teacher['teacher_id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    <?php
                                                    if (
                                                        (string)($lesson['teacher_id'] ?? '') ===
                                                        (string)$teacher['teacher_id']
                                                    ) {
                                                        echo 'selected';
                                                    }
                                                    ?>
                                                >
                                                    <?= htmlspecialchars($teacher['teacher_name'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                        <button
                                            type="submit"
                                            name="assign_classroom"
                                            value="1"
                                            class="assign-button"
                                            onclick="return confirm('Assign this teacher and create a NISEL Virtual Classroom for this lesson?');"
                                        >
                                            <?= $row_room_code !== '' ? 'Update' : 'Assign & Create' ?>
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="room-empty">
                                        Booking ID unavailable
                                    </span>

                                <?php endif; ?>

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
