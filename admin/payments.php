<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN BOOKING MANAGEMENT
| PDO VERSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {
    header("Location: ../admin_login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require "../config/db.php";


if (!isset($pdo)) {

    die(
        "Database connection is not available. " .
        "Please check config/db.php"
    );

}


$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| MESSAGE
|--------------------------------------------------------------------------
*/

$message = "";

$messageType = "";


/*
|--------------------------------------------------------------------------
| ASSIGN / UPDATE BOOKING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['assign_booking'])
) {


    $bookingId =
        isset($_POST['booking_id'])
            ? (int)$_POST['booking_id']
            : 0;


    $teacherId =
        trim(
            $_POST['teacher_id']
            ?? ''
        );


    $lessonDate =
        trim(
            $_POST['lesson_date']
            ?? ''
        );


    $lessonTime =
        trim(
            $_POST['lesson_time']
            ?? ''
        );


    if ($bookingId <= 0) {

        $message =
            "Invalid booking selected.";

        $messageType =
            "error";

    }

    elseif ($teacherId === '') {

        $message =
            "Please select a teacher.";

        $messageType =
            "error";

    }

    elseif ($lessonDate === '') {

        $message =
            "Please select a lesson date.";

        $messageType =
            "error";

    }

    elseif ($lessonTime === '') {

        $message =
            "Please select a lesson time.";

        $messageType =
            "error";

    }

    else {

        try {


            /*
            |--------------------------------------------------------------------------
            | GET TEACHER
            |--------------------------------------------------------------------------
            */

            $teacherStmt =
                $pdo->prepare("
                    SELECT
                        teacher_id,
                        teacher_name,
                        subjects,
                        curriculum,
                        status
                    FROM teachers
                    WHERE teacher_id = ?
                    LIMIT 1
                ");


            $teacherStmt->execute([
                $teacherId
            ]);


            $teacher =
                $teacherStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$teacher) {

                throw new Exception(
                    "Selected teacher could not be found."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | GET BOOKING
            |--------------------------------------------------------------------------
            */

            $bookingStmt =
                $pdo->prepare("
                    SELECT
                        id,
                        student_id,
                        student_name,
                        email,
                        curriculum,
                        class_year,
                        subjects,
                        payment_status
                    FROM bookings
                    WHERE id = ?
                    LIMIT 1
                ");


            $bookingStmt->execute([
                $bookingId
            ]);


            $booking =
                $bookingStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$booking) {

                throw new Exception(
                    "Booking could not be found."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE BOOKING
            |--------------------------------------------------------------------------
            */

            $update =
                $pdo->prepare("
                    UPDATE bookings
                    SET
                        teacher_id = :teacher_id,
                        teacher_name = :teacher_name,
                        lesson_date = :lesson_date,
                        lesson_time = :lesson_time,
                        assignment_status = 'Assigned',
                        lesson_status = 'Scheduled'
                    WHERE id = :id
                    LIMIT 1
                ");


            $update->execute([

                ':teacher_id'
                    => $teacher['teacher_id'],

                ':teacher_name'
                    => $teacher['teacher_name'],

                ':lesson_date'
                    => $lessonDate,

                ':lesson_time'
                    => $lessonTime,

                ':id'
                    => $bookingId

            ]);


            $message =
                "Booking successfully assigned to " .
                $teacher['teacher_name'] .
                ".";


            $messageType =
                "success";


        }

        catch (
            Exception $e
        ) {

            $message =
                $e->getMessage();

            $messageType =
                "error";

        }

    }

}


/*
|--------------------------------------------------------------------------
| REMOVE TEACHER ASSIGNMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_assignment'])
) {


    $bookingId =
        isset($_POST['booking_id'])
            ? (int)$_POST['booking_id']
            : 0;


    if ($bookingId > 0) {

        try {

            $stmt =
                $pdo->prepare("
                    UPDATE bookings
                    SET
                        teacher_id = NULL,
                        teacher_name = NULL,
                        lesson_date = NULL,
                        lesson_time = NULL,
                        assignment_status = 'Unassigned',
                        lesson_status = 'Scheduled'
                    WHERE id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $bookingId
            ]);


            $message =
                "Teacher assignment removed successfully.";

            $messageType =
                "success";


        }

        catch (
            PDOException $e
        ) {

            $message =
                "Unable to remove assignment.";

            $messageType =
                "error";

        }

    }

}


/*
|--------------------------------------------------------------------------
| MARK LESSON COMPLETED
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['complete_lesson'])
) {


    $bookingId =
        isset($_POST['booking_id'])
            ? (int)$_POST['booking_id']
            : 0;


    if ($bookingId > 0) {

        try {

            $stmt =
                $pdo->prepare("
                    UPDATE bookings
                    SET
                        lesson_status = 'Completed'
                    WHERE id = ?
                    LIMIT 1
                ");


            $stmt->execute([
                $bookingId
            ]);


            $message =
                "Lesson marked as completed.";

            $messageType =
                "success";


        }

        catch (
            PDOException $e
        ) {

            $message =
                "Unable to update lesson status.";

            $messageType =
                "error";

        }

    }

}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$curriculumFilter =
    trim(
        $_GET['curriculum']
        ?? ''
    );


$paymentFilter =
    trim(
        $_GET['payment']
        ?? ''
    );


$assignmentFilter =
    trim(
        $_GET['assignment']
        ?? ''
    );


$lessonFilter =
    trim(
        $_GET['lesson_status']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            b.student_name LIKE ?
            OR b.email LIKE ?
            OR b.phone LIKE ?
            OR b.booking_reference LIKE ?
            OR b.subjects LIKE ?
        )
    ";


    $searchValue =
        '%' .
        $search .
        '%';


    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

    $params[] =
        $searchValue;

}


/*
|--------------------------------------------------------------------------
| CURRICULUM FILTER
|--------------------------------------------------------------------------
*/

if (
    $curriculumFilter !== ''
) {

    $where[] =
        "b.curriculum = ?";

    $params[] =
        $curriculumFilter;

}


/*
|--------------------------------------------------------------------------
| PAYMENT FILTER
|--------------------------------------------------------------------------
*/

if (
    $paymentFilter !== ''
) {

    if (
        $paymentFilter === 'paid'
    ) {

        $where[] = "
            (
                LOWER(b.payment_status) = 'paid'
                OR
                LOWER(b.payment_status) = 'success'
            )
        ";

    }

    elseif (
        $paymentFilter === 'pending'
    ) {

        $where[] = "
            LOWER(b.payment_status) = 'pending'
        ";

    }

}


/*
|--------------------------------------------------------------------------
| ASSIGNMENT FILTER
|--------------------------------------------------------------------------
*/

if (
    $assignmentFilter !== ''
) {

    if (
        $assignmentFilter === 'assigned'
    ) {

        $where[] = "
            b.teacher_id IS NOT NULL
            AND b.teacher_id <> ''
        ";

    }

    elseif (
        $assignmentFilter === 'unassigned'
    ) {

        $where[] = "
            (
                b.teacher_id IS NULL
                OR
                b.teacher_id = ''
            )
        ";

    }

}


/*
|--------------------------------------------------------------------------
| LESSON STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $lessonFilter !== ''
) {

    $where[] =
        "LOWER(b.lesson_status) = LOWER(?)";

    $params[] =
        $lessonFilter;

}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSQL = "";


if (
    !empty($where)
) {

    $whereSQL =
        " WHERE " .
        implode(
            " AND ",
            $where
        );

}


/*
|--------------------------------------------------------------------------
| GET BOOKINGS
|--------------------------------------------------------------------------
*/

try {


    $sql = "
        SELECT

            b.id,

            b.booking_reference,

            b.student_id,

            b.student_name,

            b.email,

            b.phone,

            b.dob,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.amount,

            b.payment_status,

            b.paystack_reference,

            b.teacher_id,

            b.teacher_name,

            b.assignment_status,

            b.lesson_date,

            b.lesson_time,

            b.lesson_status

        FROM bookings b

        $whereSQL

        ORDER BY
            CASE
                WHEN b.lesson_date IS NULL
                THEN 1
                ELSE 0
            END,

            b.lesson_date ASC,

            b.lesson_time ASC,

            b.id DESC
    ";


    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $bookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


}

catch (
    PDOException $e
) {

    die(
        "Unable to load bookings: " .
        h(
            $e->getMessage()
        )
    );

}


/*
|--------------------------------------------------------------------------
| GET TEACHERS
|--------------------------------------------------------------------------
*/

try {

    $teacherListStmt =
        $pdo->query("
            SELECT
                teacher_id,
                teacher_name,
                subjects,
                curriculum,
                availability,
                zoom_link,
                status
            FROM teachers
            WHERE
                status IS NULL
                OR
                status = 'Active'
                OR
                status = 'active'
            ORDER BY teacher_name ASC
        ");


    $teachers =
        $teacherListStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


}

catch (
    PDOException $e
) {

    $teachers = [];

}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalBookings =
    count($bookings);


$paidBookings =
    0;


$pendingBookings =
    0;


$assignedBookings =
    0;


$unassignedBookings =
    0;


$completedLessons =
    0;


$scheduledLessons =
    0;


foreach (
    $bookings as $b
) {


    $payment =
        strtolower(
            trim(
                $b['payment_status']
                ?? ''
            )
        );


    if (
        $payment === 'paid' ||
        $payment === 'success'
    ) {

        $paidBookings++;

    }


    if (
        $payment === 'pending'
    ) {

        $pendingBookings++;

    }


    if (
        !empty(
            $b['teacher_id']
        )
    ) {

        $assignedBookings++;

    }

    else {

        $unassignedBookings++;

    }


    $lesson =
        strtolower(
            trim(
                $b['lesson_status']
                ?? ''
            )
        );


    if (
        $lesson === 'completed'
    ) {

        $completedLessons++;

    }


    if (
        $lesson === 'scheduled'
    ) {

        $scheduledLessons++;

    }

}


/*
|--------------------------------------------------------------------------
| UNIQUE CURRICULA
|--------------------------------------------------------------------------
*/

$curricula = [];


foreach (
    $bookings as $b
) {

    $value =
        trim(
            $b['curriculum']
            ?? ''
        );


    if (
        $value !== ''
    ) {

        $curricula[$value] =
            true;

    }

}


$curricula =
    array_keys(
        $curricula
    );


sort(
    $curricula
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

    Booking Management |
    NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   RESET
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

    background: #f4f7fb;

    color: #172b4d;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 245px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003366,
            #00264d
        );

    color: white;

    padding: 24px 14px;

    z-index: 1000;

}


.brand {

    text-align: center;

    padding:
        0 5px 24px;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

}


.brand-icon {

    width: 58px;

    height: 58px;

    margin:
        0 auto 10px;

    border-radius: 16px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

}


.brand h2 {

    margin: 0;

    font-size: 20px;

}


.brand p {

    margin:
        5px 0 0;

    font-size: 9px;

    letter-spacing: 2px;

    opacity: .7;

}


.nav {

    margin-top: 25px;

}


.nav a {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 14px;

    margin-bottom: 7px;

    border-radius: 9px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration: none;

    font-size: 14px;

    transition: .2s;

}


.nav a:hover,
.nav a.active {

    background:
        rgba(
            255,
            255,
            255,
            .14
        );

    color: white;

}


.nav-icon {

    width: 22px;

    text-align: center;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 245px;

    padding: 28px;

    min-height: 100vh;

}


/* =====================================================
   HEADER
===================================================== */

.header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 24px;

}


.header h1 {

    margin: 0;

    color: #003366;

    font-size: 27px;

}


.header p {

    margin:
        6px 0 0;

    color: #667085;

    font-size: 13px;

}


.admin {

    background: white;

    padding: 10px 15px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    gap: 10px;

    box-shadow:
        0 3px 15px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.admin-icon {

    width: 36px;

    height: 36px;

    border-radius: 50%;

    background: #e8f2ff;

    display: flex;

    align-items: center;

    justify-content: center;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            6,
            1fr
        );

    gap: 14px;

    margin-bottom: 22px;

}


.stat {

    background: white;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.stat-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background: #eef4ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

}


.stat-number {

    margin-top: 13px;

    font-size: 24px;

    font-weight: 800;

    color: #003366;

}


.stat-label {

    margin-top: 3px;

    font-size: 10px;

    color: #667085;

}


/* =====================================================
   FILTER CARD
===================================================== */

.filter-card {

    background: white;

    border-radius: 16px;

    padding: 21px;

    margin-bottom: 22px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.filter-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;

}


.filter-title h2 {

    margin: 0;

    font-size: 17px;

    color: #003366;

}


.filter-title span {

    color: #98a2b3;

    font-size: 11px;

}


.filters {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

}


input,
select {

    width: 100%;

    padding: 12px 13px;

    border:
        1px solid
        #d0d5dd;

    border-radius: 9px;

    background: white;

    color: #344054;

    outline: none;

    font-size: 12px;

}


input:focus,
select:focus {

    border-color: #0066aa;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            102,
            170,
            .08
        );

}


.filter-btn {

    border: none;

    background: #003366;

    color: white;

    padding:
        0 18px;

    border-radius: 9px;

    font-weight: 700;

    cursor: pointer;

}


.filter-btn:hover {

    background: #0055a5;

}


.clear-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding:
        10px 15px;

    margin-top: 12px;

    border-radius: 8px;

    background: #f2f4f7;

    color: #344054;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding: 14px 17px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 13px;

    font-weight: 600;

}


.alert.success {

    background: #ecfdf3;

    color: #027a48;

    border:
        1px solid
        #abefc6;

}


.alert.error {

    background: #fef3f2;

    color: #b42318;

    border:
        1px solid
        #fecdca;

}


/* =====================================================
   BOOKING CARD
===================================================== */

.booking-card {

    background: white;

    border-radius: 16px;

    padding: 20px;

    margin-bottom: 18px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.booking-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    padding-bottom: 15px;

    border-bottom:
        1px solid
        #eaecf0;

}


.student {

    display: flex;

    align-items: center;

    gap: 13px;

}


.student-avatar {

    width: 48px;

    height: 48px;

    border-radius: 50%;

    background: #e8f2ff;

    color: #003366;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

}


.student-name {

    margin: 0;

    font-size: 15px;

    color: #172b4d;

}


.student-meta {

    margin-top: 5px;

    color: #98a2b3;

    font-size: 10px;

}


.reference {

    color: #667085;

    font-size: 10px;

    text-align: right;

}


.reference strong {

    color: #003366;

}


/* =====================================================
   BOOKING GRID
===================================================== */

.booking-grid {

    display: grid;

    grid-template-columns:
        repeat(
            5,
            1fr
        );

    gap: 12px;

    margin-top: 17px;

}


.info {

    background: #f8fafc;

    border-radius: 9px;

    padding: 12px;

    min-height: 67px;

}


.info-label {

    color: #98a2b3;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 7px;

}


.info-value {

    color: #344054;

    font-size: 12px;

    font-weight: 600;

    word-break: break-word;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

}


.badge.paid {

    background: #e7f7ed;

    color: #16803d;

}


.badge.pending {

    background: #fff4e5;

    color: #b54708;

}


.badge.assigned {

    background: #eef4ff;

    color: #175cd3;

}


.badge.unassigned {

    background: #f2f4f7;

    color: #667085;

}


.badge.completed {

    background: #e7f7ed;

    color: #16803d;

}


.badge.scheduled {

    background: #eef4ff;

    color: #175cd3;

}


.badge.cancelled {

    background: #fef3f2;

    color: #b42318;

}


/* =====================================================
   ASSIGNMENT SECTION
===================================================== */

.assignment {

    margin-top: 18px;

    padding-top: 18px;

    border-top:
        1px solid
        #eaecf0;

}


.assignment-title {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 13px;

}


.assignment-title h3 {

    margin: 0;

    font-size: 14px;

    color: #003366;

}


.assignment-form {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        auto;

    gap: 10px;

    align-items: end;

}


.form-field label {

    display: block;

    margin-bottom: 6px;

    color: #667085;

    font-size: 10px;

    font-weight: 700;

}


.assign-btn {

    height: 42px;

    padding:
        0 18px;

    border: none;

    border-radius: 9px;

    background: #003366;

    color: white;

    font-weight: 700;

    cursor: pointer;

}


.assign-btn:hover {

    background: #0055a5;

}


.teacher-current {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 12px;

    padding: 11px 13px;

    background: #f8fafc;

    border-radius: 9px;

}


.teacher-current span {

    font-size: 11px;

    color: #667085;

}


.teacher-current strong {

    color: #003366;

}


/* =====================================================
   ACTIONS
===================================================== */

.actions {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 15px;

}


.action-btn {

    border: none;

    cursor: pointer;

    padding:
        8px 11px;

    border-radius: 8px;

    font-size: 10px;

    font-weight: 700;

}


.complete-btn {

    background: #ecfdf3;

    color: #027a48;

}


.remove-btn {

    background: #fef3f2;

    color: #b42318;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    background: white;

    border-radius: 16px;

    padding: 60px 20px;

    text-align: center;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.empty-icon {

    font-size: 48px;

    margin-bottom: 12px;

}


.empty h3 {

    margin: 0;

    color: #003366;

}


.empty p {

    color: #98a2b3;

    font-size: 12px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    padding: 15px;

    color: #98a2b3;

    font-size: 10px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:1250px) {

    .stats {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }


    .filters {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }


    .booking-grid {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }


    .assignment-form {

        grid-template-columns:
            1fr
            1fr
            1fr
            auto;

    }

}


@media(max-width:900px) {

    .sidebar {

        width: 70px;

        padding:
            20px 8px;

    }


    .brand h2,
    .brand p,
    .nav-text {

        display: none;

    }


    .brand {

        border: none;

    }


    .nav a {

        justify-content:
            center;

    }


    .main {

        margin-left: 70px;

        padding: 18px;

    }


    .header {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap: 15px;

    }

}


@media(max-width:650px) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .filters {

        grid-template-columns: 1fr;

    }


    .booking-grid {

        grid-template-columns:
            1fr
            1fr;

    }


    .assignment-form {

        grid-template-columns: 1fr;

    }


    .booking-top {

        flex-direction:
            column;

    }


    .reference {

        text-align: left;

    }

}


@media(max-width:450px) {

    .stats {

        grid-template-columns:
            1fr;

    }


    .booking-grid {

        grid-template-columns: 1fr;

    }


    .main {

        padding: 12px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="brand">


        <div class="brand-icon">

            🎓

        </div>


        <h2>

            NISEL

        </h2>


        <p>

            ONLINE EDUCATION

        </p>


    </div>


    <nav class="nav">


        <a href="dashboard.php">

            <span class="nav-icon">
                🏠
            </span>

            <span class="nav-text">
                Dashboard
            </span>

        </a>


        <a href="teachers.php">

            <span class="nav-icon">
                👨‍🏫
            </span>

            <span class="nav-text">
                Teachers
            </span>

        </a>


        <a href="students.php">

            <span class="nav-icon">
                👨‍🎓
            </span>

            <span class="nav-text">
                Students
            </span>

        </a>


        <a
            href="booking.php"
            class="active"
        >

            <span class="nav-icon">
                📚
            </span>

            <span class="nav-text">
                Bookings
            </span>

        </a>


        <a href="payments.php">

            <span class="nav-icon">
                💳
            </span>

            <span class="nav-text">
                Payments
            </span>

        </a>


        <a href="logout.php">

            <span class="nav-icon">
                🚪
            </span>

            <span class="nav-text">
                Logout
            </span>

        </a>


    </nav>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="header">


        <div>

            <h1>

                📚 Booking Management

            </h1>


            <p>

                Manage student bookings,
                assign teachers and schedule lessons.

            </p>

        </div>


        <div class="admin">


            <div class="admin-icon">

                👤

            </div>


            <div>


                <strong>

                    <?= h(
                        $_SESSION[
                            'admin_name'
                        ]
                        ??
                        'Administrator'
                    ) ?>

                </strong>


                <div
                    style="
                        color:#98a2b3;
                        font-size:10px;
                        margin-top:3px;
                    "
                >

                    Administrator

                </div>


            </div>


        </div>


    </div>



    <!-- ALERT -->

    <?php if (
        $message !== ''
    ): ?>


        <div
            class="
                alert
                <?= h(
                    $messageType
                ) ?>
            "
        >

            <?= h(
                $message
            ) ?>

        </div>


    <?php endif; ?>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat">

            <div class="stat-top">

                <strong>
                    Bookings
                </strong>

                <div class="stat-icon">
                    📚
                </div>

            </div>

            <div class="stat-number">
                <?= $totalBookings ?>
            </div>

            <div class="stat-label">
                Total bookings
            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Paid
                </strong>

                <div class="stat-icon">
                    💳
                </div>

            </div>

            <div class="stat-number">
                <?= $paidBookings ?>
            </div>

            <div class="stat-label">
                Paid bookings
            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Pending
                </strong>

                <div class="stat-icon">
                    ⏳
                </div>

            </div>

            <div class="stat-number">
                <?= $pendingBookings ?>
            </div>

            <div class="stat-label">
                Awaiting payment
            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Assigned
                </strong>

                <div class="stat-icon">
                    👨‍🏫
                </div>

            </div>

            <div class="stat-number">
                <?= $assignedBookings ?>
            </div>

            <div class="stat-label">
                Teacher assigned
            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Unassigned
                </strong>

                <div class="stat-icon">
                    ⚠️
                </div>

            </div>

            <div class="stat-number">
                <?= $unassignedBookings ?>
            </div>

            <div class="stat-label">
                Need teacher
            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Completed
                </strong>

                <div class="stat-icon">
                    ✅
                </div>

            </div>

            <div class="stat-number">
                <?= $completedLessons ?>
            </div>

            <div class="stat-label">
                Completed lessons
            </div>

        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <section class="filter-card">


        <div class="filter-title">


            <h2>

                🔎 Find Bookings

            </h2>


            <span>

                Combine filters to narrow the results

            </span>


        </div>



        <form
            method="GET"
            action=""
        >


            <div class="filters">


                <input
                    type="text"
                    name="search"
                    value="<?= h(
                        $search
                    ) ?>"
                    placeholder="Search student, email, phone, reference or subject..."
                >


                <select
                    name="curriculum"
                >

                    <option value="">

                        All Curricula

                    </option>


                    <?php foreach (
                        $curricula
                        as $curriculum
                    ): ?>

                        <option
                            value="<?= h(
                                $curriculum
                            ) ?>"
                            <?= (
                                $curriculumFilter
                                ===
                                $curriculum
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= h(
                                $curriculum
                            ) ?>

                        </option>

                    <?php endforeach; ?>


                </select>



                <select
                    name="payment"
                >

                    <option value="">

                        All Payments

                    </option>


                    <option
                        value="paid"
                        <?= $paymentFilter === 'paid'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Paid

                    </option>


                    <option
                        value="pending"
                        <?= $paymentFilter === 'pending'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Pending

                    </option>


                </select>



                <select
                    name="assignment"
                >

                    <option value="">

                        All Assignments

                    </option>


                    <option
                        value="assigned"
                        <?= $assignmentFilter === 'assigned'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Teacher Assigned

                    </option>


                    <option
                        value="unassigned"
                        <?= $assignmentFilter === 'unassigned'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Unassigned

                    </option>


                </select>



                <select
                    name="lesson_status"
                >

                    <option value="">

                        All Lesson Status

                    </option>


                    <option
                        value="Scheduled"
                        <?= strtolower(
                            $lessonFilter
                        ) === 'scheduled'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Scheduled

                    </option>


                    <option
                        value="Completed"
                        <?= strtolower(
                            $lessonFilter
                        ) === 'completed'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Completed

                    </option>


                    <option
                        value="Cancelled"
                        <?= strtolower(
                            $lessonFilter
                        ) === 'cancelled'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Cancelled

                    </option>


                </select>



                <button
                    type="submit"
                    class="filter-btn"
                >

                    Filter

                </button>


            </div>


        </form>


        <?php if (
            $search !== ''
            ||
            $curriculumFilter !== ''
            ||
            $paymentFilter !== ''
            ||
            $assignmentFilter !== ''
            ||
            $lessonFilter !== ''
        ): ?>


            <a
                href="booking.php"
                class="clear-btn"
            >

                ✕ Clear Filters

            </a>


        <?php endif; ?>


    </section>



    <!-- =================================================
         BOOKINGS
    ================================================== -->

    <?php if (
        empty($bookings)
    ): ?>


        <div class="empty">


            <div class="empty-icon">

                📚

            </div>


            <h3>

                No bookings found

            </h3>


            <p>

                There are no bookings matching
                your current filters.

            </p>


        </div>


    <?php else: ?>


        <?php foreach (
            $bookings
            as $booking
        ): ?>


            <?php


            $studentName =
                trim(
                    $booking[
                        'student_name'
                    ]
                    ??
                    'Student'
                );


            $words =
                preg_split(
                    '/\s+/',
                    $studentName
                );


            $initials = '';


            foreach (
                $words as $word
            ) {

                if (
                    $word === ''
                ) {
                    continue;
                }


                $initials .=
                    strtoupper(
                        substr(
                            $word,
                            0,
                            1
                        )
                    );


                if (
                    strlen(
                        $initials
                    ) >= 2
                ) {
                    break;
                }

            }


            if (
                $initials === ''
            ) {
                $initials = 'ST';
            }


            $payment =
                strtolower(
                    trim(
                        $booking[
                            'payment_status'
                        ]
                        ??
                        ''
                    )
                );


            $paymentClass =
                (
                    $payment === 'paid'
                    ||
                    $payment === 'success'
                )
                    ? 'paid'
                    : 'pending';


            $isAssigned =
                !empty(
                    $booking[
                        'teacher_id'
                    ]
                );


            $lesson =
                strtolower(
                    trim(
                        $booking[
                            'lesson_status'
                        ]
                        ??
                        ''
                    )
                );


            if (
                $lesson === 'completed'
            ) {

                $lessonClass =
                    'completed';

            }

            elseif (
                $lesson === 'cancelled'
            ) {

                $lessonClass =
                    'cancelled';

            }

            else {

                $lessonClass =
                    'scheduled';

            }


            ?>


            <section class="booking-card">


                <!-- BOOKING HEADER -->

                <div class="booking-top">


                    <div class="student">


                        <div class="student-avatar">

                            <?= h(
                                $initials
                            ) ?>

                        </div>


                        <div>


                            <h3
                                class="student-name"
                            >

                                <?= h(
                                    $studentName
                                ) ?>

                            </h3>


                            <div
                                class="student-meta"
                            >

                                Student ID:
                                <?= h(
                                    $booking[
                                        'student_id'
                                    ]
                                    ??
                                    'N/A'
                                ) ?>

                                &nbsp; • &nbsp;

                                📧
                                <?= h(
                                    $booking[
                                        'email'
                                    ]
                                ) ?>

                            </div>


                        </div>


                    </div>


                    <div
                        class="reference"
                    >

                        Booking Reference

                        <br>

                        <strong>

                            <?= h(
                                $booking[
                                    'booking_reference'
                                ]
                                ??
                                'N/A'
                            ) ?>

                        </strong>

                    </div>


                </div>



                <!-- BOOKING INFORMATION -->

                <div class="booking-grid">


                    <div class="info">


                        <div class="info-label">

                            Curriculum

                        </div>


                        <div class="info-value">

                            <?= h(
                                $booking[
                                    'curriculum'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Class / Year

                        </div>


                        <div class="info-value">

                            <?= h(
                                $booking[
                                    'class_year'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Subject

                        </div>


                        <div class="info-value">

                            <?= h(
                                $booking[
                                    'subjects'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Amount

                        </div>


                        <div class="info-value">

                            GHS
                            <?= number_format(
                                (float)(
                                    $booking[
                                        'amount'
                                    ]
                                    ??
                                    0
                                ),
                                2
                            ) ?>

                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Payment

                        </div>


                        <div class="info-value">


                            <span
                                class="
                                    badge
                                    <?= $paymentClass ?>
                                "
                            >

                                <?= (
                                    $payment ===
                                    'paid'
                                    ||
                                    $payment ===
                                    'success'
                                )
                                    ? '✓ PAID'
                                    : '⏳ PENDING'
                                ?>

                            </span>


                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Teacher

                        </div>


                        <div class="info-value">


                            <?php if (
                                $isAssigned
                            ): ?>


                                <span
                                    class="
                                        badge
                                        assigned
                                    "
                                >

                                    👨‍🏫
                                    <?= h(
                                        $booking[
                                            'teacher_name'
                                        ]
                                    ) ?>

                                </span>


                            <?php else: ?>


                                <span
                                    class="
                                        badge
                                        unassigned
                                    "
                                >

                                    Not Assigned

                                </span>


                            <?php endif; ?>


                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Lesson Date

                        </div>


                        <div class="info-value">

                            <?php if (
                                !empty(
                                    $booking[
                                        'lesson_date'
                                    ]
                                )
                            ): ?>


                                📅
                                <?= h(
                                    date(
                                        'd M Y',
                                        strtotime(
                                            $booking[
                                                'lesson_date'
                                            ]
                                        )
                                    )
                                ) ?>


                            <?php else: ?>


                                Not scheduled

                            <?php endif; ?>


                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Lesson Time

                        </div>


                        <div class="info-value">

                            <?php if (
                                !empty(
                                    $booking[
                                        'lesson_time'
                                    ]
                                )
                            ): ?>


                                🕐
                                <?= h(
                                    date(
                                        'h:i A',
                                        strtotime(
                                            $booking[
                                                'lesson_time'
                                            ]
                                        )
                                    )
                                ) ?>


                            <?php else: ?>


                                Not set

                            <?php endif; ?>


                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Assignment

                        </div>


                        <div class="info-value">


                            <?php if (
                                $isAssigned
                            ): ?>


                                <span
                                    class="
                                        badge
                                        assigned
                                    "
                                >

                                    Assigned

                                </span>


                            <?php else: ?>


                                <span
                                    class="
                                        badge
                                        unassigned
                                    "
                                >

                                    Unassigned

                                </span>


                            <?php endif; ?>


                        </div>


                    </div>



                    <div class="info">


                        <div class="info-label">

                            Lesson Status

                        </div>


                        <div class="info-value">


                            <span
                                class="
                                    badge
                                    <?= $lessonClass ?>
                                "
                            >

                                <?php

                                if (
                                    $lesson ===
                                    'completed'
                                ) {

                                    echo '✓ Completed';

                                }

                                elseif (
                                    $lesson ===
                                    'cancelled'
                                ) {

                                    echo '✕ Cancelled';

                                }

                                else {

                                    echo '● Scheduled';

                                }

                                ?>

                            </span>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                     TEACHER ASSIGNMENT
                ================================================== -->

                <div class="assignment">


                    <div
                        class="assignment-title"
                    >

                        <h3>

                            👨‍🏫
                            Teacher Assignment &
                            Lesson Scheduling

                        </h3>

                    </div>



                    <?php if (
                        $isAssigned
                    ): ?>


                        <div
                            class="teacher-current"
                        >


                            <span>

                                Currently assigned teacher:

                            </span>


                            <strong>

                                <?= h(
                                    $booking[
                                        'teacher_name'
                                    ]
                                ) ?>

                            </strong>


                        </div>


                    <?php endif; ?>



                    <form
                        method="POST"
                        action=""
                        class="assignment-form"
                    >


                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?= h(
                                $booking[
                                    'id'
                                ]
                            ) ?>"
                        >


                        <input
                            type="hidden"
                            name="assign_booking"
                            value="1"
                        >


                        <!-- TEACHER -->

                        <div class="form-field">


                            <label>

                                Select Teacher

                            </label>


                            <select
                                name="teacher_id"
                                required
                            >


                                <option value="">

                                    Select a teacher...

                                </option>


                                <?php foreach (
                                    $teachers
                                    as $teacher
                                ): ?>


                                    <option
                                        value="<?= h(
                                            $teacher[
                                                'teacher_id'
                                            ]
                                        ) ?>"
                                        <?= (
                                            $booking[
                                                'teacher_id'
                                            ]
                                            ==
                                            $teacher[
                                                'teacher_id'
                                            ]
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= h(
                                            $teacher[
                                                'teacher_name'
                                            ]
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $teacher[
                                                    'subjects'
                                                ]
                                            )
                                        ): ?>

                                            —
                                            <?= h(
                                                $teacher[
                                                    'subjects'
                                                ]
                                            ) ?>

                                        <?php endif; ?>


                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>



                        <!-- DATE -->

                        <div class="form-field">


                            <label>

                                Lesson Date

                            </label>


                            <input
                                type="date"
                                name="lesson_date"
                                value="<?= !empty(
                                    $booking[
                                        'lesson_date'
                                    ]
                                )
                                    ? h(
                                        $booking[
                                            'lesson_date'
                                        ]
                                    )
                                    : ''
                                ?>"
                                min="<?= date(
                                    'Y-m-d'
                                ) ?>"
                                required
                            >


                        </div>



                        <!-- TIME -->

                        <div class="form-field">


                            <label>

                                Lesson Time

                            </label>


                            <input
                                type="time"
                                name="lesson_time"
                                value="<?= !empty(
                                    $booking[
                                        'lesson_time'
                                    ]
                                )
                                    ? h(
                                        $booking[
                                            'lesson_time'
                                        ]
                                    )
                                    : ''
                                ?>"
                                required
                            >


                        </div>



                        <!-- BUTTON -->

                        <div
                            class="form-field"
                        >

                            <label>

                                &nbsp;

                            </label>


                            <button
                                type="submit"
                                class="assign-btn"
                            >

                                <?= $isAssigned
                                    ? '↻ Update Schedule'
                                    : '✓ Assign Teacher'
                                ?>

                            </button>


                        </div>


                    </form>



                    <!-- ACTIONS -->

                    <div
                        class="actions"
                    >


                        <?php if (
                            $isAssigned
                            &&
                            $lesson !==
                            'completed'
                        ): ?>


                            <form
                                method="POST"
                                action=""
                                onsubmit="
                                    return confirm(
                                        'Mark this lesson as completed?'
                                    );
                                "
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?= h(
                                        $booking[
                                            'id'
                                        ]
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    name="complete_lesson"
                                    class="
                                        action-btn
                                        complete-btn
                                    "
                                >

                                    ✓ Mark Lesson
                                    Completed

                                </button>


                            </form>


                        <?php endif; ?>



                        <?php if (
                            $isAssigned
                        ): ?>


                            <form
                                method="POST"
                                action=""
                                onsubmit="
                                    return confirm(
                                        'Remove this teacher assignment?'
                                    );
                                "
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?= h(
                                        $booking[
                                            'id'
                                        ]
                                    ) ?>"
                                >


                                <button
                                    type="submit"
                                    name="remove_assignment"
                                    class="
                                        action-btn
                                        remove-btn
                                    "
                                >

                                    ✕ Remove Assignment

                                </button>


                            </form>


                        <?php endif; ?>


                    </div>


                </div>


            </section>


        <?php endforeach; ?>


    <?php endif; ?>



    <div class="footer">

        NISEL ONLINE EDUCATION
        ·
        Administrator Booking Management

    </div>


</main>


</body>

</html>
