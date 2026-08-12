<?php
session_start();

require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT DASHBOARD - PHASE 9
|--------------------------------------------------------------------------
| Corrected version
|
| IMPORTANT:
| This version does NOT use:
|
|     live_classes.class_time
|
| Instead, it uses:
|
|     bookings.lesson_date
|     bookings.lesson_time
|
| Teacher information comes from:
|
|     teachers.teacher_id
|
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
| GET STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

$student_id = $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? 'Student';


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
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


function formatDateValue($date)
{
    if (empty($date)) {
        return "Not scheduled";
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return "Not scheduled";
    }

    return date(
        "d M Y",
        $timestamp
    );
}


function formatTimeValue($time)
{
    if (empty($time)) {
        return "Not set";
    }

    $timestamp = strtotime($time);

    if ($timestamp === false) {
        return "Not set";
    }

    return date(
        "h:i A",
        $timestamp
    );
}


function lessonStatusClass($status)
{
    $status = strtolower(
        trim($status ?? 'scheduled')
    );

    if ($status === 'completed') {
        return 'completed';
    }

    if ($status === 'cancelled') {
        return 'cancelled';
    }

    return 'scheduled';
}


function lessonStatusText($status)
{
    $status = strtolower(
        trim($status ?? 'scheduled')
    );

    if ($status === 'completed') {
        return 'Completed';
    }

    if ($status === 'cancelled') {
        return 'Cancelled';
    }

    return 'Scheduled';
}


function paymentStatusClass($status)
{
    $status = strtolower(
        trim($status ?? '')
    );

    if (
        $status === 'paid' ||
        $status === 'success'
    ) {
        return 'paid';
    }

    return 'pending';
}


function paymentStatusText($status)
{
    $status = strtolower(
        trim($status ?? '')
    );

    if (
        $status === 'paid' ||
        $status === 'success'
    ) {
        return 'PAID';
    }

    if ($status === '') {
        return 'PENDING';
    }

    return strtoupper($status);
}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$totalLessons = 0;
$totalToday = 0;
$totalUpcoming = 0;
$totalPaid = 0;

$todayLessons = [];
$upcomingLessons = [];
$recentLessons = [];

$errorMessage = "";


/*
|--------------------------------------------------------------------------
| DATABASE CHECK
|--------------------------------------------------------------------------
*/

if (!isset($pdo)) {

    $errorMessage =
        "Database connection was not found. Please check config/db.php.";

} else {

    try {

        /*
        |--------------------------------------------------------------------------
        | TOTAL ASSIGNED LESSONS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookings
            WHERE student_id = ?
        ");

        $stmt->execute([
            $student_id
        ]);

        $totalLessons =
            (int)$stmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | TODAY'S LESSONS
        |--------------------------------------------------------------------------
        */

        $todayStmt = $pdo->prepare("
            SELECT
                b.id,
                b.booking_reference,
                b.student_name,
                b.email,
                b.phone,
                b.curriculum,
                b.class_year,
                b.subjects,
                b.lesson_date,
                b.lesson_time,
                b.lesson_status,
                b.payment_status,

                t.teacher_id AS assigned_teacher_id,
                t.teacher_name AS assigned_teacher_name,
                t.email AS teacher_email,
                t.phone AS teacher_phone,
                t.zoom_link AS teacher_zoom_link

            FROM bookings b

            LEFT JOIN teachers t
                ON b.teacher_id = t.teacher_id

            WHERE
                b.student_id = ?
                AND b.lesson_date = CURDATE()

            ORDER BY
                b.lesson_time ASC
        ");

        $todayStmt->execute([
            $student_id
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
                b.student_name,
                b.email,
                b.phone,
                b.curriculum,
                b.class_year,
                b.subjects,
                b.lesson_date,
                b.lesson_time,
                b.lesson_status,
                b.payment_status,

                t.teacher_id AS assigned_teacher_id,
                t.teacher_name AS assigned_teacher_name,
                t.email AS teacher_email,
                t.phone AS teacher_phone,
                t.zoom_link AS teacher_zoom_link

            FROM bookings b

            LEFT JOIN teachers t
                ON b.teacher_id = t.teacher_id

            WHERE
                b.student_id = ?
                AND b.lesson_date > CURDATE()

            ORDER BY
                b.lesson_date ASC,
                b.lesson_time ASC

            LIMIT 8
        ");

        $upcomingStmt->execute([
            $student_id
        ]);

        $upcomingLessons =
            $upcomingStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        $totalUpcoming =
            count($upcomingLessons);


        /*
        |--------------------------------------------------------------------------
        | PAID BOOKINGS
        |--------------------------------------------------------------------------
        */

        $paidStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE
                student_id = ?
                AND (
                    LOWER(payment_status) = 'paid'
                    OR
                    LOWER(payment_status) = 'success'
                )
        ");

        $paidStmt->execute([
            $student_id
        ]);

        $totalPaid =
            (int)$paidStmt->fetchColumn();


        /*
        |--------------------------------------------------------------------------
        | RECENT LESSONS
        |--------------------------------------------------------------------------
        */

        $recentStmt = $pdo->prepare("
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

                t.teacher_id AS assigned_teacher_id,
                t.teacher_name AS assigned_teacher_name,
                t.email AS teacher_email,
                t.phone AS teacher_phone,
                t.zoom_link AS teacher_zoom_link

            FROM bookings b

            LEFT JOIN teachers t
                ON b.teacher_id = t.teacher_id

            WHERE
                b.student_id = ?

            ORDER BY
                b.lesson_date DESC,
                b.lesson_time DESC

            LIMIT 6
        ");

        $recentStmt->execute([
            $student_id
        ]);

        $recentLessons =
            $recentStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | DATABASE ERROR
        |--------------------------------------------------------------------------
        */

        $errorMessage =
            "Unable to load dashboard: "
            . $e->getMessage();
    }
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
    Student Dashboard |
    NISEL ONLINE EDUCATION
</title>


<style>

/* =========================================================
   GENERAL
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
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


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 245px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

    overflow-y: auto;

    z-index: 1000;
}


.logo {

    text-align: center;

    font-size: 21px;

    font-weight: bold;

    line-height: 1.4;

    padding: 10px 5px 30px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);
}


.menu {

    margin-top: 25px;
}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 14px 16px;

    margin-bottom: 7px;

    border-radius: 8px;

    font-size: 14px;

    transition:
        .2s ease;
}


.menu a:hover {

    background:
        rgba(255,255,255,.12);

    transform:
        translateX(3px);
}


.menu a.active {

    background: white;

    color: #003366;

    font-weight: bold;
}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 245px;

    min-height: 100vh;

    padding: 25px;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    background: white;

    border-radius: 12px;

    padding: 18px 22px;

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.06);
}


.topbar h2 {

    margin: 0;

    color: #003366;

    font-size: 22px;
}


.student-name {

    color: #666;

    font-size: 14px;
}


.student-name strong {

    color: #003366;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    background:
        linear-gradient(
            135deg,
            #003366,
            #0055a5
        );

    color: white;

    border-radius: 14px;

    padding: 28px;

    margin-bottom: 25px;

    box-shadow:
        0 8px 25px
        rgba(0,51,102,.18);
}


.page-header h2 {

    margin:
        0 0 8px;

    font-size: 25px;
}


.page-header p {

    margin: 0;

    color: #dbeafe;

    line-height: 1.6;
}


/* =========================================================
   ERROR MESSAGE
========================================================= */

.error-message {

    background: #fff0f0;

    color: #a40000;

    border-left:
        5px solid
        #d60000;

    padding: 15px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    line-height: 1.5;

    overflow-x: auto;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}


.stat-card {

    background: white;

    border-radius: 12px;

    padding: 22px;

    box-shadow:
        0 4px 18px
        rgba(0,0,0,.06);

    border:
        1px solid
        #e5eaf0;

    transition:
        .2s ease;
}


.stat-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 7px 22px
        rgba(0,0,0,.09);
}


.stat-icon {

    width: 45px;

    height: 45px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eaf3fb;

    font-size: 21px;

    margin-bottom: 12px;
}


.stat-card h3 {

    margin: 0;

    color: #003366;

    font-size: 29px;
}


.stat-card p {

    margin:
        6px 0 0;

    color: #777;

    font-size: 13px;
}


/* =========================================================
   CONTENT GRID
========================================================= */

.dashboard-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 22px;

    margin-bottom: 25px;
}


.card {

    background: white;

    border-radius: 13px;

    box-shadow:
        0 4px 18px
        rgba(0,0,0,.06);

    border:
        1px solid
        #e5eaf0;

    overflow: hidden;
}


.card-header {

    padding: 18px 20px;

    border-bottom:
        1px solid
        #edf0f3;

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 10px;
}


.card-header h3 {

    margin: 0;

    color: #003366;

    font-size: 17px;
}


.card-body {

    padding: 20px;
}


/* =========================================================
   TODAY'S LESSON
========================================================= */

.today-list {

    display: flex;

    flex-direction: column;

    gap: 14px;
}


.lesson-card {

    border:
        1px solid
        #e2e7ed;

    border-radius: 10px;

    padding: 15px;

    background: #fbfcfd;

    transition: .2s ease;
}


.lesson-card:hover {

    border-color:
        #b9cee2;

    background: #f7fbff;
}


.lesson-top {

    display: flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    gap: 15px;

    margin-bottom: 10px;
}


.subject-name {

    color: #003366;

    font-weight: bold;

    font-size: 16px;
}


.lesson-time {

    font-size: 13px;

    color: #555;

    margin-top: 5px;
}


.lesson-details {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 8px;

    margin-top: 10px;
}


.detail {

    font-size: 12px;

    color: #666;
}


.detail strong {

    display: block;

    color: #444;

    margin-bottom: 2px;
}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    white-space: nowrap;
}


.badge.scheduled {

    background: #e8f3ff;

    color: #0060a8;
}


.badge.completed {

    background: #e8f8ee;

    color: #18753c;
}


.badge.cancelled {

    background: #ffeaea;

    color: #b40000;
}


.badge.paid {

    background: #e7f8ed;

    color: #137333;
}


.badge.pending {

    background: #fff4dc;

    color: #996000;
}


/* =========================================================
   ZOOM BUTTON
========================================================= */

.zoom-button {

    display: inline-block;

    margin-top: 12px;

    padding: 9px 13px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;

    transition: .2s ease;
}


.zoom-button:hover {

    background: #0055a5;

    transform:
        translateY(-1px);
}


.no-zoom {

    display: inline-block;

    margin-top: 12px;

    font-size: 11px;

    color: #999;
}


/* =========================================================
   UPCOMING LESSONS
========================================================= */

.upcoming-list {

    display: flex;

    flex-direction: column;

    gap: 10px;
}


.upcoming-item {

    display: flex;

    align-items: center;

    gap: 13px;

    padding: 12px;

    border-radius: 9px;

    background: #f7f9fb;

    border:
        1px solid
        #edf0f3;
}


.date-box {

    min-width: 58px;

    text-align: center;

    border-radius: 8px;

    background: #003366;

    color: white;

    padding: 8px 5px;
}


.date-box .day {

    display: block;

    font-size: 20px;

    font-weight: bold;
}


.date-box .month {

    display: block;

    font-size: 10px;

    text-transform: uppercase;
}


.upcoming-info {

    flex: 1;

    min-width: 0;
}


.upcoming-info strong {

    display: block;

    color: #003366;

    font-size: 14px;

    margin-bottom: 4px;
}


.upcoming-info span {

    display: block;

    color: #777;

    font-size: 11px;

    margin-top: 3px;
}


/* =========================================================
   RECENT LESSONS
========================================================= */

.full-card {

    margin-bottom: 25px;
}


.table-container {

    width: 100%;

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse:
        collapse;
}


th {

    text-align: left;

    background: #f5f8fb;

    color: #003366;

    font-size: 12px;

    padding: 13px;

    white-space: nowrap;
}


td {

    padding: 13px;

    border-top:
        1px solid
        #edf0f3;

    font-size: 12px;

    color: #555;

    vertical-align: middle;
}


td strong {

    color: #003366;
}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-actions {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;
}


.quick-action {

    text-decoration: none;

    background: #f5f9fd;

    border:
        1px solid
        #dce8f2;

    border-radius: 10px;

    padding: 18px;

    color: #003366;

    transition: .2s ease;
}


.quick-action:hover {

    background: #003366;

    color: white;

    transform:
        translateY(-2px);
}


.quick-action-icon {

    font-size: 25px;

    margin-bottom: 8px;
}


.quick-action strong {

    display: block;

    font-size: 14px;

    margin-bottom: 5px;
}


.quick-action span {

    font-size: 11px;

    opacity: .75;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.no-data {

    padding: 30px 15px;

    text-align: center;

    color: #777;
}


.no-data-icon {

    font-size: 40px;

    margin-bottom: 8px;
}


.no-data p {

    margin: 5px 0;

    font-size: 13px;
}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align: center;

    padding: 25px 10px;

    color: #888;

    font-size: 11px;
}


/* =========================================================
   MOBILE MENU BUTTON
========================================================= */

.mobile-menu {

    display: none;

    position: fixed;

    top: 15px;

    left: 15px;

    width: 45px;

    height: 45px;

    border: none;

    border-radius: 8px;

    background: #003366;

    color: white;

    font-size: 21px;

    z-index: 2000;

    cursor: pointer;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .dashboard-grid {

        grid-template-columns:
            1fr;
    }
}


@media (max-width: 800px) {

    .mobile-menu {

        display: block;
    }

    .sidebar {

        transform:
            translateX(-100%);

        transition:
            transform .25s ease;
    }

    .sidebar.open {

        transform:
            translateX(0);
    }

    .main {

        margin-left: 0;

        padding:
            75px 15px 20px;
    }

    .topbar {

        flex-direction:
            column;

        align-items:
            flex-start;
    }

    .quick-actions {

        grid-template-columns:
            1fr;
    }
}


@media (max-width: 550px) {

    .stats {

        grid-template-columns:
            1fr 1fr;

        gap: 10px;
    }

    .stat-card {

        padding: 15px;
    }

    .stat-card h3 {

        font-size: 23px;
    }

    .page-header {

        padding: 20px;
    }

    .page-header h2 {

        font-size: 21px;
    }

    .lesson-details {

        grid-template-columns:
            1fr;
    }

    .lesson-top {

        flex-direction:
            column;
    }

    .upcoming-item {

        align-items:
            flex-start;
    }

    th,
    td {

        padding: 10px;
    }
}

</style>

</head>


<body>


<!-- =========================================================
     MOBILE MENU
========================================================= -->

<button
    class="mobile-menu"
    id="mobileMenu"
    type="button"
    aria-label="Open menu"
>
    ☰
</button>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<div
    class="sidebar"
    id="sidebar"
>

    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">

        <a
            href="dashboard_phase9.php"
            class="active"
        >
            🏠 Dashboard
        </a>


        <a href="schedule.php">
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


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h2>
            Student Dashboard
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
            👋 Welcome Back,
            <?= h($student_name) ?>
        </h2>

        <p>
            Manage your lessons, view your schedule,
            check your teachers and join your online
            classes from your NISEL student portal.
        </p>

    </div>


    <!-- ERROR -->

    <?php if ($errorMessage !== ""): ?>

        <div class="error-message">

            <strong>
                Dashboard Error:
            </strong>

            <?= h($errorMessage) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="stats">


        <!-- TOTAL LESSONS -->

        <div class="stat-card">

            <div class="stat-icon">
                📚
            </div>

            <h3>
                <?= $totalLessons ?>
            </h3>

            <p>
                Total Assigned Lessons
            </p>

        </div>


        <!-- TODAY -->

        <div class="stat-card">

            <div class="stat-icon">
                📅
            </div>

            <h3>
                <?= $totalToday ?>
            </h3>

            <p>
                Lessons Today
            </p>

        </div>


        <!-- UPCOMING -->

        <div class="stat-card">

            <div class="stat-icon">
                ⏰
            </div>

            <h3>
                <?= $totalUpcoming ?>
            </h3>

            <p>
                Upcoming Lessons
            </p>

        </div>


        <!-- PAYMENTS -->

        <div class="stat-card">

            <div class="stat-icon">
                💳
            </div>

            <h3>
                <?= $totalPaid ?>
            </h3>

            <p>
                Paid Bookings
            </p>

        </div>


    </div>


    <!-- =====================================================
         TODAY + UPCOMING
    ====================================================== -->

    <div class="dashboard-grid">


        <!-- =================================================
             TODAY'S LESSONS
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <h3>
                    📅 Today's Lessons
                </h3>

                <span class="badge scheduled">
                    <?= $totalToday ?>
                </span>

            </div>


            <div class="card-body">


                <?php if (!empty($todayLessons)): ?>


                    <div class="today-list">


                        <?php foreach (
                            $todayLessons
                            as $lesson
                        ): ?>


                            <div class="lesson-card">


                                <div class="lesson-top">


                                    <div>

                                        <div class="subject-name">

                                            <?= h(
                                                $lesson['subjects']
                                                ?? 'Subject'
                                            ) ?>

                                        </div>


                                        <div class="lesson-time">

                                            🕐

                                            <?= h(
                                                formatTimeValue(
                                                    $lesson['lesson_time']
                                                    ?? ''
                                                )
                                            ) ?>

                                        </div>

                                    </div>


                                    <span
                                        class="badge
                                        <?= h(
                                            lessonStatusClass(
                                                $lesson['lesson_status']
                                                ?? 'scheduled'
                                            )
                                        ) ?>"
                                    >

                                        <?= h(
                                            lessonStatusText(
                                                $lesson['lesson_status']
                                                ?? 'scheduled'
                                            )
                                        ) ?>

                                    </span>


                                </div>


                                <div class="lesson-details">


                                    <div class="detail">

                                        <strong>
                                            Teacher
                                        </strong>

                                        <?= h(
                                            $lesson[
                                                'assigned_teacher_name'
                                            ]
                                            ?? 'Not assigned'
                                        ) ?>

                                    </div>


                                    <div class="detail">

                                        <strong>
                                            Class
                                        </strong>

                                        <?= h(
                                            $lesson['class_year']
                                            ?? 'N/A'
                                        ) ?>

                                    </div>


                                    <div class="detail">

                                        <strong>
                                            Curriculum
                                        </strong>

                                        <?= h(
                                            $lesson['curriculum']
                                            ?? 'N/A'
                                        ) ?>

                                    </div>


                                    <div class="detail">

                                        <strong>
                                            Payment
                                        </strong>

                                        <span
                                            class="badge
                                            <?= h(
                                                paymentStatusClass(
                                                    $lesson[
                                                        'payment_status'
                                                    ]
                                                    ?? ''
                                                )
                                            ) ?>"
                                        >

                                            <?= h(
                                                paymentStatusText(
                                                    $lesson[
                                                        'payment_status'
                                                    ]
                                                    ?? ''
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                </div>


                                <?php

                                $zoomLink =
                                    $lesson[
                                        'teacher_zoom_link'
                                    ]
                                    ?? '';

                                ?>


                                <?php if (
                                    !empty($zoomLink)
                                ): ?>

                                    <a
                                        href="<?= h(
                                            $zoomLink
                                        ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="zoom-button"
                                    >
                                        🎥 Join Zoom Class
                                    </a>

                                <?php else: ?>

                                    <span
                                        class="no-zoom"
                                    >
                                        Zoom link not available
                                    </span>

                                <?php endif; ?>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="no-data">

                        <div class="no-data-icon">
                            📅
                        </div>

                        <p>
                            You have no lessons scheduled
                            for today.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>


        <!-- =================================================
             UPCOMING LESSONS
        ================================================== -->

        <div class="card">

            <div class="card-header">

                <h3>
                    ⏰ Upcoming Lessons
                </h3>

                <span class="badge scheduled">
                    <?= $totalUpcoming ?>
                </span>

            </div>


            <div class="card-body">


                <?php if (!empty($upcomingLessons)): ?>


                    <div class="upcoming-list">


                        <?php foreach (
                            $upcomingLessons
                            as $lesson
                        ): ?>


                            <?php

                            $lessonDate =
                                $lesson[
                                    'lesson_date'
                                ]
                                ?? '';

                            $day =
                                !empty($lessonDate)
                                ? date(
                                    'd',
                                    strtotime(
                                        $lessonDate
                                    )
                                )
                                : '--';

                            $month =
                                !empty($lessonDate)
                                ? date(
                                    'M',
                                    strtotime(
                                        $lessonDate
                                    )
                                )
                                : '---';

                            ?>


                            <div
                                class="upcoming-item"
                            >


                                <div
                                    class="date-box"
                                >

                                    <span
                                        class="day"
                                    >
                                        <?= h($day) ?>
                                    </span>

                                    <span
                                        class="month"
                                    >
                                        <?= h($month) ?>
                                    </span>

                                </div>


                                <div
                                    class="upcoming-info"
                                >

                                    <strong>

                                        <?= h(
                                            $lesson[
                                                'subjects'
                                            ]
                                            ?? 'Subject'
                                        ) ?>

                                    </strong>


                                    <span>

                                        🕐

                                        <?= h(
                                            formatTimeValue(
                                                $lesson[
                                                    'lesson_time'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>

                                    </span>


                                    <span>

                                        👨‍🏫

                                        <?= h(
                                            $lesson[
                                                'assigned_teacher_name'
                                            ]
                                            ?? 'Teacher not assigned'
                                        ) ?>

                                    </span>


                                    <span>

                                        <?= h(
                                            $lesson[
                                                'curriculum'
                                            ]
                                            ?? ''
                                        ) ?>

                                        -

                                        <?= h(
                                            $lesson[
                                                'class_year'
                                            ]
                                            ?? ''
                                        ) ?>

                                    </span>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="no-data">

                        <div class="no-data-icon">
                            ⏰
                        </div>

                        <p>
                            No upcoming lessons found.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>


    </div>


    <!-- =====================================================
         RECENT LESSONS
    ====================================================== -->

    <div class="card full-card">


        <div class="card-header">

            <h3>
                📚 Recent Lessons
            </h3>

        </div>


        <div class="table-container">


            <?php if (!empty($recentLessons)): ?>


                <table>

                    <thead>

                        <tr>

                            <th>
                                Reference
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
                                Teacher
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Payment
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $recentLessons
                            as $lesson
                        ): ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= h(
                                            $lesson[
                                                'booking_reference'
                                            ]
                                            ?? 'N/A'
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?= h(
                                        $lesson[
                                            'subjects'
                                        ]
                                        ?? 'N/A'
                                    ) ?>

                                </td>


                                <td>

                                    <?= h(
                                        $lesson[
                                            'curriculum'
                                        ]
                                        ?? 'N/A'
                                    ) ?>

                                </td>


                                <td>

                                    <?= h(
                                        $lesson[
                                            'class_year'
                                        ]
                                        ?? 'N/A'
                                    ) ?>

                                </td>


                                <td>

                                    <?= h(
                                        formatDateValue(
                                            $lesson[
                                                'lesson_date'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= h(
                                        formatTimeValue(
                                            $lesson[
                                                'lesson_time'
                                            ]
                                            ?? ''
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= h(
                                        $lesson[
                                            'assigned_teacher_name'
                                        ]
                                        ?? 'Not assigned'
                                    ) ?>

                                </td>


                                <td>

                                    <span
                                        class="badge
                                        <?= h(
                                            lessonStatusClass(
                                                $lesson[
                                                    'lesson_status'
                                                ]
                                                ?? 'scheduled'
                                            )
                                        ) ?>"
                                    >

                                        <?= h(
                                            lessonStatusText(
                                                $lesson[
                                                    'lesson_status'
                                                ]
                                                ?? 'scheduled'
                                            )
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <span
                                        class="badge
                                        <?= h(
                                            paymentStatusClass(
                                                $lesson[
                                                    'payment_status'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                    >

                                        <?= h(
                                            paymentStatusText(
                                                $lesson[
                                                    'payment_status'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>

                                    </span>

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

                    <p>
                        No lesson records found.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </div>


    <!-- =====================================================
         QUICK ACTIONS
    ====================================================== -->

    <div class="card full-card">


        <div class="card-header">

            <h3>
                ⚡ Quick Actions
            </h3>

        </div>


        <div class="card-body">


            <div class="quick-actions">


                <a
                    href="schedule.php"
                    class="quick-action"
                >

                    <div
                        class="quick-action-icon"
                    >
                        📅
                    </div>

                    <strong>
                        View My Schedule
                    </strong>

                    <span>
                        See all your assigned lessons
                    </span>

                </a>


                <a
                    href="subjects.php"
                    class="quick-action"
                >

                    <div
                        class="quick-action-icon"
                    >
                        📚
                    </div>

                    <strong>
                        My Subjects
                    </strong>

                    <span>
                        View your registered subjects
                    </span>

                </a>


                <a
                    href="profile.php"
                    class="quick-action"
                >

                    <div
                        class="quick-action-icon"
                    >
                        👤
                    </div>

                    <strong>
                        My Profile
                    </strong>

                    <span>
                        View and update your account
                    </span>

                </a>


            </div>


        </div>


    </div>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="footer">

        © <?= date('Y') ?>

        NISEL ONLINE EDUCATION.

        All Rights Reserved.

    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const mobileMenu =
    document.getElementById(
        'mobileMenu'
    );

const sidebar =
    document.getElementById(
        'sidebar'
    );


mobileMenu.addEventListener(
    'click',
    function () {

        sidebar.classList.toggle(
            'open'
        );

    }
);


/*
|--------------------------------------------------------------------------
| CLOSE MOBILE SIDEBAR WHEN LINK IS CLICKED
|--------------------------------------------------------------------------
*/

const menuLinks =
    sidebar.querySelectorAll(
        'a'
    );


menuLinks.forEach(
    function (link) {

        link.addEventListener(
            'click',
            function () {

                sidebar.classList.remove(
                    'open'
                );

            }
        );

    }
);

</script>


</body>

</html>
