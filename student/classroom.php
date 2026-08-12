<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| STUDENT AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");

    exit;
}


$studentId =
    (string)$_SESSION['student_id'];


$studentName =
    $_SESSION['student_name']
    ?? 'Student';


/*
|--------------------------------------------------------------------------
| CLASSROOM
|--------------------------------------------------------------------------
*/

$classId =
    (int)(
        $_GET['id']
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
    LIMIT 1
");


$stmt->execute([
    $classId
]);


$class =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$class) {

    die(
        "This classroom could not be found."
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
    Virtual Classroom | NISEL
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


.live {

    display: flex;

    align-items: center;

    gap: 8px;

    background: #142d40;

    padding: 9px 13px;

    border-radius: 20px;

    font-size: 11px;
}


.dot {

    width: 9px;

    height: 9px;

    border-radius: 50%;

    background: #ff3b3b;

    animation:
        pulse 1.2s infinite;
}


@keyframes pulse {

    50% {
        opacity: .3;
    }

}


.main {

    height:
        calc(
            100vh - 70px
        );

    display: grid;

    grid-template-columns:
        1fr 330px;
}


.videos {

    padding: 18px;

    display: grid;

    grid-template-columns:
        1fr 280px;

    gap: 15px;
}


.video-box {

    position: relative;

    background: #0d2638;

    border-radius: 15px;

    overflow: hidden;

    min-height: 250px;
}


.video-box video {

    width: 100%;

    height: 100%;

    object-fit: cover;

    background: #06131f;
}


.label {

    position: absolute;

    left: 12px;

    bottom: 12px;

    padding: 7px 10px;

    border-radius: 7px;

    background:
        rgba(0,0,0,.65);

    font-size: 11px;
}


.waiting {

    position: absolute;

    inset: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-direction: column;

    background: #0a1d2c;
}


.waiting-icon {

    font-size: 40px;

    margin-bottom: 10px;
}


.waiting h2 {

    margin: 0;

    font-size: 17px;
}


.waiting p {

    color: #7890a2;

    font-size: 11px;
}


.controls {

    position: absolute;

    bottom: 20px;

    left: 50%;

    transform:
        translateX(-50%);

    display: flex;

    gap: 9px;

    background:
        rgba(4,15,25,.9);

    padding: 10px;

    border-radius: 14px;

    z-index: 20;
}


.control {

    width: 45px;

    height: 45px;

    border: 0;

    border-radius: 11px;

    background: #1b3447;

    color: white;

    cursor: pointer;

    font-size: 17px;
}


.control.active {

    background: #07558f;
}


.control.leave {

    background: #d92d20;

    width: 65px;
}


/* CHAT */

.chat {

    background: #0c2235;

    border-left:
        1px solid
        rgba(255,255,255,.07);

    display: flex;

    flex-direction: column;
}


.chat-header {

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

    color: #7f9aab;

    font-size: 10px;

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

    display: inline-block;

    background: #142f43;

    padding: 9px 11px;

    border-radius: 9px;

    font-size: 11px;

    max-width: 90%;
}


.message.mine {

    text-align: right;
}


.message.mine .message-text {

    background: #07558f;
}


.message.mine .message-name {

    color: #7de2a8;
}


.chat-input {

    padding: 12px;

    display: flex;

    gap: 7px;

    border-top:
        1px solid
        rgba(255,255,255,.08);
}


.chat-input input {

    flex: 1;

    min-width: 0;

    background: #142f43;

    color: white;

    border: 0;

    padding: 11px;

    border-radius: 8px;

    outline: none;

    font-size: 11px;
}


.chat-input button {

    width: 42px;

    border: 0;

    border-radius: 8px;

    background: #07558f;

    color: white;

    cursor: pointer;
}


@media(max-width:1000px) {

    .main {

        grid-template-columns: 1fr;

    }


    .chat {

        display: none;

    }

}


@media(max-width:700px) {

    .videos {

        grid-template-columns: 1fr;

    }


    .local {

        position: absolute;

        width: 150px;

        height: 110px;

        right: 15px;

        top: 15px;

        z-index: 5;

    }

}

</style>

</head>


<body>


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


    <div class="live">

        <span class="dot"></span>

        LIVE

        <span id="timer">
            00:00:00
        </span>

    </div>


</header>


<main class="main">


    <section class="videos">


        <!-- TEACHER -->

        <div class="video-box">


            <video
                id="remoteVideo"
                autoplay
                playsinline
            ></video>


            <div
                id="waiting"
                class="waiting"
            >

                <div class="waiting-icon">
                    👨‍🏫
                </div>


                <h2>
                    Waiting for teacher
                </h2>


                <p>
                    Your teacher's video will appear here.
                </p>

            </div>


            <div class="label">

                👨‍🏫 Teacher

            </div>


        </div>


        <!-- STUDENT -->

        <div
            class="video-box local"
        >


            <video
                id="localVideo"
                autoplay
                muted
                playsinline
            ></video>


            <div class="label">

                👨‍🎓

                <?= h($studentName) ?>

            </div>


        </div>


        <!-- CONTROLS -->

        <div class="controls">


            <button
                id="micBtn"
                class="control active"
            >
                🎤
            </button>


            <button
                id="cameraBtn"
                class="control active"
            >
                📹
            </button>


            <button
                id="screenBtn"
                class="control"
            >
                🖥
            </button>


            <button
                id="leaveBtn"
                class="control leave"
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


            <button>
                ➤
            </button>

        </form>


    </aside>


</main>


<script>

const ROOM_ID =
    <?= (int)$classId ?>;


const USER_ID =
    <?= json_encode(
        (string)$studentId
    ) ?>;


const USER_ROLE =
    "student";


const USER_NAME =
    <?= json_encode(
        $studentName
    ) ?>;


const SIGNAL_API =
    "../api/classroom_signal.php";


const CHAT_API =
    "../api/classroom_chat.php";


let peerConnection = null;

let localStream = null;

let screenStream = null;

let lastSignalId = 0;

let lastMessageId = 0;

let startTime = Date.now();


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


const leaveBtn =
    document.getElementById(
        "leaveBtn"
    );


const messages =
    document.getElementById(
        "messages"
    );


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
| START
|--------------------------------------------------------------------------
*/

async function start()
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


    } catch (error) {

        console.error(
            error
        );


        alert(
            "Please allow camera and microphone access to join the classroom."
        );
    }
}


/*
|--------------------------------------------------------------------------
| PEER CONNECTION
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


    peerConnection.ontrack =
        event => {

            remoteVideo.srcObject =
                event.streams[0];


            waiting.style.display =
                "none";
        };


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
}


/*
|--------------------------------------------------------------------------
| SIGNAL
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
        JSON.stringify(data)
    );


    await fetch(
        SIGNAL_API,
        {
            method: "POST",
            body
        }
    );
}


/*
|--------------------------------------------------------------------------
| POLL
|--------------------------------------------------------------------------
*/

async function pollSignals()
{

    try {

        const response =
            await fetch(
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
                lastSignalId
            );


        const data =
            await response.json();


        if (!data.success) {

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
    | OFFER FROM TEACHER
    |--------------------------------------------------------------------------
    */

    if (
        type === "offer"
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


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | ICE
    |--------------------------------------------------------------------------
    */

    if (
        type === "candidate"
    ) {

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

        const track =
            localStream
                ?.getAudioTracks()[0];


        if (!track) {

            return;
        }


        track.enabled =
            !track.enabled;


        micBtn.textContent =
            track.enabled
                ? "🎤"
                : "🔇";


        micBtn.classList.toggle(
            "active",
            track.enabled
        );
    };


/*
|--------------------------------------------------------------------------
| CAMERA
|--------------------------------------------------------------------------
*/

cameraBtn.onclick =
    () => {

        const track =
            localStream
                ?.getVideoTracks()[0];


        if (!track) {

            return;
        }


        track.enabled =
            !track.enabled;


        cameraBtn.textContent =
            track.enabled
                ? "📹"
                : "🚫";


        cameraBtn.classList.toggle(
            "active",
            track.enabled
        );
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
                .padStart(2,"0")
            +
            ":"
            +
            String(minutes)
                .padStart(2,"0")
            +
            ":"
            +
            String(secs)
                .padStart(2,"0");

    },
    1000
);


/*
|--------------------------------------------------------------------------
| CHAT
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        "chatForm"
    )
    .addEventListener(
        "submit",
        async e => {

            e.preventDefault();


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


        if (!data.success) {

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


function addMessage(msg)
{

    const div =
        document.createElement(
            "div"
        );


    const mine =
        String(
            msg.sender_id
        )
        ===
        String(USER_ID);


    div.className =
        "message"
        +
        (
            mine
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


function escapeHtml(value)
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
| LEAVE
|--------------------------------------------------------------------------
*/

leaveBtn.onclick =
    async () => {

        if (
            confirm(
                "Leave the classroom?"
            )
        ) {

            await sendSignal(
                "leave",
                {}
            );


            localStream
                ?.getTracks()
                .forEach(
                    track =>
                        track.stop()
                );


            peerConnection
                ?.close();


            window.location.href =
                "dashboard.php";
        }
    };


/*
|--------------------------------------------------------------------------
| START
|--------------------------------------------------------------------------
*/

start();


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
