<?php

require "../teacher_auth.php";
require "../config/db.php";


/* =========================================================
   TEACHER INFORMATION
========================================================= */

$teacher_id = $_SESSION['teacher_id'] ?? '';

$teacher_name =
    $_SESSION['teacher_name'] ?? 'Teacher';


if (empty($teacher_id)) {

    header("Location: login.php");
    exit;

}


/* =========================================================
   READ SCHEDULE JSON
========================================================= */

$data_directory =
    dirname(__DIR__) . "/data";

$schedule_file =
    $data_directory . "/schedules.json";


if (!is_dir($data_directory)) {

    mkdir(
        $data_directory,
        0777,
        true
    );

}


if (!file_exists($schedule_file)) {

    file_put_contents(
        $schedule_file,
        json_encode([], JSON_PRETTY_PRINT)
    );

}


$schedules = [];

$json =
    file_get_contents($schedule_file);


if (
    $json !== false &&
    trim($json) !== ""
) {

    $decoded =
        json_decode($json, true);

    if (is_array($decoded)) {

        $schedules = $decoded;

    }

}


/* =========================================================
   GET TEACHER'S ASSIGNED STUDENTS
========================================================= */

try {

    $stmt = $pdo->prepare("

        SELECT *

        FROM bookings

        WHERE teacher_id = ?

        ORDER BY id DESC

    ");

    $stmt->execute([
        $teacher_id
    ]);

    $students =
        $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die(
        "Unable to load dashboard: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}


/* =========================================================
   FILTER TEACHER SCHEDULE
========================================================= */

$teacher_schedules = [];


foreach (
    $schedules
    as $lesson
) {

    if (
        isset(
            $lesson['teacher_id']
        )
        &&
        (string)$lesson['teacher_id']
        === (string)$teacher_id
    ) {

        $teacher_schedules[] =
            $lesson;

    }

}


/* =========================================================
   CURRENT DATE
========================================================= */

$today =
    date("Y-m-d");


$current_time =
    date("H:i");


/* =========================================================
   STATISTICS
========================================================= */

$total_students =
    count($students);

$total_lessons =
    count($teacher_schedules);

$completed_lessons = 0;

$scheduled_lessons = 0;

$cancelled_lessons = 0;

$today_lessons = 0;

$upcoming_lessons = 0;


foreach (
    $teacher_schedules
    as $lesson
) {

    $status =
        $lesson['lesson_status']
        ?? 'Scheduled';


    if (
        $status === "Completed"
    ) {

        $completed_lessons++;

    } elseif (
        $status === "Cancelled"
    ) {

        $cancelled_lessons++;

    } else {

        $scheduled_lessons++;

    }


    /* =============================================
       TODAY
    ============================================= */

    if (
        ($lesson['lesson_date'] ?? '')
        === $today
        &&
        $status !== "Cancelled"
    ) {

        $today_lessons++;

    }


    /* =============================================
       UPCOMING
    ============================================= */

    $lesson_datetime =
        ($lesson['lesson_date'] ?? '')
        . ' '
        .
        ($lesson['lesson_time'] ?? '00:00');


    if (
        $status === "Scheduled"
        &&
        $lesson_datetime >=
        date("Y-m-d H:i")
    ) {

        $upcoming_lessons++;

    }

}


/* =========================================================
   SORT SCHEDULE
========================================================= */

usort(
    $teacher_schedules,
    function (
        $a,
        $b
    ) {

        $date_a =
            ($a['lesson_date'] ?? '')
            . ' '
            .
            ($a['lesson_time'] ?? '');

        $date_b =
            ($b['lesson_date'] ?? '')
            . ' '
            .
            ($b['lesson_time'] ?? '');

        return strcmp(
            $date_a,
            $date_b
        );

    }
);


/* =========================================================
   GET TODAY'S LESSONS
========================================================= */

$today_schedule = [];


foreach (
    $teacher_schedules
    as $lesson
) {

    if (
        ($lesson['lesson_date'] ?? '')
        === $today
        &&
        ($lesson['lesson_status'] ?? '')
        !== "Cancelled"
    ) {

        $today_schedule[] =
            $lesson;

    }

}


/* =========================================================
   GET NEXT 5 LESSONS
========================================================= */

$upcoming_schedule = [];

foreach (
    $teacher_schedules
    as $lesson
) {

    $status =
        $lesson['lesson_status']
        ?? 'Scheduled';

    $datetime =
        ($lesson['lesson_date'] ?? '')
        . ' '
        .
        ($lesson['lesson_time'] ?? '00:00');


    if (
        $status === "Scheduled"
        &&
        $datetime >=
        date("Y-m-d H:i")
    ) {

        $upcoming_schedule[] =
            $lesson;

    }

}


$upcoming_schedule =
    array_slice(
        $upcoming_schedule,
        0,
        5
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
Teacher Dashboard |
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


.menu a:hover {

    background: #0055a5;

}


.menu a.active {

    background: #0055a5;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* =====================================================
   HEADER
===================================================== */

.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

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
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.card h3 {

    margin: 0;

    font-size: 30px;

    color: #003366;

}


.card p {

    margin: 8px 0 0;

    color: #777;

}


/* =====================================================
   SECTION
===================================================== */

.section {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.section h2 {

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

    min-width: 800px;

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

    background: #f7faff;

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


.scheduled {

    background: #cfe2ff;

    color: #084298;

}


.completed {

    background: #d4edda;

    color: #155724;

}


.cancelled {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   BUTTON
===================================================== */

.button {

    display: inline-block;

    padding: 9px 15px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 5px;

}


.button:hover {

    background: #0055a5;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 35px;

    color: #777;

}


/* =====================================================
   MOBILE
===================================================== */

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

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<div class="logo">

NISEL<br>
ONLINE EDUCATION

</div>


<div class="menu">


<a
    href="dashboard.php"
    class="active"
>
🏠 Dashboard
</a>


<a href="students.php">
👨‍🎓 My Students
</a>


<a href="schedule.php">
📅 My Schedule
</a>


<a href="profile.php">
👤 My Profile
</a>

<a href="classroom.php?id=<?= (int)$booking['id'] ?>">
🎥 Classroom
</a>


<a href="logout.php">
🚪 Logout
</a>


</div>


</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


<!-- HEADER -->

<div class="header">

<h1>

Welcome,
<?php
echo htmlspecialchars(
    $teacher_name
);
?>

</h1>

<p>

Teacher Dashboard —
NISEL ONLINE EDUCATION

</p>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


<div class="card">

<h3>

<?php
echo $total_students;
?>

</h3>

<p>
Assigned Students
</p>

</div>


<div class="card">

<h3>

<?php
echo $total_lessons;
?>

</h3>

<p>
Total Lessons
</p>

</div>


<div class="card">

<h3>

<?php
echo $today_lessons;
?>

</h3>

<p>
Today's Lessons
</p>

</div>


<div class="card">

<h3>

<?php
echo $completed_lessons;
?>

</h3>

<p>
Completed Lessons
</p>

</div>


<div class="card">

<h3>

<?php
echo $upcoming_lessons;
?>

</h3>

<p>
Upcoming Lessons
</p>

</div>


</div>


<!-- =====================================================
     TODAY
===================================================== -->

<div class="section">


<h2>

📅 Today's Lessons

</h2>


<?php if (
    count($today_schedule) > 0
): ?>


<div class="table-wrapper">


<table>


<tr>

<th>
Time
</th>

<th>
Student
</th>

<th>
Subject
</th>

<th>
Curriculum
</th>

<th>
Lesson
</th>

<th>
Status
</th>

</tr>


<?php foreach (
    $today_schedule
    as $lesson
): ?>


<tr>


<td>

<strong>

<?php

echo date(
    "h:i A",
    strtotime(
        $lesson[
            'lesson_time'
        ]
    )
);

?>

</strong>

</td>


<td>

<?php

echo htmlspecialchars(
    $lesson[
        'student_name'
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $lesson[
        'subjects'
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $lesson[
        'curriculum'
    ]
);

?>

</td>


<td>

Lesson

<?php

echo (int)
    $lesson[
        'lesson_number'
    ];

?>

of 8

</td>


<td>


<?php

$status =
    $lesson[
        'lesson_status'
    ]
    ?? 'Scheduled';


if (
    $status ===
    'Completed'
) {

    echo '
    <span class="badge completed">
        Completed
    </span>';

} elseif (
    $status ===
    'Cancelled'
) {

    echo '
    <span class="badge cancelled">
        Cancelled
    </span>';

} else {

    echo '
    <span class="badge scheduled">
        Scheduled
    </span>';

}

?>


</td>


</tr>


<?php endforeach; ?>


</table>


</div>


<?php else: ?>


<div class="empty">

📅

<p>
You have no lessons scheduled for today.
</p>

</div>


<?php endif; ?>


</div>


<!-- =====================================================
     UPCOMING
===================================================== -->

<div class="section">


<h2>

⏰ Upcoming Lessons

</h2>


<?php if (
    count($upcoming_schedule) > 0
): ?>


<div class="table-wrapper">


<table>


<tr>

<th>
Date
</th>

<th>
Time
</th>

<th>
Student
</th>

<th>
Subject
</th>

<th>
Lesson
</th>

<th>
Status
</th>

</tr>


<?php foreach (
    $upcoming_schedule
    as $lesson
): ?>


<tr>


<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $lesson[
            'lesson_date'
        ]
    )
);

?>

<br>

<small>

<?php

echo date(
    "l",
    strtotime(
        $lesson[
            'lesson_date'
        ]
    )
);

?>

</small>

</td>


<td>

<?php

echo date(
    "h:i A",
    strtotime(
        $lesson[
            'lesson_time'
        ]
    )
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $lesson[
        'student_name'
    ]
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $lesson[
        'subjects'
    ]
);

?>

</td>


<td>

Lesson

<?php

echo (int)
    $lesson[
        'lesson_number'
    ];

?>

of 8

</td>


<td>

<span class="badge scheduled">

Scheduled

</span>

</td>


</tr>


<?php endforeach; ?>


</table>


</div>


<?php else: ?>


<div class="empty">

No upcoming lessons.

</div>


<?php endif; ?>


</div>


<!-- =====================================================
     STUDENTS
===================================================== -->

<div class="section">


<h2>

👨‍🎓 My Students

</h2>


<?php if (
    count($students) > 0
): ?>


<div class="table-wrapper">


<table>


<tr>

<th>
Student
</th>

<th>
Curriculum
</th>

<th>
Class
</th>

<th>
Subject(s)
</th>

<th>
Payment
</th>

<th>
Action
</th>

</tr>


<?php foreach (
    $students
    as $student
): ?>


<tr>


<td>

<strong>

<?php

echo htmlspecialchars(
    $student[
        'student_name'
    ]
);

?>

</strong>

<br>

<small>

<?php

echo htmlspecialchars(
    $student[
        'email'
    ] ?? ''
);

?>

</small>

</td>


<td>

<?php

echo htmlspecialchars(
    $student[
        'curriculum'
    ] ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $student[
        'class_year'
    ] ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $student[
        'subjects'
    ] ?? ''
);

?>

</td>


<td>

<?php

$payment =
    strtolower(
        trim(
            $student[
                'payment_status'
            ] ?? ''
        )
    );


if (
    $payment === 'paid'
    ||
    $payment === 'success'
) {

    echo '
    <span class="badge completed">
        PAID
    </span>';

} else {

    echo '
    <span class="badge cancelled">
        PENDING
    </span>';

}

?>

</td>


<td>

<a
    href="student_details.php?id=<?php
        echo (int)
            $student['id'];
    ?>"
    class="button"
>

View

</a>

</td>


</tr>


<?php endforeach; ?>


</table>


</div>


<?php else: ?>


<div class="empty">

👨‍🎓

<p>
No students have been assigned to you yet.
</p>

</div>


<?php endif; ?>


</div>


</div>


</body>

</html>
