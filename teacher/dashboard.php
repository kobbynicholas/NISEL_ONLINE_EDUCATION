<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/db.php";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


/*
=========================================================
TEACHER SESSION
=========================================================
*/

$teacher_id = $_SESSION["teacher_id"] ?? 0;

if (!$teacher_id) {
    header("Location: login.php");
    exit;
}

$teacher_name =
    $_SESSION["teacher_name"]
    ?? "Teacher";


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
GET TEACHER
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT
        teacher_id,
        teacher_name,
        email,
        phone,
        status
    FROM teachers
    WHERE teacher_id = ?
    LIMIT 1
");

$stmt->execute([$teacher_id]);

$teacher = $stmt->fetch();

if (!$teacher) {

    session_destroy();

    header("Location: login.php");
    exit;
}

$teacher_name =
    $teacher["teacher_name"];


/*
=========================================================
COUNTS
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE teacher_id = ?
");

$stmt->execute([$teacher_id]);

$total_bookings =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        teacher_id = ?
        AND payment_status IN
        ('Paid','paid','Success','successful','Completed')
");

$stmt->execute([$teacher_id]);

$paid_bookings =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        teacher_id = ?
        AND live_status = 'live'
");

$stmt->execute([$teacher_id]);

$live_classes =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        teacher_id = ?
        AND lesson_status = 'Completed'
");

$stmt->execute([$teacher_id]);

$completed_classes =
    (int)$stmt->fetchColumn();


/*
=========================================================
RECENT BOOKINGS
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        booking_reference,
        student_name,
        email,
        subjects,
        curriculum,
        class_year,
        lesson_date,
        lesson_time,
        payment_status,
        lesson_status,
        live_status,
        live_room_code
    FROM bookings
    WHERE teacher_id = ?
    ORDER BY id DESC
    LIMIT 10
");

$stmt->execute([$teacher_id]);

$bookings =
    $stmt->fetchAll();

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
Teacher Dashboard | NISEL ONLINE EDUCATION
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Inter,
        Arial,
        sans-serif;
    background: #eef3f8;
    color: #182230;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 240px;
    background: #003b70;
    color: white;
    padding: 25px 15px;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
}

.logo {
    text-align: center;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(255,255,255,.12);
}

.logo strong {
    display: block;
    font-size: 20px;
}

.logo small {
    display: block;
    margin-top: 5px;
    color: #a8d8f5;
    font-size: 9px;
    letter-spacing: 2px;
}

.nav {
    margin-top: 25px;
}

.nav a {
    display: block;
    padding: 13px 14px;
    margin-bottom: 6px;
    border-radius: 9px;
    color: #dcecff;
    text-decoration: none;
    font-size: 13px;
}

.nav a:hover,
.nav a.active {
    background: rgba(255,255,255,.13);
    color: white;
}

.logout {
    margin-top: 30px !important;
    background: #dc3545 !important;
}

.main {
    margin-left: 240px;
    width: calc(100% - 240px);
    padding: 28px;
}

.welcome {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
    margin-bottom: 22px;
}

.welcome h1 {
    margin: 0;
    color: #003b70;
    font-size: 27px;
}

.welcome p {
    margin: 7px 0 0;
    color: #718096;
    font-size: 13px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.card {
    background: white;
    padding: 21px;
    border-radius: 14px;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
}

.card-label {
    color: #718096;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 700;
}

.card-number {
    margin-top: 8px;
    color: #003b70;
    font-size: 28px;
    font-weight: 800;
}

.panel {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 19px 22px;
    border-bottom: 1px solid #edf1f5;
}

.panel-header h2 {
    margin: 0;
    color: #003b70;
    font-size: 18px;
}

.schedule-link {
    color: #0877c9;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

th {
    background: #003b70;
    color: white;
    padding: 13px;
    text-align: left;
    font-size: 10px;
}

td {
    padding: 13px;
    border-bottom: 1px solid #edf1f5;
    font-size: 11px;
}

.student {
    font-weight: 800;
}

.subject {
    display: inline-block;
    background: #edf5fc;
    color: #075a9e;
    padding: 5px 8px;
    border-radius: 6px;
    font-weight: 700;
}

.badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
}

.paid {
    background: #dcf7e8;
    color: #137a43;
}

.pending {
    background: #fff1d6;
    color: #986000;
}

.live {
    background: #ffe4e4;
    color: #b42318;
}

.normal {
    background: #edf1f5;
    color: #667085;
}

.classroom-btn {
    display: inline-block;
    padding: 8px 11px;
    border-radius: 7px;
    background: #0877c9;
    color: white;
    text-decoration: none;
    font-size: 10px;
    font-weight: 800;
}

.classroom-btn:hover {
    background: #075f9e;
}

.empty {
    text-align: center;
    padding: 45px;
    color: #7b8794;
}

@media(max-width:1000px) {

    .cards {
        grid-template-columns: repeat(2,1fr);
    }
}

@media(max-width:700px) {

    .sidebar {
        width: 70px;
        padding: 15px 8px;
    }

    .logo strong,
    .logo small {
        display: none;
    }

    .nav a {
        text-align: center;
        font-size: 0;
    }

    .nav a::first-letter {
        font-size: 18px;
    }

    .main {
        margin-left: 70px;
        width: calc(100% - 70px);
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

    <div class="logo">
        <strong>NISEL</strong>
        <small>ONLINE EDUCATION</small>
    </div>

    <nav class="nav">

        <a
            href="dashboard.php"
            class="active"
        >
            🏠 Dashboard
        </a>

        <a href="schedule.php">
            📅 My Schedule
        </a>

        <a href="assignments.php">
            📝 Assignments
        </a>

        <a href="classroom.php">
            🎥 Classroom
        </a>

        <a href="profile.php">
            👤 My Profile
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            🚪 Logout
        </a>

    </nav>

</aside>


<main class="main">

    <section class="welcome">

        <h1>
            Welcome, <?= e($teacher_name) ?>
        </h1>

        <p>
            Manage your students, lessons and live virtual classes from your NISEL teacher portal.
        </p>

    </section>


    <section class="cards">

        <div class="card">

            <div class="card-label">
                Total Lessons
            </div>

            <div class="card-number">
                <?= number_format($total_bookings) ?>
            </div>

        </div>


        <div class="card">

            <div class="card-label">
                Paid Lessons
            </div>

            <div class="card-number">
                <?= number_format($paid_bookings) ?>
            </div>

        </div>


        <div class="card">

            <div class="card-label">
                Live Classes
            </div>

            <div class="card-number">
                <?= number_format($live_classes) ?>
            </div>

        </div>


        <div class="card">

            <div class="card-label">
                Completed
            </div>

            <div class="card-number">
                <?= number_format($completed_classes) ?>
            </div>

        </div>

    </section>


    <section class="panel">

        <div class="panel-header">

            <h2>
                Recent Lessons
            </h2>

            <a
                href="schedule.php"
                class="schedule-link"
            >
                View Full Schedule →
            </a>

        </div>


        <div class="table-wrapper">

        <?php if ($bookings): ?>

        <table>

            <thead>

                <tr>

                    <th>Student</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Payment</th>
                    <th>Live Status</th>
                    <th>Classroom</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($bookings as $booking): ?>

                <?php

                $payment =
                    strtolower(
                        trim(
                            $booking["payment_status"] ?? ""
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

                $live_status =
                    strtolower(
                        trim(
                            $booking["live_status"] ?? "waiting"
                        )
                    );

                ?>

                <tr>

                    <td>

                        <div class="student">
                            <?= e(
                                $booking["student_name"]
                                ?? "Student"
                            ) ?>
                        </div>

                        <div style="color:#8994a3;font-size:9px;">
                            <?= e(
                                $booking["email"] ?? ""
                            ) ?>
                        </div>

                    </td>


                    <td>

                        <span class="subject">
                            <?= e(
                                $booking["subjects"]
                                ?? "Lesson"
                            ) ?>
                        </span>

                    </td>


                    <td>
                        <?= !empty($booking["lesson_date"])
                            ? e(
                                date(
                                    "d M Y",
                                    strtotime(
                                        $booking["lesson_date"]
                                    )
                                )
                            )
                            : "—"
                        ?>
                    </td>


                    <td>
                        <?= !empty($booking["lesson_time"])
                            ? e(
                                date(
                                    "h:i A",
                                    strtotime(
                                        $booking["lesson_time"]
                                    )
                                )
                            )
                            : "—"
                        ?>
                    </td>


                    <td>

                        <?php if ($is_paid): ?>

                            <span class="badge paid">
                                ✓ PAID
                            </span>

                        <?php else: ?>

                            <span class="badge pending">
                                <?= e(
                                    $booking["payment_status"]
                                    ?? "Pending"
                                ) ?>
                            </span>

                        <?php endif; ?>

                    </td>


                    <td>

                        <?php if ($live_status === "live"): ?>

                            <span class="badge live">
                                🔴 LIVE
                            </span>

                        <?php elseif ($live_status === "ended"): ?>

                            <span class="badge normal">
                                ENDED
                            </span>

                        <?php else: ?>

                            <span class="badge normal">
                                WAITING
                            </span>

                        <?php endif; ?>

                    </td>


                    <td>

                        <a
                            href="classroom.php?id=<?= (int)$booking["id"] ?>"
                            class="classroom-btn"
                        >
                            🎥 Classroom
                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

        <?php else: ?>

            <div class="empty">
                You currently have no assigned lessons.
            </div>

        <?php endif; ?>

        </div>

    </section>

</main>

</div>

</body>

</html>
