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

/*
|--------------------------------------------------------------------------
| LIVE CLASSROOM BUTTON
|--------------------------------------------------------------------------
*/

function classroomButton($bookingId)
{

    $bookingId = (int) $bookingId;

    if ($bookingId <= 0) {

        return '';

    }

    return '
        <a
            href="classroom.php?id=' .
            $bookingId .
            '"
            class="classroom-button"
            title="Open NISEL Live Classroom"
        >
            🎥 <span>Live Classroom</span>
        </a>
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
   RESET
===================================================== */

* {

    box-sizing:
        border-box;

}


/* =====================================================
   BODY
===================================================== */

body {

    margin:
        0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #eef3f8;

    color:
        #333;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position:
        fixed;

    left:
        0;

    top:
        0;

    width:
        240px;

    height:
        100vh;

    background:
        #003b70;

    color:
        white;

    padding:
        25px 15px;

    z-index:
        1000;

}


.logo {

    text-align:
        center;

    font-size:
        19px;

    font-weight:
        bold;

    line-height:
        1.5;

    margin-bottom:
        35px;

}


.menu a {

    display:
        block;

    color:
        white;

    text-decoration:
        none;

    padding:
        14px;

    margin-bottom:
        8px;

    border-radius:
        8px;

    transition:
        0.2s;

}


.menu a:hover {

    background:
        #075ca5;

}


.menu a.active {

    background:
        #0867b8;

}


.menu a.logout {

    margin-top:
        30px;

}


/* =====================================================
   MAIN - MODERN SCHEDULE DESIGN
   Sidebar above this point is intentionally untouched.
===================================================== */

.main {
    margin-left: 240px;
    padding: 28px 32px 45px;
    min-height: 100vh;
    background:
        radial-gradient(circle at 95% 0%, rgba(8,103,184,.07), transparent 28%),
        #f4f7fb;
}

/* =========================
   PAGE HEADER
========================= */

.topbar {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
    padding: 28px 30px;
    border: 1px solid #e5ebf2;
    border-radius: 20px;
    margin-bottom: 22px;
    box-shadow: 0 10px 35px rgba(20, 55, 90, .07);
}

.topbar::after {
    content: "";
    position: absolute;
    width: 170px;
    height: 170px;
    right: -65px;
    top: -75px;
    border-radius: 50%;
    background: rgba(8,103,184,.08);
}

.topbar h1 {
    position: relative;
    z-index: 1;
    margin: 0 0 7px;
    color: #073b68;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.5px;
}

.topbar p {
    position: relative;
    z-index: 1;
    margin: 0;
    color: #718096;
    font-size: 14px;
    line-height: 1.6;
}

/* =========================
   STAT CARDS
========================= */

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    min-height: 135px;
    background: #fff;
    padding: 22px;
    border: 1px solid #e5ebf2;
    border-radius: 18px;
    box-shadow: 0 8px 28px rgba(20,55,90,.06);
    transition: transform .2s ease, box-shadow .2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 35px rgba(20,55,90,.10);
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 100px;
    height: 100px;
    right: -35px;
    top: -35px;
    border-radius: 50%;
    background: rgba(8,103,184,.07);
}

.stat-icon {
    position: relative;
    z-index: 1;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border-radius: 12px;
    background: #edf6ff;
    font-size: 21px;
}

.stat-number {
    position: relative;
    z-index: 1;
    color: #073b68;
    font-size: 30px;
    line-height: 1;
    font-weight: 800;
}

.stat-title {
    position: relative;
    z-index: 1;
    margin-top: 7px;
    color: #7a8795;
    font-size: 13px;
    font-weight: 600;
}

/* =========================
   SECTIONS
========================= */

.section {
    background: #fff;
    border: 1px solid #e5ebf2;
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 8px 28px rgba(20,55,90,.055);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 18px;
}

.section-header h2 {
    margin: 0;
    color: #073b68;
    font-size: 19px;
    font-weight: 800;
}

/* =========================
   TODAY'S LESSON
========================= */

.today-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #ffffff 0%, #f9fbfe 100%);
    border: 1px solid #e1e8f0;
    border-radius: 17px;
    padding: 21px;
    margin-bottom: 14px;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}

.today-card::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #0867b8, #39a6e8);
}

.today-card:hover {
    transform: translateY(-2px);
    border-color: #cbd9e8;
    box-shadow: 0 12px 30px rgba(20,55,90,.09);
}

.lesson-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
}

.subject-title {
    color: #073b68;
    font-size: 19px;
    font-weight: 800;
    line-height: 1.35;
    margin-bottom: 5px;
}

.lesson-reference {
    color: #8995a3;
    font-size: 11px;
    font-weight: 600;
}

.lesson-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-top: 18px;
}

.lesson-info {
    min-width: 0;
    background: #f6f8fb;
    border: 1px solid #e9eef4;
    border-radius: 12px;
    padding: 13px;
}

.lesson-label {
    display: block;
    margin-bottom: 6px;
    color: #8b97a5;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.lesson-value {
    color: #25364a;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.45;
    word-break: break-word;
}

.teacher-box {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.teacher-avatar {
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #eaf4ff;
    font-size: 17px;
}

.teacher-name {
    color: #073b68;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.35;
    word-break: break-word;
}

/* =========================
   STATUS BADGES
========================= */

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 26px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .2px;
    white-space: nowrap;
}

.badge.scheduled {
    background: #eaf4ff;
    color: #0867b8;
}

.badge.completed {
    background: #eaf8ef;
    color: #16803d;
}

.badge.cancelled {
    background: #fff0f0;
    color: #c62828;
}

.badge.paid {
    background: #eaf8ef;
    color: #16803d;
}

.badge.pending {
    background: #fff6df;
    color: #9a6b00;
}

/* =========================
   ACTION BUTTONS
========================= */

.zoom-button,
.classroom-button,
.book-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 10px 15px;
    border: 0;
    border-radius: 10px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.zoom-button {
    background: #eaf4ff;
    color: #0867b8;
    margin-top: 15px;
}

.zoom-button:hover {
    background: #dceeff;
    color: #075a9f;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(8,103,184,.12);
}

.classroom-button {
    background: linear-gradient(135deg, #073b68, #0867b8);
    color: #fff;
    margin-top: 15px;
    margin-left: 8px;
    box-shadow: 0 6px 16px rgba(8,103,184,.18);
}

.classroom-button:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 9px 20px rgba(8,103,184,.25);
}

.no-zoom {
    display: inline-block;
    margin-top: 15px;
    color: #a0aab5;
    font-size: 12px;
    font-weight: 600;
}

.book-button {
    background: #073b68;
    color: #fff;
}

.book-button:hover {
    background: #0867b8;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 7px 18px rgba(8,103,184,.18);
}

/* =========================
   EMPTY STATE
========================= */

.empty-state {
    padding: 50px 20px;
    text-align: center;
    color: #7b8794;
}

.empty-icon {
    width: 62px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    border-radius: 18px;
    background: #eef6ff;
    font-size: 30px;
}

.empty-state h3 {
    margin: 0 0 7px;
    color: #34495e;
    font-size: 17px;
}

.empty-state p {
    margin: 0;
    color: #8a96a3;
    font-size: 13px;
    line-height: 1.6;
}

/* =========================
   ALL LESSONS TABLE
========================= */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e6ecf2;
    border-radius: 14px;
}

table {
    width: 100%;
    min-width: 900px;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    padding: 13px 14px;
    background: #f5f8fb;
    border-bottom: 1px solid #e2e9f0;
    color: #617184;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .6px;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

th:first-child {
    border-top-left-radius: 13px;
}

th:last-child {
    border-top-right-radius: 13px;
}

td {
    padding: 14px;
    border-bottom: 1px solid #edf1f5;
    color: #536273;
    font-size: 12px;
    vertical-align: middle;
}

tbody tr {
    transition: background .15s ease;
}

tbody tr:hover td {
    background: #f9fbfd;
}

tbody tr:last-child td {
    border-bottom: 0;
}

.subject-cell {
    color: #073b68;
    font-size: 13px;
    font-weight: 800;
}

.not-scheduled {
    color: #a0aab5;
    font-size: 11px;
    font-style: italic;
}


.lesson-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0 8px;
    margin-top: 2px;
}

.lesson-actions .zoom-button,
.lesson-actions .classroom-button {
    margin-top: 15px;
    margin-left: 0;
}

/* =========================
   UPCOMING LESSONS
========================= */

.section .today-card + .today-card {
    margin-top: 0;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {
    .lesson-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .main {
        padding: 22px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    /*
     * The existing sidebar is intentionally NOT restyled here.
     * Only the main schedule content becomes responsive.
     */
    .main {
        margin-left: 0;
        padding: 18px 14px 35px;
    }

    .topbar {
        padding: 22px;
        border-radius: 16px;
    }

    .topbar h1 {
        font-size: 23px;
    }

    .section {
        padding: 18px;
        border-radius: 16px;
    }

    .lesson-top {
        flex-direction: column;
    }

    .lesson-grid {
        grid-template-columns: 1fr 1fr;
    }

    .section-header {
        align-items: flex-start;
    }
}

@media (max-width: 560px) {
    .lesson-grid {
        grid-template-columns: 1fr;
    }

    .section-header {
        flex-direction: column;
    }

    .book-button {
        width: 100%;
    }

    .zoom-button,
    .classroom-button {
        width: 100%;
        margin-left: 0;
    }

    .classroom-button {
        margin-top: 9px;
    }

    .stats {
        gap: 12px;
    }

    .stat-card {
        min-height: 115px;
        padding: 18px;
    }

    .today-card {
        padding: 17px;
    }

    .subject-title {
        font-size: 17px;
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

                    <div class="lesson-actions">

                        <?= zoomButton(
                            $today['teacher_zoom_link']
                            ?? ''
                        ) ?>

                        <?= classroomButton(
                            $today['id']
                            ?? 0
                        ) ?>

                    </div>


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



                    <div class="lesson-actions">

                        <?= zoomButton(
                            $lesson['teacher_zoom_link']
                            ?? ''
                        ) ?>

                        <?= classroomButton(
                            $lesson['id']
                            ?? 0
                        ) ?>

                    </div>


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
