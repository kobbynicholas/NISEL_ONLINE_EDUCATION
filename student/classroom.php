<?php

/* =========================================================
   NISEL ONLINE EDUCATION
   STUDENT LIVE VIRTUAL CLASSROOM
   ========================================================= */

session_start();

require_once __DIR__ . '/../config/db.php';


/* =========================================================
   STUDENT LOGIN CHECK
   ========================================================= */

if (
    empty($_SESSION['student_logged_in']) ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");
    exit;
}


$student_id =
    (int) $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name'] ?? 'Student';


/* =========================================================
   GET CLASSROOM / BOOKING ID
   ========================================================= */

$booking_id =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;


/* =========================================================
   INVALID CLASSROOM ID
   ========================================================= */

if ($booking_id <= 0) {

    http_response_code(400);

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
            Invalid Classroom | NISEL
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;
                background:
                    linear-gradient(
                        135deg,
                        #edf5fb,
                        #f8fbff
                    );
            }

            .box {
                width: 90%;
                max-width: 500px;
                background: #fff;
                padding: 45px 35px;
                border-radius: 22px;
                text-align: center;
                box-shadow:
                    0 20px 50px
                    rgba(0, 47, 84, .12);
            }

            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: #eaf4ff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 38px;
            }

            h2 {
                margin: 0 0 12px;
                color: #003b70;
            }

            p {
                color: #667085;
                line-height: 1.7;
            }

            .button {
                display: inline-block;
                margin-top: 20px;
                padding: 13px 24px;
                background: #003b70;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: bold;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <div class="icon">
                🎥
            </div>

            <h2>
                Invalid Classroom
            </h2>

            <p>
                No valid lesson was selected.
                Please return to your schedule and
                select one of your lessons.
            </p>

            <a
                href="schedule.php"
                class="button"
            >
                ← Return to Schedule
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/* =========================================================
   LOAD BOOKING
   ========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $booking_id
    ]);

    $booking =
        $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    http_response_code(500);

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
            Classroom Error | NISEL
        </title>

        <style>

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
                background: #eef3f8;
            }

            .box {
                width: 90%;
                max-width: 550px;
                padding: 40px;
                background: white;
                border-radius: 20px;
                text-align: center;
                box-shadow:
                    0 15px 40px
                    rgba(0,0,0,.10);
            }

            h2 {
                color: #b42318;
            }

            p {
                color: #667085;
                line-height: 1.6;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <h2>
                Unable to Load Classroom
            </h2>

            <p>
                The classroom could not be loaded.
                Please contact the administrator if the
                problem continues.
            </p>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/* =========================================================
   BOOKING NOT FOUND
   ========================================================= */

if (!$booking) {

    http_response_code(404);

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
            Classroom Not Found | NISEL
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
                background: #eef3f8;
            }

            .box {
                width: 90%;
                max-width: 500px;
                background: white;
                padding: 45px 35px;
                border-radius: 20px;
                text-align: center;
                box-shadow:
                    0 20px 50px
                    rgba(0,0,0,.12);
            }

            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: #eef4ff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 36px;
            }

            h2 {
                color: #003b70;
                margin-bottom: 10px;
            }

            p {
                color: #667085;
                line-height: 1.6;
            }

            .button {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 22px;
                background: #003b70;
                color: white;
                text-decoration: none;
                border-radius: 9px;
                font-weight: bold;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <div class="icon">
                🔍
            </div>

            <h2>
                Classroom Not Found
            </h2>

            <p>
                The selected lesson could not be found.
            </p>

            <a
                href="schedule.php"
                class="button"
            >
                ← Return to Schedule
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/* =========================================================
   VERIFY STUDENT
   ========================================================= */

$booking_student_id = 0;

if (isset($booking['student_id'])) {

    $booking_student_id =
        (int) $booking['student_id'];

}


/*
 * If the booking contains a student ID,
 * make sure it belongs to this student.
 */

if (
    $booking_student_id > 0 &&
    $booking_student_id !== $student_id
) {

    http_response_code(403);

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
            Access Denied | NISEL
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                font-family: Arial, sans-serif;
                background: #eef3f8;
            }

            .box {
                width: 90%;
                max-width: 500px;
                background: white;
                padding: 45px 35px;
                border-radius: 20px;
                text-align: center;
                box-shadow:
                    0 20px 50px
                    rgba(0,0,0,.12);
            }

            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: #fff1f0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 36px;
            }

            h2 {
                color: #b42318;
                margin-bottom: 10px;
            }

            p {
                color: #667085;
                line-height: 1.6;
            }

            .button {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 22px;
                background: #003b70;
                color: white;
                text-decoration: none;
                border-radius: 9px;
                font-weight: bold;
            }

        </style>

    </head>

    <body>

        <div class="box">

            <div class="icon">
                🔒
            </div>

            <h2>
                Classroom Access Denied
            </h2>

            <p>
                This lesson does not belong to your
                student account.
            </p>

            <a
                href="schedule.php"
                class="button"
            >
                ← Return to Schedule
            </a>

        </div>

    </body>

    </html>

    <?php

    exit;
}


/* =========================================================
   BOOKING INFORMATION
   ========================================================= */

$subject =
    $booking['subject']
    ?? $booking['subject_name']
    ?? 'Online Lesson';


$curriculum =
    $booking['curriculum']
    ?? $booking['curricula']
    ?? '';


$teacher_id =
    isset($booking['teacher_id'])
        ? (int) $booking['teacher_id']
        : 0;


/* =========================================================
   PAYMENT STATUS
   ========================================================= */

$payment_status =
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    );


$is_paid = false;


if (
    in_array(
        $payment_status,
        [
            'paid',
            'successful',
            'completed',
            'confirmed',
            'success'
        ],
        true
    )
) {

    $is_paid = true;

}


/*
 * Support installations where the booking
 * status is used to indicate confirmation.
 */

if (!$is_paid) {

    $booking_status =
        strtolower(
            trim(
                $booking['booking_status']
                ?? $booking['status']
                ?? ''
            )
        );


    if (
        in_array(
            $booking_status,
            [
                'paid',
                'confirmed',
                'approved'
            ],
            true
        )
    ) {

        $is_paid = true;

    }

}


/* =========================================================
   TEACHER INFORMATION
   ========================================================= */

$teacher_name =
    'Assigned Teacher';

$teacher_email =
    '';


if ($teacher_id > 0) {

    try {

        $teacher_stmt = $pdo->prepare("
            SELECT *
            FROM teachers
            WHERE id = ?
            LIMIT 1
        ");

        $teacher_stmt->execute([
            $teacher_id
        ]);

        $teacher =
            $teacher_stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($teacher) {

            $teacher_name =
                $teacher['teacher_name']
                ?? $teacher['full_name']
                ?? $teacher['name']
                ?? 'Assigned Teacher';


            $teacher_email =
                $teacher['email']
                ?? '';

        }

    } catch (PDOException $e) {

        $teacher_name =
            'Assigned Teacher';

    }

}


/* =========================================================
   CLASS DATE / TIME
   ========================================================= */

$class_date =
    $booking['class_date']
    ?? $booking['lesson_date']
    ?? $booking['date']
    ?? '';


$class_time =
    $booking['class_time']
    ?? $booking['lesson_time']
    ?? $booking['scheduled_time']
    ?? $booking['time']
    ?? '';


/* =========================================================
   FORMAT DATE
   ========================================================= */

$display_date = '';


if ($class_date !== '') {

    $timestamp =
        strtotime($class_date);


    if ($timestamp !== false) {

        $display_date =
            date(
                'l, d F Y',
                $timestamp
            );

    } else {

        $display_date =
            $class_date;

    }

}


/* =========================================================
   FORMAT TIME
   ========================================================= */

$display_time =
    $class_time !== ''
        ? $class_time
        : 'Scheduled lesson';


/* =========================================================
   CLASSROOM KEY
   ========================================================= */

$classroom_key =
    'niseL-room-' . $booking_id;


/* =========================================================
   INITIALS
   ========================================================= */

$student_initial =
    strtoupper(
        substr(
            trim($student_name),
            0,
            1
        )
    );


if ($student_initial === '') {

    $student_initial = 'S';

}


$teacher_initial =
    strtoupper(
        substr(
            trim($teacher_name),
            0,
            1
        )
    );


if ($teacher_initial === '') {

    $teacher_initial = 'T';

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

<?php
echo htmlspecialchars($subject);
?>

|
NISEL Virtual Classroom

</title>


<style>

* {
    box-sizing: border-box;
}


:root {

    --navy: #003b70;
    --blue: #0877c9;
    --dark: #071827;
    --light-blue: #eaf4ff;
    --background: #eef3f8;
    --white: #ffffff;
    --text: #172b4d;
    --muted: #667085;
    --border: #e4e7ec;
    --success: #12b76a;
    --danger: #d92d20;

}


body {

    margin: 0;

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background:
        var(--background);

    color:
        var(--text);

}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {

    height: 72px;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0068a8
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        0 25px;

    position: sticky;

    top: 0;

    z-index: 100;

    box-shadow:
        0 4px 20px
        rgba(0,0,0,.12);

}


.brand {

    display: flex;

    align-items: center;

    gap: 12px;

}


.brand-logo {

    width: 43px;

    height: 43px;

    border-radius: 12px;

    background:
        rgba(255,255,255,.15);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 21px;

}


.brand-text strong {

    display: block;

    font-size: 16px;

}


.brand-text span {

    display: block;

    font-size: 10px;

    opacity: .75;

    letter-spacing: 1.5px;

}


.user-area {

    display: flex;

    align-items: center;

    gap: 12px;

}


.user-name {

    font-size: 14px;

    font-weight: 600;

}


.user-avatar {

    width: 40px;

    height: 40px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.20);

    border:
        2px solid
        rgba(255,255,255,.5);

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

}


/* =========================================================
   PAGE
========================================================= */

.page {

    width: 96%;

    max-width: 1550px;

    margin:
        25px auto;

}


/* =========================================================
   CLASS HEADER
========================================================= */

.class-header {

    background:
        white;

    border-radius:
        18px;

    padding:
        20px 24px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        20px;

    margin-bottom:
        20px;

    box-shadow:
        0 8px 30px
        rgba(0,47,84,.06);

    border:
        1px solid
        var(--border);

}


.class-info {

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

}


.subject-icon {

    width: 55px;

    height: 55px;

    border-radius: 15px;

    background:
        var(--light-blue);

    color:
        var(--navy);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size: 25px;

}


.class-info h1 {

    margin:
        0 0 5px;

    font-size:
        23px;

    color:
        var(--navy);

}


.class-meta {

    color:
        var(--muted);

    font-size:
        13px;

}


.class-meta span {

    margin-right:
        14px;

}


.live-status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        7px;

    padding:
        8px 13px;

    background:
        #ecfdf3;

    color:
        #067647;

    border-radius:
        20px;

    font-size:
        12px;

    font-weight:
        700;

}


.live-dot {

    width:
        8px;

    height:
        8px;

    border-radius:
        50%;

    background:
        var(--success);

}


/* =========================================================
   NOTICE
========================================================= */

.notice {

    padding:
        13px 16px;

    border-radius:
        12px;

    margin-bottom:
        18px;

    background:
        #fff8e6;

    color:
        #7a4f00;

    border:
        1px solid
        #f5d98b;

    font-size:
        13px;

}


/* =========================================================
   CLASSROOM GRID
========================================================= */

.classroom-grid {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1fr)
        330px;

    gap:
        20px;

}


/* =========================================================
   VIDEO CARD
========================================================= */

.video-card {

    background:
        var(--dark);

    border-radius:
        20px;

    min-height:
        620px;

    overflow:
        hidden;

    position:
        relative;

    box-shadow:
        0 12px 40px
        rgba(0,0,0,.18);

}


.video-stage {

    min-height:
        620px;

    position:
        relative;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        radial-gradient(
            circle at center,
            #123d5a,
            #071827 65%
        );

}


/* =========================================================
   REMOTE VIDEO
========================================================= */

#remoteVideo {

    width:
        100%;

    height:
        100%;

    min-height:
        620px;

    object-fit:
        cover;

    display:
        none;

    background:
        #000;

}


/* =========================================================
   LOCAL VIDEO
========================================================= */

.local-video {

    position:
        absolute;

    right:
        20px;

    bottom:
        90px;

    width:
        220px;

    height:
        135px;

    border-radius:
        14px;

    object-fit:
        cover;

    background:
        #111;

    border:
        2px solid
        rgba(255,255,255,.7);

    display:
        none;

    z-index:
        10;

}


/* =========================================================
   PLACEHOLDER
========================================================= */

.video-placeholder {

    text-align:
        center;

    color:
        white;

    padding:
        30px;

    position:
        relative;

    z-index:
        5;

}


.video-placeholder-icon {

    width:
        90px;

    height:
        90px;

    border-radius:
        50%;

    margin:
        0 auto 18px;

    background:
        rgba(255,255,255,.08);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        38px;

}


.video-placeholder h2 {

    margin:
        0 0 10px;

    font-size:
        21px;

}


.video-placeholder p {

    color:
        rgba(255,255,255,.65);

    max-width:
        450px;

    line-height:
        1.6;

    margin:
        0 auto;

    font-size:
        14px;

}


/* =========================================================
   JOIN BUTTON
========================================================= */

.start-btn {

    border:
        none;

    padding:
        13px 22px;

    border-radius:
        10px;

    background:
        var(--blue);

    color:
        white;

    font-weight:
        700;

    cursor:
        pointer;

    font-size:
        14px;

    margin-top:
        20px;

}


.start-btn:hover {

    background:
        #0565aa;

}


/* =========================================================
   VIDEO CONTROLS
========================================================= */

.video-controls {

    position:
        absolute;

    left:
        0;

    right:
        0;

    bottom:
        0;

    padding:
        18px 20px;

    background:
        linear-gradient(
            transparent,
            rgba(0,0,0,.9)
        );

    display:
        flex;

    justify-content:
        center;

    gap:
        10px;

    z-index:
        30;

}


.control-btn {

    width:
        46px;

    height:
        46px;

    border:
        none;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.12);

    color:
        white;

    cursor:
        pointer;

    font-size:
        18px;

    transition:
        .2s;

}


.control-btn:hover {

    background:
        rgba(255,255,255,.25);

    transform:
        translateY(-2px);

}


.control-btn.leave {

    background:
        var(--danger);

}


/* =========================================================
   SIDE PANEL
========================================================= */

.side-panel {

    display:
        flex;

    flex-direction:
        column;

    gap:
        18px;

}


.panel {

    background:
        white;

    border-radius:
        18px;

    border:
        1px solid
        var(--border);

    box-shadow:
        0 8px 30px
        rgba(0,47,84,.06);

    overflow:
        hidden;

}


.panel-header {

    padding:
        17px 18px;

    border-bottom:
        1px solid
        var(--border);

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

}


.panel-header h3 {

    margin:
        0;

    font-size:
        15px;

    color:
        var(--navy);

}


.panel-body {

    padding:
        18px;

}


/* =========================================================
   TEACHER
========================================================= */

.teacher-card {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.teacher-avatar {

    width:
        50px;

    height:
        50px;

    border-radius:
        50%;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0b82c6
        );

    color:
        white;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        19px;

    font-weight:
        700;

}


.teacher-card strong {

    display:
        block;

    font-size:
        14px;

}


.teacher-card span {

    display:
        block;

    color:
        var(--muted);

    font-size:
        12px;

    margin-top:
        4px;

}


/* =========================================================
   LESSON DETAILS
========================================================= */

.detail {

    display:
        flex;

    gap:
        12px;

    margin-bottom:
        16px;

}


.detail:last-child {

    margin-bottom:
        0;

}


.detail-icon {

    width:
        35px;

    height:
        35px;

    min-width:
        35px;

    border-radius:
        9px;

    background:
        #f1f7fc;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

}


.detail strong {

    display:
        block;

    font-size:
        12px;

    color:
        var(--muted);

    margin-bottom:
        3px;

}


.detail span {

    font-size:
        13px;

    color:
        var(--text);

}


/* =========================================================
   PAYMENT
========================================================= */

.payment {

    padding:
        13px;

    border-radius:
        10px;

    font-size:
        12px;

    font-weight:
        700;

}


.payment.paid {

    background:
        #ecfdf3;

    color:
        #067647;

}


.payment.unpaid {

    background:
        #fff4ed;

    color:
        #b54708;

}


/* =========================================================
   CHAT
========================================================= */

.chat-panel {

    min-height:
        250px;

}


.chat-messages {

    height:
        190px;

    overflow-y:
        auto;

    padding:
        15px;

}


.chat-empty {

    height:
        100%;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        var(--muted);

    font-size:
        12px;

    text-align:
        center;

}


.chat-form {

    display:
        flex;

    border-top:
        1px solid
        var(--border);

}


.chat-form input {

    flex:
        1;

    border:
        none;

    outline:
        none;

    padding:
        13px;

    font-size:
        12px;

}


.chat-form button {

    width:
        48px;

    border:
        none;

    background:
        var(--navy);

    color:
        white;

    cursor:
        pointer;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:
        center;

    color:
        #98a2b3;

    font-size:
        11px;

    padding:
        25px 0;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .classroom-grid {

        grid-template-columns:
            1fr;

    }

    .side-panel {

        display:
            grid;

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 700px) {

    .topbar {

        padding:
            0 14px;

    }

    .user-name {

        display:
            none;

    }

    .page {

        width:
            94%;

    }

    .class-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }

    .class-info h1 {

        font-size:
            19px;

    }

    .side-panel {

        display:
            flex;

    }

    .video-card,
    .video-stage,
    #remoteVideo {

        min-height:
            420px;

    }

    .local-video {

        width:
            140px;

        height:
            90px;

        right:
            12px;

        bottom:
            80px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     TOP BAR
===================================================== -->

<header class="topbar">

    <div class="brand">

        <div class="brand-logo">
            🎓
        </div>

        <div class="brand-text">

            <strong>
                NISEL ONLINE EDUCATION
            </strong>

            <span>
                VIRTUAL CLASSROOM
            </span>

        </div>

    </div>


    <div class="user-area">

        <div class="user-name">

            <?php
            echo htmlspecialchars(
                $student_name
            );
            ?>

        </div>

        <div class="user-avatar">

            <?php
            echo htmlspecialchars(
                $student_initial
            );
            ?>

        </div>

    </div>

</header>



<!-- =====================================================
     PAGE
===================================================== -->

<main class="page">


    <!-- =================================================
         CLASS HEADER
    ================================================== -->

    <section class="class-header">

        <div class="class-info">

            <div class="subject-icon">
                🎓
            </div>

            <div>

                <h1>

                    <?php
                    echo htmlspecialchars(
                        $subject
                    );
                    ?>

                </h1>

                <div class="class-meta">


                    <?php if ($curriculum !== ''): ?>

                        <span>

                            📚

                            <?php
                            echo htmlspecialchars(
                                $curriculum
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <?php if ($display_date !== ''): ?>

                        <span>

                            📅

                            <?php
                            echo htmlspecialchars(
                                $display_date
                            );
                            ?>

                        </span>

                    <?php endif; ?>


                    <span>

                        🆔

                        Class #

                        <?php
                        echo $booking_id;
                        ?>

                    </span>

                </div>

            </div>

        </div>


        <div
            class="live-status"
            id="liveStatus"
        >

            <span class="live-dot"></span>

            Classroom Ready

        </div>

    </section>



    <!-- =================================================
         PAYMENT NOTICE
    ================================================== -->

    <?php if (!$is_paid): ?>

        <div class="notice">

            ⚠️

            Your payment has not yet been confirmed.

            You may view the classroom, but the live
            lesson will only become available after
            payment confirmation.

        </div>

    <?php endif; ?>



    <!-- =================================================
         CLASSROOM GRID
    ================================================== -->

    <div class="classroom-grid">


        <!-- =============================================
             VIDEO AREA
        ============================================== -->

        <section class="video-card">

            <div class="video-stage">


                <!-- REMOTE TEACHER VIDEO -->

                <video
                    id="remoteVideo"
                    autoplay
                    playsinline
                ></video>


                <!-- LOCAL STUDENT VIDEO -->

                <video
                    id="localVideo"
                    class="local-video"
                    autoplay
                    muted
                    playsinline
                ></video>


                <!-- VIDEO PLACEHOLDER -->

                <div
                    class="video-placeholder"
                    id="videoPlaceholder"
                >

                    <div
                        class="video-placeholder-icon"
                    >
                        🎥
                    </div>


                    <h2>
                        Live Virtual Classroom
                    </h2>


                    <p>
                        Join the classroom when your
                        lesson is ready. Your teacher's
                        live video will appear here.
                    </p>


                    <?php if ($is_paid): ?>

                        <button
                            type="button"
                            class="start-btn"
                            id="joinClassBtn"
                        >
                            🎥 Join Live Classroom
                        </button>

                    <?php else: ?>

                        <button
                            type="button"
                            class="start-btn"
                            disabled
                            style="
                                opacity:.5;
                                cursor:not-allowed;
                            "
                        >
                            🔒 Payment Required
                        </button>

                    <?php endif; ?>

                </div>


            </div>


            <!-- VIDEO CONTROLS -->

            <div
                class="video-controls"
                id="videoControls"
                style="display:none;"
            >

                <button
                    type="button"
                    class="control-btn"
                    id="micBtn"
                    title="Microphone"
                >
                    🎤
                </button>


                <button
                    type="button"
                    class="control-btn"
                    id="cameraBtn"
                    title="Camera"
                >
                    📷
                </button>


                <button
                    type="button"
                    class="control-btn"
                    id="screenBtn"
                    title="Share Screen"
                >
                    🖥️
                </button>


                <button
                    type="button"
                    class="control-btn leave"
                    id="leaveBtn"
                    title="Leave Classroom"
                >
                    📞
                </button>

            </div>

        </section>



        <!-- =============================================
             RIGHT PANEL
        ============================================== -->

        <aside class="side-panel">


            <!-- TEACHER -->

            <section class="panel">

                <div class="panel-header">

                    <h3>
                        Your Teacher
                    </h3>

                </div>


                <div class="panel-body">

                    <div class="teacher-card">

                        <div class="teacher-avatar">

                            <?php
                            echo htmlspecialchars(
                                $teacher_initial
                            );
                            ?>

                        </div>


                        <div>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $teacher_name
                                );
                                ?>

                            </strong>

                            <span>
                                Teacher
                            </span>

                        </div>

                    </div>

                </div>

            </section>



            <!-- LESSON INFORMATION -->

            <section class="panel">

                <div class="panel-header">

                    <h3>
                        Lesson Information
                    </h3>

                </div>


                <div class="panel-body">


                    <div class="detail">

                        <div class="detail-icon">
                            📚
                        </div>

                        <div>

                            <strong>
                                SUBJECT
                            </strong>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $subject
                                );
                                ?>

                            </span>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-icon">
                            📅
                        </div>

                        <div>

                            <strong>
                                DATE
                            </strong>

                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $display_date !== ''
                                        ? $display_date
                                        : 'Not specified'
                                );

                                ?>

                            </span>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-icon">
                            ⏰
                        </div>

                        <div>

                            <strong>
                                TIME
                            </strong>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $display_time
                                );
                                ?>

                            </span>

                        </div>

                    </div>


                    <div class="detail">

                        <div class="detail-icon">
                            🆔
                        </div>

                        <div>

                            <strong>
                                CLASSROOM ID
                            </strong>

                            <span>

                                <?php
                                echo $booking_id;
                                ?>

                            </span>

                        </div>

                    </div>


                </div>

            </section>



            <!-- PAYMENT -->

            <section class="panel">

                <div class="panel-header">

                    <h3>
                        Payment
                    </h3>

                </div>


                <div class="panel-body">

                    <?php if ($is_paid): ?>

                        <div class="payment paid">

                            ✓ Payment confirmed

                        </div>

                    <?php else: ?>

                        <div class="payment unpaid">

                            ⚠ Payment confirmation pending

                        </div>

                    <?php endif; ?>

                </div>

            </section>



            <!-- CHAT -->

            <section class="panel chat-panel">

                <div class="panel-header">

                    <h3>
                        Classroom Chat
                    </h3>

                    <span>
                        💬
                    </span>

                </div>


                <div
                    class="chat-messages"
                    id="chatMessages"
                >

                    <div class="chat-empty">

                        Chat will become available
                        when the live classroom
                        connection is established.

                    </div>

                </div>


                <form
                    class="chat-form"
                    id="chatForm"
                >

                    <input
                        type="text"
                        id="chatInput"
                        placeholder="Type a message..."
                        autocomplete="off"
                    >


                    <button
                        type="submit"
                        title="Send"
                    >
                        ➤
                    </button>

                </form>

            </section>


        </aside>


    </div>



    <!-- FOOTER -->

    <div class="footer">

        ©
        <?php echo date('Y'); ?>

        NISEL ONLINE EDUCATION.

        All Rights Reserved.

    </div>


</main>



<script>

/* =========================================================
   NISEL STUDENT CLASSROOM JAVASCRIPT
   ========================================================= */


/* =========================================================
   CLASSROOM DATA
========================================================= */

const bookingId =
    <?php
    echo (int) $booking_id;
    ?>;


const classroomKey =
    <?php
    echo json_encode(
        $classroom_key
    );
    ?>;


const isPaid =
    <?php
    echo $is_paid
        ? 'true'
        : 'false';
    ?>;


const studentName =
    <?php
    echo json_encode(
        $student_name
    );
    ?>;


const teacherName =
    <?php
    echo json_encode(
        $teacher_name
    );
    ?>;


/* =========================================================
   WEBRTC VARIABLES
========================================================= */

let localStream =
    null;

let peerConnection =
    null;

let screenStream =
    null;


/* =========================================================
   DOM ELEMENTS
========================================================= */

const joinClassBtn =
    document.getElementById(
        'joinClassBtn'
    );


const remoteVideo =
    document.getElementById(
        'remoteVideo'
    );


const localVideo =
    document.getElementById(
        'localVideo'
    );


const videoPlaceholder =
    document.getElementById(
        'videoPlaceholder'
    );


const videoControls =
    document.getElementById(
        'videoControls'
    );


const micBtn =
    document.getElementById(
        'micBtn'
    );


const cameraBtn =
    document.getElementById(
        'cameraBtn'
    );


const screenBtn =
    document.getElementById(
        'screenBtn'
    );


const leaveBtn =
    document.getElementById(
        'leaveBtn'
    );


const liveStatus =
    document.getElementById(
        'liveStatus'
    );


/* =========================================================
   JOIN CLASSROOM
========================================================= */

if (joinClassBtn) {

    joinClassBtn.addEventListener(
        'click',
        joinClassroom
    );

}


async function joinClassroom() {

    if (!isPaid) {

        alert(
            'Your payment has not yet been confirmed.'
        );

        return;
    }


    /*
     * Create WebRTC connection.
     */

    try {

        peerConnection =
            new RTCPeerConnection({

                iceServers: [

                    {
                        urls:
                            'stun:stun.l.google.com:19302'
                    },

                    {
                        urls:
                            'stun:stun1.l.google.com:19302'
                    }

                ]

            });


        /* =============================================
           RECEIVE TEACHER MEDIA
        ============================================== */

        peerConnection.ontrack =
            function(event) {

                console.log(
                    'Remote media received.'
                );


                if (
                    event.streams &&
                    event.streams[0]
                ) {

                    remoteVideo.srcObject =
                        event.streams[0];


                    remoteVideo.style.display =
                        'block';


                    videoPlaceholder.style.display =
                        'none';


                    if (liveStatus) {

                        liveStatus.innerHTML =
                            '<span class="live-dot"></span> LIVE CLASS';

                    }

                }

            };


        /* =============================================
           ICE CANDIDATE
        ============================================== */

        peerConnection.onicecandidate =
            function(event) {

                if (
                    event.candidate
                ) {

                    /*
                     * The signaling server will use
                     * this candidate in the next
                     * live-classroom phase.
                     */

                    console.log(
                        'ICE candidate generated.'
                    );

                }

            };


        /* =============================================
           TRY CAMERA + MICROPHONE
        ============================================== */

        try {

            if (
                navigator.mediaDevices &&
                navigator.mediaDevices.getUserMedia
            ) {

                localStream =
                    await navigator.mediaDevices
                        .getUserMedia({

                            video: true,

                            audio: true

                        });


                localVideo.srcObject =
                    localStream;


                localVideo.style.display =
                    'block';


                /*
                 * Add local tracks.
                 */

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


                console.log(
                    'Camera and microphone connected.'
                );

            }

        } catch (mediaError) {

            /*
             * Camera/microphone are OPTIONAL.
             *
             * The student can still enter.
             */

            console.warn(
                'Camera/microphone unavailable:',
                mediaError
            );


            localStream =
                null;


            localVideo.style.display =
                'none';


            console.log(
                'Joining without local camera/microphone.'
            );

        }


        /* =============================================
           SHOW CLASSROOM
        ============================================== */

        videoControls.style.display =
            'flex';


        videoPlaceholder.style.display =
            'none';


        /* =============================================
           STATUS MESSAGE
        ============================================== */

        const oldMessage =
            document.getElementById(
                'classroomConnectedMessage'
            );


        if (!oldMessage) {

            const classroomMessage =
                document.createElement(
                    'div'
                );


            classroomMessage.id =
                'classroomConnectedMessage';


            classroomMessage.style.position =
                'absolute';


            classroomMessage.style.top =
                '20px';


            classroomMessage.style.left =
                '20px';


            classroomMessage.style.right =
                '20px';


            classroomMessage.style.padding =
                '12px 16px';


            classroomMessage.style.background =
                'rgba(0,59,112,.92)';


            classroomMessage.style.color =
                'white';


            classroomMessage.style.borderRadius =
                '10px';


            classroomMessage.style.fontSize =
                '13px';


            classroomMessage.style.zIndex =
                '20';


            classroomMessage.textContent =
                '✓ You have joined the classroom. Waiting for your teacher...';


            const videoStage =
                document.querySelector(
                    '.video-stage'
                );


            if (videoStage) {

                videoStage.appendChild(
                    classroomMessage
                );

            }

        }


        /*
         * IMPORTANT:
         *
         * The actual teacher ↔ student WebRTC
         * signaling will be connected through
         * the NISEL signaling backend.
         */

        console.log(
            'NISEL classroom joined:',
            classroomKey
        );


    } catch (error) {

        console.error(
            'Classroom error:',
            error
        );


        alert(
            'Unable to connect to the classroom. ' +
            'Please refresh the page and try again.'
        );

    }

}


/* =========================================================
   MICROPHONE
========================================================= */

if (micBtn) {

    micBtn.addEventListener(
        'click',
        toggleMicrophone
    );

}


async function toggleMicrophone() {

    /*
     * If no local stream exists,
     * request microphone only.
     */

    if (!localStream) {

        try {

            const audioStream =
                await navigator.mediaDevices
                    .getUserMedia({

                        audio: true

                    });


            localStream =
                audioStream;


            const audioTrack =
                audioStream
                    .getAudioTracks()[0];


            if (
                peerConnection &&
                audioTrack
            ) {

                peerConnection.addTrack(
                    audioTrack,
                    localStream
                );

            }


            micBtn.textContent =
                '🎤';


            return;

        } catch (error) {

            alert(
                'Microphone permission was not granted or no microphone was detected.'
            );

            return;

        }

    }


    const audioTracks =
        localStream.getAudioTracks();


    if (
        audioTracks.length === 0
    ) {

        try {

            const audioStream =
                await navigator.mediaDevices
                    .getUserMedia({
                        audio: true
                    });


            const audioTrack =
                audioStream.getAudioTracks()[0];


            localStream.addTrack(
                audioTrack
            );


            if (peerConnection) {

                peerConnection.addTrack(
                    audioTrack,
                    localStream
                );

            }


            micBtn.textContent =
                '🎤';


        } catch (error) {

            alert(
                'No microphone was detected.'
            );

        }


        return;
    }


    const enabled =
        audioTracks[0].enabled;


    audioTracks.forEach(
        function(track) {

            track.enabled =
                !enabled;

        }
    );


    micBtn.textContent =
        enabled
            ? '🔇'
            : '🎤';

}


/* =========================================================
   CAMERA
========================================================= */

if (cameraBtn) {

    cameraBtn.addEventListener(
        'click',
        toggleCamera
    );

}


async function toggleCamera() {

    /*
     * If there is no local stream,
     * request camera only.
     */

    if (!localStream) {

        try {

            const cameraStream =
                await navigator.mediaDevices
                    .getUserMedia({

                        video: true

                    });


            localStream =
                cameraStream;


            const videoTrack =
                cameraStream.getVideoTracks()[0];


            if (
                peerConnection &&
                videoTrack
            ) {

                peerConnection.addTrack(
                    videoTrack,
                    localStream
                );

            }


            localVideo.srcObject =
                localStream;


            localVideo.style.display =
                'block';


            cameraBtn.textContent =
                '📷';


            return;

        } catch (error) {

            alert(
                'Camera permission was not granted or no camera was detected.'
            );

            return;

        }

    }


    const videoTracks =
        localStream.getVideoTracks();


    if (
        videoTracks.length === 0
    ) {

        try {

            const cameraStream =
                await navigator.mediaDevices
                    .getUserMedia({
                        video: true
                    });


            const videoTrack =
                cameraStream.getVideoTracks()[0];


            localStream.addTrack(
                videoTrack
            );


            if (peerConnection) {

                peerConnection.addTrack(
                    videoTrack,
                    localStream
                );

            }


            localVideo.srcObject =
                localStream;


            localVideo.style.display =
                'block';


            cameraBtn.textContent =
                '📷';


        } catch (error) {

            alert(
                'No camera was detected.'
            );

        }


        return;
    }


    const enabled =
        videoTracks[0].enabled;


    videoTracks.forEach(
        function(track) {

            track.enabled =
                !enabled;

        }
    );


    cameraBtn.textContent =
        enabled
            ? '🚫'
            : '📷';

}


/* =========================================================
   SCREEN SHARING
========================================================= */

if (screenBtn) {

    screenBtn.addEventListener(
        'click',
        shareScreen
    );

}


async function shareScreen() {

    if (
        !navigator.mediaDevices ||
        !navigator.mediaDevices.getDisplayMedia
    ) {

        alert(
            'Screen sharing is not supported by this browser.'
        );

        return;
    }


    try {

        screenStream =
            await navigator.mediaDevices
                .getDisplayMedia({

                    video: true

                });


        const screenTrack =
            screenStream.getVideoTracks()[0];


        if (!screenTrack) {
            return;
        }


        localVideo.srcObject =
            screenStream;


        localVideo.style.display =
            'block';


        /*
         * Replace camera track if WebRTC sender exists.
         */

        if (peerConnection) {

            const sender =
                peerConnection
                    .getSenders()
                    .find(
                        function(item) {

                            return (
                                item.track &&
                                item.track.kind ===
                                'video'
                            );

                        }
                    );


            if (sender) {

                await sender.replaceTrack(
                    screenTrack
                );

            }

        }


        screenTrack.onended =
            async function() {

                /*
                 * Restore camera after
                 * screen sharing ends.
                 */

                if (
                    localStream &&
                    peerConnection
                ) {

                    const cameraTrack =
                        localStream
                            .getVideoTracks()
                            [0];


                    if (cameraTrack) {

                        const sender =
                            peerConnection
                                .getSenders()
                                .find(
                                    function(item) {

                                        return (
                                            item.track &&
                                            item.track.kind ===
                                            'video'
                                        );

                                    }
                                );


                        if (sender) {

                            await sender.replaceTrack(
                                cameraTrack
                            );

                        }


                        localVideo.srcObject =
                            localStream;

                    } else {

                        localVideo.srcObject =
                            null;

                        localVideo.style.display =
                            'none';

                    }

                }

            };


    } catch (error) {

        console.log(
            'Screen sharing cancelled.'
        );

    }

}


/* =========================================================
   LEAVE CLASSROOM
========================================================= */

if (leaveBtn) {

    leaveBtn.addEventListener(
        'click',
        leaveClassroom
    );

}


function leaveClassroom() {

    /*
     * Stop camera and microphone.
     */

    if (localStream) {

        localStream
            .getTracks()
            .forEach(
                function(track) {

                    track.stop();

                }
            );

    }


    /*
     * Stop screen sharing.
     */

    if (screenStream) {

        screenStream
            .getTracks()
            .forEach(
                function(track) {

                    track.stop();

                }
            );

    }


    /*
     * Close WebRTC.
     */

    if (peerConnection) {

        peerConnection.close();

    }


    localStream =
        null;

    screenStream =
        null;

    peerConnection =
        null;


    localVideo.srcObject =
        null;


    remoteVideo.srcObject =
        null;


    localVideo.style.display =
        'none';


    remoteVideo.style.display =
        'none';


    videoControls.style.display =
        'none';


    videoPlaceholder.style.display =
        'block';


    if (liveStatus) {

        liveStatus.innerHTML =
            '<span class="live-dot"></span> Classroom Ready';

    }


    const connectedMessage =
        document.getElementById(
            'classroomConnectedMessage'
        );


    if (connectedMessage) {

        connectedMessage.remove();

    }

}


/* =========================================================
   CHAT
========================================================= */

const chatForm =
    document.getElementById(
        'chatForm'
    );


const chatInput =
    document.getElementById(
        'chatInput'
    );


const chatMessages =
    document.getElementById(
        'chatMessages'
    );


if (chatForm) {

    chatForm.addEventListener(
        'submit',
        function(event) {

            event.preventDefault();


            const message =
                chatInput.value.trim();


            if (!message) {

                return;

            }


            const emptyMessage =
                chatMessages.querySelector(
                    '.chat-empty'
                );


            if (emptyMessage) {

                emptyMessage.remove();

            }


            const item =
                document.createElement(
                    'div'
                );


            item.style.padding =
                '9px 11px';


            item.style.marginBottom =
                '8px';


            item.style.background =
                '#f1f7fc';


            item.style.borderRadius =
                '9px';


            item.style.fontSize =
                '12px';


            item.innerHTML =
                '<strong>You:</strong> ' +
                escapeHtml(message);


            chatMessages.appendChild(
                item
            );


            chatMessages.scrollTop =
                chatMessages.scrollHeight;


            chatInput.value =
                '';

        }
    );

}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(value) {

    const div =
        document.createElement(
            'div'
        );


    div.textContent =
        value;


    return div.innerHTML;

}


/* =========================================================
   PAGE EXIT
========================================================= */

window.addEventListener(
    'beforeunload',
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


        if (screenStream) {

            screenStream
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

    }
);

</script>


</body>

</html>
