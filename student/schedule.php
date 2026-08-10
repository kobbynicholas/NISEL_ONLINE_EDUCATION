<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT SCHEDULE
|--------------------------------------------------------------------------
| PDO VERSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK STUDENT LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {
    header("Location: login.php");
    exit;
}


$student_id = $_SESSION['student_id'];

$student_name = $_SESSION['student_name'] ?? 'Student';

$student_email =
    $_SESSION['student_email']
    ?? $_SESSION['email']
    ?? '';


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatDateValue($date)
{
    if (empty($date)) {
        return "Not scheduled";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return "Not scheduled";
    }

    return date("d M Y", $timestamp);
}


/*
|--------------------------------------------------------------------------
| FORMAT TIME
|--------------------------------------------------------------------------
*/

function formatTimeValue($time)
{
    if (empty($time)) {
        return "Not set";
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return "Not set";
    }

    return date("h:i A", $timestamp);
}


/*
|--------------------------------------------------------------------------
| LESSON STATUS
|--------------------------------------------------------------------------
*/

function lessonStatusBadge($status)
{
    $status = strtolower(
        trim($status ?? 'scheduled')
    );

    if ($status === "completed") {

        return '
            <span class="badge completed">
                Completed
            </span>
        ';
    }

    if ($status === "cancelled") {

        return '
            <span class="badge cancelled">
                Cancelled
            </span>
        ';
    }

    return '
        <span class="badge scheduled">
            Scheduled
        </span>
    ';
}


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS
|--------------------------------------------------------------------------
*/

function paymentStatusBadge($status)
{
    $status = strtolower(
        trim($status ?? '')
    );

    if (
        $status === "paid" ||
        $status === "success"
    ) {

        return '
            <span class="badge paid">
                PAID
            </span>
        ';
    }

    if (empty($status)) {

        return '
            <span class="badge pending">
                PENDING
            </span>
        ';
    }

    return '
        <span class="badge pending">
            ' .
            h(strtoupper($status))
            . '
        </span>
    ';
}


/*
|--------------------------------------------------------------------------
| ZOOM BUTTON
|--------------------------------------------------------------------------
*/

function zoomButton($zoomLink)
{
    if (!empty($zoomLink)) {

        return '
            <a
                href="' . h($zoomLink) . '"
                target="_blank"
                rel="noopener noreferrer"
                class="zoom-button"
            >
                🎥 Join Zoom Class
            </a>
        ';
    }

    return '
        <span class="no-zoom">
            Zoom link not available
        </span>
    ';
}


/*
|--------------------------------------------------------------------------
| GET ALL STUDENT LESSONS
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| We use student_id as the main relationship between the student
| and booking.
|
| Teacher email and phone are NOT selected.
|
|--------------------------------------------------------------------------
*/

$scheduleStmt = $pdo->prepare("

    SELECT

        b.id,

        b.booking_reference,

        b.student_name,

        b.curriculum,

        b.class_year,

        b.subjects,

        b.lesson_date,

        b.lesson_time,

        b.lesson_status,

        b.payment_status,

        b.teacher_id,

        b.teacher_name,

        t.teacher_name AS assigned_teacher_name,

        t.zoom_link AS teacher_zoom_link


    FROM bookings b


    LEFT JOIN teachers t

        ON b.teacher_id = t.teacher_id


    WHERE b.student_id = ?


    ORDER BY

        CASE
            WHEN b.lesson_date IS NULL
            THEN 1
            ELSE 0
        END,

        b.lesson_date ASC,

        b.lesson_time ASC

");


$scheduleStmt->execute([
    $student_id
]);


$schedules = $scheduleStmt->fetchAll(
    PDO::FETCH_ASSOC
);


$totalLessons = count(
    $schedules
);


/*
|--------------------------------------------------------------------------
| TODAY'S LESSONS
|--------------------------------------------------------------------------
|
| DATE() allows this to work whether lesson_date is DATE or DATETIME.
|
|--------------------------------------------------------------------------
*/

$todayStmt = $pdo->prepare("

    SELECT

        b.id,

        b.booking_reference,

        b.student_name,

        b.curriculum,

        b.class_year,

        b.subjects,

        b.lesson_date,

        b.lesson_time,

        b.lesson_status,

        b.payment_status,

        b.teacher_id,

        b.teacher_name,

        t.teacher_name AS assigned_teacher_name,

        t.zoom_link AS teacher_zoom_link


    FROM bookings b


    LEFT JOIN teachers t

        ON b.teacher_id = t.teacher_id


    WHERE

        b.student_id = ?

        AND DATE(b.lesson_date) = CURDATE()


    ORDER BY

        b.lesson_time ASC

");


$todayStmt->execute([
    $student_id
]);


$todayLessons = $todayStmt->fetchAll(
    PDO::FETCH_ASSOC
);


$totalToday = count(
    $todayLessons
);


/*
|--------------------------------------------------------------------------
| UPCOMING LESSONS
|--------------------------------------------------------------------------
*/

$upcomingStmt = $pdo->prepare("

    SELECT

        b.id,

        b.booking_reference,

        b.student_name,

        b.curriculum,

        b.class_year,

        b.subjects,

        b.lesson_date,

        b.lesson_time,

        b.lesson_status,

        b.payment_status,

        b.teacher_id,

        b.teacher_name,

        t.teacher_name AS assigned_teacher_name,

        t.zoom_link AS teacher_zoom_link


    FROM bookings b


    LEFT JOIN teachers t

        ON b.teacher_id = t.teacher_id


    WHERE

        b.student_id = ?

        AND DATE(b.lesson_date) > CURDATE()


    ORDER BY

        b.lesson_date ASC,

        b.lesson_time ASC


    LIMIT 8

");


$upcomingStmt->execute([
    $student_id
]);


$upcomingLessons = $upcomingStmt->fetchAll(
    PDO::FETCH_ASSOC
);


$totalUpcoming = count(
    $upcomingLessons
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
    My Schedule | NISEL ONLINE EDUCATION
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

    font-family:
        Arial,
        Helvetica,
        sans-serif;

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

    overflow-y: auto;
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

    transition: 0.2s;
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
   TOP BAR
===================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.topbar h2 {

    margin: 0;

    color: #003366;
}


.student-name {

    color: #666;
}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.page-header h2 {

    margin: 0 0 8px;

    color: #003366;
}


.page-header p {

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
        repeat(
            auto-fit,
            minmax(200px, 1fr)
        );

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
   SECTION
===================================================== */

.section-card {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.section-header {

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    margin-bottom: 20px;
}


.section-header h3 {

    margin: 0;

    color: #003366;
}


.section-header p {

    margin: 5px 0 0;

    color: #777;
}


/* =====================================================
   TODAY LESSON CARD
===================================================== */

.today-lesson {

    border: 1px solid #e1e7ee;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 15px;

    display: grid;

    grid-template-columns:
        160px 1fr auto;

    gap: 20px;

    align-items: center;
}


.today-lesson:last-child {

    margin-bottom: 0;
}


.lesson-time {

    color: #003366;

    font-weight: bold;

    font-size: 18px;
}


.lesson-time small {

    display: block;

    color: #777;

    font-size: 12px;

    margin-top: 5px;
}


.lesson-info h4 {

    margin: 0 0 10px;

    color: #003366;

    font-size: 18px;
}


.lesson-info p {

    margin: 5px 0;

    color: #555;
}


.lesson-action {

    text-align: right;
}


/* =====================================================
   UPCOMING
===================================================== */

.upcoming-card {

    border-left:
        5px solid #003366;

    background: #f8fbff;

    padding: 18px;

    border-radius: 8px;

    margin-bottom: 12px;
}


.upcoming-card:last-child {

    margin-bottom: 0;
}


.upcoming-date {

    color: #003366;

    font-weight: bold;

    font-size: 16px;
}


.upcoming-subject {

    font-size: 18px;

    font-weight: bold;

    margin-top: 5px;
}


.upcoming-details {

    margin-top: 8px;

    color: #666;

    line-height: 1.6;
}


/* =====================================================
   ALL SCHEDULE TABLE
===================================================== */

.table-container {

    background: white;

    padding: 20px;

    border-radius: 12px;

    overflow-x: auto;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.table-container h3 {

    color: #003366;

    margin-top: 0;

    margin-bottom: 20px;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
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

    border-bottom:
        1px solid #ddd;

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

    white-space: nowrap;
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


.pending {

    background: #fff3cd;

    color: #856404;
}


.paid {

    background: #d4edda;

    color: #155724;
}


/* =====================================================
   TEACHER
===================================================== */

.teacher-name {

    color: #003366;

    font-weight: bold;
}


/* =====================================================
   ZOOM
===================================================== */

.zoom-button {

    display: inline-block;

    padding: 9px 14px;

    background: #2d8cff;

    color: white;

    text-decoration: none;

    border-radius: 6px;

    font-size: 13px;

    font-weight: bold;

    white-space: nowrap;

    transition: 0.2s;
}


.zoom-button:hover {

    background: #1769c2;
}


.no-zoom {

    color: #999;

    font-size: 12px;

    white-space: nowrap;
}


/* =====================================================
   EMPTY STATE
===================================================== */

.no-data {

    padding: 35px;

    text-align: center;

    color: #777;
}


.no-data-icon {

    font-size: 45px;

    margin-bottom: 10px;
}


.no-data h3 {

    color: #003366;

    margin-bottom: 5px;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }


    .main {

        margin-left: 0;

        padding: 15px;
    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;
    }


    .stats {

        grid-template-columns: 1fr;
    }


    .today-lesson {

        grid-template-columns: 1fr;
    }


    .lesson-action {

        text-align: left;
    }


    .section-header {

        align-items: flex-start;
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


    <div class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a
            href="schedule.php"
            class="active"
        >

            📅 My Schedule

        </a>


        <a href="subjects.php">

            📚 My Subjects

        </a>


        <a href="profile.php">

            👤 My Profile

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>


    </div>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <h2>

            My Schedule

        </h2>


        <div class="student-name">

            Welcome,

            <strong>

                <?= h($student_name) ?>

            </strong>

        </div>


    </div>


    <!-- PAGE HEADER -->

    <div class="page-header">


        <h2>

            📅 My Lesson Schedule

        </h2>


        <p>

            View your lessons, teachers,
            lesson times and join your
            online classes through Zoom.

        </p>


    </div>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?= $totalLessons ?>

            </h3>

            <p>

                Total Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?= $totalToday ?>

            </h3>

            <p>

                Today's Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?= $totalUpcoming ?>

            </h3>

            <p>

                Upcoming Lessons

            </p>

        </div>


    </div>


    <!-- =================================================
         TODAY'S LESSONS
    ================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <h3>

                    📌 Today's Lessons

                </h3>


                <p>

                    Lessons scheduled for today

                </p>

            </div>


        </div>


        <?php if (
            !empty($todayLessons)
        ): ?>


            <?php foreach (
                $todayLessons
                as $today
            ): ?>


                <div class="today-lesson">


                    <!-- TIME -->

                    <div class="lesson-time">

                        🕐

                        <?= h(

                            formatTimeValue(

                                $today[
                                    'lesson_time'
                                ]

                            )

                        ) ?>


                        <small>

                            <?= h(

                                formatDateValue(

                                    $today[
                                        'lesson_date'
                                    ]

                                )

                            ) ?>

                        </small>

                    </div>


                    <!-- INFORMATION -->

                    <div class="lesson-info">


                        <h4>

                            <?= h(

                                $today[
                                    'subjects'
                                ]

                                ??

                                'Lesson'

                            ) ?>

                        </h4>


                        <p>

                            <strong>
                                Curriculum:
                            </strong>

                            <?= h(

                                $today[
                                    'curriculum'
                                ]

                                ??

                                'N/A'

                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Class:
                            </strong>

                            <?= h(

                                $today[
                                    'class_year'
                                ]

                                ??

                                'N/A'

                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Teacher:
                            </strong>


                            <span
                                class="teacher-name"
                            >

                                <?= h(

                                    $today[
                                        'assigned_teacher_name'
                                    ]

                                    ??

                                    $today[
                                        'teacher_name'
                                    ]

                                    ??

                                    'Not assigned'

                                ) ?>

                            </span>

                        </p>


                        <div
                            style="margin-top:10px;"
                        >

                            <?= lessonStatusBadge(

                                $today[
                                    'lesson_status'
                                ]

                            ) ?>

                        </div>


                    </div>


                    <!-- ZOOM -->

                    <div class="lesson-action">


                        <?= zoomButton(

                            $today[
                                'teacher_zoom_link'
                            ]

                            ??

                            ''

                        ) ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    📅

                </div>


                <h3>

                    No Lessons Today

                </h3>


                <p>

                    You currently have no lesson
                    scheduled for today.

                </p>


            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         UPCOMING LESSONS
    ================================================== -->

    <div class="section-card">


        <div class="section-header">


            <div>

                <h3>

                    📚 Upcoming Lessons

                </h3>


                <p>

                    Your next scheduled lessons

                </p>

            </div>


        </div>


        <?php if (
            !empty($upcomingLessons)
        ): ?>


            <?php foreach (
                $upcomingLessons
                as $lesson
            ): ?>


                <div class="upcoming-card">


                    <div class="upcoming-date">

                        📅

                        <?= h(

                            formatDateValue(

                                $lesson[
                                    'lesson_date'
                                ]

                            )

                        ) ?>


                        &nbsp; | &nbsp;


                        🕐

                        <?= h(

                            formatTimeValue(

                                $lesson[
                                    'lesson_time'
                                ]

                            )

                        ) ?>

                    </div>


                    <div class="upcoming-subject">

                        <?= h(

                            $lesson[
                                'subjects'
                            ]

                            ??

                            'Lesson'

                        ) ?>

                    </div>


                    <div class="upcoming-details">


                        <strong>

                            Curriculum:

                        </strong>

                        <?= h(

                            $lesson[
                                'curriculum'
                            ]

                            ??

                            'N/A'

                        ) ?>


                        <br>


                        <strong>

                            Class:

                        </strong>

                        <?= h(

                            $lesson[
                                'class_year'
                            ]

                            ??

                            'N/A'

                        ) ?>


                        <br>


                        <strong>

                            Teacher:

                        </strong>

                        <span
                            class="teacher-name"
                        >

                            <?= h(

                                $lesson[
                                    'assigned_teacher_name'
                                ]

                                ??

                                $lesson[
                                    'teacher_name'
                                ]

                                ??

                                'Not assigned'

                            ) ?>

                        </span>


                        <br>


                        <br>


                        <?= lessonStatusBadge(

                            $lesson[
                                'lesson_status'
                            ]

                        ) ?>


                        &nbsp;


                        <?= zoomButton(

                            $lesson[
                                'teacher_zoom_link'
                            ]

                            ??

                            ''

                        ) ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    📚

                </div>


                <h3>

                    No Upcoming Lessons

                </h3>


                <p>

                    Your upcoming lessons will
                    appear here once they are scheduled.

                </p>


            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         ALL ASSIGNED LESSONS
    ================================================== -->

    <div class="table-container">


        <h3>

            📋 All Assigned Lessons

        </h3>


        <?php if (
            !empty($schedules)
        ): ?>


            <table>


                <thead>

                    <tr>


                        <th>
                            Date
                        </th>


                        <th>
                            Time
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
                            Teacher
                        </th>


                        <th>
                            Payment
                        </th>


                        <th>
                            Status
                        </th>


                        <th>
                            Zoom
                        </th>


                    </tr>

                </thead>


                <tbody>


                <?php foreach (
                    $schedules
                    as $row
                ): ?>


                    <tr>


                        <!-- DATE -->

                        <td>

                            <?= h(

                                formatDateValue(

                                    $row[
                                        'lesson_date'
                                    ]

                                )

                            ) ?>

                        </td>


                        <!-- TIME -->

                        <td>

                            <?= h(

                                formatTimeValue(

                                    $row[
                                        'lesson_time'
                                    ]

                                )

                            ) ?>

                        </td>


                        <!-- SUBJECT -->

                        <td>

                            <strong>

                                <?= h(

                                    $row[
                                        'subjects'
                                    ]

                                    ??

                                    'Lesson'

                                ) ?>

                            </strong>

                        </td>


                        <!-- CURRICULUM -->

                        <td>

                            <?= h(

                                $row[
                                    'curriculum'
                                ]

                                ??

                                'N/A'

                            ) ?>

                        </td>


                        <!-- CLASS -->

                        <td>

                            <?= h(

                                $row[
                                    'class_year'
                                ]

                                ??

                                'N/A'

                            ) ?>

                        </td>


                        <!-- TEACHER -->

                        <td>

                            <span
                                class="teacher-name"
                            >

                                <?= h(

                                    $row[
                                        'assigned_teacher_name'
                                    ]

                                    ??

                                    $row[
                                        'teacher_name'
                                    ]

                                    ??

                                    'Not assigned'

                                ) ?>

                            </span>

                        </td>


                        <!-- PAYMENT -->

                        <td>

                            <?= paymentStatusBadge(

                                $row[
                                    'payment_status'
                                ]

                            ) ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?= lessonStatusBadge(

                                $row[
                                    'lesson_status'
                                ]

                            ) ?>

                        </td>


                        <!-- ZOOM -->

                        <td>

                            <?= zoomButton(

                                $row[
                                    'teacher_zoom_link'
                                ]

                                ??

                                ''

                            ) ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    📚

                </div>


                <h3>

                    No Lessons Found

                </h3>


                <p>

                    You currently have no lessons
                    assigned to your account.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
