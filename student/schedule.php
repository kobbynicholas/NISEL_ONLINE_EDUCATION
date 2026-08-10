<?php

session_start();

require "../config/db.php";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}


$student_id = $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name'] ?? "Student";

$student_email =
    $_SESSION['student_email'] ?? "";


/* =========================================================
   GET STUDENT BOOKINGS / SCHEDULE
========================================================= */

try {

    /*
     * Your current bookings table contains the student,
     * subjects and assigned teacher information.
     *
     * We first retrieve the bookings. If schedule-specific
     * columns exist, they will also be displayed.
     */

    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE email = ?
        AND teacher_name IS NOT NULL
        AND teacher_name <> ''
        ORDER BY id DESC
    ");

    $stmt->execute([
        $student_email
    ]);

    $schedules = $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load your schedule: "
        . $e->getMessage()
    );

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
My Schedule | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

}


/* =====================================================
   SIDEBAR
===================================================== */

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

    margin-bottom: 30px;

}


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 6px;

}


.sidebar a:hover {

    background: #0055aa;

}


.sidebar a.active {

    background: #0055aa;

}


.logout {

    background: #dc3545;

    margin-top: 25px;

}


.logout:hover {

    background: #bb2d3b !important;

}


/* =====================================================
   MAIN CONTENT
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.header p {

    margin: 0;

    color: #666;

}


/* =====================================================
   SCHEDULE CONTAINER
===================================================== */

.schedule-container {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.schedule-container h2 {

    margin-top: 0;

    color: #003366;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 850px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

}


tr:hover {

    background: #f7f9fc;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.assigned {

    background: #d1ecf1;

    color: #0c5460;

}


.paid {

    background: #d4edda;

    color: #155724;

}


.pending {

    background: #fff3cd;

    color: #856404;

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}


.empty-icon {

    font-size: 50px;

    margin-bottom: 15px;

}


/* =====================================================
   INFO BOX
===================================================== */

.info {

    background: #f4f9ff;

    border-left: 5px solid #003366;

    padding: 15px;

    margin-bottom: 20px;

    border-radius: 6px;

    color: #555;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 20px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<div class="logo">

NISEL ONLINE EDUCATION

</div>


<a href="dashboard.php">

🏠 Dashboard

</a>


<a href="profile.php">

👤 My Profile

</a>


<a href="bookings.php">

📚 My Bookings

</a>


<a
    href="schedule.php"
    class="active"
>

📅 My Schedule

</a>


<a href="payments.php">

💳 Payments

</a>


<a
    href="logout.php"
    class="logout"
>

🚪 Logout

</a>


</div>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


<div class="header">

<h1>

My Lesson Schedule

</h1>

<p>

View lessons for which a teacher has been
assigned by NISEL ONLINE EDUCATION.

</p>

</div>


<div class="schedule-container">


<div class="info">

<strong>Important:</strong>

Your teacher is assigned by the NISEL
ONLINE EDUCATION administrator. If a teacher
has not yet been assigned, the booking will
not appear in this schedule.

</div>


<h2>
Scheduled Lessons
</h2>


<?php if (count($schedules) > 0): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
Subject(s)
</th>

<th>
Curriculum
</th>

<th>
Class / Year
</th>

<th>
Teacher
</th>

<th>
Payment
</th>

<th>
Schedule Date
</th>

<th>
Time
</th>

</tr>

</thead>


<tbody>


<?php foreach ($schedules as $schedule): ?>


<tr>


<!-- SUBJECT -->

<td>

<?php

echo htmlspecialchars(
    $schedule['subjects'] ?? ''
);

?>

</td>


<!-- CURRICULUM -->

<td>

<?php

echo htmlspecialchars(
    $schedule['curriculum'] ?? ''
);

?>

</td>


<!-- CLASS / YEAR -->

<td>

<?php

$class_year =
    $schedule['class_year']
    ?? $schedule['class']
    ?? '';

echo htmlspecialchars(
    $class_year
);

?>

</td>


<!-- TEACHER -->

<td>

<span class="badge assigned">

<?php

echo htmlspecialchars(
    $schedule['teacher_name']
);

?>

</span>

</td>


<!-- PAYMENT -->

<td>

<?php

$payment_status =
    strtolower(
        trim(
            $schedule['payment_status']
            ?? ''
        )
    );


if (
    $payment_status === "paid" ||
    $payment_status === "success"
) {

    echo '
        <span class="badge paid">
            Paid
        </span>
    ';

} else {

    echo '
        <span class="badge pending">
            Pending
        </span>
    ';

}

?>

</td>


<!-- DATE -->

<td>

<?php

/*
 * These fields are checked safely because your current
 * bookings table may not yet contain schedule_date.
 */

$schedule_date =
    $schedule['schedule_date']
    ?? $schedule['lesson_date']
    ?? $schedule['date']
    ?? 'Not scheduled';


if (
    $schedule_date !== 'Not scheduled'
    &&
    $schedule_date !== ''
) {

    $timestamp =
        strtotime($schedule_date);

    if ($timestamp !== false) {

        echo htmlspecialchars(
            date(
                "d M Y",
                $timestamp
            )
        );

    } else {

        echo htmlspecialchars(
            $schedule_date
        );

    }

} else {

    echo "Not scheduled";

}

?>

</td>


<!-- TIME -->

<td>

<?php

$schedule_time =
    $schedule['schedule_time']
    ?? $schedule['lesson_time']
    ?? $schedule['time']
    ?? 'Not scheduled';


echo htmlspecialchars(
    $schedule_time
);

?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty">


<div class="empty-icon">
📅
</div>


<h3>
No Scheduled Lessons
</h3>


<p>
You currently have no lessons assigned to a teacher.
</p>


<p>
Once the administrator assigns a teacher and
sets your lesson schedule, it will appear here.
</p>


</div>


<?php endif; ?>


</div>


</div>


</body>

</html>
