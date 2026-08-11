<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
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
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| HELPER
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
| ASSIGN TEACHER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["assign_teacher"])
) {

    $booking_id = (int)(
        $_POST["booking_id"] ?? 0
    );

    $teacher_id = trim(
        $_POST["teacher_id"] ?? ""
    );


    if ($booking_id <= 0) {

        $message = "Invalid booking selected.";
        $message_type = "error";

    } elseif ($teacher_id === "") {

        $message = "Please select a teacher.";
        $message_type = "error";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | START TRANSACTION
            |--------------------------------------------------------------------------
            */

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GET TEACHER
            |--------------------------------------------------------------------------
            */

            $teacherStmt = $pdo->prepare("
                SELECT
                    teacher_id,
                    teacher_name,
                    email,
                    phone,
                    subjects,
                    curriculum,
                    availability,
                    zoom_link,
                    status
                FROM teachers
                WHERE teacher_id = ?
                AND LOWER(status) = 'active'
                LIMIT 1
            ");

            $teacherStmt->execute([
                $teacher_id
            ]);

            $teacher = $teacherStmt->fetch(
                PDO::FETCH_ASSOC
            );


            if (!$teacher) {

                throw new Exception(
                    "The selected teacher was not found or is not active."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | GET BOOKING
            |--------------------------------------------------------------------------
            */

            $bookingStmt = $pdo->prepare("
                SELECT
                    id,
                    booking_reference,
                    student_name,
                    email,
                    subjects,
                    curriculum,
                    teacher_id,
                    teacher_name,
                    assignment_status
                FROM bookings
                WHERE id = ?
                LIMIT 1
            ");

            $bookingStmt->execute([
                $booking_id
            ]);

            $booking = $bookingStmt->fetch(
                PDO::FETCH_ASSOC
            );


            if (!$booking) {

                throw new Exception(
                    "The selected booking was not found."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE BOOKING
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | teacher_id   = teachers.teacher_id
            | teacher_name = teachers.teacher_name
            |
            */

            $updateStmt = $pdo->prepare("
                UPDATE bookings

                SET
                    teacher_id = :teacher_id,
                    teacher_name = :teacher_name,
                    assignment_status = 'Assigned'

                WHERE id = :booking_id
            ");


            $updateStmt->execute([

                ":teacher_id" =>
                    $teacher["teacher_id"],

                ":teacher_name" =>
                    $teacher["teacher_name"],

                ":booking_id" =>
                    $booking_id

            ]);


            /*
            |--------------------------------------------------------------------------
            | VERIFY THE UPDATE
            |--------------------------------------------------------------------------
            */

            $verifyStmt = $pdo->prepare("
                SELECT
                    id,
                    teacher_id,
                    teacher_name,
                    assignment_status
                FROM bookings
                WHERE id = ?
                LIMIT 1
            ");

            $verifyStmt->execute([
                $booking_id
            ]);

            $updatedBooking =
                $verifyStmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$updatedBooking) {

                throw new Exception(
                    "The booking could not be verified after the update."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VERIFY TEACHER NAME
            |--------------------------------------------------------------------------
            */

            if (
                $updatedBooking["teacher_id"]
                !==
                $teacher["teacher_id"]
            ) {

                throw new Exception(
                    "Teacher ID was not saved correctly."
                );

            }


            if (
                trim(
                    $updatedBooking["teacher_name"]
                )
                !==
                trim(
                    $teacher["teacher_name"]
                )
            ) {

                throw new Exception(
                    "Teacher name was not saved correctly."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            $message =
                "Teacher " .
                $teacher["teacher_name"] .
                " has been successfully assigned to " .
                $booking["student_name"] .
                ".";

            $message_type = "success";


        } catch (Exception $e) {

            if (
                $pdo->inTransaction()
            ) {
                $pdo->rollBack();
            }


            $message =
                "Unable to assign teacher: " .
                $e->getMessage();

            $message_type = "error";

        }

    }
}


/*
|--------------------------------------------------------------------------
| UNASSIGN TEACHER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["unassign_teacher"])
) {

    $booking_id = (int)(
        $_POST["booking_id"] ?? 0
    );


    if ($booking_id <= 0) {

        $message =
            "Invalid booking selected.";

        $message_type = "error";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE bookings

                SET
                    teacher_id = NULL,
                    teacher_name = NULL,
                    assignment_status = 'Unassigned'

                WHERE id = ?
            ");

            $stmt->execute([
                $booking_id
            ]);


            $message =
                "Teacher assignment removed successfully.";

            $message_type = "success";


        } catch (PDOException $e) {

            $message =
                "Unable to remove teacher assignment.";

            $message_type = "error";

        }

    }
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET["search"] ?? "");

$curriculum =
    trim($_GET["curriculum"] ?? "");

$payment =
    trim($_GET["payment"] ?? "");

$assignment =
    trim($_GET["assignment"] ?? "");


/*
|--------------------------------------------------------------------------
| GET TEACHERS
|--------------------------------------------------------------------------
*/

$teacherListStmt = $pdo->prepare("
    SELECT
        teacher_id,
        teacher_name,
        email,
        phone,
        subjects,
        curriculum,
        availability,
        zoom_link
    FROM teachers
    WHERE LOWER(status) = 'active'
    ORDER BY teacher_name ASC
");

$teacherListStmt->execute();

$teachers =
    $teacherListStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| GET BOOKINGS
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        b.id,

        b.booking_reference,

        b.student_name,

        b.email,

        b.phone,

        b.dob,

        b.curriculum,

        b.class_year,

        b.subjects,

        b.amount,

        b.payment_status,

        b.teacher_id,

        b.teacher_name,

        b.assignment_status,

        b.lesson_date,

        b.lesson_time,

        b.lesson_status,

        t.teacher_name AS real_teacher_name,

        t.email AS real_teacher_email,

        t.phone AS real_teacher_phone,

        t.zoom_link AS real_teacher_zoom

    FROM bookings b

    LEFT JOIN teachers t

        ON b.teacher_id = t.teacher_id

    WHERE 1 = 1

";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== "") {

    $sql .= "

        AND (

            b.student_name LIKE :search

            OR b.email LIKE :search

            OR b.phone LIKE :search

            OR b.booking_reference LIKE :search

            OR b.subjects LIKE :search

            OR b.teacher_name LIKE :search

        )

    ";

    $params[":search"] =
        "%" . $search . "%";
}


/*
|--------------------------------------------------------------------------
| CURRICULUM FILTER
|--------------------------------------------------------------------------
*/

if ($curriculum !== "") {

    $sql .= "
        AND b.curriculum = :curriculum
    ";

    $params[":curriculum"] =
        $curriculum;
}


/*
|--------------------------------------------------------------------------
| PAYMENT FILTER
|--------------------------------------------------------------------------
*/

if ($payment !== "") {

    $sql .= "
        AND LOWER(b.payment_status) = LOWER(:payment)
    ";

    $params[":payment"] =
        $payment;
}


/*
|--------------------------------------------------------------------------
| ASSIGNMENT FILTER
|--------------------------------------------------------------------------
*/

if ($assignment === "assigned") {

    $sql .= "

        AND b.teacher_id IS NOT NULL

        AND b.teacher_id <> ''

    ";

}

elseif ($assignment === "unassigned") {

    $sql .= "

        AND (
            b.teacher_id IS NULL
            OR b.teacher_id = ''
        )

    ";

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY

        CASE

            WHEN
                b.teacher_id IS NULL
                OR b.teacher_id = ''

            THEN 0

            ELSE 1

        END ASC,

        b.id DESC

";


$bookingStmt =
    $pdo->prepare($sql);

$bookingStmt->execute(
    $params
);

$bookings =
    $bookingStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalBookings =
    count($bookings);


$assignedCount = 0;

$unassignedCount = 0;


foreach ($bookings as $b) {

    if (
        !empty($b["teacher_id"])
    ) {

        $assignedCount++;

    } else {

        $unassignedCount++;

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
Assign Teachers |
NISEL ONLINE EDUCATION
</title>


<style>

/* =====================================================
   RESET
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

    background: #eef3f8;

    color: #1f2937;

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
        linear-gradient(
            180deg,
            #003b70,
            #002b52
        );

    color: white;

    padding: 25px 14px;

    overflow-y: auto;

}


.logo {

    text-align: center;

    font-size: 20px;

    font-weight: 800;

    line-height: 1.35;

    padding-bottom: 25px;

    margin-bottom: 15px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);

}


.logo small {

    display: block;

    font-size: 9px;

    letter-spacing: 2px;

    opacity: .7;

    margin-top: 5px;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 13px 14px;

    margin: 5px 0;

    color: white;

    text-decoration: none;

    border-radius: 9px;

    transition:
        .2s ease;

}


.menu a:hover {

    background:
        rgba(255,255,255,.12);

}


.menu a.active {

    background:
        rgba(255,255,255,.16);

    box-shadow:
        inset 3px 0 #38bdf8;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 28px;

}


/* =====================================================
   TOP HEADER
===================================================== */

.top-header {

    background: white;

    border-radius: 16px;

    padding: 24px 28px;

    margin-bottom: 22px;

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

}


.top-header h1 {

    margin: 0;

    color: #003b70;

    font-size: 27px;

}


.top-header p {

    margin: 7px 0 0;

    color: #64748b;

}


.admin-badge {

    background: #e8f3ff;

    color: #0055a5;

    padding: 10px 15px;

    border-radius: 25px;

    font-weight: bold;

    font-size: 13px;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding: 15px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-weight: 600;

}


.alert.success {

    background: #dcfce7;

    color: #166534;

    border:
        1px solid #bbf7d0;

}


.alert.error {

    background: #fee2e2;

    color: #991b1b;

    border:
        1px solid #fecaca;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 22px;

}


.stat-card {

    background: white;

    padding: 20px;

    border-radius: 14px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

}


.stat-title {

    color: #64748b;

    font-size: 13px;

    font-weight: 600;

}


.stat-number {

    color: #003b70;

    font-size: 28px;

    font-weight: 800;

    margin-top: 7px;

}


.stat-card.assigned {

    border-left:
        4px solid #22c55e;

}


.stat-card.unassigned {

    border-left:
        4px solid #f59e0b;

}


.stat-card.total {

    border-left:
        4px solid #0ea5e9;

}


/* =====================================================
   FILTER CARD
===================================================== */

.filter-card {

    background: white;

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

}


.filter-title {

    font-size: 17px;

    font-weight: 700;

    color: #003b70;

    margin-bottom: 15px;

}


.filters {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

}


.filters input,
.filters select {

    width: 100%;

    padding: 11px 13px;

    border:
        1px solid #d5dce5;

    border-radius: 8px;

    outline: none;

    background: white;

}


.filters input:focus,
.filters select:focus {

    border-color: #0ea5e9;

    box-shadow:
        0 0 0 3px
        rgba(14,165,233,.1);

}


.filter-btn {

    border: none;

    padding: 11px 18px;

    background: #003b70;

    color: white;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 700;

}


.filter-btn:hover {

    background: #0055a5;

}


.clear-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 11px 15px;

    background: #f1f5f9;

    color: #475569;

    border-radius: 8px;

    text-decoration: none;

    font-weight: 600;

}


/* =====================================================
   TABLE CARD
===================================================== */

.table-card {

    background: white;

    border-radius: 15px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

    overflow: hidden;

}


.table-header {

    padding: 20px 22px;

    border-bottom:
        1px solid #edf1f5;

    display: flex;

    justify-content:
        space-between;

    align-items: center;

}


.table-header h2 {

    margin: 0;

    font-size: 18px;

    color: #003b70;

}


.result-count {

    color: #64748b;

    font-size: 13px;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    min-width: 1250px;

    border-collapse:
        collapse;

}


thead th {

    background: #f8fafc;

    color: #475569;

    font-size: 11px;

    text-transform:
        uppercase;

    letter-spacing: .5px;

    padding: 14px;

    text-align: left;

    border-bottom:
        1px solid #e2e8f0;

}


tbody td {

    padding: 15px 14px;

    border-bottom:
        1px solid #edf1f5;

    vertical-align: middle;

}


tbody tr:hover {

    background: #f8fbff;

}


/* =====================================================
   STUDENT
===================================================== */

.student-name {

    color: #003b70;

    font-weight: 700;

}


.reference {

    font-size: 11px;

    color: #94a3b8;

    margin-top: 4px;

}


.contact {

    font-size: 12px;

    color: #64748b;

    margin-top: 4px;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-flex;

    align-items: center;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 11px;

    font-weight: 700;

}


.badge.paid {

    background: #dcfce7;

    color: #166534;

}


.badge.pending {

    background: #fef3c7;

    color: #92400e;

}


.badge.assigned {

    background: #dcfce7;

    color: #166534;

}


.badge.unassigned {

    background: #fee2e2;

    color: #991b1b;

}


.curriculum {

    color: #334155;

    font-weight: 700;

}


.subject {

    max-width: 210px;

    color: #475569;

    line-height: 1.45;

}


/* =====================================================
   CURRENT TEACHER
===================================================== */

.current-teacher {

    display: flex;

    align-items: center;

    gap: 9px;

}


.teacher-avatar {

    width: 36px;

    height: 36px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #dbeafe,
            #bfdbfe
        );

    display: flex;

    align-items: center;

    justify-content: center;

    color: #1d4ed8;

    font-weight: 800;

}


.teacher-name {

    font-weight: 700;

    color: #003b70;

}


.not-assigned {

    color: #94a3b8;

    font-size: 12px;

}


/* =====================================================
   ASSIGN FORM
===================================================== */

.assign-form {

    display: flex;

    align-items: center;

    gap: 7px;

}


.assign-form select {

    min-width: 190px;

    padding: 9px 10px;

    border:
        1px solid #cbd5e1;

    border-radius: 7px;

    background: white;

    color: #334155;

    outline: none;

}


.assign-form select:focus {

    border-color: #0ea5e9;

}


.assign-btn {

    border: none;

    background: #003b70;

    color: white;

    padding: 9px 13px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: 700;

}


.assign-btn:hover {

    background: #0055a5;

}


.unassign-btn {

    border: none;

    background: #fee2e2;

    color: #991b1b;

    padding: 8px 11px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: 700;

}


.unassign-btn:hover {

    background: #fecaca;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 60px 20px;

    color: #64748b;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 10px;

}


.empty h3 {

    color: #334155;

    margin: 10px 0;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 1100px) {

    .filters {

        grid-template-columns:
            1fr 1fr;

    }

}


@media(max-width: 850px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .stats {

        grid-template-columns: 1fr;

    }


    .top-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }

}


@media(max-width: 600px) {

    .filters {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">

        NISEL<br>
        ONLINE EDUCATION

        <small>
            ADMINISTRATION
        </small>

    </div>


    <nav class="menu">


        <a href="dashboard.php">

            🏠
            Dashboard

        </a>


        <a href="students.php">

            🎓
            Students

        </a>


        <a href="teachers.php">

            👨‍🏫
            Teachers

        </a>


        <a
            href="teacher_applications.php"
        >

            📋
            Teacher Applications

        </a>


        <a
            href="assign_teachers.php"
            class="active"
        >

            👨‍🏫
            Assign Teachers

        </a>


        <a href="bookings.php">

            📚
            Bookings

        </a>


        <a href="payments.php">

            💳
            Payments

        </a>


        <a href="reports.php">

            📊
            Reports

        </a>


        <a href="schedules.php">

            📅
            Schedules

        </a>


        <a href="logout.php">

            🚪
            Logout

        </a>


    </nav>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <section class="top-header">

        <div>

            <h1>
                👨‍🏫 Assign Teachers
            </h1>

            <p>
                Assign and manage teachers for
                student bookings.
            </p>

        </div>


        <div class="admin-badge">

            🔐 Administrator

        </div>

    </section>



    <!-- MESSAGE -->

    <?php if (!empty($message)): ?>

        <div
            class="alert
            <?php
                echo $message_type === "success"
                    ? "success"
                    : "error";
            ?>"
        >

            <?php
            echo h($message);
            ?>

        </div>

    <?php endif; ?>



    <!-- STATISTICS -->

    <section class="stats">


        <div class="stat-card total">

            <div class="stat-title">
                Total Bookings
            </div>

            <div class="stat-number">
                <?php
                echo $totalBookings;
                ?>
            </div>

        </div>


        <div class="stat-card assigned">

            <div class="stat-title">
                Assigned
            </div>

            <div class="stat-number">
                <?php
                echo $assignedCount;
                ?>
            </div>

        </div>


        <div class="stat-card unassigned">

            <div class="stat-title">
                Awaiting Assignment
            </div>

            <div class="stat-number">
                <?php
                echo $unassignedCount;
                ?>
            </div>

        </div>


    </section>



    <!-- FILTERS -->

    <section class="filter-card">


        <div class="filter-title">

            🔎 Find Bookings

        </div>


        <form
            method="GET"
            class="filters"
        >


            <input
                type="text"
                name="search"
                placeholder="Search student, email, booking reference..."
                value="<?php
                    echo h($search);
                ?>"
            >


            <select name="curriculum">

                <option value="">
                    All Curricula
                </option>

                <option
                    value="Cambridge"
                    <?php
                    echo $curriculum === "Cambridge"
                        ? "selected"
                        : "";
                    ?>
                >
                    Cambridge
                </option>

                <option
                    value="IB"
                    <?php
                    echo $curriculum === "IB"
                        ? "selected"
                        : "";
                    ?>
                >
                    IB
                </option>

                <option
                    value="GES"
                    <?php
                    echo $curriculum === "GES"
                        ? "selected"
                        : "";
                    ?>
                >
                    GES
                </option>

                <option
                    value="SAT"
                    <?php
                    echo $curriculum === "SAT"
                        ? "selected"
                        : "";
                    ?>
                >
                    SAT
                </option>

            </select>


            <select name="payment">

                <option value="">
                    All Payments
                </option>

                <option
                    value="Paid"
                    <?php
                    echo $payment === "Paid"
                        ? "selected"
                        : "";
                    ?>
                >
                    Paid
                </option>

                <option
                    value="Pending"
                    <?php
                    echo $payment === "Pending"
                        ? "selected"
                        : "";
                    ?>
                >
                    Pending
                </option>

            </select>


            <select name="assignment">

                <option value="">
                    All Assignments
                </option>

                <option
                    value="assigned"
                    <?php
                    echo $assignment === "assigned"
                        ? "selected"
                        : "";
                    ?>
                >
                    Assigned
                </option>

                <option
                    value="unassigned"
                    <?php
                    echo $assignment === "unassigned"
                        ? "selected"
                        : "";
                    ?>
                >
                    Unassigned
                </option>

            </select>


            <button
                type="submit"
                class="filter-btn"
            >

                Filter

            </button>


        </form>


        <?php if (
            $search !== "" ||
            $curriculum !== "" ||
            $payment !== "" ||
            $assignment !== ""
        ): ?>

            <div
                style="
                    margin-top:12px;
                "
            >

                <a
                    href="assign_teachers.php"
                    class="clear-btn"
                >

                    ✕ Clear Filters

                </a>

            </div>

        <?php endif; ?>


    </section>



    <!-- BOOKINGS -->

    <section class="table-card">


        <div class="table-header">

            <h2>
                📚 Student Bookings
            </h2>


            <div class="result-count">

                <?php
                echo $totalBookings;
                ?>
                result(s)

            </div>

        </div>



        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            Student
                        </th>

                        <th>
                            Curriculum
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Current Teacher
                        </th>

                        <th>
                            Assignment
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if (
                    count($bookings) > 0
                ): ?>


                    <?php
                    foreach (
                        $bookings
                        as $booking
                    ):
                    ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>

                            <div class="student-name">

                                <?php
                                echo h(
                                    $booking[
                                        "student_name"
                                    ]
                                );
                                ?>

                            </div>


                            <div class="reference">

                                Ref:
                                <?php
                                echo h(
                                    $booking[
                                        "booking_reference"
                                    ]
                                );
                                ?>

                            </div>


                            <?php if (
                                !empty(
                                    $booking["email"]
                                )
                            ): ?>

                                <div class="contact">

                                    📧
                                    <?php
                                    echo h(
                                        $booking["email"]
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>


                        </td>



                        <!-- CURRICULUM -->

                        <td>

                            <span class="curriculum">

                                <?php
                                echo h(
                                    $booking[
                                        "curriculum"
                                    ] ??
                                    "Not specified"
                                );
                                ?>

                            </span>

                        </td>



                        <!-- SUBJECT -->

                        <td>

                            <div class="subject">

                                <?php
                                echo h(
                                    $booking[
                                        "subjects"
                                    ] ??
                                    "Not specified"
                                );
                                ?>

                            </div>

                        </td>



                        <!-- PAYMENT -->

                        <td>

                            <?php

                            $paymentStatus =
                                strtolower(
                                    trim(
                                        $booking[
                                            "payment_status"
                                        ] ?? ""
                                    )
                                );


                            if (
                                $paymentStatus === "paid" ||
                                $paymentStatus === "success"
                            ):

                            ?>

                                <span
                                    class="badge paid"
                                >

                                    ✓ PAID

                                </span>

                            <?php else: ?>

                                <span
                                    class="badge pending"
                                >

                                    ! <?php
                                    echo h(
                                        strtoupper(
                                            $booking[
                                                "payment_status"
                                            ] ??
                                            "PENDING"
                                        )
                                    );
                                    ?>

                                </span>

                            <?php endif; ?>

                        </td>



                        <!-- CURRENT TEACHER -->

                        <td>


                            <?php

                            /*
                            --------------------------------------------------
                            IMPORTANT:
                            Use real teacher record first.
                            If it does not exist, fall back to booking name.
                            --------------------------------------------------
                            */

                            $displayTeacherName = "";


                            if (
                                !empty(
                                    $booking[
                                        "real_teacher_name"
                                    ]
                                )
                            ) {

                                $displayTeacherName =
                                    $booking[
                                        "real_teacher_name"
                                    ];

                            } elseif (
                                !empty(
                                    $booking[
                                        "teacher_name"
                                    ]
                                )
                            ) {

                                $displayTeacherName =
                                    $booking[
                                        "teacher_name"
                                    ];

                            }


                            ?>


                            <?php if (
                                !empty(
                                    $displayTeacherName
                                )
                            ): ?>


                                <div
                                    class="current-teacher"
                                >


                                    <div
                                        class="teacher-avatar"
                                    >

                                        <?php

                                        echo strtoupper(
                                            substr(
                                                $displayTeacherName,
                                                0,
                                                1
                                            )
                                        );

                                        ?>

                                    </div>


                                    <div>

                                        <div
                                            class="teacher-name"
                                        >

                                            <?php
                                            echo h(
                                                $displayTeacherName
                                            );
                                            ?>

                                        </div>


                                        <span
                                            class="
                                                badge
                                                assigned
                                            "
                                        >

                                            Assigned

                                        </span>

                                    </div>


                                </div>


                            <?php else: ?>


                                <span
                                    class="badge unassigned"
                                >

                                    Not Assigned

                                </span>


                            <?php endif; ?>


                        </td>



                        <!-- ASSIGN TEACHER -->

                        <td>


                            <form
                                method="POST"
                                class="assign-form"
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo (int)
                                        $booking["id"];
                                    ?>"
                                >


                                <select
                                    name="teacher_id"
                                    required
                                >

                                    <option value="">

                                        Select Teacher

                                    </option>


                                    <?php
                                    foreach (
                                        $teachers
                                        as $teacher
                                    ):
                                    ?>


                                        <option
                                            value="<?php
                                            echo h(
                                                $teacher[
                                                    "teacher_id"
                                                ]
                                            );
                                            ?>"
                                            <?php

                                            if (
                                                !empty(
                                                    $booking[
                                                        "teacher_id"
                                                    ]
                                                ) &&
                                                $booking[
                                                    "teacher_id"
                                                ]
                                                ===
                                                $teacher[
                                                    "teacher_id"
                                                ]
                                            ) {

                                                echo "selected";

                                            }

                                            ?>
                                        >

                                            <?php
                                            echo h(
                                                $teacher[
                                                    "teacher_name"
                                                ]
                                            );
                                            ?>

                                        </option>


                                    <?php
                                    endforeach;
                                    ?>


                                </select>



                                <button
                                    type="submit"
                                    name="assign_teacher"
                                    value="1"
                                    class="assign-btn"
                                >

                                    <?php

                                    if (
                                        !empty(
                                            $booking[
                                                "teacher_id"
                                            ]
                                        )
                                    ) {

                                        echo "Update";

                                    } else {

                                        echo "Assign";

                                    }

                                    ?>

                                </button>


                            </form>



                            <?php if (
                                !empty(
                                    $booking[
                                        "teacher_id"
                                    ]
                                )
                            ): ?>


                                <form
                                    method="POST"
                                    style="
                                        margin-top:7px;
                                    "
                                >


                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php
                                        echo (int)
                                            $booking["id"];
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="unassign_teacher"
                                        value="1"
                                        class="unassign-btn"
                                        onclick="
                                            return confirm(
                                                'Remove this teacher assignment?'
                                            );
                                        "
                                    >

                                        Remove Assignment

                                    </button>


                                </form>


                            <?php endif; ?>


                        </td>


                    </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            <div
                                class="empty-icon"
                            >
                                👨‍🏫
                            </div>


                            <h3>
                                No bookings found
                            </h3>


                            <p>
                                There are no bookings
                                matching your filters.
                            </p>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</main>


</body>

</html>
