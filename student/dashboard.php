<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/db.php";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


$student_id =
    $_SESSION["student_id"] ?? 0;


if (!$student_id) {

    header("Location: login.php");
    exit;

}


$student_name =
    $_SESSION["student_name"]
    ?? "Student";


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
COUNTS
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE student_id = ?
");

$stmt->execute([
    $student_id
]);

$total_lessons =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        student_id = ?
        AND payment_status IN
        ('Paid','paid','Success','successful','Completed')
");

$stmt->execute([
    $student_id
]);

$paid_lessons =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        student_id = ?
        AND live_status = 'live'
");

$stmt->execute([
    $student_id
]);

$live_lessons =
    (int)$stmt->fetchColumn();


$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings
    WHERE
        student_id = ?
        AND lesson_status = 'Completed'
");

$stmt->execute([
    $student_id
]);

$completed_lessons =
    (int)$stmt->fetchColumn();


/*
=========================================================
RECENT LESSONS
=========================================================
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        booking_reference,
        subjects,
        curriculum,
        class_year,
        lesson_date,
        lesson_time,
        teacher_id,
        teacher_name,
        payment_status,
        lesson_status,
        live_status,
        live_room_code
    FROM bookings
    WHERE student_id = ?
    ORDER BY id DESC
    LIMIT 8
");

$stmt->execute([
    $student_id
]);

$lessons =
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
Student Dashboard | NISEL
</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial,sans-serif;
    background: #eef3f8;
    color: #172033;
}

.layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 235px;
    background: #003b70;
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    padding: 25px 12px;
    color: white;
}

.logo {
    text-align: center;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(255,255,255,.12);
}

.logo strong {
    font-size: 20px;
}

.logo small {
    display: block;
    font-size: 9px;
    letter-spacing: 2px;
    color: #a8d8f5;
    margin-top: 4px;
}

.nav {
    margin-top: 25px;
}

.nav a {
    display: block;
    color: #dcecff;
    text-decoration: none;
    padding: 13px;
    margin-bottom: 6px;
    border-radius: 9px;
    font-size: 12px;
}

.nav a:hover,
.nav a.active {
    background: rgba(255,255,255,.13);
    color: white;
}

.logout {
    background: #dc3545 !important;
    margin-top: 25px;
}

.main {
    margin-left: 235px;
    width: calc(100% - 235px);
    padding: 28px;
}

.hero {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
}

.hero h1 {
    margin: 0;
    color: #003b70;
    font-size: 27px;
}

.hero p {
    margin: 7px 0 0;
    color: #718096;
    font-size: 13px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
}

.label {
    color: #718096;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.number {
    color: #003b70;
    font-size: 28px;
    font-weight: 800;
    margin-top: 7px;
}

.panel {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 7px 25px rgba(0,0,0,.06);
}

.panel-header {
    padding: 20px;
    border-bottom: 1px solid #edf1f5;
}

.panel-header h2 {
    margin: 0;
    color: #003b70;
    font-size: 18px;
}

.lesson {
    padding: 17px 20px;
    border-bottom: 1px solid #edf1f5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.lesson:last-child {
    border-bottom: none;
}

.subject {
    color: #003b70;
    font-weight: 800;
    font-size: 13px;
}

.teacher {
    color: #718096;
    font-size: 10px;
    margin-top: 5px;
}

.meta {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    margin-top: 8px;
}

.meta span {
    background: #f4f7fa;
    padding: 5px 8px;
    border-radius: 5px;
    color: #667085;
    font-size: 9px;
}

.button {
    display: inline-block;
    padding: 9px 12px;
    border-radius: 7px;
    background: #0877c9;
    color: white;
    text-decoration: none;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.badge {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 8px;
    font-weight: 800;
}

.live {
    background: #ffe4e4;
    color: #b42318;
}

.waiting {
    background: #fff1d6;
    color: #986000;
}

.paid {
    background: #dcf7e8;
    color: #137a43;
}

@media(max-width:900px) {

    .cards {
        grid-template-columns: repeat(2,1fr);
    }

}

@media(max-width:650px) {

    .sidebar {
        width: 65px;
    }

    .logo strong,
    .logo small {
        display: none;
    }

    .nav a {
        font-size: 0;
        text-align: center;
    }

    .main {
        margin-left: 65px;
        width: calc(100% - 65px);
        padding: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    .lesson {
        align-items: flex-start;
        flex-direction: column;
    }

}

</style>

</head>

<body>

<div class="layout">

<aside class="sidebar">

    <div class="logo">

        <strong>NISEL</strong>

        <small>
            ONLINE EDUCATION
        </small>

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

        <a href="classroom.php">
            🎥 Live Classroom
        </a>

        <a href="profile.php">
            👤 My Profile
        </a>

        <a href="payments.php">
            💳 My Payments
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

<section class="hero">

    <h1>
        Welcome, <?= e($student_name) ?>
    </h1>

    <p>
        Manage your lessons, schedule and live virtual classes.
    </p>

</section>


<section class="cards">

    <div class="card">
        <div class="label">Lessons</div>
        <div class="number">
            <?= $total_lessons ?>
        </div>
    </div>

    <div class="card">
        <div class="label">Paid Lessons</div>
        <div class="number">
            <?= $paid_lessons ?>
        </div>
    </div>

    <div class="card">
        <div class="label">Live Now</div>
        <div class="number">
            <?= $live_lessons ?>
        </div>
    </div>

    <div class="card">
        <div class="label">Completed</div>
        <div class="number">
            <?= $completed_lessons ?>
        </div>
    </div>

</section>


<section class="panel">

    <div class="panel-header">

        <h2>
            My Recent Lessons
        </h2>

    </div>


    <?php if ($lessons): ?>

        <?php foreach ($lessons as $lesson): ?>

            <?php

            $live =
                strtolower(
                    trim(
                        $lesson["live_status"]
                        ?? "waiting"
                    )
                );

            $payment =
                strtolower(
                    trim(
                        $lesson["payment_status"]
                        ?? ""
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

            ?>

            <div class="lesson">

                <div>

                    <div class="subject">

                        <?= e(
                            $lesson["subjects"]
                            ?? "Lesson"
                        ) ?>

                    </div>


                    <div class="teacher">

                        Teacher:
                        <?= e(
                            $lesson["teacher_name"]
                            ?? "Not assigned"
                        ) ?>

                    </div>


                    <div class="meta">

                        <span>
                            📅
                            <?= !empty($lesson["lesson_date"])
                                ? e(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $lesson["lesson_date"]
                                        )
                                    )
                                )
                                : "Date not set"
                            ?>
                        </span>

                        <span>
                            🕐
                            <?= !empty($lesson["lesson_time"])
                                ? e(
                                    date(
                                        "h:i A",
                                        strtotime(
                                            $lesson["lesson_time"]
                                        )
                                    )
                                )
                                : "Time not set"
                            ?>
                        </span>

                        <?php if ($is_paid): ?>

                            <span class="badge paid">
                                ✓ PAID
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <div>

                    <?php if ($live === "live"): ?>

                        <div
                            class="badge live"
                            style="margin-bottom:6px;"
                        >
                            🔴 LIVE NOW
                        </div>

                    <?php elseif ($live === "waiting"): ?>

                        <div
                            class="badge waiting"
                            style="margin-bottom:6px;"
                        >
                            WAITING
                        </div>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $lesson["teacher_id"]
                        )
                    ): ?>

                        <a
                            href="classroom.php?id=<?= (int)$lesson["id"] ?>"
                            class="button"
                        >
                            🎥 Classroom
                        </a>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div
            style="
                padding:50px;
                text-align:center;
                color:#718096;
                font-size:12px;
            "
        >
            You have no lessons yet.
        </div>

    <?php endif; ?>

</section>

</main>

</div>

</body>

</html>
