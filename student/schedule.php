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
   NISEL STUDENT SCHEDULE
   COMPLETE MODERN DESIGN
===================================================== */

:root {
    --navy: #063b69;
    --navy-dark: #022b4d;
    --blue: #0877c9;
    --blue-light: #eaf5ff;
    --page: #f4f7fb;
    --card: #ffffff;
    --text: #172b3f;
    --muted: #718096;
    --line: #e4ebf2;
    --green: #149447;
    --green-bg: #eaf8ef;
    --orange: #a66a00;
    --orange-bg: #fff6df;
    --red: #c53030;
    --red-bg: #fff0f0;
    --shadow: 0 12px 35px rgba(17, 45, 72, .07);
}

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI",
                 Roboto, Arial, sans-serif;
    background: var(--page);
    color: var(--text);
}

/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 245px;
    height: 100vh;
    padding: 22px 14px;
    background: linear-gradient(180deg, #063f70 0%, #022e52 100%);
    color: #fff;
    z-index: 1000;
    overflow-y: auto;
    box-shadow: 5px 0 25px rgba(0, 35, 65, .12);
}

.logo {
    padding: 8px 8px 24px;
    margin-bottom: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,.13);
    color: #fff;
    font-size: 19px;
    font-weight: 800;
    line-height: 1.35;
    letter-spacing: .3px;
}

.nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.nav a {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px 13px;
    border-radius: 10px;
    color: rgba(255,255,255,.82);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: .2s ease;
}

.nav a:hover {
    background: rgba(255,255,255,.11);
    color: #fff;
    transform: translateX(2px);
}

.nav a.active {
    background: linear-gradient(90deg, #0877c9, #0b6db2);
    color: #fff;
    box-shadow: 0 6px 15px rgba(0,0,0,.12);
}

.nav a.logout {
    margin-top: 20px;
    background: rgba(220, 53, 69, .88);
    color: #fff;
}

.nav a.logout:hover {
    background: #c92f40;
}

/* =====================================================
   MAIN
===================================================== */

.main {
    margin-left: 245px;
    min-height: 100vh;
    padding: 30px 34px 50px;
}

/* =====================================================
   PAGE HERO
===================================================== */

.schedule-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    padding: 28px 30px;
    margin-bottom: 22px;
    border: 1px solid var(--line);
    border-radius: 20px;
    background:
        linear-gradient(135deg, #ffffff 0%, #f8fbff 65%, #edf7ff 100%);
    box-shadow: var(--shadow);
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 17px;
    min-width: 0;
}

.hero-icon {
    flex: 0 0 58px;
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: linear-gradient(135deg, #e8f4ff, #d8edff);
    font-size: 29px;
}

.hero-title {
    margin: 0 0 5px;
    color: var(--navy);
    font-size: 27px;
    font-weight: 800;
    letter-spacing: -.5px;
}

.hero-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.55;
}

.hero-date {
    flex: 0 0 auto;
    padding: 11px 15px;
    border: 1px solid #dbe8f3;
    border-radius: 12px;
    background: #fff;
    color: #527089;
    font-size: 12px;
    font-weight: 700;
}

/* =====================================================
   STATISTICS
===================================================== */

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 20px;
    border: 1px solid var(--line);
    border-radius: 17px;
    background: var(--card);
    box-shadow: var(--shadow);
}

.stat-card::after {
    content: "";
    position: absolute;
    right: -30px;
    top: -30px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(8,119,201,.07);
}

.stat-icon {
    position: relative;
    z-index: 1;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 13px;
    border-radius: 12px;
    background: var(--blue-light);
    font-size: 20px;
}

.stat-number {
    position: relative;
    z-index: 1;
    color: var(--navy);
    font-size: 29px;
    font-weight: 800;
    line-height: 1;
}

.stat-title {
    position: relative;
    z-index: 1;
    margin-top: 7px;
    color: var(--muted);
    font-size: 12px;
    font-weight: 600;
}

/* =====================================================
   CONTENT SECTION
===================================================== */

.section {
    margin-bottom: 22px;
    padding: 23px;
    border: 1px solid var(--line);
    border-radius: 19px;
    background: #fff;
    box-shadow: var(--shadow);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
}

.section-title-wrap {
    display: flex;
    align-items: center;
    gap: 11px;
}

.section-icon {
    width: 37px;
    height: 37px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--blue-light);
    font-size: 17px;
}

.section-header h2 {
    margin: 0;
    color: var(--navy);
    font-size: 18px;
    font-weight: 800;
}

/* =====================================================
   TODAY / UPCOMING LESSON CARDS
===================================================== */

.today-card {
    position: relative;
    overflow: hidden;
    padding: 20px;
    margin-bottom: 13px;
    border: 1px solid #e3eaf1;
    border-radius: 16px;
    background: linear-gradient(135deg, #fff, #fbfdff);
    transition: .2s ease;
}

.today-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, #0877c9, #39a9e8);
}

.today-card:hover {
    transform: translateY(-2px);
    border-color: #cbdceb;
    box-shadow: 0 10px 25px rgba(17,45,72,.08);
}

.lesson-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
}

.subject-title {
    color: var(--navy);
    font-size: 18px;
    font-weight: 800;
    line-height: 1.3;
}

.lesson-reference {
    margin-top: 5px;
    color: #8b98a6;
    font-size: 11px;
}

.lesson-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 11px;
    margin-top: 18px;
}

.lesson-info {
    min-width: 0;
    padding: 12px;
    border: 1px solid #e9eef4;
    border-radius: 11px;
    background: #f7f9fc;
}

.lesson-label {
    display: block;
    margin-bottom: 5px;
    color: #8a97a5;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}

.lesson-value {
    color: #2b3c50;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
    word-break: break-word;
}

.teacher-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

.teacher-avatar {
    flex: 0 0 35px;
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #eaf4ff;
    font-size: 16px;
}

.teacher-name {
    color: var(--navy);
    font-size: 12px;
    font-weight: 700;
}

/* =====================================================
   STATUS
===================================================== */

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 25px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.badge.scheduled {
    background: var(--blue-light);
    color: #0867b8;
}

.badge.completed,
.badge.paid {
    background: var(--green-bg);
    color: var(--green);
}

.badge.cancelled {
    background: var(--red-bg);
    color: var(--red);
}

.badge.pending {
    background: var(--orange-bg);
    color: var(--orange);
}

/* =====================================================
   ACTIONS
===================================================== */

.lesson-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-top: 17px;
}

.zoom-button,
.classroom-button,
.book-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 39px;
    padding: 9px 14px;
    border: 0;
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s ease;
}

.zoom-button {
    background: #eaf4ff;
    color: #0867b8;
}

.zoom-button:hover {
    background: #dceeff;
    transform: translateY(-1px);
}

.classroom-button {
    background: linear-gradient(135deg, #063b69, #0877c9);
    color: #fff;
    box-shadow: 0 5px 13px rgba(8,119,201,.18);
}

.classroom-button:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(8,119,201,.25);
}

.no-zoom {
    color: #9aa5b1;
    font-size: 11px;
    font-weight: 600;
}

.book-button {
    background: #063b69;
    color: #fff;
}

.book-button:hover {
    background: #0877c9;
    color: #fff;
    transform: translateY(-1px);
}

/* =====================================================
   TABLE
===================================================== */

.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e4ebf2;
    border-radius: 13px;
}

table {
    width: 100%;
    min-width: 880px;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    padding: 13px 14px;
    background: #f5f8fb;
    border-bottom: 1px solid #e2e9f0;
    color: #627184;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 14px;
    border-bottom: 1px solid #edf1f5;
    color: #566576;
    font-size: 12px;
    vertical-align: middle;
}

tbody tr:hover td {
    background: #fafcff;
}

tbody tr:last-child td {
    border-bottom: 0;
}

.subject-cell {
    color: var(--navy);
    font-weight: 800;
}

.not-scheduled {
    color: #9ba6b1;
    font-style: italic;
}

/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {
    padding: 50px 20px;
    text-align: center;
}

.empty-icon {
    width: 62px;
    height: 62px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    border-radius: 18px;
    background: var(--blue-light);
    font-size: 29px;
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

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {
    .lesson-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .schedule-hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .hero-date {
        width: 100%;
    }
}

@media (max-width: 800px) {
    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: auto;
    }

    .main {
        margin-left: 0;
        padding: 18px 14px 35px;
    }

    .nav {
        display: grid;
        grid-template-columns: repeat(2, minmax(0,1fr));
    }

    .nav a.logout {
        margin-top: 0;
    }

    .schedule-hero {
        padding: 21px;
        border-radius: 16px;
    }

    .hero-title {
        font-size: 23px;
    }

    .section {
        padding: 18px;
        border-radius: 16px;
    }

    .lesson-top {
        flex-direction: column;
    }
}

@media (max-width: 560px) {
    .nav {
        grid-template-columns: 1fr;
    }

    .hero-left {
        align-items: flex-start;
    }

    .hero-icon {
        flex-basis: 48px;
        width: 48px;
        height: 48px;
        font-size: 23px;
    }

    .hero-title {
        font-size: 21px;
    }

    .lesson-grid {
        grid-template-columns: 1fr;
    }

    .lesson-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .zoom-button,
    .classroom-button,
    .book-button {
        width: 100%;
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

        <a href="dashboard.php">
            🏠 Dashboard
        </a>

        <a
            href="schedule.php"
            class="active"
        >
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

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="schedule-hero">

        <div class="hero-left">

            <div class="hero-icon">📅</div>

            <div>

                <h1 class="hero-title">
                    Lesson Schedule
                </h1>

                <p class="hero-subtitle">
                    View your assigned lessons, teachers and
                    join your virtual classroom.
                </p>

            </div>

        </div>

        <div class="hero-date">
            📚 My Learning Schedule
        </div>

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

            <div class="section-title-wrap">
                <div class="section-icon">📌</div>
                <h2>Today's Lessons</h2>
            </div>

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

            <div class="section-title-wrap">
                <div class="section-icon">🗓️</div>
                <h2>Upcoming Lessons</h2>
            </div>

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

            <div class="section-title-wrap">
                <div class="section-icon">📚</div>
                <h2>All Assigned Lessons</h2>
            </div>


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
