<?php

session_start();

require "../config/db.php";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {

    header("Location: login.php");
    exit;

}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_id =
    $_SESSION['student_id'] ?? '';

$student_name =
    $_SESSION['student_name'] ?? 'Student';

$student_email =
    $_SESSION['student_email'] ?? '';


/* =========================================================
   SCHEDULE FILE
========================================================= */

$data_directory =
    dirname(__DIR__) . "/data";

$schedule_file =
    $data_directory . "/schedules.json";


/* =========================================================
   CREATE DATA FOLDER IF NEEDED
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
        json_encode(
            [],
            JSON_PRETTY_PRINT
        )
    );

}


/* =========================================================
   READ SCHEDULE FILE
========================================================= */

$schedules = [];

$json =
    file_get_contents($schedule_file);


if (
    $json !== false &&
    trim($json) !== ""
) {

    $decoded =
        json_decode(
            $json,
            true
        );

    if (is_array($decoded)) {

        $schedules = $decoded;

    }

}


/* =========================================================
   GET STUDENT BOOKINGS
========================================================= */

$bookings = [];


try {

    /*
     * We first try to identify the student using email.
     */

    if (!empty($student_email)) {

        $stmt = $pdo->prepare("

            SELECT *

            FROM bookings

            WHERE email = ?

            ORDER BY id DESC

        ");

        $stmt->execute([
            $student_email
        ]);

    } else {

        /*
         * If your student session contains an ID,
         * use it as a fallback.
         */

        $stmt = $pdo->prepare("

            SELECT *

            FROM bookings

            WHERE id = ?

            ORDER BY id DESC

        ");

        $stmt->execute([
            $student_id
        ]);

    }


    $bookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die(

        "Unable to load student bookings: "

        . htmlspecialchars(
            $e->getMessage()
        )

    );

}


/* =========================================================
   FIND STUDENT'S SCHEDULE
========================================================= */

$student_schedules = [];


foreach (
    $schedules as $lesson
) {

    $belongs_to_student = false;


    /* =====================================================
       MATCH BY EMAIL
    ===================================================== */

    if (
        !empty(
            $lesson['student_email']
            ?? ''
        )
        &&
        !empty($student_email)
    ) {

        if (
            strtolower(
                trim(
                    $lesson['student_email']
                )
            )
            ===
            strtolower(
                trim(
                    $student_email
                )
            )
        ) {

            $belongs_to_student = true;

        }

    }


    /* =====================================================
       MATCH BY BOOKING ID
    ===================================================== */

    if (
        !$belongs_to_student
        &&
        isset(
            $lesson['booking_id']
        )
    ) {

        foreach (
            $bookings as $booking
        ) {

            if (
                isset(
                    $booking['id']
                )
                &&
                (int)$lesson['booking_id']
                ===
                (int)$booking['id']
            ) {

                $belongs_to_student = true;

                break;

            }

        }

    }


    /*
     * Add lesson to student's schedule.
     */

    if ($belongs_to_student) {

        $student_schedules[] =
            $lesson;

    }

}


/* =========================================================
   SORT LESSONS
========================================================= */

usort(

    $student_schedules,

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
    count($student_schedules);

$completed_lessons = 0;

$scheduled_lessons = 0;

$cancelled_lessons = 0;


foreach (
    $student_schedules
    as $lesson
) {

    $status =
        $lesson['lesson_status']
        ?? 'Scheduled';


    if (
        strtolower($status)
        === 'completed'
    ) {

        $completed_lessons++;

    }

    elseif (
        strtolower($status)
        === 'cancelled'
    ) {

        $cancelled_lessons++;

    }

    else {

        $scheduled_lessons++;

    }

}


/* =========================================================
   TODAY'S DATE
========================================================= */

$today =
    date("Y-m-d");


/* =========================================================
   TODAY'S LESSONS
========================================================= */

$today_lessons = [];


foreach (
    $student_schedules
    as $lesson
) {

    if (
        ($lesson['lesson_date'] ?? '')
        === $today
        &&
        strtolower(
            $lesson['lesson_status']
            ?? 'Scheduled'
        )
        !== 'cancelled'
    ) {

        $today_lessons[] =
            $lesson;

    }

}


/* =========================================================
   UPCOMING LESSONS
========================================================= */

$upcoming_lessons = [];


$current_datetime =
    date("Y-m-d H:i");


foreach (
    $student_schedules
    as $lesson
) {

    $status =
        strtolower(
            $lesson['lesson_status']
            ?? 'Scheduled'
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

        $upcoming_lessons[] =
            $lesson;

    }

}


/* =========================================================
   PROGRESS
========================================================= */

$progress = 0;


if (
    $total_lessons > 0
) {

    $progress =
        round(
            (
                $completed_lessons
                /
                $total_lessons
            )
            * 100
        );

}


/* =========================================================
   CURRENT MONTH
========================================================= */

$current_month =
    date("F Y");

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

My Schedule |
NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   GENERAL
===================================================== */

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


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.sidebar a:hover {

    background: #0055a5;

}


.sidebar a.active {

    background: #0055a5;

}


.logout {

    margin-top: 25px;

    background: #c82333;

}


.logout:hover {

    background: #a71d2a !important;

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

.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.header p {

    margin: 0;

    color: #666;

    line-height: 1.6;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.stat-card h3 {

    margin: 0;

    font-size: 30px;

    color: #003366;

}


.stat-card p {

    margin: 8px 0 0;

    color: #777;

}


/* =====================================================
   PROGRESS
===================================================== */

.progress-card {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.progress-card h2 {

    margin-top: 0;

    color: #003366;

}


.progress-info {

    display: flex;

    justify-content: space-between;

    margin-bottom: 10px;

}


.progress-bar {

    width: 100%;

    height: 18px;

    background: #e5e5e5;

    border-radius: 20px;

    overflow: hidden;

}


.progress-fill {

    height: 100%;

    background: #003366;

    border-radius: 20px;

    transition: width .4s ease;

}


/* =====================================================
   SECTION
===================================================== */

.section {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.section h2 {

    margin-top: 0;

    color: #003366;

}


/* =====================================================
   TODAY'S LESSON CARD
===================================================== */

.today-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(260px, 1fr));

    gap: 20px;

}


.today-card {

    border: 1px solid #ddd;

    border-radius: 10px;

    padding: 20px;

    background: #f8fbff;

}


.today-time {

    font-size: 24px;

    font-weight: bold;

    color: #003366;

}


.today-student {

    font-size: 18px;

    font-weight: bold;

    margin-top: 10px;

}


.today-subject {

    color: #666;

    margin-top: 6px;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 850px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 12px;

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

    padding: 6px 11px;

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
   WEEK LABEL
===================================================== */

.week-label {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 5px;

    background: #eef3f8;

    color: #003366;

    font-size: 12px;

    font-weight: bold;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}


.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;

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

<div class="sidebar">


    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <a href="dashboard.php">

        🏠 Dashboard

    </a>


    <a href="profile.php">

        👤 My Profile

    </a>


    <a href="bookings.php">

        📚 My Bookings

    </a>


    <a
        href="schedule.php"
        class="active"
    >

        📅 My Schedule

    </a>


    <a href="payments.php">

        💳 Payments

    </a>


    <a
        href="logout.php"
        class="logout"
    >

        🚪 Logout

    </a>


</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- =================================================
         HEADER
    ================================================= -->

    <div class="header">


        <h1>

            📅 My Lesson Schedule

        </h1>


        <p>

            Welcome,

            <strong>

                <?php

                echo htmlspecialchars(
                    $student_name
                );

                ?>

            </strong>.

            Your NISEL ONLINE EDUCATION lesson
            package is designed for

            <strong>
                2 lessons per week
            </strong>

            and

            <strong>
                8 lessons per month.
            </strong>

        </p>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?php

                echo $total_lessons;

                ?>

            </h3>

            <p>

                Total Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $scheduled_lessons;

                ?>

            </h3>

            <p>

                Scheduled

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $completed_lessons;

                ?>

            </h3>

            <p>

                Completed

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $cancelled_lessons;

                ?>

            </h3>

            <p>

                Cancelled

            </p>

        </div>


    </div>



    <!-- =================================================
         PROGRESS
    ================================================= -->

    <?php if (
        $total_lessons > 0
    ): ?>


        <div class="progress-card">


            <h2>

                📊 <?php echo $current_month; ?>
                Lesson Progress

            </h2>


            <div class="progress-info">

                <span>

                    Completed:

                    <strong>

                        <?php

                        echo $completed_lessons;

                        ?>

                    </strong>

                    of

                    <strong>

                        <?php

                        echo $total_lessons;

                        ?>

                    </strong>

                </span>


                <strong>

                    <?php

                    echo $progress;

                    ?>%

                </strong>

            </div>


            <div class="progress-bar">


                <div
                    class="progress-fill"
                    style="width:
                        <?php
                        echo $progress;
                        ?>%;
                    "
                ></div>


            </div>


        </div>


    <?php endif; ?>



    <!-- =================================================
         TODAY'S LESSONS
    ================================================= -->

    <div class="section">


        <h2>

            📅 Today's Lessons

        </h2>


        <?php if (
            count($today_lessons) > 0
        ): ?>


            <div class="today-grid">


                <?php foreach (
                    $today_lessons
                    as $lesson
                ): ?>


                    <div class="today-card">


                        <div class="today-time">

                            <?php

                            echo date(
                                "h:i A",
                                strtotime(
                                    $lesson[
                                        'lesson_time'
                                    ]
                                )
                            );

                            ?>

                        </div>


                        <div class="today-student">

                            <?php

                            echo htmlspecialchars(
                                $lesson[
                                    'teacher_name'
                                ]
                            );

                            ?>

                        </div>


                        <div class="today-subject">

                            Subject:

                            <strong>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                );

                                ?>

                            </strong>

                        </div>


                        <div
                            style="
                                margin-top:12px;
                            "
                        >

                            Lesson

                            <strong>

                                <?php

                                echo (int)
                                    $lesson[
                                        'lesson_number'
                                    ];

                                ?>

                            </strong>

                            of 8

                        </div>


                        <div
                            style="
                                margin-top:12px;
                            "
                        >


                            <?php

                            $status =
                                $lesson[
                                    'lesson_status'
                                ]
                                ?? 'Scheduled';


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


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    📅

                </div>


                <h3>

                    No Lesson Today

                </h3>


                <p>

                    You do not have a lesson scheduled
                    for today.

                </p>


            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         UPCOMING LESSONS
    ================================================= -->

    <div class="section">


        <h2>

            ⏰ Upcoming Lessons

        </h2>


        <?php if (
            count($upcoming_lessons) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>

                        <th>
                            Lesson
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Day
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Subject
                        </th>

                    </tr>


                    <?php foreach (
                        $upcoming_lessons
                        as $lesson
                    ): ?>


                        <tr>


                            <td>

                                Lesson

                                <strong>

                                    <?php

                                    echo (int)
                                        $lesson[
                                            'lesson_number'
                                        ];

                                    ?>

                                </strong>

                                of 8

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $lesson[
                                            'lesson_date'
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "l",
                                    strtotime(
                                        $lesson[
                                            'lesson_date'
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $lesson[
                                                'lesson_time'
                                            ]
                                        )
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'teacher_name'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                );

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    ⏰

                </div>


                <h3>

                    No Upcoming Lessons

                </h3>


                <p>

                    You currently have no upcoming
                    scheduled lessons.

                </p>


            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         COMPLETE MONTHLY SCHEDULE
    ================================================= -->

    <div class="section">


        <h2>

            📚 Complete 8-Lesson Schedule

        </h2>


        <?php if (
            count($student_schedules) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>


                        <th>

                            Lesson

                        </th>


                        <th>

                            Week

                        </th>


                        <th>

                            Date

                        </th>


                        <th>

                            Day

                        </th>


                        <th>

                            Time

                        </th>


                        <th>

                            Teacher

                        </th>


                        <th>

                            Subject

                        </th>


                        <th>

                            Status

                        </th>


                    </tr>


                    <?php foreach (
                        $student_schedules
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

                                    of 8

                                </small>

                            </td>


                            <td>

                                <span
                                    class="week-label"
                                >

                                    Week

                                    <?php

                                    echo $week;

                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $lesson[
                                            'lesson_date'
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "l",
                                    strtotime(
                                        $lesson[
                                            'lesson_date'
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo date(
                                        "h:i A",
                                        strtotime(
                                            $lesson[
                                                'lesson_time'
                                            ]
                                        )
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'teacher_name'
                                    ]
                                    ??
                                    'Not Assigned'
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                    ??
                                    'N/A'
                                );

                                ?>

                            </td>


                            <td>


                                <?php

                                $status =
                                    $lesson[
                                        'lesson_status'
                                    ]
                                    ?? 'Scheduled';


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


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    📚

                </div>


                <h3>

                    Your Schedule Is Not Ready Yet

                </h3>


                <p>

                    Your teacher has not created your
                    8-lesson monthly schedule yet.

                </p>


                <p>

                    Once your teacher creates the
                    schedule, all your lessons will
                    appear here automatically.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
