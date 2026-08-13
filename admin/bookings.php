<?php

session_start();

require "../admin_auth.php";
require "../config/db.php";

/*
=========================================================
NISEL ONLINE EDUCATION
ADMIN BOOKING MANAGEMENT

This page controls:

1. View bookings
2. Assign teacher
3. Unassign teacher
4. Schedule lesson
5. Change lesson status
6. Create live classroom room code
7. View live classroom
=========================================================
*/


/*
=========================================================
DATABASE ERROR MODE
=========================================================
*/

if ($pdo instanceof PDO) {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );
}


/*
=========================================================
HELPER
=========================================================
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
=========================================================
ADMIN INFORMATION
=========================================================
*/

$adminName =
    $_SESSION['admin_name']
    ?? 'Administrator';


/*
=========================================================
MESSAGES
=========================================================
*/

$message = "";
$messageType = "success";


/*
=========================================================
CSRF TOKEN
=========================================================
*/

if (
    empty(
        $_SESSION['booking_csrf']
    )
) {

    $_SESSION['booking_csrf'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['booking_csrf'];


/*
=========================================================
PROCESS POST REQUEST
=========================================================
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    /*
    -----------------------------------------------------
    CSRF CHECK
    -----------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';

    if (
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $message =
            "Invalid security token. Please refresh the page.";

        $messageType =
            "error";

    } else {

        /*
        =================================================
        ACTION
        =================================================
        */

        $action =
            $_POST['action']
            ?? '';


        /*
        =================================================
        GET BOOKING ID
        =================================================
        */

        $bookingId =
            isset($_POST['booking_id'])
                ? (int)$_POST['booking_id']
                : 0;


        /*
        =================================================
        VALIDATE BOOKING
        =================================================
        */

        if ($bookingId <= 0) {

            $message =
                "Invalid booking ID.";

            $messageType =
                "error";

        } else {


            /*
            =================================================
            ASSIGN TEACHER
            =================================================
            */

            if (
                $action ===
                'assign_teacher'
            ) {

                $teacherId =
                    trim(
                        $_POST['teacher_id']
                        ?? ''
                    );


                if ($teacherId === '') {

                    $message =
                        "Please select a teacher.";

                    $messageType =
                        "error";

                } else {

                    try {

                        /*
                        -------------------------------------
                        GET TEACHER
                        -------------------------------------
                        */

                        $teacherStmt =
                            $pdo->prepare("
                                SELECT
                                    id,
                                    teacher_id,
                                    teacher_name,
                                    phone,
                                    email,
                                    status
                                FROM teachers
                                WHERE teacher_id = ?
                                LIMIT 1
                            ");

                        $teacherStmt->execute([
                            $teacherId
                        ]);

                        $teacher =
                            $teacherStmt->fetch();


                        if (!$teacher) {

                            throw new Exception(
                                "Selected teacher was not found."
                            );
                        }


                        /*
                        -------------------------------------
                        CHECK TEACHER STATUS
                        -------------------------------------
                        */

                        $teacherStatus =
                            strtolower(
                                trim(
                                    $teacher['status']
                                    ?? ''
                                )
                            );


                        if (
                            !in_array(
                                $teacherStatus,
                                [
                                    'active',
                                    'approved'
                                ],
                                true
                            )
                        ) {

                            throw new Exception(
                                "The selected teacher is not active."
                            );
                        }


                        /*
                        -------------------------------------
                        GET BOOKING
                        -------------------------------------
                        */

                        $bookingStmt =
                            $pdo->prepare("
                                SELECT
                                    id,
                                    booking_reference,
                                    payment_status,
                                    lesson_status
                                FROM bookings
                                WHERE id = ?
                                LIMIT 1
                            ");

                        $bookingStmt->execute([
                            $bookingId
                        ]);

                        $booking =
                            $bookingStmt->fetch();


                        if (!$booking) {

                            throw new Exception(
                                "Booking was not found."
                            );
                        }


                        /*
                        -------------------------------------
                        PAYMENT CHECK
                        -------------------------------------
                        */

                        $paymentStatus =
                            strtolower(
                                trim(
                                    $booking['payment_status']
                                    ?? ''
                                )
                            );


                        /*
                        Teacher assignment is allowed
                        even if payment is pending.

                        However, the classroom will only
                        become available after payment.
                        */


                        /*
                        -------------------------------------
                        UPDATE BOOKING
                        -------------------------------------
                        */

                        $update =
                            $pdo->prepare("
                                UPDATE bookings

                                SET
                                    teacher_id = ?,
                                    teacher_name = ?,
                                    assignment_status = 'Assigned'

                                WHERE id = ?
                            ");

                        $update->execute([
                            $teacher['teacher_id'],
                            $teacher['teacher_name'],
                            $bookingId
                        ]);


                        /*
                        -------------------------------------
                        SUCCESS
                        -------------------------------------
                        */

                        $message =
                            "Teacher "
                            . $teacher['teacher_name']
                            . " has been assigned successfully.";

                        $messageType =
                            "success";

                    } catch (
                        Exception $e
                    ) {

                        $message =
                            "Teacher assignment failed: "
                            . $e->getMessage();

                        $messageType =
                            "error";
                    }
                }
            }


            /*
            =================================================
            UNASSIGN TEACHER
            =================================================
            */

            elseif (
                $action ===
                'unassign_teacher'
            ) {

                try {

                    $stmt =
                        $pdo->prepare("
                            UPDATE bookings

                            SET
                                teacher_id = NULL,
                                teacher_name = NULL,
                                assignment_status = 'Pending'

                            WHERE id = ?
                        ");

                    $stmt->execute([
                        $bookingId
                    ]);


                    /*
                    -----------------------------------------
                    ALSO RESET LIVE CLASS
                    -----------------------------------------
                    */

                    $stmt =
                        $pdo->prepare("
                            UPDATE bookings

                            SET
                                live_room_code = NULL,
                                live_status = 'waiting',
                                live_started_at = NULL,
                                live_ended_at = NULL

                            WHERE id = ?
                        ");

                    $stmt->execute([
                        $bookingId
                    ]);


                    $message =
                        "Teacher assignment removed successfully.";

                    $messageType =
                        "success";

                } catch (
                    PDOException $e
                ) {

                    $message =
                        "Unable to remove teacher assignment.";

                    $messageType =
                        "error";
                }
            }


            /*
            =================================================
            UPDATE LESSON
            =================================================
            */

            elseif (
                $action ===
                'update_lesson'
            ) {

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

                $lessonStatus =
                    trim(
                        $_POST['lesson_status']
                        ?? 'Scheduled'
                    );


                /*
                -----------------------------------------
                VALIDATE LESSON STATUS
                -----------------------------------------
                */

                $allowedStatuses = [
                    'Scheduled',
                    'Completed',
                    'Cancelled'
                ];


                if (
                    !in_array(
                        $lessonStatus,
                        $allowedStatuses,
                        true
                    )
                ) {

                    $message =
                        "Invalid lesson status.";

                    $messageType =
                        "error";

                } else {

                    try {

                        /*
                        -------------------------------------
                        CHECK TEACHER ASSIGNMENT
                        -------------------------------------
                        */

                        $check =
                            $pdo->prepare("
                                SELECT
                                    id,
                                    teacher_id
                                FROM bookings
                                WHERE id = ?
                                LIMIT 1
                            ");

                        $check->execute([
                            $bookingId
                        ]);

                        $booking =
                            $check->fetch();


                        if (!$booking) {

                            throw new Exception(
                                "Booking was not found."
                            );
                        }


                        /*
                        -------------------------------------
                        UPDATE
                        -------------------------------------
                        */

                        $update =
                            $pdo->prepare("
                                UPDATE bookings

                                SET
                                    lesson_date = ?,
                                    lesson_time = ?,
                                    lesson_status = ?

                                WHERE id = ?
                            ");

                        $update->execute([
                            $lessonDate !== ''
                                ? $lessonDate
                                : null,

                            $lessonTime !== ''
                                ? $lessonTime
                                : null,

                            $lessonStatus,

                            $bookingId
                        ]);


                        /*
                        -------------------------------------
                        IF CANCELLED
                        END LIVE CLASS
                        -------------------------------------
                        */

                        if (
                            strtolower(
                                $lessonStatus
                            ) ===
                            'cancelled'
                        ) {

                            $endLive =
                                $pdo->prepare("
                                    UPDATE bookings

                                    SET
                                        live_status = 'ended',
                                        live_ended_at = NOW()

                                    WHERE id = ?
                                ");

                            $endLive->execute([
                                $bookingId
                            ]);
                        }


                        $message =
                            "Lesson schedule updated successfully.";

                        $messageType =
                            "success";

                    } catch (
                        Exception $e
                    ) {

                        $message =
                            "Unable to update lesson: "
                            . $e->getMessage();

                        $messageType =
                            "error";
                    }
                }
            }


            /*
            =================================================
            CREATE LIVE ROOM
            =================================================
            */

            elseif (
                $action ===
                'create_live_room'
            ) {

                try {

                    /*
                    -----------------------------------------
                    GET BOOKING
                    -----------------------------------------
                    */

                    $stmt =
                        $pdo->prepare("
                            SELECT
                                id,
                                payment_status,
                                lesson_status,
                                teacher_id,
                                live_room_code
                            FROM bookings
                            WHERE id = ?
                            LIMIT 1
                        ");

                    $stmt->execute([
                        $bookingId
                    ]);

                    $booking =
                        $stmt->fetch();


                    if (!$booking) {

                        throw new Exception(
                            "Booking not found."
                        );
                    }


                    /*
                    -----------------------------------------
                    TEACHER CHECK
                    -----------------------------------------
                    */

                    if (
                        empty(
                            $booking['teacher_id']
                        )
                    ) {

                        throw new Exception(
                            "A teacher must be assigned before creating a classroom."
                        );
                    }


                    /*
                    -----------------------------------------
                    PAYMENT CHECK
                    -----------------------------------------
                    */

                    $paymentStatus =
                        strtolower(
                            trim(
                                $booking['payment_status']
                                ?? ''
                            )
                        );


                    if (
                        !in_array(
                            $paymentStatus,
                            [
                                'paid',
                                'success'
                            ],
                            true
                        )
                    ) {

                        throw new Exception(
                            "The booking must be paid before the live classroom can be created."
                        );
                    }


                    /*
                    -----------------------------------------
                    LESSON STATUS CHECK
                    -----------------------------------------
                    */

                    if (
                        strtolower(
                            trim(
                                $booking['lesson_status']
                                ?? ''
                            )
                        )
                        ===
                        'cancelled'
                    ) {

                        throw new Exception(
                            "A classroom cannot be created for a cancelled lesson."
                        );
                    }


                    /*
                    -----------------------------------------
                    EXISTING ROOM
                    -----------------------------------------
                    */

                    if (
                        !empty(
                            $booking['live_room_code']
                        )
                    ) {

                        $message =
                            "This booking already has a live classroom.";

                        $messageType =
                            "success";

                    } else {

                        /*
                        -------------------------------------
                        GENERATE ROOM
                        -------------------------------------
                        */

                        do {

                            $roomCode =
                                'NISEL-'
                                .
                                strtoupper(
                                    bin2hex(
                                        random_bytes(8)
                                    )
                                );


                            $checkRoom =
                                $pdo->prepare("
                                    SELECT id
                                    FROM bookings
                                    WHERE live_room_code = ?
                                    LIMIT 1
                                ");

                            $checkRoom->execute([
                                $roomCode
                            ]);

                        } while (
                            $checkRoom->fetch()
                        );


                        /*
                        -------------------------------------
                        SAVE ROOM
                        -------------------------------------
                        */

                        $update =
                            $pdo->prepare("
                                UPDATE bookings

                                SET
                                    live_room_code = ?,
                                    live_status = 'waiting',
                                    live_started_at = NULL,
                                    live_ended_at = NULL

                                WHERE id = ?
                            ");

                        $update->execute([
                            $roomCode,
                            $bookingId
                        ]);


                        $message =
                            "NISEL live classroom created successfully.";

                        $messageType =
                            "success";
                    }

                } catch (
                    Exception $e
                ) {

                    $message =
                        "Unable to create classroom: "
                        . $e->getMessage();

                    $messageType =
                        "error";
                }
            }


            /*
            =================================================
            RESET LIVE ROOM
            =================================================
            */

            elseif (
                $action ===
                'reset_live_room'
            ) {

                try {

                    $stmt =
                        $pdo->prepare("
                            UPDATE bookings

                            SET
                                live_room_code = NULL,
                                live_status = 'waiting',
                                live_started_at = NULL,
                                live_ended_at = NULL

                            WHERE id = ?
                        ");

                    $stmt->execute([
                        $bookingId
                    ]);


                    $message =
                        "Live classroom reset successfully.";

                    $messageType =
                        "success";

                } catch (
                    PDOException $e
                ) {

                    $message =
                        "Unable to reset live classroom.";

                    $messageType =
                        "error";
                }
            }

        }
    }
}


/*
=========================================================
FILTERS
=========================================================
*/

$search =
    trim(
        $_GET['search']
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
        $_GET['lesson']
        ?? ''
    );


/*
=========================================================
GET TEACHERS
=========================================================
*/

$teacherStmt =
    $pdo->prepare("
        SELECT
            id,
            teacher_id,
            teacher_name,
            phone,
            email,
            status,
            subjects,
            curriculum
        FROM teachers

        WHERE
            LOWER(status) IN
            ('active','approved')

        ORDER BY teacher_name ASC
    ");

$teacherStmt->execute();

$teachers =
    $teacherStmt->fetchAll();


/*
=========================================================
BUILD BOOKING QUERY
=========================================================
*/

$sql = "

    SELECT

        b.id,

        b.booking_reference,

        b.student_id,

        b.student_name,

        b.email,

        b.phone,

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

        b.lesson_status,

        b.live_room_code,

        b.live_status,

        b.live_started_at,

        b.live_ended_at,

        t.teacher_name AS assigned_teacher_name,

        t.phone AS teacher_phone,

        t.email AS teacher_email

    FROM bookings b

    LEFT JOIN teachers t

        ON b.teacher_id =
           t.teacher_id

    WHERE 1 = 1
";


$params = [];


/*
=========================================================
SEARCH
=========================================================
*/

if ($search !== '') {

    $sql .= "

        AND (

            b.booking_reference LIKE ?

            OR b.student_name LIKE ?

            OR b.email LIKE ?

            OR b.phone LIKE ?

            OR b.subjects LIKE ?

            OR b.teacher_name LIKE ?

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

    $params[] =
        $searchValue;
}


/*
=========================================================
PAYMENT FILTER
=========================================================
*/

if (
    $paymentFilter !== ''
) {

    $sql .= "
        AND LOWER(
            TRIM(
                b.payment_status
            )
        ) = LOWER(?)
    ";

    $params[] =
        $paymentFilter;
}


/*
=========================================================
ASSIGNMENT FILTER
=========================================================
*/

if (
    $assignmentFilter !== ''
) {

    if (
        $assignmentFilter ===
        'unassigned'
    ) {

        $sql .= "

            AND (
                b.teacher_id IS NULL
                OR b.teacher_id = ''
            )

        ";

    } elseif (
        $assignmentFilter ===
        'assigned'
    ) {

        $sql .= "

            AND b.teacher_id IS NOT NULL
            AND b.teacher_id <> ''

        ";
    }
}


/*
=========================================================
LESSON FILTER
=========================================================
*/

if (
    $lessonFilter !== ''
) {

    $sql .= "
        AND LOWER(
            TRIM(
                b.lesson_status
            )
        ) = LOWER(?)
    ";

    $params[] =
        $lessonFilter;
}


/*
=========================================================
ORDER
=========================================================
*/

$sql .= "

    ORDER BY

        CASE

            WHEN
                b.lesson_date = CURDATE()
            THEN 0

            WHEN
                b.lesson_date > CURDATE()
            THEN 1

            WHEN
                b.lesson_date IS NULL
            THEN 2

            ELSE 3

        END,

        b.lesson_date ASC,

        b.lesson_time ASC,

        b.id DESC

";


/*
=========================================================
GET BOOKINGS
=========================================================
*/

$bookingStmt =
    $pdo->prepare($sql);

$bookingStmt->execute(
    $params
);

$bookings =
    $bookingStmt->fetchAll();


/*
=========================================================
STATISTICS
=========================================================
*/

$totalBookings =
    count($bookings);

$paidBookings = 0;

$assignedBookings = 0;

$unassignedBookings = 0;

$todayBookings = 0;

$liveBookings = 0;


foreach (
    $bookings
    as $booking
) {

    $payment =
        strtolower(
            trim(
                $booking['payment_status']
                ?? ''
            )
        );


    if (
        in_array(
            $payment,
            [
                'paid',
                'success'
            ],
            true
        )
    ) {

        $paidBookings++;
    }


    if (
        !empty(
            $booking['teacher_id']
        )
    ) {

        $assignedBookings++;

    } else {

        $unassignedBookings++;
    }


    if (
        !empty(
            $booking['lesson_date']
        )
        &&
        $booking['lesson_date']
        ===
        date('Y-m-d')
    ) {

        $todayBookings++;
    }


    if (
        strtolower(
            trim(
                $booking['live_status']
                ?? ''
            )
        )
        ===
        'live'
    ) {

        $liveBookings++;
    }
}


/*
=========================================================
HTML
=========================================================
*/

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


.menu a:hover,
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
   TOPBAR
===================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 25px;

    display: flex;

    justify-content:
        space-between;

    align-items: center;

    gap: 20px;
}


.topbar h1 {

    margin: 0;

    color: #003366;

    font-size: 25px;
}


.admin {

    color: #555;

    font-size: 14px;
}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 15px 18px;

    border-radius: 9px;

    margin-bottom: 20px;

    font-weight: 600;
}


.message.success {

    background: #dff5e8;

    color: #126b3a;

    border: 1px solid #bce8ce;
}


.message.error {

    background: #fde5e5;

    color: #a31f1f;

    border: 1px solid #f5c1c1;
}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(6, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.stat {

    background: white;

    padding: 20px;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);
}


.stat .number {

    font-size: 27px;

    font-weight: bold;

    color: #003366;

    margin-bottom: 5px;
}


.stat .label {

    font-size: 12px;

    color: #777;

    text-transform:
        uppercase;
}


/* =====================================================
   FILTER
===================================================== */

.filter-box {

    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 25px;
}


.filter-form {

    display: grid;

    grid-template-columns:
        2fr 1fr 1fr 1fr auto;

    gap: 10px;
}


input,
select {

    width: 100%;

    padding: 11px 12px;

    border:
        1px solid #d5dce3;

    border-radius: 7px;

    font-size: 14px;

    background: white;
}


input:focus,
select:focus {

    outline: none;

    border-color: #0055a5;
}


.filter-button {

    border: none;

    background: #003366;

    color: white;

    padding:
        11px 18px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: bold;
}


.clear-button {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    background: #eef2f5;

    color: #333;

    padding:
        11px 15px;

    border-radius: 7px;

    text-decoration: none;

    font-weight: bold;
}


/* =====================================================
   TABLE
===================================================== */

.table-card {

    background: white;

    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.06);
}


.table-header {

    padding: 20px;

    border-bottom:
        1px solid #e7edf2;

    display: flex;

    justify-content:
        space-between;

    align-items: center;
}


.table-header h2 {

    margin: 0;

    color: #003366;

    font-size: 19px;
}


.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse:
        collapse;

    min-width: 1450px;
}


th {

    background: #003366;

    color: white;

    padding: 13px 10px;

    text-align: left;

    font-size: 12px;

    white-space: nowrap;
}


td {

    padding: 13px 10px;

    border-bottom:
        1px solid #edf0f2;

    vertical-align: top;

    font-size: 13px;
}


tr:hover td {

    background: #f8fbfd;
}


/* =====================================================
   STUDENT
===================================================== */

.student-name {

    font-weight: bold;

    color: #003366;

    margin-bottom: 3px;
}


.student-contact {

    font-size: 11px;

    color: #777;

    margin-bottom: 2px;
}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

    white-space: nowrap;
}


.paid {

    background: #dff5e8;

    color: #126b3a;
}


.pending {

    background: #fff3cd;

    color: #856404;
}


.assigned {

    background: #e2efff;

    color: #145a9c;
}


.not-assigned {

    background: #f1f1f1;

    color: #777;
}


.scheduled {

    background: #e2efff;

    color: #145a9c;
}


.completed {

    background: #dff5e8;

    color: #126b3a;
}


.cancelled {

    background: #fde5e5;

    color: #a31f1f;
}


.live-badge {

    background: #dff5e8;

    color: #126b3a;
}


.waiting-badge {

    background: #fff3cd;

    color: #856404;
}


.ended-badge {

    background: #e5e7eb;

    color: #374151;
}


/* =====================================================
   TEACHER
===================================================== */

.teacher-name {

    font-weight: bold;

    color: #003366;

    margin-bottom: 4px;
}


.teacher-phone {

    color: #777;

    font-size: 11px;
}


/* =====================================================
   FORMS
===================================================== */

.action-form {

    margin-bottom: 7px;
}


.action-form:last-child {

    margin-bottom: 0;
}


.small-select {

    min-width: 180px;

    margin-bottom: 6px;
}


.date-input {

    width: 145px;

    margin-bottom: 5px;
}


.time-input {

    width: 120px;

    margin-bottom: 5px;
}


.status-select {

    width: 145px;

    margin-bottom: 6px;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn {

    display: inline-block;

    border: none;

    padding:
        8px 11px;

    border-radius: 6px;

    font-size: 11px;

    font-weight: bold;

    cursor: pointer;

    text-decoration: none;

    white-space: nowrap;
}


.btn-primary {

    background: #003366;

    color: white;
}


.btn-primary:hover {

    background: #0055a5;
}


.btn-success {

    background: #198754;

    color: white;
}


.btn-danger {

    background: #dc3545;

    color: white;
}


.btn-warning {

    background: #e0a800;

    color: white;
}


.btn-secondary {

    background: #687684;

    color: white;
}


.btn-disabled {

    background: #e9ecef;

    color: #999;

    cursor: not-allowed;
}


/* =====================================================
   ROOM CODE
===================================================== */

.room-code {

    display: inline-block;

    background: #f0f6fb;

    border:
        1px solid #d5e5f1;

    color: #003366;

    padding:
        5px 8px;

    border-radius: 5px;

    font-family:
        monospace;

    font-size: 10px;

    margin-bottom: 6px;
}


.no-room {

    color: #999;

    font-size: 11px;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    padding: 60px;

    text-align: center;

    color: #777;
}


.empty-icon {

    font-size: 45px;

    margin-bottom: 10px;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(3, 1fr);
    }

}


@media(max-width:800px) {

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

        flex-direction:
            column;

        align-items:
            flex-start;
    }


    .filter-form {

        grid-template-columns:
            1fr;
    }


    .stats {

        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media(max-width:500px) {

    .stats {

        grid-template-columns:
            1fr;
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
            href="students.php"
        >

            👨‍🎓 Students

        </a>


        <a
            href="teachers.php"
        >

            👨‍🏫 Teachers

        </a>


        <a
            href="bookings.php"
            class="active"
        >

            📚 Bookings

        </a>


        <a
            href="payments.php"
        >

            💳 Payments

        </a>


        <a
            href="teacher_applications.php"
        >

            📝 Teacher Applications

        </a>


        <a
            href="reports.php"
        >

            📊 Reports

        </a>


        <a
            href="settings.php"
        >

            ⚙️ Settings

        </a>


        <a
            href="logout.php"
        >

            🚪 Logout

        </a>

    </div>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOPBAR -->

    <div class="topbar">

        <h1>

            📚 Booking Management

        </h1>


        <div class="admin">

            Administrator:

            <strong>

                <?= h($adminName) ?>

            </strong>

        </div>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ''): ?>

        <div
            class="message
            <?= $messageType === 'error'
                ? 'error'
                : 'success'
            ?>"
        >

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat">

            <div class="number">

                <?= $totalBookings ?>

            </div>

            <div class="label">

                Total Bookings

            </div>

        </div>


        <div class="stat">

            <div class="number">

                <?= $paidBookings ?>

            </div>

            <div class="label">

                Paid

            </div>

        </div>


        <div class="stat">

            <div class="number">

                <?= $assignedBookings ?>

            </div>

            <div class="label">

                Assigned

            </div>

        </div>


        <div class="stat">

            <div class="number">

                <?= $unassignedBookings ?>

            </div>

            <div class="label">

                Unassigned

            </div>

        </div>


        <div class="stat">

            <div class="number">

                <?= $todayBookings ?>

            </div>

            <div class="label">

                Today's Lessons

            </div>

        </div>


        <div class="stat">

            <div class="number">

                <?= $liveBookings ?>

            </div>

            <div class="label">

                Live Now

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTER
    ================================================== -->

    <div class="filter-box">

        <form
            method="GET"
            class="filter-form"
        >


            <input
                type="text"
                name="search"
                placeholder="
                    Search student,
                    booking reference,
                    email, phone or subject
                "
                value="<?= h($search) ?>"
            >


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
                    Assigned
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
                name="lesson"
            >

                <option value="">
                    All Lesson Status
                </option>

                <option
                    value="scheduled"
                    <?= $lessonFilter === 'scheduled'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Scheduled
                </option>

                <option
                    value="completed"
                    <?= $lessonFilter === 'completed'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Completed
                </option>

                <option
                    value="cancelled"
                    <?= $lessonFilter === 'cancelled'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Cancelled
                </option>

            </select>


            <div>

                <button
                    type="submit"
                    class="filter-button"
                >

                    Search

                </button>


                <a
                    href="bookings.php"
                    class="clear-button"
                >

                    Clear

                </a>

            </div>

        </form>

    </div>


    <!-- =================================================
         BOOKINGS
    ================================================== -->

    <div class="table-card">


        <div class="table-header">

            <h2>

                All NISEL Bookings

            </h2>


            <div>

                <?= $totalBookings ?>

                booking(s)

            </div>

        </div>


        <div class="table-wrapper">


            <?php if (
                count($bookings) > 0
            ): ?>


                <table>


                    <thead>

                    <tr>

                        <th>
                            Booking
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Curriculum
                        </th>

                        <th>
                            Class
                        </th>

                        <th>
                            Subject(s)
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Schedule
                        </th>

                        <th>
                            Lesson
                        </th>

                        <th>
                            Live Classroom
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $bookings
                        as $row
                    ): ?>


                        <?php

                        $payment =
                            strtolower(
                                trim(
                                    $row['payment_status']
                                    ?? ''
                                )
                            );


                        $lessonStatus =
                            strtolower(
                                trim(
                                    $row['lesson_status']
                                    ?? 'scheduled'
                                )
                            );


                        $liveStatus =
                            strtolower(
                                trim(
                                    $row['live_status']
                                    ?? 'waiting'
                                )
                            );


                        $isPaid =
                            in_array(
                                $payment,
                                [
                                    'paid',
                                    'success'
                                ],
                                true
                            );


                        $hasTeacher =
                            !empty(
                                $row['teacher_id']
                            );


                        ?>


                        <tr>


                            <!-- BOOKING -->

                            <td>

                                <strong>

                                    <?= h(
                                        $row['booking_reference']
                                    ) ?>

                                </strong>

                                <br>

                                <small
                                    style="
                                        color:#888;
                                    "
                                >

                                    ID:
                                    <?= (int)$row['id'] ?>

                                </small>

                            </td>


                            <!-- STUDENT -->

                            <td>

                                <div
                                    class="student-name"
                                >

                                    <?= h(
                                        $row['student_name']
                                    ) ?>

                                </div>


                                <div
                                    class="student-contact"
                                >

                                    📧
                                    <?= h(
                                        $row['email']
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $row['phone']
                                    )
                                ): ?>

                                    <div
                                        class="student-contact"
                                    >

                                        📞
                                        <?= h(
                                            $row['phone']
                                        ) ?>

                                    </div>

                                <?php endif; ?>


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
                                    ?? ''
                                ) ?>

                            </td>


                            <!-- SUBJECT -->

                            <td>

                                <?= h(
                                    $row['subjects']
                                ) ?>

                            </td>


                            <!-- PAYMENT -->

                            <td>

                                <?php if (
                                    $isPaid
                                ): ?>

                                    <span
                                        class="badge paid"
                                    >

                                        PAID

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge pending"
                                    >

                                        <?= h(
                                            strtoupper(
                                                $row['payment_status']
                                                ?? 'PENDING'
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $row['amount']
                                    )
                                ): ?>

                                    <br>

                                    <small
                                        style="
                                            color:#777;
                                        "
                                    >

                                        GHS
                                        <?= number_format(
                                            (float)
                                            $row['amount'],
                                            2
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- TEACHER -->

                            <td>


                                <?php if (
                                    $hasTeacher
                                ): ?>

                                    <div
                                        class="teacher-name"
                                    >

                                        <?= h(
                                            $row['assigned_teacher_name']
                                            ??
                                            $row['teacher_name']
                                        ) ?>

                                    </div>


                                    <?php if (
                                        !empty(
                                            $row['teacher_phone']
                                        )
                                    ): ?>

                                        <div
                                            class="teacher-phone"
                                        >

                                            📞
                                            <?= h(
                                                $row['teacher_phone']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <span
                                        class="badge assigned"
                                    >

                                        ASSIGNED

                                    </span>


                                <?php else: ?>

                                    <span
                                        class="
                                            badge
                                            not-assigned
                                        "
                                    >

                                        NOT ASSIGNED

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- SCHEDULE -->

                            <td>


                                <form
                                    method="POST"
                                    class="action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update_lesson"
                                    >


                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= (int)$row['id'] ?>"
                                    >


                                    <input
                                        type="date"
                                        name="lesson_date"
                                        class="date-input"
                                        value="<?= h(
                                            $row['lesson_date']
                                            ?? ''
                                        ) ?>"
                                    >


                                    <input
                                        type="time"
                                        name="lesson_time"
                                        class="time-input"
                                        value="<?= h(
                                            $row['lesson_time']
                                            ?? ''
                                        ) ?>"
                                    >


                                    <select
                                        name="lesson_status"
                                        class="status-select"
                                    >

                                        <option
                                            value="Scheduled"
                                            <?= $lessonStatus === 'scheduled'
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            Scheduled

                                        </option>


                                        <option
                                            value="Completed"
                                            <?= $lessonStatus === 'completed'
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            Completed

                                        </option>


                                        <option
                                            value="Cancelled"
                                            <?= $lessonStatus === 'cancelled'
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            Cancelled

                                        </option>

                                    </select>


                                    <br>


                                    <button
                                        type="submit"
                                        class="
                                            btn
                                            btn-primary
                                        "
                                    >

                                        💾 Save

                                    </button>

                                </form>


                            </td>


                            <!-- LESSON STATUS -->

                            <td>


                                <?php if (
                                    $lessonStatus
                                    ===
                                    'completed'
                                ): ?>

                                    <span
                                        class="
                                            badge
                                            completed
                                        "
                                    >

                                        COMPLETED

                                    </span>


                                <?php elseif (
                                    $lessonStatus
                                    ===
                                    'cancelled'
                                ): ?>

                                    <span
                                        class="
                                            badge
                                            cancelled
                                        "
                                    >

                                        CANCELLED

                                    </span>


                                <?php else: ?>

                                    <span
                                        class="
                                            badge
                                            scheduled
                                        "
                                    >

                                        SCHEDULED

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- LIVE CLASSROOM -->

                            <td>


                                <?php if (
                                    !empty(
                                        $row['live_room_code']
                                    )
                                ): ?>


                                    <div
                                        class="room-code"
                                    >

                                        <?= h(
                                            $row['live_room_code']
                                        ) ?>

                                    </div>


                                    <br>


                                    <?php if (
                                        $liveStatus
                                        ===
                                        'live'
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                live-badge
                                            "
                                        >

                                            🔴 LIVE

                                        </span>


                                    <?php elseif (
                                        $liveStatus
                                        ===
                                        'ended'
                                    ): ?>

                                        <span
                                            class="
                                                badge
                                                ended-badge
                                            "
                                        >

                                            ENDED

                                        </span>


                                    <?php else: ?>

                                        <span
                                            class="
                                                badge
                                                waiting-badge
                                            "
                                        >

                                            WAITING

                                        </span>

                                    <?php endif; ?>


                                    <br><br>


                                    <?php if (
                                        $hasTeacher
                                        &&
                                        $isPaid
                                        &&
                                        $lessonStatus
                                        !==
                                        'cancelled'
                                    ): ?>

                                        <a
                                            href="../teacher/classroom.php?id=<?= (int)$row['id'] ?>"
                                            target="_blank"
                                            class="
                                                btn
                                                btn-success
                                            "
                                        >

                                            🎥 Open

                                        </a>

                                    <?php endif; ?>


                                <?php else: ?>


                                    <span
                                        class="no-room"
                                    >

                                        No classroom

                                    </span>


                                <?php endif; ?>


                            </td>


                            <!-- ACTIONS -->

                            <td>


                                <!-- ASSIGN TEACHER -->

                                <form
                                    method="POST"
                                    class="action-form"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="assign_teacher"
                                    >


                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= (int)$row['id'] ?>"
                                    >


                                    <select
                                        name="teacher_id"
                                        class="small-select"
                                        required
                                    >

                                        <option value="">

                                            Select Teacher

                                        </option>


                                        <?php foreach (
                                            $teachers
                                            as $teacher
                                        ): ?>

                                            <option
                                                value="<?= h(
                                                    $teacher['teacher_id']
                                                ) ?>"

                                                <?= (
                                                    $row['teacher_id']
                                                    ===
                                                    $teacher['teacher_id']
                                                )
                                                    ? 'selected'
                                                    : ''
                                                ?>
                                            >

                                                <?= h(
                                                    $teacher['teacher_name']
                                                ) ?>

                                            </option>

                                        <?php endforeach; ?>


                                    </select>


                                    <br>


                                    <button
                                        type="submit"
                                        class="
                                            btn
                                            btn-primary
                                        "
                                    >

                                        👨‍🏫
                                        Assign

                                    </button>

                                </form>


                                <!-- UNASSIGN -->

                                <?php if (
                                    $hasTeacher
                                ): ?>

                                    <form
                                        method="POST"
                                        class="action-form"
                                        onsubmit="
                                            return confirm(
                                                'Remove this teacher assignment?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h(
                                                $csrfToken
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="unassign_teacher"
                                        >


                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$row['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-danger
                                            "
                                        >

                                            ❌ Unassign

                                        </button>

                                    </form>

                                <?php endif; ?>


                                <!-- CREATE ROOM -->

                                <?php if (
                                    $hasTeacher
                                    &&
                                    $isPaid
                                    &&
                                    $lessonStatus
                                    !==
                                    'cancelled'
                                    &&
                                    empty(
                                        $row['live_room_code']
                                    )
                                ): ?>


                                    <form
                                        method="POST"
                                        class="action-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h(
                                                $csrfToken
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="create_live_room"
                                        >


                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$row['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-success
                                            "
                                        >

                                            🎥 Create Classroom

                                        </button>

                                    </form>


                                <?php endif; ?>


                                <!-- RESET ROOM -->

                                <?php if (
                                    !empty(
                                        $row['live_room_code']
                                    )
                                ): ?>


                                    <form
                                        method="POST"
                                        class="action-form"
                                        onsubmit="
                                            return confirm(
                                                'Reset this live classroom?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h(
                                                $csrfToken
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reset_live_room"
                                        >


                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$row['id'] ?>"
                                        >


                                        <button
                                            type="submit"
                                            class="
                                                btn
                                                btn-warning
                                            "
                                        >

                                            🔄 Reset Room

                                        </button>

                                    </form>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">

                        📚

                    </div>


                    <h3>

                        No Bookings Found

                    </h3>


                    <p>

                        There are no bookings
                        matching your search.

                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


</body>

</html>
