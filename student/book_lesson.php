<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT BOOK A LESSON
|--------------------------------------------------------------------------
*/

require "../config/db.php";


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


/*
|--------------------------------------------------------------------------
| GET LOGGED-IN STUDENT
|--------------------------------------------------------------------------
*/

$student_id =
    (int) $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? 'Student';

$student_email =
    $_SESSION['student_email']
    ?? '';


/*
|--------------------------------------------------------------------------
| CURRICULUM PRICES
|--------------------------------------------------------------------------
|
| Cambridge = GHS 1,000
| IB        = GHS 1,200
| GES       = GHS   800
|
| 2 lessons per week
| 8 lessons per month
|
|--------------------------------------------------------------------------
*/

$curriculumPrices = [

    'Cambridge' => 1000,

    'IB' => 1200,

    'SAT' => 800,
   
    'GES' => 800
];


/*
|--------------------------------------------------------------------------
| PAGE VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";

$message_type = "";

$formData = [

    'student_name' => '',
    'dob'          => '',
    'phone'        => '',
    'email'        => '',
    'curriculum'   => '',
    'class_year'   => '',
    'subjects'     => ''

];


/*
|--------------------------------------------------------------------------
| GET STUDENT INFORMATION
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            student_name,
            dob,
            phone,
            email
        FROM students
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $student_id
    ]);

    $student =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$student) {

        session_destroy();

        header("Location: login.php");

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | PRE-FILL STUDENT INFORMATION
    |--------------------------------------------------------------------------
    */

    $formData['student_name'] =
        $student['student_name']
        ?? $student_name;

    $formData['dob'] =
        $student['dob']
        ?? '';

    $formData['phone'] =
        $student['phone']
        ?? '';

    $formData['email'] =
        $student['email']
        ?? $student_email;


} catch (PDOException $e) {

    die(
        "Unable to load student information."
    );

}


/*
|--------------------------------------------------------------------------
| PROCESS BOOKING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $studentName =
        trim(
            $_POST['student_name']
            ?? ''
        );

    $dob =
        trim(
            $_POST['dob']
            ?? ''
        );

    $phone =
        trim(
            $_POST['phone']
            ?? ''
        );

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    $curriculum =
        trim(
            $_POST['curriculum']
            ?? ''
        );

    $classYear =
        trim(
            $_POST['class_year']
            ?? ''
        );

    $subjects =
        trim(
            $_POST['subjects']
            ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | PRESERVE FORM DATA
    |--------------------------------------------------------------------------
    */

    $formData = [

        'student_name' => $studentName,

        'dob' => $dob,

        'phone' => $phone,

        'email' => $email,

        'curriculum' => $curriculum,

        'class_year' => $classYear,

        'subjects' => $subjects

    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $studentName === '' ||
        $dob === '' ||
        $phone === '' ||
        $email === '' ||
        $curriculum === '' ||
        $classYear === '' ||
        $subjects === ''
    ) {

        $message =
            "Please complete all required fields.";

        $message_type =
            "error";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EMAIL
    |--------------------------------------------------------------------------
    */

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $message_type =
            "error";

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CURRICULUM
    |--------------------------------------------------------------------------
    */

    elseif (
        !array_key_exists(
            $curriculum,
            $curriculumPrices
        )
    ) {

        $message =
            "Invalid curriculum selected.";

        $message_type =
            "error";

    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE PRICE
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | We DO NOT accept amount from POST.
    |
    | The amount comes directly from
    | the curriculum price table above.
    |
    |--------------------------------------------------------------------------
    */

    else {

        $amount =
            (float)
            $curriculumPrices[
                $curriculum
            ];


        /*
        |--------------------------------------------------------------------------
        | CHECK FOR EXISTING UNPAID BOOKING
        |--------------------------------------------------------------------------
        |
        | Prevents the student from repeatedly creating
        | identical pending bookings.
        |
        |--------------------------------------------------------------------------
        */

        try {

            $check =
                $pdo->prepare("
                    SELECT
                        id,
                        booking_reference,
                        payment_status

                    FROM bookings

                    WHERE student_id = ?

                    AND curriculum = ?

                    AND class_year = ?

                    AND subjects = ?

                    AND (
                        payment_status IS NULL
                        OR payment_status = ''
                        OR LOWER(payment_status) = 'pending'
                    )

                    LIMIT 1
                ");

            $check->execute([

                $student_id,

                $curriculum,

                $classYear,

                $subjects

            ]);


            $existingBooking =
                $check->fetch(
                    PDO::FETCH_ASSOC
                );


            if ($existingBooking) {

                $message =
                    "You already have a pending booking for this subject. "
                    .
                    "Please complete the existing payment.";

                $message_type =
                    "error";

            }


        } catch (PDOException $e) {

            $message =
                "Unable to check existing bookings.";

            $message_type =
                "error";

        }


        /*
        |--------------------------------------------------------------------------
        | CREATE NEW BOOKING
        |--------------------------------------------------------------------------
        */

        if (
            $message_type !== "error"
        ) {

            try {


                /*
                |--------------------------------------------------------------------------
                | GENERATE UNIQUE BOOKING REFERENCE
                |--------------------------------------------------------------------------
                */

                $bookingReference = 'NISEL-' .
                    date('YmdHis') .
                    '-' .
                    strtoupper(
                        substr(
                            bin2hex(
                                random_bytes(4)
                            ),
                            0,
                            8
                        )
                    );


                /*
                |--------------------------------------------------------------------------
                | DEFAULT BOOKING STATUS
                |--------------------------------------------------------------------------
                */

                $paymentStatus =
                    'Pending';


                $lessonStatus =
                    'Scheduled';


                $assignmentStatus =
                    'Pending';


                /*
                |--------------------------------------------------------------------------
                | INSERT BOOKING
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare("
                        INSERT INTO bookings (

                            booking_reference,

                            student_id,

                            student_name,

                            dob,

                            phone,

                            email,

                            curriculum,

                            class_year,

                            subjects,

                            amount,

                            payment_status,

                            lesson_status,

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
                            ?,
                            ?,
                            ?,
                            ?,
                            ?

                        )
                    ");


                $stmt->execute([

                    $bookingReference,

                    $student_id,

                    $studentName,

                    $dob,

                    $phone,

                    $email,

                    $curriculum,

                    $classYear,

                    $subjects,

                    $amount,

                    $paymentStatus,

                    $lessonStatus,

                    $assignmentStatus

                ]);


                /*
                |--------------------------------------------------------------------------
                | GET NEW BOOKING ID
                |--------------------------------------------------------------------------
                */

                $bookingId =
                    $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | SAVE BOOKING ID IN SESSION
                |--------------------------------------------------------------------------
                |
                | This allows payment.php to know exactly
                | which booking the student is paying for.
                |
                |--------------------------------------------------------------------------
                */

                $_SESSION[
                    'pending_booking_id'
                ] =
                    $bookingId;


                $_SESSION[
                    'pending_booking_reference'
                ] =
                    $bookingReference;


                /*
                |--------------------------------------------------------------------------
                | REDIRECT TO PAYMENT
                |--------------------------------------------------------------------------
                */

                header(
                    "Location: payment.php?booking_id="
                    .
                    urlencode(
                        $bookingId
                    )
                );

                exit;


            } catch (PDOException $e) {

                $message =
                    "Unable to create your booking. "
                    .
                    "Please try again.";

                $message_type =
                    "error";

                /*
                |--------------------------------------------------------------------------
                | DEVELOPMENT ERROR
                |--------------------------------------------------------------------------
                |
                | If you are developing on XAMPP and want to see
                | the exact database error, temporarily uncomment:
                |
                | $message = $e->getMessage();
                |
                |--------------------------------------------------------------------------
                */

            }

        }

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
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background:
        #003366;

    color: white;

    padding:
        25px 15px;

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

    transition:
        0.2s;

}


.menu a:hover {

    background:
        #0055a5;

}


.menu a.active {

    background:
        #0055a5;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

    max-width:
        1400px;

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

    justify-content:
        space-between;

    align-items:
        center;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

}


.topbar h2 {

    margin: 0;

    color:
        #003366;

}


.student-name {

    color:
        #666;

}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    background:
        #003366;

    color: white;

    padding: 30px;

    border-radius: 14px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.12);

}


.page-header h1 {

    margin:
        0 0 10px;

    font-size:
        30px;

}


.page-header p {

    margin: 0;

    color:
        #e8f2ff;

    line-height:
        1.6;

}


/* =====================================================
   PRICING CARDS
===================================================== */

.pricing-grid {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap: 18px;

    margin-bottom: 25px;

}


.price-card {

    background:
        white;

    padding: 22px;

    border-radius: 12px;

    border:
        2px solid
        #e1e8ef;

    text-align: center;

    transition:
        .2s;

}


.price-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 8px 20px
        rgba(0,0,0,.08);

}


.price-card h3 {

    margin:
        0 0 10px;

    color:
        #003366;

}


.price-card .price {

    font-size:
        25px;

    font-weight:
        bold;

    color:
        #0055a5;

    margin-bottom:
        8px;

}


.price-card p {

    margin: 0;

    color:
        #777;

    font-size:
        13px;

}


/* =====================================================
   FORM CONTAINER
===================================================== */

.form-container {

    background:
        white;

    padding:
        30px;

    border-radius:
        14px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.08);

}


.form-container h2 {

    margin-top: 0;

    color:
        #003366;

}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding:
        15px 18px;

    border-radius:
        8px;

    margin-bottom:
        20px;

    font-weight:
        bold;

}


.message.error {

    background:
        #f8d7da;

    color:
        #721c24;

    border:
        1px solid
        #f5c6cb;

}


.message.success {

    background:
        #d4edda;

    color:
        #155724;

    border:
        1px solid
        #c3e6cb;

}


/* =====================================================
   FORM GRID
===================================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            1fr
        );

    gap:
        20px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

}


.form-group.full {

    grid-column:
        1 / -1;

}


.form-group label {

    font-weight:
        bold;

    margin-bottom:
        8px;

    color:
        #444;

}


.form-group input,
.form-group select,
.form-group textarea {

    width:
        100%;

    padding:
        13px;

    border:
        1px solid
        #ccd5df;

    border-radius:
        7px;

    font-size:
        15px;

    background:
        #fff;

    transition:
        .2s;

}


.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {

    outline:
        none;

    border-color:
        #0055a5;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            85,
            165,
            .10
        );

}


.form-group textarea {

    min-height:
        120px;

    resize:
        vertical;

}


/* =====================================================
   STUDENT INFORMATION
===================================================== */

.readonly-field {

    background:
        #f4f6f8 !important;

}


.form-note {

    margin-top:
        6px;

    font-size:
        12px;

    color:
        #777;

}


/* =====================================================
   PRICE DISPLAY
===================================================== */

.price-display {

    margin-top:
        25px;

    padding:
        22px;

    background:
        #f4f9ff;

    border-left:
        5px solid
        #003366;

    border-radius:
        8px;

}


.price-display-title {

    font-size:
        14px;

    color:
        #666;

    margin-bottom:
        6px;

}


.price-display-amount {

    font-size:
        30px;

    font-weight:
        bold;

    color:
        #003366;

}


.price-display-info {

    margin-top:
        8px;

    font-size:
        13px;

    color:
        #666;

}


/* =====================================================
   BUTTONS
===================================================== */

.form-actions {

    display:
        flex;

    gap:
        12px;

    margin-top:
        25px;

}


.btn {

    display:
        inline-block;

    padding:
        13px 22px;

    border:
        none;

    border-radius:
        7px;

    text-decoration:
        none;

    font-size:
        15px;

    font-weight:
        bold;

    cursor:
        pointer;

    transition:
        .2s;

}


.btn-primary {

    background:
        #003366;

    color:
        white;

}


.btn-primary:hover {

    background:
        #0055a5;

}


.btn-secondary {

    background:
        #e9eef3;

    color:
        #333;

}


.btn-secondary:hover {

    background:
        #dce4eb;

}


/* =====================================================
   REQUIRED
===================================================== */

.required {

    color:
        #d00;

}


/* =====================================================
   MOBILE
===================================================== */

@media(
    max-width: 900px
) {

    .sidebar {

        position:
            relative;

        width:
            100%;

        height:
            auto;

    }


    .main {

        margin-left:
            0;

        padding:
            15px;

    }


    .pricing-grid {

        grid-template-columns:
            1fr;

    }


    .form-grid {

        grid-template-columns:
            1fr;

    }


    .form-group.full {

        grid-column:
            auto;

    }


    .topbar {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            10px;

    }

}


@media(
    max-width: 600px
) {

    .page-header {

        padding:
            22px;

    }


    .page-header h1 {

        font-size:
            24px;

    }


    .form-container {

        padding:
            20px;

    }


    .form-actions {

        flex-direction:
            column;

    }


    .btn {

        width:
            100%;

        text-align:
            center;

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
        >
            🏠 Dashboard
        </a>


        <a
            href="profile.php"
        >
            👤 My Profile
        </a>


        <a
            href="bookings.php"
            class="active"
        >
            📚 My Bookings
        </a>


        <a
            href="schedule.php"
        >
            📅 My Schedule
        </a>


        <a
            href="payments.php"
        >
            💳 Payments
        </a>


        <a
            href="logout.php"
        >
            🚪 Logout
        </a>

    </div>

</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h2>
            Book a Subject
        </h2>


        <div class="student-name">

            Welcome,

            <strong>
                <?php

                echo htmlspecialchars(
                    $student_name,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>
            </strong>

        </div>

    </div>



    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>
            📚 Book Your Subject
        </h1>

        <p>

            Select your curriculum,
            class/year and subject.

            Your monthly lesson package
            includes two lessons per week
            and eight lessons per month.

        </p>

    </div>



    <!-- =================================================
         PRICING
    ================================================== -->

    <div class="pricing-grid">


        <div class="price-card">

            <h3>
                Cambridge
            </h3>

            <div class="price">
                GHS 1,000
            </div>

            <p>
                2 lessons per week
                <br>
                8 lessons per month
            </p>

        </div>



        <div class="price-card">

            <h3>
                IB
            </h3>

            <div class="price">
                GHS 1,200
            </div>

            <p>
                2 lessons per week
                <br>
                8 lessons per month
            </p>

        </div>




 <div class="pricing-grid">


        <div class="price-card">

            <h3>
                SAT
            </h3>

            <div class="price">
                GHS 800
            </div>

            <p>
                2 lessons per week
                <br>
                8 lessons per month
            </p>

        </div>


        <div class="price-card">

            <h3>
                GES
            </h3>

            <div class="price">
                GHS 800
            </div>

            <p>
                2 lessons per week
                <br>
                8 lessons per month
            </p>

        </div>


    </div>



    <!-- =================================================
         FORM
    ================================================== -->

    <div class="form-container">


        <h2>
            Student Booking Information
        </h2>


        <?php if ($message !== ""): ?>

            <div class="message
                <?php
                echo htmlspecialchars(
                    $message_type
                );
                ?>
            ">

                <?php

                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    'UTF-8'
                );

                ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            action=""
            id="bookingForm"
        >


            <div class="form-grid">


                <!-- STUDENT NAME -->

                <div class="form-group">

                    <label>

                        Student Name

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="student_name"
                        value="<?php

                            echo htmlspecialchars(
                                $formData['student_name'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                        required
                    >

                </div>



                <!-- DATE OF BIRTH -->

                <div class="form-group">

                    <label>

                        Date of Birth

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="date"
                        name="dob"
                        value="<?php

                            echo htmlspecialchars(
                                $formData['dob'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                        required
                    >

                </div>



                <!-- PHONE -->

                <div class="form-group">

                    <label>

                        Phone Number

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="tel"
                        name="phone"
                        value="<?php

                            echo htmlspecialchars(
                                $formData['phone'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                        placeholder="e.g. 0240000000"
                        required
                    >

                </div>



                <!-- EMAIL -->

                <div class="form-group">

                    <label>

                        Email Address

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="email"
                        name="email"
                        value="<?php

                            echo htmlspecialchars(
                                $formData['email'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                        required
                    >

                </div>



                <!-- CURRICULUM -->

                <div class="form-group">

                    <label>

                        Curriculum

                        <span class="required">
                            *
                        </span>

                    </label>


                    <select
                        name="curriculum"
                        id="curriculum"
                        required
                    >

                        <option value="">
                            Select Curriculum
                        </option>


                        <option
                            value="Cambridge"
                            <?php

                            if (
                                $formData['curriculum']
                                ===
                                'Cambridge'
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >
                            Cambridge
                        </option>


                        <option
                            value="IB"
                            <?php

                            if (
                                $formData['curriculum']
                                ===
                                'IB'
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >
                            IB
                        </option>


                        <option
                            value="GES"
                            <?php

                            if (
                                $formData['curriculum']
                                ===
                                'GES'
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >

                           GES
                        </option>


                        <option
                            value="SAT"
                            <?php

                            if (
                                $formData['curriculum']
                                ===
                                'SAT'
                            ) {

                                echo 'selected';

                            }

                            ?>
                        >
  
                            SAT
                        </option>

                    </select>

                </div>



                <!-- CLASS / YEAR -->

                <div class="form-group">

                    <label>

                        Class / Year / Grade

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="class_year"
                        value="<?php

                            echo htmlspecialchars(
                                $formData['class_year'],
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                        placeholder="e.g. Year 10, Grade 8, SHS 2"
                        required
                    >

                </div>



                <!-- SUBJECTS -->

                <div class="form-group full">

                    <label>

                        Subject(s)

                        <span class="required">
                            *
                        </span>

                    </label>


                    <textarea
                        name="subjects"
                        placeholder="Enter the subject(s) you want to study. Example: Mathematics"
                        required
                    ><?php

                        echo htmlspecialchars(
                            $formData['subjects'],
                            ENT_QUOTES,
                            'UTF-8'
                        );

                    ?></textarea>


                    <div class="form-note">

                        You may enter multiple subjects
                        if your booking package supports
                        multiple subjects.

                    </div>

                </div>


            </div>



            <!-- =================================================
                 PRICE DISPLAY
            ================================================== -->

            <div
                class="price-display"
                id="priceBox"
            >

                <div class="price-display-title">

                    Selected Curriculum Price

                </div>


                <div
                    class="price-display-amount"
                    id="priceDisplay"
                >

                    Select a curriculum

                </div>


                <div
                    class="price-display-info"
                    id="priceInfo"
                >

                    Your price will be calculated
                    automatically according to the
                    selected curriculum.

                </div>

            </div>



            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="form-actions">


                <a
                    href="dashboard.php"
                    class="btn btn-secondary"
                >

                    ← Cancel

                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    Continue to Payment →

                </button>


            </div>


        </form>


    </div>


</div>



<script>

/*
|--------------------------------------------------------------------------
| CURRICULUM PRICES
|--------------------------------------------------------------------------
*/

const curriculumPrices = {

    Cambridge: 1000,

    IB: 1200,

    SAT:800,
   
    GES: 800

};


/*
|--------------------------------------------------------------------------
| ELEMENTS
|--------------------------------------------------------------------------
*/

const curriculum =
    document.getElementById(
        "curriculum"
    );


const priceDisplay =
    document.getElementById(
        "priceDisplay"
    );


const priceInfo =
    document.getElementById(
        "priceInfo"
    );


/*
|--------------------------------------------------------------------------
| UPDATE PRICE
|--------------------------------------------------------------------------
*/

function updatePrice() {

    const selected =
        curriculum.value;


    if (
        curriculumPrices[
            selected
        ]
    ) {


        const amount =
            curriculumPrices[
                selected
            ];


        priceDisplay.textContent =
            "GHS "
            +
            amount.toLocaleString(
                "en-GH",
                {
                    minimumFractionDigits: 2,

                    maximumFractionDigits: 2

                }
            );


        priceInfo.textContent =
            selected
            +
            " curriculum • "
            +
            "2 lessons per week • "
            +
            "8 lessons per month";


    } else {


        priceDisplay.textContent =
            "Select a curriculum";


        priceInfo.textContent =
            "Your price will be calculated "
            +
            "automatically according to the "
            +
            "selected curriculum.";

    }

}


/*
|--------------------------------------------------------------------------
| LISTEN FOR CURRICULUM CHANGE
|--------------------------------------------------------------------------
*/

curriculum.addEventListener(
    "change",
    updatePrice
);


/*
|--------------------------------------------------------------------------
| DISPLAY EXISTING SELECTION
|--------------------------------------------------------------------------
*/

updatePrice();

</script>


</body>

</html>
