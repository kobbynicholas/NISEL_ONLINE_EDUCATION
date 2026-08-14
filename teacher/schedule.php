<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER SCHEDULE
| PDO VERSION
|--------------------------------------------------------------------------
*/

require "../teacher_auth.php";
require "../config/db.php";


/*
|--------------------------------------------------------------------------
| LOGGED-IN TEACHER
|--------------------------------------------------------------------------
*/

$teacher_id =
    $_SESSION['teacher_id'];

$teacher_name =
    $_SESSION['teacher_name']
    ?? 'Teacher';


/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/

$message = "";

$message_type = "";


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| UPDATE LESSON SCHEDULE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['update_schedule'])
) {

    $booking_id =
        filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );


    $lesson_date =
        trim(
            $_POST['lesson_date'] ?? ''
        );


    $lesson_time =
        trim(
            $_POST['lesson_time'] ?? ''
        );


    $lesson_status =
        trim(
            $_POST['lesson_status']
            ?? 'Scheduled'
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE BOOKING ID
    |--------------------------------------------------------------------------
    */

    if (!$booking_id) {

        $message =
            "Invalid booking.";

        $message_type =
            "error";

    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE DATE
    |--------------------------------------------------------------------------
    */

    elseif (
        $lesson_date === ''
    ) {

        $message =
            "Please select a lesson date.";

        $message_type =
            "error";

    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE TIME
    |--------------------------------------------------------------------------
    */

    elseif (
        $lesson_time === ''
    ) {

        $message =
            "Please select a lesson time.";

        $message_type =
            "error";

    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATE STATUS
    |--------------------------------------------------------------------------
    */

    elseif (
        !in_array(
            $lesson_status,
            [
                'Scheduled',
                'Completed',
                'Cancelled'
            ],
            true
        )
    ) {

        $message =
            "Invalid lesson status.";

        $message_type =
            "error";

    }

    else {

        try {

            /*
            |--------------------------------------------------------------------------
            | SECURITY CHECK
            |--------------------------------------------------------------------------
            |
            | Make sure this booking belongs to the
            | currently logged-in teacher.
            |
            */

            $check =
                $pdo->prepare("
                    SELECT id
                    FROM bookings
                    WHERE id = ?
                    AND teacher_id = ?
                    LIMIT 1
                ");


            $check->execute([
                $booking_id,
                $teacher_id
            ]);


            $booking =
                $check->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$booking) {

                $message =
                    "You are not authorised to modify this booking.";

                $message_type =
                    "error";

            }

            else {

                /*
                |--------------------------------------------------------------------------
                | UPDATE BOOKING
                |--------------------------------------------------------------------------
                */

                $update =
                    $pdo->prepare("
                        UPDATE bookings

                        SET
                            lesson_date = ?,
                            lesson_time = ?,
                            lesson_status = ?

                        WHERE id = ?
                        AND teacher_id = ?
                    ");


                $update->execute([
                    $lesson_date,
                    $lesson_time,
                    $lesson_status,
                    $booking_id,
                    $teacher_id
                ]);


                $message =
                    "Lesson schedule updated successfully.";

                $message_type =
                    "success";
            }

        } catch (PDOException $e) {

            $message =
                "Unable to update the lesson schedule.";

            $message_type =
                "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| TODAY'S LESSONS
|--------------------------------------------------------------------------
*/

try {

    $todayStmt =
        $pdo->prepare("
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
                payment_status

            FROM bookings

            WHERE teacher_id = ?
            AND lesson_date = CURDATE()

            ORDER BY lesson_time ASC
        ");


    $todayStmt->execute([
        $teacher_id
    ]);


    $todayLessons =
        $todayStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $todayLessons = [];

    if ($message === "") {

        $message =
            "Unable to load today's lessons.";

        $message_type =
            "error";
    }
}


/*
|--------------------------------------------------------------------------
| ALL SCHEDULED LESSONS
|--------------------------------------------------------------------------
*/

try {

    $scheduleStmt =
        $pdo->prepare("
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
                payment_status

            FROM bookings

            WHERE teacher_id = ?

            ORDER BY

                CASE
                    WHEN lesson_date IS NULL
                    THEN 1
                    ELSE 0
                END,

                lesson_date ASC,
                lesson_time ASC
        ");


    $scheduleStmt->execute([
        $teacher_id
    ]);


    $schedules =
        $scheduleStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $schedules = [];

    if ($message === "") {

        $message =
            "Unable to load your lesson schedule.";

        $message_type =
            "error";
    }
}


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalLessons =
    count($schedules);


$todayLessonCount =
    count($todayLessons);

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
    My Schedule | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

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

    width: 250px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70 0%,
            #002b55 100%
        );

    color: white;

    padding: 24px 15px;

    z-index: 1000;

    overflow-y: auto;

}


.logo {

    padding:
        10px 8px 28px;

    text-align: center;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

    margin-bottom: 22px;

}


.logo-icon {

    width: 55px;

    height: 55px;

    margin:
        0 auto 12px;

    border-radius: 15px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

}


.logo h2 {

    font-size: 19px;

    line-height: 1.25;

    letter-spacing: .3px;

}


.logo p {

    margin-top: 6px;

    font-size: 9px;

    letter-spacing: 2px;

    opacity: .65;

}


.menu-title {

    color:
        rgba(
            255,
            255,
            255,
            .42
        );

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    padding:
        0 13px 10px;

}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 12px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration: none;

    padding:
        12px 13px;

    margin-bottom: 5px;

    border-radius: 9px;

    font-size: 13px;

    transition:
        .2s ease;

}


.sidebar a:hover {

    background:
        rgba(
            255,
            255,
            255,
            .10
        );

    color: white;

    transform:
        translateX(2px);

}


.sidebar a.active {

    background:
        rgba(
            255,
            255,
            255,
            .16
        );

    color: white;

    font-weight: 700;

    box-shadow:
        inset 3px 0 #4db8ff;

}


.menu-icon {

    width: 23px;

    text-align: center;

    font-size: 16px;

}


.logout {

    margin-top: 25px !important;

    background:
        rgba(
            220,
            53,
            69,
            .95
        ) !important;

    color: white !important;

}


.logout:hover {

    background:
        #c82333 !important;

}


/* ==================================================
   MAIN
================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* ==================================================
   TOP BAR
================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.topbar h2 {

    margin: 0;

    color: #003366;

}


.teacher {

    color: #666;

}


/* ==================================================
   PAGE HEADER
================================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

}


.page-header h2 {

    margin: 0 0 8px;

    color: #003366;

}


.page-header p {

    margin: 0;

    color: #666;

}


/* ==================================================
   STATISTICS
================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(200px, 1fr)
        );

    gap: 20px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

}


.stat-card h3 {

    margin: 0;

    font-size: 30px;

    color: #003366;

}


.stat-card p {

    margin: 8px 0 0;

    color: #777;

}


/* ==================================================
   MESSAGES
================================================== */

.message {

    padding: 14px;

    border-radius: 7px;

    margin-bottom: 20px;

}


.success {

    background: #d4edda;

    color: #155724;

}


.error {

    background: #f8d7da;

    color: #721c24;

}


/* ==================================================
   TODAY
================================================== */

.today {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

}


.today h3 {

    color: #003366;

    margin-top: 0;

}


/* ==================================================
   TABLE
================================================== */

.table-container {

    background: white;

    padding: 20px;

    border-radius: 12px;

    overflow-x: auto;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

}


td {

    padding: 12px;

    border-bottom:
        1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* ==================================================
   BADGES
================================================== */

.badge {

    display: inline-block;

    padding: 6px 10px;

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


.pending {

    background: #fff3cd;

    color: #856404;

}


.paid {

    background: #d4edda;

    color: #155724;

}


/* ==================================================
   FORM
================================================== */

.schedule-form {

    display: flex;

    flex-direction: column;

    gap: 8px;

}


.schedule-form input,
.schedule-form select {

    padding: 8px;

    border:
        1px solid #ccc;

    border-radius: 5px;

}


.schedule-form button {

    padding: 9px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

    font-weight: bold;

}


.schedule-form button:hover {

    background: #0055a5;

}


/* ==================================================
   NO DATA
================================================== */

.no-data {

    padding: 35px;

    text-align: center;

    color: #777;

}


/* ==================================================
   MOBILE
================================================== */

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


<!-- ==================================================
     SIDEBAR
================================================== -->

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

<!-- ==================================================
     MAIN
================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <h2>

            My Schedule

        </h2>


        <div class="teacher">

            Welcome,

            <strong>

                <?= h($teacher_name) ?>

            </strong>

        </div>


    </div>


    <!-- PAGE HEADER -->

    <div class="page-header">


        <h2>

            📅 Lesson Schedule

        </h2>


        <p>

            View and manage the lessons assigned
            to you.

        </p>


    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="message <?= h($message_type) ?>">

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <!-- STATISTICS -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?= $totalLessons ?>

            </h3>

            <p>

                Total Assigned Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?= $todayLessonCount ?>

            </h3>

            <p>

                Lessons Today

            </p>

        </div>


    </div>


    <!-- ==================================================
         TODAY'S LESSONS
    ================================================== -->

    <div class="today">


        <h3>

            📅 Today's Lessons

        </h3>


        <?php if ($todayLessonCount > 0): ?>


            <div class="table-container">


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
                            Status
                        </th>
                        
                        <th>
                           Classroom
                        </th>


                        
                    </tr>


                    <?php foreach (
                        $todayLessons
                        as $today
                    ): ?>


                        <tr>


                            <td>

                                <?php

                                if (
                                    !empty(
                                        $today['lesson_time']
                                    )
                                ) {

                                    echo h(
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $today['lesson_time']
                                            )
                                        )
                                    );

                                } else {

                                    echo "Time not set";

                                }

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?= h(
                                        $today['student_name']
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= h(
                                    $today['subjects']
                                ) ?>

                            </td>


                            <td>

                                <?= h(
                                    $today['curriculum']
                                ) ?>

                            </td>


                            <td>


                                <?php

                                $status =
                                    strtolower(
                                        trim(
                                            $today[
                                                'lesson_status'
                                            ]
                                            ??
                                            'scheduled'
                                        )
                                    );


                                if (
                                    $status ===
                                    'completed'
                                ) {

                                    echo
                                    '<span class="badge completed">
                                        Completed
                                    </span>';

                                }

                                elseif (
                                    $status ===
                                    'cancelled'
                                ) {

                                    echo
                                    '<span class="badge cancelled">
                                        Cancelled
                                    </span>';

                                }

                                else {

                                    echo
                                    '<span class="badge scheduled">
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


            <div class="no-data">

                <p>

                    You have no lessons scheduled
                    for today.

                </p>

            </div>


        <?php endif; ?>


    </div>


    <!-- ==================================================
         ALL LESSONS
    ================================================== -->

    <div class="table-container">


        <h3 style="color:#003366;">

            📚 All Assigned Lessons

        </h3>


        <?php if ($totalLessons > 0): ?>


            <table>


                <tr>

                    <th>
                        Student
                    </th>

                    <th>
                        Subject(s)
                    </th>

                    <th>
                        Curriculum
                    </th>

                    <th>
                        Class
                    </th>

                    <th>
                        Lesson Date
                    </th>

                    <th>
                        Lesson Time
                    </th>

                    <th>
                        Payment
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Update
                    </th>

                </tr>


                <?php foreach (
                    $schedules
                    as $row
                ): ?>


                    <?php

                    $lessonStatus =
                        strtolower(
                            trim(
                                $row[
                                    'lesson_status'
                                ]
                                ??
                                'scheduled'
                            )
                        );

                    ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>

                            <strong>

                                <?= h(
                                    $row[
                                        'student_name'
                                    ]
                                ) ?>

                            </strong>

                            <br>

                            <small>

                                <?= h(
                                    $row[
                                        'booking_reference'
                                    ]
                                ) ?>

                            </small>

                        </td>


                        <!-- SUBJECT -->

                        <td>

                            <?= h(
                                $row['subjects']
                            ) ?>

                        </td>


                        <!-- CURRICULUM -->

                        <td>

                            <?= h(
                                $row['curriculum']
                            ) ?>

                        </td>


                        <!-- CLASS -->

                        <td>

                            <?= h(
                                $row['class_year']
                            ) ?>

                        </td>


                        <!-- DATE -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $row[
                                        'lesson_date'
                                    ]
                                )
                            ) {

                                echo h(
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $row[
                                                'lesson_date'
                                            ]
                                        )
                                    )
                                );

                            } else {

                                echo
                                '<span style="color:#999">
                                    Not scheduled
                                </span>';

                            }

                            ?>

                        </td>


                        <!-- TIME -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $row[
                                        'lesson_time'
                                    ]
                                )
                            ) {

                                echo h(
                                    date(
                                        "h:i A",
                                        strtotime(
                                            $row[
                                                'lesson_time'
                                            ]
                                        )
                                    )
                                );

                            } else {

                                echo
                                '<span style="color:#999">
                                    Not set
                                </span>';

                            }

                            ?>

                        </td>


                        <!-- PAYMENT -->

                        <td>


                            <?php

                            $payment =
                                strtolower(
                                    trim(
                                        $row[
                                            'payment_status'
                                        ]
                                        ??
                                        ''
                                    )
                                );


                            if (
                                $payment === 'paid'
                                ||
                                $payment === 'success'
                            ) {

                                echo
                                '<span class="badge paid">
                                    PAID
                                </span>';

                            } else {

                                echo
                                '<span class="badge pending">'
                                .
                                h(
                                    strtoupper(
                                        $row[
                                            'payment_status'
                                        ]
                                        ??
                                        'PENDING'
                                    )
                                )
                                .
                                '</span>';

                            }

                            ?>

                        </td>


                        <!-- STATUS -->

                        <td>


                            <?php

                            if (
                                $lessonStatus ===
                                'completed'
                            ) {

                                echo
                                '<span class="badge completed">
                                    Completed
                                </span>';

                            }

                            elseif (
                                $lessonStatus ===
                                'cancelled'
                            ) {

                                echo
                                '<span class="badge cancelled">
                                    Cancelled
                                </span>';

                            }

                            else {

                                echo
                                '<span class="badge scheduled">
                                    Scheduled
                                </span>';

                            }

                            ?>

                        </td>


                        <!-- UPDATE -->

                        <td>


                            <form
                                method="POST"
                                class="schedule-form"
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?= (int)$row['id'] ?>"
                                >


                                <input
                                    type="date"
                                    name="lesson_date"
                                    value="<?= h(
                                        $row[
                                            'lesson_date'
                                        ]
                                        ?? ''
                                    ) ?>"
                                    required
                                >


                                <input
                                    type="time"
                                    name="lesson_time"
                                    value="<?= h(
                                        $row[
                                            'lesson_time'
                                        ]
                                        ?? ''
                                    ) ?>"
                                    required
                                >


                                <select
                                    name="lesson_status"
                                >


                                    <option
                                        value="Scheduled"
                                        <?= $lessonStatus === 'scheduled'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Scheduled

                                    </option>


                                    <option
                                        value="Completed"
                                        <?= $lessonStatus === 'completed'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Completed

                                    </option>


                                    <option
                                        value="Cancelled"
                                        <?= $lessonStatus === 'cancelled'
                                            ? 'selected'
                                            : '' ?>
                                    >

                                        Cancelled

                                    </option>


                                </select>


                                <button
                                    type="submit"
                                    name="update_schedule"
                                >

                                    Save

                                </button>


                            </form>


                        </td>


                    </tr>


                <?php endforeach; ?>


            </table>


        <?php else: ?>


            <div class="no-data">

                <p>

                    You currently have no students
                    assigned to you.

                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
