<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/db.php";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


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
    $_SESSION["student_id"] ?? 0;


if (!$student_id) {

    header("Location: login.php");
    exit;

}


$student_name =
    $_SESSION["student_name"]
    ?? "Student";


/*
=========================================================
BOOKING ID
=========================================================
*/

$booking_id = 0;

if (isset($_GET["id"])) {

    $booking_id =
        (int)$_GET["id"];

} elseif (isset($_GET["booking_id"])) {

    $booking_id =
        (int)$_GET["booking_id"];

}


if ($booking_id <= 0) {

    die("
        <!DOCTYPE html>

        <html>

        <head>

        <title>
            Invalid Classroom
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
            background:white;
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
            color:white;
            text-decoration:none;
            border-radius:8px;
        }

        </style>

        </head>

        <body>

        <div class="box">

            <h2>
                Invalid Classroom
            </h2>

            <p>
                No valid booking was selected.
            </p>

            <a href="schedule.php">
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
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT *
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

$booking =
    $stmt->fetch();


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
            background:white;
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
            color:white;
            text-decoration:none;
            border-radius:8px;
        }

        </style>

        </head>

        <body>

        <div class="box">

            <h2>
                Classroom Access Denied
            </h2>

            <p>
                This lesson does not belong to your account.
            </p>

            <a href="schedule.php">
                Return to Schedule
            </a>

        </div>

        </body>

        </html>
    ");

}


/*
=========================================================
BOOKING
=========================================================
*/

$subject =
    $booking["subjects"]
    ?? "Lesson";

$teacher_name =
    $booking["teacher_name"]
    ?? "Teacher";

$curriculum =
    $booking["curriculum"]
    ?? "Curriculum";

$class_year =
    $booking["class_year"]
    ?? "Class";

$payment_status =
    $booking["payment_status"]
    ?? "Pending";

$live_status =
    strtolower(
        trim(
            $booking["live_status"]
            ?? "waiting"
        )
    );

$room_code =
    trim(
        $booking["live_room_code"]
        ?? ""
    );


/*
=========================================================
ROOM CODE
=========================================================
*/

if ($room_code === "") {

    $room_code =
        "NISEL-"
        . $booking_id
        . "-"
        . strtoupper(
            substr(
                md5(
                    uniqid(
                        (string)$booking_id,
                        true
                    )
                ),
                0,
                6
            )
        );


    $stmt = $pdo->prepare("
        UPDATE bookings
        SET live_room_code = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $room_code,
        $booking_id
    ]);

}


/*
=========================================================
PAYMENT
=========================================================
*/

$payment =
    strtolower(
        trim(
            $payment_status
        )
    );


$is_paid =
    in_array(
        $payment,
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
POST API
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
    STATUS
    =====================================================
    */

    if ($action === "get_status") {

        $stmt = $pdo->prepare("
            SELECT
                live_status,
                live_started_at,
                live_ended_at
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $booking_id
        ]);

        $status =
            $stmt->fetch();


        echo json_encode([
            "success" => true,
            "status" =>
                $status["live_status"]
                ?? "waiting",
            "started_at" =>
                $status["live_started_at"]
                ?? null,
            "ended_at" =>
                $status["live_ended_at"]
                ?? null
        ]);

        exit;
    }


    /*
    =====================================================
    SIGNAL
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


        $allowed = [
            "answer",
            "ice-candidate",
            "ready",
            "hangup"
        ];


        if (
            !in_array(
                $signal_type,
                $allowed,
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
    CHAT
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
            "success" => true
        ]);

        exit;
    }


    if ($action === "get_messages") {

        $last_message_id =
            isset($_POST["last_message_id"])
                ? (int)$_POST["last_message_id"]
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
Student Live Classroom | NISEL
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial,sans-serif;
    background: #07111f;
    color: white;
}

.top {
    height: 65px;
    background: #003b70;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
}

.logo {
    font-weight: 800;
}

.logo small {
    display: block;
    color: #a8d8f5;
    font-size: 9px;
    margin-top: 3px;
}

.back {
    color: white;
    text-decoration: none;
    background: rgba(255,255,255,.12);
    padding: 9px 13px;
    border-radius: 8px;
    font-size: 11px;
}

.main {
    display: grid;
    grid-template-columns: 1fr 320px;
    height: calc(100vh - 65px);
}

.video {
    position: relative;
    background: #020617;
    overflow: hidden;
}

#teacherVideo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#studentVideo {
    position: absolute;
    right: 20px;
    bottom: 90px;
    width: 220px;
    height: 140px;
    object-fit: cover;
    border-radius: 12px;
    border: 2px solid rgba(255,255,255,.3);
    transform: scaleX(-1);
    background: #111827;
}

.label {
    position: absolute;
    left: 20px;
    bottom: 20px;
    padding: 8px 11px;
    border-radius: 7px;
    background: rgba(0,0,0,.65);
    font-size: 11px;
}

.waiting {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(2,6,23,.94);
}

.wait-card {
    text-align: center;
    padding: 35px;
}

.wait-card h2 {
    margin: 10px 0;
}

.wait-card p {
    color: #9aa9ba;
    font-size: 13px;
    max-width: 400px;
    line-height: 1.6;
}

.join {
    border: none;
    background: #16a34a;
    color: white;
    padding: 13px 22px;
    border-radius: 9px;
    font-weight: 800;
    cursor: pointer;
}

.leave {
    display: none;
    border: none;
    background: #dc3545;
    color: white;
    padding: 13px 22px;
    border-radius: 9px;
    font-weight: 800;
    cursor: pointer;
}

.controls {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    background: rgba(15,23,42,.95);
    padding: 8px;
    border-radius: 30px;
}

.control {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    background: #263348;
    color: white;
    cursor: pointer;
}

.control.off {
    background: #b42336;
}

.sidebar {
    background: #111827;
    display: flex;
    flex-direction: column;
}

.info {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.info h2 {
    margin: 0;
}

.info p {
    color: #718096;
    font-size: 11px;
}

.row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 10px;
    border-bottom: 1px solid rgba(255,255,255,.04);
}

.row span:first-child {
    color: #718096;
}

.chat-title {
    padding: 14px 18px;
    font-size: 12px;
    font-weight: 800;
}

.messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
}

.message {
    margin-bottom: 12px;
}

.message.mine {
    text-align: right;
}

.name {
    color: #7f8da1;
    font-size: 9px;
    margin-bottom: 4px;
}

.bubble {
    display: inline-block;
    max-width: 90%;
    padding: 8px 10px;
    border-radius: 8px;
    background: #263348;
    font-size: 10px;
    text-align: left;
}

.message.mine .bubble {
    background: #075a9e;
}

.chat {
    display: flex;
    gap: 6px;
    padding: 10px;
    border-top: 1px solid rgba(255,255,255,.08);
}

.chat input {
    flex: 1;
    background: #1e293b;
    color: white;
    border: none;
    outline: none;
    border-radius: 7px;
    padding: 10px;
}

.chat button {
    border: none;
    background: #0877c9;
    color: white;
    border-radius: 7px;
    padding: 0 12px;
}

@media(max-width:850px) {

    .main {
        grid-template-columns: 1fr;
    }

    .sidebar {
        display: none;
    }

}

</style>

</head>

<body>

<header class="top">

    <div class="logo">

        NISEL ONLINE EDUCATION

        <small>
            STUDENT LIVE CLASSROOM
        </small>

    </div>

    <a
        href="schedule.php"
        class="back"
    >
        ← Schedule
    </a>

</header>


<main class="main">


<section class="video">

    <video
        id="teacherVideo"
        autoplay
        playsinline
    ></video>


    <video
        id="studentVideo"
        autoplay
        muted
        playsinline
    ></video>


    <div class="label">

        👨‍🏫
        <?= e($teacher_name) ?>

    </div>


    <div
        class="waiting"
        id="waiting"
    >

        <div class="wait-card">

            <div style="font-size:45px;">
                🎓
            </div>

            <h2 id="waitingTitle">
                Waiting for Teacher
            </h2>

            <p id="waitingText">
                Your teacher has not started this class yet.
            </p>

            <?php if ($is_paid): ?>

                <button
                    class="join"
                    id="joinButton"
                >
                    🎥 Join Live Class
                </button>

            <?php else: ?>

                <p style="color:#f0b44c;">
                    Payment has not been confirmed for this lesson.
                </p>

            <?php endif; ?>


            <button
                class="leave"
                id="leaveButton"
            >
                Leave Class
            </button>

        </div>

    </div>


    <div class="controls">

        <button
            class="control"
            id="micButton"
        >
            🎤
        </button>

        <button
            class="control"
            id="cameraButton"
        >
            📷
        </button>

        <button
            class="control"
            id="fullscreenButton"
        >
            ⛶
        </button>

    </div>

</section>


<aside class="sidebar">

    <div class="info">

        <h2>
            <?= e($subject) ?>
        </h2>

        <p>
            <?= e($curriculum) ?>
            ·
            <?= e($class_year) ?>
        </p>


        <div class="row">

            <span>
                Teacher
            </span>

            <span>
                <?= e($teacher_name) ?>
            </span>

        </div>


        <div class="row">

            <span>
                Room
            </span>

            <span>
                <?= e($room_code) ?>
            </span>

        </div>


        <div class="row">

            <span>
                Booking
            </span>

            <span>
                <?= e(
                    $booking["booking_reference"]
                    ?? $booking_id
                ) ?>
            </span>

        </div>

    </div>


    <div class="chat-title">
        💬 Classroom Chat
    </div>


    <div
        class="messages"
        id="messages"
    ></div>


    <form
        class="chat"
        id="chatForm"
    >

        <input
            id="chatInput"
            placeholder="Message teacher..."
            maxlength="2000"
            autocomplete="off"
        >

        <button>
            Send
        </button>

    </form>

</aside>

</main>


<script>

const BOOKING_ID =
    <?= (int)$booking_id ?>;

const URL =
    "classroom.php?id="
    + BOOKING_ID;


const teacherVideo =
    document.getElementById(
        "teacherVideo"
    );

const studentVideo =
    document.getElementById(
        "studentVideo"
    );

const waiting =
    document.getElementById(
        "waiting"
    );

const waitingTitle =
    document.getElementById(
        "waitingTitle"
    );

const waitingText =
    document.getElementById(
        "waitingText"
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

const messages =
    document.getElementById(
        "messages"
    );

const chatForm =
    document.getElementById(
        "chatForm"
    );

const chatInput =
    document.getElementById(
        "chatInput"
    );


let localStream = null;

let peerConnection = null;

let joined = false;

let lastSignalId = 0;

let lastMessageId = 0;

let pendingCandidates = [];


const configuration = {

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
POST
=========================================================
*/

async function post(data)
{

    const form =
        new FormData();


    Object.keys(data).forEach(
        function(key) {

            form.append(
                key,
                data[key]
            );

        }
    );


    const response =
        await fetch(
            URL,
            {
                method: "POST",
                body: form,
                credentials: "same-origin"
            }
        );


    return response.json();

}


/*
=========================================================
CHECK STATUS
=========================================================
*/

async function checkStatus()
{

    try {

        const result =
            await post({

                classroom_action:
                    "get_status"

            });


        if (!result.success) {
            return;
        }


        const status =
            String(
                result.status
                || "waiting"
            ).toLowerCase();


        if (status === "live") {

            waitingTitle.textContent =
                "Class is Live";

            waitingText.textContent =
                "Your teacher has started the lesson. Click Join Live Class to enter.";

            return;

        }


        if (status === "ended") {

            waitingTitle.textContent =
                "Class Ended";

            waitingText.textContent =
                "Your teacher has ended this lesson.";

            if (joinButton) {

                joinButton.style.display =
                    "none";

            }

            return;

        }


        if (!joined) {

            waitingTitle.textContent =
                "Waiting for Teacher";

            waitingText.textContent =
                "Your teacher has not started this class yet.";

        }

    } catch(error) {

        console.error(
            error
        );

    }

}


/*
=========================================================
JOIN
=========================================================
*/

if (joinButton) {

    joinButton.addEventListener(
        "click",
        async function() {

            try {

                localStream =
                    await navigator
                        .mediaDevices
                        .getUserMedia({

                            video: true,

                            audio: true

                        });


                studentVideo.srcObject =
                    localStream;


                createPeerConnection();


                joined = true;


                joinButton.style.display =
                    "none";

                leaveButton.style.display =
                    "inline-block";


                waitingTitle.textContent =
                    "Connecting...";

                waitingText.textContent =
                    "Connecting to your teacher.";


                waiting.style.display =
                    "flex";


                await sendSignal(
                    "ready",
                    {
                        ready: true
                    }
                );


                pollSignals();

            } catch(error) {

                console.error(
                    error
                );


                alert(
                    "Please allow camera and microphone access to join the live classroom."
                );

            }

        }
    );

}


/*
=========================================================
PEER CONNECTION
=========================================================
*/

function createPeerConnection()
{

    if (peerConnection) {
        return;
    }


    peerConnection =
        new RTCPeerConnection(
            configuration
        );


    localStream
        .getTracks()
        .forEach(
            function(track) {

                peerConnection.addTrack(
                    track,
                    localStream
                );

            }
        );


    peerConnection.ontrack =
        function(event) {

            if (
                event.streams
                &&
                event.streams[0]
            ) {

                teacherVideo.srcObject =
                    event.streams[0];

                waiting.style.display =
                    "none";

            }

        };


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


    peerConnection.onconnectionstatechange =
        function() {

            if (!peerConnection) {
                return;
            }


            if (
                peerConnection
                    .connectionState
                ===
                "connected"
            ) {

                waiting.style.display =
                    "none";

            }

        };

}


/*
=========================================================
SEND SIGNAL
=========================================================
*/

async function sendSignal(
    type,
    data
)
{

    return post({

        classroom_action:
            "send_signal",

        signal_type:
            type,

        signal_data:
            JSON.stringify(
                data
            )

    });

}


/*
=========================================================
POLL SIGNALS
=========================================================
*/

async function pollSignals()
{

    if (!joined) {
        return;
    }


    try {

        const result =
            await post({

                classroom_action:
                    "get_signals",

                last_id:
                    lastSignalId

            });


        if (
            result.success
            &&
            result.signals
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

    } catch(error) {

        console.error(
            "Signal error:",
            error
        );

    }

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
        "offer"
    ) {

        if (!peerConnection) {
            createPeerConnection();
        }


        const offer =
            JSON.parse(
                signal.signal_data
            );


        await peerConnection
            .setRemoteDescription(
                new RTCSessionDescription(
                    offer
                )
            );


        await processCandidates();


        const answer =
            await peerConnection
                .createAnswer();


        await peerConnection
            .setLocalDescription(
                answer
            );


        await sendSignal(
            "answer",
            answer
        );


        waitingTitle.textContent =
            "Connecting...";

        waitingText.textContent =
            "Connecting to your teacher.";


        return;

    }


    if (
        signal.signal_type
        ===
        "ice-candidate"
    ) {

        const candidate =
            new RTCIceCandidate(
                JSON.parse(
                    signal.signal_data
                )
            );


        if (
            peerConnection
            &&
            peerConnection.remoteDescription
        ) {

            await peerConnection
                .addIceCandidate(
                    candidate
                );

        } else {

            pendingCandidates.push(
                candidate
            );

        }

    }


    if (
        signal.signal_type
        ===
        "hangup"
    ) {

        waiting.style.display =
            "flex";

        waitingTitle.textContent =
            "Teacher Left";

        waitingText.textContent =
            "Your teacher has left the classroom.";

    }

}


/*
=========================================================
PENDING ICE
=========================================================
*/

async function processCandidates()
{

    while (
        pendingCandidates.length
        > 0
    ) {

        const candidate =
            pendingCandidates.shift();


        try {

            await peerConnection
                .addIceCandidate(
                    candidate
                );

        } catch(error) {

            console.error(
                error
            );

        }

    }

}


/*
=========================================================
LEAVE
=========================================================
*/

if (leaveButton) {

    leaveButton.addEventListener(
        "click",
        async function() {

            await sendSignal(
                "hangup",
                {
                    reason:
                        "student_left"
                }
            );


            joined = false;


            if (localStream) {

                localStream
                    .getTracks()
                    .forEach(
                        function(track) {
                            track.stop();
                        }
                    );

                localStream = null;

            }


            if (peerConnection) {

                peerConnection.close();

                peerConnection =
                    null;

            }


            teacherVideo.srcObject =
                null;


            studentVideo.srcObject =
                null;


            leaveButton.style.display =
                "none";


            if (joinButton) {

                joinButton.style.display =
                    "inline-block";

            }


            waiting.style.display =
                "flex";


            waitingTitle.textContent =
                "You Left the Class";

            waitingText.textContent =
                "Click Join Live Class to reconnect.";

        }
    );

}


/*
=========================================================
MIC
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


        micButton.classList.toggle(
            "off",
            enabled
        );


        micButton.textContent =
            enabled
                ? "🔇"
                : "🎤";

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


        cameraButton.classList.toggle(
            "off",
            enabled
        );


        cameraButton.textContent =
            enabled
                ? "🚫"
                : "📷";

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


        const result =
            await post({

                classroom_action:
                    "send_message",

                message:
                    message

            });


        if (result.success) {

            chatInput.value =
                "";

            loadMessages();

        }

    }
);


/*
=========================================================
MESSAGES
=========================================================
*/

async function loadMessages()
{

    try {

        const result =
            await post({

                classroom_action:
                    "get_messages",

                last_message_id:
                    lastMessageId

            });


        if (
            result.success
            &&
            result.messages
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


                    const div =
                        document.createElement(
                            "div"
                        );


                    div.className =
                        "message";


                    if (
                        message.sender_role
                        ===
                        "student"
                    ) {

                        div.classList.add(
                            "mine"
                        );

                    }


                    const name =
                        document.createElement(
                            "div"
                        );

                    name.className =
                        "name";

                    name.textContent =
                        message.sender_name;


                    const bubble =
                        document.createElement(
                            "div"
                        );

                    bubble.className =
                        "bubble";

                    bubble.textContent =
                        message.message;


                    div.appendChild(
                        name
                    );

                    div.appendChild(
                        bubble
                    );


                    messages.appendChild(
                        div
                    );


                    messages.scrollTop =
                        messages.scrollHeight;

                }
            );

        }

    } catch(error) {

        console.error(
            error
        );

    }

}


/*
=========================================================
POLLING
=========================================================
*/

setInterval(
    function() {

        checkStatus();

    },
    3000
);


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


checkStatus();

loadMessages();

</script>

</body>

</html>
