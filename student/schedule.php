<?php
/* NISEL SCHEDULE PDO FIX - uses $pdo consistently */

session_start();

require "../config/db.php";


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


$student_id =
    $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? 'Student';


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT SCHEDULE
|--------------------------------------------------------------------------
*/


$message = "";

$message_type = "";

/*
|--------------------------------------------------------------------------
| GET STUDENT BOOKINGS
|--------------------------------------------------------------------------
|
| The teacher's Zoom link is retrieved from the teachers table.
|
| bookings.teacher_id is matched against teachers.teacher_id.
|
*/

$scheduleStmt = $pdo->prepare("
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

    WHERE b.student_id = ?

    ORDER BY
        b.lesson_date ASC,
        b.lesson_time ASC
");

$scheduleStmt->execute([
    $student_id
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

$totalLessons = count($schedules);


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

    ORDER BY b.lesson_time ASC
");

$todayStmt->execute([
    $student_id
]);

$todayLessons = $todayStmt->fetchAll(PDO::FETCH_ASSOC);

$totalToday = count($todayLessons);


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

$upcomingLessons = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);


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

    return date(
        "d M Y",
        strtotime($date)
    );
}


function formatTimeValue($time)
{
    if (empty($time)) {
        return "Not set";
    }

    return date(
        "h:i A",
        strtotime($time)
    );
}


function lessonStatusBadge($status)
{
    $status = strtolower(
        trim($status ?? 'scheduled')
    );

    if ($status === "completed") {

        return '<span class="badge completed">
                    Completed
                </span>';

    }

    if ($status === "cancelled") {

        return '<span class="badge cancelled">
                    Cancelled
                </span>';

    }

    return '<span class="badge scheduled">
                Scheduled
            </span>';
}


function paymentStatusBadge($status)
{
    $status = strtolower(
        trim($status ?? '')
    );

    if (
        $status === "paid" ||
        $status === "success"
    ) {

        return '<span class="badge paid">
                    PAID
                </span>';

    }

    if (empty($status)) {

        return '<span class="badge pending">
                    PENDING
                </span>';

    }

    return '<span class="badge pending">'
        . h(strtoupper($status))
        . '</span>';
}


function classroomButton($bookingId)
{
    $bookingId = (int) $bookingId;

    if ($bookingId <= 0) {
        return '';
    }

    return '
        <a
            href="classroom.php?id=' . $bookingId . '"
            class="classroom-button"
        >
            🎥 Live Classroom
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
/* =========================================================
   NISEL ONLINE EDUCATION
   STUDENT SCHEDULE - REBUILT MODERN LAYOUT
   The sidebar appearance is intentionally preserved.
========================================================= */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
    background: #eef3f8;
    color: #243447;
}

/* =========================================================
   PAGE LAYOUT
========================================================= */

.layout {
    display: flex;
    width: 100%;
    min-height: 100vh;
    align-items: stretch;
}

/* =========================================================
   SIDEBAR - PRESERVED
========================================================= */

.sidebar {
    position: sticky;
    top: 0;
    left: 0;
    flex: 0 0 240px;
    width: 240px;
    height: 100vh;
    padding: 25px 15px;
    overflow-y: auto;
    background: #003366;
    color: white;
    z-index: 1000;
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

/* =========================================================
   MAIN
========================================================= */

.main {
    flex: 1 1 auto;
    min-width: 0;
    width: auto;
    margin: 0;
    padding: 28px 32px 45px;
}

/* =========================================================
   TOP WELCOME BAR
========================================================= */

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 24px;
    margin-bottom: 20px;
    border: 1px solid #e1e8ef;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 6px 22px rgba(0, 45, 80, .06);
}

.topbar h2 {
    margin: 0;
    color: #003366;
    font-size: 21px;
    font-weight: 800;
}

.student-name {
    color: #718096;
    font-size: 13px;
}

.student-name strong {
    color: #003366;
}

/* =========================================================
   HERO
========================================================= */

.page-header {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    min-height: 145px;
    padding: 28px 30px;
    margin-bottom: 20px;
    border-radius: 18px;
    background: linear-gradient(135deg, #003366 0%, #075d9f 72%, #0877c9 100%);
    color: #fff;
    box-shadow: 0 12px 30px rgba(0, 51, 102, .18);
}

.page-header::after {
    content: "";
    position: absolute;
    right: -55px;
    top: -75px;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
}

.page-header::before {
    content: "";
    position: absolute;
    right: 85px;
    bottom: -85px;
    width: 145px;
    height: 145px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
}

.page-header h2,
.page-header p {
    position: relative;
    z-index: 1;
}

.page-header h2 {
    margin: 0 0 9px;
    color: #fff;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.4px;
}

.page-header p {
    max-width: 650px;
    margin: 0;
    color: rgba(255,255,255,.82);
    font-size: 13px;
    line-height: 1.6;
}

.page-header::after {
    pointer-events: none;
}

/* =========================================================
   STATISTICS
========================================================= */

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.stat-card {
    position: relative;
    overflow: hidden;
    padding: 20px;
    border: 1px solid #e2e9f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 7px 22px rgba(0, 45, 80, .055);
    transition: transform .2s ease, box-shadow .2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0, 45, 80, .09);
}

.stat-card::after {
    content: "";
    position: absolute;
    right: -35px;
    top: -35px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(8,119,201,.06);
}

.stat-icon {
    width: 41px;
    height: 41px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border-radius: 11px;
    background: #eaf5ff;
    font-size: 19px;
}

.stat-card h3 {
    position: relative;
    z-index: 1;
    margin: 0;
    color: #003366;
    font-size: 29px;
    font-weight: 800;
}

.stat-card p {
    position: relative;
    z-index: 1;
    margin: 6px 0 0;
    color: #7a8795;
    font-size: 12px;
    font-weight: 600;
}

/* =========================================================
   SECTION CARDS
========================================================= */

.today,
.table-container {
    padding: 22px;
    margin-bottom: 20px;
    border: 1px solid #e2e9f0;
    border-radius: 17px;
    background: #fff;
    box-shadow: 0 7px 22px rgba(0, 45, 80, .055);
}

.today h3,
.table-container h3 {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0 0 17px;
    color: #003366;
    font-size: 18px;
    font-weight: 800;
}

/* =========================================================
   LESSON TABLES
========================================================= */

.today > div[style*="overflow-x:auto"] {
    border: 1px solid #e4ebf2;
    border-radius: 12px;
}

.today table,
.table-container table {
    width: 100%;
    min-width: 850px;
    border-collapse: separate;
    border-spacing: 0;
}

.today th,
.table-container th {
    padding: 12px 13px;
    background: #f4f7fa;
    color: #647386;
    border-bottom: 1px solid #e1e8ef;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .5px;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.today td,
.table-container td {
    padding: 13px;
    border-bottom: 1px solid #edf1f5;
    color: #536273;
    font-size: 12px;
    vertical-align: middle;
}

.today tbody tr:hover td,
.table-container tbody tr:hover td,
.today tr:hover td,
.table-container tr:hover td {
    background: #fafcff;
}

.today tr:last-child td,
.table-container tr:last-child td {
    border-bottom: 0;
}

.today td strong,
.table-container td strong {
    color: #243b53;
}

/* =========================================================
   TEACHER
========================================================= */

.teacher-info {
    line-height: 1.45;
}

.teacher-name {
    color: #003366;
    font-size: 12px;
    font-weight: 800;
}

.teacher-contact {
    margin-top: 3px;
    color: #8a96a3;
    font-size: 11px;
}

/* =========================================================
   BADGES
========================================================= */

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

.scheduled {
    background: #e7f2ff;
    color: #0867b8;
}

.completed {
    background: #e8f7ed;
    color: #16803d;
}

.cancelled {
    background: #fff0f0;
    color: #c53030;
}

.pending {
    background: #fff5dc;
    color: #9a6b00;
}

.paid {
    background: #e8f7ed;
    color: #16803d;
}

/* =========================================================
   CLASSROOM BUTTON
========================================================= */

.classroom-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 38px;
    padding: 8px 12px;
    border-radius: 9px;
    text-decoration: none;
    font-size: 11px;
    font-weight: 800;
    white-space: nowrap;
    background: linear-gradient(135deg, #003366, #0877c9);
    color: #fff;
    box-shadow: 0 5px 12px rgba(8,119,201,.18);
    transition: .2s ease;
}

.classroom-button:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 17px rgba(8,119,201,.24);
}

/* =========================================================
   NO DATA
========================================================= */

.no-data {
    padding: 45px 20px;
    text-align: center;
    color: #7b8794;
}

.no-data-icon {
    width: 58px;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    border-radius: 16px;
    background: #eaf5ff;
    font-size: 27px;
}

.no-data p {
    margin: 6px 0;
    font-size: 13px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {
    .main {
        padding: 22px;
    }

    .stats {
        grid-template-columns: repeat(3, minmax(0,1fr));
    }
}

@media (max-width: 800px) {
    .layout {
        display: block;
    }

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        min-height: 0;
        flex: none;
    }

    .main {
        width: 100%;
        padding: 17px 14px 35px;
    }

    .topbar {
        align-items: flex-start;
        flex-direction: column;
        padding: 18px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
        padding: 23px;
    }

    .page-header h2 {
        font-size: 23px;
    }

    .stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .today,
    .table-container {
        padding: 16px;
    }

    .today > div[style*="overflow-x:auto"] {
        border-radius: 10px;
    }

    
}

</style>


</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="layout">

<div class="sidebar">

    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">

        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="schedule.php"
           class="active">

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
     MAIN CONTENT
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

            📅 Lesson Schedule

        </h2>

        <p>

            View your assigned lessons,
            teachers and join your online
            classes directly through Zoom.

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

                Total Assigned Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?= $totalToday ?>

            </h3>

            <p>

                Lessons Today

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?= count($upcomingLessons) ?>

            </h3>

            <p>

                Upcoming Lessons

            </p>

        </div>


    </div>


    <!-- =================================================
         TODAY'S LESSONS
    ================================================== -->

    <div class="today">

        <h3>

            📌 Today's Lessons

        </h3>


        <?php if ($totalToday > 0): ?>


            <div style="overflow-x:auto;">

                <table>

                    <tr>

                        <th>
                            Time
                        </th>

                        <th>
                            Subject(s)
                        </th>

                        <th>
                            Curriculum
                        </th>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Zoom
                        </th>

                    </tr>


                    <?php foreach ($todayLessons as $today): ?>


                        <tr>

                            <td>

                                <strong>

                                    <?= h(
                                        formatTimeValue(
                                            $today['lesson_time']
                                        )
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= h(
                                    $today['subjects']
                                ) ?>

                            </td>


                            <td>

                                <?= h(
                                    $today['curriculum']
                                ) ?>

                            </td>


                            <td>

                                <div class="teacher-info">

                                    <div class="teacher-name">

                                        <?= h(
                                            $today['assigned_teacher_name']
                                            ?? 'Not assigned'
                                        ) ?>

                                    </div>


                                    <?php if (
                                        !empty(
                                            $today['teacher_phone']
                                        )
                                    ): ?>

                                        <div class="teacher-contact">

                                            📞

                                            <?= h(
                                                $today['teacher_phone']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <td>

                                <?= lessonStatusBadge(
                                    $today['lesson_status']
                                ) ?>

                            </td>


                            <td>

                                <?= classroomButton(
                                    $today['id']
                                    ?? 0
                                ) ?>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                </table>

            </div>


        <?php else: ?>


            <div class="no-data">

                <div class="no-data-icon">

                    📅

                </div>

                <p>

                    You have no lessons
                    scheduled for today.

                </p>

            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         ALL ASSIGNED LESSONS
    ================================================== -->

    <div class="table-container">


        <h3>

            📚 All Assigned Lessons

        </h3>


        <?php if ($totalLessons > 0): ?>


            <table>

                <tr>

                    <th>
                        Student
                    </th>

                    <th>
                        Subject(s)
                    </th>

                    <th>
                        Curriculum
                    </th>

                    <th>
                        Class
                    </th>

                    <th>
                        Lesson Date
                    </th>

                    <th>
                        Lesson Time
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
                        Zoom Class
                    </th>

                </tr>


                <?php foreach ($schedules as $row): ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>

                            <strong>

                                <?= h(
                                    $row['student_name']
                                ) ?>

                            </strong>


                            <br>


                            <small>

                                <?= h(
                                    $row['booking_reference']
                                ) ?>

                            </small>

                        </td>


                        <!-- SUBJECT -->

                        <td>

                            <?= h(
                                $row['subjects']
                            ) ?>

                        </td>


                        <!-- CURRICULUM -->

                        <td>

                            <?= h(
                                $row['curriculum']
                            ) ?>

                        </td>


                        <!-- CLASS -->

                        <td>

                            <?= h(
                                $row['class_year']
                            ) ?>

                        </td>


                        <!-- DATE -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $row['lesson_date']
                                )
                            ):

                            ?>

                                <?= h(
                                    formatDateValue(
                                        $row['lesson_date']
                                    )
                                ) ?>

                            <?php else: ?>

                                <span
                                    style="color:#999;"
                                >

                                    Not scheduled

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- TIME -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $row['lesson_time']
                                )
                            ):

                            ?>

                                <?= h(
                                    formatTimeValue(
                                        $row['lesson_time']
                                    )
                                ) ?>

                            <?php else: ?>

                                <span
                                    style="color:#999;"
                                >

                                    Not set

                                </span>

                            <?php endif; ?>

                        </td>


                        <!-- TEACHER -->

                        <td>

                            <div class="teacher-info">


                                <div class="teacher-name">

                                    <?= h(
                                        $row['assigned_teacher_name']
                                        ?? 'Not assigned'
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $row['teacher_phone']
                                    )
                                ): ?>

                                    <div class="teacher-contact">

                                        📞

                                        <?= h(
                                            $row['teacher_phone']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


                            </div>

                        </td>


                        <!-- PAYMENT -->

                        <td>

                            <?= paymentStatusBadge(
                                $row['payment_status']
                            ) ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?= lessonStatusBadge(
                                $row['lesson_status']
                            ) ?>

                        </td>


                        <!-- ZOOM -->

                        <td>

                            <?= classroomButton(
                                $row['id']
                                ?? 0
                            ) ?>

                        </td>


                    </tr>


                <?php endforeach; ?>


            </table>


        <?php else: ?>


            <div class="no-data">

                <div class="no-data-icon">

                    📚

                </div>


                <p>

                    You currently have
                    no lessons assigned to you.

                </p>


                <p>

                    Your schedule will appear
                    here when the administrator
                    assigns your classes.

                </p>

            </div>


        <?php endif; ?>


    </div>


</div>

</div>


</body>

</html>
