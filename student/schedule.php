<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT SCHEDULE
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_id']) &&
    !isset($_SESSION['student_email'])
) {

    header("Location: login.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| GET STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

$student_id =
    $_SESSION['student_id']
    ?? null;


$student_email =
    $_SESSION['student_email']
    ??
    $_SESSION['email']
    ??
    '';


$student_name =
    $_SESSION['student_name']
    ??
    $_SESSION['name']
    ??
    'Student';


/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
|
| If the student's email is not stored in the session,
| retrieve it from the students table using student_id.
|
*/

if (
    empty($student_email)
    &&
    !empty($student_id)
) {

    $studentLookup =
        $pdo->prepare("

            SELECT email, student_name

            FROM students

            WHERE id = ?

            LIMIT 1

        ");


    $studentLookup->execute([

        $student_id

    ]);


    $studentRecord =
        $studentLookup->fetch(
            PDO::FETCH_ASSOC
        );


    if ($studentRecord) {

        $student_email =
            $studentRecord['email']
            ??
            '';


        $student_name =
            $studentRecord['student_name']
            ??
            $student_name;

    }

}


/*
|--------------------------------------------------------------------------
| MAKE SURE WE HAVE AN EMAIL
|--------------------------------------------------------------------------
*/

if (
    empty($student_email)
) {

    die("

        <div style='

            font-family:Arial;

            text-align:center;

            padding:60px;

        '>

            <h2 style='color:#003366;'>

                Student Information Not Found

            </h2>

            <p>

                Your student email could not be
                identified from your account.

            </p>

            <a
                href='dashboard.php'
                style='
                    display:inline-block;
                    margin-top:20px;
                    padding:12px 20px;
                    background:#003366;
                    color:white;
                    text-decoration:none;
                    border-radius:6px;
                '
            >

                Back to Dashboard

            </a>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| GET ALL STUDENT BOOKINGS
|--------------------------------------------------------------------------
|
| We use the student's email because your bookings
| table already stores the student's email.
|
| The teacher's Zoom link is retrieved from teachers.
|
*/

$scheduleStmt =
    $pdo->prepare("

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

            b.teacher_id,

            b.teacher_name,

            b.assignment_status,

            t.teacher_id
                AS assigned_teacher_id,

            t.teacher_name
                AS assigned_teacher_name,

            t.email
                AS teacher_email,

            t.phone
                AS teacher_phone,

            t.zoom_link
                AS teacher_zoom_link


        FROM bookings b


        LEFT JOIN teachers t

            ON b.teacher_id = t.teacher_id


        WHERE LOWER(
            TRIM(b.email)
        )
        =
        LOWER(
            TRIM(?)
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
    count(
        $schedules
    );


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
        b.curriculum,
        b.class_year,
        b.subjects,
        b.lesson_date,
        b.lesson_time,
        b.lesson_status,
        b.payment_status,

        b.teacher_name,

        t.teacher_id AS assigned_teacher_id,
        t.teacher_name AS assigned_teacher_name,
        t.zoom_link AS teacher_zoom_link

    FROM bookings b

    LEFT JOIN teachers t
        ON b.teacher_id = t.teacher_id

    WHERE
        LOWER(TRIM(b.email))
        =
        LOWER(TRIM(?))

        AND DATE(b.lesson_date) = CURDATE()

    ORDER BY
        b.lesson_time ASC

");

$todayStmt->execute([
    $student_email
]);

$todayLessons = $todayStmt->fetchAll(
    PDO::FETCH_ASSOC
);

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
        b.curriculum,
        b.class_year,
        b.subjects,
        b.lesson_date,
        b.lesson_time,
        b.lesson_status,
        b.payment_status,

        b.teacher_name,

        t.teacher_id AS assigned_teacher_id,
        t.teacher_name AS assigned_teacher_name,
        t.zoom_link AS teacher_zoom_link

    FROM bookings b

    LEFT JOIN teachers t
        ON b.teacher_id = t.teacher_id

    WHERE
        LOWER(TRIM(b.email))
        =
        LOWER(TRIM(?))

        AND DATE(b.lesson_date) > CURDATE()

    ORDER BY
        b.lesson_date ASC,
        b.lesson_time ASC

    LIMIT 8

");

$upcomingStmt->execute([
    $student_email
]);

$upcomingLessons = $upcomingStmt->fetchAll(
    PDO::FETCH_ASSOC
);


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

        "d M Y",

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
                ??
                'scheduled'
            )

        );


    if (
        $status === "completed"
    ) {

        return '

            <span class="badge completed">

                Completed

            </span>

        ';

    }


    if (
        $status === "cancelled"
    ) {

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

    $status =
        strtolower(

            trim(
                $status
                ??
                ''
            )

        );


    if (

        $status === "paid"

        ||

        $status === "success"

    ) {

        return '

            <span class="badge paid">

                PAID

            </span>

        ';

    }


    return '

        <span class="badge pending">

            '
            .
            h(
                strtoupper(
                    $status
                    ?: 'PENDING'
                )
            )
            .
            '

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
        !empty($zoomLink)
    ) {

        return '

            <a

                href="'
                .
                h($zoomLink)
                .
                '"

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

            Zoom link not available yet

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
   TODAY'S LESSONS
===================================================== */

.section-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    margin: 0 0 5px;
    color: #003366;
}

.section-header p {
    margin: 0;
    color: #777;
}

.lesson-count {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #003366;
    color: white;

    display: flex;
    align-items: center;
    justify-content: center;

    font-weight: bold;
    font-size: 18px;
}

.today-lessons {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.today-lesson-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;

    display: grid;
    grid-template-columns: 180px 1fr auto;
    gap: 20px;

    align-items: center;
}

.lesson-time {
    display: flex;
    align-items: center;
    gap: 12px;
}

.time-icon {
    width: 45px;
    height: 45px;

    background: #eef5ff;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 20px;
}

.lesson-time strong {
    display: block;
    color: #003366;
    font-size: 17px;
}

.lesson-time small {
    color: #777;
}

.lesson-information h3 {
    margin: 0 0 10px;
    color: #003366;
}

.lesson-information p {
    margin: 5px 0;
    color: #555;
}

.lesson-status {
    margin-top: 10px;
}

.lesson-action {
    text-align: right;
}

.empty-state {
    text-align: center;
    padding: 45px 20px;
    color: #777;
}

.empty-icon {
    font-size: 45px;
    margin-bottom: 10px;
}

.empty-state h3 {
    color: #003366;
    margin-bottom: 5px;
}

.zoom-button {
    display: inline-block;

    padding: 11px 18px;

    background: #003366;
    color: white;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

    transition: 0.2s;
}

.zoom-button:hover {
    background: #0055a5;
}

.no-zoom {
    display: inline-block;

    padding: 10px 15px;

    background: #f1f1f1;

    color: #777;

    border-radius: 7px;

    font-size: 13px;
}

/* =====================================================
   TABLE
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
   ZOOM BUTTON
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

    transition:
        background 0.2s,
        transform 0.2s;

}


.zoom-button:hover {

    background: #1769c2;

    transform:
        translateY(-1px);

}


.no-zoom {

    color: #999;

    font-size: 12px;

    white-space: nowrap;

}


/* =====================================================
   TEACHER
===================================================== */

.teacher-info {

    line-height: 1.5;

}


.teacher-name {

    font-weight: bold;

    color: #003366;

}


.teacher-contact {

    font-size: 12px;

    color: #777;

}


/* =====================================================
   NO DATA
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


    .page-header {

        padding: 20px;

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


   <!-- =====================================================
     TODAY'S LESSONS
===================================================== -->

<div class="section-card">

    <div class="section-header">

        <div>
            <h2>📅 Today's Lessons</h2>

            <p>
                Your lessons scheduled for today
            </p>
        </div>

        <div class="lesson-count">

            <?= (int)$totalToday ?>

        </div>

    </div>


    <?php if (empty($todayLessons)): ?>

        <div class="empty-state">

            <div class="empty-icon">
                📚
            </div>

            <h3>
                No Lesson Today
            </h3>

            <p>
                You do not have any lesson scheduled
                for today.
            </p>

        </div>

    <?php else: ?>


        <div class="today-lessons">


            <?php foreach ($todayLessons as $lesson): ?>


                <div class="today-lesson-card">


                    <div class="lesson-time">

                        <div class="time-icon">
                            🕐
                        </div>

                        <div>

                            <strong>

                                <?= h(
                                    formatTimeValue(
                                        $lesson['lesson_time']
                                    )
                                ) ?>

                            </strong>

                            <small>
                                Today
                            </small>

                        </div>

                    </div>


                    <div class="lesson-information">


                        <h3>

                            <?= h(
                                $lesson['subjects']
                            ) ?>

                        </h3>


                        <p>

                            <strong>
                                Curriculum:
                            </strong>

                            <?= h(
                                $lesson['curriculum']
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Class:
                            </strong>

                            <?= h(
                                $lesson['class_year']
                            ) ?>

                        </p>


                        <p>

                            <strong>
                                Teacher:
                            </strong>

                            <?= h(
                                $lesson['assigned_teacher_name']
                                ??
                                $lesson['teacher_name']
                                ??
                                'Not assigned'
                            ) ?>

                        </p>


                        <div class="lesson-status">

                            <?= lessonStatusBadge(
                                $lesson['lesson_status']
                            ) ?>

                        </div>


                    </div>


                    <div class="lesson-action">

                        <?= zoomButton(
                            $lesson['teacher_zoom_link']
                            ?? ''
                        ) ?>

                    </div>


                </div>


            <?php endforeach; ?>


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


        <?php if (
            $totalLessons > 0
        ): ?>


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


                <?php foreach (
                    $schedules
                    as $row
                ): ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>


                            <strong>

                                <?= h(

                                    $row[
                                        'student_name'
                                    ]

                                ) ?>

                            </strong>


                            <br>


                            <small>

                                <?= h(

                                    $row[
                                        'booking_reference'
                                    ]

                                ) ?>

                            </small>


                        </td>


                        <!-- SUBJECT -->

                        <td>

                            <?= h(

                                $row[
                                    'subjects'
                                ]

                            ) ?>

                        </td>


                        <!-- CURRICULUM -->

                        <td>

                            <?= h(

                                $row[
                                    'curriculum'
                                ]

                            ) ?>

                        </td>


                        <!-- CLASS -->

                        <td>

                            <?= h(

                                $row[
                                    'class_year'
                                ]

                            ) ?>

                        </td>


                        <!-- DATE -->

                        <td>


                            <?php if (

                                !empty(

                                    $row[
                                        'lesson_date'
                                    ]

                                )

                            ): ?>


                                <?= h(

                                    formatDateValue(

                                        $row[
                                            'lesson_date'
                                        ]

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


                            <?php if (

                                !empty(

                                    $row[
                                        'lesson_time'
                                    ]

                                )

                            ): ?>


                                <?= h(

                                    formatTimeValue(

                                        $row[
                                            'lesson_time'
                                        ]

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


                            <div
                                class="teacher-info"
                            >


                                <div
                                    class="teacher-name"
                                >

                                    <?= h(

                                        $row[
                                            'assigned_teacher_name'
                                        ]
                                        ??
                                        'Not assigned'

                                    ) ?>

                                </div>


                                <?php if (

                                    !empty(

                                        $row[
                                            'teacher_phone'
                                        ]

                                    )

                                ): ?>


                                    <div
                                        class="teacher-contact"
                                    >

                                        📞

                                        <?= h(

                                            $row[
                                                'teacher_phone'
                                            ]

                                        ) ?>

                                    </div>


                                <?php endif; ?>


                            </div>


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


</body>

</html>
