<?php

require "../teacher_auth.php";
require "../config/db.php";


/*
==================================================
LOGGED-IN TEACHER
==================================================
*/

$teacher_id   = $_SESSION['teacher_id'] ?? '';
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

$message = "";
$message_type = "";


/*
==================================================
UPDATE LESSON SCHEDULE
==================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST['update_schedule'])
) {

    $booking_id = intval($_POST['booking_id'] ?? 0);

    $lesson_date = trim(
        $_POST['lesson_date'] ?? ''
    );

    $lesson_time = trim(
        $_POST['lesson_time'] ?? ''
    );

    $lesson_status = trim(
        $_POST['lesson_status'] ?? 'Scheduled'
    );


    /*
    ==============================================
    SECURITY CHECK

    Make sure the booking belongs to this teacher.
    ==============================================
    */

    try {

        $check = $pdo->prepare("
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

        $checkResult = $check->fetch();


        if (!$checkResult) {

            $message =
                "You are not authorised to modify this booking.";

            $message_type = "error";

        } else {


            /*
            ==========================================
            UPDATE LESSON
            ==========================================
            */

            $update = $pdo->prepare("
                UPDATE bookings
                SET
                    lesson_date = ?,
                    lesson_time = ?,
                    lesson_status = ?
                WHERE id = ?
                AND teacher_id = ?
            ");


            $success = $update->execute([
                $lesson_date,
                $lesson_time,
                $lesson_status,
                $booking_id,
                $teacher_id
            ]);


            if ($success) {

                $message =
                    "Lesson schedule updated successfully.";

                $message_type = "success";

            } else {

                $message =
                    "Unable to update the lesson schedule.";

                $message_type = "error";

            }

        }

    } catch (PDOException $e) {

        $message =
            "Database error: " . $e->getMessage();

        $message_type = "error";

    }

}


/*
==================================================
GET TODAY'S LESSONS
==================================================
*/

try {

    $todayStmt = $pdo->prepare("
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

    $todayLessons = $todayStmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load today's lessons: "
        . $e->getMessage()
    );

}


/*
==================================================
GET ALL SCHEDULED LESSONS
==================================================
*/

try {

    $scheduleStmt = $pdo->prepare("
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
                WHEN lesson_date IS NULL THEN 1
                ELSE 0
            END,
            lesson_date ASC,
            lesson_time ASC
    ");

    $scheduleStmt->execute([
        $teacher_id
    ]);

    $schedules = $scheduleStmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load lesson schedule: "
        . $e->getMessage()
    );

}


/*
==================================================
COUNT LESSONS
==================================================
*/

$totalLessons = count($schedules);

$todayLessonCount = count($todayLessons);

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

/* ==========================================
   GENERAL
========================================== */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

    color: #333;

}


/* ==========================================
   SIDEBAR
========================================== */

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


/* ==========================================
   MAIN
========================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* ==========================================
   TOP BAR
========================================== */

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


/* ==========================================
   HEADER
========================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.page-header h2 {

    margin: 0 0 8px;

    color: #003366;

}


.page-header p {

    margin: 0;

    color: #666;

}


/* ==========================================
   STAT CARDS
========================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(200px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

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


/* ==========================================
   MESSAGE
========================================== */

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


/* ==========================================
   TODAY
========================================== */

.today {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.today h3 {

    color: #003366;

    margin-top: 0;

}


/* ==========================================
   TABLE
========================================== */

.table-container {

    background: white;

    padding: 20px;

    border-radius: 12px;

    overflow-x: auto;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

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

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* ==========================================
   BADGES
========================================== */

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


/* ==========================================
   FORM
========================================== */

.schedule-form {

    display: flex;

    flex-direction: column;

    gap: 8px;

}


.schedule-form input,
.schedule-form select {

    padding: 8px;

    border: 1px solid #ccc;

    border-radius: 5px;

}


.schedule-form button {

    padding: 9px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

}


.schedule-form button:hover {

    background: #0055a5;

}


/* ==========================================
   NO DATA
========================================== */

.no-data {

    padding: 35px;

    text-align: center;

    color: #777;

}


/* ==========================================
   MOBILE
========================================== */

@media(max-width: 800px) {

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


<!-- ==========================================
     SIDEBAR
========================================== -->

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


        <a href="schedule.php"
           class="active">

            📅 My Schedule

        </a>


        <a href="profile.php">

            👤 My Profile

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>

    </div>

</div>


<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h2>

            My Schedule

        </h2>


        <div class="teacher">

            Welcome,

            <strong>

                <?php

                echo htmlspecialchars(
                    $teacher_name
                );

                ?>

            </strong>

        </div>

    </div>



    <!-- HEADER -->

    <div class="page-header">

        <h2>

            Lesson Schedule

        </h2>

        <p>

            View and manage the lessons assigned
            to you.

        </p>

    </div>



    <!-- MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="message
            <?php echo htmlspecialchars($message_type); ?>">

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php endif; ?>



    <!-- STATISTICS -->

    <div class="stats">

        <div class="stat-card">

            <h3>

                <?php

                echo $totalLessons;

                ?>

            </h3>

            <p>

                Total Assigned Lessons

            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $todayLessonCount;

                ?>

            </h3>

            <p>

                Lessons Today

            </p>

        </div>

    </div>



    <!-- TODAY'S LESSONS -->

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

                            echo date(
                                "h:i A",
                                strtotime(
                                    $today['lesson_time']
                                )
                            );

                        } else {

                            echo "Time not set";

                        }

                        ?>

                    </td>


                    <td>

                        <strong>

                            <?php

                            echo htmlspecialchars(
                                $today['student_name']
                            );

                            ?>

                        </strong>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $today['subjects']
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            $today['curriculum']
                        );

                        ?>

                    </td>


                    <td>

                        <?php

                        $status = strtolower(
                            $today['lesson_status']
                            ?? 'scheduled'
                        );


                        if (
                            $status === "completed"
                        ) {

                            echo '
                            <span class="badge completed">
                                Completed
                            </span>';

                        } elseif (
                            $status === "cancelled"
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

            <div class="no-data">

                <p>

                    You have no lessons scheduled
                    for today.

                </p>

            </div>

        <?php endif; ?>

    </div>



    <!-- ALL LESSONS -->

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

            /*
             * Determine lesson status once so it
             * can be used by the display and form.
             */

            $lessonStatus =
                strtolower(
                    $row['lesson_status']
                    ?? 'scheduled'
                );

            ?>


            <tr>


                <!-- STUDENT -->

                <td>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $row['student_name']
                        );

                        ?>

                    </strong>

                    <br>

                    <small>

                        <?php

                        echo htmlspecialchars(
                            $row['booking_reference']
                            ?? ''
                        );

                        ?>

                    </small>

                </td>



                <!-- SUBJECT -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $row['subjects']
                    );

                    ?>

                </td>



                <!-- CURRICULUM -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $row['curriculum']
                    );

                    ?>

                </td>



                <!-- CLASS -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $row['class_year']
                        ?? ''
                    );

                    ?>

                </td>



                <!-- DATE -->

                <td>

                    <?php

                    if (
                        !empty(
                            $row['lesson_date']
                        )
                    ) {

                        $date =
                            strtotime(
                                $row['lesson_date']
                            );

                        if ($date !== false) {

                            echo date(
                                "d M Y",
                                $date
                            );

                        } else {

                            echo htmlspecialchars(
                                $row['lesson_date']
                            );

                        }

                    } else {

                        echo "<span style='color:#999'>
                                Not scheduled
                              </span>";

                    }

                    ?>

                </td>



                <!-- TIME -->

                <td>

                    <?php

                    if (
                        !empty(
                            $row['lesson_time']
                        )
                    ) {

                        $time =
                            strtotime(
                                $row['lesson_time']
                            );

                        if ($time !== false) {

                            echo date(
                                "h:i A",
                                $time
                            );

                        } else {

                            echo htmlspecialchars(
                                $row['lesson_time']
                            );

                        }

                    } else {

                        echo "<span style='color:#999'>
                                Not set
                              </span>";

                    }

                    ?>

                </td>



                <!-- PAYMENT -->

                <td>

                    <?php

                    $payment = strtolower(
                        trim(
                            $row['payment_status']
                            ?? ''
                        )
                    );


                    if (
                        $payment === "paid" ||
                        $payment === "success"
                    ) {

                        echo '
                        <span class="badge paid">
                            PAID
                        </span>';

                    } else {

                        echo '
                        <span class="badge pending">
                            ' .
                            htmlspecialchars(
                                strtoupper(
                                    $row['payment_status']
                                    ?? 'PENDING'
                                )
                            )
                            . '
                        </span>';

                    }

                    ?>

                </td>



                <!-- STATUS -->

                <td>

                    <?php

                    if (
                        $lessonStatus ===
                        "completed"
                    ) {

                        echo '
                        <span class="badge completed">
                            Completed
                        </span>';

                    } elseif (
                        $lessonStatus ===
                        "cancelled"
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



                <!-- UPDATE FORM -->

                <td>

                    <form
                        method="POST"
                        class="schedule-form"
                    >

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php
                                echo (int)$row['id'];
                            ?>"
                        >


                        <input
                            type="date"
                            name="lesson_date"
                            value="<?php
                                echo htmlspecialchars(
                                    $row['lesson_date']
                                    ?? ''
                                );
                            ?>"
                            required
                        >


                        <input
                            type="time"
                            name="lesson_time"
                            value="<?php
                                echo htmlspecialchars(
                                    $row['lesson_time']
                                    ?? ''
                                );
                            ?>"
                            required
                        >


                        <select
                            name="lesson_status"
                        >

                            <option
                                value="Scheduled"
                                <?php
                                if (
                                    $lessonStatus ===
                                    "scheduled"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Scheduled
                            </option>


                            <option
                                value="Completed"
                                <?php
                                if (
                                    $lessonStatus ===
                                    "completed"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Completed
                            </option>


                            <option
                                value="Cancelled"
                                <?php
                                if (
                                    $lessonStatus ===
                                    "cancelled"
                                ) {
                                    echo "selected";
                                }
                                ?>
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
