<?php
session_start();
require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| PAYMENT SUCCESS PAGE
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK STUDENT LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");
    exit;

}


$student_id =
    (int) $_SESSION['student_id'];


/*
|--------------------------------------------------------------------------
| GET BOOKING REFERENCE
|--------------------------------------------------------------------------
*/

$bookingReference =
    trim(
        $_GET['booking']
        ??
        $_SESSION['paid_booking_reference']
        ??
        ''
    );


/*
|--------------------------------------------------------------------------
| GET BOOKING
|--------------------------------------------------------------------------
*/

$booking = null;


if (
    $bookingReference !== ''
) {

    try {

        $stmt = $pdo->prepare("
            SELECT

                id,
                booking_reference,
                student_id,
                student_name,
                email,
                curriculum,
                class_year,
                subjects,
                amount,
                payment_status,
                paystack_reference,
                lesson_date,
                lesson_time,
                lesson_status,
                teacher_name

            FROM bookings

            WHERE
                booking_reference = ?

            AND
                student_id = ?

            LIMIT 1
        ");

        $stmt->execute([

            $bookingReference,

            $student_id

        ]);

        $booking =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


    } catch (PDOException $e) {

        $booking = null;

    }

}


/*
|--------------------------------------------------------------------------
| IF BOOKING WAS NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$booking) {

    /*
    |--------------------------------------------------------------------------
    | Try using paid booking ID from session
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_SESSION['paid_booking_id']
        )
    ) {

        try {

            $stmt = $pdo->prepare("
                SELECT

                    id,
                    booking_reference,
                    student_id,
                    student_name,
                    email,
                    curriculum,
                    class_year,
                    subjects,
                    amount,
                    payment_status,
                    paystack_reference,
                    lesson_date,
                    lesson_time,
                    lesson_status,
                    teacher_name

                FROM bookings

                WHERE
                    id = ?

                AND
                    student_id = ?

                LIMIT 1
            ");

            $stmt->execute([

                (int)
                $_SESSION[
                    'paid_booking_id'
                ],

                $student_id

            ]);

            $booking =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


        } catch (PDOException $e) {

            $booking = null;

        }

    }

}


/*
|--------------------------------------------------------------------------
| STUDENT NAME
|--------------------------------------------------------------------------
*/

$studentName =
    $booking['student_name']
    ??
    $_SESSION['student_name']
    ??
    'Student';


/*
|--------------------------------------------------------------------------
| DISPLAY VALUES
|--------------------------------------------------------------------------
*/

$displayReference =
    $booking['booking_reference']
    ??
    $bookingReference
    ??
    'N/A';


$curriculum =
    $booking['curriculum']
    ??
    'N/A';


$subject =
    $booking['subjects']
    ??
    'N/A';


$classYear =
    $booking['class_year']
    ??
    'N/A';


$amount =
    (float)
    (
        $booking['amount']
        ??
        $_SESSION['paid_amount']
        ??
        0
    );


$paymentStatus =
    strtolower(
        trim(
            $booking['payment_status']
            ??
            'paid'
        )
    );


$paystackReference =
    $booking['paystack_reference']
    ??
    '';


$lessonDate =
    $booking['lesson_date']
    ??
    '';


$lessonTime =
    $booking['lesson_time']
    ??
    '';


$teacherName =
    $booking['teacher_name']
    ??
    '';


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

$formattedDate =
    'Not yet scheduled';


if (
    !empty($lessonDate)
) {

    $timestamp =
        strtotime(
            $lessonDate
        );

    if (
        $timestamp !== false
    ) {

        $formattedDate =
            date(
                'd M Y',
                $timestamp
            );

    }

}


/*
|--------------------------------------------------------------------------
| FORMAT TIME
|--------------------------------------------------------------------------
*/

$formattedTime =
    'Not yet scheduled';


if (
    !empty($lessonTime)
) {

    $timestamp =
        strtotime(
            $lessonTime
        );

    if (
        $timestamp !== false
    ) {

        $formattedTime =
            date(
                'h:i A',
                $timestamp
            );

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

    Payment Successful |
    NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {

    box-sizing:
        border-box;

}


body {

    margin:
        0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #eef3f8;

    color:
        #333;

}


/* =====================================================
   MAIN CONTAINER
===================================================== */

.page {

    min-height:
        100vh;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        30px;

}


/* =====================================================
   SUCCESS CARD
===================================================== */

.card {

    width:
        100%;

    max-width:
        720px;

    background:
        white;

    border-radius:
        18px;

    padding:
        40px;

    box-shadow:
        0 10px 35px
        rgba(
            0,
            0,
            0,
            .10
        );

}


/* =====================================================
   LOGO
===================================================== */

.logo {

    text-align:
        center;

    color:
        #003366;

    font-size:
        22px;

    font-weight:
        bold;

    line-height:
        1.4;

    margin-bottom:
        25px;

}


/* =====================================================
   SUCCESS ICON
===================================================== */

.success-icon {

    width:
        85px;

    height:
        85px;

    margin:
        0 auto 20px;

    border-radius:
        50%;

    background:
        #e7f7ed;

    color:
        #16803d;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        45px;

    font-weight:
        bold;

}


/* =====================================================
   TITLE
===================================================== */

h1 {

    text-align:
        center;

    margin:
        0 0 10px;

    color:
        #003366;

    font-size:
        30px;

}


.subtitle {

    text-align:
        center;

    color:
        #666;

    line-height:
        1.6;

    margin:
        0 auto 30px;

    max-width:
        550px;

}


/* =====================================================
   PAYMENT BADGE
===================================================== */

.paid-badge {

    text-align:
        center;

    margin-bottom:
        25px;

}


.paid-badge span {

    display:
        inline-block;

    padding:
        8px 18px;

    border-radius:
        30px;

    background:
        #e7f7ed;

    color:
        #16803d;

    font-size:
        13px;

    font-weight:
        bold;

}


/* =====================================================
   AMOUNT
===================================================== */

.amount-box {

    text-align:
        center;

    background:
        #f4f8fc;

    border-radius:
        12px;

    padding:
        22px;

    margin-bottom:
        25px;

}


.amount-label {

    font-size:
        13px;

    color:
        #777;

}


.amount {

    color:
        #003366;

    font-size:
        36px;

    font-weight:
        bold;

    margin-top:
        5px;

}


/* =====================================================
   DETAILS
===================================================== */

.details {

    border:
        1px solid
        #e2e7ed;

    border-radius:
        12px;

    overflow:
        hidden;

    margin-bottom:
        25px;

}


.detail-row {

    display:
        flex;

    justify-content:
        space-between;

    gap:
        20px;

    padding:
        15px 18px;

    border-bottom:
        1px solid
        #edf0f3;

}


.detail-row:last-child {

    border-bottom:
        none;

}


.detail-label {

    color:
        #777;

    font-size:
        14px;

}


.detail-value {

    color:
        #333;

    font-size:
        14px;

    font-weight:
        600;

    text-align:
        right;

}


/* =====================================================
   PACKAGE INFORMATION
===================================================== */

.package {

    background:
        #f8fafc;

    border-radius:
        12px;

    padding:
        20px;

    margin-bottom:
        25px;

}


.package h3 {

    margin:
        0 0 12px;

    color:
        #003366;

    font-size:
        17px;

}


.package ul {

    margin:
        0;

    padding-left:
        20px;

    color:
        #555;

    line-height:
        1.9;

}


/* =====================================================
   NOTICE
===================================================== */

.notice {

    background:
        #fff8e1;

    border-left:
        4px solid
        #f0ad4e;

    padding:
        15px;

    border-radius:
        7px;

    margin-bottom:
        25px;

    color:
        #66512c;

    font-size:
        13px;

    line-height:
        1.6;

}


/* =====================================================
   BUTTONS
===================================================== */

.buttons {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    gap:
        12px;

}


.button {

    display:
        block;

    text-align:
        center;

    padding:
        14px;

    border-radius:
        8px;

    text-decoration:
        none;

    font-weight:
        bold;

    font-size:
        14px;

    transition:
        .2s;

}


.button-primary {

    background:
        #003366;

    color:
        white;

}


.button-primary:hover {

    background:
        #0055a5;

}


.button-secondary {

    background:
        #e9eef3;

    color:
        #333;

}


.button-secondary:hover {

    background:
        #dce4eb;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align:
        center;

    margin-top:
        25px;

    color:
        #999;

    font-size:
        12px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(
    max-width: 600px
) {

    .page {

        padding:
            15px;

    }


    .card {

        padding:
            25px 18px;

    }


    h1 {

        font-size:
            25px;

    }


    .amount {

        font-size:
            31px;

    }


    .detail-row {

        flex-direction:
            column;

        gap:
            5px;

    }


    .detail-value {

        text-align:
            left;

    }


    .buttons {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<div class="page">


    <div class="card">


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="logo">

            NISEL<br>

            ONLINE EDUCATION

        </div>



        <!-- =================================================
             SUCCESS ICON
        ================================================== -->

        <div class="success-icon">

            ✓

        </div>



        <!-- =================================================
             TITLE
        ================================================== -->

        <h1>

            Payment Successful!

        </h1>


        <p class="subtitle">

            Congratulations,
            <strong>
                <?= htmlspecialchars(
                    $studentName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            >!

            Your subject booking has been
            successfully confirmed.

        </p>



        <!-- =================================================
             STATUS
        ================================================== -->

        <div class="paid-badge">

            <span>

                ✓ PAYMENT CONFIRMED

            </span>

        </div>



        <!-- =================================================
             AMOUNT
        ================================================== -->

        <div class="amount-box">

            <div class="amount-label">

                Amount Paid

            </div>


            <div class="amount">

                GHS
                <?= number_format(
                    $amount,
                    2
                ) ?>

            </div>

        </div>



        <!-- =================================================
             BOOKING DETAILS
        ================================================== -->

        <div class="details">


            <div class="detail-row">

                <span class="detail-label">

                    Booking Reference

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $displayReference,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Curriculum

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $curriculum,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Subject

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $subject,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Class / Year

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $classYear,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Teacher

                </span>


                <span class="detail-value">

                    <?php

                    if (
                        !empty(
                            $teacherName
                        )
                    ) {

                        echo htmlspecialchars(
                            $teacherName,
                            ENT_QUOTES,
                            'UTF-8'
                        );

                    } else {

                        echo "Not yet assigned";

                    }

                    ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Lesson Date

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $formattedDate,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>



            <div class="detail-row">

                <span class="detail-label">

                    Lesson Time

                </span>


                <span class="detail-value">

                    <?= htmlspecialchars(
                        $formattedTime,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>


        </div>



        <!-- =================================================
             LESSON PACKAGE
        ================================================== -->

        <div class="package">

            <h3>

                📚 Your Lesson Package

            </h3>


            <ul>

                <li>
                    <strong>
                        2 lessons per week
                    </strong>
                </li>

                <li>
                    <strong>
                        8 lessons per month
                    </strong>
                </li>

                <li>
                    Your teacher will be assigned
                    by NISEL administration.
                </li>

                <li>
                    Your lesson schedule will appear
                    in <strong>My Schedule</strong>.
                </li>

                <li>
                    Your Zoom meeting link will appear
                    when your teacher has added it.
                </li>

            </ul>

        </div>



        <!-- =================================================
             NOTICE
        ================================================== -->

        <div class="notice">

            <strong>
                What's next?
            </strong>

            Your payment has been recorded.

            The administrator will assign a teacher
            to your booking, after which your lesson
            dates and times will appear on your
            schedule.

            You can return to your dashboard or
            check your schedule below.

        </div>



        <!-- =================================================
             BUTTONS
        ================================================== -->

        <div class="buttons">


            <a
                href="dashboard.php"
                class="button button-primary"
            >

                🏠 Student Dashboard

            </a>


            <a
                href="schedule.php"
                class="button button-secondary"
            >

                📅 View My Schedule

            </a>


        </div>



        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="footer">

            NISEL ONLINE EDUCATION

            <br>

            Online learning made simple.

        </div>


    </div>


</div>


</body>

</html>
