<?php

session_start();

require "../teacher_auth.php";
require "../config/db.php";

/*
==================================================
NISEL ONLINE EDUCATION
TEACHER LIVE CLASSROOM
CONNECTED DIRECTLY TO BOOKINGS
==================================================
*/

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

$teacher_id   = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

$message = "";
$error   = "";


/*
==================================================
HELPER
==================================================
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
==================================================
GET BOOKING ID
==================================================
*/

$booking_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


/*
==================================================
IF NO BOOKING ID
SHOW TEACHER'S ASSIGNED CLASSES
==================================================
*/

if ($booking_id <= 0) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            booking_reference,
            student_name,
            email,
            phone,
            curriculum,
            class_year,
            subjects,
            lesson_date,
            lesson_time,
            lesson_status,
            payment_status,
            live_room_code,
            live_status
        FROM bookings
        WHERE teacher_id = ?
        AND payment_status IN ('Paid','paid','Success','success')
        AND lesson_status <> 'Cancelled'
        ORDER BY
            CASE
                WHEN lesson_date = CURDATE()
                THEN 0
                WHEN lesson_date > CURDATE()
                THEN 1
                ELSE 2
            END,
            lesson_date ASC,
            lesson_time ASC
    ");

    $stmt->execute([
        $teacher_id
    ]);

    $classes = $stmt->fetchAll(
        PDO::FETCH_ASSOC
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
            Live Classroom |
            NISEL ONLINE EDUCATION
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #eef3f8;
                color: #333;
            }

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
            }

            .menu a:hover,
            .menu a.active {
                background: #0055a5;
            }

            .main {
                margin-left: 240px;
                padding: 30px;
            }

            .topbar {
                background: white;
                padding: 20px;
                border-radius: 12px;
                margin-bottom: 25px;

                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .topbar h2 {
                margin: 0;
                color: #003366;
            }

            .page-header {
                background: white;
                padding: 25px;
                border-radius: 12px;
                margin-bottom: 25px;
                box-shadow: 0 4px 15px rgba(0,0,0,.06);
            }

            .page-header h1 {
                margin: 0 0 8px;
                color: #003366;
            }

            .page-header p {
                margin: 0;
                color: #666;
            }

            .classes {
                display: grid;
                grid-template-columns:
                    repeat(auto-fit, minmax(300px, 1fr));

                gap: 20px;
            }

            .class-card {
                background: white;
                border-radius: 14px;
                padding: 22px;

                box-shadow:
                    0 5px 20px rgba(0,0,0,.07);

                border-left: 5px solid #003366;
            }

            .class-card.today {
                border-left-color: #00a86b;
            }

            .subject {
                font-size: 20px;
                font-weight: bold;
                color: #003366;
                margin-bottom: 12px;
            }

            .student {
                font-size: 17px;
                font-weight: bold;
                margin-bottom: 10px;
            }

            .info {
                color: #666;
                line-height: 1.8;
                font-size: 14px;
            }

            .status {
                display: inline-block;
                padding: 6px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: bold;
                margin-top: 10px;
            }

            .waiting {
                background: #fff3cd;
                color: #856404;
            }

            .live {
                background: #d4edda;
                color: #155724;
            }

            .ended {
                background: #e2e3e5;
                color: #383d41;
            }

            .btn {
                display: inline-block;
                margin-top: 18px;
                padding: 12px 18px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                color: white;
                background: #003366;
            }

            .btn:hover {
                background: #0055a5;
            }

            .empty {
                background: white;
                padding: 50px;
                text-align: center;
                border-radius: 12px;
                color: #777;
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
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

            }

        </style>

    </head>

    <body>


    <div class="sidebar">

        <div class="logo">

            NISEL<br>
            ONLINE EDUCATION

        </div>

        <div class="menu">

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="students.php">
                👨‍🎓 My Students
            </a>

            <a href="schedule.php">
                📅 My Schedule
            </a>

            <a
                href="classroom.php"
                class="active"
            >
                🎥 Live Classroom
            </a>

            <a href="profile.php">
                👤 My Profile
            </a>

            <a href="logout.php">
                🚪 Logout
            </a>

        </div>

    </div>


    <div class="main">

        <div class="topbar">

            <h2>
                🎥 Live Classroom
            </h2>

            <div>

                Welcome,

                <strong>
                    <?= h($teacher_name) ?>
                </strong>

            </div>

        </div>


        <div class="page-header">

            <h1>
                Your Assigned Classes
            </h1>

            <p>
                Select a scheduled lesson to enter
                the NISEL live classroom.
            </p>

        </div>


        <?php if (!$classes): ?>

            <div class="empty">

                <h2>
                    📚 No Classes Available
                </h2>

                <p>
                    You currently have no paid lessons
                    assigned to you.
                </p>

            </div>

        <?php else: ?>

            <div class="classes">

                <?php foreach ($classes as $class): ?>

                    <?php

                    $isToday =
                        !empty($class['lesson_date'])
                        &&
                        $class['lesson_date']
                        === date('Y-m-d');

                    $liveStatus =
                        strtolower(
                            $class['live_status']
                            ?? 'waiting'
                        );

                    ?>

                    <div
                        class="class-card
                        <?= $isToday ? 'today' : '' ?>"
                    >

                        <div class="subject">

                            📚

                            <?= h(
                                $class['subjects']
                            ) ?>

                        </div>


                        <div class="student">

                            👨‍🎓

                            <?= h(
                                $class['student_name']
                            ) ?>

                        </div>


                        <div class="info">

                            <div>
                                <strong>
                                    Curriculum:
                                </strong>

                                <?= h(
                                    $class['curriculum']
                                ) ?>
                            </div>


                            <div>
                                <strong>
                                    Class:
                                </strong>

                                <?= h(
                                    $class['class_year']
                                ) ?>
                            </div>


                            <div>
                                <strong>
                                    Date:
                                </strong>

                                <?= h(
                                    $class['lesson_date']
                                    ?: 'Not scheduled'
                                ) ?>
                            </div>


                            <div>
                                <strong>
                                    Time:
                                </strong>

                                <?= h(
                                    $class['lesson_time']
                                    ?: 'Not set'
                                ) ?>
                            </div>


                            <div>

                                <span
                                    class="status
                                    <?= h($liveStatus) ?>"
                                >

                                    <?= strtoupper(
                                        h($liveStatus)
                                    ) ?>

                                </span>

                            </div>

                        </div>


                        <a
                            href="classroom.php?id=<?= (int)$class['id'] ?>"
                            class="btn"
                        >

                            🎥 Enter Classroom

                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    </body>

    </html>

    <?php

    exit;
}


/*
==================================================
GET BOOKING
==================================================

IMPORTANT SECURITY:
The booking MUST belong to the
currently logged-in teacher.
==================================================
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        booking_reference,
        student_name,
        email,
        phone,
        curriculum,
        class_year,
        subjects,
        lesson_date,
        lesson_time,
        lesson_status,
        payment_status,
        teacher_id,
        teacher_name,
        live_room_code,
        live_status,
        live_started_at,
        live_ended_at
    FROM bookings
    WHERE id = ?
    AND teacher_id = ?
    LIMIT 1
");

$stmt->execute([
    $booking_id,
    $teacher_id
]);

$booking = $stmt->fetch(
    PDO::FETCH_ASSOC
);


/*
==================================================
INVALID / UNAUTHORISED BOOKING
==================================================
*/

if (!$booking) {

    http_response_code(403);

    die("

        <div style='
            font-family:Arial;
            text-align:center;
            padding:70px;
        '>

            <h2 style='color:#003366'>
                Classroom Not Found
            </h2>

            <p>
                This classroom does not exist
                or is not assigned to you.
            </p>

            <a
                href='classroom.php'
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
                Back to My Classes
            </a>

        </div>

    ");

}


/*
==================================================
PAYMENT CHECK
==================================================
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
        ['paid', 'success']
    )
) {

    die("

        <div style='
            font-family:Arial;
            text-align:center;
            padding:70px;
        '>

            <h2 style='color:#003366'>
                Payment Required
            </h2>

            <p>
                This lesson has not been marked
                as paid.
            </p>

            <a
                href='classroom.php'
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
                Back to Classes
            </a>

        </div>

    ");

}


/*
==================================================
GENERATE LIVE ROOM CODE
==================================================

The room belongs to the booking.
==================================================
*/

if (
    empty($booking['live_room_code'])
) {

    $roomCode =
        'NISEL-' .
        strtoupper(
            bin2hex(
                random_bytes(8)
            )
        );

    $updateRoom = $pdo->prepare("
        UPDATE bookings
        SET live_room_code = ?
        WHERE id = ?
        AND teacher_id = ?
    ");

    $updateRoom->execute([
        $roomCode,
        $booking_id,
        $teacher_id
    ]);

    $booking['live_room_code'] =
        $roomCode;
}


/*
==================================================
START CLASS
==================================================
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['start_class'])
) {

    $update = $pdo->prepare("
        UPDATE bookings

        SET
            live_status = 'live',
            live_started_at = NOW()

        WHERE id = ?
        AND teacher_id = ?
    ");

    $update->execute([
        $booking_id,
        $teacher_id
    ]);

    $booking['live_status'] =
        'live';

    $booking['live_started_at'] =
        date('Y-m-d H:i:s');
}


/*
==================================================
END CLASS
==================================================
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['end_class'])
) {

    $update = $pdo->prepare("
        UPDATE bookings

        SET
            live_status = 'ended',
            live_ended_at = NOW()

        WHERE id = ?
        AND teacher_id = ?
    ");

    $update->execute([
        $booking_id,
        $teacher_id
    ]);

    $booking['live_status'] =
        'ended';

    $booking['live_ended_at'] =
        date('Y-m-d H:i:s');
}


/*
==================================================
ROOM INFORMATION
==================================================
*/

$roomCode =
    $booking['live_room_code'];

$roomTitle =
    $booking['subjects']
    . " - "
    . $booking['student_name'];

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
    <?= h($roomTitle) ?>
    |
    NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #101820;
    color: white;
    font-family: Arial, sans-serif;
}

.header {
    height: 70px;
    background: #003366;

    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 0 25px;
}

.logo {
    font-weight: bold;
    font-size: 18px;
}

.room-info {
    text-align: right;
}

.room-info strong {
    display: block;
}

.live {
    color: #5cff9d;
    font-size: 12px;
    font-weight: bold;
}

.classroom {
    padding: 20px;
}

.video-grid {
    display: grid;
    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 20px;
}

.video-box {
    background: #000;
    border-radius: 12px;
    overflow: hidden;
    min-height: 420px;

    position: relative;
}

.video-box video {
    width: 100%;
    height: 100%;
    min-height: 420px;

    object-fit: cover;

    background: #000;
}

.video-label {
    position: absolute;
    bottom: 15px;
    left: 15px;

    background: rgba(0,0,0,.7);

    padding: 8px 12px;
    border-radius: 7px;
}

.controls {
    background: #17212b;

    padding: 18px;

    margin-top: 20px;

    border-radius: 12px;

    display: flex;
    justify-content: center;
    gap: 12px;

    flex-wrap: wrap;
}

button {
    border: none;
    border-radius: 8px;

    padding: 12px 18px;

    font-weight: bold;

    cursor: pointer;
}

.start {
    background: #00a86b;
    color: white;
}

.end {
    background: #d62828;
    color: white;
}

.normal {
    background: #31566f;
    color: white;
}

button:hover {
    opacity: .85;
}

.status {
    text-align: center;
    padding: 15px;
    color: #bbb;
}

@media(max-width:800px) {

    .video-grid {
        grid-template-columns: 1fr;
    }

    .video-box,
    .video-box video {
        min-height: 280px;
    }

    .header {
        height: auto;
        padding: 15px;
        gap: 10px;
    }

}

</style>

</head>

<body>


<div class="header">

    <div class="logo">

        NISEL ONLINE EDUCATION

    </div>


    <div class="room-info">

        <strong>

            <?= h(
                $booking['subjects']
            ) ?>

        </strong>

        <span>

            Student:
            <?= h(
                $booking['student_name']
            ) ?>

        </span>

    </div>

</div>


<div class="classroom">


    <div class="video-grid">


        <!-- TEACHER -->

        <div class="video-box">

            <video
                id="localVideo"
                autoplay
                muted
                playsinline
            ></video>

            <div class="video-label">

                🎥 You
                (<?= h($teacher_name) ?>)

            </div>

        </div>


        <!-- STUDENT -->

        <div class="video-box">

            <video
                id="remoteVideo"
                autoplay
                playsinline
            ></video>

            <div class="video-label">

                👨‍🎓
                <?= h(
                    $booking['student_name']
                ) ?>

            </div>

        </div>

    </div>


    <div class="status" id="status">

        Classroom ready.

    </div>


    <div class="controls">


        <?php if (
            strtolower(
                $booking['live_status']
            ) !== 'live'
        ): ?>

            <form method="POST">

                <button
                    class="start"
                    name="start_class"
                    type="submit"
                >

                    🔴 Start Live Class

                </button>

            </form>

        <?php else: ?>

            <button
                class="normal"
                onclick="toggleMicrophone()"
            >

                🎤 Microphone

            </button>


            <button
                class="normal"
                onclick="toggleCamera()"
            >

                📹 Camera

            </button>


            <button
                class="normal"
                onclick="shareScreen()"
            >

                🖥 Share Screen

            </button>


            <form method="POST">

                <button
                    class="end"
                    name="end_class"
                    type="submit"
                    onclick="
                        return confirm(
                            'End this live class?'
                        );
                    "
                >

                    🔴 End Class

                </button>

            </form>

        <?php endif; ?>

    </div>

</div>


<script>

/*
==================================================
NISEL LIVE CLASSROOM
==================================================
*/

const roomCode =
    <?= json_encode($roomCode) ?>;

const bookingId =
    <?= (int)$booking_id ?>;

let localStream = null;

let screenStream = null;


/*
==================================================
GET CAMERA + MICROPHONE
==================================================
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


        document
            .getElementById(
                "localVideo"
            )
            .srcObject =
            localStream;


        document
            .getElementById(
                "status"
            )
            .innerText =
            "Camera and microphone ready.";

    }

    catch(error) {

        console.error(error);

        document
            .getElementById(
                "status"
            )
            .innerText =
            "Camera/microphone permission was denied.";

    }

}


/*
==================================================
MICROPHONE
==================================================
*/

function toggleMicrophone()
{

    if (!localStream) {
        return;
    }

    const tracks =
        localStream.getAudioTracks();

    if (!tracks.length) {
        return;
    }

    tracks[0].enabled =
        !tracks[0].enabled;

}


/*
==================================================
CAMERA
==================================================
*/

function toggleCamera()
{

    if (!localStream) {
        return;
    }

    const tracks =
        localStream.getVideoTracks();

    if (!tracks.length) {
        return;
    }

    tracks[0].enabled =
        !tracks[0].enabled;

}


/*
==================================================
SCREEN SHARING
==================================================
*/

async function shareScreen()
{

    try {

        screenStream =
            await navigator.mediaDevices
                .getDisplayMedia({

                    video: true

                });


        document
            .getElementById(
                "localVideo"
            )
            .srcObject =
            screenStream;


        screenStream
            .getVideoTracks()[0]
            .addEventListener(
                "ended",
                () => {

                    if (localStream) {

                        document
                            .getElementById(
                                "localVideo"
                            )
                            .srcObject =
                            localStream;

                    }

                }
            );

    }

    catch(error) {

        console.log(
            "Screen sharing cancelled."
        );

    }

}


/*
==================================================
START
==================================================
*/

<?php if (
    strtolower(
        $booking['live_status']
    ) === 'live'
): ?>

startCamera();

<?php endif; ?>

</script>


</body>

</html>
