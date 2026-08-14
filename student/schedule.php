<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT LESSON SCHEDULE
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


/*
|--------------------------------------------------------------------------
| GET STUDENT SESSION
|--------------------------------------------------------------------------
*/

$student_id =
    (int) $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? "Student";


/*
|--------------------------------------------------------------------------
| GET STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

try {

    $studentStmt = $pdo->prepare("
        SELECT
            id,
            student_name,
            email
        FROM students
        WHERE id = ?
        LIMIT 1
    ");

    $studentStmt->execute([
        $student_id
    ]);

    $student = $studentStmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$student) {

        session_destroy();

        header("Location: login.php");
        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | USE DATABASE VALUES
    |--------------------------------------------------------------------------
    */

    $student_id =
        (int) $student['id'];

    $student_name =
        $student['student_name'];

    $student_email =
        trim(
            $student['email']
        );


    /*
    |--------------------------------------------------------------------------
    | GET ALL LESSONS
    |--------------------------------------------------------------------------
    |
    | We check:
    |
    | 1. student_id
    | OR
    | 2. student email
    |
    | This makes the page compatible with existing bookings.
    |
    |--------------------------------------------------------------------------
    */

    $scheduleStmt = $pdo->prepare("
        SELECT

            b.id,

            b.booking_reference,

            b.student_id,

            b.student_name,

            b.email,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.lesson_date,

            b.lesson_time,

            b.lesson_status,

            b.payment_status,

            b.teacher_id,

            b.teacher_name,

            COALESCE(
                t.teacher_name,
                b.teacher_name
            ) AS assigned_teacher_name,

            t.zoom_link AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE

            (
                b.student_id = ?

                OR

                LOWER(TRIM(b.email))
                =
                LOWER(TRIM(?))
            )

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
        $student_id,
        $student_email
    ]);


    $schedules =
        $scheduleStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | TOTAL LESSONS
    |--------------------------------------------------------------------------
    */

    $totalLessons =
        count($schedules);


    /*
    |--------------------------------------------------------------------------
    | TODAY'S LESSONS
    |--------------------------------------------------------------------------
    */

    $todayStmt = $pdo->prepare("
        SELECT

            b.id,

            b.booking_reference,

            b.student_id,

            b.student_name,

            b.email,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.lesson_date,

            b.lesson_time,

            b.lesson_status,

            b.payment_status,

            b.teacher_id,

            b.teacher_name,

            COALESCE(
                t.teacher_name,
                b.teacher_name
            ) AS assigned_teacher_name,

            t.zoom_link AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE

            (
                b.student_id = ?

                OR

                LOWER(TRIM(b.email))
                =
                LOWER(TRIM(?))
            )

            AND DATE(b.lesson_date) = CURDATE()

        ORDER BY

            b.lesson_time ASC
    ");


    $todayStmt->execute([
        $student_id,
        $student_email
    ]);


    $todayLessons =
        $todayStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    $totalToday =
        count($todayLessons);


    /*
    |--------------------------------------------------------------------------
    | UPCOMING LESSONS
    |--------------------------------------------------------------------------
    */

    $upcomingStmt = $pdo->prepare("
        SELECT

            b.id,

            b.booking_reference,

            b.student_id,

            b.student_name,

            b.email,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.lesson_date,

            b.lesson_time,

            b.lesson_status,

            b.payment_status,

            b.teacher_id,

            b.teacher_name,

            COALESCE(
                t.teacher_name,
                b.teacher_name
            ) AS assigned_teacher_name,

            t.zoom_link AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE

            (
                b.student_id = ?

                OR

                LOWER(TRIM(b.email))
                =
                LOWER(TRIM(?))
            )

            AND DATE(b.lesson_date) > CURDATE()

        ORDER BY

            b.lesson_date ASC,

            b.lesson_time ASC

        LIMIT 8
    ");


    $upcomingStmt->execute([
        $student_id,
        $student_email
    ]);


    $upcomingLessons =
        $upcomingStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die(
        "
        <div style='
            font-family:Arial;
            padding:40px;
            color:#b00020;
        '>
            <h2>Unable to load schedule</h2>

            <p>
                Database error:
            </p>

            <pre>"
            .
            htmlspecialchars(
                $e->getMessage()
            )
            .
            "</pre>
        </div>
        "
    );

}


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function h($value)
{

    return htmlspecialchars(
        (string) $value,
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

    if (
        empty($date)
    ) {

        return "Not scheduled";

    }


    $timestamp =
        strtotime($date);


    if (
        $timestamp === false
    ) {

        return "Not scheduled";

    }


    return date(
        "D, d M Y",
        $timestamp
    );

}


/*
|--------------------------------------------------------------------------
| FORMAT TIME
|--------------------------------------------------------------------------
*/

function formatTimeValue($time)
{

    if (
        empty($time)
    ) {

        return "Not set";

    }


    $timestamp =
        strtotime($time);


    if (
        $timestamp === false
    ) {

        return "Not set";

    }


    return date(
        "h:i A",
        $timestamp
    );

}


/*
|--------------------------------------------------------------------------
| LESSON STATUS
|--------------------------------------------------------------------------
*/

function lessonStatusBadge($status)
{

    $status =
        strtolower(
            trim(
                $status
                ?? "scheduled"
            )
        );


    if (
        $status === "completed"
    ) {

        return '
            <span class="badge completed">
                ✓ Completed
            </span>
        ';

    }


    if (
        $status === "cancelled"
    ) {

        return '
            <span class="badge cancelled">
                ✕ Cancelled
            </span>
        ';

    }


    return '
        <span class="badge scheduled">
            ● Scheduled
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

    $status =
        strtolower(
            trim(
                $status
                ?? ""
            )
        );


    if (
        $status === "paid" ||
        $status === "success"
    ) {

        return '
            <span class="badge paid">
                ✓ PAID
            </span>
        ';

    }


    if (
        $status === "failed"
    ) {

        return '
            <span class="badge cancelled">
                ✕ FAILED
            </span>
        ';

    }


    return '
        <span class="badge pending">
            PENDING
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

    if (
        !empty(
            trim(
                $zoomLink
            )
        )
    ) {

        return '
            <a
                href="' .
                h($zoomLink)
                . '"
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
   NISEL ONLINE EDUCATION
   STUDENT SCHEDULE - MODERN CSS
===================================================== */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #eef3f8;
    color: #333;
    min-height: 100vh;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 240px;
    height: 100vh;
    background: linear-gradient(180deg, #003b70 0%, #002b55 100%);
    color: #fff;
    padding: 25px 15px;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 4px 0 18px rgba(0, 0, 0, 0.10);
}

.logo {
    text-align: center;
    font-size: 19px;
    font-weight: 700;
    line-height: 1.5;
    margin-bottom: 35px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

/* The HTML uses .nav */
.nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    color: rgba(255, 255, 255, 0.88);
    text-decoration: none;
    padding: 13px 14px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.2s ease, transform 0.2s ease, color 0.2s ease;
}

.nav a:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    transform: translateX(3px);
}

.nav a.active {
    background: #0867b8;
    color: #fff;
    font-weight: 700;
    box-shadow: inset 3px 0 #62c4ff;
}

.nav a.logout {
    margin-top: 25px;
    background: rgba(220, 53, 69, 0.92);
    color: #fff;
}

.nav a.logout:hover {
    background: #c82333;
    transform: translateX(2px);
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 240px;
    padding: 30px;
    min-height: 100vh;
}

/* =========================
   HEADER
========================= */

.topbar {
    background: #fff;
    padding: 24px 28px;
    border-radius: 14px;
    margin-bottom: 25px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.03);
}

.topbar h1 {
    margin: 0 0 8px;
    color: #003b70;
    font-size: 28px;
    line-height: 1.2;
}

.topbar p {
    margin: 0;
    color: #777;
    font-size: 14px;
    line-height: 1.5;
}

/* =========================
   STATISTICS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    position: relative;
    background: #fff;
    padding: 22px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e8edf3;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.09);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: rgba(0, 83, 151, 0.07);
    right: -20px;
    top: -20px;
}

.stat-icon {
    position: relative;
    z-index: 1;
    font-size: 25px;
    margin-bottom: 8px;
}

.stat-number {
    position: relative;
    z-index: 1;
    font-size: 30px;
    font-weight: 700;
    color: #003b70;
}

.stat-title {
    position: relative;
    z-index: 1;
    margin-top: 5px;
    color: #777;
    font-size: 14px;
}

/* =========================
   SECTIONS
========================= */

.section {
    background: #fff;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #e8edf3;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.section-header h2 {
    margin: 0;
    color: #003b70;
    font-size: 20px;
}

/* =========================
   TODAY CARD
========================= */

.today-card {
    background: #fff;
    border: 1px solid #e3e8ef;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.today-card:hover {
    transform: translateY(-2px);
    border-color: #cbdceb;
    box-shadow: 0 7px 22px rgba(0, 0, 0, 0.08);
}

.lesson-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;
}

.subject-title {
    font-size: 20px;
    font-weight: 700;
    color: #003b70;
    margin-bottom: 6px;
    line-height: 1.3;
}

.lesson-reference {
    font-size: 12px;
    color: #888;
    word-break: break-word;
}

.lesson-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-top: 18px;
}

.lesson-info {
    background: #f7f9fc;
    padding: 13px;
    border-radius: 9px;
    border: 1px solid #edf1f5;
    min-width: 0;
}

.lesson-label {
    display: block;
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 5px;
}

.lesson-value {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    word-break: break-word;
}

/* =========================
   TEACHER
========================= */

.teacher-box {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.teacher-avatar {
    flex: 0 0 40px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e7f2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.teacher-name {
    font-weight: 600;
    color: #003b70;
    word-break: break-word;
}

/* =========================
   BADGES
========================= */

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.badge.scheduled {
    background: #e8f2ff;
    color: #0867b8;
}

.badge.completed {
    background: #e7f7ed;
    color: #16803d;
}

.badge.cancelled {
    background: #fdecec;
    color: #c62828;
}

.badge.paid {
    background: #e7f7ed;
    color: #16803d;
}

.badge.pending {
    background: #fff5dc;
    color: #9a6b00;
}

/* =========================
   CLASSROOM BUTTON
========================= */

.classroom-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 18px;
    padding: 11px 18px;
    background: linear-gradient(135deg, #003b70, #0867b8);
    color: #fff;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0, 59, 112, 0.18);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.classroom-button:hover {
    background: linear-gradient(135deg, #00529a, #0878d0);
    transform: translateY(-2px);
    box-shadow: 0 7px 16px rgba(0, 59, 112, 0.25);
}

.classroom-button:active {
    transform: translateY(0);
}

/* =========================
   ZOOM BUTTON
========================= */

.zoom-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin-top: 18px;
    padding: 11px 17px;
    background: #0b65b7;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.zoom-button:hover {
    background: #084f8f;
    transform: translateY(-1px);
    box-shadow: 0 5px 12px rgba(8, 79, 143, 0.18);
}

.no-zoom {
    display: inline-block;
    margin-top: 18px;
    color: #999;
    font-size: 13px;
}

/* =========================
   EMPTY STATE
========================= */

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: #777;
}

.empty-icon {
    font-size: 45px;
    margin-bottom: 12px;
}

.empty-state h3 {
    color: #555;
    margin: 5px 0 8px;
    font-size: 18px;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
}

/* =========================
   TABLE
========================= */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #f4f7fb;
    color: #003b70;
    font-size: 12px;
    text-transform: uppercase;
    text-align: left;
    padding: 14px;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
}

td {
    padding: 15px 14px;
    border-bottom: 1px solid #edf0f4;
    font-size: 13px;
    vertical-align: middle;
}

tr:hover td {
    background: #fafcff;
}

.subject-cell {
    font-weight: 700;
    color: #003b70;
}

.not-scheduled {
    color: #999;
    font-style: italic;
}

/* =========================
   BOOK BUTTON
========================= */

.book-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 11px 18px;
    background: #003b70;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: background 0.2s ease, transform 0.2s ease;
}

.book-button:hover {
    background: #075ca5;
    transform: translateY(-1px);
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1000px) {
    .lesson-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 18px;
        box-shadow: none;
    }

    .logo {
        margin-bottom: 15px;
    }

    .nav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
    }

    .nav a {
        margin: 0;
    }

    .nav a.logout {
        margin-top: 0;
    }

    .main {
        margin-left: 0;
        padding: 15px;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .lesson-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 600px) {
    .nav {
        grid-template-columns: 1fr;
    }

    .topbar {
        padding: 20px;
    }

    .topbar h1 {
        font-size: 23px;
    }

    .section {
        padding: 17px;
    }

    .lesson-grid {
        grid-template-columns: 1fr;
    }

    .lesson-top {
        gap: 10px;
    }

    .subject-title {
        font-size: 18px;
    }

    .classroom-button,
    .zoom-button,
    .book-button {
        width: 100%;
    }

    .teacher-box {
        align-items: flex-start;
    }
}

@media (max-width: 400px) {
    .main {
        padding: 10px;
    }

    .topbar {
        padding: 17px;
    }

    .section {
        padding: 14px;
    }

    .today-card {
        padding: 15px;
    }

    .lesson-info {
        padding: 11px;
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


    <nav class="nav">

        <a
            href="dashboard.php"
            class="active"
        >
            🏠 Dashboard
        </a>

        <a href="schedule.php">
            📅 My Schedule
        </a>

        <a href="classroom.php">
            🎥 Live Classroom
        </a>

        <a href="profile.php">
            👤 My Profile
        </a>

        <a href="payments.php">
            💳 My Payments
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            🚪 Logout
        </a>

    </nav>





<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="topbar">

        <h1>

            📅 Lesson Schedule

        </h1>


        <p>

            View your assigned lessons and join your online
            classes directly through Zoom.

        </p>

    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                📚
            </div>

            <div class="stat-number">

                <?= $totalLessons ?>

            </div>

            <div class="stat-title">

                Total Assigned Lessons

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                📌
            </div>

            <div class="stat-number">

                <?= $totalToday ?>

            </div>

            <div class="stat-title">

                Lessons Today

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                🗓️
            </div>

            <div class="stat-number">

                <?= count($upcomingLessons) ?>

            </div>

            <div class="stat-title">

                Upcoming Lessons

            </div>

        </div>

    </div>



    <!-- =================================================
         TODAY'S LESSONS
    ================================================== -->

    <div class="section">


        <div class="section-header">

            <h2>

                📌 Today's Lessons

            </h2>

        </div>



        <?php if ($totalToday > 0): ?>


            <?php foreach (
                $todayLessons
                as $today
            ): ?>


                <div class="today-card">


                    <div class="lesson-top">


                        <div>

                            <div class="subject-title">

                                <?= h(
                                    $today['subjects']
                                    ?? 'Lesson'
                                ) ?>

                            </div>


                            <div class="lesson-reference">

                                Booking Reference:

                                <?= h(
                                    $today['booking_reference']
                                    ?? 'N/A'
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <?= lessonStatusBadge(
                                $today['lesson_status']
                                ?? 'Scheduled'
                            ) ?>

                        </div>

                    </div>



                    <div class="lesson-grid">


                        <!-- DATE -->

                        <div class="lesson-info">

                            <span class="lesson-label">

                                Date

                            </span>


                            <span class="lesson-value">

                                <?= formatDateValue(
                                    $today['lesson_date']
                                ) ?>

                            </span>

                        </div>



                        <!-- TIME -->

                        <div class="lesson-info">

                            <span class="lesson-label">

                                Time

                            </span>


                            <span class="lesson-value">

                                <?= formatTimeValue(
                                    $today['lesson_time']
                                ) ?>

                            </span>

                        </div>



                        <!-- TEACHER -->

                        <div class="lesson-info">

                            <span class="lesson-label">

                                Teacher

                            </span>


                            <div class="teacher-box">

                                <div class="teacher-avatar">

                                    👨‍🏫

                                </div>


                                <div class="teacher-name">

                                    <?= h(
                                        $today[
                                            'assigned_teacher_name'
                                        ]
                                        ?? 'Not Assigned'
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <!-- PAYMENT -->

                        <div class="lesson-info">

                            <span class="lesson-label">

                                Payment

                            </span>


                            <?= paymentStatusBadge(
                                $today['payment_status']
                                ?? ''
                            ) ?>

                        </div>


                    </div>



                    <!-- ZOOM -->

                    <?= zoomButton(
                        $today['teacher_zoom_link']
                        ?? ''
                    ) ?>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty-state">

                <div class="empty-icon">

                    📅

                </div>


                <h3>

                    No lessons scheduled for today

                </h3>


                <p>

                    Your lessons scheduled for today
                    will appear here.

                </p>

            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         UPCOMING LESSONS
    ================================================== -->

    <div class="section">


        <div class="section-header">

            <h2>

                🗓️ Upcoming Lessons

            </h2>

        </div>



        <?php if (
            count($upcomingLessons) > 0
        ): ?>


            <?php foreach (
                $upcomingLessons
                as $lesson
            ): ?>


                <div class="today-card">


                    <div class="lesson-top">


                        <div>

                            <div class="subject-title">

                                <?= h(
                                    $lesson['subjects']
                                    ?? 'Lesson'
                                ) ?>

                            </div>


                            <div class="lesson-reference">

                                <?= h(
                                    $lesson['booking_reference']
                                    ?? 'N/A'
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <?= lessonStatusBadge(
                                $lesson['lesson_status']
                                ?? 'Scheduled'
                            ) ?>

                        </div>


                    </div>



                    <div class="lesson-grid">


                        <div class="lesson-info">

                            <span class="lesson-label">

                                Date

                            </span>


                            <span class="lesson-value">

                                <?= formatDateValue(
                                    $lesson['lesson_date']
                                ) ?>

                            </span>

                        </div>



                        <div class="lesson-info">

                            <span class="lesson-label">

                                Time

                            </span>


                            <span class="lesson-value">

                                <?= formatTimeValue(
                                    $lesson['lesson_time']
                                ) ?>

                            </span>

                        </div>



                        <div class="lesson-info">

                            <span class="lesson-label">

                                Teacher

                            </span>


                            <div class="teacher-box">

                                <div class="teacher-avatar">

                                    👨‍🏫

                                </div>


                                <div class="teacher-name">

                                    <?= h(
                                        $lesson[
                                            'assigned_teacher_name'
                                        ]
                                        ?? 'Not Assigned'
                                    ) ?>

                                </div>

                            </div>

                        </div>



                        <div class="lesson-info">

                            <span class="lesson-label">

                                Payment

                            </span>


                            <?= paymentStatusBadge(
                                $lesson['payment_status']
                                ?? ''
                            ) ?>

                        </div>


                    </div>



                    <?= zoomButton(
                        $lesson['teacher_zoom_link']
                        ?? ''
                    ) ?>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty-state">

                <div class="empty-icon">

                    🗓️

                </div>


                <h3>

                    No upcoming lessons

                </h3>


                <p>

                    Your upcoming scheduled lessons
                    will appear here.

                </p>

            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         ALL ASSIGNED LESSONS
    ================================================== -->

    <div class="section">


        <div class="section-header">

            <h2>

                📚 All Assigned Lessons

            </h2>


            <a
                href="book_lesson.php"
                class="book-button"
            >

                ➕ Book a Subject

            </a>

        </div>



        <?php if (
            $totalLessons > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Subject
                            </th>

                            <th>
                                Curriculum
                            </th>

                            <th>
                                Class / Year
                            </th>

                            <th>
                                Teacher
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
                                Payment
                            </th>

                            <th>
                                Class
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $schedules
                        as $row
                    ): ?>


                        <tr>


                            <!-- SUBJECT -->

                            <td class="subject-cell">

                                <?= h(
                                    $row['subjects']
                                    ?? 'N/A'
                                ) ?>

                            </td>



                            <!-- CURRICULUM -->

                            <td>

                                <?= h(
                                    $row['curriculum']
                                    ?? 'N/A'
                                ) ?>

                            </td>



                            <!-- CLASS -->

                            <td>

                                <?= h(
                                    $row['class_year']
                                    ?? 'N/A'
                                ) ?>

                            </td>



                            <!-- TEACHER -->

                            <td>

                                <?php

                                $teacherDisplay =
                                    $row[
                                        'assigned_teacher_name'
                                    ]
                                    ??
                                    'Not Assigned';

                                ?>

                                <div class="teacher-box">

                                    <div class="teacher-avatar">

                                        👨‍🏫

                                    </div>


                                    <div class="teacher-name">

                                        <?= h(
                                            $teacherDisplay
                                        ) ?>

                                    </div>

                                </div>

                            </td>



                            <!-- DATE -->

                            <td>

                                <?php

                                if (
                                    empty(
                                        $row['lesson_date']
                                    )
                                ) {

                                    echo '
                                        <span class="not-scheduled">
                                            Not Scheduled
                                        </span>
                                    ';

                                } else {

                                    echo h(
                                        formatDateValue(
                                            $row['lesson_date']
                                        )
                                    );

                                }

                                ?>

                            </td>



                            <!-- TIME -->

                            <td>

                                <?php

                                if (
                                    empty(
                                        $row['lesson_time']
                                    )
                                ) {

                                    echo '
                                        <span class="not-scheduled">
                                            Not Set
                                        </span>
                                    ';

                                } else {

                                    echo h(
                                        formatTimeValue(
                                            $row['lesson_time']
                                        )
                                    );

                                }

                                ?>

                            </td>



                            <!-- LESSON STATUS -->

                            <td>

                                <?= lessonStatusBadge(
                                    $row['lesson_status']
                                    ?? 'Scheduled'
                                ) ?>

                            </td>



                            <!-- PAYMENT -->

                            <td>

                                <?= paymentStatusBadge(
                                    $row['payment_status']
                                    ?? ''
                                ) ?>

                            </td>



                            <!-- ZOOM -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row[
                                            'teacher_zoom_link'
                                        ]
                                    )
                                ) {

                                    ?>

                                    <a
                                        href="<?= h(
                                            $row[
                                                'teacher_zoom_link'
                                            ]
                                        ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="zoom-button"
                                        style="
                                            margin-top:0;
                                            padding:8px 11px;
                                            font-size:11px;
                                        "
                                    >

                                        🎥 Join

                                    </a>

                                    <?php

                                } else {

                                    ?>

                                    <span
                                        class="not-scheduled"
                                    >

                                        Not Available

                                    </span>

                                    <?php

                                }

                                ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-state">

                <div class="empty-icon">

                    📚

                </div>


                <h3>

                    You currently have no lessons assigned

                </h3>


                <p>

                    Your schedule will appear here
                    when the administrator assigns
                    your classes.

                </p>


                <br>


                <a
                    href="book_lesson.php"
                    class="book-button"
                >

                    ➕ Book a Subject

                </a>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
