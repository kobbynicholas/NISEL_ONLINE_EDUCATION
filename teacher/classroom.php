<?php

session_start();

require_once "../teacher_auth.php";
require_once "../config/db.php";

/*
=========================================================
NISEL ONLINE EDUCATION
TEACHER LIVE CLASSROOM
=========================================================

URL:

teacher/classroom.php?id=BOOKING_ID

=========================================================
*/


/*
=========================================================
PDO
=========================================================
*/

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);


/*
=========================================================
HELPER
=========================================================
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
=========================================================
TEACHER SESSION
=========================================================
*/

$teacher_id =
    $_SESSION["teacher_id"]
    ?? null;

$teacher_name =
    $_SESSION["teacher_name"]
    ?? "Teacher";


if (!$teacher_id) {

    header("Location: login.php");
    exit;
}


/*
=========================================================
BOOKING ID
=========================================================
*/

$booking_id = 0;

if (isset($_GET["id"])) {

    $booking_id = (int)$_GET["id"];

} elseif (isset($_GET["booking_id"])) {

    $booking_id = (int)$_GET["booking_id"];

} elseif (isset($_GET["classroom_id"])) {

    $booking_id = (int)$_GET["classroom_id"];

}


if ($booking_id <= 0) {

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invalid Classroom</title>
            <style>
                body{
                    margin:0;
                    font-family:Arial,sans-serif;
                    background:#eef3f8;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    min-height:100vh;
                }

                .box{
                    background:#fff;
                    padding:40px;
                    border-radius:18px;
                    box-shadow:0 15px 40px rgba(0,0,0,.12);
                    text-align:center;
                    max-width:450px;
                }

                h2{
                    color:#003b70;
                }

                a{
                    display:inline-block;
                    margin-top:20px;
                    padding:12px 20px;
                    background:#003b70;
                    color:white;
                    text-decoration:none;
                    border-radius:8px;
                }
            </style>
        </head>

        <body>

            <div class='box'>

                <h2>
                    Invalid Classroom
                </h2>

                <p>
                    No valid lesson was selected.
                </p>

                <a href='schedule.php'>
                    Return to Schedule
                </a>

            </div>

        </body>
        </html>
    ");

}


/*
=========================================================
LOAD BOOKING

IMPORTANT:

The booking must belong to this teacher.
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT
        b.*
    FROM bookings b
    WHERE
        b.id = ?
        AND b.teacher_id = ?
    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $teacher_id
]);

$booking = $stmt->fetch();


/*
=========================================================
BOOKING NOT FOUND
=========================================================
*/

if (!$booking) {

    http_response_code(403);

    die("
        <!DOCTYPE html>

        <html>

        <head>

            <title>
                Classroom Access Denied
            </title>

            <style>

                body{
                    margin:0;
                    font-family:Arial,sans-serif;
                    background:#eef3f8;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    min-height:100vh;
                }

                .box{
                    background:#fff;
                    padding:45px;
                    border-radius:18px;
                    box-shadow:0 15px 40px rgba(0,0,0,.12);
                    text-align:center;
                    max-width:500px;
                }

                h2{
                    color:#003b70;
                }

                a{
                    display:inline-block;
                    margin-top:20px;
                    padding:12px 20px;
                    background:#003b70;
                    color:white;
                    text-decoration:none;
                    border-radius:8px;
                }

            </style>

        </head>

        <body>

            <div class='box'>

                <h2>
                    Classroom Access Denied
                </h2>

                <p>
                    This lesson is not assigned to your teacher account.
                </p>

                <a href='schedule.php'>
                    Return to Schedule
                </a>

            </div>

        </body>

        </html>
    ");

}


/*
=========================================================
BOOKING VALUES
=========================================================
*/

$student_name =
    $booking["student_name"]
    ?? "Student";

$subject =
    $booking["subjects"]
    ?? "Lesson";

$curriculum =
    $booking["curriculum"]
    ?? "Curriculum";

$class_year =
    $booking["class_year"]
    ?? "Class";

$payment_status =
    strtolower(
        trim(
            $booking["payment_status"]
            ?? ""
        )
    );

$lesson_status =
    strtolower(
        trim(
            $booking["lesson_status"]
            ?? ""
        )
    );

$live_status =
    strtolower(
        trim(
            $booking["live_status"]
            ?? "waiting"
        )
    );


/*
=========================================================
PAYMENT
=========================================================
*/

$is_paid =
    in_array(
        $payment_status,
        [
            "paid",
            "success",
            "successful",
            "completed"
        ],
        true
    );


/*
=========================================================
CANCELLED
=========================================================
*/

$is_cancelled =
    in_array(
        $lesson_status,
        [
            "cancelled",
            "canceled"
        ],
        true
    );


/*
=========================================================
GENERATE ROOM CODE
=========================================================
*/

$room_code =
    trim(
        $booking["live_room_code"]
        ?? ""
    );


if ($room_code === "") {

    $room_code =
        "NISEL-"
        . strtoupper(
            bin2hex(
                random_bytes(5)
            )
        );

    $stmt = $pdo->prepare("
        UPDATE bookings

        SET
            live_room_code = ?,
            live_status = 'waiting'

        WHERE
            id = ?
            AND teacher_id = ?
    ");

    $stmt->execute([
        $room_code,
        $booking_id,
        $teacher_id
    ]);

}


/*
=========================================================
API REQUEST HANDLER
=========================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["classroom_action"])
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $action =
        trim(
            $_POST["classroom_action"]
        );


    /*
    =====================================================
    START CLASS
    =====================================================
    */

    if ($action === "start_class") {

        if (!$is_paid) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "The student's payment has not been confirmed."
            ]);

            exit;
        }


        if ($is_cancelled) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "This lesson has been cancelled."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE bookings

            SET
                live_status = 'live',
                live_started_at = NOW(),
                live_ended_at = NULL,
                lesson_status = 'active'

            WHERE
                id = ?
                AND teacher_id = ?
        ");

        $stmt->execute([
            $booking_id,
            $teacher_id
        ]);


        echo json_encode([
            "success" => true,
            "status" => "live",
            "room_code" => $room_code
        ]);

        exit;
    }


    /*
    =====================================================
    END CLASS
    =====================================================
    */

    if ($action === "end_class") {

        $stmt = $pdo->prepare("
            UPDATE bookings

            SET
                live_status = 'ended',
                live_ended_at = NOW(),
                lesson_status = 'completed'

            WHERE
                id = ?
                AND teacher_id = ?
        ");

        $stmt->execute([
            $booking_id,
            $teacher_id
        ]);


        echo json_encode([
            "success" => true,
            "status" => "ended"
        ]);

        exit;
    }


    /*
    =====================================================
    SEND WEBRTC SIGNAL
    =====================================================
    */

    if ($action === "send_signal") {

        $signal_type =
            trim(
                $_POST["signal_type"]
                ?? ""
            );

        $signal_data =
            $_POST["signal_data"]
            ?? "";


        $allowed_types = [
            "offer",
            "answer",
            "ice-candidate",
            "ready",
            "hangup"
        ];


        if (
            !in_array(
                $signal_type,
                $allowed_types,
                true
            )
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Invalid signal type."
            ]);

            exit;
        }


        if (
            strlen($signal_data)
            > 1000000
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Signal data is too large."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
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
                'teacher',
                ?,
                ?
            )
        ");

        $stmt->execute([
            $booking_id,
            $room_code,
            $signal_type,
            $signal_data
        ]);


        echo json_encode([
            "success" => true,
            "id" =>
                $pdo->lastInsertId()
        ]);

        exit;
    }


    /*
    =====================================================
    GET STUDENT WEBRTC SIGNALS
    =====================================================
    */

    if ($action === "get_signals") {

        $last_id =
            isset($_POST["last_id"])
                ? (int) $_POST["last_id"]
                : 0;


        $stmt = $pdo->prepare("
            SELECT
                id,
                signal_type,
                signal_data,
                created_at

            FROM classroom_signals

            WHERE
                booking_id = ?
                AND room_code = ?
                AND sender_role = 'student'
                AND id > ?

            ORDER BY id ASC

            LIMIT 100
        ");

        $stmt->execute([
            $booking_id,
            $room_code,
            $last_id
        ]);


        echo json_encode([
            "success" => true,
            "signals" =>
                $stmt->fetchAll()
        ]);

        exit;
    }


    /*
    =====================================================
    DELETE OLD SIGNALS
    =====================================================
    */

    if ($action === "clear_signals") {

        $stmt = $pdo->prepare("
            DELETE FROM classroom_signals
            WHERE
                booking_id = ?
                AND room_code = ?
        ");

        $stmt->execute([
            $booking_id,
            $room_code
        ]);


        echo json_encode([
            "success" => true
        ]);

        exit;
    }


    /*
    =====================================================
    SEND CHAT MESSAGE
    =====================================================
    */

    if ($action === "send_message") {

        $message =
            trim(
                $_POST["message"]
                ?? ""
            );


        if ($message === "") {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Message cannot be empty."
            ]);

            exit;
        }


        if (
            mb_strlen($message)
            > 2000
        ) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Message is too long."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
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
                'teacher',
                ?,
                ?
            )
        ");

        $stmt->execute([
            $booking_id,
            $room_code,
            $teacher_name,
            $message
        ]);


        echo json_encode([
            "success" => true,
            "id" =>
                $pdo->lastInsertId()
        ]);

        exit;
    }


    /*
    =====================================================
    GET CHAT MESSAGES
    =====================================================
    */

    if ($action === "get_messages") {

        $last_message_id =
            isset(
                $_POST["last_message_id"]
            )
                ? (int)
                    $_POST["last_message_id"]
                : 0;


        $stmt = $pdo->prepare("
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

        $stmt->execute([
            $booking_id,
            $room_code,
            $last_message_id
        ]);


        echo json_encode([
            "success" => true,
            "messages" =>
                $stmt->fetchAll()
        ]);

        exit;
    }


    /*
    =====================================================
    GET LIVE STATUS
    =====================================================
    */

    if ($action === "get_status") {

        $stmt = $pdo->prepare("
            SELECT
                live_status,
                live_started_at,
                live_ended_at

            FROM bookings

            WHERE
                id = ?
                AND teacher_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $booking_id,
            $teacher_id
        ]);


        $data =
            $stmt->fetch();


        echo json_encode([
            "success" => true,
            "status" =>
                $data["live_status"]
                ?? "waiting",

            "started_at" =>
                $data["live_started_at"]
                ?? null,

            "ended_at" =>
                $data["live_ended_at"]
                ?? null
        ]);

        exit;
    }


    /*
    =====================================================
    UNKNOWN ACTION
    =====================================================
    */

    echo json_encode([
        "success" => false,
        "message" =>
            "Unknown classroom action."
    ]);

    exit;
}


/*
=========================================================
DISPLAY DATA
=========================================================
*/

$lesson_date =
    !empty($booking["lesson_date"])
        ? date(
            "l, d F Y",
            strtotime(
                $booking["lesson_date"]
            )
        )
        : "Not scheduled";


$lesson_time =
    !empty($booking["lesson_time"])
        ? date(
            "h:i A",
            strtotime(
                $booking["lesson_time"]
            )
        )
        : "Not set";


$booking_reference =
    $booking["booking_reference"]
    ?? ("BOOKING-" . $booking_id);

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
    NISEL Live Classroom
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
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #070d18;

    color: #ffffff;

}


/* =====================================================
   HEADER
===================================================== */

.topbar {

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    height: 68px;

    z-index: 100;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 20px;

    background:
        rgba(0, 51, 102, .98);

    border-bottom:
        1px solid
        rgba(255,255,255,.1);

}


.logo-area {

    display: flex;

    align-items: center;

    gap: 12px;

}


.logo-box {

    width: 40px;

    height: 40px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

    background:
        linear-gradient(
            135deg,
            #0877c9,
            #00a7e8
        );

}


.logo-text strong {

    display: block;

    font-size: 14px;

}


.logo-text small {

    display: block;

    color: #a8ddf7;

    font-size: 10px;

    margin-top: 2px;

}


.top-actions {

    display: flex;

    align-items: center;

    gap: 12px;

}


.room {

    color: #b9c7d8;

    font-size: 11px;

    font-family: monospace;

}


.exit-button {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding:
        9px 13px;

    border-radius: 8px;

    background: #dc3545;

    color: #ffffff;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;

}


/* =====================================================
   MAIN
===================================================== */

.app {

    position: absolute;

    top: 68px;

    left: 0;

    right: 0;

    bottom: 0;

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        340px;

}


/* =====================================================
   VIDEO AREA
===================================================== */

.video-area {

    position: relative;

    background:
        radial-gradient(
            circle at center,
            #17243b 0%,
            #070d18 65%
        );

    overflow: hidden;

}


.teacher-video {

    position: absolute;

    top: 15px;

    left: 15px;

    right: 15px;

    bottom: 15px;

    background: #020617;

    border-radius: 16px;

    overflow: hidden;

    border:
        1px solid
        rgba(255,255,255,.1);

}


#teacherVideo {

    width: 100%;

    height: 100%;

    object-fit: cover;

    background: #020617;

}


.video-name {

    position: absolute;

    left: 15px;

    bottom: 15px;

    padding:
        7px 11px;

    background:
        rgba(0,0,0,.65);

    border-radius: 7px;

    font-size: 11px;

    z-index: 5;

}


/* =====================================================
   STUDENT VIDEO
===================================================== */

.student-video {

    position: absolute;

    right: 30px;

    bottom: 95px;

    width: 245px;

    height: 155px;

    z-index: 20;

    overflow: hidden;

    border-radius: 12px;

    background: #111827;

    border:
        2px solid
        rgba(255,255,255,.25);

    box-shadow:
        0 15px 35px
        rgba(0,0,0,.5);

}


#studentVideo {

    width: 100%;

    height: 100%;

    object-fit: cover;

    background: #020617;

}


.student-label {

    position: absolute;

    left: 9px;

    bottom: 9px;

    padding:
        5px 8px;

    border-radius: 5px;

    background:
        rgba(0,0,0,.65);

    font-size: 10px;

}


/* =====================================================
   WAITING PANEL
===================================================== */

.waiting {

    position: absolute;

    inset: 0;

    z-index: 30;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(2,6,23,.82);

}


.waiting-card {

    width:
        min(470px, 90%);

    padding: 35px;

    border-radius: 20px;

    text-align: center;

    background:
        rgba(15,23,42,.97);

    border:
        1px solid
        rgba(255,255,255,.1);

    box-shadow:
        0 20px 60px
        rgba(0,0,0,.4);

}


.waiting-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 18px;

    border-radius: 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0877c9
        );

}


.waiting-card h2 {

    margin:
        0 0 10px;

    font-size: 22px;

}


.waiting-card p {

    margin: 0;

    color: #9eacbd;

    line-height: 1.6;

    font-size: 13px;

}


.live-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 17px;

    padding:
        7px 12px;

    border-radius: 30px;

    background: #123b2a;

    color: #72e2a4;

    font-size: 11px;

    font-weight: 800;

}


.live-badge.waiting-badge {

    background: #3a3014;

    color: #f7d66a;

}


.live-badge.ended-badge {

    background: #29313d;

    color: #aeb9c8;

}


/* =====================================================
   CONTROLS
===================================================== */

.controls {

    position: absolute;

    bottom: 22px;

    left: 50%;

    transform:
        translateX(-50%);

    z-index: 60;

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 8px;

    border-radius: 40px;

    background:
        rgba(15,23,42,.95);

    border:
        1px solid
        rgba(255,255,255,.12);

    box-shadow:
        0 12px 35px
        rgba(0,0,0,.4);

}


.control {

    width: 44px;

    height: 44px;

    border: none;

    border-radius: 50%;

    background: #263348;

    color: #ffffff;

    cursor: pointer;

    font-size: 17px;

}


.control:hover {

    background: #34445c;

}


.control.off {

    background: #b42336;

}


.start-button,
.end-button {

    height: 44px;

    border: none;

    border-radius: 24px;

    padding:
        0 17px;

    color: white;

    font-weight: 800;

    cursor: pointer;

}


.start-button {

    background: #16a34a;

}


.start-button:hover {

    background: #15803d;

}


.end-button {

    display: none;

    background: #dc3545;

}


.end-button:hover {

    background: #bb2d3b;

}


/* =====================================================
   RIGHT PANEL
===================================================== */

.sidebar {

    display: flex;

    flex-direction: column;

    min-width: 0;

    background: #111827;

    border-left:
        1px solid
        rgba(255,255,255,.08);

}


/* =====================================================
   LESSON INFORMATION
===================================================== */

.lesson-card {

    padding: 20px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);

}


.lesson-card h2 {

    margin: 0;

    font-size: 18px;

}


.curriculum {

    margin-top: 5px;

    color: #5ccdf6;

    font-size: 11px;

}


.info-row {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding:
        8px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.04);

    font-size: 11px;

}


.info-row span:first-child {

    color: #718096;

}


.info-row span:last-child {

    color: #e2e8f0;

    text-align: right;

}


.payment-badge {

    display: inline-flex;

    margin-top: 12px;

    padding:
        6px 9px;

    border-radius: 6px;

    background: #123b2a;

    color: #6ee7a0;

    font-size: 9px;

    font-weight: 800;

}


/* =====================================================
   CHAT
===================================================== */

.chat-header {

    padding:
        14px 18px;

    font-size: 13px;

    font-weight: 800;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);

}


.chat-messages {

    flex: 1;

    min-height: 0;

    overflow-y: auto;

    padding: 15px;

}


.empty-chat {

    text-align: center;

    color: #66758a;

    font-size: 11px;

    padding: 25px 10px;

}


.message {

    margin-bottom: 14px;

}


.message.mine {

    text-align: right;

}


.message-name {

    color: #7f8da1;

    font-size: 9px;

    margin-bottom: 4px;

}


.message-bubble {

    display: inline-block;

    max-width: 90%;

    padding:
        8px 10px;

    border-radius: 9px;

    background: #263348;

    color: #e7edf5;

    font-size: 11px;

    line-height: 1.45;

    text-align: left;

}


.message.mine
.message-bubble {

    background: #075a9e;

}


.message-time {

    margin-top: 3px;

    color: #5f6e82;

    font-size: 8px;

}


.chat-form {

    display: flex;

    gap: 7px;

    padding: 12px;

    border-top:
        1px solid
        rgba(255,255,255,.08);

}


.chat-input {

    flex: 1;

    min-width: 0;

    border: none;

    outline: none;

    border-radius: 8px;

    padding:
        10px;

    background: #1e293b;

    color: white;

    font-size: 11px;

}


.chat-input::placeholder {

    color: #68788e;

}


.chat-send {

    border: none;

    border-radius: 8px;

    padding:
        0 13px;

    background: #0877c9;

    color: white;

    cursor: pointer;

    font-weight: 800;

}


/* =====================================================
   PAYMENT BLOCK
===================================================== */

.blocked {

    position: absolute;

    inset: 0;

    z-index: 90;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(2,6,23,.96);

}


.blocked-card {

    width:
        min(460px, 90%);

    padding: 35px;

    text-align: center;

    border-radius: 18px;

    background: #111827;

    border:
        1px solid
        rgba(255,255,255,.1);

}


.blocked-card h2 {

    margin-top: 0;

}


.blocked-card p {

    color: #9eacbd;

    line-height: 1.6;

    font-size: 13px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 950px) {

    .app {

        grid-template-columns: 1fr;

    }


    .sidebar {

        display: none;

    }

}


@media(max-width: 600px) {

    .logo-text {

        display: none;

    }


    .room {

        display: none;

    }


    .student-video {

        width: 150px;

        height: 100px;

        right: 15px;

        bottom: 90px;

    }


    .controls {

        max-width: 96%;

    }


    .control {

        width: 40px;

        height: 40px;

    }


    .start-button,
    .end-button {

        padding:
            0 11px;

        font-size: 11px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     TOP BAR
===================================================== -->

<header class="topbar">


    <div class="logo-area">

        <div class="logo-box">
            N
        </div>


        <div class="logo-text">

            <strong>
                NISEL ONLINE EDUCATION
            </strong>

            <small>
                Live Teacher Classroom
            </small>

        </div>

    </div>


    <div class="top-actions">

        <div class="room">

            ROOM:
            <?= e($room_code) ?>

        </div>


        <a
            href="schedule.php"
            class="exit-button"
        >

            ✕ Exit

        </a>

    </div>

</header>


<!-- =====================================================
     APPLICATION
===================================================== -->

<div class="app">


    <!-- =================================================
         VIDEO
    ================================================== -->

    <section class="video-area">


        <div class="teacher-video">


            <video
                id="teacherVideo"
                autoplay
                muted
                playsinline
            ></video>


            <div class="video-name">

                👨‍🏫

                <?= e($teacher_name) ?>

            </div>


            <!-- WAITING SCREEN -->

            <div
                class="waiting"
                id="waitingScreen"
            >

                <div class="waiting-card">


                    <div
                        class="waiting-icon"
                        id="waitingIcon"
                    >

                        🎓

                    </div>


                    <h2
                        id="waitingTitle"
                    >

                        Ready to Start Class

                    </h2>


                    <p
                        id="waitingMessage"
                    >

                        Start the lesson when you are
                        ready. Your student will be able
                        to join the live classroom.

                    </p>


                    <div
                        class="live-badge waiting-badge"
                        id="statusBadge"
                    >

                        ● WAITING

                    </div>

                </div>

            </div>

        </div>


        <!-- STUDENT VIDEO -->

        <div
            class="student-video"
        >

            <video
                id="studentVideo"
                autoplay
                playsinline
            ></video>


            <div class="student-label">

                👨‍🎓

                <?= e($student_name) ?>

            </div>

        </div>


        <!-- CONTROLS -->

        <div class="controls">


            <button
                type="button"
                class="control"
                id="micButton"
                title="Microphone"
            >
                🎤
            </button>


            <button
                type="button"
                class="control"
                id="cameraButton"
                title="Camera"
            >
                📷
            </button>


            <button
                type="button"
                class="control"
                id="screenButton"
                title="Share screen"
            >
                🖥️
            </button>


            <button
                type="button"
                class="control"
                id="fullscreenButton"
                title="Fullscreen"
            >
                ⛶
            </button>


            <button
                type="button"
                class="start-button"
                id="startButton"
            >
                ▶ Start Class
            </button>


            <button
                type="button"
                class="end-button"
                id="endButton"
            >
                ■ End Class
            </button>

        </div>


        <!-- PAYMENT BLOCK -->

        <?php if (!$is_paid): ?>

            <div class="blocked">

                <div class="blocked-card">

                    <div
                        style="
                            font-size:45px;
                            margin-bottom:15px;
                        "
                    >
                        💳
                    </div>


                    <h2>
                        Payment Required
                    </h2>


                    <p>

                        This lesson cannot be started
                        because the student's payment has
                        not been confirmed.

                    </p>

                </div>

            </div>

        <?php endif; ?>


        <?php if ($is_cancelled): ?>

            <div class="blocked">

                <div class="blocked-card">

                    <div
                        style="
                            font-size:45px;
                            margin-bottom:15px;
                        "
                    >
                        ⚠️
                    </div>


                    <h2>
                        Lesson Cancelled
                    </h2>


                    <p>

                        This lesson has been cancelled
                        and cannot be started.

                    </p>

                </div>

            </div>

        <?php endif; ?>


    </section>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <!-- LESSON DETAILS -->

        <div class="lesson-card">


            <h2>

                <?= e($subject) ?>

            </h2>


            <div class="curriculum">

                <?= e($curriculum) ?>

                ·

                <?= e($class_year) ?>

            </div>


            <div
                style="
                    margin-top:15px;
                "
            >


                <div class="info-row">

                    <span>
                        Student
                    </span>

                    <span>
                        <?= e($student_name) ?>
                    </span>

                </div>


                <div class="info-row">

                    <span>
                        Date
                    </span>

                    <span>
                        <?= e($lesson_date) ?>
                    </span>

                </div>


                <div class="info-row">

                    <span>
                        Time
                    </span>

                    <span>
                        <?= e($lesson_time) ?>
                    </span>

                </div>


                <div class="info-row">

                    <span>
                        Booking
                    </span>

                    <span>
                        <?= e($booking_reference) ?>
                    </span>

                </div>


                <div class="info-row">

                    <span>
                        Room
                    </span>

                    <span>
                        <?= e($room_code) ?>
                    </span>

                </div>


            </div>


            <?php if ($is_paid): ?>

                <div class="payment-badge">

                    ✓ PAYMENT CONFIRMED

                </div>

            <?php endif; ?>


        </div>


        <!-- CHAT HEADER -->

        <div class="chat-header">

            💬 Classroom Chat

        </div>


        <!-- CHAT MESSAGES -->

        <div
            class="chat-messages"
            id="chatMessages"
        >

            <div
                class="empty-chat"
                id="emptyChat"
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
                placeholder="Message your student..."
                autocomplete="off"
                maxlength="2000"
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
NISEL TEACHER CLASSROOM
WEBRTC JAVASCRIPT
=========================================================
*/


/*
=========================================================
CONFIG
=========================================================
*/

const BOOKING_ID =
    <?= (int) $booking_id ?>;


const ROOM_CODE =
    <?= json_encode($room_code) ?>;


const CLASSROOM_URL =
    "classroom.php?id="
    + BOOKING_ID;


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


const waitingScreen =
    document.getElementById(
        "waitingScreen"
    );


const waitingIcon =
    document.getElementById(
        "waitingIcon"
    );


const waitingTitle =
    document.getElementById(
        "waitingTitle"
    );


const waitingMessage =
    document.getElementById(
        "waitingMessage"
    );


const statusBadge =
    document.getElementById(
        "statusBadge"
    );


const startButton =
    document.getElementById(
        "startButton"
    );


const endButton =
    document.getElementById(
        "endButton"
    );


const micButton =
    document.getElementById(
        "micButton"
    );


const cameraButton =
    document.getElementById(
        "cameraButton"
    );


const screenButton =
    document.getElementById(
        "screenButton"
    );


const fullscreenButton =
    document.getElementById(
        "fullscreenButton"
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


const emptyChat =
    document.getElementById(
        "emptyChat"
    );


/*
=========================================================
WEBRTC VARIABLES
=========================================================
*/

let localStream = null;

let peerConnection = null;

let remoteStream = null;

let screenStream = null;

let classStarted = false;

let lastSignalId = 0;

let lastMessageId = 0;

let pollingSignals = false;

let pollingMessages = false;

let pendingIceCandidates = [];


/*
=========================================================
STUN SERVERS
=========================================================
*/

const rtcConfiguration = {

    iceServers: [

        {
            urls:
                "stun:stun.l.google.com:19302"
        },

        {
            urls:
                "stun:stun1.l.google.com:19302"
        }

    ]

};


/*
=========================================================
STATUS DISPLAY
=========================================================
*/

function setClassStatus(
    type,
    icon,
    title,
    message
) {

    waitingIcon.textContent =
        icon;

    waitingTitle.textContent =
        title;

    waitingMessage.textContent =
        message;


    statusBadge.className =
        "live-badge";


    if (type === "waiting") {

        statusBadge.classList.add(
            "waiting-badge"
        );

        statusBadge.textContent =
            "● WAITING";

    }


    if (type === "live") {

        statusBadge.textContent =
            "● LIVE";

    }


    if (type === "ended") {

        statusBadge.classList.add(
            "ended-badge"
        );

        statusBadge.textContent =
            "● ENDED";

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


    remoteStream =
        new MediaStream();


    studentVideo.srcObject =
        remoteStream;


    /*
    ---------------------------------------------
    REMOTE TRACK
    ---------------------------------------------
    */

    peerConnection.ontrack =
        function(event) {

            if (
                event.streams
                &&
                event.streams[0]
            ) {

                event.streams[0]
                    .getTracks()
                    .forEach(
                        function(track) {

                            const exists =
                                remoteStream
                                    .getTracks()
                                    .some(
                                        function(
                                            existing
                                        ) {

                                            return (
                                                existing.id
                                                ===
                                                track.id
                                            );

                                        }
                                    );


                            if (!exists) {

                                remoteStream.addTrack(
                                    track
                                );

                            }

                        }
                    );

            }

            studentVideo.play()
                .catch(
                    function() {}
                );

        };


    /*
    ---------------------------------------------
    ICE CANDIDATES
    ---------------------------------------------
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
    ---------------------------------------------
    CONNECTION STATE
    ---------------------------------------------
    */

    peerConnection.onconnectionstatechange =
        function() {

            if (!peerConnection) {

                return;

            }


            const state =
                peerConnection
                    .connectionState;


            console.log(
                "WebRTC connection:",
                state
            );


            if (state === "connected") {

                waitingScreen.style.display =
                    "none";

                setClassStatus(
                    "live",
                    "🎥",
                    "Class is Live",
                    "You are connected to your student."
                );

            }


            if (
                state === "connecting"
            ) {

                waitingScreen.style.display =
                    "flex";

                setClassStatus(
                    "waiting",
                    "🔄",
                    "Connecting...",
                    "Connecting to the student."
                );

            }


            if (
                state === "disconnected"
            ) {

                waitingScreen.style.display =
                    "flex";

                setClassStatus(
                    "waiting",
                    "🔄",
                    "Connection Interrupted",
                    "Waiting for the student to reconnect."
                );

            }


            if (
                state === "failed"
            ) {

                waitingScreen.style.display =
                    "flex";

                setClassStatus(
                    "waiting",
                    "⚠️",
                    "Connection Failed",
                    "The live video connection could not be established."
                );

            }

        };


    /*
    ---------------------------------------------
    ICE CONNECTION STATE
    ---------------------------------------------
    */

    peerConnection.oniceconnectionstatechange =
        function() {

            console.log(
                "ICE:",
                peerConnection
                    .iceConnectionState
            );

        };


    return peerConnection;
}


/*
=========================================================
START CAMERA
=========================================================
*/

async function startLocalMedia()
{

    if (localStream) {

        return localStream;

    }


    if (
        !navigator.mediaDevices
        ||
        !navigator.mediaDevices.getUserMedia
    ) {

        alert(
            "Your browser does not support camera access."
        );

        return null;

    }


    try {

        localStream =
            await navigator
                .mediaDevices
                .getUserMedia({

                    video: {
                        width: {
                            ideal: 1280
                        },

                        height: {
                            ideal: 720
                        }
                    },

                    audio: true

                });


        teacherVideo.srcObject =
            localStream;


        teacherVideo.play()
            .catch(
                function() {}
            );


        const pc =
            createPeerConnection();


        localStream
            .getTracks()
            .forEach(
                function(track) {

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


        alert(
            "Camera or microphone access was denied. Please allow access in your browser."
        );


        return null;

    }

}


/*
=========================================================
START CLASS
=========================================================
*/

startButton.addEventListener(
    "click",
    async function() {

        if (classStarted) {

            return;

        }


        const stream =
            await startLocalMedia();


        if (!stream) {

            return;

        }


        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "start_class"
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


            if (!result.success) {

                alert(
                    result.message
                    ||
                    "Unable to start class."
                );

                return;

            }


            classStarted =
                true;


            startButton.style.display =
                "none";


            endButton.style.display =
                "inline-flex";


            setClassStatus(
                "waiting",
                "📡",
                "Waiting for Student",
                "The classroom is live. Waiting for the student to join."
            );


            /*
            -------------------------------------
            CREATE OFFER
            -------------------------------------
            */

            await createOffer();


        } catch (error) {

            console.error(
                "Start class error:",
                error
            );


            alert(
                "Unable to connect to the classroom server."
            );

        }

    }
);


/*
=========================================================
CREATE WEBRTC OFFER
=========================================================
*/

async function createOffer()
{

    const pc =
        createPeerConnection();


    try {

        const offer =
            await pc.createOffer({

                offerToReceiveAudio: true,

                offerToReceiveVideo: true

            });


        await pc.setLocalDescription(
            offer
        );


        await sendSignal(
            "offer",
            offer
        );


    } catch (error) {

        console.error(
            "Offer error:",
            error
        );

    }

}


/*
=========================================================
SEND SIGNAL
=========================================================
*/

async function sendSignal(
    signalType,
    signalData
)
{

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


        return await response.json();


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
POLL SIGNALS
=========================================================
*/

async function pollSignals()
{

    if (
        !classStarted
        ||
        pollingSignals
    ) {

        return;

    }


    pollingSignals =
        true;


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
            &&
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
                            signal.id,
                            10
                        )
                    );


                await processSignal(
                    signal
                );

            }

        }


    } catch (error) {

        console.error(
            "Signal polling:",
            error
        );

    }


    pollingSignals =
        false;

}


/*
=========================================================
PROCESS SIGNAL
=========================================================
*/

async function processSignal(
    signal
)
{

    if (
        signal.signal_type
        ===
        "answer"
    ) {

        await handleAnswer(
            signal
        );

        return;

    }


    if (
        signal.signal_type
        ===
        "ice-candidate"
    ) {

        await handleIceCandidate(
            signal
        );

        return;

    }


    if (
        signal.signal_type
        ===
        "ready"
    ) {

        if (classStarted) {

            console.log(
                "Student is ready."
            );

        }

        return;

    }


    if (
        signal.signal_type
        ===
        "hangup"
    ) {

        setClassStatus(
            "waiting",
            "📴",
            "Student Left",
            "The student has left the classroom."
        );

        waitingScreen.style.display =
            "flex";

    }

}


/*
=========================================================
HANDLE ANSWER
=========================================================
*/

async function handleAnswer(
    signal
)
{

    if (!peerConnection) {

        return;

    }


    try {

        const answer =
            JSON.parse(
                signal.signal_data
            );


        await peerConnection
            .setRemoteDescription(
                new RTCSessionDescription(
                    answer
                )
            );


        /*
        -----------------------------------------
        PROCESS QUEUED ICE
        -----------------------------------------
        */

        await processPendingIce();


        setClassStatus(
            "live",
            "🎥",
            "Student Connected",
            "Your live classroom connection is active."
        );


    } catch (error) {

        console.error(
            "Answer error:",
            error
        );

    }

}


/*
=========================================================
HANDLE ICE
=========================================================
*/

async function handleIceCandidate(
    signal
)
{

    if (!peerConnection) {

        return;

    }


    try {

        const candidate =
            JSON.parse(
                signal.signal_data
            );


        const iceCandidate =
            new RTCIceCandidate(
                candidate
            );


        if (
            peerConnection
                .remoteDescription
        ) {

            await peerConnection
                .addIceCandidate(
                    iceCandidate
                );

        } else {

            pendingIceCandidates.push(
                iceCandidate
            );

        }


    } catch (error) {

        console.error(
            "ICE error:",
            error
        );

    }

}


/*
=========================================================
PROCESS QUEUED ICE
=========================================================
*/

async function processPendingIce()
{

    if (!peerConnection) {

        return;

    }


    if (
        !peerConnection.remoteDescription
    ) {

        return;

    }


    while (
        pendingIceCandidates.length
        > 0
    ) {

        const candidate =
            pendingIceCandidates.shift();


        try {

            await peerConnection
                .addIceCandidate(
                    candidate
                );

        } catch (error) {

            console.error(
                "Queued ICE error:",
                error
            );

        }

    }

}


/*
=========================================================
END CLASS
=========================================================
*/

endButton.addEventListener(
    "click",
    async function() {

        const confirmed =
            confirm(
                "Are you sure you want to end this live class?"
            );


        if (!confirmed) {

            return;

        }


        const formData =
            new FormData();


        formData.append(
            "classroom_action",
            "end_class"
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


            if (!result.success) {

                alert(
                    result.message
                    ||
                    "Unable to end class."
                );

                return;

            }


            classStarted =
                false;


            if (peerConnection) {

                peerConnection.close();

                peerConnection =
                    null;

            }


            if (localStream) {

                localStream
                    .getTracks()
                    .forEach(
                        function(track) {

                            track.stop();

                        }
                    );

                localStream =
                    null;

            }


            teacherVideo.srcObject =
                null;


            studentVideo.srcObject =
                null;


            waitingScreen.style.display =
                "flex";


            startButton.style.display =
                "inline-flex";


            endButton.style.display =
                "none";


            setClassStatus(
                "ended",
                "📴",
                "Class Ended",
                "This lesson has been completed."
            );


        } catch (error) {

            console.error(
                "End class error:",
                error
            );

        }

    }
);


/*
=========================================================
MICROPHONE
=========================================================
*/

micButton.addEventListener(
    "click",
    function() {

        if (!localStream) {

            return;

        }


        const tracks =
            localStream
                .getAudioTracks();


        if (!tracks.length) {

            return;

        }


        const currentlyEnabled =
            tracks[0].enabled;


        tracks.forEach(
            function(track) {

                track.enabled =
                    !currentlyEnabled;

            }
        );


        if (currentlyEnabled) {

            micButton.textContent =
                "🔇";

            micButton.classList.add(
                "off"
            );

        } else {

            micButton.textContent =
                "🎤";

            micButton.classList.remove(
                "off"
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

        if (!localStream) {

            return;

        }


        const tracks =
            localStream
                .getVideoTracks();


        if (!tracks.length) {

            return;

        }


        const currentlyEnabled =
            tracks[0].enabled;


        tracks.forEach(
            function(track) {

                track.enabled =
                    !currentlyEnabled;

            }
        );


        if (currentlyEnabled) {

            cameraButton.textContent =
                "🚫";

            cameraButton.classList.add(
                "off"
            );

        } else {

            cameraButton.textContent =
                "📷";

            cameraButton.classList.remove(
                "off"
            );

        }

    }
);


/*
=========================================================
SCREEN SHARING
=========================================================
*/

screenButton.addEventListener(
    "click",
    async function() {

        if (!peerConnection) {

            alert(
                "Start the class first."
            );

            return;

        }


        if (
            !navigator.mediaDevices
            ||
            !navigator.mediaDevices
                .getDisplayMedia
        ) {

            alert(
                "Screen sharing is not supported by this browser."
            );

            return;

        }


        try {

            screenStream =
                await navigator
                    .mediaDevices
                    .getDisplayMedia({
                        video: true,
                        audio: false
                    });


            const screenTrack =
                screenStream
                    .getVideoTracks()[0];


            const sender =
                peerConnection
                    .getSenders()
                    .find(
                        function(item) {

                            return (
                                item.track
                                &&
                                item.track.kind
                                ===
                                "video"
                            );

                        }
                    );


            if (sender) {

                await sender
                    .replaceTrack(
                        screenTrack
                    );

            }


            teacherVideo.srcObject =
                screenStream;


            screenTrack.onended =
                async function() {

                    if (
                        localStream
                    ) {

                        const cameraTrack =
                            localStream
                                .getVideoTracks()[0];


                        if (
                            sender
                            &&
                            cameraTrack
                        ) {

                            await sender
                                .replaceTrack(
                                    cameraTrack
                                );

                        }


                        teacherVideo.srcObject =
                            localStream;

                    }

                };


        } catch (error) {

            console.error(
                "Screen sharing error:",
                error
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
    async function() {

        try {

            if (
                !document.fullscreenElement
            ) {

                await document
                    .documentElement
                    .requestFullscreen();

            } else {

                await document
                    .exitFullscreen();

            }

        } catch (error) {

            console.error(
                "Fullscreen:",
                error
            );

        }

    }
);


/*
=========================================================
SEND CHAT
=========================================================
*/

chatForm.addEventListener(
    "submit",
    async function(event) {

        event.preventDefault();


        const message =
            chatInput.value.trim();


        if (!message) {

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
                    "";

                loadMessages();

            } else {

                alert(
                    result.message
                    ||
                    "Unable to send message."
                );

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

    if (pollingMessages) {

        return;

    }


    pollingMessages =
        true;


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
            &&
            Array.isArray(
                result.messages
            )
        ) {

            result.messages.forEach(
                function(message) {

                    lastMessageId =
                        Math.max(
                            lastMessageId,
                            parseInt(
                                message.id,
                                10
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
            "Messages:",
            error
        );

    }


    pollingMessages =
        false;

}


/*
=========================================================
DISPLAY MESSAGE
=========================================================
*/

function appendMessage(
    message
)
{

    if (emptyChat) {

        emptyChat.style.display =
            "none";

    }


    const wrapper =
        document.createElement(
            "div"
        );


    wrapper.className =
        "message";


    if (
        message.sender_role
        ===
        "teacher"
    ) {

        wrapper.classList.add(
            "mine"
        );

    }


    const name =
        document.createElement(
            "div"
        );


    name.className =
        "message-name";


    name.textContent =
        message.sender_name;


    const bubble =
        document.createElement(
            "div"
        );


    bubble.className =
        "message-bubble";


    bubble.textContent =
        message.message;


    const time =
        document.createElement(
            "div"
        );


    time.className =
        "message-time";


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
POLLING
=========================================================
*/

setInterval(
    function() {

        pollSignals();

    },
    1000
);


setInterval(
    function() {

        loadMessages();

    },
    2000
);


/*
=========================================================
INITIAL CHAT LOAD
=========================================================
*/

loadMessages();


/*
=========================================================
PAGE EXIT
=========================================================
*/

window.addEventListener(
    "beforeunload",
    function() {

        if (localStream) {

            localStream
                .getTracks()
                .forEach(
                    function(track) {

                        track.stop();

                    }
                );

        }

    }
);

</script>


</body>

</html>
