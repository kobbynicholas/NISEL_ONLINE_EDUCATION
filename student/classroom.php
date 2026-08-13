<?php

/* =========================================================
   NISEL ONLINE EDUCATION
   STUDENT LIVE CLASSROOM
   ========================================================= */

session_start();

require_once __DIR__ . '/../config/db.php';


/* =========================================================
   BASIC SESSION CHECK
   ========================================================= */

if (
    empty($_SESSION['student_logged_in']) ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");
    exit;
}


$student_id = (int) $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';


/* =========================================================
   GET BOOKING / LESSON ID
   ========================================================= */

$booking_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/* =========================================================
   INVALID CLASSROOM
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
                    Inter,
                    Arial,
                    sans-serif;
                background:
                    linear-gradient(
                        135deg,
                        #eef5fb,
                        #f7faff
                    );
            }

            .box {
                width: 90%;
                max-width: 500px;
                background: #ffffff;
                padding: 45px 35px;
                border-radius: 22px;
                text-align: center;
                box-shadow:
                    0 20px 50px
                    rgba(0, 50, 100, .12);
            }

            .icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: #eaf3ff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 38px;
            }

            h2 {
                margin: 0 0 12px;
                color: #003b70;
                font-size: 26px;
            }

            p {
                color: #667085;
                line-height: 1.7;
                font-size: 15px;
            }

            .button {
                display: inline-block;
                margin-top: 20px;
                padding: 13px 24px;
                background: #003b70;
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 700;
                transition: .2s;
            }

            .button:hover {
                background: #0877c9;
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
                select a classroom from one of your lessons.
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

    /*
     * First try the normal NISEL booking structure.
     */

    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $booking_id
    ]);

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    http_response_code(500);

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <title>
            Classroom Error | NISEL
        </title>

        <style>

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                background: #eef3f8;
                font-family: Arial, sans-serif;
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
            }

        </style>

    </head>

    <body>

        <div class="box">

            <h2>
                Unable to Load Classroom
            </h2>

            <p>
                There was a problem loading this lesson.
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
                    rgba(0, 0, 0, .12);
            }

            .icon {
                font-size: 55px;
                margin-bottom: 15px;
            }

            h2 {
                margin: 0 0 10px;
                color: #003b70;
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

            .button:hover {
                background: #0877c9;
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
                It may have been removed or the classroom
                link may no longer be valid.
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
   VERIFY STUDENT OWNERSHIP
   ========================================================= */

$booking_student_id = 0;


/*
 * Support the normal student_id column.
 */

if (isset($booking['student_id'])) {

    $booking_student_id =
        (int) $booking['student_id'];

}


/*
 * Make sure the booking belongs to the
 * currently logged-in student.
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
                    rgba(0, 0, 0, .12);
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
                font-size: 38px;
            }

            h2 {
                margin: 0 0 10px;
                color: #b42318;
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
   GET BOOKING INFORMATION
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
            ?? $booking['payment_status_text']
            ?? ''
        )
    );


$is_paid = (
    $payment_status === 'paid' ||
    $payment_status === 'successful' ||
    $payment_status === 'completed' ||
    $payment_status === 'confirmed' ||
    $payment_status === 'success'
);


/*
 * Some existing NISEL installations use booking_status
 * rather than payment_status.
 */

if (!$is_paid) {

    $booking_status =
        strtolower(
            trim(
                $booking['status']
                ?? $booking['booking_status']
                ?? ''
            )
        );

    if (
        $booking_status === 'paid' ||
        $booking_status === 'confirmed' ||
        $booking_status === 'approved'
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

        $teacher = $teacher_stmt->fetch(
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

        /*
         * Teacher information is optional.
         * The classroom can still load.
         */

        $teacher_name =
            'Assigned Teacher';

    }

}


/* =========================================================
   DATE / TIME
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
   DISPLAY DATE
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
   DISPLAY TIME
   ========================================================= */

$display_time =
    $class_time !== ''
        ? $class_time
        : 'Scheduled lesson';


/* =========================================================
   CLASSROOM TITLE
   ========================================================= */

$classroom_title =
    $subject;


/* =========================================================
   LIVE CLASSROOM IDENTIFIER
   ========================================================= */

$classroom_key =
    'niseL-room-' . $booking_id;


/* =========================================================
   PROFILE INITIAL
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


/* =========================================================
   TEACHER INITIAL
   ========================================================= */

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

<?php echo htmlspecialchars($classroom_title); ?>

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

    --light-blue: #eaf4ff;

    --background: #eef3f8;

    --white: #ffffff;

    --text: #172b4d;

    --muted: #667085;

    --border: #e4e7ec;

    --success: #12b76a;

    --danger: #d92d20;

}


/* =========================================================
   BODY
========================================================= */

body {

    margin: 0;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        var(--background);

    color: var(--text);

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

    width: 42px;

    height: 42px;

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

    font-size: 11px;

    opacity: .75;

    letter-spacing: 1.2px;

}


.user-area {

    display: flex;

    align-items: center;

    gap: 12px;

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


.user-name {

    font-size: 14px;

    font-weight: 600;

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

    background: white;

    border-radius: 18px;

    padding: 20px 24px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 8px 30px
        rgba(0, 47, 84, .06);

    border:
        1px solid
        var(--border);

}


.class-info {

    display: flex;

    align-items: center;

    gap: 15px;

}


.subject-icon {

    width: 55px;

    height: 55px;

    border-radius: 15px;

    background:
        var(--light-blue);

    color:
        var(--navy);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

}


.class-info h1 {

    margin: 0 0 5px;

    font-size: 23px;

    color: var(--navy);

}


.class-meta {

    color: var(--muted);

    font-size: 13px;

}


.class-meta span {

    margin-right: 14px;

}


.live-status {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding:
        8px 13px;

    background:
        #ecfdf3;

    color:
        #067647;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 700;

}


.live-dot {

    width: 8px;

    height: 8px;

    border-radius: 50%;

    background:
        var(--success);

}


/* =========================================================
   CLASSROOM GRID
========================================================= */

.classroom-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        330px;

    gap: 20px;

}


/* =========================================================
   VIDEO AREA
========================================================= */

.video-card {

    background:
        #071827;

    border-radius: 20px;

    min-height: 620px;

    overflow: hidden;

    position: relative;

    box-shadow:
        0 12px 40px
        rgba(0,0,0,.18);

}


.video-stage {

    min-height: 620px;

    position: relative;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        radial-gradient(
            circle at center,
            #123d5a,
            #071827 65%
        );

}


.video-placeholder {

    text-align: center;

    color: white;

    padding: 30px;

}


.video-placeholder-icon {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    margin:
        0 auto 18px;

    background:
        rgba(255,255,255,.08);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 38px;

}


.video-placeholder h2 {

    margin:
        0 0 10px;

    font-size: 21px;

}


.video-placeholder p {

    color:
        rgba(255,255,255,.65);

    max-width: 450px;

    line-height: 1.6;

    margin:
        0 auto;

    font-size: 14px;

}


/* =========================================================
   VIDEO ELEMENTS
========================================================= */

#remoteVideo {

    width: 100%;

    height: 100%;

    min-height: 620px;

    object-fit: cover;

    display: none;

    background: #000;

}


.local-video {

    position: absolute;

    right: 20px;

    bottom: 90px;

    width: 220px;

    height: 135px;

    border-radius: 14px;

    object-fit: cover;

    background: #111;

    border:
        2px solid
        rgba(255,255,255,.7);

    display: none;

    z-index: 10;

}


/* =========================================================
   VIDEO CONTROLS
========================================================= */

.video-controls {

    position: absolute;

    left: 0;

    right: 0;

    bottom: 0;

    padding:
        18px 20px;

    background:
        linear-gradient(
            transparent,
            rgba(0,0,0,.85)
        );

    display: flex;

    justify-content: center;

    gap: 10px;

}


.control-btn {

    width: 46px;

    height: 46px;

    border: none;

    border-radius: 50%;

    background:
        rgba(255,255,255,.12);

    color: white;

    cursor: pointer;

    font-size: 18px;

    transition: .2s;

}


.control-btn:hover {

    background:
        rgba(255,255,255,.22);

    transform:
        translateY(-2px);

}


.control-btn.leave {

    background:
        var(--danger);

}


.start-btn {

    border: none;

    padding:
        13px 22px;

    border-radius: 10px;

    background:
        var(--blue);

    color: white;

    font-weight: 700;

    cursor: pointer;

    font-size: 14px;

    margin-top: 20px;

}


.start-btn:hover {

    background:
        #0565aa;

}


/* =========================================================
   SIDE PANEL
========================================================= */

.side-panel {

    display: flex;

    flex-direction: column;

    gap: 18px;

}


.panel {

    background:
        white;

    border-radius: 18px;

    border:
        1px solid
        var(--border);

    box-shadow:
        0 8px 30px
        rgba(0,47,84,.06);

    overflow: hidden;

}


.panel-header {

    padding:
        17px 18px;

    border-bottom:
        1px solid
        var(--border);

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.panel-header h3 {

    margin: 0;

    font-size: 15px;

    color: var(--navy);

}


.panel-body {

    padding: 18px;

}


/* =========================================================
   TEACHER
========================================================= */

.teacher-card {

    display: flex;

    align-items: center;

    gap: 12px;

}


.teacher-avatar {

    width: 50px;

    height: 50px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0b82c6
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    font-weight: 700;

}


.teacher-card strong {

    display: block;

    font-size: 14px;

}


.teacher-card span {

    display: block;

    color: var(--muted);

    font-size: 12px;

    margin-top: 4px;

}


/* =========================================================
   LESSON DETAILS
========================================================= */

.detail {

    display: flex;

    gap: 12px;

    margin-bottom: 16px;

}


.detail:last-child {

    margin-bottom: 0;

}


.detail-icon {

    width: 35px;

    height: 35px;

    min-width: 35px;

    border-radius: 9px;

    background:
        #f1f7fc;

    display: flex;

    align-items: center;

    justify-content: center;

}


.detail strong {

    display: block;

    font-size: 12px;

    color: var(--muted);

    margin-bottom: 3px;

}


.detail span {

    font-size: 13px;

    color: var(--text);

}


/* =========================================================
   PAYMENT
========================================================= */

.payment {

    padding: 13px;

    border-radius: 10px;

    font-size: 12px;

    font-weight: 700;

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

    flex: 1;

    min-height: 250px;

}


.chat-messages {

    height: 190px;

    overflow-y: auto;

    padding: 15px;

}


.chat-empty {

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--muted);

    font-size: 12px;

    text-align: center;

}


.chat-form {

    display: flex;

    border-top:
        1px solid
        var(--border);

}


.chat-form input {

    flex: 1;

    border: none;

    outline: none;

    padding:
        13px;

    font-size: 12px;

}


.chat-form button {

    width: 48px;

    border: none;

    background:
        var(--navy);

    color: white;

    cursor: pointer;

}


/* =========================================================
   ALERT
========================================================= */

.notice {

    padding:
        13px 16px;

    border-radius: 12px;

    margin-bottom: 18px;

    background:
        #fff8e6;

    color:
        #7a4f00;

    border:
        1px solid
        #f5d98b;

    font-size: 13px;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align: center;

    color:
        #98a2b3;

    font-size: 11px;

    padding:
        25px 0;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1050px) {

    .classroom-grid {

        grid-template-columns: 1fr;

    }

    .side-panel {

        display: grid;

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

        display: none;

    }

    .page {

        width: 94%;

    }

    .class-header {

        align-items: flex-start;

        flex-direction: column;

    }

    .class-info h1 {

        font-size: 19px;

    }

    .side-panel {

        display: flex;

    }

    .video-card,
    .video-stage,
    #remoteVideo {

        min-height: 420px;

    }

    .local-video {

        width: 140px;

        height: 90px;

        right: 12px;

        bottom: 80px;

    }

}

</style>

</head>


<body>


<!-- ======================================================
     TOP BAR
====================================================== -->

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
            echo htmlspecialchars($student_name);
            ?>

        </div>

        <div class="user-avatar">

            <?php
            echo htmlspecialchars($student_initial);
            ?>

        </div>

    </div>

</header>



<!-- ======================================================
     MAIN PAGE
====================================================== -->

<main class="page">


    <!-- CLASS HEADER -->

    <section class="class-header">

        <div class="class-info">

            <div class="subject-icon">
                🎓
            </div>

            <div>

                <h1>

                    <?php
                    echo htmlspecialchars(
                        $classroom_title
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
                        Class #<?php
                        echo $booking_id;
                        ?>
                    </span>

                </div>

            </div>

        </div>


        <div class="live-status">

            <span class="live-dot"></span>

            Classroom Ready

        </div>

    </section>



    <?php if (!$is_paid): ?>

        <div class="notice">

            ⚠️ Your payment has not yet been confirmed.
            You may view the classroom, but the live lesson
            will only be available after payment is confirmed.

        </div>

    <?php endif; ?>



    <!-- ==================================================
         CLASSROOM
    ================================================== -->

    <div class="classroom-grid">


        <!-- VIDEO -->

        <section class="video-card">

            <div class="video-stage">


                <!-- Remote teacher video -->

                <video
                    id="remoteVideo"
                    autoplay
                    playsinline
                ></video>


                <!-- Local student video -->

                <video
                    id="localVideo"
                    class="local-video"
                    autoplay
                    muted
                    playsinline
                ></video>


                <!-- Placeholder -->

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
                        Your live lesson will appear here
                        when the teacher starts the classroom.
                    </p>


                    <?php if ($is_paid): ?>

                        <button
                            type="button"
                            class="start-btn"
                            id="joinClassBtn"
                        >
                            🎥 Join Live Classroom
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



        <!-- =================================================
             SIDE PANEL
        ================================================= -->

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

                        Classroom chat will become
                        available when the live lesson starts.

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


    <div class="footer">

        ©
        <?php echo date('Y'); ?>
        NISEL ONLINE EDUCATION.
        All Rights Reserved.

    </div>


</main>



<script>

/* =========================================================
   NISEL STUDENT CLASSROOM
   ========================================================= */

const bookingId =
    <?php echo (int) $booking_id; ?>;

const classroomKey =
    <?php
    echo json_encode($classroom_key);
    ?>;

const isPaid =
    <?php
    echo $is_paid ? 'true' : 'false';
    ?>;


let localStream = null;

let peerConnection = null;


/* =========================================================
   ELEMENTS
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

const leaveBtn =
    document.getElementById(
        'leaveBtn'
    );

const screenBtn =
    document.getElementById(
        'screenBtn'
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


    try {

        /*
         * Request camera and microphone.
         */

        localStream =
            await navigator.mediaDevices.getUserMedia({

                video: true,

                audio: true

            });


        localVideo.srcObject =
            localStream;


        localVideo.style.display =
            'block';


        videoPlaceholder.style.display =
            'none';


        videoControls.style.display =
            'flex';


        /*
         * Create WebRTC connection.
         *
         * The signaling server will be connected
         * in the next classroom phase.
         */

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
            function(event) {

                if (
                    event.streams &&
                    event.streams[0]
                ) {

                    remoteVideo.srcObject =
                        event.streams[0];

                    remoteVideo.style.display =
                        'block';

                }

            };


        /*
         * At this stage the student's local camera
         * and microphone are active.
         *
         * Teacher-to-student WebRTC signaling will
         * be connected through the NISEL classroom
         * signaling backend.
         */

        console.log(
            'Joined classroom:',
            classroomKey
        );

        alert(
            'You have joined the classroom. Your camera and microphone are ready.'
        );


    } catch (error) {

        console.error(error);

        alert(
            'Unable to access your camera or microphone. ' +
            'Please allow camera and microphone permissions ' +
            'in your browser and try again.'
        );

    }

}


/* =========================================================
   MICROPHONE
========================================================= */

if (micBtn) {

    micBtn.addEventListener(
        'click',
        function() {

            if (!localStream) {
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


            micBtn.textContent =
                enabled
                    ? '🔇'
                    : '🎤';

        }
    );

}


/* =========================================================
   CAMERA
========================================================= */

if (cameraBtn) {

    cameraBtn.addEventListener(
        'click',
        function() {

            if (!localStream) {
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


            cameraBtn.textContent =
                enabled
                    ? '🚫'
                    : '📷';

        }
    );

}


/* =========================================================
   SCREEN SHARING
========================================================= */

if (screenBtn) {

    screenBtn.addEventListener(
        'click',
        async function() {

            try {

                const screenStream =
                    await navigator.mediaDevices
                        .getDisplayMedia({
                            video: true
                        });


                const screenTrack =
                    screenStream.getVideoTracks()[0];


                if (
                    peerConnection
                ) {

                    const sender =
                        peerConnection
                            .getSenders()
                            .find(
                                s =>
                                    s.track &&
                                    s.track.kind ===
                                    'video'
                            );


                    if (sender) {

                        await sender.replaceTrack(
                            screenTrack
                        );

                    }

                }


                localVideo.srcObject =
                    screenStream;


                screenTrack.onended =
                    function() {

                        if (
                            localStream &&
                            peerConnection
                        ) {

                            const cameraTrack =
                                localStream
                                    .getVideoTracks()[0];


                            const sender =
                                peerConnection
                                    .getSenders()
                                    .find(
                                        s =>
                                            s.track &&
                                            s.track.kind ===
                                            'video'
                                    );


                            if (
                                sender &&
                                cameraTrack
                            ) {

                                sender.replaceTrack(
                                    cameraTrack
                                );

                            }

                            localVideo.srcObject =
                                localStream;

                        }

                    };


            } catch (error) {

                console.log(
                    'Screen sharing cancelled.'
                );

            }

        }
    );

}


/* =========================================================
   LEAVE CLASS
========================================================= */

if (leaveBtn) {

    leaveBtn.addEventListener(
        'click',
        leaveClassroom
    );

}


function leaveClassroom() {

    if (localStream) {

        localStream
            .getTracks()
            .forEach(
                track => track.stop()
            );

    }


    if (peerConnection) {

        peerConnection.close();

        peerConnection =
            null;

    }


    localStream =
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


            /*
             * Temporary local classroom chat.
             * The live database chat will be connected
             * to the NISEL classroom backend.
             */

            const empty =
                chatMessages.querySelector(
                    '.chat-empty'
                );


            if (empty) {
                empty.remove();
            }


            const item =
                document.createElement(
                    'div'
                );


            item.style.padding =
                '8px 10px';


            item.style.marginBottom =
                '7px';


            item.style.background =
                '#f1f7fc';


            item.style.borderRadius =
                '8px';


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
   HTML ESCAPE
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
                    track => track.stop()
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
