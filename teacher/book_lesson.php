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


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_id =
    $_SESSION['student_id'] ?? '';

$student_name =
    $_SESSION['student_name'] ?? '';

$student_email =
    $_SESSION['student_email'] ?? '';

$student_phone =
    $_SESSION['student_phone'] ?? '';


/* =========================================================
   VALIDATE STUDENT SESSION
========================================================= */

if (
    empty($student_name) ||
    empty($student_email)
) {

    die(
        "Your student account information could not be loaded. "
        . "Please log out and log in again."
    );

}


/* =========================================================
   LESSON PACKAGE
========================================================= */

$lesson_amount = 1000;


/*
 * Every subject booking contains:
 *
 * 2 lessons per week
 * 8 lessons per month
 *
 * Therefore one booking = one subject = 8 lessons.
 */


/* =========================================================
   AVAILABLE CURRICULA
========================================================= */

$curricula = [

    "Cambridge",

    "IB",

    "GES"

];


/* =========================================================
   AVAILABLE SUBJECTS
========================================================= */

$subjects = [

    "Mathematics",

    "English",

    "Physics",

    "Chemistry",

    "Biology",

    "Computer Science",

    "Additional Mathematics",

    "Economics",

    "Accounting",

    "Business Studies",

    "Geography",

    "History",

    "Global Perspectives"

];


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   PROCESS BOOKING
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["create_booking"])
) {


    /* =====================================================
       GET FORM DATA
    ===================================================== */

    $curriculum =
        trim(
            $_POST["curriculum"] ?? ''
        );


    $class_year =
        trim(
            $_POST["class_year"] ?? ''
        );


    $subject =
        trim(
            $_POST["subject"] ?? ''
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $curriculum === '' ||
        $class_year === '' ||
        $subject === ''
    ) {

        $message =
            "Please complete all required fields.";

        $message_type =
            "error";

    }


    elseif (
        !in_array(
            $curriculum,
            $curricula,
            true
        )
    ) {

        $message =
            "Invalid curriculum selected.";

        $message_type =
            "error";

    }


    elseif (
        !in_array(
            $subject,
            $subjects,
            true
        )
    ) {

        $message =
            "Invalid subject selected.";

        $message_type =
            "error";

    }


    else {


        /* =================================================
           CHECK WHETHER STUDENT ALREADY HAS THIS SUBJECT
           BOOKED
        ================================================= */

        try {

            $check = $pdo->prepare("

                SELECT id

                FROM bookings

                WHERE email = ?

                AND curriculum = ?

                AND class_year = ?

                AND subjects = ?

                AND payment_status IN (
                    'Pending',
                    'Paid',
                    'success',
                    'Success'
                )

                LIMIT 1

            ");


            $check->execute([

                $student_email,

                $curriculum,

                $class_year,

                $subject

            ]);


            $existing =
                $check->fetch(
                    PDO::FETCH_ASSOC
                );


        } catch (PDOException $e) {

            $existing = false;

            $message =
                "Unable to check your existing bookings.";

            $message_type =
                "error";

        }


        /* =================================================
           PREVENT DUPLICATE SUBJECT BOOKING
        ================================================= */

        if ($existing) {

            $message =

                "You already have an active "
                . htmlspecialchars($subject)
                . " booking for "
                . htmlspecialchars($class_year)
                . ". You can book another subject separately.";

            $message_type =
                "error";

        }


        else {


            /* =============================================
               GENERATE BOOKING REFERENCE
            ============================================= */

            $booking_reference =

                "NISEL-"
                .
                date("Ymd")
                .
                "-"
                .
                strtoupper(
                    substr(
                        uniqid(),
                        -6
                    )
                );


            /* =============================================
               CREATE BOOKING
            ============================================= */

            try {


                /*
                 * The booking is created as Pending.
                 *
                 * Payment will be handled after this
                 * booking has been created.
                 */

                $stmt = $pdo->prepare("

                    INSERT INTO bookings (

                        booking_reference,

                        student_name,

                        email,

                        phone,

                        curriculum,

                        class_year,

                        subjects,

                        amount,

                        payment_status,

                        assignment_status

                    )

                    VALUES (

                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Pending',
                        'Pending'

                    )

                ");


                $stmt->execute([

                    $booking_reference,

                    $student_name,

                    $student_email,

                    $student_phone,

                    $curriculum,

                    $class_year,

                    $subject,

                    $lesson_amount

                ]);


                $booking_id =
                    $pdo->lastInsertId();


                /*
                 * Store booking information temporarily
                 * in session so payment.php can use it.
                 */

                $_SESSION[
                    'pending_booking'
                ] = [

                    'id' =>
                        $booking_id,

                    'booking_reference' =>
                        $booking_reference,

                    'student_name' =>
                        $student_name,

                    'email' =>
                        $student_email,

                    'phone' =>
                        $student_phone,

                    'curriculum' =>
                        $curriculum,

                    'class_year' =>
                        $class_year,

                    'subject' =>
                        $subject,

                    'amount' =>
                        $lesson_amount

                ];


                /*
                 * Send the student to payment.php.
                 *
                 * We pass the booking reference so the
                 * payment page knows exactly which booking
                 * is being paid for.
                 */

                header(
                    "Location: ../payment.php?booking="
                    .
                    urlencode(
                        $booking_reference
                    )
                );

                exit;


            } catch (PDOException $e) {

                $message =

                    "Unable to create your booking. "
                    .
                    $e->getMessage();

                $message_type =
                    "error";

            }

        }

    }

}


/* =========================================================
   GET STUDENT'S ACTIVE BOOKINGS
========================================================= */

$my_bookings = [];


try {

    $stmt = $pdo->prepare("

        SELECT

            id,

            booking_reference,

            curriculum,

            class_year,

            subjects,

            amount,

            payment_status,

            teacher_name,

            assignment_status

        FROM bookings

        WHERE email = ?

        ORDER BY id DESC

        LIMIT 20

    ");


    $stmt->execute([
        $student_email
    ]);


    $my_bookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $my_bookings = [];

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

Book a Subject |
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


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.sidebar a:hover {

    background: #0055a5;

}


.sidebar a.active {

    background: #0055a5;

}


.logout {

    margin-top: 25px;

    background: #c82333;

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

    line-height: 1.6;

}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 15px;

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


/* =====================================================
   PACKAGE INFORMATION
===================================================== */

.package {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.package h2 {

    color: #003366;

    margin-top: 0;

}


.package-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

}


.package-item {

    background: #f4f8fc;

    padding: 18px;

    border-radius: 8px;

    text-align: center;

}


.package-item strong {

    display: block;

    font-size: 25px;

    color: #003366;

}


.package-item span {

    color: #666;

}


/* =====================================================
   BOOKING FORM
===================================================== */

.form-card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.form-card h2 {

    margin-top: 0;

    color: #003366;

}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

}


.form-group label {

    display: block;

    margin-bottom: 8px;

    font-weight: bold;

}


.form-group select,

.form-group input {

    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 15px;

}


.form-group select:focus,

.form-group input:focus {

    outline: none;

    border-color: #003366;

}


.full {

    grid-column: 1 / -1;

}


/* =====================================================
   SUBJECT NOTICE
===================================================== */

.subject-notice {

    background: #f4f9ff;

    border-left: 5px solid #003366;

    padding: 15px;

    margin-top: 20px;

    line-height: 1.6;

}


/* =====================================================
   PRICE
===================================================== */

.price-box {

    margin-top: 25px;

    padding: 20px;

    border-radius: 8px;

    background: #f7f7f7;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.price-label {

    color: #555;

}


.price {

    font-size: 28px;

    font-weight: bold;

    color: #003366;

}


/* =====================================================
   BUTTON
===================================================== */

.book-button {

    margin-top: 20px;

    padding: 14px 28px;

    border: none;

    background: #003366;

    color: white;

    border-radius: 6px;

    cursor: pointer;

    font-size: 16px;

    font-weight: bold;

}


.book-button:hover {

    background: #0055a5;

}


/* =====================================================
   BOOKINGS
===================================================== */

.bookings-card {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.bookings-card h2 {

    color: #003366;

    margin-top: 0;

}


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

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.pending {

    background: #fff3cd;

    color: #856404;

}


.paid {

    background: #d4edda;

    color: #155724;

}


.assigned {

    background: #cfe2ff;

    color: #084298;

}


.not-assigned {

    background: #f8d7da;

    color: #721c24;

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


    .form-grid {

        grid-template-columns: 1fr;

    }


    .full {

        grid-column: auto;

    }


    .price-box {

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


    <a href="dashboard.php">

        🏠 Dashboard

    </a>


    <a
        href="book_lesson.php"
        class="active"
    >

        📚 Book a Subject

    </a>


    <a href="bookings.php">

        📖 My Bookings

    </a>


    <a href="schedule.php">

        📅 My Schedule

    </a>


    <a href="payments.php">

        💳 Payments

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


</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- =================================================
         HEADER
    ================================================= -->

    <div class="header">


        <h1>

            📚 Book a Subject

        </h1>


        <p>

            Welcome,

            <strong>

                <?php

                echo htmlspecialchars(
                    $student_name
                );

                ?>

            </strong>.

            Choose one subject to create your
            monthly lesson package.

        </p>


    </div>



    <!-- =================================================
         MESSAGE
    ================================================= -->

    <?php if (
        $message !== ''
    ): ?>


        <div class="message
            <?php

            echo htmlspecialchars(
                $message_type
            );

            ?>"
        >

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =================================================
         PACKAGE INFORMATION
    ================================================= -->

    <div class="package">


        <h2>

            📦 NISEL Monthly Lesson Package

        </h2>


        <div class="package-grid">


            <div class="package-item">

                <strong>
                    2
                </strong>

                <span>
                    Lessons Per Week
                </span>

            </div>


            <div class="package-item">

                <strong>
                    8
                </strong>

                <span>
                    Lessons Per Month
                </span>

            </div>


            <div class="package-item">

                <strong>
                    1
                </strong>

                <span>
                    Subject Per Booking
                </span>

            </div>


            <div class="package-item">

                <strong>
                    GHS 1,000
                </strong>

                <span>
                    Monthly Package
                </span>

            </div>


        </div>


    </div>



    <!-- =================================================
         BOOKING FORM
    ================================================= -->

    <div class="form-card">


        <h2>

            📝 Create a New Subject Booking

        </h2>


        <p>

            Select <strong>ONE subject</strong>.
            If you want another subject, you can
            create another booking after completing
            this one.

        </p>


        <form
            method="POST"
            action=""
        >


            <div class="form-grid">


                <!-- =====================================
                     CURRICULUM
                ====================================== -->

                <div class="form-group">


                    <label>

                        Curriculum

                    </label>


                    <select
                        name="curriculum"
                        required
                    >


                        <option value="">

                            Select Curriculum

                        </option>


                        <?php foreach (
                            $curricula
                            as $curriculum
                        ): ?>


                            <option
                                value="<?php

                                    echo htmlspecialchars(
                                        $curriculum
                                    );

                                ?>"
                                <?php

                                if (
                                    (
                                        $_POST[
                                            'curriculum'
                                        ]
                                        ??
                                        ''
                                    )
                                    ===
                                    $curriculum
                                ) {

                                    echo "selected";

                                }

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $curriculum
                                );

                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>



                <!-- =====================================
                     CLASS / YEAR
                ====================================== -->

                <div class="form-group">


                    <label>

                        Class / Year

                    </label>


                    <input
                        type="text"
                        name="class_year"
                        placeholder="e.g. Year 10"
                        value="<?php

                            echo htmlspecialchars(
                                $_POST[
                                    'class_year'
                                ]
                                ??
                                ''
                            );

                        ?>"
                        required
                    >


                </div>



                <!-- =====================================
                     SUBJECT
                ====================================== -->

                <div class="form-group full">


                    <label>

                        Subject

                    </label>


                    <select
                        name="subject"
                        required
                    >


                        <option value="">

                            Select ONE Subject

                        </option>


                        <?php foreach (
                            $subjects
                            as $item
                        ): ?>


                            <option
                                value="<?php

                                    echo htmlspecialchars(
                                        $item
                                    );

                                ?>"
                                <?php

                                if (
                                    (
                                        $_POST[
                                            'subject'
                                        ]
                                        ??
                                        ''
                                    )
                                    ===
                                    $item
                                ) {

                                    echo "selected";

                                }

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $item
                                );

                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


            </div>



            <!-- =========================================
                 NOTICE
            ========================================== -->

            <div class="subject-notice">


                <strong>

                    Important:

                </strong>


                Each booking is for

                <strong>
                    ONE subject
                </strong>

                and includes

                <strong>
                    8 lessons per month
                </strong>

                at

                <strong>
                    2 lessons per week.
                </strong>


                <br><br>


                If you want to study another subject,
                you must create a separate booking
                for that subject.


            </div>



            <!-- =========================================
                 PRICE
            ========================================== -->

            <div class="price-box">


                <div>


                    <div class="price-label">

                        Monthly Package Fee

                    </div>


                    <small>

                        8 lessons / 2 lessons per week

                    </small>


                </div>


                <div class="price">

                    GHS

                    <?php

                    echo number_format(
                        $lesson_amount,
                        2
                    );

                    ?>

                </div>


            </div>



            <button
                type="submit"
                name="create_booking"
                class="book-button"
            >

                💳 Continue to Payment

            </button>


        </form>


    </div>



    <!-- =================================================
         MY BOOKINGS
    ================================================= -->

    <div class="bookings-card">


        <h2>

            📖 My Subject Bookings

        </h2>


        <?php if (
            count($my_bookings) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>


                        <th>

                            Booking Reference

                        </th>


                        <th>

                            Subject

                        </th>


                        <th>

                            Curriculum

                        </th>


                        <th>

                            Class

                        </th>


                        <th>

                            Package

                        </th>


                        <th>

                            Payment

                        </th>


                        <th>

                            Teacher

                        </th>


                        <th>

                            Assignment

                        </th>


                    </tr>


                    <?php foreach (
                        $my_bookings
                        as $booking
                    ): ?>


                        <tr>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $booking[
                                            'booking_reference'
                                        ]
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $booking[
                                        'subjects'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $booking[
                                        'curriculum'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $booking[
                                        'class_year'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                8 Lessons

                                <br>

                                <small>

                                    2 per week

                                </small>

                            </td>


                            <td>


                                <?php

                                $payment =
                                    strtolower(
                                        trim(
                                            $booking[
                                                'payment_status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );


                                if (
                                    $payment ===
                                    'paid'
                                    ||
                                    $payment ===
                                    'success'
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

                                        PENDING

                                    </span>

                                    ';

                                }

                                ?>


                            </td>


                            <td>


                                <?php

                                if (
                                    empty(
                                        $booking[
                                            'teacher_name'
                                        ]
                                    )
                                ) {

                                    echo "Not Assigned";

                                } else {

                                    echo htmlspecialchars(
                                        $booking[
                                            'teacher_name'
                                        ]
                                    );

                                }

                                ?>


                            </td>


                            <td>


                                <?php

                                $assignment =
                                    strtolower(
                                        trim(
                                            $booking[
                                                'assignment_status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );


                                if (
                                    $assignment ===
                                    'assigned'
                                ) {

                                    echo '

                                    <span
                                        class="badge assigned"
                                    >

                                        ASSIGNED

                                    </span>

                                    ';

                                } else {

                                    echo '

                                    <span
                                        class="
                                            badge
                                            not-assigned
                                        "
                                    >

                                        NOT ASSIGNED

                                    </span>

                                    ';

                                }

                                ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div
                style="
                    text-align:center;
                    padding:40px;
                    color:#777;
                "
            >

                <div
                    style="
                        font-size:45px;
                    "
                >

                    📚

                </div>


                <h3>

                    No Subject Bookings Yet

                </h3>


                <p>

                    Create your first subject booking
                    using the form above.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
