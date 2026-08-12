<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| TEACHER AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['teacher_logged_in']) ||
    $_SESSION['teacher_logged_in'] !== true ||
    empty($_SESSION['teacher_id'])
) {

    header("Location: login.php");

    exit;
}


$teacherId =
    (string)$_SESSION['teacher_id'];


$teacherName =
    $_SESSION['teacher_name']
    ?? 'Teacher';


/*
|--------------------------------------------------------------------------
| CLASSROOM ID
|--------------------------------------------------------------------------
*/

$classId =
    (int)(
        $_GET['id']
        ??
        $_POST['id']
        ??
        0
    );


if ($classId <= 0) {

    die("Invalid classroom.");
}


/*
|--------------------------------------------------------------------------
| GET CLASS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        title,
        subject,
        teacher_id
    FROM live_classes
    WHERE id = ?
    AND teacher_id = ?
    LIMIT 1
");


$stmt->execute([
    $classId,
    $teacherId
]);


$class =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$class) {

    die(
        "This classroom does not exist or you are not authorised to access it."
    );
}


function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
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
    <?= h($class['title']) ?>
    | NISEL Virtual Classroom
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background: #071827;

    color: white;

    font-family:
        Inter,
        Arial,
        sans-serif;

    overflow: hidden;
}


/* =====================================================
   HEADER
===================================================== */

.header {

    height: 70px;

    background: #0c2235;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 22px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);
}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;
}


.logo {

    width: 42px;

    height: 42px;

    border-radius: 11px;

    background: #07558f;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;
}


.brand h1 {

    margin: 0;

    font-size: 16px;
}


.brand span {

    display: block;

    color: #8ca5b8;

    font-size: 11px;

    margin-top: 3px;
}


.live-status {

    display: flex;

    align-items: center;

    gap: 8px;

    background: #142d40;

    padding: 9px 13px;

    border-radius: 20px;

    font-size: 11px;
}


.live-dot {

    width: 9px;

    height: 9px;

    background: #ff3b3b;

    border-radius: 50%;

    animation:
        pulse 1.2s infinite;
}


@keyframes pulse {

    50% {
        opacity: .3;
    }

}


/* =====================================================
   LAYOUT
===================================================== */

.classroom {

    height:
        calc(
            100vh - 70px
        );

    display: grid;

    grid-template-columns:
        1fr 330px;
}


/* =====================================================
   VIDEO AREA
===================================================== */

.video-area {

    padding: 18px;

    display: grid;

    grid-template-columns:
        1fr 280px;

    gap: 15px;

    min-width: 0;
}


.video-box {

    position: relative;

    background: #0d2638;

    border-radius: 15px;

    overflow: hidden;

    min-height: 250px;

    border:
        1px solid
        rgba(255,255,255,.08);
}


.video-box video {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

    background: #06131f;
}


.video-label {

    position: absolute;

    bottom: 12px;

    left: 12px;

    background:
        rgba(0,0,0,.65);

    padding:
        7px 10px;

    border-radius: 7px;

    font-size: 11px;

    z-index: 2;
}


.video-placeholder {

    position: absolute;

    inset: 0;

    display: none;

    align-items: center;

    justify-content: center;

    flex-direction: column;

    background: #0a1d2c;

    color: #7890a2;
}


.video-placeholder .person {

    width: 65px;

    height: 65px;

    border-radius: 50%;

    background: #17384f;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    margin-bottom: 10px;
}


/* =====================================================
   REMOTE MAIN
===================================================== */

.remote-video {

    height: 100%;
}


/* =====================================================
   SELF VIDEO
===================================================== */

.local-video {

    height: 100%;
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

    display: flex;

    align-items: center;

    gap: 9px;

    background:
        rgba(
            4,
            15,
            25,
            .90
        );

    padding: 10px;

    border-radius: 14px;

    z-index: 10;
}


.control-btn {

    width: 45px;

    height: 45px;

    border: none;

    border-radius: 11px;

    background: #1b3447;

    color: white;

    cursor: pointer;

    font-size: 17px;
}


.control-btn:hover {

    background: #2b4b61;

}


.control-btn.active {

    background: #07558f;
}


.control-btn.end {

    background: #d92d20;

    width: 65px;
}


.control-btn.end:hover {

    background: #b42318;
}


/* =====================================================
   CHAT
===================================================== */

.chat {

    background: #0c2235;

    border-left:
        1px solid
        rgba(255,255,255,.07);

    display: flex;

    flex-direction: column;

    min-width: 0;
}


.chat-header {

    height: 65px;

    padding: 15px;

    border-bottom:
        1px solid
        rgba(255,255,255,.08);
}


.chat-header strong {

    font-size: 14px;
}


.chat-header span {

    display: block;

    font-size: 10px;

    color: #7f9aab;

    margin-top: 4px;
}


.messages {

    flex: 1;

    overflow-y: auto;

    padding: 15px;
}


.message {

    margin-bottom: 13px;
}


.message-name {

    color: #6ec5ff;

    font-size: 10px;

    font-weight: bold;

    margin-bottom: 4px;
}


.message-text {

    background: #142f43;

    padding: 9px 11px;

    border-radius: 9px;

    font-size: 11px;

    line-height: 1.5;

    display: inline-block;

    max-width: 90%;
}


.message.mine {

    text-align: right;
}


.message.mine .message-name {

    color: #7de2a8;
}


.message.mine .message-text {

    background: #07558f;
}


.chat-input {

    padding: 12px;

    border-top:
        1px solid
        rgba(255,255,255,.08);

    display: flex;

    gap: 7px;
}


.chat-input input {

    flex: 1;

    min-width: 0;

    background: #142f43;

    border: none;

    color: white;

    padding: 11px;

    border-radius: 8px;

    outline: none;

    font-size: 11px;
}


.chat-input button {

    width: 42px;

    border: none;

    border-radius: 8px;

    background: #07558f;

    color: white;

    cursor: pointer;
}


/* =====================================================
   WAITING
===================================================== */

.waiting {

    position: absolute;

    inset: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-direction: column;

    background: #0a1d2c;

    z-index: 3;
}


.waiting-icon {

    font-size: 40px;

    margin-bottom: 12px;
}


.waiting h2 {

    margin: 0;

    font-size: 17px;
}


.waiting p {

    color: #7890a2;

    font-size: 11px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1000px) {

    .classroom {

        grid-template-columns: 1fr;

    }


    .chat {

        display: none;

    }

}


@media(max-width:700px) {

    .video-area {

        grid-template-columns: 1fr;

    }


    .local-video {

        position: absolute;

        width: 150px;

        height: 110px;

        right: 15px;

        top: 15px;

        z-index: 5;

    }


    .controls {

        bottom: 10px;

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


        <div class="logo">
            🎓
        </div>


        <div>

            <h1>
                NISEL VIRTUAL CLASSROOM
            </h1>


            <span>

                <?= h($class['title']) ?>

                ·

                <?= h($class['subject']) ?>

            </span>

        </div>


    </div>


    <div class="live-status">

        <span class="live-dot"></span>

        LIVE

        <span id="timer">
            00:00:00
        </span>

    </div>


</header>


<!-- =====================================================
     CLASSROOM
===================================================== -->

<main class="classroom">


    <section class="video-area">


        <!-- REMOTE STUDENT -->

        <div class="video-box remote-video">


            <video
                id="remoteVideo"
                autoplay
                playsinline
            ></video>


            <div
                id="remotePlaceholder"
                class="video-placeholder"
            >

                <div class="person">
                    👨‍🎓
                </div>


                <strong>
                    Student
                </strong>


                <small>
                    Waiting to join...
                </small>

            </div>


            <div class="video-label">

                👨‍🎓 Student

            </div>


            <div
                id="waiting"
                class="waiting"
            >

                <div class="waiting-icon">
                    ⏳
                </div>


                <h2>
                    Waiting for student
                </h2>


                <p>
                    Share this classroom with your student.
                </p>

            </div>


        </div>


        <!-- TEACHER -->

        <div class="video-box local-video">


            <video
                id="localVideo"
                autoplay
                muted
                playsinline
            ></video>


            <div
                id="localPlaceholder"
                class="video-placeholder"
            >

                <div class="person">
                    👨‍🏫
                </div>


                <strong>
                    <?= h($teacherName) ?>
                </strong>

            </div>


            <div class="video-label">

                👨‍🏫

                <?= h($teacherName) ?>

            </div>


        </div>


        <!-- CONTROLS -->

        <div class="controls">


            <button
                id="micBtn"
                class="control-btn active"
                title="Microphone"
            >
                🎤
            </button>


            <button
                id="cameraBtn"
                class="control-btn active"
                title="Camera"
            >
                📹
            </button>


            <button
                id="screenBtn"
                class="control-btn"
                title="Share screen"
            >
                🖥
            </button>


            <button
                id="endBtn"
                class="control-btn end"
                title="End class"
            >
                ☎
            </button>


        </div>


    </section>


    <!-- CHAT -->

    <aside class="chat">


        <div class="chat-header">

            <strong>
                💬 Classroom Chat
            </strong>


            <span>
                Live conversation
            </span>

        </div>


        <div
            id="messages"
            class="messages"
        ></div>


        <form
            id="chatForm"
            class="chat-input"
        >

            <input
                id="chatMessage"
                type="text"
                placeholder="Type a message..."
                autocomplete="off"
            >


            <button
                type="submit"
            >
                ➤
            </button>

        </form>


    </aside>


</main>


<script>

/*
|--------------------------------------------------------------------------
| NISEL WEBRTC CLASSROOM
|--------------------------------------------------------------------------
*/


const ROOM_ID =
    <?= (int)$classId ?>;


const USER_ID =
    <?= json_encode(
        (string)$teacherId
    ) ?>;


const USER_ROLE =
    "teacher";


const USER_NAME =
    <?= json_encode(
        $teacherName
    ) ?>;


/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const SIGNAL_API =
    "../api/classroom_signal.php";


const CHAT_API =
    "../api/classroom_chat.php";


/*
|--------------------------------------------------------------------------
| WEBRTC
|--------------------------------------------------------------------------
*/

let peerConnection = null;

let localStream = null;

let screenStream = null;

let lastSignalId = 0;

let lastMessageId = 0;

let startTime = Date.now();


/*
|--------------------------------------------------------------------------
| DOM
|--------------------------------------------------------------------------
*/

const localVideo =
    document.getElementById(
        "localVideo"
    );


const remoteVideo =
    document.getElementById(
        "remoteVideo"
    );


const waiting =
    document.getElementById(
        "waiting"
    );


const micBtn =
    document.getElementById(
        "micBtn"
    );


const cameraBtn =
    document.getElementById(
        "cameraBtn"
    );


const screenBtn =
    document.getElementById(
        "screenBtn"
    );


const endBtn =
    document.getElementById(
        "endBtn"
    );


const messages =
    document.getElementById(
        "messages"
    );


/*
|--------------------------------------------------------------------------
| STUN SERVERS
|--------------------------------------------------------------------------
*/

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
|--------------------------------------------------------------------------
| START CAMERA
|--------------------------------------------------------------------------
*/

async function startCamera()
{

    try {

        localStream =
            await navigator.mediaDevices
                .getUserMedia({
                    video: true,
                    audio: true
                });


        localVideo.srcObject =
            localStream;


        createPeerConnection();


        /*
        |--------------------------------------------------------------------------
        | TEACHER SENDS READY
        |--------------------------------------------------------------------------
        */

        await sendSignal(
            "ready",
            {}
        );


        /*
        |--------------------------------------------------------------------------
        | TEACHER CREATES OFFER
        |--------------------------------------------------------------------------
        */

        await createOffer();


    } catch (error) {

        console.error(
            error
        );


        alert(
            "Camera and microphone access is required for the virtual classroom."
        );
    }
}


/*
|--------------------------------------------------------------------------
| CREATE PEER CONNECTION
|--------------------------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | LOCAL TRACKS
    |--------------------------------------------------------------------------
    */

    localStream
        .getTracks()
        .forEach(
            track => {

                peerConnection.addTrack(
                    track,
                    localStream
                );

            }
        );


    /*
    |--------------------------------------------------------------------------
    | REMOTE TRACK
    |--------------------------------------------------------------------------
    */

    peerConnection.ontrack =
        event => {

            remoteVideo.srcObject =
                event.streams[0];


            waiting.style.display =
                "none";
        };


    /*
    |--------------------------------------------------------------------------
    | ICE CANDIDATE
    |--------------------------------------------------------------------------
    */

    peerConnection.onicecandidate =
        async event => {

            if (
                event.candidate
            ) {

                await sendSignal(
                    "candidate",
                    event.candidate
                );
            }
        };


    /*
    |--------------------------------------------------------------------------
    | CONNECTION STATE
    |--------------------------------------------------------------------------
    */

    peerConnection.onconnectionstatechange =
        () => {

            console.log(
                "Connection:",
                peerConnection.connectionState
            );


            if (
                peerConnection.connectionState
                ===
                "connected"
            ) {

                waiting.style.display =
                    "none";
            }


            if (
                peerConnection.connectionState
                ===
                "disconnected"
            ) {

                waiting.style.display =
                    "flex";
            }

        };
}


/*
|--------------------------------------------------------------------------
| CREATE OFFER
|--------------------------------------------------------------------------
*/

async function createOffer()
{

    if (!peerConnection) {

        return;
    }


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
}


/*
|--------------------------------------------------------------------------
| SEND SIGNAL
|--------------------------------------------------------------------------
*/

async function sendSignal(
    type,
    data
)
{

    const body =
        new URLSearchParams();


    body.append(
        "action",
        "send"
    );


    body.append(
        "room_id",
        ROOM_ID
    );


    body.append(
        "signal_type",
        type
    );


    body.append(
        "signal_data",
        JSON.stringify(
            data
        )
    );


    try {

        await fetch(
            SIGNAL_API,
            {
                method:
                    "POST",

                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded"
                },

                body
            }
        );

    } catch (error) {

        console.error(
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| POLL SIGNALS
|--------------------------------------------------------------------------
*/

async function pollSignals()
{

    try {

        const url =
            SIGNAL_API
            +
            "?action=poll"
            +
            "&room_id="
            +
            ROOM_ID
            +
            "&last_id="
            +
            lastSignalId;


        const response =
            await fetch(
                url
            );


        const data =
            await response.json();


        if (
            !data.success
        ) {

            return;
        }


        for (
            const signal
            of data.signals
        ) {

            lastSignalId =
                Math.max(
                    lastSignalId,
                    parseInt(
                        signal.id
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | IGNORE OUR OWN SIGNALS
            |--------------------------------------------------------------------------
            */

            if (
                signal.sender_role
                ===
                USER_ROLE
                &&
                String(
                    signal.sender_id
                )
                ===
                String(USER_ID)
            ) {

                continue;
            }


            await handleSignal(
                signal
            );
        }


    } catch (error) {

        console.error(
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| HANDLE SIGNAL
|--------------------------------------------------------------------------
*/

async function handleSignal(
    signal
)
{

    const type =
        signal.signal_type;


    const data =
        JSON.parse(
            signal.signal_data
        );


    /*
    |--------------------------------------------------------------------------
    | STUDENT ANSWER
    |--------------------------------------------------------------------------
    */

    if (
        type === "answer"
    ) {

        if (!peerConnection) {

            createPeerConnection();
        }


        await peerConnection
            .setRemoteDescription(
                new RTCSessionDescription(
                    data
                )
            );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | ICE CANDIDATE
    |--------------------------------------------------------------------------
    */

    if (
        type === "candidate"
    ) {

        if (!peerConnection) {

            return;
        }


        try {

            await peerConnection
                .addIceCandidate(
                    new RTCIceCandidate(
                        data
                    )
                );

        } catch (error) {

            console.error(
                error
            );
        }
    }

}


/*
|--------------------------------------------------------------------------
| MICROPHONE
|--------------------------------------------------------------------------
*/

micBtn.onclick =
    () => {

        if (!localStream) {

            return;
        }


        const track =
            localStream.getAudioTracks()[0];


        if (!track) {

            return;
        }


        track.enabled =
            !track.enabled;


        micBtn.classList.toggle(
            "active",
            track.enabled
        );


        micBtn.textContent =
            track.enabled
                ? "🎤"
                : "🔇";
    };


/*
|--------------------------------------------------------------------------
| CAMERA
|--------------------------------------------------------------------------
*/

cameraBtn.onclick =
    () => {

        if (!localStream) {

            return;
        }


        const track =
            localStream.getVideoTracks()[0];


        if (!track) {

            return;
        }


        track.enabled =
            !track.enabled;


        cameraBtn.classList.toggle(
            "active",
            track.enabled
        );


        cameraBtn.textContent =
            track.enabled
                ? "📹"
                : "🚫";
    };


/*
|--------------------------------------------------------------------------
| SCREEN SHARE
|--------------------------------------------------------------------------
*/

screenBtn.onclick =
    async () => {

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
                    ?.getSenders()
                    .find(
                        s =>
                            s.track
                            &&
                            s.track.kind
                            ===
                            "video"
                    );


            if (sender) {

                await sender.replaceTrack(
                    screenTrack
                );
            }


            localVideo.srcObject =
                screenStream;


            screenTrack.onended =
                async () => {

                    const cameraTrack =
                        localStream
                            .getVideoTracks()[0];


                    if (sender) {

                        await sender.replaceTrack(
                            cameraTrack
                        );
                    }


                    localVideo.srcObject =
                        localStream;
                };


        } catch (error) {

            console.log(
                "Screen sharing cancelled."
            );
        }
    };


/*
|--------------------------------------------------------------------------
| TIMER
|--------------------------------------------------------------------------
*/

setInterval(
    () => {

        const seconds =
            Math.floor(
                (
                    Date.now()
                    -
                    startTime
                ) / 1000
            );


        const hours =
            Math.floor(
                seconds / 3600
            );


        const minutes =
            Math.floor(
                (
                    seconds % 3600
                ) / 60
            );


        const secs =
            seconds % 60;


        document
            .getElementById(
                "timer"
            )
            .textContent =

            String(hours)
                .padStart(2, "0")
            +
            ":"
            +
            String(minutes)
                .padStart(2, "0")
            +
            ":"
            +
            String(secs)
                .padStart(2, "0");

    },
    1000
);


/*
|--------------------------------------------------------------------------
| CHAT SEND
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        "chatForm"
    )
    .addEventListener(
        "submit",
        async event => {

            event.preventDefault();


            const input =
                document.getElementById(
                    "chatMessage"
                );


            const message =
                input.value.trim();


            if (!message) {

                return;
            }


            const body =
                new URLSearchParams();


            body.append(
                "action",
                "send"
            );


            body.append(
                "room_id",
                ROOM_ID
            );


            body.append(
                "message",
                message
            );


            await fetch(
                CHAT_API,
                {
                    method:
                        "POST",

                    body
                }
            );


            input.value =
                "";
        }
    );


/*
|--------------------------------------------------------------------------
| CHAT POLLING
|--------------------------------------------------------------------------
*/

async function pollChat()
{

    try {

        const response =
            await fetch(
                CHAT_API
                +
                "?action=poll"
                +
                "&room_id="
                +
                ROOM_ID
                +
                "&last_id="
                +
                lastMessageId
            );


        const data =
            await response.json();


        if (
            !data.success
        ) {

            return;
        }


        for (
            const msg
            of data.messages
        ) {

            lastMessageId =
                Math.max(
                    lastMessageId,
                    parseInt(
                        msg.id
                    )
                );


            addMessage(
                msg
            );
        }


    } catch (error) {

        console.error(
            error
        );
    }
}


/*
|--------------------------------------------------------------------------
| ADD CHAT MESSAGE
|--------------------------------------------------------------------------
*/

function addMessage(
    msg
)
{

    const div =
        document.createElement(
            "div"
        );


    div.className =
        "message"
        +
        (
            String(
                msg.sender_id
            )
            ===
            String(USER_ID)
                ? " mine"
                : ""
        );


    div.innerHTML =

        `
        <div class="message-name">
            ${escapeHtml(
                msg.sender_name
            )}
        </div>

        <div class="message-text">
            ${escapeHtml(
                msg.message
            )}
        </div>
        `;


    messages.appendChild(
        div
    );


    messages.scrollTop =
        messages.scrollHeight;
}


/*
|--------------------------------------------------------------------------
| ESCAPE HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(
    value
)
{

    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        value;


    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| END CLASS
|--------------------------------------------------------------------------
*/

endBtn.onclick =
    async () => {

        const confirmEnd =
            confirm(
                "Are you sure you want to leave/end this classroom?"
            );


        if (!confirmEnd) {

            return;
        }


        try {

            await sendSignal(
                "leave",
                {
                    name:
                        USER_NAME
                }
            );

        } catch (error) {

            console.error(
                error
            );
        }


        if (localStream) {

            localStream
                .getTracks()
                .forEach(
                    track =>
                        track.stop()
                );
        }


        if (peerConnection) {

            peerConnection.close();
        }


        window.location.href =
            "live_classes.php";
    };


/*
|--------------------------------------------------------------------------
| START
|--------------------------------------------------------------------------
*/

startCamera();


/*
|--------------------------------------------------------------------------
| POLLING
|--------------------------------------------------------------------------
*/

setInterval(
    pollSignals,
    1000
);


setInterval(
    pollChat,
    1000
);

</script>

</body>

</html>
