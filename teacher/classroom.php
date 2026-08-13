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
TEACHER SESSION
=========================================================
*/

$teacher_id =
    $_SESSION["teacher_id"] ?? 0;


if (!$teacher_id) {

    header("Location: login.php");
    exit;

}


$teacher_name =
    $_SESSION["teacher_name"]
    ?? "Teacher";


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
        <title>Invalid Classroom</title>
        <style>
        body{
            margin:0;
            font-family:Arial;
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
        h2{color:#003b70;}
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
            <h2>Invalid Classroom</h2>
            <p>No valid booking was selected.</p>
            <a href='schedule.php'>Return to Schedule</a>
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
        AND teacher_id = ?
    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $teacher_id
]);

$booking =
    $stmt->fetch();


if (!$booking) {

    http_response_code(403);

    die("
        <!DOCTYPE html>
        <html>
        <head>
        <title>Access Denied</title>
        <style>
        body{
            margin:0;
            font-family:Arial;
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
        h2{color:#003b70;}
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
            <h2>Classroom Access Denied</h2>
            <p>This booking is not assigned to your teacher account.</p>
            <a href='schedule.php'>Return to Schedule</a>
        </div>
        </body>
        </html>
    ");

}


/*
=========================================================
BOOKING DATA
=========================================================
*/

$subject =
    $booking["subjects"]
    ?? "Lesson";

$student_name =
    $booking["student_name"]
    ?? "Student";

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
    $booking["live_status"]
    ?? "waiting";

$room_code =
    trim(
        $booking["live_room_code"]
        ?? ""
    );


/*
=========================================================
CREATE ROOM CODE IF NECESSARY
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
API ACTIONS
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


        if (!$is_paid) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "The student's payment has not been confirmed."
            ]);

            exit;
        }


        $stmt = $pdo->prepare("
            UPDATE bookings
            SET
                live_status = 'live',
                live_started_at = NOW()
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
                live_ended_at = NOW()
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
    GET STATUS
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
    SEND SIGNAL
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


        $allowed =
            [
                "offer",
                "ice-candidate",
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
    GET STUDENT SIGNALS
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
Live Classroom | NISEL
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

.video-area {
    position: relative;
    background: #020617;
    overflow: hidden;
}

#remoteVideo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: #020617;
}

#localVideo {
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

.video-label {
    position: absolute;
    left: 20px;
    bottom: 20px;
    background: rgba(0,0,0,.65);
    padding: 8px 11px;
    border-radius: 7px;
    font-size: 11px;
}

.waiting {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(2,6,23,.93);
}

.waiting-card {
    text-align: center;
    padding: 35px;
}

.waiting-card h2 {
    margin: 0 0 8px;
}

.waiting-card p {
    color: #9aa9ba;
    font-size: 13px;
}

.start-button {
    border: none;
    background: #16a34a;
    color: white;
    padding: 13px 22px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: 800;
}

.end-button {
    display: none;
    border: none;
    background: #dc3545;
    color: white;
    padding: 13px 22px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: 800;
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
    border-left: 1px solid rgba(255,255,255,.08);
}

.info {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.info h2 {
    margin: 0;
    font-size: 18px;
}

.info p {
    color: #718096;
    font-size: 11px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 10px;
    border-bottom: 1px solid rgba(255,255,255,.04);
}

.info-row span:first-child {
    color: #718096;
}

.chat-title {
    padding: 14px 18px;
    font-size: 12px;
    font-weight: 800;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.messages {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
}

.message {
    margin-bottom: 12px;
}

.message-name {
    color: #7f8da1;
    font-size: 9px;
    margin-bottom: 4px;
}

.message-bubble {
    display: inline-block;
    max-width: 90%;
    padding: 8px 10px;
    background: #263348;
    border-radius: 8px;
    font-size: 10px;
}

.message.mine {
    text-align: right;
}

.message.mine .message-bubble {
    background: #075a9e;
}

.chat {
    display: flex;
    padding: 10px;
    gap: 6px;
    border-top: 1px solid rgba(255,255,255,.08);
}

.chat input {
    flex: 1;
    background: #1e293b;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 7px;
    outline: none;
}

.chat button {
    border: none;
    background: #0877c9;
    color: white;
    border-radius: 7px;
    padding: 0 12px;
    cursor: pointer;
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
            TEACHER LIVE CLASSROOM
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


<section class="video-area">

    <video
        id="remoteVideo"
        autoplay
        playsinline
    ></video>


    <video
        id="localVideo"
        autoplay
        muted
        playsinline
    ></video>


    <div class="video-label">

        👨‍🎓
        <?= e($student_name) ?>

    </div>


    <div
        class="waiting"
        id="waiting"
    >

        <div class="waiting-card">

            <div style="font-size:45px;">
                🎥
            </div>

            <h2>
                Ready to Start?
            </h2>

            <p>
                <?= e($subject) ?>
                with
                <?= e($student_name) ?>
            </p>

            <button
                id="startButton"
                class="start-button"
            >
                ▶ Start Live Class
            </button>

            <button
                id="endButton"
                class="end-button"
            >
                ■ End Class
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


        <div class="info-row">
            <span>Student</span>
            <span><?= e($student_name) ?></span>
        </div>


        <div class="info-row">
            <span>Room</span>
            <span><?= e($room_code) ?></span>
        </div>


        <div class="info-row">
            <span>Booking</span>
            <span><?= e(
                $booking["booking_reference"]
                ?? $booking_id
            ) ?></span>
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
            type="text"
            id="chatInput"
            placeholder="Message student..."
            maxlength="2000"
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


const remoteVideo =
    document.getElementById(
        "remoteVideo"
    );

const localVideo =
    document.getElementById(
        "localVideo"
    );

const waiting =
    document.getElementById(
        "waiting"
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

let lastSignalId = 0;

let lastMessageId = 0;

let started = false;

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
POST HELPER
=========================================================
*/

async function post(
    data
)
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
START CLASS
=========================================================
*/

startButton.addEventListener(
    "click",
    async function() {

        try {

            const result =
                await post({

                    classroom_action:
                        "start_class"

                });


            if (!result.success) {

                alert(
                    result.message
                    ||
                    "Unable to start class."
                );

                return;

            }


            await startMedia();


            started = true;

            waiting.style.display =
                "none";

            startButton.style.display =
                "none";

            endButton.style.display =
                "inline-block";

            pollSignals();

        } catch(error) {

            console.error(error);

            alert(
                "Unable to start the classroom."
            );

        }

    }
);


/*
=========================================================
MEDIA
=========================================================
*/

async function startMedia()
{

    localStream =
        await navigator
            .mediaDevices
            .getUserMedia({

                video: true,

                audio: true

            });


    localVideo.srcObject =
        localStream;


    createPeerConnection();

}


/*
=========================================================
PEER
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

                remoteVideo.srcObject =
                    event.streams[0];

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

    if (!started) {

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
            "Signal error",
            error
        );

    }

}


/*
=========================================================
PROCESS STUDENT SIGNAL
=========================================================
*/

async function processSignal(
    signal
)
{

    if (
        signal.signal_type
        ===
        "ready"
    ) {

        await createOffer();

        return;

    }


    if (
        signal.signal_type
        ===
        "answer"
    ) {

        if (!peerConnection) {

            return;

        }


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


        await processCandidates();

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

}


/*
=========================================================
CREATE OFFER
=========================================================
*/

async function createOffer()
{

    if (!peerConnection) {

        return;

    }


    try {

        const offer =
            await peerConnection
                .createOffer();


        await peerConnection
            .setLocalDescription(
                offer
            );


        await sendSignal(
            "offer",
            offer
        );

    } catch(error) {

        console.error(
            "Offer error",
            error
        );

    }

}


/*
=========================================================
PENDING ICE
=========================================================
*/

async function processCandidates()
{

    if (!peerConnection) {

        return;

    }


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
END CLASS
=========================================================
*/

endButton.addEventListener(
    "click",
    async function() {

        if (
            !confirm(
                "End this live class?"
            )
        ) {

            return;

        }


        await post({

            classroom_action:
                "end_class"

        });


        started = false;


        if (localStream) {

            localStream
                .getTracks()
                .forEach(
                    function(track) {
                        track.stop();
                    }
                );

        }


        if (peerConnection) {

            peerConnection.close();

        }


        waiting.style.display =
            "flex";

        startButton.style.display =
            "inline-block";

        endButton.style.display =
            "none";

    }
);


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

        if (!document.fullscreenElement) {

            await document
                .documentElement
                .requestFullscreen();

        } else {

            await document.exitFullscreen();

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
LOAD CHAT
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
                        "teacher"
                    ) {

                        div.classList.add(
                            "mine"
                        );

                    }


                    div.innerHTML = "";

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


                    div.appendChild(name);
                    div.appendChild(bubble);

                    messages.appendChild(div);

                    messages.scrollTop =
                        messages.scrollHeight;

                }
            );

        }

    } catch(error) {

        console.error(error);

    }

}


/*
=========================================================
INTERVALS
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

</script>

</body>

</html>
