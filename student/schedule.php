<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| CHECK STUDENT LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {

    header("Location: login.php");
    exit;

}


$student_id =
    $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? "Student";

$student_email =
    $_SESSION['student_email']
    ?? "";


/*
|--------------------------------------------------------------------------
| GET STUDENT INFORMATION
|--------------------------------------------------------------------------
|
| We get the student's email directly from the students table.
| This matches the way the student dashboard finds bookings.
|
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
    | USE DATABASE EMAIL
    |--------------------------------------------------------------------------
    */

    $student_email =
        trim(
            $student['email']
            ?? $student_email
        );


    $student_name =
        $student['student_name']
        ?? $student_name;


    /*
    |--------------------------------------------------------------------------
    | GET ALL STUDENT LESSONS
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | We use bookings.email because that is how your
    | existing student dashboard identifies bookings.
    |
    | Teacher phone and email are NOT selected.
    |
    |--------------------------------------------------------------------------
    */

    $scheduleStmt = $pdo->prepare("
        SELECT

            b.id,

            b.booking_reference,

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

            t.teacher_name
                AS assigned_teacher_name,

            t.zoom_link
                AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE b.email = ?

        ORDER BY

            CASE
                WHEN b.lesson_date IS NULL THEN 1
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

            t.teacher_name
                AS assigned_teacher_name,

            t.zoom_link
                AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE

            b.email = ?

            AND DATE(b.lesson_date) = CURDATE()

        ORDER BY
            b.lesson_time ASC
    ");

    $todayStmt->execute([
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

            t.teacher_name
                AS assigned_teacher_name,

            t.zoom_link
                AS teacher_zoom_link

        FROM bookings b

        LEFT JOIN teachers t
            ON b.teacher_id = t.teacher_id

        WHERE

            b.email = ?

            AND DATE(b.lesson_date) > CURDATE()

        ORDER BY

            b.lesson_date ASC,

            b.lesson_time ASC

        LIMIT 8
    ");

    $upcomingStmt->execute([
        $student_email
    ]);

    $upcomingLessons =
        $upcomingStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | SHOW REAL DATABASE ERROR WHILE TESTING
    |--------------------------------------------------------------------------
    */

    die(
        "Unable to load student schedule: "
        . htmlspecialchars(
            $e->getMessage()
        )
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
                ?? "scheduled"
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
                ?? ""
            )
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
            '
            .
            h(
                strtoupper(
                    $status ?: "PENDING"
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
