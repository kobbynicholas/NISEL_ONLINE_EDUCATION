<?php

session_start();

require_once "../config/db.php";

/*
=========================================================
NISEL ONLINE EDUCATION
STUDENT LIVE CLASSROOM
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
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
=========================================================
STUDENT SESSION
=========================================================
*/

$student_id =
    $_SESSION["student_id"] ?? null;

$student_name =
    $_SESSION["student_name"] ?? "Student";


if (!$student_id) {

    header("Location: login.php");
    exit;
}


/*
=========================================================
BOOKING ID
=========================================================
*/

$booking_id =
    isset($_GET["id"])
        ? (int)$_GET["id"]
        : 0;


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
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .box{
            background:#fff;
            padding:40px;
            border-radius:18px;
            text-align:center;
            box-shadow:0 15px 40px rgba(0,0,0,.12);
        }

        h2{
            color:#003b70;
        }

        a{
            display:inline-block;
            margin-top:20px;
            padding:12px 20px;
            background:#003b70;
            color:#fff;
            text-decoration:none;
            border-radius:8px;
        }
        </style>

        </head>

        <body>

        <div class='box'>

            <h2>Invalid Classroom</h2>

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
LOAD STUDENT BOOKING
=========================================================

The booking must belong to the logged-in student.
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT
        b.*
    FROM bookings b

    WHERE
        b.id = ?
        AND b.student_id = ?

    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $student_id
]);

$booking = $stmt->fetch();


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
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .box{
            background:#fff;
            padding:45px;
            border-radius:18px;
            text-align:center;
            box-shadow:0 15px 40px rgba(0,0,0,.12);
            max-width:480px;
        }

        h2{
            color:#003b70;
        }

        a{
            display:inline-block;
            margin-top:20px;
            padding:12px 20px;
            background:#003b70;
            color:#fff;
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
                This lesson does not belong to your student account.
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
BOOKING INFORMATION
=========================================================
*/

$subject =
    $booking["subjects"]
    ?? "Lesson";

$curriculum =
    $booking["curriculum"]
    ?? "Curriculum";

$class_year =
    $booking["class_year"]
    ?? "Class";

$teacher_name =
    $booking["teacher_name"]
    ?? "NISEL Teacher";

$payment_status =
    strtolower(
        trim(
            $booking["payment_status"]
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

$lesson_status =
    strtolower(
        trim(
            $booking["lesson_status"]
            ?? ""
        )
    );


/*
=========================================================
PAYMENT CHECK
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
ROOM CODE
=========================================================
*/

$room_code =
    trim(
        $booking["live_room_code"]
        ?? ""
    );


/*
=========================================================
DATE / TIME
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


/*
=========================================================
API REQUESTS
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
    GET CLASS STATUS
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
                AND student_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $booking_id,
            $student_id
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
    SEND SIGNAL TO TEACHER
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
                'student',
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
    GET TEACHER SIGNALS
    =====================================================
    */

    if ($action === "get_signals") {

        $last_id =
            isset($_POST["last_id"])
                ? (int)$_POST["last_id"]
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
                AND sender_role = 'teacher'
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
                'student',
                ?,
                ?
            )
        ");

        $stmt->execute([
            $booking_id,
            $room_code,
            $student_name,
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
NISEL Student Live Classroom
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
   TOP BAR
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
        rgba(0,51,102,.98);

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

    font-weight: 900;

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
        minmax(0,1fr)
        340px;

}


/* =====================================================
   VIDEO AREA
===================================================== */

.video-area {

    position: relative;

    overflow: hidden;

    background:
        radial-gradient(
            circle at center,
            #17243b 0%,
            #070d18 65%
        );

}


.teacher-video {

    position: absolute;

    top: 15px;

    left: 15px;

    right: 15px;

    bottom: 15px;

    overflow: hidden;

    border-radius: 16px;

    background: #020617;

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


.teacher-name {

    position: absolute;

    left: 15px;

    bottom: 15px;

    padding:
        7px 11px;

    border-radius: 7px;

    background:
        rgba(0,0,0,.65);

    font-size: 11px;

    z-index: 5;

}


/* =====================================================
   STUDENT SELF VIDEO
===================================================== */

.student-video {

    position: absolute;

    right: 30px;

    bottom: 95px;

    width: 230px;

    height: 145px;

    z-index: 30;

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

    transform: scaleX(-1);

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
   WAITING SCREEN
===================================================== */

.waiting {

    position: absolute;

    inset: 0;

    z-index: 20;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(2,6,23,.88);

}


.waiting-card {

    width:
        min(470px,90%);

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

    margin:
        0 auto 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

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


.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 17px;

    padding:
        7px 12px;

    border-radius: 30px;

    background: #3a3014;

    color: #f7d66a;

    font-size: 11px;

    font-weight: 800;

}


.status-badge.live {

    background: #123b2a;

    color: #72e2a4;

}


.status-badge.ended {

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


.join-button {

    height: 44px;

    padding:
        0 18px;

    border: none;

    border-radius: 24px;

    background: #16a34a;

    color: #fff;

    font-weight: 800;

    cursor: pointer;

}


.join-button:hover {

    background: #15803d;

}


.leave-button {

    display: none;

    height: 44px;

    padding:
        0 17px;

    border: none;

    border-radius: 24px;

    background: #dc3545;

    color: #fff;

    font-weight: 800;

    cursor: pointer;

}


/* =====================================================
   SIDEBAR
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

    padding: 10px;

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
   BLOCKED
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
        min(460px,90%);

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

@media(max-width:950px) {

    .app {

        grid-template-columns:1fr;

    }


    .sidebar {

        display:none;

    }

}


@media(max-width:600px) {

    .logo-text {

        display:none;

    }


    .room {

        display:none;

    }


    .student-video {

        width:150px;

        height:100px;

        right:15px;

        bottom:90px;

    }


    .controls {

        max-width:96%;

    }


    .control {

        width:40px;

        height:40px;

    }

}

</style>

</head>


<body>


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
                Student Live Classroom
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


<div class="app">


    <!-- =================================================
         VIDEO AREA
    ================================================== -->

    <section class="video-area">


        <div class="teacher-video">


            <video
                id="teacherVideo"
                autoplay
                playsinline
            ></video>


            <div class="teacher-name">

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
                        Waiting for Teacher
                    </h2>


                    <p
                        id="waitingMessage"
                    >

                        Your teacher has not started
                        this live class yet.

                    </p>


                    <div
                        class="status-badge"
                        id="statusBadge"
                    >

                        ● WAITING

                    </div>

                </div>

            </div>

        </div>


        <!-- STUDENT SELF VIEW -->

        <div class="student-video">

            <video
                id="studentVideo"
                autoplay
                muted
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
                id="fullscreenButton"
                title="Fullscreen"
            >
                ⛶
            </button>


            <button
                type="button"
                class="join-button"
                id="joinButton"
            >
                🎥 Join Class
            </button>


            <button
                type="button"
                class="leave-button"
                id="leaveButton"
            >
                Leave Class
            </button>

        </div>


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

                        Your payment has not yet been
                        confirmed for this lesson.

                    </p>

                </div>

            </div>

        <?php elseif ($is_cancelled): ?>

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
                        and cannot be joined.

                    </p>

                </div>

            </div>

        <?php endif; ?>


    </section>


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


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
                        Teacher
                    </span>

                    <span>
                        <?= e($teacher_name) ?>
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


        <div class="chat-header">

            💬 Classroom Chat

        </div>


        <div
            class="chat-messages"
            id="chatMessages"
        >

            <div
                class="empty-chat"
                id="emptyChat"
            >

                Chat with your teacher here.

            </div>

        </div>


        <form
            class="chat-form"
            id="chatForm"
        >

            <input
                type="text"
                id="chatInput"
                class="chat-input"
                placeholder="Message your teacher..."
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
NISEL STUDENT CLASSROOM
WEBRTC
=========================================================
*/


const BOOKING_ID =
    <?= (int)$booking_id ?>;


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


const joinButton =
    document.getElementById(
        "joinButton"
    );


const leaveButton =
    document.getElementById(
        "leaveButton"
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
WEBRTC
=========================================================
*/

let localStream = null;

let peerConnection = null;

let remoteStream = null;

let joinedClass = false;

let lastSignalId = 0;

let lastMessageId = 0;

let pollingSignals = false;

let pollingMessages = false;

let pendingIceCandidates = [];


/*
=========================================================
STUN
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
STATUS
=========================================================
*/

function setStatus(
    type,
    icon,
    title,
    message
)
{

    waitingIcon.textContent =
        icon;


    waitingTitle.textContent =
        title;


    waitingMessage.textContent =
        message;


    statusBadge.className =
        "status-badge";


    if (type === "live") {

        statusBadge.classList.add(
            "live"
        );

        statusBadge.textContent =
            "● LIVE";

    }


    else if (type === "ended") {

        statusBadge.classList.add(
            "ended"
        );

        statusBadge.textContent =
            "● ENDED";

    }


    else {

        statusBadge.textContent =
            "● WAITING";

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


    teacherVideo.srcObject =
        remoteStream;


    /*
    -----------------------------------------
    REMOTE TRACK
    -----------------------------------------
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
                                            current
                                        ) {

                                            return (
                                                current.id
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


            teacherVideo
                .play()
                .catch(
                    function() {}
                );

        };


    /*
    -----------------------------------------
    ICE
    -----------------------------------------
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
    -----------------------------------------
    CONNECTION STATE
    -----------------------------------------
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
                "Student WebRTC:",
                state
            );


            if (state === "connected") {

                waitingScreen.style.display =
                    "none";


                setStatus(
                    "live",
                    "🎥",
                    "Live Classroom",
                    "You are connected to your teacher."
                );

            }


            if (
                state === "connecting"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "waiting",
                    "🔄",
                    "Connecting...",
                    "Connecting to your teacher."
                );

            }


            if (
                state === "disconnected"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "waiting",
                    "🔄",
                    "Connection Interrupted",
                    "Waiting for your teacher to reconnect."
                );

            }


            if (
                state === "failed"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "waiting",
                    "⚠️",
                    "Connection Failed",
                    "The live connection could not be established."
                );

            }

        };


    return peerConnection;
}


/*
=========================================================
START LOCAL MEDIA
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


        studentVideo.srcObject =
            localStream;


        studentVideo
            .play()
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
            "Please allow camera and microphone access to join the live classroom."
        );


        return null;

    }

}


/*
=========================================================
JOIN CLASS
=========================================================
*/

joinButton.addEventListener(
    "click",
    async function() {

        if (joinedClass) {

            return;

        }


        const stream =
            await startLocalMedia();


        if (!stream) {

            return;

        }


        joinedClass =
            true;


        joinButton.style.display =
            "none";


        leaveButton.style.display =
            "inline-flex";


        setStatus(
            "waiting",
            "🔄",
            "Connecting...",
            "Connecting to your teacher."
        );


        /*
        -----------------------------------------
        SEND READY
        -----------------------------------------
        */

        await sendSignal(
            "ready",
            {
                ready: true
            }
        );


        /*
        -----------------------------------------
        START SIGNAL POLLING
        -----------------------------------------
        */

        pollSignals();

    }
);


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
        !joinedClass
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
            "Signal polling error:",
            error
        );

    }


    pollingSignals =
        false;

}


/*
=========================================================
PROCESS TEACHER SIGNAL
=========================================================
*/

async function processSignal(
    signal
)
{

    /*
    -----------------------------------------
    TEACHER OFFER
    -----------------------------------------
    */

    if (
        signal.signal_type
        ===
        "offer"
    ) {

        await handleOffer(
            signal
        );

        return;

    }


    /*
    -----------------------------------------
    TEACHER ICE
    -----------------------------------------
    */

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


    /*
    -----------------------------------------
    TEACHER HANGUP
    -----------------------------------------
    */

    if (
        signal.signal_type
        ===
        "hangup"
    ) {

        setStatus(
            "waiting",
            "📴",
            "Teacher Left",
            "Your teacher has left the classroom."
        );


        waitingScreen.style.display =
            "flex";

    }

}


/*
=========================================================
HANDLE OFFER
=========================================================
*/

async function handleOffer(
    signal
)
{

    const pc =
        createPeerConnection();


    try {

        const offer =
            JSON.parse(
                signal.signal_data
            );


        await pc.setRemoteDescription(
            new RTCSessionDescription(
                offer
            )
        );


        await processPendingIce();


        const answer =
            await pc.createAnswer();


        await pc.setLocalDescription(
            answer
        );


        await sendSignal(
            "answer",
            answer
        );


        setStatus(
            "waiting",
            "📡",
            "Joining Classroom",
            "Finishing connection to your teacher."
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
HANDLE ICE CANDIDATE
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
PROCESS PENDING ICE
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
                "Pending ICE error:",
                error
            );

        }

    }

}


/*
=========================================================
CHECK CLASS STATUS
=========================================================
*/

async function checkClassStatus()
{

    const formData =
        new FormData();


    formData.append(
        "classroom_action",
        "get_status"
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

            return;

        }


        const status =
            String(
                result.status
                || "waiting"
            ).toLowerCase();


        /*
        -----------------------------------------
        LIVE
        -----------------------------------------
        */

        if (status === "live") {

            if (!joinedClass) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "live",
                    "🎥",
                    "Class is Live",
                    "Your teacher has started the class. Click Join Class to enter."
                );

            }

            return;

        }


        /*
        -----------------------------------------
        ENDED
        -----------------------------------------
        */

        if (status === "ended") {

            waitingScreen.style.display =
                "flex";


            joinButton.style.display =
                "none";


            leaveButton.style.display =
                "none";


            setStatus(
                "ended",
                "📴",
                "Class Ended",
                "Your teacher has ended this lesson."
            );


            return;

        }


        /*
        -----------------------------------------
        WAITING
        -----------------------------------------
        */

        if (!joinedClass) {

            waitingScreen.style.display =
                "flex";


            setStatus(
                "waiting",
                "🎓",
                "Waiting for Teacher",
                "Your teacher has not started this live class yet."
            );

        }

    } catch (error) {

        console.error(
            "Status error:",
            error
        );

    }

}


/*
=========================================================
LEAVE CLASS
=========================================================
*/

leaveButton.addEventListener(
    "click",
    async function() {

        const confirmed =
            confirm(
                "Leave the live classroom?"
            );


        if (!confirmed) {

            return;

        }


        await sendSignal(
            "hangup",
            {
                reason: "student_left"
            }
        );


        joinedClass =
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


        joinButton.style.display =
            "inline-flex";


        leaveButton.style.display =
            "none";


        waitingScreen.style.display =
            "flex";


        setStatus(
            "waiting",
            "🎓",
            "You Left the Class",
            "Click Join Class to reconnect."
        );

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


        const enabled =
            tracks[0].enabled;


        tracks.forEach(
            function(track) {

                track.enabled =
                    !enabled;

            }
        );


        if (enabled) {

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


        const enabled =
            tracks[0].enabled;


        tracks.forEach(
            function(track) {

                track.enabled =
                    !enabled;

            }
        );


        if (enabled) {

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
CHAT
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
LOAD MESSAGES
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
            "Message error:",
            error
        );

    }


    pollingMessages =
        false;

}


/*
=========================================================
APPEND MESSAGE
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
        "student"
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
POLL CLASS STATUS
=========================================================
*/

setInterval(
    function() {

        checkClassStatus();

    },
    3000
);


/*
=========================================================
POLL SIGNALS
=========================================================
*/

setInterval(
    function() {

        if (joinedClass) {

            pollSignals();

        }

    },
    1000
);


/*
=========================================================
POLL CHAT
=========================================================
*/

setInterval(
    function() {

        loadMessages();

    },
    2000
);


/*
=========================================================
INITIAL LOAD
=========================================================
*/

checkClassStatus();

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
