<?php

session_start();

require "../teacher_auth.php";
require "../config/db.php";

/*
=========================================================
NISEL ONLINE EDUCATION
TEACHER CLASSROOM

URL:

teacher/classroom.php?id=BOOKING_ID

Example:

teacher/classroom.php?id=25
=========================================================
*/


/*
=========================================================
PDO SETTINGS
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
LOGGED-IN TEACHER
=========================================================
*/

$teacher_id =
    $_SESSION['teacher_id'] ?? '';

$teacher_name =
    $_SESSION['teacher_name']
    ?? 'Teacher';


if (empty($teacher_id)) {

    header("Location: login.php");
    exit;

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
BOOKING ID
=========================================================
*/

$booking_id =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


if ($booking_id <= 0) {

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
                No valid lesson was supplied.
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

SECURITY:

The booking ID AND teacher ID must match.

This prevents a teacher from changing:

?id=25

to access another teacher's classroom.
=========================================================
*/

$stmt = $pdo->prepare("

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

        b.teacher_id,

        b.teacher_name,

        b.assignment_status,

        b.lesson_date,

        b.lesson_time,

        b.lesson_status,

        b.live_room_code,

        b.live_status,

        b.live_started_at,

        b.live_ended_at

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
        <div style='
            font-family:Arial;
            text-align:center;
            padding:70px;
        '>

            <h2 style='color:#003366;'>
                Classroom Access Denied
            </h2>

            <p>
                This lesson does not exist or has not
                been assigned to you.
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
BOOKING STATUS
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
            ?? 'scheduled'
        )
    );

$live_status =
    strtolower(
        trim(
            $booking['live_status']
            ?? 'waiting'
        )
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


$can_teach =
    $is_paid &&
    !$is_cancelled;


/*
=========================================================
GENERATE ROOM CODE

If a room does not exist yet, create one.

The room is unique to this booking.
=========================================================
*/

if (
    empty($booking['live_room_code'])
) {

    $room_code =
        'NISEL-' .
        strtoupper(
            bin2hex(
                random_bytes(5)
            )
        );

    $updateRoom =
        $pdo->prepare("

            UPDATE bookings

            SET
                live_room_code = ?,
                live_status = 'waiting'

            WHERE
                id = ?

                AND teacher_id = ?

        ");

    $updateRoom->execute([
        $room_code,
        $booking_id,
        $teacher_id
    ]);

    $booking['live_room_code'] =
        $room_code;

    $booking['live_status'] =
        'waiting';

    $live_status =
        'waiting';
}


/*
=========================================================
API REQUESTS
=========================================================
*/

if (
    isset(
        $_POST['classroom_action']
    )
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    $action =
        $_POST['classroom_action'];


    /*
    =====================================================
    START CLASS
    =====================================================
    */

   if (
    $action ===
    'start_class'
) {

    if (!$is_paid) {

        echo json_encode([
            'success' => false,
            'message' =>
                "The student's payment has not been confirmed."
        ]);

        exit;
    }

    if ($is_cancelled) {

        echo json_encode([
            'success' => false,
            'message' =>
                'This lesson has been cancelled.'
        ]);

        exit;
    }

    /*
    ==============================================
    UPDATE CLASS STATUS
    ==============================================
    */

    $start = $pdo->prepare("
        UPDATE bookings
        SET
            live_status = 'live',
            live_started_at = COALESCE(
                live_started_at,
                NOW()
            ),
            live_ended_at = NULL
        WHERE
            id = ?
            AND teacher_id = ?
    ");

    $start->execute([
        $booking_id,
        $teacher_id
    ]);

    echo json_encode([
        'success' => true,
        'status' => 'live',
        'room_code' =>
            $booking['live_room_code']
    ]);

    exit;
}

        /*
        ---------------------------------------------
        UPDATE CLASS STATUS
        ---------------------------------------------
        */

        $start =
            $pdo->prepare("

                UPDATE bookings

                SET

                    live_status = 'live',

                    live_started_at =
                        COALESCE(
                            live_started_at,
                            NOW()
                        ),

                    live_ended_at = NULL

                WHERE

                    id = ?

                    AND teacher_id = ?

            ");

        $start->execute([
            $booking_id,
            $teacher_id
        ]);


        echo json_encode([
            'success' => true,
            'status' => 'live',
            'room_code' =>
                $booking['live_room_code']
        ]);

        exit;
    }


    /*
    =====================================================
    END CLASS
    =====================================================
    */

    if (
        $action ===
        'end_class'
    ) {

        $end =
            $pdo->prepare("

                UPDATE bookings

                SET

                    live_status = 'ended',

                    live_ended_at = NOW(),

                    lesson_status = 'completed'

                WHERE

                    id = ?

                    AND teacher_id = ?

            ");

        $end->execute([
            $booking_id,
            $teacher_id
        ]);


        /*
        ---------------------------------------------
        SEND HANGUP SIGNAL
        ---------------------------------------------
        */

        $hangup =
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
                    'teacher',
                    'hangup',
                    ?
                )

            ");

        $hangup->execute([
            $booking_id,
            $booking['live_room_code'],
            json_encode([
                'message' =>
                    'Teacher ended the class'
            ])
        ]);


        echo json_encode([
            'success' => true,
            'status' => 'ended'
        ]);

        exit;
    }


    /*
    =====================================================
    SEND WEBRTC SIGNAL
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


        $allowedSignals = [

            'offer',

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


        $signal =
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
                    'teacher',
                    ?,
                    ?
                )

            ");


        $signal->execute([
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
    GET STUDENT SIGNALS
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
                ? (int)
                    $_POST['last_id']
                : 0;


        $signals =
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

                    AND sender_role = 'student'

                    AND id > ?

                ORDER BY id ASC

                LIMIT 100

            ");


        $signals->execute([
            $booking_id,
            $booking['live_room_code'],
            $last_id
        ]);


        echo json_encode([
            'success' => true,
            'signals' =>
                $signals->fetchAll()
        ]);

        exit;
    }


    /*
    =====================================================
    SEND CHAT MESSAGE
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


        if (
            mb_strlen($message)
            > 1000
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Message is too long.'
            ]);

            exit;
        }


        $chat =
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
                    'teacher',
                    ?,
                    ?
                )

            ");


        $chat->execute([
            $booking_id,
            $booking['live_room_code'],
            $teacher_name,
            $message
        ]);


        echo json_encode([
            'success' => true
        ]);

        exit;
    }


    /*
    =====================================================
    GET CHAT MESSAGES
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


        $messages =
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


        $messages->execute([
            $booking_id,
            $booking['live_room_code'],
            $last_message_id
        ]);


        echo json_encode([
            'success' => true,
            'messages' =>
                $messages->fetchAll()
        ]);

        exit;
    }


    /*
    =====================================================
    CLASS STATUS
    =====================================================
    */

    if (
        $action ===
        'get_status'
    ) {

        $status =
            $pdo->prepare("

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


        $status->execute([
            $booking_id,
            $teacher_id
        ]);


        $statusData =
            $status->fetch();


        echo json_encode([
            'success' => true,
            'status' =>
                $statusData['live_status']
                ?? 'waiting',
            'started_at' =>
                $statusData['live_started_at']
                ?? null,
            'ended_at' =>
                $statusData['live_ended_at']
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
        'success' => false,
        'message' =>
            'Unknown classroom action.'
    ]);

    exit;



/*
=========================================================
DISPLAY VALUES
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


$student_display =
    $booking['student_name']
    ?? 'Student';


$room_code =
    $booking['live_room_code'];

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

    Teacher Classroom |
    <?= h($booking['subjects']) ?>

</title>


<style>

/* =====================================================
   GENERAL
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
   HEADER
===================================================== */

.header {

    height: 70px;

    position: fixed;

    top: 0;

    left: 0;

    right: 0;

    z-index: 100;

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    padding:
        0 22px;

    background: #003366;

    border-bottom:
        1px solid
        rgba(255,255,255,.12);
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

    font-weight: bold;

    font-size: 20px;
}


.brand-text strong {

    display: block;

    font-size: 15px;
}


.brand-text span {

    display: block;

    font-size: 11px;

    color: #9ddfff;
}


.header-right {

    display: flex;

    align-items: center;

    gap: 15px;
}


.room-code {

    font-family: monospace;

    font-size: 11px;

    color: #cbd5e1;
}


.exit {

    color: white;

    text-decoration: none;

    background: #dc3545;

    padding:
        9px 13px;

    border-radius: 7px;

    font-size: 12px;

    font-weight: bold;
}


/* =====================================================
   MAIN CLASSROOM
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


.main-video {

    position: absolute;

    inset: 15px;

    background: #111827;

    border-radius: 13px;

    overflow: hidden;

    border:
        1px solid
        rgba(255,255,255,.1);
}


.main-video video {

    width: 100%;

    height: 100%;

    object-fit: cover;

    background: #020617;
}


.video-label {

    position: absolute;

    left: 12px;

    bottom: 12px;

    background:
        rgba(0,0,0,.65);

    padding:
        6px 10px;

    border-radius: 6px;

    font-size: 11px;

    z-index: 10;
}


/* =====================================================
   STUDENT PREVIEW
===================================================== */

.student-preview {

    position: absolute;

    right: 25px;

    bottom: 95px;

    width: 230px;

    height: 145px;

    z-index: 20;

    border:
        2px solid
        rgba(255,255,255,.25);

    border-radius: 10px;

    overflow: hidden;

    background: #111827;

    box-shadow:
        0 12px 35px
        rgba(0,0,0,.5);
}


.student-preview video {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


/* =====================================================
   WAITING SCREEN
===================================================== */

.waiting-screen {

    position: absolute;

    inset: 0;

    z-index: 30;

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


.waiting-card {

    width: min(450px,90%);

    padding: 35px;

    text-align: center;

    background:
        rgba(15,23,42,.94);

    border:
        1px solid
        rgba(255,255,255,.1);

    border-radius: 16px;
}


.waiting-icon {

    font-size: 50px;

    margin-bottom: 15px;
}


.waiting-card h2 {

    margin:
        0 0 10px;
}


.waiting-card p {

    color: #cbd5e1;

    font-size: 14px;

    line-height: 1.6;
}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-block;

    margin-top: 12px;

    padding:
        7px 12px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: bold;
}


.status-waiting {

    background: #3f3212;

    color: #ffd966;
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

    left: 50%;

    bottom: 20px;

    transform:
        translateX(-50%);

    z-index: 50;

    display: flex;

    gap: 8px;

    padding: 9px;

    border-radius: 30px;

    background:
        rgba(15,23,42,.94);

    border:
        1px solid
        rgba(255,255,255,.1);
}


.control {

    width: 45px;

    height: 45px;

    border: none;

    border-radius: 50%;

    background: #253247;

    color: white;

    cursor: pointer;

    font-size: 17px;
}


.control:hover {

    background: #334155;
}


.control.active {

    background: #dc3545;
}


.start-button {

    border: none;

    border-radius: 22px;

    padding:
        0 18px;

    background: #16a34a;

    color: white;

    font-weight: bold;

    cursor: pointer;
}


.start-button:hover {

    background: #15803d;
}


.end-button {

    border: none;

    border-radius: 22px;

    padding:
        0 18px;

    background: #dc3545;

    color: white;

    font-weight: bold;

    cursor: pointer;

    display: none;
}


/* =====================================================
   SIDE PANEL
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
   LESSON DETAILS
===================================================== */

.lesson {

    padding: 20px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);
}


.lesson h2 {

    margin:
        0 0 6px;

    font-size: 19px;
}


.lesson-subtitle {

    color: #67d5ff;

    font-size: 12px;

    margin-bottom: 15px;
}


.info {

    display: flex;

    justify-content:
        space-between;

    gap: 10px;

    margin-bottom: 9px;

    font-size: 12px;
}


.info span:first-child {

    color: #94a3b8;
}


.info span:last-child {

    text-align: right;

    color: #e2e8f0;
}


.payment {

    display: inline-block;

    margin-top: 10px;

    padding:
        5px 9px;

    border-radius: 5px;

    background: #123a29;

    color: #69e6a1;

    font-size: 10px;

    font-weight: bold;
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


.chat-name {

    color: #94a3b8;

    font-size: 10px;

    margin-bottom: 3px;
}


.chat-bubble {

    display: inline-block;

    padding:
        8px 11px;

    background: #253247;

    border-radius: 9px;

    font-size: 12px;

    line-height: 1.45;

    text-align: left;
}


.mine .chat-bubble {

    background: #0055a5;
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

    background: #1e293b;

    color: white;

    border-radius: 7px;

    padding: 10px;

    font-size: 12px;
}


.chat-send {

    border: none;

    background: #0877c9;

    color: white;

    border-radius: 7px;

    padding:
        0 13px;

    cursor: pointer;

    font-weight: bold;
}


/* =====================================================
   NOT PAID
===================================================== */

.not-paid {

    position: absolute;

    inset: 0;

    z-index: 60;

    background:
        rgba(5,10,18,.96);

    display: flex;

    align-items: center;

    justify-content: center;
}


.not-paid-card {

    text-align: center;

    width: min(500px,90%);

    padding: 35px;

    border-radius: 15px;

    background: #111827;

    border:
        1px solid
        rgba(255,255,255,.1);
}


.not-paid-card h2 {

    margin-top: 0;
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


    .student-preview {

        width: 160px;

        height: 105px;

        right: 12px;

        bottom: 85px;

    }


    .room-code {

        display: none;

    }

}


@media(max-width:600px) {

    .brand-text {

        display: none;

    }


    .header {

        padding:
            0 12px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">


    <div class="brand">


        <div class="brand-icon">

            N

        </div>


        <div class="brand-text">

            <strong>
                NISEL ONLINE EDUCATION
            </strong>

            <span>
                Teacher Classroom
            </span>

        </div>


    </div>


    <div class="header-right">


        <div class="room-code">

            ROOM:

            <?= h($room_code) ?>

        </div>


        <a
            href="schedule.php"
            class="exit"
        >

            ✕ Exit

        </a>


    </div>


</header>


<!-- =====================================================
     CLASSROOM
===================================================== -->

<div class="classroom">


    <!-- =================================================
         VIDEO
    ================================================== -->

    <main class="video-area">


        <div class="main-video">


            <video
                id="teacherVideo"
                autoplay
                muted
                playsinline
            ></video>


            <div class="video-label">

                👨‍🏫

                <?= h($teacher_name) ?>

            </div>


            <!-- WAITING SCREEN -->

            <div
                class="waiting-screen"
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

                        Click "Start Class" below
                        to open the virtual classroom.

                    </p>


                    <div
                        id="statusBadge"
                        class="
                            status
                            status-waiting
                        "
                    >

                        ● Waiting

                    </div>


                </div>

            </div>


        </div>


        <!-- STUDENT VIDEO -->

        <div
            class="student-preview"
        >

            <video
                id="studentVideo"
                autoplay
                playsinline
            ></video>


            <div
                class="video-label"
            >

                👨‍🎓

                <?= h($student_display) ?>

            </div>

        </div>


        <!-- CONTROLS -->

        <div class="controls">


            <button
                type="button"
                id="micButton"
                class="control"
                title="Microphone"
            >

                🎤

            </button>


            <button
                type="button"
                id="cameraButton"
                class="control"
                title="Camera"
            >

                📷

            </button>


            <button
                type="button"
                id="screenButton"
                class="control"
                title="Share Screen"
            >

                🖥️

            </button>


            <button
                type="button"
                id="fullscreenButton"
                class="control"
                title="Fullscreen"
            >

                ⛶

            </button>


            <button
                type="button"
                id="startButton"
                class="start-button"
            >

                ▶ Start Class

            </button>


            <button
                type="button"
                id="endButton"
                class="end-button"
            >

                ■ End Class

            </button>


        </div>


    </main>


    <!-- =================================================
         SIDE PANEL
    ================================================== -->

    <aside class="side-panel">


        <!-- LESSON -->

        <div class="lesson">


            <h2>

                <?= h(
                    $booking['subjects']
                ) ?>

            </h2>


            <div class="lesson-subtitle">

                <?= h(
                    $booking['curriculum']
                ) ?>

                ·

                <?= h(
                    $booking['class_year']
                ) ?>

            </div>


            <div class="info">

                <span>
                    Student
                </span>

                <span>
                    <?= h(
                        $student_display
                    ) ?>
                </span>

            </div>


            <div class="info">

                <span>
                    Date
                </span>

                <span>
                    <?= h(
                        $lesson_date_display
                    ) ?>
                </span>

            </div>


            <div class="info">

                <span>
                    Time
                </span>

                <span>
                    <?= h(
                        $lesson_time_display
                    ) ?>
                </span>

            </div>


            <div class="info">

                <span>
                    Booking
                </span>

                <span>
                    <?= h(
                        $booking['booking_reference']
                    ) ?>
                </span>

            </div>


            <span class="payment">

                ✓ PAYMENT CONFIRMED

            </span>


        </div>


        <!-- CHAT -->

        <div class="chat-title">

            💬 Classroom Chat

        </div>


        <div
            class="chat-messages"
            id="chatMessages"
        >

            <div
                id="chatEmpty"
                style="
                    text-align:center;
                    color:#64748b;
                    font-size:11px;
                    padding:20px;
                "
            >

                Chat is ready.

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
                placeholder="Message student..."
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
TEACHER CLASSROOM
WEBRTC
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


const CLASSROOM_URL =
    "classroom.php?id="
    +
    BOOKING_ID;


const STUDENT_NAME =
    <?= json_encode(
        $student_display
    ) ?>;


const TEACHER_NAME =
    <?= json_encode(
        $teacher_name
    ) ?>;


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


const startButton =
    document.getElementById(
        "startButton"
    );


const endButton =
    document.getElementById(
        "endButton"
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

let screenStream = null;

let lastSignalId = 0;

let lastMessageId = 0;

let classStarted = false;

let polling = false;


/*
=========================================================
STUN

For production we will add TURN.
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
STATUS UI
=========================================================
*/

function setStatus(
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
        "status status-"
        +
        type;


    if (
        type ===
        "live"
    ) {

        statusBadge.textContent =
            "● LIVE";

    } else if (
        type ===
        "ended"
    ) {

        statusBadge.textContent =
            "● ENDED";

    } else if (
        type ===
        "error"
    ) {

        statusBadge.textContent =
            "● ERROR";

    } else {

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

    if (
        peerConnection
    ) {

        return peerConnection;

    }


    peerConnection =
        new RTCPeerConnection(
            rtcConfiguration
        );


    /*
    -----------------------------------------------
    REMOTE STUDENT STREAM
    -----------------------------------------------
    */

    remoteStream =
        new MediaStream();


    studentVideo.srcObject =
        remoteStream;


    /*
    -----------------------------------------------
    STUDENT TRACK
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


            studentVideo.play()
                .catch(
                    () => {}
                );

        };


    /*
    -----------------------------------------------
    ICE
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
                "Connection:",
                state
            );


            if (
                state ===
                "connected"
            ) {

                waitingScreen.style.display =
                    "none";


                setStatus(
                    "live",
                    "🎥",
                    "Class is Live",
                    "You are connected to the student."
                );

            }


            if (
                state ===
                "connecting"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "waiting",
                    "🔄",
                    "Connecting...",
                    "Waiting for the student to connect."
                );

            }


            if (
                state ===
                "disconnected"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "waiting",
                    "🔄",
                    "Connection interrupted",
                    "Waiting for the student to reconnect."
                );

            }


            if (
                state ===
                "failed"
            ) {

                waitingScreen.style.display =
                    "flex";


                setStatus(
                    "error",
                    "⚠️",
                    "Connection failed",
                    "The WebRTC connection could not be established."
                );

            }

        };


    return peerConnection;
}


/*
=========================================================
START CAMERA/MIC
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
            await navigator
                .mediaDevices
                .getUserMedia({

                    video: true,

                    audio: true

                });


        teacherVideo.srcObject =
            localStream;


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
            error
        );


        setStatus(
            "error",
            "🎤",
            "Camera/Microphone Error",
            "Please allow camera and microphone access."
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

        if (
            classStarted
        ) {

            return;

        }


        /*
        -------------------------------------------
        CAMERA
        -------------------------------------------
        */

        const stream =
            await startLocalMedia();


        if (
            !stream
        ) {

            return;

        }


        /*
        -------------------------------------------
        DATABASE
        -------------------------------------------
        */

        try {

            const formData =
                new FormData();


            formData.append(
                "classroom_action",
                "start_class"
            );


            const response =
                await fetch(
                    CLASSROOM_URL,
                    {
                        method: "POST",
                        body: formData,
                        credentials:
                            "same-origin"
                    }
                );


            const result =
                await response.json();


            if (
                !result.success
            ) {

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
                "inline-block";


            setStatus(
                "live",
                "🎥",
                "Class Started",
                "Waiting for the student to join..."
            );


            /*
            ---------------------------------------
            CREATE OFFER
            ---------------------------------------
            */

            await createOffer();


        } catch (error) {

            console.error(
                error
            );

            alert(
                "Unable to start the classroom."
            );

        }

    }
);


/*
=========================================================
CREATE OFFER
=========================================================
*/

async function createOffer()
{

    const pc =
        createPeerConnection();


    try {

        const offer =
            await pc.createOffer({

                offerToReceiveAudio:
                    true,

                offerToReceiveVideo:
                    true

            });


        await pc.setLocalDescription(
            offer
        );


        await sendSignal(
            "offer",
            offer
        );


        setStatus(
            "waiting",
            "📡",
            "Waiting for Student",
            "The classroom is open. Waiting for the student to connect."
        );


    } catch (error) {

        console.error(
            "Offer error:",
            error
        );


        setStatus(
            "error",
            "⚠️",
            "Unable to start video",
            "The WebRTC offer could not be created."
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
                    credentials:
                        "same-origin"
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
POLL STUDENT SIGNALS
=========================================================
*/

async function pollSignals()
{

    if (
        !classStarted ||
        polling
    ) {

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
                    credentials:
                        "same-origin"
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
                    "answer"
                ) {

                    await handleAnswer(
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


            }

        }


    } catch (error) {

        console.error(
            "Signal polling error:",
            error
        );

    } finally {

        polling = false;

    }

}


/*
=========================================================
HANDLE STUDENT ANSWER
=========================================================
*/

async function handleAnswer(
    signal
)
{

    try {

        const answer =
            JSON.parse(
                signal.signal_data
            );


        const pc =
            createPeerConnection();


        await pc.setRemoteDescription(
            new RTCSessionDescription(
                answer
            )
        );


        setStatus(
            "live",
            "🎥",
            "Connecting Student",
            "The student has joined the classroom."
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
HANDLE STUDENT ICE
=========================================================
*/

async function handleIceCandidate(
    signal
)
{

    try {

        const candidate =
            JSON.parse(
                signal.signal_data
            );


        const pc =
            createPeerConnection();


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
END CLASS
=========================================================
*/

endButton.addEventListener(
    "click",
    async function() {

        if (
            !confirm(
                "Are you sure you want to end this class?"
            )
        ) {

            return;

        }


        try {

            const formData =
                new FormData();


            formData.append(
                "classroom_action",
                "end_class"
            );


            const response =
                await fetch(
                    CLASSROOM_URL,
                    {
                        method: "POST",
                        body: formData,
                        credentials:
                            "same-origin"
                    }
                );


            const result =
                await response.json();


            if (
                result.success
            ) {

                classStarted =
                    false;


                if (
                    peerConnection
                ) {

                    peerConnection.close();

                    peerConnection =
                        null;

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


                waitingScreen.style.display =
                    "flex";


                startButton.style.display =
                    "inline-block";


                endButton.style.display =
                    "none";


                setStatus(
                    "ended",
                    "📴",
                    "Class Ended",
                    "This lesson has been completed."
                );

            }

        } catch (error) {

            console.error(
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

        if (
            !localStream
        ) {

            return;

        }


        const tracks =
            localStream.getAudioTracks();


        if (
            tracks.length === 0
        ) {

            return;

        }


        const enabled =
            tracks[0].enabled;


        tracks.forEach(
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

            return;

        }


        const tracks =
            localStream.getVideoTracks();


        if (
            tracks.length === 0
        ) {

            return;

        }


        const enabled =
            tracks[0].enabled;


        tracks.forEach(
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
SCREEN SHARING
=========================================================
*/

screenButton.addEventListener(
    "click",
    async function() {

        if (
            !peerConnection
        ) {

            alert(
                "Start the class first."
            );

            return;

        }


        try {

            screenStream =
                await navigator
                    .mediaDevices
                    .getDisplayMedia({

                        video: true

                    });


            const screenTrack =
                screenStream
                    .getVideoTracks()[0];


            const sender =
                peerConnection
                    .getSenders()
                    .find(
                        s =>
                            s.track &&
                            s.track.kind
                            ===
                            "video"
                    );


            if (
                sender
            ) {

                await sender.replaceTrack(
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
                            sender &&
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
                "Screen share:",
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
    function() {

        if (
            !document.fullscreenElement
        ) {

            document.documentElement
                .requestFullscreen()
                .catch(
                    () => {}
                );

        } else {

            document
                .exitFullscreen()
                .catch(
                    () => {}
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
                        credentials:
                            "same-origin"
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
                    credentials:
                        "same-origin"
                }
            );


        const result =
            await response.json();


        if (
            !result.success
        ) {

            return;

        }


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


    } catch (error) {

        console.error(
            error
        );

    }

}


/*
=========================================================
APPEND CHAT
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
            "teacher"
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
INITIALIZATION
=========================================================
*/

loadMessages();


setInterval(
    loadMessages,
    2000
);


setInterval(
    pollSignals,
    1200
);


/*
=========================================================
PAGE EXIT
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

</script>


</body>

</html>
