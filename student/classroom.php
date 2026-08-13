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

$room_code =
    trim((string)($booking['live_room_code'] ?? ''));

$classroom_key =
    $room_code !== ''
        ? $room_code
        : 'NISEL-' . $booking_id;


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


/* =========================================================
   STUDENT CLASSROOM API
   Handles WebRTC signaling and classroom chat.
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["classroom_action"])) {

    header("Content-Type: application/json; charset=UTF-8");

    function student_json_response(array $data) {
        echo json_encode($data);
        exit;
    }

    try {

        $action =
            trim((string)($_POST["classroom_action"] ?? ""));

        /*
         * Always re-check that the booking belongs to
         * the logged-in student.
         */
        $check = $pdo->prepare("
            SELECT *
            FROM bookings
            WHERE id = ?
            LIMIT 1
        ");

        $check->execute([
            $booking_id
        ]);

        $apiBooking = $check->fetch(PDO::FETCH_ASSOC);

        if (!$apiBooking) {
            student_json_response([
                "success" => false,
                "message" => "Booking not found."
            ]);
        }

        $apiStudentId =
            (int)($apiBooking["student_id"] ?? 0);

        if (
            $apiStudentId > 0 &&
            $apiStudentId !== $student_id
        ) {
            student_json_response([
                "success" => false,
                "message" => "You do not have access to this classroom."
            ]);
        }

        $apiRoom =
            trim((string)(
                $apiBooking["live_room_code"] ?? ""
            ));

        /*
         * -----------------------------------------------------
         * CLASS STATUS
         * -----------------------------------------------------
         */
        if ($action === "get_status") {

            student_json_response([
                "success" => true,
                "status" =>
                    strtolower(
                        trim(
                            (string)(
                                $apiBooking["live_status"]
                                ?? "waiting"
                            )
                        )
                    ),
                "room_code" => $apiRoom
            ]);
        }

        /*
         * -----------------------------------------------------
         * STUDENT SENDS WEBRTC SIGNAL
         * -----------------------------------------------------
         */
        if ($action === "send_signal") {

            $signalType =
                trim((string)(
                    $_POST["signal_type"] ?? ""
                ));

            $signalData =
                (string)(
                    $_POST["signal_data"] ?? ""
                );

            $allowedSignals = [
                "ready",
                "answer",
                "ice-candidate",
                "hangup"
            ];

            if (!in_array(
                $signalType,
                $allowedSignals,
                true
            )) {
                student_json_response([
                    "success" => false,
                    "message" => "Invalid classroom signal."
                ]);
            }

            if (
                $signalData === "" ||
                strlen($signalData) > 1000000
            ) {
                student_json_response([
                    "success" => false,
                    "message" => "Invalid signal data."
                ]);
            }

            if ($apiRoom === "") {
                student_json_response([
                    "success" => false,
                    "message" => "The teacher has not started the classroom yet."
                ]);
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
                (?, ?, 'student', ?, ?)
            ");

            $stmt->execute([
                $booking_id,
                $apiRoom,
                $signalType,
                $signalData
            ]);

            student_json_response([
                "success" => true,
                "id" => (int)$pdo->lastInsertId()
            ]);
        }

        /*
         * -----------------------------------------------------
         * STUDENT RECEIVES TEACHER SIGNALS
         * -----------------------------------------------------
         */
        if ($action === "get_signals") {

            $lastId =
                max(
                    0,
                    (int)(
                        $_POST["last_id"] ?? 0
                    )
                );

            if ($apiRoom === "") {
                student_json_response([
                    "success" => true,
                    "signals" => []
                ]);
            }

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    signal_type,
                    signal_data,
                    created_at
                FROM classroom_signals
                WHERE booking_id = ?
                  AND room_code = ?
                  AND sender_role = 'teacher'
                  AND id > ?
                ORDER BY id ASC
                LIMIT 100
            ");

            $stmt->execute([
                $booking_id,
                $apiRoom,
                $lastId
            ]);

            student_json_response([
                "success" => true,
                "signals" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        /*
         * -----------------------------------------------------
         * STUDENT CHAT MESSAGE
         * -----------------------------------------------------
         */
        if ($action === "send_message") {

            $message =
                trim((string)(
                    $_POST["message"] ?? ""
                ));

            if ($message === "") {
                student_json_response([
                    "success" => false,
                    "message" => "Message cannot be empty."
                ]);
            }

            if (
                function_exists("mb_strlen") &&
                mb_strlen($message) > 2000
            ) {
                student_json_response([
                    "success" => false,
                    "message" => "Message is too long."
                ]);
            }

            if ($apiRoom === "") {
                student_json_response([
                    "success" => false,
                    "message" => "The classroom is not active yet."
                ]);
            }

            $columnsStmt =
                $pdo->query(
                    "SHOW COLUMNS FROM classroom_messages"
                );

            $columns =
                $columnsStmt->fetchAll(
                    PDO::FETCH_COLUMN,
                    0
                );

            $columnMap =
                array_flip($columns);

            $insertColumns = [
                "booking_id",
                "room_code"
            ];

            $insertValues = [
                $booking_id,
                $apiRoom
            ];

            $placeholders = [
                "?",
                "?"
            ];

            if (isset($columnMap["sender_role"])) {

                $insertColumns[] =
                    "sender_role";

                $insertValues[] =
                    "student";

                $placeholders[] =
                    "?";

            } elseif (
                isset($columnMap["sender_type"])
            ) {

                $insertColumns[] =
                    "sender_type";

                $insertValues[] =
                    "student";

                $placeholders[] =
                    "?";
            }

            if (isset($columnMap["sender_name"])) {

                $insertColumns[] =
                    "sender_name";

                $insertValues[] =
                    $student_name;

                $placeholders[] =
                    "?";

            } elseif (
                isset($columnMap["user_name"])
            ) {

                $insertColumns[] =
                    "user_name";

                $insertValues[] =
                    $student_name;

                $placeholders[] =
                    "?";
            }

            if (!isset($columnMap["message"])) {
                student_json_response([
                    "success" => false,
                    "message" => "The classroom_messages table is missing the message column."
                ]);
            }

            $insertColumns[] =
                "message";

            $insertValues[] =
                $message;

            $placeholders[] =
                "?";

            $sql =
                "INSERT INTO classroom_messages (" .
                implode(", ", $insertColumns) .
                ") VALUES (" .
                implode(", ", $placeholders) .
                ")";

            $stmt =
                $pdo->prepare($sql);

            $stmt->execute(
                $insertValues
            );

            student_json_response([
                "success" => true,
                "id" => (int)$pdo->lastInsertId()
            ]);
        }

        /*
         * -----------------------------------------------------
         * LOAD CHAT MESSAGES
         * -----------------------------------------------------
         */
        if ($action === "get_messages") {

            $lastMessageId =
                max(
                    0,
                    (int)(
                        $_POST["last_message_id"] ?? 0
                    )
                );

            if ($apiRoom === "") {
                student_json_response([
                    "success" => true,
                    "messages" => []
                ]);
            }

            $columnsStmt =
                $pdo->query(
                    "SHOW COLUMNS FROM classroom_messages"
                );

            $columns =
                $columnsStmt->fetchAll(
                    PDO::FETCH_COLUMN,
                    0
                );

            $columnMap =
                array_flip($columns);

            $senderColumn =
                isset($columnMap["sender_role"])
                    ? "sender_role"
                    : (
                        isset($columnMap["sender_type"])
                            ? "sender_type"
                            : "NULL"
                    );

            $nameColumn =
                isset($columnMap["sender_name"])
                    ? "sender_name"
                    : (
                        isset($columnMap["user_name"])
                            ? "user_name"
                            : "''"
                    );

            $createdColumn =
                isset($columnMap["created_at"])
                    ? "created_at"
                    : "NULL";

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    {$senderColumn} AS sender_role,
                    {$nameColumn} AS sender_name,
                    message,
                    {$createdColumn} AS created_at
                FROM classroom_messages
                WHERE booking_id = ?
                  AND room_code = ?
                  AND id > ?
                ORDER BY id ASC
                LIMIT 100
            ");

            $stmt->execute([
                $booking_id,
                $apiRoom,
                $lastMessageId
            ]);

            student_json_response([
                "success" => true,
                "messages" =>
                    $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
        }

        student_json_response([
            "success" => false,
            "message" => "Unknown classroom action."
        ]);

    } catch (PDOException $e) {

        error_log(
            "NISEL student classroom error: " .
            $e->getMessage()
        );

        student_json_response([
            "success" => false,
            "message" =>
                "A classroom database error occurred."
        ]);
    }
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
   LIVE CLASSROOM CHAT
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
        async function(event) {

            event.preventDefault();

            const message =
                chatInput.value.trim();

            if (!message || !joined) {
                return;
            }

            chatInput.disabled =
                true;

            try {

                const result =
                    await classroomPost({
                        classroom_action:
                            'send_message',
                        message:
                            message
                    });

                if (!result.success) {

                    alert(
                        result.message ||
                        'Unable to send message.'
                    );

                    return;
                }

                chatInput.value =
                    '';

                await loadChatMessages();

            } catch (error) {

                console.error(
                    'Chat error:',
                    error
                );

                alert(
                    error.message ||
                    'Unable to send message.'
                );

            } finally {

                chatInput.disabled =
                    false;

                chatInput.focus();
            }
        }
    );
}


async function loadChatMessages() {

    if (!joined || !chatMessages) {
        return;
    }

    try {

        const result =
            await classroomPost({
                classroom_action:
                    'get_messages',
                last_message_id:
                    lastMessageId
            });

        if (
            !result.success ||
            !Array.isArray(result.messages)
        ) {
            return;
        }

        result.messages.forEach(
            function(item) {

                lastMessageId =
                    Math.max(
                        lastMessageId,
                        parseInt(
                            item.id,
                            10
                        ) || 0
                    );

                const emptyMessage =
                    chatMessages.querySelector(
                        '.chat-empty'
                    );

                if (emptyMessage) {
                    emptyMessage.remove();
                }

                const row =
                    document.createElement(
                        'div'
                    );

                row.style.padding =
                    '9px 11px';

                row.style.marginBottom =
                    '8px';

                row.style.background =
                    item.sender_role ===
                        'student'
                        ? '#e8f3ff'
                        : '#f1f7fc';

                row.style.borderRadius =
                    '9px';

                row.style.fontSize =
                    '12px';

                const sender =
                    document.createElement(
                        'strong'
                    );

                sender.textContent =
                    (
                        item.sender_role ===
                        'student'
                    )
                        ? 'You: '
                        : (
                            (item.sender_name ||
                            'Teacher') +
                            ': '
                        );

                const body =
                    document.createElement(
                        'span'
                    );

                body.textContent =
                    item.message || '';

                row.appendChild(
                    sender
                );

                row.appendChild(
                    body
                );

                chatMessages.appendChild(
                    row
                );
            }
        );

        if (result.messages.length) {

            chatMessages.scrollTop =
                chatMessages.scrollHeight;
        }

    } catch (error) {

        console.error(
            'Load chat error:',
            error
        );
    }
}


setInterval(
    function() {

        if (joined) {
            loadChatMessages();
        }

    },
    2000
);



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
