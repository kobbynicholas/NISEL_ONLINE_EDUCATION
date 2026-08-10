<?php

require "../teacher_auth.php";
require "../config/db.php";


/*
==================================================
GET LOGGED-IN TEACHER
==================================================
*/

$teacher_id = $_SESSION['teacher_id'] ?? '';
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';


/*
==================================================
CHECK STUDENT ID
==================================================
*/

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: students.php");
    exit();

}

$student_id = intval($_GET['id']);


/*
==================================================
GET STUDENT DETAILS
==================================================

The query checks BOTH:

    booking ID
    teacher_id

This prevents a teacher from viewing another
teacher's students by changing the URL.
==================================================
*/

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
            assignment_status

        FROM bookings

        WHERE id = ?
        AND teacher_id = ?

        LIMIT 1

    ");


    $stmt->execute([
        $student_id,
        $teacher_id
    ]);


    /*
    ==============================================
    GET STUDENT
    ==============================================
    */

    $student = $stmt->fetch(PDO::FETCH_ASSOC);


    /*
    ==============================================
    CHECK IF STUDENT EXISTS
    ==============================================
    */

    if (!$student) {

        die("

            <div style='
                font-family:Arial;
                text-align:center;
                padding:60px;
            '>

                <h2 style='color:#003366;'>
                    Student Not Found
                </h2>

                <p>
                    This student does not exist or has not
                    been assigned to you.
                </p>

                <a
                    href='students.php'
                    style='
                        display:inline-block;
                        margin-top:20px;
                        padding:12px 20px;
                        background:#003366;
                        color:white;
                        text-decoration:none;
                        border-radius:6px;
                    '
                >

                    Back to My Students

                </a>

            </div>

        ");

    }


} catch (PDOException $e) {

    die(
        "Database error: "
        . htmlspecialchars($e->getMessage())
    );

}


/*
==================================================
FORMAT PAYMENT STATUS
==================================================
*/

$payment_status = strtolower(
    trim(
        $student['payment_status'] ?? ''
    )
);


if (
    $payment_status === "paid" ||
    $payment_status === "success"
) {

    $payment_class = "paid";

    $payment_text = "PAID";

} elseif (
    $payment_status === "pending"
) {

    $payment_class = "pending";

    $payment_text = "PENDING";

} else {

    $payment_class = "other";

    $payment_text =
        strtoupper(
            $student['payment_status']
            ?? 'UNKNOWN'
        );

}


/*
==================================================
DATE OF BIRTH
==================================================
*/

$dob = "N/A";


if (!empty($student['dob'])) {

    $timestamp = strtotime(
        $student['dob']
    );


    if ($timestamp !== false) {

        $dob = date(
            "d F Y",
            $timestamp
        );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>

Student Details |
NISEL ONLINE EDUCATION

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
   BACK BUTTON
========================================== */

.back-button {

    display: inline-block;

    margin-bottom: 20px;

    padding: 10px 18px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}


.back-button:hover {

    background: #0055a5;

}


/* ==========================================
   STUDENT HEADER
========================================== */

.student-header {

    background: white;

    padding: 30px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.student-header h1 {

    margin: 0 0 8px 0;

    color: #003366;

}


.reference {

    color: #777;

}


/* ==========================================
   INFORMATION GRID
========================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(250px, 1fr));

    gap: 20px;

    margin-bottom: 20px;

}


/* ==========================================
   INFORMATION CARD
========================================== */

.info-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.info-card h3 {

    margin-top: 0;

    margin-bottom: 20px;

    color: #003366;

    border-bottom: 1px solid #ddd;

    padding-bottom: 10px;

}


/* ==========================================
   DETAIL ROW
========================================== */

.detail-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 11px 0;

    border-bottom: 1px solid #eee;

}


.detail-row:last-child {

    border-bottom: none;

}


.detail-label {

    font-weight: bold;

    color: #666;

}


.detail-value {

    text-align: right;

    word-break: break-word;

}


/* ==========================================
   SUBJECTS
========================================== */

.subject-box {

    background: #f4f9ff;

    border-left: 5px solid #003366;

    padding: 18px;

    border-radius: 6px;

    line-height: 1.7;

}


/* ==========================================
   STATUS BADGES
========================================== */

.badge {

    display: inline-block;

    padding: 6px 12px;

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


.other {

    background: #e2e3e5;

    color: #383d41;

}


/* ==========================================
   ASSIGNMENT
========================================== */

.assignment {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

    margin-bottom: 20px;

}


.assignment h3 {

    color: #003366;

    margin-top: 0;

}


/* ==========================================
   NOTICE
========================================== */

.notice {

    background: #eef6ff;

    border-left: 5px solid #003366;

    padding: 15px;

    border-radius: 6px;

    color: #444;

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


    .detail-row {

        flex-direction: column;

        gap: 5px;

    }


    .detail-value {

        text-align: left;

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



<!-- ==========================================
     MAIN CONTENT
========================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h2>

            Student Details

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



    <!-- BACK -->

    <a
        href="students.php"
        class="back-button"
    >

        ← Back to My Students

    </a>



    <!-- STUDENT HEADER -->

    <div class="student-header">

        <h1>

            <?php

            echo htmlspecialchars(
                $student['student_name']
            );

            ?>

        </h1>


        <div class="reference">

            Booking Reference:

            <strong>

                <?php

                echo htmlspecialchars(
                    $student['booking_reference']
                );

                ?>

            </strong>

        </div>

    </div>



    <!-- INFORMATION -->

    <div class="info-grid">


        <!-- PERSONAL INFORMATION -->

        <div class="info-card">

            <h3>

                👤 Personal Information

            </h3>


            <div class="detail-row">

                <div class="detail-label">

                    Full Name

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['student_name']
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Date of Birth

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $dob
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Phone

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['phone']
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Email

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['email']
                    );

                    ?>

                </div>

            </div>

        </div>



        <!-- ACADEMIC INFORMATION -->

        <div class="info-card">

            <h3>

                🎓 Academic Information

            </h3>


            <div class="detail-row">

                <div class="detail-label">

                    Curriculum

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['curriculum']
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Class / Year / Grade

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['class_year']
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Subjects

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['subjects']
                    );

                    ?>

                </div>

            </div>


            <div class="detail-row">

                <div class="detail-label">

                    Assigned Teacher

                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $student['teacher_name']
                        ?? $teacher_name
                    );

                    ?>

                </div>

            </div>

        </div>


    </div>



    <!-- SUBJECTS -->

    <div
        class="info-card"
        style="margin-bottom:20px;"
    >

        <h3>

            📚 Booked Subject(s)

        </h3>


        <div class="subject-box">

            <?php

            echo nl2br(
                htmlspecialchars(
                    $student['subjects']
                )
            );

            ?>

        </div>

    </div>



    <!-- PAYMENT INFORMATION -->

    <div
        class="info-card"
        style="margin-bottom:20px;"
    >

        <h3>

            💳 Payment Information

        </h3>


        <div class="detail-row">

            <div class="detail-label">

                Payment Status

            </div>

            <div class="detail-value">

                <span
                    class="badge <?php
                        echo htmlspecialchars(
                            $payment_class
                        );
                    ?>"
                >

                    <?php

                    echo htmlspecialchars(
                        $payment_text
                    );

                    ?>

                </span>

            </div>

        </div>


        <div class="detail-row">

            <div class="detail-label">

                Amount

            </div>

            <div class="detail-value">

                GHS

                <?php

                echo number_format(
                    (float)(
                        $student['amount'] ?? 0
                    ),
                    2
                );

                ?>

            </div>

        </div>


        <div class="detail-row">

            <div class="detail-label">

                Paystack Reference

            </div>

            <div class="detail-value">

                <?php

                if (
                    !empty(
                        $student['paystack_reference']
                    )
                ) {

                    echo htmlspecialchars(
                        $student['paystack_reference']
                    );

                } else {

                    echo "N/A";

                }

                ?>

            </div>

        </div>

    </div>



    <!-- ASSIGNMENT INFORMATION -->

    <div class="assignment">

        <h3>

            👨‍🏫 Teacher Assignment

        </h3>


        <div class="detail-row">

            <div class="detail-label">

                Teacher

            </div>

            <div class="detail-value">

                <?php

                echo htmlspecialchars(
                    $student['teacher_name']
                    ?? $teacher_name
                );

                ?>

            </div>

        </div>


        <div class="detail-row">

            <div class="detail-label">

                Assignment Status

            </div>

            <div class="detail-value">

                <?php

                echo htmlspecialchars(
                    $student['assignment_status']
                    ?? 'Assigned'
                );

                ?>

            </div>

        </div>

    </div>



    <!-- NOTICE -->

    <div class="notice">

        <strong>Note:</strong>

        This student has been assigned to you by
        the NISEL administrator. If you have any
        questions regarding the student's booking,
        please contact the administrator.

    </div>


</div>


</body>

</html>
