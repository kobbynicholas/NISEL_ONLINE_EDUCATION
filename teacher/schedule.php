<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER SCHEDULE
|--------------------------------------------------------------------------
| PDO VERSION
|--------------------------------------------------------------------------
*/

require "../teacher_auth.php";
require "../config/db.php";


/*
|--------------------------------------------------------------------------
| TEACHER INFORMATION
|--------------------------------------------------------------------------
*/

$teacher_id = $_SESSION['teacher_id'] ?? '';
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


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

    return date("d M Y", $timestamp);
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

    return date("h:i A", $timestamp);
}


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

    return '
        <span class="badge pending">
            ' .
            h(
                strtoupper(
                    $status ?: 'PENDING'
                )
            )
            . '
        </span>
    ';
}


/*
|--------------------------------------------------------------------------
| UPDATE SELECTED STUDENT'S SCHEDULE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['update_schedule'])
) {

    $booking_id =
        intval(
            $_POST['booking_id'] ?? 0
        );

    $lesson_date =
        trim(
            $_POST['lesson_date'] ?? ''
        );

    $lesson_time =
        trim(
            $_POST['lesson_time'] ?? ''
        );

    $lesson_status =
        trim(
            $_POST['lesson_status']
            ?? 'Scheduled'
        );


    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($booking_id <= 0) {

        $message =
            "Please select a student.";

        $message_type =
            "error";

    } elseif (
        empty($lesson_date)
    ) {

        $message =
            "Please select a lesson date.";

        $message_type =
            "error";

    } elseif (
        empty($lesson_time)
    ) {

        $message =
            "Please select a lesson time.";

        $message_type =
            "error";

    } else {


        /*
        |--------------------------------------------------------------------------
        | SECURITY CHECK
        |--------------------------------------------------------------------------
        |
        | The booking MUST belong to the logged-in teacher.
        |
        */

        $checkStmt = $pdo->prepare("

            SELECT
                id,
                student_name,
                teacher_id

            FROM bookings

            WHERE
                id = ?
                AND teacher_id = ?

            LIMIT 1

        ");


        $checkStmt->execute([
            $booking_id,
            $teacher_id
        ]);


        $booking =
            $checkStmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$booking) {

            $message =
                "You are not authorised to schedule this student.";

            $message_type =
                "error";

        } else {


            /*
            |--------------------------------------------------------------------------
            | UPDATE SCHEDULE
            |--------------------------------------------------------------------------
            */

            $updateStmt = $pdo->prepare("

                UPDATE bookings

                SET

                    lesson_date = ?,
                    lesson_time = ?,
                    lesson_status = ?

                WHERE

                    id = ?
                    AND teacher_id = ?

            ");


            $updated =
                $updateStmt->execute([
                    $lesson_date,
                    $lesson_time,
                    $lesson_status,
                    $booking_id,
                    $teacher_id
                ]);


            if ($updated) {

                $message =
                    "Schedule for "
                    . $booking['student_name']
                    . " updated successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Unable to update the schedule.";

                $message_type =
                    "error";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET STUDENTS ASSIGNED TO THIS TEACHER
|--------------------------------------------------------------------------
|
| This is the list used by the selectable Student dropdown.
|
*/

$studentsStmt = $pdo->prepare("

    SELECT

        id,
        booking_reference,
        student_name,
        curriculum,
        class_year,
        subjects,
        lesson_date,
        lesson_time,
        lesson_status,
        payment_status

    FROM bookings

    WHERE teacher_id = ?

    ORDER BY

        student_name ASC,
        subjects ASC,
        lesson_date ASC

");


$studentsStmt->execute([
    $teacher_id
]);


$assignedStudents =
    $studentsStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$totalLessons =
    count($assignedStudents);


/*
|--------------------------------------------------------------------------
| TODAY'S LESSONS
|--------------------------------------------------------------------------
*/

$todayStmt = $pdo->prepare("

    SELECT

        id,
        booking_reference,
        student_name,
        curriculum,
        class_year,
        subjects,
        lesson_date,
        lesson_time,
        lesson_status,
        payment_status

    FROM bookings

    WHERE

        teacher_id = ?

        AND DATE(lesson_date) = CURDATE()

    ORDER BY

        lesson_time ASC

");


$todayStmt->execute([
    $teacher_id
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

        id,
        booking_reference,
        student_name,
        curriculum,
        class_year,
        subjects,
        lesson_date,
        lesson_time,
        lesson_status,
        payment_status

    FROM bookings

    WHERE

        teacher_id = ?

        AND DATE(lesson_date) > CURDATE()

    ORDER BY

        lesson_date ASC,
        lesson_time ASC

    LIMIT 8

");


$upcomingStmt->execute([
    $teacher_id
]);


$upcomingLessons =
    $upcomingStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


$totalUpcoming =
    count($upcomingLessons);

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
    Teacher Schedule | NISEL ONLINE EDUCATION
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

    transition: .2s;
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


.teacher {

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
   MESSAGE
===================================================== */

.message {

    padding: 14px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-weight: 600;
}


.message.success {

    background: #d4edda;

    color: #155724;
}


.message.error {

    background: #f8d7da;

    color: #721c24;
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
   SCHEDULE STUDENT
===================================================== */

.schedule-box {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.schedule-box h3 {

    margin-top: 0;

    color: #003366;
}


.schedule-box > p {

    color: #777;

    margin-bottom: 20px;
}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;

    gap: 7px;
}


.form-group.full {

    grid-column:
        1 / -1;
}


.form-group label {

    font-weight: bold;

    color: #003366;
}


.form-group select,
.form-group input {

    width: 100%;

    padding: 12px;

    border:
        1px solid #ccd5df;

    border-radius: 7px;

    background: white;

    font-size: 15px;

    outline: none;
}


.form-group select:focus,
.form-group input:focus {

    border-color: #0055a5;

    box-shadow:
        0 0 0 3px
        rgba(0,85,165,.1);
}


.student-preview {

    background: #f4f8fc;

    border:
        1px solid #dbe5ef;

    border-radius: 8px;

    padding: 15px;

    margin-top: 5px;
}


.student-preview strong {

    color: #003366;
}


.student-preview span {

    color: #666;

    font-size: 13px;
}


.save-button {

    border: none;

    background: #003366;

    color: white;

    padding: 13px 22px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 15px;

    font-weight: bold;

    margin-top: 10px;
}


.save-button:hover {

    background: #0055a5;
}


/* =====================================================
   TODAY
===================================================== */

.today {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);
}


.today h3 {

    color: #003366;

    margin-top: 0;
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

    margin-bottom: 25px;
}


.table-container h3 {

    color: #003366;

    margin-top: 0;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;
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
   STUDENT NAME
===================================================== */

.student-name {

    color: #003366;

    font-weight: bold;
}


.reference {

    font-size: 12px;

    color: #888;

    margin-top: 3px;
}


/* =====================================================
   UPCOMING
===================================================== */

.upcoming {

    background: #f8fbff;

    border-left:
        5px solid #003366;

    padding: 18px;

    border-radius: 8px;

    margin-bottom: 12px;
}


.upcoming:last-child {

    margin-bottom: 0;
}


.upcoming-date {

    color: #003366;

    font-weight: bold;
}


.upcoming-subject {

    font-size: 17px;

    font-weight: bold;

    margin-top: 6px;
}


.upcoming-info {

    color: #666;

    margin-top: 7px;

    line-height: 1.6;
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

    font-size: 40px;

    margin-bottom: 8px;
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


    .form-grid {

        grid-template-columns: 1fr;
    }


    .form-group.full {

        grid-column: auto;
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


        <a href="students.php">

            👨‍🎓 My Students

        </a>


        <a
            href="schedule.php"
            class="active"
        >

            📅 My Schedule

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


        <div class="teacher">

            Welcome,

            <strong>

                <?= h($teacher_name) ?>

            </strong>

        </div>


    </div>


    <!-- PAGE HEADER -->

    <div class="page-header">


        <h2>

            📅 Teacher Lesson Schedule

        </h2>


        <p>

            Select one of your assigned students,
            choose the lesson date and time,
            then save the schedule.

        </p>


    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div
            class="message
            <?= h($message_type) ?>"
        >

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?= $totalLessons ?>

            </h3>

            <p>

                Assigned Lessons

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

                <?= $totalUpcoming ?>

            </h3>

            <p>

                Upcoming Lessons

            </p>

        </div>


    </div>


    <!-- =================================================
         SCHEDULE A STUDENT
    ================================================== -->

    <div class="schedule-box">


        <h3>

            📝 Schedule a Student

        </h3>


        <p>

            Select the student from your assigned
            students and set the lesson date and time.

        </p>


        <?php if (
            !empty($assignedStudents)
        ): ?>


            <form
                method="POST"
                id="scheduleForm"
            >


                <div class="form-grid">


                    <!-- STUDENT -->

                    <div class="form-group full">


                        <label for="booking_id">

                            Student

                        </label>


                        <select
                            name="booking_id"
                            id="booking_id"
                            required
                        >


                            <option value="">

                                -- Select Student --

                            </option>


                            <?php foreach (
                                $assignedStudents
                                as $student
                            ): ?>


                                <option

                                    value="<?= (int)$student['id'] ?>"

                                    data-name="<?= h(
                                        $student['student_name']
                                    ) ?>"

                                    data-subject="<?= h(
                                        $student['subjects']
                                    ) ?>"

                                    data-curriculum="<?= h(
                                        $student['curriculum']
                                    ) ?>"

                                    data-class="<?= h(
                                        $student['class_year']
                                    ) ?>"

                                    data-date="<?= h(
                                        $student['lesson_date']
                                    ) ?>"

                                    data-time="<?= h(
                                        $student['lesson_time']
                                    ) ?>"

                                >

                                    <?= h(
                                        $student['student_name']
                                    ) ?>

                                    -

                                    <?= h(
                                        $student['subjects']
                                    ) ?>

                                    -

                                    <?= h(
                                        $student['booking_reference']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                        <div
                            class="student-preview"
                            id="studentPreview"
                            style="display:none;"
                        >

                            <strong
                                id="previewName"
                            ></strong>

                            <br>

                            <span
                                id="previewDetails"
                            ></span>

                        </div>


                    </div>


                    <!-- DATE -->

                    <div class="form-group">


                        <label for="lesson_date">

                            Lesson Date

                        </label>


                        <input
                            type="date"
                            name="lesson_date"
                            id="lesson_date"
                            required
                        >


                    </div>


                    <!-- TIME -->

                    <div class="form-group">


                        <label for="lesson_time">

                            Lesson Time

                        </label>


                        <input
                            type="time"
                            name="lesson_time"
                            id="lesson_time"
                            required
                        >


                    </div>


                    <!-- STATUS -->

                    <div class="form-group">


                        <label for="lesson_status">

                            Lesson Status

                        </label>


                        <select
                            name="lesson_status"
                            id="lesson_status"
                        >


                            <option
                                value="Scheduled"
                            >

                                Scheduled

                            </option>


                            <option
                                value="Completed"
                            >

                                Completed

                            </option>


                            <option
                                value="Cancelled"
                            >

                                Cancelled

                            </option>


                        </select>


                    </div>


                    <!-- BUTTON -->

                    <div class="form-group">


                        <label>
                            &nbsp;
                        </label>


                        <button
                            type="submit"
                            name="update_schedule"
                            class="save-button"
                        >

                            💾 Save Schedule

                        </button>


                    </div>


                </div>


            </form>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    👨‍🎓

                </div>


                <strong>

                    No Students Assigned

                </strong>


                <p>

                    Students assigned to you will
                    appear here.

                </p>


            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         TODAY'S LESSONS
    ================================================== -->

    <div class="today">


        <h3>

            📅 Today's Lessons

        </h3>


        <?php if (
            !empty($todayLessons)
        ): ?>


            <div
                class="table-container"
                style="box-shadow:none;padding:0;"
            >


                <table>


                    <tr>

                        <th>
                            Time
                        </th>

                        <th>
                            Student
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
                            Status
                        </th>

                    </tr>


                    <?php foreach (
                        $todayLessons
                        as $today
                    ): ?>


                        <tr>


                            <td>

                                <strong>

                                    <?= h(

                                        formatTimeValue(

                                            $today[
                                                'lesson_time'
                                            ]

                                        )

                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <span
                                    class="student-name"
                                >

                                    <?= h(

                                        $today[
                                            'student_name'
                                        ]

                                    ) ?>

                                </span>


                                <div
                                    class="reference"
                                >

                                    <?= h(

                                        $today[
                                            'booking_reference'
                                        ]

                                    ) ?>

                                </div>

                            </td>


                            <td>

                                <?= h(

                                    $today[
                                        'subjects'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    $today[
                                        'curriculum'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    $today[
                                        'class_year'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= lessonStatusBadge(

                                    $today[
                                        'lesson_status'
                                    ]

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

                    You have no lessons scheduled
                    for today.

                </p>


            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         UPCOMING LESSONS
    ================================================== -->

    <div class="table-container">


        <h3>

            📚 Upcoming Lessons

        </h3>


        <?php if (
            !empty($upcomingLessons)
        ): ?>


            <?php foreach (
                $upcomingLessons
                as $lesson
            ): ?>


                <div class="upcoming">


                    <div class="upcoming-date">

                        📅

                        <?= h(

                            formatDateValue(

                                $lesson[
                                    'lesson_date'
                                ]

                            )

                        ) ?>


                        &nbsp;&nbsp;


                        🕐

                        <?= h(

                            formatTimeValue(

                                $lesson[
                                    'lesson_time'
                                ]

                            )

                        ) ?>

                    </div>


                    <div
                        class="upcoming-subject"
                    >

                        <?= h(

                            $lesson[
                                'subjects'
                            ]

                        ) ?>

                    </div>


                    <div
                        class="upcoming-info"
                    >

                        <strong>
                            Student:
                        </strong>

                        <span
                            class="student-name"
                        >

                            <?= h(

                                $lesson[
                                    'student_name'
                                ]

                            ) ?>

                        </span>


                        <br>


                        <strong>
                            Curriculum:
                        </strong>

                        <?= h(

                            $lesson[
                                'curriculum'
                            ]

                        ) ?>


                        <br>


                        <strong>
                            Class:
                        </strong>

                        <?= h(

                            $lesson[
                                'class_year'
                            ]

                        ) ?>


                        <br>


                        <br>


                        <?= lessonStatusBadge(

                            $lesson[
                                'lesson_status'
                            ]

                        ) ?>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    📚

                </div>


                <p>

                    No upcoming lessons.

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
            !empty($assignedStudents)
        ): ?>


            <table>


                <thead>

                    <tr>

                        <th>
                            Student
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
                            Lesson Date
                        </th>

                        <th>
                            Lesson Time
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach (
                        $assignedStudents
                        as $row
                    ): ?>


                        <tr>


                            <td>


                                <span
                                    class="student-name"
                                >

                                    <?= h(

                                        $row[
                                            'student_name'
                                        ]

                                    ) ?>

                                </span>


                                <div
                                    class="reference"
                                >

                                    <?= h(

                                        $row[
                                            'booking_reference'
                                        ]

                                    ) ?>

                                </div>


                            </td>


                            <td>

                                <?= h(

                                    $row[
                                        'subjects'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    $row[
                                        'curriculum'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    $row[
                                        'class_year'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    formatDateValue(

                                        $row[
                                            'lesson_date'
                                        ]

                                    )

                                ) ?>

                            </td>


                            <td>

                                <?= h(

                                    formatTimeValue(

                                        $row[
                                            'lesson_time'
                                        ]

                                    )

                                ) ?>

                            </td>


                            <td>

                                <?= paymentStatusBadge(

                                    $row[
                                        'payment_status'
                                    ]

                                ) ?>

                            </td>


                            <td>

                                <?= lessonStatusBadge(

                                    $row[
                                        'lesson_status'
                                    ]

                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>


            </table>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    👨‍🎓

                </div>


                <p>

                    You currently have no students
                    assigned to you.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| STUDENT SELECTION
|--------------------------------------------------------------------------
|
| When the teacher selects a student, the existing
| date and time are loaded automatically.
|
|--------------------------------------------------------------------------
*/

const studentSelect =
    document.getElementById(
        "booking_id"
    );


const preview =
    document.getElementById(
        "studentPreview"
    );


const previewName =
    document.getElementById(
        "previewName"
    );


const previewDetails =
    document.getElementById(
        "previewDetails"
    );


const lessonDate =
    document.getElementById(
        "lesson_date"
    );


const lessonTime =
    document.getElementById(
        "lesson_time"
    );


const lessonStatus =
    document.getElementById(
        "lesson_status"
    );


if (studentSelect) {


    studentSelect.addEventListener(
        "change",
        function()
        {

            const selected =
                this.options[
                    this.selectedIndex
                ];


            if (
                !selected.value
            ) {

                preview.style.display =
                    "none";

                lessonDate.value =
                    "";

                lessonTime.value =
                    "";

                lessonStatus.value =
                    "Scheduled";

                return;
            }


            const name =
                selected.dataset.name
                || "";


            const subject =
                selected.dataset.subject
                || "";


            const curriculum =
                selected.dataset.curriculum
                || "";


            const classYear =
                selected.dataset.class
                || "";


            const existingDate =
                selected.dataset.date
                || "";


            const existingTime =
                selected.dataset.time
                || "";


            previewName.textContent =
                name;


            previewDetails.textContent =

                subject
                + " | "
                + curriculum
                + " | Class: "
                + classYear;


            preview.style.display =
                "block";


            /*
            |--------------------------------------------------------------------------
            | LOAD EXISTING DATE
            |--------------------------------------------------------------------------
            */

            if (existingDate) {

                lessonDate.value =
                    existingDate.substring(
                        0,
                        10
                    );

            } else {

                lessonDate.value =
                    "";

            }


            /*
            |--------------------------------------------------------------------------
            | LOAD EXISTING TIME
            |--------------------------------------------------------------------------
            */

            if (existingTime) {

                lessonTime.value =
                    existingTime.substring(
                        0,
                        5
                    );

            } else {

                lessonTime.value =
                    "";

            }

        }
    );

}

</script>


</body>

</html>
