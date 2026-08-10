<?php

require "../teacher_auth.php";
require "../config/db.php";


/* =========================================================
   LOGGED-IN TEACHER
========================================================= */

$teacher_id = $_SESSION['teacher_id'] ?? '';

$teacher_name =
    $_SESSION['teacher_name'] ?? 'Teacher';


/*
=========================================================
   MAKE SURE TEACHER IS LOGGED IN
=========================================================
*/

if (empty($teacher_id)) {

    header("Location: login.php");
    exit;

}


/* =========================================================
   GET STUDENTS ASSIGNED TO THIS TEACHER
========================================================= */

try {

    $stmt = $pdo->prepare("

        SELECT

            id,

            booking_reference,

            student_name,

            dob,

            phone,

            email,

            curriculum,

            class_year,

            subjects,

            amount,

            payment_status,

            paystack_reference,

            teacher_id,

            teacher_name,

            assignment_status,

            lesson_date,

            lesson_time,

            lesson_status

        FROM bookings

        WHERE teacher_id = ?

        ORDER BY id DESC

    ");


    $stmt->execute([
        $teacher_id
    ]);


    $students = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    die(

        "Unable to load students: "

        . htmlspecialchars(
            $e->getMessage()
        )

    );

}


/* =========================================================
   TOTAL STUDENTS
========================================================= */

$total_students = count($students);


/* =========================================================
   COUNT PAID STUDENTS
========================================================= */

$paid_students = 0;

foreach ($students as $student) {

    $status = strtolower(
        trim(
            $student['payment_status'] ?? ''
        )
    );


    if (
        $status === 'paid'
        ||
        $status === 'success'
        ||
        $status === 'successful'
    ) {

        $paid_students++;

    }

}


/* =========================================================
   COUNT SCHEDULED LESSONS
========================================================= */

$scheduled_lessons = 0;

foreach ($students as $student) {

    if (
        !empty(
            $student['lesson_date']
        )
    ) {

        $scheduled_lessons++;

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

My Students |
NISEL ONLINE EDUCATION

</title>


<style>


/* =====================================================
   GENERAL
===================================================== */

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
   TOP BAR
===================================================== */

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


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.page-header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.page-header p {

    margin: 0;

    color: #666;

}


/* =====================================================
   STATISTICS
===================================================== */

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


/* =====================================================
   STUDENT TABLE
===================================================== */

.students-container {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.students-container h2 {

    margin-top: 0;

    color: #003366;

}


.table-wrapper {

    overflow-x: auto;

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

    white-space: nowrap;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* =====================================================
   STUDENT NAME
===================================================== */

.student-name {

    font-weight: bold;

    color: #003366;

}


.student-email {

    font-size: 12px;

    color: #777;

    margin-top: 4px;

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


.paid {

    background: #d4edda;

    color: #155724;

}


.pending {

    background: #fff3cd;

    color: #856404;

}


.assigned {

    background: #d1ecf1;

    color: #0c5460;

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


.not-scheduled {

    background: #e2e3e5;

    color: #383d41;

}


/* =====================================================
   VIEW BUTTON
===================================================== */

.view-button {

    display: inline-block;

    padding: 8px 14px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 13px;

}


.view-button:hover {

    background: #0055a5;

}


/* =====================================================
   NO STUDENTS
===================================================== */

.no-data {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}


.no-data-icon {

    font-size: 50px;

    margin-bottom: 15px;

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


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a
            href="students.php"
            class="active"
        >

            👨‍🎓 My Students

        </a>


        <a href="schedule.php">

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



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <h2>

            My Students

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



    <!-- PAGE HEADER -->

    <div class="page-header">


        <h1>

            My Assigned Students

        </h1>


        <p>

            View students assigned to you by
            the NISEL ONLINE EDUCATION administrator.

        </p>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?php

                echo $total_students;

                ?>

            </h3>

            <p>

                Total Students

            </p>

        </div>



        <div class="stat-card">

            <h3>

                <?php

                echo $paid_students;

                ?>

            </h3>

            <p>

                Paid Bookings

            </p>

        </div>



        <div class="stat-card">

            <h3>

                <?php

                echo $scheduled_lessons;

                ?>

            </h3>

            <p>

                Scheduled Lessons

            </p>

        </div>


    </div>



    <!-- =================================================
         STUDENTS
    ================================================= -->

    <div class="students-container">


        <h2>

            👨‍🎓 Student List

        </h2>


        <?php if ($total_students > 0): ?>


        <div class="table-wrapper">


            <table>


                <thead>


                    <tr>


                        <th>

                            Student

                        </th>


                        <th>

                            Phone

                        </th>


                        <th>

                            Curriculum

                        </th>


                        <th>

                            Class / Year

                        </th>


                        <th>

                            Subject(s)

                        </th>


                        <th>

                            Payment

                        </th>


                        <th>

                            Lesson Date

                        </th>


                        <th>

                            Lesson Status

                        </th>


                        <th>

                            Action

                        </th>


                    </tr>


                </thead>


                <tbody>


                <?php foreach (
                    $students
                    as $student
                ): ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>


                            <div
                                class="student-name"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $student['student_name']
                                );

                                ?>

                            </div>


                            <div
                                class="student-email"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $student['email']
                                );

                                ?>

                            </div>


                        </td>



                        <!-- PHONE -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $student['phone']
                                ?? 'N/A'
                            );

                            ?>

                        </td>



                        <!-- CURRICULUM -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $student['curriculum']
                                ?? 'N/A'
                            );

                            ?>

                        </td>



                        <!-- CLASS -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $student['class_year']
                                ?? 'N/A'
                            );

                            ?>

                        </td>



                        <!-- SUBJECTS -->

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $student['subjects']
                                ?? 'N/A'
                            );

                            ?>

                        </td>



                        <!-- PAYMENT -->

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

                                ||

                                $payment === 'successful'

                            ) {

                                echo '

                                    <span
                                        class="badge paid"
                                    >

                                        PAID

                                    </span>

                                ';

                            } else {

                                echo '

                                    <span
                                        class="badge pending"
                                    >

                                        ' .

                                        htmlspecialchars(
                                            strtoupper(
                                                $student[
                                                    'payment_status'
                                                ] ?? 'PENDING'
                                            )
                                        )

                                        . '

                                    </span>

                                ';

                            }

                            ?>


                        </td>



                        <!-- LESSON DATE -->

                        <td>


                            <?php

                            if (

                                !empty(
                                    $student[
                                        'lesson_date'
                                    ]
                                )

                            ) {


                                $date =
                                    strtotime(
                                        $student[
                                            'lesson_date'
                                        ]
                                    );


                                if (
                                    $date !== false
                                ) {

                                    echo htmlspecialchars(
                                        date(
                                            "d M Y",
                                            $date
                                        )
                                    );

                                } else {

                                    echo htmlspecialchars(
                                        $student[
                                            'lesson_date'
                                        ]
                                    );

                                }


                            } else {

                                echo '

                                    <span
                                        class="badge not-scheduled"
                                    >

                                        Not Scheduled

                                    </span>

                                ';

                            }

                            ?>


                        </td>



                        <!-- LESSON STATUS -->

                        <td>


                            <?php

                            $lesson_status =
                                strtolower(
                                    trim(
                                        $student[
                                            'lesson_status'
                                        ] ?? ''
                                    )
                                );


                            if (
                                $lesson_status ===
                                'completed'
                            ) {

                                echo '

                                    <span
                                        class="badge completed"
                                    >

                                        Completed

                                    </span>

                                ';

                            } elseif (
                                $lesson_status ===
                                'cancelled'
                            ) {

                                echo '

                                    <span
                                        class="badge cancelled"
                                    >

                                        Cancelled

                                    </span>

                                ';

                            } elseif (
                                !empty(
                                    $student[
                                        'lesson_date'
                                    ]
                                )
                            ) {

                                echo '

                                    <span
                                        class="badge scheduled"
                                    >

                                        Scheduled

                                    </span>

                                ';

                            } else {

                                echo '

                                    <span
                                        class="badge not-scheduled"
                                    >

                                        Not Scheduled

                                    </span>

                                ';

                            }

                            ?>


                        </td>



                        <!-- ACTION -->

                        <td>


                            <a
                                href="student_details.php?id=<?php
                                    echo (int)
                                        $student['id'];
                                ?>"
                                class="view-button"
                            >

                                View Details

                            </a>


                        </td>


                    </tr>


                <?php endforeach; ?>


                </tbody>


            </table>


        </div>


        <?php else: ?>


            <div class="no-data">


                <div class="no-data-icon">

                    👨‍🎓

                </div>


                <h3>

                    No Students Assigned

                </h3>


                <p>

                    You currently have no students
                    assigned to you.

                </p>


                <p>

                    Students will appear here after
                    the NISEL administrator assigns
                    them to you.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
