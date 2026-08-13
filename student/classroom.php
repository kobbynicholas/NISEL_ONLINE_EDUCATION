<?php

session_start();

require "../config/db.php";

/*
=========================================================
NISEL ONLINE EDUCATION
STUDENT CLASSROOM

URL:

student/classroom.php?id=BOOKING_ID

Example:

student/classroom.php?id=42
=========================================================
*/


/*
=========================================================
DATABASE
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
STUDENT LOGIN
=========================================================
*/

if (
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");

    exit;
}


$student_id =
    (int)$_SESSION['student_id'];


$student_name =
    $_SESSION['student_name']
    ?? 'Student';


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
BOOKING ID
=========================================================
*/

$booking_id =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


if ($booking_id <= 0) {

    http_response_code(400);

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            padding:70px;
        '>

            <h2 style='color:#003366;'>
                Invalid Classroom
            </h2>

            <p>
                No valid booking was supplied.
            </p>

            <a
                href='schedule.php'
                style='
                    display:inline-block;
                    margin-top:20px;
                    padding:12px 20px;
                    background:#003366;
                    color:white;
                    text-decoration:none;
                    border-radius:7px;
                '
            >
                ← Back to My Schedule
            </a>

        </div>
    ");
}


/*
=========================================================
GET BOOKING

IMPORTANT SECURITY:

booking ID AND student_id are checked.

A student cannot simply change:

?id=45

to view another student's booking.
=========================================================
*/

$stmt =
    $pdo->prepare("

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

            t.email AS teacher_email,

            t.phone AS teacher_phone

        FROM bookings b

        LEFT JOIN teachers t

            ON b.teacher_id =
               t.teacher_id

        WHERE
            b.id = ?

            AND b.student_id = ?

        LIMIT 1

    ");


$stmt->execute([
    $booking_id,
    $student_id
]);


$booking =
    $stmt->fetch();


/*
=========================================================
BOOKING NOT FOUND
=========================================================
*/

if (!$booking) {

    http_response_code(403);

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            padding:70px;
        '>

            <h2 style='color:#003366;'>
                Classroom Access Denied
            </h2>

            <p>
                This classroom does not exist,
                or it does not belong to your account.
            </p>

            <a
                href='schedule.php'
                style='
                    display:inline-block;
                    margin-top:20px;
                    padding:12px 20px;
                    background:#003366;
                    color:white;
                    text-decoration:none;
                    border-radius:7px;
                '
            >
                ← Back to My Schedule
            </a>

        </div>
    ");

}


/*
=========================================================
BOOKING VALUES
=========================================================
*/

$payment_status =
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    );


$lesson_status =
    strtolower(
        trim(
            $booking['lesson_status']
            ?? ''
        )
    );


$live_status =
    strtolower(
        trim(
            $booking['live_status']
            ?? 'waiting'
        )
    );


$teacher_assigned =
    !empty(
        $booking['teacher_id']
    );


$room_exists =
    !empty(
        $booking['live_room_code']
    );


$is_paid =
    in_array(
        $payment_status,
        [
            'paid',
            'success'
        ],
        true
    );


$is_cancelled =
    $lesson_status ===
    'cancelled';


$is_completed =
    $lesson_status ===
    'completed';


$can_enter =
    $is_paid
    &&
    $teacher_assigned
    &&
    $room_exists
    &&
    !$is_cancelled;


/*
=========================================================
API REQUESTS

The same page can act as the signaling endpoint.

Javascript sends POST requests to:

classroom.php?id=BOOKING_ID
=========================================================
*/

if (
    isset($_POST['classroom_action'])
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    $action =
        $_POST['classroom_action'];


    /*
    =====================================================
    VERIFY CLASSROOM ACCESS FOR API
    =====================================================
    */

    if (!$can_enter) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Classroom is not currently available.'
        ]);

        exit;
    }


    /*
    =====================================================
    SEND SIGNAL
    =====================================================
    */

    if (
        $action ===
        'send_signal'
    ) {

        $signal_type =
            trim(
                $_POST['signal_type']
                ?? ''
            );


        $signal_data =
            $_POST['signal_data']
            ?? '';


        /*
        ---------------------------------------------
        ALLOWED SIGNAL TYPES
        ---------------------------------------------
        */

        $allowedSignals = [
            'offer',
            'answer',
            'ice-candidate',
            'hangup',
            'ready'
        ];


        if (
            !in_array(
                $signal_type,
                $allowedSignals,
                true
            )
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid signal type.'
            ]);

            exit;
        }


        /*
        ---------------------------------------------
        LIMIT SIGNAL SIZE
        ---------------------------------------------
        */

        if (
            strlen(
                $signal_data
            ) > 500000
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Signal data is too large.'
            ]);

            exit;
        }


        /*
        ---------------------------------------------
        INSERT
        ---------------------------------------------
        */

        $signalStmt =
            $pdo->prepare("

                INSERT INTO classroom_signals

                (
                    booking_id,
                    room_code,
                    sender_role,
                    signal_type,
                    signal_data
                )

                VALUES
                (
                    ?,
                    ?,
                    'student',
                    ?,
                    ?
                )

            ");


        $signalStmt->execute([
            $booking_id,
            $booking['live_room_code'],
            $signal_type,
            $signal_data
        ]);


        echo json_encode([
            'success' => true,
            'id' =>
                $pdo->lastInsertId()
        ]);

        exit;
    }


    /*
    =====================================================
    GET SIGNALS
    =====================================================
    */

    if (
        $action ===
        'get_signals'
    ) {

        $last_id =
            isset(
                $_POST['last_id']
            )
                ? (int)$_POST['last_id']
                : 0;


        /*
        ---------------------------------------------
        ONLY GET TEACHER SIGNALS
        ---------------------------------------------
        */

        $signalStmt =
            $pdo->prepare("

                SELECT

                    id,
                    signal_type,
                    signal_data,
                    created_at

                FROM classroom_signals

                WHERE
                    booking_id = ?

                    AND room_code = ?

                    AND sender_role = 'teacher'

                    AND id > ?

                ORDER BY id ASC

                LIMIT 100

            ");


        $signalStmt->execute([
            $booking_id,
            $booking['live_room_code'],
            $last_id
        ]);


        $signals =
            $signalStmt->fetchAll();


        echo json_encode([
            'success' => true,
            'signals' => $signals
        ]);

        exit;
    }


    /*
    =====================================================
    SEND MESSAGE
    =====================================================
    */

    if (
        $action ===
        'send_message'
    ) {

        $message =
            trim(
                $_POST['message']
                ?? ''
            );


        if (
            $message === ''
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Message cannot be empty.'
            ]);

            exit;
        }


        /*
        ---------------------------------------------
        LIMIT MESSAGE
        ---------------------------------------------
        */

        if (
            mb_strlen($message) > 1000
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Message is too long.'
            ]);

            exit;
        }


        /*
        ---------------------------------------------
        SAVE MESSAGE
        ---------------------------------------------
        */

        $messageStmt =
            $pdo->prepare("

                INSERT INTO classroom_messages

                (
                    booking_id,
                    room_code,
                    sender_role,
                    sender_name,
                    message
                )

                VALUES
                (
                    ?,
                    ?,
                    'student',
                    ?,
                    ?
                )

            ");


        $messageStmt->execute([
            $booking_id,
            $booking['live_room_code'],
            $student_name,
            $message
        ]);


        echo json_encode([
            'success' => true
        ]);

        exit;
    }


    /*
    =====================================================
    GET MESSAGES
    =====================================================
    */

    if (
        $action ===
        'get_messages'
    ) {

        $last_message_id =
            isset(
                $_POST['last_message_id']
            )
                ? (int)
                    $_POST['last_message_id']
                : 0;


        $messageStmt =
            $pdo->prepare("

                SELECT

                    id,
                    sender_role,
                    sender_name,
                    message,
                    created_at

                FROM classroom_messages

                WHERE
                    booking_id = ?

                    AND room_code = ?

                    AND id > ?

                ORDER BY id ASC

                LIMIT 100

            ");


        $messageStmt->execute([
            $booking_id,
            $booking['live_room_code'],
            $last_message_id
        ]);


        $messages =
            $messageStmt->fetchAll();


        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);

        exit;
    }


    /*
    =====================================================
    HEARTBEAT
    =====================================================
    */

    if (
        $action ===
        'heartbeat'
    ) {

        echo json_encode([
            'success' => true,
            'live_status' =>
                $live_status
        ]);

        exit;
    }


    /*
    =====================================================
    UNKNOWN ACTION
    =====================================================
    */

    echo json_encode([
        'success' => false,
        'message' =>
            'Unknown classroom action.'
    ]);

    exit;
}


/*
=========================================================
FORMAT DATE
=========================================================
*/

$lesson_date_display =
    !empty(
        $booking['lesson_date']
    )
        ? date(
            'l, d F Y',
            strtotime(
                $booking['lesson_date']
            )
        )
        : 'Not scheduled';


/*
=========================================================
FORMAT TIME
=========================================================
*/

$lesson_time_display =
    !empty(
        $booking['lesson_time']
    )
        ? date(
            'h:i A',
            strtotime(
                $booking['lesson_time']
            )
        )
        : 'Not set';


/*
=========================================================
TEACHER NAME
=========================================================
*/

$teacher_display =
    $booking['assigned_teacher_name']
    ??
    $booking['teacher_name']
    ??
    'Not assigned';


/*
=========================================================
ROOM
=========================================================
*/

$room_code =
    $booking['live_room_code']
    ?? '';


?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="
        width=device-width,
        initial-scale=1.0
    "
>

<title>

    NISEL Classroom |
    <?= h($booking['subjects']) ?>

</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
}


html,
body {

    margin: 0;

    padding: 0;

    width: 100%;

    height: 100%;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #0b1220;

    color: white;
}


/* =====================================================
   TOP BAR
===================================================== */

.classroom-header {

    height: 70px;

    background: #003366;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.12);

    position: fixed;

    left: 0;

    right: 0;

    top: 0;

    z-index: 100;
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;
}


.brand-icon {

    width: 42px;

    height: 42px;

    border-radius: 10px;

    background:
        linear-gradient(
            135deg,
            #0877c9,
            #00a6e8
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

    font-weight: bold;
}


.brand-text {

    line-height: 1.2;
}


.brand-text strong {

    display: block;

    font-size: 16px;
}


.brand-text span {

    display: block;

    color: #9ddfff;

    font-size: 11px;
}


.header-info {

    display: flex;

    align-items: center;

    gap: 20px;
}


.room-label {

    font-family:
        monospace;

    font-size: 11px;

    color: #cbd5e1;
}


.exit-button {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    background: #dc3545;

    color: white;

    text-decoration: none;

    padding:
        9px 13px;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;
}


/* =====================================================
   MAIN
===================================================== */

.classroom {

    position: absolute;

    top: 70px;

    left: 0;

    right: 0;

    bottom: 0;

    display: grid;

    grid-template-columns:
        1fr 330px;

    gap: 0;
}


/* =====================================================
   VIDEO AREA
===================================================== */

.video-area {

    position: relative;

    background: #050a12;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;
}


.video-stage {

    position: absolute;

    inset: 15px;

    display: grid;

    grid-template-columns:
        1fr;

    gap: 12px;
}


.video-box {

    position: relative;

    background: #111827;

    border-radius: 12px;

    overflow: hidden;

    border:
        1px solid
        rgba(255,255,255,.08);
}


.video-box video {

    width: 100%;

    height: 100%;

    object-fit: cover;

    background: #020617;
}


.teacher-video {

    min-height: 400px;
}


.student-preview {

    position: absolute;

    width: 220px;

    height: 135px;

    right: 20px;

    bottom: 20px;

    z-index: 10;

    border:
        2px solid
        rgba(255,255,255,.25);

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.5);
}


.student-preview video {

    object-fit: cover;
}


.video-label {

    position: absolute;

    left: 12px;

    bottom: 12px;

    z-index: 5;

    background:
        rgba(0,0,0,.65);

    padding:
        6px 9px;

    border-radius: 5px;

    font-size: 11px;
}


/* =====================================================
   CONNECTION MESSAGE
===================================================== */

.connection-screen {

    position: absolute;

    inset: 0;

    z-index: 20;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        radial-gradient(
            circle at center,
            #17233b,
            #050a12
        );
}


.connection-card {

    width: min(450px,90%);

    text-align: center;

    padding: 35px;

    border-radius: 16px;

    background:
        rgba(15,23,42,.9);

    border:
        1px solid
        rgba(255,255,255,.1);

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.35);
}


.connection-icon {

    font-size: 50px;

    margin-bottom: 15px;
}


.connection-card h2 {

    margin:
        0 0 10px;

    font-size: 23px;
}


.connection-card p {

    color: #cbd5e1;

    font-size: 14px;

    line-height: 1.6;
}


/* =====================================================
   STATUS
===================================================== */

.status-pill {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        7px 11px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;

    margin-top: 15px;
}


.status-waiting {

    background: #3f3212;

    color: #ffd966;
}


.status-connecting {

    background: #123452;

    color: #75cfff;
}


.status-live {

    background: #123a29;

    color: #69e6a1;
}


.status-ended {

    background: #303642;

    color: #cbd5e1;
}


.status-error {

    background: #431d24;

    color: #ff9ba8;
}


/* =====================================================
   CONTROLS
===================================================== */

.controls {

    position: absolute;

    bottom: 20px;

    left: 50%;

    transform:
        translateX(-50%);

    z-index: 30;

    display: flex;

    gap: 8px;

    padding: 9px;

    border-radius: 30px;

    background:
        rgba(15,23,42,.92);

    border:
        1px solid
        rgba(255,255,255,.1);

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.4);
}


.control-btn {

    width: 43px;

    height: 43px;

    border: none;

    border-radius: 50%;

    background: #253247;

    color: white;

    cursor: pointer;

    font-size: 17px;
}


.control-btn:hover {

    background: #334155;
}


.control-btn.active {

    background: #dc3545;
}


/* =====================================================
   SIDEBAR
===================================================== */

.side-panel {

    background: #111827;

    border-left:
        1px solid
        rgba(255,255,255,.08);

    display: flex;

    flex-direction: column;

    overflow: hidden;
}


/* =====================================================
   LESSON INFO
===================================================== */

.lesson-info {

    padding: 20px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);
}


.lesson-info h2 {

    margin:
        0 0 5px;

    font-size: 19px;
}


.lesson-info .curriculum {

    color: #67d5ff;

    font-size: 12px;

    margin-bottom: 15px;
}


.info-row {

    display: flex;

    justify-content:
        space-between;

    gap: 10px;

    margin-bottom: 8px;

    font-size: 12px;
}


.info-row span:first-child {

    color: #94a3b8;
}


.info-row span:last-child {

    color: #e2e8f0;

    text-align: right;
}


/* =====================================================
   CHAT
===================================================== */

.chat-title {

    padding:
        14px 18px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);

    font-weight: bold;

    font-size: 13px;
}


.chat-messages {

    flex: 1;

    overflow-y: auto;

    padding: 15px;
}


.chat-message {

    margin-bottom: 13px;

    max-width: 90%;
}


.chat-message.mine {

    margin-left: auto;

    text-align: right;
}


.chat-bubble {

    display: inline-block;

    padding:
        8px 11px;

    border-radius: 9px;

    background: #253247;

    font-size: 12px;

    line-height: 1.45;

    text-align: left;
}


.mine .chat-bubble {

    background: #0055a5;
}


.chat-name {

    font-size: 10px;

    color: #94a3b8;

    margin-bottom: 3px;
}


.chat-time {

    font-size: 9px;

    color: #64748b;

    margin-top: 3px;
}


.chat-form {

    padding: 12px;

    border-top:
        1px solid
        rgba(255,255,255,.08);

    display: flex;

    gap: 7px;
}


.chat-input {

    flex: 1;

    min-width: 0;

    border: none;

    outline: none;

    border-radius: 7px;

    padding:
        10px;

    background: #1e293b;

    color: white;

    font-size: 12px;
}


.chat-input::placeholder {

    color: #64748b;
}


.chat-send {

    border: none;

    border-radius: 7px;

    padding:
        0 13px;

    background: #0877c9;

    color: white;

    cursor: pointer;

    font-weight: bold;
}


/* =====================================================
   NOT AVAILABLE
===================================================== */

.not-ready {

    min-height: 100vh;

    background: #eef3f8;

    color: #334155;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;
}


.not-ready-card {

    width: min(650px,100%);

    background: white;

    border-radius: 16px;

    padding: 35px;

    box-shadow:
        0 15px 50px
        rgba(0,0,0,.1);
}


.not-ready-icon {

    width: 70px;

    height: 70px;

    border-radius: 50%;

    background: #e8f4ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

    margin-bottom: 20px;
}


.not-ready-card h1 {

    color: #003366;

    margin:
        0 0 10px;
}


.not-ready-card p {

    color: #64748b;

    line-height: 1.7;
}


.lesson-summary {

    background: #f7faff;

    border:
        1px solid #e2e8f0;

    border-radius: 10px;

    padding: 18px;

    margin:
        20px 0;
}


.summary-row {

    display: flex;

    justify-content:
        space-between;

    padding:
        8px 0;

    border-bottom:
        1px solid #e9eef3;

    font-size: 13px;
}


.summary-row:last-child {

    border-bottom: none;
}


.summary-label {

    color: #64748b;
}


.summary-value {

    color: #003366;

    font-weight: bold;

    text-align: right;
}


.back-button {

    display: inline-block;

    padding:
        11px 17px;

    border-radius: 7px;

    background: #003366;

    color: white;

    text-decoration: none;

    font-weight: bold;

    font-size: 13px;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:900px) {

    .classroom {

        grid-template-columns: 1fr;

    }


    .side-panel {

        display: none;

    }


    .video-stage {

        inset: 8px;

    }


    .student-preview {

        width: 150px;

        height: 100px;

        right: 10px;

        bottom: 80px;

    }


    .room-label {

        display: none;

    }

}


@media(max-width:600px) {

    .brand-text {

        display: none;

    }


    .classroom-header {

        padding:
            0 12px;

    }


    .teacher-video {

        min-height: 300px;

    }


    .connection-card {

        padding: 25px;

    }

}

</style>

</head>


<body>


<?php if (!$can_enter): ?>


<!-- =====================================================
     CLASS NOT READY
===================================================== -->

<div class="not-ready">

    <div class="not-ready-card">


        <div class="not-ready-icon">

            🎓

        </div>


        <h1>

            Your Classroom Is Not Ready

        </h1>


        <p>

            Your booking has been found, but the
            NISEL classroom is not currently available.

        </p>


        <div class="lesson-summary">


            <div class="summary-row">

                <span class="summary-label">

                    Subject

                </span>

                <span class="summary-value">

                    <?= h(
                        $booking['subjects']
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Curriculum

                </span>

                <span class="summary-value">

                    <?= h(
                        $booking['curriculum']
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Teacher

                </span>

                <span class="summary-value">

                    <?= h(
                        $teacher_display
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Lesson Date

                </span>

                <span class="summary-value">

                    <?= h(
                        $lesson_date_display
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Lesson Time

                </span>

                <span class="summary-value">

                    <?= h(
                        $lesson_time_display
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Payment

                </span>

                <span class="summary-value">

                    <?= h(
                        strtoupper(
                            $booking['payment_status']
                            ?? 'PENDING'
                        )
                    ) ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Teacher Assignment

                </span>

                <span class="summary-value">

                    <?= $teacher_assigned
                        ? 'Assigned'
                        : 'Not Assigned'
                    ?>

                </span>

            </div>


            <div class="summary-row">

                <span class="summary-label">

                    Classroom

                </span>

                <span class="summary-value">

                    <?php

                    if ($is_cancelled) {

                        echo "Lesson Cancelled";

                    } elseif (!$is_paid) {

                        echo "Waiting for Payment";

                    } elseif (!$teacher_assigned) {

                        echo "Waiting for Teacher";

                    } elseif (!$room_exists) {

                        echo "Waiting for Classroom";

                    } elseif ($is_completed) {

                        echo "Lesson Completed";

                    } else {

                        echo "Not Available";

                    }

                    ?>

                </span>

            </div>


        </div>


        <a
            href="schedule.php"
            class="back-button"
        >

            ← Back to My Schedule

        </a>


    </div>

</div>


<?php else: ?>


<!-- =====================================================
     CLASSROOM
===================================================== -->

<header class="classroom-header">


    <div class="brand">


        <div class="brand-icon">

            N

        </div>


        <div class="brand-text">

            <strong>
                NISEL ONLINE EDUCATION
            </strong>

            <span>
                Virtual Classroom
            </span>

        </div>


    </div>


    <div class="header-info">


        <div class="room-label">

            ROOM:

            <?= h(
                $room_code
            ) ?>

        </div>


        <a
            href="schedule.php"
            class="exit-button"
        >

            ✕ Exit

        </a>


    </div>


</header>


<div class="classroom">


    <!-- =================================================
         VIDEO
    ================================================== -->

    <main class="video-area">


        <div class="video-stage">


            <!-- TEACHER VIDEO -->

            <div class="video-box teacher-video">


                <video
                    id="teacherVideo"
                    autoplay
                    playsinline
                ></video>


                <div
                    class="video-label"
                    id="teacherLabel"
                >

                    👨‍🏫
                    <?= h(
                        $teacher_display
                    ) ?>

                </div>


                <!-- CONNECTION SCREEN -->

                <div
                    class="connection-screen"
                    id="connectionScreen"
                >

                    <div
                        class="connection-card"
                    >

                        <div
                            class="connection-icon"
                            id="connectionIcon"
                        >

                            ⏳

                        </div>


                        <h2
                            id="connectionTitle"
                        >

                            Waiting for your teacher

                        </h2>


                        <p
                            id="connectionMessage"
                        >

                            Please wait while your
                            teacher joins the classroom.

                        </p>


                        <div
                            id="connectionStatus"
                            class="
                                status-pill
                                status-waiting
                            "
                        >

                            ● Waiting

                        </div>

                    </div>

                </div>


            </div>


            <!-- STUDENT PREVIEW -->

            <div
                class="
                    video-box
                    student-preview
                "
            >

                <video
                    id="studentVideo"
                    autoplay
                    muted
                    playsinline
                ></video>


                <div
                    class="video-label"
                >

                    You

                </div>

            </div>


        </div>


        <!-- CONTROLS -->

        <div class="controls">


            <button
                type="button"
                id="micButton"
                class="control-btn"
                title="Microphone"
            >

                🎤

            </button>


            <button
                type="button"
                id="cameraButton"
                class="control-btn"
                title="Camera"
            >

                📷

            </button>


            <button
                type="button"
                id="fullscreenButton"
                class="control-btn"
                title="Fullscreen"
            >

                ⛶

            </button>


            <button
                type="button"
                id="leaveButton"
                class="control-btn"
                title="Leave Classroom"
            >

                📞

            </button>


        </div>


    </main>


    <!-- =================================================
         RIGHT PANEL
    ================================================== -->

    <aside class="side-panel">


        <!-- LESSON INFORMATION -->

        <div class="lesson-info">


            <h2>

                <?= h(
                    $booking['subjects']
                ) ?>

            </h2>


            <div
                class="curriculum"
            >

                <?= h(
                    $booking['curriculum']
                ) ?>

                ·

                <?= h(
                    $booking['class_year']
                ) ?>

            </div>


            <div class="info-row">

                <span>
                    Teacher
                </span>

                <span>
                    <?= h(
                        $teacher_display
                    ) ?>
                </span>

            </div>


            <div class="info-row">

                <span>
                    Date
                </span>

                <span>
                    <?= h(
                        $lesson_date_display
                    ) ?>
                </span>

            </div>


            <div class="info-row">

                <span>
                    Time
                </span>

                <span>
                    <?= h(
                        $lesson_time_display
                    ) ?>
                </span>

            </div>


            <div class="info-row">

                <span>
                    Booking
                </span>

                <span>
                    <?= h(
                        $booking['booking_reference']
                    ) ?>
                </span>

            </div>


        </div>


        <!-- CHAT TITLE -->

        <div class="chat-title">

            💬 Classroom Chat

        </div>


        <!-- CHAT -->

        <div
            class="chat-messages"
            id="chatMessages"
        >

            <div
                style="
                    text-align:center;
                    color:#64748b;
                    font-size:11px;
                    padding:20px;
                "
                id="chatEmpty"
            >

                Classroom chat is ready.

            </div>

        </div>


        <!-- CHAT FORM -->

        <form
            class="chat-form"
            id="chatForm"
        >

            <input
                type="text"
                id="chatInput"
                class="chat-input"
                placeholder="Type a message..."
                autocomplete="off"
            >


            <button
                type="submit"
                class="chat-send"
            >

                Send

            </button>

        </form>


    </aside>


</div>


<script>

/*
=========================================================
NISEL ONLINE EDUCATION
STUDENT CLASSROOM JAVASCRIPT
=========================================================
*/


/*
=========================================================
CONFIGURATION
=========================================================
*/

const BOOKING_ID =
    <?= (int)$booking_id ?>;


const ROOM_CODE =
    <?= json_encode(
        $room_code
    ) ?>;


const STUDENT_NAME =
    <?= json_encode(
        $student_name
    ) ?>;


const CLASSROOM_URL =
    "classroom.php?id="
    +
    BOOKING_ID;


/*
=========================================================
DOM
=========================================================
*/

const teacherVideo =
    document.getElementById(
        "teacherVideo"
    );


const studentVideo =
    document.getElementById(
        "studentVideo"
    );


const connectionScreen =
    document.getElementById(
        "connectionScreen"
    );


const connectionIcon =
    document.getElementById(
        "connectionIcon"
    );


const connectionTitle =
    document.getElementById(
        "connectionTitle"
    );


const connectionMessage =
    document.getElementById(
        "connectionMessage"
    );


const connectionStatus =
    document.getElementById(
        "connectionStatus"
    );


const micButton =
    document.getElementById(
        "micButton"
    );


const cameraButton =
    document.getElementById(
        "cameraButton"
    );


const fullscreenButton =
    document.getElementById(
        "fullscreenButton"
    );


const leaveButton =
    document.getElementById(
        "leaveButton"
    );


const chatForm =
    document.getElementById(
        "chatForm"
    );


const chatInput =
    document.getElementById(
        "chatInput"
    );


const chatMessages =
    document.getElementById(
        "chatMessages"
    );


const chatEmpty =
    document.getElementById(
        "chatEmpty"
    );


/*
=========================================================
WEBRTC
=========================================================
*/

let peerConnection = null;

let localStream = null;

let remoteStream = null;

let lastSignalId = 0;

let lastMessageId = 0;

let polling = false;


/*
=========================================================
WEBRTC CONFIGURATION
=========================================================

STUN helps peers discover possible network paths.

For production, we should later add a TURN server
because some networks cannot establish a direct
peer-to-peer connection.
=========================================================
*/

const rtcConfiguration = {

    iceServers: [

        {
            urls:
                "stun:stun.l.google.com:19302"
        }

    ]

};


/*
=========================================================
CLASSROOM STATUS
=========================================================
*/

function setConnectionStatus(
    type,
    icon,
    title,
    message
) {

    connectionIcon.textContent =
        icon;

    connectionTitle.textContent =
        title;

    connectionMessage.textContent =
        message;


    connectionStatus.className =
        "status-pill "
        +
        "status-"
        +
        type;


    if (type === "live") {

        connectionStatus.textContent =
            "● Connected";

    } else if (
        type === "connecting"
    ) {

        connectionStatus.textContent =
            "● Connecting...";

    } else if (
        type === "error"
    ) {

        connectionStatus.textContent =
            "● Connection problem";

    } else if (
        type === "ended"
    ) {

        connectionStatus.textContent =
            "● Ended";

    } else {

        connectionStatus.textContent =
            "● Waiting";

    }

}


/*
=========================================================
HIDE CONNECTION SCREEN
=========================================================
*/

function hideConnectionScreen()
{

    connectionScreen.style.display =
        "none";

}


/*
=========================================================
SHOW CONNECTION SCREEN
=========================================================
*/

function showConnectionScreen()
{

    connectionScreen.style.display =
        "flex";

}


/*
=========================================================
SEND SIGNAL TO PHP
=========================================================
*/

async function sendSignal(
    signalType,
    signalData
) {

    try {

        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "send_signal"
        );


        formData.append(
            "signal_type",
            signalType
        );


        formData.append(
            "signal_data",
            JSON.stringify(
                signalData
            )
        );


        const response =
            await fetch(
                CLASSROOM_URL,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const result =
            await response.json();


        return result;


    } catch (error) {

        console.error(
            "Signal error:",
            error
        );

        return {
            success: false
        };

    }

}


/*
=========================================================
CREATE PEER CONNECTION
=========================================================
*/

function createPeerConnection()
{

    if (peerConnection) {

        return peerConnection;

    }


    peerConnection =
        new RTCPeerConnection(
            rtcConfiguration
        );


    /*
    -----------------------------------------------
    REMOTE STREAM
    -----------------------------------------------
    */

    remoteStream =
        new MediaStream();


    teacherVideo.srcObject =
        remoteStream;


    /*
    -----------------------------------------------
    REMOTE TRACK
    -----------------------------------------------
    */

    peerConnection.ontrack =
        function(event) {

            event.streams[0]
                .getTracks()
                .forEach(
                    track => {

                        remoteStream.addTrack(
                            track
                        );

                    }
                );


            hideConnectionScreen();


            setConnectionStatus(
                "live",
                "🎥",
                "You are connected",
                "Your teacher is now connected to the classroom."
            );

        };


    /*
    -----------------------------------------------
    ICE CANDIDATES
    -----------------------------------------------
    */

    peerConnection.onicecandidate =
        function(event) {

            if (
                event.candidate
            ) {

                sendSignal(
                    "ice-candidate",
                    event.candidate
                );

            }

        };


    /*
    -----------------------------------------------
    CONNECTION STATE
    -----------------------------------------------
    */

    peerConnection.onconnectionstatechange =
        function() {

            if (
                !peerConnection
            ) {

                return;

            }


            const state =
                peerConnection.connectionState;


            console.log(
                "WebRTC state:",
                state
            );


            if (
                state ===
                "connected"
            ) {

                hideConnectionScreen();


                setConnectionStatus(
                    "live",
                    "🎥",
                    "Connected",
                    "Your live lesson is in progress."
                );

            }


            if (
                state ===
                "connecting"
            ) {

                showConnectionScreen();


                setConnectionStatus(
                    "connecting",
                    "🔄",
                    "Connecting...",
                    "Connecting you to your teacher."
                );

            }


            if (
                state ===
                "disconnected"
            ) {

                showConnectionScreen();


                setConnectionStatus(
                    "connecting",
                    "🔄",
                    "Connection interrupted",
                    "Trying to reconnect..."
                );

            }


            if (
                state ===
                "failed"
            ) {

                showConnectionScreen();


                setConnectionStatus(
                    "error",
                    "⚠️",
                    "Connection failed",
                    "The classroom connection could not be established."
                );

            }


            if (
                state ===
                "closed"
            ) {

                showConnectionScreen();


                setConnectionStatus(
                    "ended",
                    "📴",
                    "Classroom closed",
                    "The classroom connection has ended."
                );

            }

        };


    return peerConnection;
}


/*
=========================================================
GET LOCAL CAMERA/MICROPHONE
=========================================================
*/

async function startLocalMedia()
{

    if (
        localStream
    ) {

        return localStream;

    }


    try {

        localStream =
            await navigator.mediaDevices
                .getUserMedia({

                    video: true,

                    audio: true

                });


        studentVideo.srcObject =
            localStream;


        /*
        -------------------------------------------
        ADD TRACKS
        -------------------------------------------
        */

        const pc =
            createPeerConnection();


        localStream
            .getTracks()
            .forEach(
                track => {

                    pc.addTrack(
                        track,
                        localStream
                    );

                }
            );


        return localStream;


    } catch (error) {

        console.error(
            "Media error:",
            error
        );


        setConnectionStatus(
            "error",
            "🎤",
            "Camera or microphone unavailable",
            "Please allow camera and microphone access in your browser."
        );


        return null;

    }

}


/*
=========================================================
HANDLE TEACHER OFFER
=========================================================
*/

async function handleOffer(
    signal
) {

    try {

        const offer =
            JSON.parse(
                signal.signal_data
            );


        const pc =
            createPeerConnection();


        await startLocalMedia();


        /*
        -------------------------------------------
        SET TEACHER OFFER
        -------------------------------------------
        */

        await pc.setRemoteDescription(
            new RTCSessionDescription(
                offer
            )
        );


        /*
        -------------------------------------------
        CREATE ANSWER
        -------------------------------------------
        */

        const answer =
            await pc.createAnswer();


        await pc.setLocalDescription(
            answer
        );


        /*
        -------------------------------------------
        SEND ANSWER
        -------------------------------------------
        */

        await sendSignal(
            "answer",
            answer
        );


        setConnectionStatus(
            "connecting",
            "🔄",
            "Connecting...",
            "Your browser is establishing the classroom connection."
        );


    } catch (error) {

        console.error(
            "Offer error:",
            error
        );


        setConnectionStatus(
            "error",
            "⚠️",
            "Connection error",
            "Unable to process the teacher's classroom connection."
        );

    }

}


/*
=========================================================
HANDLE ICE CANDIDATE
=========================================================
*/

async function handleIceCandidate(
    signal
) {

    try {

        const candidate =
            JSON.parse(
                signal.signal_data
            );


        const pc =
            createPeerConnection();


        /*
        -------------------------------------------
        If remote description has not been set yet,
        wait briefly.

        -------------------------------------------
        */

        if (
            !pc.remoteDescription
        ) {

            return;

        }


        await pc.addIceCandidate(
            new RTCIceCandidate(
                candidate
            )
        );


    } catch (error) {

        console.error(
            "ICE error:",
            error
        );

    }

}


/*
=========================================================
PROCESS SIGNALS
=========================================================
*/

async function pollSignals()
{

    if (polling) {

        return;

    }


    polling = true;


    try {

        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "get_signals"
        );


        formData.append(
            "last_id",
            lastSignalId
        );


        const response =
            await fetch(
                CLASSROOM_URL,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const result =
            await response.json();


        if (
            result.success &&
            Array.isArray(
                result.signals
            )
        ) {

            for (
                const signal
                of result.signals
            ) {

                lastSignalId =
                    Math.max(
                        lastSignalId,
                        parseInt(
                            signal.id
                        )
                    );


                if (
                    signal.signal_type
                    ===
                    "offer"
                ) {

                    await handleOffer(
                        signal
                    );

                }


                else if (
                    signal.signal_type
                    ===
                    "ice-candidate"
                ) {

                    await handleIceCandidate(
                        signal
                    );

                }


                else if (
                    signal.signal_type
                    ===
                    "hangup"
                ) {

                    handleTeacherHangup();

                }

            }

        }


    } catch (error) {

        console.error(
            "Polling error:",
            error
        );

    } finally {

        polling = false;

    }

}


/*
=========================================================
TEACHER HANGUP
=========================================================
*/

function handleTeacherHangup()
{

    if (
        peerConnection
    ) {

        peerConnection.close();

        peerConnection = null;

    }


    if (
        remoteStream
    ) {

        remoteStream
            .getTracks()
            .forEach(
                track =>
                    track.stop()
            );

    }


    showConnectionScreen();


    setConnectionStatus(
        "ended",
        "📴",
        "Classroom ended",
        "Your teacher has ended the classroom."
    );

}


/*
=========================================================
MICROPHONE
=========================================================
*/

micButton.addEventListener(
    "click",
    function() {

        if (
            !localStream
        ) {

            startLocalMedia();

            return;

        }


        const audioTracks =
            localStream.getAudioTracks();


        if (
            audioTracks.length === 0
        ) {

            return;

        }


        const enabled =
            audioTracks[0].enabled;


        audioTracks.forEach(
            track => {

                track.enabled =
                    !enabled;

            }
        );


        if (enabled) {

            micButton.textContent =
                "🔇";

            micButton.classList.add(
                "active"
            );

        } else {

            micButton.textContent =
                "🎤";

            micButton.classList.remove(
                "active"
            );

        }

    }
);


/*
=========================================================
CAMERA
=========================================================
*/

cameraButton.addEventListener(
    "click",
    function() {

        if (
            !localStream
        ) {

            startLocalMedia();

            return;

        }


        const videoTracks =
            localStream.getVideoTracks();


        if (
            videoTracks.length === 0
        ) {

            return;

        }


        const enabled =
            videoTracks[0].enabled;


        videoTracks.forEach(
            track => {

                track.enabled =
                    !enabled;

            }
        );


        if (enabled) {

            cameraButton.textContent =
                "🚫";

            cameraButton.classList.add(
                "active"
            );

        } else {

            cameraButton.textContent =
                "📷";

            cameraButton.classList.remove(
                "active"
            );

        }

    }
);


/*
=========================================================
FULLSCREEN
=========================================================
*/

fullscreenButton.addEventListener(
    "click",
    function() {

        const classroom =
            document.documentElement;


        if (
            !document.fullscreenElement
        ) {

            if (
                classroom.requestFullscreen
            ) {

                classroom.requestFullscreen();

            }

        } else {

            if (
                document.exitFullscreen
            ) {

                document.exitFullscreen();

            }

        }

    }
);


/*
=========================================================
LEAVE CLASSROOM
=========================================================
*/

leaveButton.addEventListener(
    "click",
    function() {

        if (
            !confirm(
                "Are you sure you want to leave the classroom?"
            )
        ) {

            return;

        }


        leaveClassroom();

    }
);


/*
=========================================================
LEAVE
=========================================================
*/

function leaveClassroom()
{

    if (
        peerConnection
    ) {

        peerConnection.close();

        peerConnection = null;

    }


    if (
        localStream
    ) {

        localStream
            .getTracks()
            .forEach(
                track =>
                    track.stop()
            );

    }


    window.location.href =
        "schedule.php";

}


/*
=========================================================
CHAT SEND
=========================================================
*/

chatForm.addEventListener(
    "submit",
    async function(event) {

        event.preventDefault();


        const message =
            chatInput.value.trim();


        if (
            message === ''
        ) {

            return;

        }


        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "send_message"
        );


        formData.append(
            "message",
            message
        );


        try {

            const response =
                await fetch(
                    CLASSROOM_URL,
                    {
                        method: "POST",
                        body: formData,
                        credentials: "same-origin"
                    }
                );


            const result =
                await response.json();


            if (
                result.success
            ) {

                chatInput.value =
                    '';

                loadMessages();

            }

        } catch (error) {

            console.error(
                "Chat error:",
                error
            );

        }

    }
);


/*
=========================================================
LOAD CHAT
=========================================================
*/

async function loadMessages()
{

    try {

        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "get_messages"
        );


        formData.append(
            "last_message_id",
            lastMessageId
        );


        const response =
            await fetch(
                CLASSROOM_URL,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                }
            );


        const result =
            await response.json();


        if (
            !result.success
        ) {

            return;

        }


        if (
            Array.isArray(
                result.messages
            )
        ) {

            result.messages.forEach(
                message => {

                    lastMessageId =
                        Math.max(
                            lastMessageId,
                            parseInt(
                                message.id
                            )
                        );


                    appendMessage(
                        message
                    );

                }
            );

        }

    } catch (error) {

        console.error(
            "Message error:",
            error
        );

    }

}


/*
=========================================================
APPEND CHAT MESSAGE
=========================================================
*/

function appendMessage(
    message
)
{

    if (
        chatEmpty
    ) {

        chatEmpty.style.display =
            "none";

    }


    const wrapper =
        document.createElement(
            "div"
        );


    wrapper.className =
        "chat-message "
        +
        (
            message.sender_role
            ===
            "student"
                ? "mine"
                : ""
        );


    const name =
        document.createElement(
            "div"
        );


    name.className =
        "chat-name";


    name.textContent =
        message.sender_name;


    const bubble =
        document.createElement(
            "div"
        );


    bubble.className =
        "chat-bubble";


    bubble.textContent =
        message.message;


    const time =
        document.createElement(
            "div"
        );


    time.className =
        "chat-time";


    time.textContent =
        message.created_at;


    wrapper.appendChild(
        name
    );


    wrapper.appendChild(
        bubble
    );


    wrapper.appendChild(
        time
    );


    chatMessages.appendChild(
        wrapper
    );


    chatMessages.scrollTop =
        chatMessages.scrollHeight;

}


/*
=========================================================
INITIALIZE
=========================================================
*/

async function initializeClassroom()
{

    setConnectionStatus(
        "waiting",
        "⏳",
        "Waiting for your teacher",
        "Please allow camera and microphone access when requested."
    );


    /*
    -----------------------------------------------
    START LOCAL MEDIA
    -----------------------------------------------
    */

    await startLocalMedia();


    /*
    -----------------------------------------------
    SIGNAL POLLING
    -----------------------------------------------
    */

    await pollSignals();


    /*
    -----------------------------------------------
    CHAT
    -----------------------------------------------
    */

    await loadMessages();


    /*
    -----------------------------------------------
    CONTINUOUS POLLING
    -----------------------------------------------
    */

    setInterval(
        pollSignals,
        1500
    );


    setInterval(
        loadMessages,
        2000
    );


    /*
    -----------------------------------------------
    HEARTBEAT
    -----------------------------------------------
    */

    setInterval(
        sendHeartbeat,
        10000
    );

}


/*
=========================================================
HEARTBEAT
=========================================================
*/

async function sendHeartbeat()
{

    try {

        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "heartbeat"
        );


        await fetch(
            CLASSROOM_URL,
            {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            }
        );

    } catch (error) {

        console.error(
            "Heartbeat error:",
            error
        );

    }

}


/*
=========================================================
PAGE CLOSE
=========================================================
*/

window.addEventListener(
    "beforeunload",
    function() {

        if (
            localStream
        ) {

            localStream
                .getTracks()
                .forEach(
                    track =>
                        track.stop()
                );

        }

    }
);


/*
=========================================================
START
=========================================================
*/

initializeClassroom();

</script>


<?php endif; ?>


</body>

</html>
