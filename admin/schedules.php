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

    width: 240px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

}


.logo {

    text-align: center;

    font-size: 19px;

    font-weight: bold;

    line-height: 1.5;

    margin-bottom: 35px;

}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.menu a:hover {

    background: #0055a5;

}


.menu a.active {

    background: #0055a5;

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
