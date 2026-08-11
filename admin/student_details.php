<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN - STUDENT DETAILS
| PDO VERSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {
    header("Location: ../admin_login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require "../config/db.php";

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| GET STUDENT ID
|--------------------------------------------------------------------------
*/

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header("Location: students.php");
    exit;
}


$student_id =
    (int)$_GET['id'];


/*
|--------------------------------------------------------------------------
| GET STUDENT
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            student_name,
            email,
            phone,
            dob
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

        die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Student Not Found</title>

                <style>

                    body {
                        font-family: Arial, sans-serif;
                        background:#f4f7fb;
                        margin:0;
                    }

                    .error-box {
                        max-width:600px;
                        margin:100px auto;
                        background:white;
                        padding:40px;
                        text-align:center;
                        border-radius:16px;
                        box-shadow:0 5px 25px rgba(0,0,0,.08);
                    }

                    .error-box h2 {
                        color:#003366;
                    }

                    .back {
                        display:inline-block;
                        margin-top:20px;
                        padding:12px 20px;
                        background:#003366;
                        color:white;
                        text-decoration:none;
                        border-radius:8px;
                    }

                </style>

            </head>

            <body>

                <div class='error-box'>

                    <h2>
                        Student Not Found
                    </h2>

                    <p>
                        The student record could not be found.
                    </p>

                    <a
                        href='students.php'
                        class='back'
                    >
                        ← Back to Students
                    </a>

                </div>

            </body>
            </html>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKINGS
    |--------------------------------------------------------------------------
    |
    | Your existing student dashboard uses email to retrieve bookings.
    |
    |--------------------------------------------------------------------------
    */

    $bookingStmt = $pdo->prepare("
        SELECT
            b.id,
            b.booking_reference,
            b.student_name,
            b.email,
            b.phone,
            b.curriculum,
            b.class_year,
            b.subjects,
            b.amount,
            b.payment_status,
            b.paystack_reference,
            b.teacher_id,
            b.teacher_name,
            b.assignment_status,
            b.lesson_date,
            b.lesson_time,
            b.lesson_status

        FROM bookings b

        WHERE b.email = ?

        ORDER BY
            b.lesson_date ASC,
            b.lesson_time ASC,
            b.id DESC
    ");

    $bookingStmt->execute([
        $student['email']
    ]);

    $bookings =
        $bookingStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die(
        "Database error: " .
        h($e->getMessage())
    );

}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalBookings =
    count($bookings);


$paidBookings = 0;

$pendingBookings = 0;

$assignedBookings = 0;

$completedLessons = 0;


foreach (
    $bookings as $booking
) {

    $payment =
        strtolower(
            trim(
                $booking['payment_status']
                ?? ''
            )
        );


    if (
        $payment === 'paid' ||
        $payment === 'success'
    ) {

        $paidBookings++;

    }


    if (
        $payment === 'pending'
    ) {

        $pendingBookings++;

    }


    if (
        !empty(
            $booking['teacher_id']
        )
    ) {

        $assignedBookings++;

    }


    $lessonStatus =
        strtolower(
            trim(
                $booking['lesson_status']
                ?? ''
            )
        );


    if (
        $lessonStatus === 'completed'
    ) {

        $completedLessons++;

    }

}


/*
|--------------------------------------------------------------------------
| INITIALS
|--------------------------------------------------------------------------
*/

$name =
    trim(
        $student['student_name']
        ?? ''
    );


$words =
    preg_split(
        '/\s+/',
        $name
    );


$initials = '';


foreach (
    $words as $word
) {

    if (
        $word === ''
    ) {
        continue;
    }

    $initials .=
        strtoupper(
            substr(
                $word,
                0,
                1
            )
        );


    if (
        strlen($initials) >= 2
    ) {
        break;
    }

}


if (
    $initials === ''
) {

    $initials = 'ST';

}


/*
|--------------------------------------------------------------------------
| DATE OF BIRTH
|--------------------------------------------------------------------------
*/

$dob = 'Not provided';


if (
    !empty(
        $student['dob']
    )
) {

    $time =
        strtotime(
            $student['dob']
        );


    if (
        $time !== false
    ) {

        $dob =
            date(
                'd F Y',
                $time
            );

    }

}


/*
|--------------------------------------------------------------------------
| CURRICULUM BADGE CLASS
|--------------------------------------------------------------------------
*/

function curriculumClass(
    $curriculum
) {

    $value =
        strtolower(
            trim(
                $curriculum
            )
        );


    if (
        strpos(
            $value,
            'cambridge'
        ) !== false
    ) {

        return 'cambridge';

    }


    if (
        strpos(
            $value,
            'ib'
        ) !== false
    ) {

        return 'ib';

    }


    if (
        strpos(
            $value,
            'ges'
        ) !== false
    ) {

        return 'ges';

    }


    if (
        strpos(
            $value,
            'sat'
        ) !== false
    ) {

        return 'sat';

    }


    return '';

}


/*
|--------------------------------------------------------------------------
| PAYMENT DISPLAY
|--------------------------------------------------------------------------
*/

function paymentClass(
    $status
) {

    $status =
        strtolower(
            trim(
                $status
            )
        );


    if (
        $status === 'paid' ||
        $status === 'success'
    ) {

        return 'paid';

    }


    if (
        $status === 'pending'
    ) {

        return 'pending';

    }


    return 'other';

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

    Student Details |
    NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing:border-box;
}


body {

    margin:0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:#f4f7fb;

    color:#172b4d;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position:fixed;

    left:0;

    top:0;

    width:245px;

    height:100vh;

    background:
        linear-gradient(
            180deg,
            #003366,
            #00264d
        );

    color:white;

    padding:25px 15px;

    z-index:1000;

}


.brand {

    text-align:center;

    padding-bottom:25px;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

}


.brand-icon {

    width:58px;

    height:58px;

    margin:
        0 auto 10px;

    border-radius:16px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:28px;

}


.brand h2 {

    margin:0;

    font-size:20px;

}


.brand p {

    margin:
        5px 0 0;

    font-size:9px;

    letter-spacing:2px;

    opacity:.7;

}


.nav {

    margin-top:25px;

}


.nav a {

    display:flex;

    align-items:center;

    gap:12px;

    padding:13px 14px;

    margin-bottom:7px;

    border-radius:9px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration:none;

    font-size:14px;

}


.nav a:hover,
.nav a.active {

    background:
        rgba(
            255,
            255,
            255,
            .13
        );

    color:white;

}


.nav-icon {

    width:23px;

    text-align:center;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left:245px;

    min-height:100vh;

    padding:30px;

}


/* =====================================================
   TOP
===================================================== */

.topbar {

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:25px;

}


.back-button {

    display:inline-flex;

    align-items:center;

    gap:7px;

    padding:10px 15px;

    border-radius:9px;

    background:white;

    color:#003366;

    text-decoration:none;

    font-size:13px;

    font-weight:700;

    box-shadow:
        0 3px 12px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.admin-badge {

    display:flex;

    align-items:center;

    gap:10px;

    background:white;

    padding:9px 14px;

    border-radius:10px;

    box-shadow:
        0 3px 12px
        rgba(
            0,
            0,
            0,
            .05
        );

    font-size:13px;

}


.admin-avatar {

    width:35px;

    height:35px;

    border-radius:50%;

    background:#e8f2ff;

    display:flex;

    align-items:center;

    justify-content:center;

}


/* =====================================================
   PROFILE HERO
===================================================== */

.profile-hero {

    background:
        linear-gradient(
            135deg,
            #003366,
            #0066aa
        );

    border-radius:18px;

    padding:30px;

    color:white;

    display:flex;

    align-items:center;

    gap:22px;

    margin-bottom:22px;

    box-shadow:
        0 8px 25px
        rgba(
            0,
            51,
            102,
            .18
        );

}


.student-avatar {

    width:90px;

    height:90px;

    flex:0 0 90px;

    border-radius:50%;

    background:
        rgba(
            255,
            255,
            255,
            .18
        );

    border:
        3px solid
        rgba(
            255,
            255,
            255,
            .3
        );

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:28px;

    font-weight:800;

}


.profile-name {

    margin:0;

    font-size:28px;

}


.profile-id {

    margin-top:7px;

    opacity:.75;

    font-size:13px;

}


.profile-email {

    margin-top:12px;

    opacity:.9;

    font-size:13px;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display:grid;

    grid-template-columns:
        repeat(
            4,
            1fr
        );

    gap:16px;

    margin-bottom:22px;

}


.stat {

    background:white;

    padding:20px;

    border-radius:14px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.stat-icon {

    font-size:22px;

}


.stat-number {

    margin-top:10px;

    font-size:25px;

    font-weight:800;

    color:#003366;

}


.stat-label {

    margin-top:3px;

    color:#667085;

    font-size:11px;

}


/* =====================================================
   CARDS
===================================================== */

.card {

    background:white;

    border-radius:16px;

    padding:23px;

    margin-bottom:22px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.card-title {

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding-bottom:15px;

    border-bottom:
        1px solid
        #eaecf0;

    margin-bottom:18px;

}


.card-title h2 {

    margin:0;

    font-size:18px;

}


/* =====================================================
   INFORMATION GRID
===================================================== */

.info-grid {

    display:grid;

    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap:15px;

}


.info-box {

    background:#f8fafc;

    padding:15px;

    border-radius:10px;

}


.info-label {

    color:#98a2b3;

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:.5px;

    margin-bottom:6px;

}


.info-value {

    color:#344054;

    font-size:13px;

    font-weight:600;

    word-break:break-word;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x:auto;

}


table {

    width:100%;

    min-width:1100px;

    border-collapse:collapse;

}


thead {

    background:#f8fafc;

}


th {

    padding:13px;

    text-align:left;

    font-size:10px;

    color:#667085;

    text-transform:uppercase;

    letter-spacing:.5px;

}


td {

    padding:14px 13px;

    border-top:
        1px solid
        #eaecf0;

    font-size:12px;

    vertical-align:middle;

}


tbody tr:hover {

    background:#f8fbff;

}


/* =====================================================
   CURRICULUM
===================================================== */

.curriculum {

    display:inline-block;

    padding:5px 9px;

    border-radius:20px;

    font-size:10px;

    font-weight:700;

    background:#eef4ff;

    color:#175cd3;

}


.curriculum.cambridge {

    background:#eef4ff;

    color:#175cd3;

}


.curriculum.ib {

    background:#f4ebff;

    color:#6941c6;

}


.curriculum.ges {

    background:#ecfdf3;

    color:#027a48;

}


.curriculum.sat {

    background:#fff4e5;

    color:#b54708;

}


/* =====================================================
   STATUS
===================================================== */

.badge {

    display:inline-block;

    padding:5px 9px;

    border-radius:20px;

    font-size:10px;

    font-weight:700;

}


.badge.paid {

    background:#e7f7ed;

    color:#16803d;

}


.badge.pending {

    background:#fff4e5;

    color:#b54708;

}


.badge.other {

    background:#f2f4f7;

    color:#667085;

}


.badge.assigned {

    background:#eef4ff;

    color:#175cd3;

}


.badge.not-assigned {

    background:#f2f4f7;

    color:#667085;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align:center;

    padding:45px;

    color:#98a2b3;

}


.empty-icon {

    font-size:40px;

    margin-bottom:10px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align:center;

    color:#98a2b3;

    font-size:11px;

    padding:10px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .info-grid {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }

}


@media(max-width:800px) {

    .sidebar {

        width:70px;

        padding:20px 8px;

    }


    .brand h2,
    .brand p,
    .nav-text {

        display:none;

    }


    .brand {

        border:none;

    }


    .nav a {

        justify-content:center;

    }


    .main {

        margin-left:70px;

        padding:20px;

    }

}


@media(max-width:600px) {

    .topbar {

        flex-direction:column;

        align-items:flex-start;

        gap:12px;

    }


    .profile-hero {

        flex-direction:column;

        text-align:center;

    }


    .stats {

        grid-template-columns:1fr;

    }


    .info-grid {

        grid-template-columns:1fr;

    }


    .main {

        padding:15px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="brand">


        <div class="brand-icon">

            🎓

        </div>


        <h2>

            NISEL

        </h2>


        <p>

            ONLINE EDUCATION

        </p>


    </div>


    <nav class="nav">


        <a href="dashboard.php">

            <span class="nav-icon">
                🏠
            </span>

            <span class="nav-text">
                Dashboard
            </span>

        </a>


        <a href="teachers.php">

            <span class="nav-icon">
                👨‍🏫
            </span>

            <span class="nav-text">
                Teachers
            </span>

        </a>


        <a
            href="students.php"
            class="active"
        >

            <span class="nav-icon">
                👨‍🎓
            </span>

            <span class="nav-text">
                Students
            </span>

        </a>


        <a href="bookings.php">

            <span class="nav-icon">
                📚
            </span>

            <span class="nav-text">
                Bookings
            </span>

        </a>


        <a href="payments.php">

            <span class="nav-icon">
                💳
            </span>

            <span class="nav-text">
                Payments
            </span>

        </a>


        <a href="logout.php">

            <span class="nav-icon">
                🚪
            </span>

            <span class="nav-text">
                Logout
            </span>

        </a>


    </nav>


</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- TOP -->

    <div class="topbar">


        <a
            href="students.php"
            class="back-button"
        >

            ← Back to Students

        </a>


        <div class="admin-badge">


            <div class="admin-avatar">

                👤

            </div>


            <div>

                <strong>

                    <?= h(
                        $_SESSION['admin_name']
                        ??
                        'Administrator'
                    ) ?>

                </strong>


                <div
                    style="
                        color:#98a2b3;
                        font-size:11px;
                        margin-top:2px;
                    "
                >

                    Administrator

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         STUDENT HERO
    ================================================== -->

    <section class="profile-hero">


        <div class="student-avatar">

            <?= h(
                $initials
            ) ?>

        </div>


        <div>


            <h1
                class="profile-name"
            >

                <?= h(
                    $student[
                        'student_name'
                    ]
                ) ?>

            </h1>


            <div
                class="profile-id"
            >

                Student ID:
                <?= h(
                    $student['id']
                ) ?>

            </div>


            <div
                class="profile-email"
            >

                📧
                <?= h(
                    $student['email']
                ) ?>

            </div>


        </div>


    </section>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat">


            <div class="stat-icon">

                📚

            </div>


            <div
                class="stat-number"
            >

                <?= $totalBookings ?>

            </div>


            <div
                class="stat-label"
            >

                Total Bookings

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                💳

            </div>


            <div
                class="stat-number"
            >

                <?= $paidBookings ?>

            </div>


            <div
                class="stat-label"
            >

                Paid Bookings

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                👨‍🏫

            </div>


            <div
                class="stat-number"
            >

                <?= $assignedBookings ?>

            </div>


            <div
                class="stat-label"
            >

                Assigned Lessons

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                ✅

            </div>


            <div
                class="stat-number"
            >

                <?= $completedLessons ?>

            </div>


            <div
                class="stat-label"
            >

                Completed Lessons

            </div>


        </div>


    </div>



    <!-- =================================================
         PERSONAL INFORMATION
    ================================================== -->

    <section class="card">


        <div class="card-title">


            <h2>

                👤 Student Information

            </h2>


        </div>


        <div class="info-grid">


            <div class="info-box">


                <div class="info-label">

                    Full Name

                </div>


                <div class="info-value">

                    <?= h(
                        $student[
                            'student_name'
                        ]
                    ) ?>

                </div>


            </div>



            <div class="info-box">


                <div class="info-label">

                    Email

                </div>


                <div class="info-value">

                    <?= h(
                        $student[
                            'email'
                        ]
                    ) ?>

                </div>


            </div>



            <div class="info-box">


                <div class="info-label">

                    Phone

                </div>


                <div class="info-value">

                    <?= !empty(
                        $student[
                            'phone'
                        ]
                    )
                        ? h(
                            $student[
                                'phone'
                            ]
                        )
                        : 'Not provided'
                    ?>

                </div>


            </div>



            <div class="info-box">


                <div class="info-label">

                    Date of Birth

                </div>


                <div class="info-value">

                    <?= h(
                        $dob
                    ) ?>

                </div>


            </div>



            <div class="info-box">


                <div class="info-label">

                    Student ID

                </div>


                <div class="info-value">

                    #<?= h(
                        $student[
                            'id'
                        ]
                    ) ?>

                </div>


            </div>


        </div>


    </section>



    <!-- =================================================
         BOOKINGS
    ================================================== -->

    <section class="card">


        <div class="card-title">


            <h2>

                📚 Student Bookings

            </h2>


            <span
                style="
                    color:#98a2b3;
                    font-size:12px;
                "
            >

                <?= $totalBookings ?>
                booking(s)

            </span>


        </div>



        <?php if (
            empty($bookings)
        ): ?>


            <div class="empty">


                <div class="empty-icon">

                    📚

                </div>


                <strong>

                    No bookings found

                </strong>


                <p>

                    This student has not created
                    any lesson bookings yet.

                </p>


            </div>


        <?php else: ?>


            <div
                class="table-wrapper"
            >


                <table>


                    <thead>

                        <tr>

                            <th>
                                Reference
                            </th>

                            <th>
                                Curriculum
                            </th>

                            <th>
                                Class / Year
                            </th>

                            <th>
                                Subject
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Teacher
                            </th>

                            <th>
                                Lesson Date
                            </th>

                            <th>
                                Lesson Time
                            </th>

                            <th>
                                Lesson Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $bookings
                        as $booking
                    ): ?>


                        <?php

                        $curriculum =
                            trim(
                                $booking[
                                    'curriculum'
                                ]
                                ??
                                ''
                            );


                        $curriculum_class =
                            curriculumClass(
                                $curriculum
                            );


                        $payment_status =
                            trim(
                                $booking[
                                    'payment_status'
                                ]
                                ??
                                ''
                            );


                        $payment_class =
                            paymentClass(
                                $payment_status
                            );


                        $lesson_status =
                            trim(
                                $booking[
                                    'lesson_status'
                                ]
                                ??
                                ''
                            );


                        ?>


                        <tr>


                            <!-- REFERENCE -->

                            <td>


                                <strong>

                                    <?= h(
                                        $booking[
                                            'booking_reference'
                                        ]
                                        ??
                                        'N/A'
                                    ) ?>

                                </strong>


                            </td>



                            <!-- CURRICULUM -->

                            <td>


                                <?php if (
                                    $curriculum !== ''
                                ): ?>


                                    <span
                                        class="
                                            curriculum
                                            <?= h(
                                                $curriculum_class
                                            ) ?>
                                        "
                                    >

                                        <?= h(
                                            $curriculum
                                        ) ?>

                                    </span>


                                <?php else: ?>


                                    <span
                                        style="
                                            color:#98a2b3;
                                        "
                                    >

                                        Not provided

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- CLASS -->

                            <td>

                                <?= h(
                                    $booking[
                                        'class_year'
                                    ]
                                    ??
                                    'Not provided'
                                ) ?>

                            </td>



                            <!-- SUBJECT -->

                            <td>

                                <strong>

                                    <?= h(
                                        $booking[
                                            'subjects'
                                        ]
                                        ??
                                        'Not provided'
                                    ) ?>

                                </strong>

                            </td>



                            <!-- AMOUNT -->

                            <td>


                                <?php if (
                                    $booking[
                                        'amount'
                                    ]
                                    !== null
                                    &&
                                    $booking[
                                        'amount'
                                    ]
                                    !== ''
                                ): ?>


                                    <strong>

                                        GHS
                                        <?= number_format(
                                            (float)
                                            $booking[
                                                'amount'
                                            ],
                                            2
                                        ) ?>

                                    </strong>


                                <?php else: ?>


                                    <span>

                                        —

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- PAYMENT -->

                            <td>


                                <span
                                    class="
                                        badge
                                        <?= h(
                                            $payment_class
                                        ) ?>
                                    "
                                >

                                    <?= h(
                                        $payment_status
                                        !== ''
                                            ? strtoupper(
                                                $payment_status
                                            )
                                            : 'NOT PAID'
                                    ) ?>

                                </span>


                            </td>



                            <!-- TEACHER -->

                            <td>


                                <?php if (
                                    !empty(
                                        $booking[
                                            'teacher_name'
                                        ]
                                    )
                                ): ?>


                                    <span
                                        class="
                                            badge
                                            assigned
                                        "
                                    >

                                        👨‍🏫

                                        <?= h(
                                            $booking[
                                                'teacher_name'
                                            ]
                                        ) ?>

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            not-assigned
                                        "
                                    >

                                        Not Assigned

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- DATE -->

                            <td>


                                <?php if (
                                    !empty(
                                        $booking[
                                            'lesson_date'
                                        ]
                                    )
                                ): ?>


                                    <?= h(
                                        date(
                                            'd M Y',
                                            strtotime(
                                                $booking[
                                                    'lesson_date'
                                                ]
                                            )
                                        )
                                    ) ?>


                                <?php else: ?>


                                    Not scheduled

                                <?php endif; ?>


                            </td>



                            <!-- TIME -->

                            <td>


                                <?= !empty(
                                    $booking[
                                        'lesson_time'
                                    ]
                                )
                                    ? h(
                                        $booking[
                                            'lesson_time'
                                        ]
                                    )
                                    : '—'
                                ?>


                            </td>



                            <!-- LESSON STATUS -->

                            <td>


                                <?php if (
                                    $lesson_status !== ''
                                ): ?>


                                    <?php

                                    $lessonClass =
                                        strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $lesson_status
                                            )
                                        );

                                    ?>


                                    <span
                                        class="
                                            badge
                                            <?= h(
                                                $lessonClass
                                            ) ?>
                                        "
                                    >

                                        <?= h(
                                            strtoupper(
                                                $lesson_status
                                            )
                                        ) ?>

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            other
                                        "
                                    >

                                        Not Scheduled

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php endif; ?>


    </section>



    <div class="footer">

        NISEL ONLINE EDUCATION
        ·
        Administrator Student Details

    </div>


</main>


</body>

</html>
