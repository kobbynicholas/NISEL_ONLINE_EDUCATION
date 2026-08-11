<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN DASHBOARD
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


if (!isset($pdo)) {

    die(
        "Database connection is not available. " .
        "Please check config/db.php"
    );

}


$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
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
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$adminName =
    $_SESSION['admin_name']
    ?? 'Administrator';


/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

try {


    /*
    |--------------------------------------------------------------------------
    | TOTAL STUDENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM students
    ");

    $totalStudents =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | TOTAL TEACHERS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM teachers
    ");

    $totalTeachers =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | TOTAL BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
    ");

    $totalBookings =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | TOTAL PAYMENT RECORDS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
    ");

    $totalPayments =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | PENDING TEACHER APPLICATIONS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM teacher_applications
        WHERE LOWER(status) = 'pending'
    ");

    $pendingApplications =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | PAID BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE
            LOWER(payment_status) = 'paid'
            OR
            LOWER(payment_status) = 'success'
    ");

    $paidBookings =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | ASSIGNED BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE
            teacher_id IS NOT NULL
            AND teacher_id <> ''
    ");

    $assignedBookings =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | UNASSIGNED BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE
            teacher_id IS NULL
            OR teacher_id = ''
    ");

    $unassignedBookings =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | TOTAL REVENUE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT
            COALESCE(
                SUM(amount),
                0
            )
        FROM payments
        WHERE
            LOWER(status) = 'paid'
            OR
            LOWER(status) = 'success'
    ");

    $totalRevenue =
        (float)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | PENDING PAYMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
        WHERE LOWER(status) = 'pending'
    ");

    $pendingPayments =
        (int)$stmt->fetchColumn();



    /*
    |--------------------------------------------------------------------------
    | RECENT BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT

            b.id,

            b.booking_reference,

            b.student_name,

            b.email,

            b.subjects,

            b.curriculum,

            b.class_year,

            b.amount,

            b.payment_status,

            b.teacher_id,

            b.teacher_name,

            b.lesson_date,

            b.lesson_status

        FROM bookings b

        ORDER BY b.id DESC

        LIMIT 10
    ");

    $recentBookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );



    /*
    |--------------------------------------------------------------------------
    | RECENT PAYMENTS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->query("
        SELECT

            id,

            booking_reference,

            student_name,

            amount,

            payment_method,

            transaction_reference,

            status

        FROM payments

        ORDER BY id DESC

        LIMIT 6
    ");

    $recentPayments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


}
catch (PDOException $e) {

    die(
        "Unable to load dashboard: "
        .
        h(
            $e->getMessage()
        )
    );

}


/*
|--------------------------------------------------------------------------
| CALCULATE PAYMENT PERCENTAGE
|--------------------------------------------------------------------------
*/

$paymentPercentage = 0;


if ($totalBookings > 0) {

    $paymentPercentage =
        round(
            (
                $paidBookings
                /
                $totalBookings
            ) * 100
        );

}


/*
|--------------------------------------------------------------------------
| CALCULATE ASSIGNMENT PERCENTAGE
|--------------------------------------------------------------------------
*/

$assignmentPercentage = 0;


if ($totalBookings > 0) {

    $assignmentPercentage =
        round(
            (
                $assignedBookings
                /
                $totalBookings
            ) * 100
        );

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

    Admin Dashboard |
    NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


body {

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fb;

    color: #172b4d;

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


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 250px;

    min-height: 100vh;

    padding: 25px;

}


/* =====================================================
   TOP BAR
===================================================== */

.topbar {

    background: white;

    border-radius: 16px;

    padding:
        20px 24px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 5px 25px
        rgba(
            16,
            24,
            40,
            .06
        );

    margin-bottom: 22px;

}


.page-title h1 {

    color: #003b70;

    font-size: 27px;

    line-height: 1.2;

}


.page-title p {

    margin-top: 6px;

    color: #667085;

    font-size: 13px;

}


.admin-profile {

    display: flex;

    align-items: center;

    gap: 11px;

}


.admin-avatar {

    width: 43px;

    height: 43px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #0066a6,
            #003b70
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

}


.admin-name {

    font-weight: 700;

    color: #172b4d;

    font-size: 13px;

}


.admin-role {

    margin-top: 3px;

    font-size: 10px;

    color: #98a2b3;

}


/* =====================================================
   STAT CARDS
===================================================== */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            1fr
        );

    gap: 16px;

    margin-bottom: 20px;

}


.stat-card {

    background: white;

    border-radius: 15px;

    padding: 18px;

    box-shadow:
        0 4px 20px
        rgba(
            16,
            24,
            40,
            .05
        );

    position: relative;

    overflow: hidden;

    transition:
        .2s ease;

}


.stat-card:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 8px 25px
        rgba(
            16,
            24,
            40,
            .09
        );

}


.stat-card::after {

    content: "";

    position: absolute;

    right: -25px;

    top: -25px;

    width: 80px;

    height: 80px;

    border-radius: 50%;

    background:
        rgba(
            0,
            102,
            166,
            .04
        );

}


.stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.stat-icon {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    background: #edf6ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 20px;

}


.stat-label {

    color: #667085;

    font-size: 11px;

    font-weight: 600;

}


.stat-number {

    margin-top: 14px;

    font-size: 28px;

    font-weight: 800;

    color: #003b70;

}


.stat-description {

    margin-top: 5px;

    color: #98a2b3;

    font-size: 10px;

}


.stat-revenue {

    background:
        linear-gradient(
            135deg,
            #003b70,
            #005fa3
        );

    color: white;

}


.stat-revenue
.stat-label,
.stat-revenue
.stat-number,
.stat-revenue
.stat-description {

    color: white;

}


.stat-revenue
.stat-icon {

    background:
        rgba(
            255,
            255,
            255,
            .14
        );

}


/* =====================================================
   QUICK ACTIONS
===================================================== */

.quick-actions {

    display: grid;

    grid-template-columns:
        repeat(
            6,
            1fr
        );

    gap: 10px;

    margin-bottom: 22px;

}


.quick-action {

    background: #003b70;

    color: white;

    text-decoration: none;

    padding:
        13px 10px;

    border-radius: 10px;

    text-align: center;

    font-size: 11px;

    font-weight: 700;

    transition:
        .2s ease;

}


.quick-action:hover {

    background: #005fa3;

    transform:
        translateY(-2px);

}


.quick-action-icon {

    display: block;

    font-size: 18px;

    margin-bottom: 6px;

}


/* =====================================================
   CONTENT GRID
===================================================== */

.content-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 2fr)
        minmax(300px, 1fr);

    gap: 20px;

}


/* =====================================================
   PANEL
===================================================== */

.panel {

    background: white;

    border-radius: 16px;

    box-shadow:
        0 4px 20px
        rgba(
            16,
            24,
            40,
            .05
        );

    overflow: hidden;

}


.panel-header {

    padding:
        19px 20px;

    border-bottom:
        1px solid
        #eaecf0;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.panel-header h2 {

    color: #003b70;

    font-size: 16px;

}


.view-all {

    color: #0066a6;

    text-decoration: none;

    font-size: 10px;

    font-weight: 700;

}


.view-all:hover {

    text-decoration: underline;

}


/* =====================================================
   BOOKING TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse:
        collapse;

    min-width: 720px;

}


th {

    background: #f8fafc;

    color: #667085;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding:
        13px 15px;

    text-align: left;

}


td {

    padding:
        13px 15px;

    border-top:
        1px solid
        #f0f2f5;

    font-size: 11px;

    color: #344054;

}


tbody tr:hover td {

    background: #f9fbfd;

}


.student-name {

    font-weight: 700;

    color: #172b4d;

}


.student-email {

    margin-top: 3px;

    color: #98a2b3;

    font-size: 9px;

}


.subject {

    font-weight: 700;

}


.badge {

    display: inline-flex;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

    white-space: nowrap;

}


.badge-paid {

    color: #137333;

    background: #e6f4ea;

}


.badge-pending {

    color: #b54708;

    background: #fff4e5;

}


.badge-assigned {

    color: #175cd3;

    background: #eff8ff;

}


.badge-unassigned {

    color: #667085;

    background: #f2f4f7;

}


.teacher {

    font-size: 10px;

    font-weight: 700;

}


/* =====================================================
   RIGHT SIDE
===================================================== */

.right-column {

    display: flex;

    flex-direction: column;

    gap: 20px;

}


/* =====================================================
   OVERVIEW
===================================================== */

.overview {

    padding: 20px;

}


.overview-row {

    margin-bottom: 18px;

}


.overview-title {

    display: flex;

    justify-content: space-between;

    font-size: 11px;

    margin-bottom: 8px;

}


.overview-title span:first-child {

    color: #344054;

    font-weight: 700;

}


.overview-title span:last-child {

    color: #667085;

}


.progress {

    width: 100%;

    height: 8px;

    border-radius: 20px;

    background: #eef2f6;

    overflow: hidden;

}


.progress-bar {

    height: 100%;

    border-radius: inherit;

    background:
        linear-gradient(
            90deg,
            #003b70,
            #36a9e1
        );

}


/* =====================================================
   PAYMENT LIST
===================================================== */

.payment-list {

    padding: 5px 20px 15px;

}


.payment-item {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        13px 0;

    border-bottom:
        1px solid
        #f0f2f5;

}


.payment-item:last-child {

    border-bottom: none;

}


.payment-icon {

    width: 37px;

    height: 37px;

    border-radius: 11px;

    background: #edf6ff;

    display: flex;

    align-items: center;

    justify-content: center;

}


.payment-info {

    flex: 1;

    min-width: 0;

}


.payment-student {

    font-size: 11px;

    font-weight: 700;

    color: #172b4d;

}


.payment-reference {

    margin-top: 3px;

    color: #98a2b3;

    font-size: 8px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.payment-amount {

    font-size: 11px;

    font-weight: 800;

    color: #003b70;

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty {

    text-align: center;

    padding: 35px 15px;

    color: #98a2b3;

    font-size: 11px;

}


/* =====================================================
   MOBILE BUTTON
===================================================== */

.mobile-menu {

    display: none;

    border: none;

    background: #003b70;

    color: white;

    width: 40px;

    height: 40px;

    border-radius: 10px;

    cursor: pointer;

    font-size: 19px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1250px) {

    .stats-grid {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }


    .quick-actions {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }

}


@media(max-width:1000px) {

    .content-grid {

        grid-template-columns: 1fr;

    }


    .right-column {

        display: grid;

        grid-template-columns:
            1fr 1fr;

    }

}


@media(max-width:800px) {

    .sidebar {

        width: 72px;

        padding:
            20px 8px;

    }


    .logo h2,
    .logo p,
    .menu-title,
    .sidebar .text {

        display: none;

    }


    .logo {

        border: none;

        padding-bottom: 20px;

    }


    .sidebar a {

        justify-content: center;

        padding: 13px 8px;

    }


    .menu-icon {

        font-size: 18px;

    }


    .main {

        margin-left: 72px;

        padding: 16px;

    }


    .stats-grid {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .topbar {

        padding: 16px;

    }


    .page-title h1 {

        font-size: 22px;

    }


    .admin-profile {

        display: none;

    }

}


@media(max-width:600px) {

    .stats-grid {

        grid-template-columns: 1fr;

    }


    .quick-actions {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .right-column {

        display: flex;

    }


    .topbar {

        align-items:
            flex-start;

    }


    .page-title p {

        line-height: 1.5;

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


        <div class="logo-icon">

            🎓

        </div>


        <h2>

            NISEL ONLINE

        </h2>


        <p>

            EDUCATION

        </p>


    </div>



    <div class="menu-title">

        Main Menu

    </div>



    <a
        href="dashboard.php"
        class="active"
    >

        <span class="menu-icon">
            🏠
        </span>

        <span class="text">
            Dashboard
        </span>

    </a>



    <a href="students.php">

        <span class="menu-icon">
            👨‍🎓
        </span>

        <span class="text">
            Students
        </span>

    </a>



    <a href="teachers.php">

        <span class="menu-icon">
            👨‍🏫
        </span>

        <span class="text">
            Teachers
        </span>

    </a>



    <a href="teacher_applications.php">

        <span class="menu-icon">
            📋
        </span>

        <span class="text">
            Teacher Applications
        </span>

    </a>



    <a href="booking.php">

        <span class="menu-icon">
            📚
        </span>

        <span class="text">
            Bookings
        </span>

    </a>



    <a href="payments.php">

        <span class="menu-icon">
            💳
        </span>

        <span class="text">
            Payments
        </span>

    </a>



    <a href="reports.php">

        <span class="menu-icon">
            📊
        </span>

        <span class="text">
            Reports
        </span>

    </a>



    <a href="schedules.php">

        <span class="menu-icon">
            📅
        </span>

        <span class="text">
            Schedules
        </span>

    </a>



    <a href="settings.php">

        <span class="menu-icon">
            ⚙️
        </span>

        <span class="text">
            Settings
        </span>

    </a>



    <a
        href="logout.php"
        class="logout"
    >

        <span class="menu-icon">
            🚪
        </span>

        <span class="text">
            Logout
        </span>

    </a>


</aside>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main">


    <!-- =================================================
         TOP BAR
    ================================================== -->

    <section class="topbar">


        <div class="page-title">


            <h1>

                Admin Dashboard

            </h1>


            <p>

                Welcome back to the
                NISEL ONLINE EDUCATION
                Administration Panel.

            </p>


        </div>



        <div class="admin-profile">


            <div class="admin-avatar">

                <?= h(
                    strtoupper(
                        substr(
                            $adminName,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>

                <div class="admin-name">

                    <?= h(
                        $adminName
                    ) ?>

                </div>


                <div class="admin-role">

                    System Administrator

                </div>

            </div>


        </div>


    </section>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="stats-grid">


        <!-- STUDENTS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Total Students

                </span>


                <div class="stat-icon">

                    👨‍🎓

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalStudents
                ) ?>

            </div>


            <div class="stat-description">

                Registered students

            </div>


        </div>



        <!-- TEACHERS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Total Teachers

                </span>


                <div class="stat-icon">

                    👨‍🏫

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalTeachers
                ) ?>

            </div>


            <div class="stat-description">

                Registered teachers

            </div>


        </div>



        <!-- BOOKINGS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Total Bookings

                </span>


                <div class="stat-icon">

                    📚

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalBookings
                ) ?>

            </div>


            <div class="stat-description">

                All student bookings

            </div>


        </div>



        <!-- REVENUE -->

        <div
            class="
                stat-card
                stat-revenue
            "
        >


            <div class="stat-top">


                <span class="stat-label">

                    Total Revenue

                </span>


                <div class="stat-icon">

                    💰

                </div>


            </div>


            <div class="stat-number">

                GHS
                <?= number_format(
                    $totalRevenue,
                    2
                ) ?>

            </div>


            <div class="stat-description">

                Successful payments

            </div>


        </div>



        <!-- PAYMENTS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Payments

                </span>


                <div class="stat-icon">

                    💳

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalPayments
                ) ?>

            </div>


            <div class="stat-description">

                Payment records

            </div>


        </div>



        <!-- APPLICATIONS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Pending Applications

                </span>


                <div class="stat-icon">

                    📋

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $pendingApplications
                ) ?>

            </div>


            <div class="stat-description">

                Awaiting approval

            </div>


        </div>



        <!-- PAID BOOKINGS -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Paid Bookings

                </span>


                <div class="stat-icon">

                    ✅

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $paidBookings
                ) ?>

            </div>


            <div class="stat-description">

                <?= $paymentPercentage ?>%
                payment completion

            </div>


        </div>



        <!-- UNASSIGNED -->

        <div class="stat-card">


            <div class="stat-top">


                <span class="stat-label">

                    Unassigned Bookings

                </span>


                <div class="stat-icon">

                    ⚠️

                </div>


            </div>


            <div class="stat-number">

                <?= number_format(
                    $unassignedBookings
                ) ?>

            </div>


            <div class="stat-description">

                Need teacher assignment

            </div>


        </div>


    </section>



    <!-- =================================================
         QUICK ACTIONS
    ================================================== -->

    <section class="quick-actions">


        <a
            href="students.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                👨‍🎓
            </span>

            Manage Students

        </a>



        <a
            href="teachers.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                👨‍🏫
            </span>

            Manage Teachers

        </a>



        <a
            href="teacher_applications.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                📋
            </span>

            Teacher Applications

        </a>



        <a
            href="booking.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                🎓
            </span>

            Assign Teachers

        </a>



        <a
            href="payments.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                💳
            </span>

            Payment Records

        </a>



        <a
            href="reports.php"
            class="quick-action"
        >

            <span class="quick-action-icon">
                📊
            </span>

            View Reports

        </a>


    </section>



    <!-- =================================================
         CONTENT
    ================================================== -->

    <section class="content-grid">


        <!-- =============================================
             RECENT BOOKINGS
        ============================================== -->

        <div class="panel">


            <div class="panel-header">


                <h2>

                    Recent Student Bookings

                </h2>


                <a
                    href="booking.php"
                    class="view-all"
                >

                    View All →

                </a>


            </div>



            <div class="table-wrapper">


                <?php if (
                    empty(
                        $recentBookings
                    )
                ): ?>


                    <div class="empty">

                        📚

                        <br><br>

                        No bookings found.

                    </div>


                <?php else: ?>


                    <table>


                        <thead>

                        <tr>

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
                                Payment
                            </th>

                            <th>
                                Teacher
                            </th>

                        </tr>

                        </thead>



                        <tbody>


                        <?php foreach (
                            $recentBookings
                            as $booking
                        ): ?>


                            <tr>


                                <!-- STUDENT -->

                                <td>


                                    <div
                                        class="
                                            student-name
                                        "
                                    >

                                        <?= h(
                                            $booking[
                                                'student_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div
                                        class="
                                            student-email
                                        "
                                    >

                                        <?= h(
                                            $booking[
                                                'email'
                                            ]
                                        ) ?>

                                    </div>


                                </td>



                                <!-- SUBJECT -->

                                <td>


                                    <span
                                        class="subject"
                                    >

                                        <?= h(
                                            $booking[
                                                'subjects'
                                            ]
                                        ) ?>

                                    </span>


                                </td>



                                <!-- CURRICULUM -->

                                <td>

                                    <?= h(
                                        $booking[
                                            'curriculum'
                                        ]
                                    ) ?>

                                </td>



                                <!-- PAYMENT -->

                                <td>


                                    <?php

                                    $paymentStatus =
                                        strtolower(
                                            trim(
                                                $booking[
                                                    'payment_status'
                                                ]
                                                ??
                                                ''
                                            )
                                        );

                                    ?>


                                    <?php if (
                                        $paymentStatus
                                        ===
                                        'paid'
                                        ||
                                        $paymentStatus
                                        ===
                                        'success'
                                    ): ?>


                                        <span
                                            class="
                                                badge
                                                badge-paid
                                            "
                                        >

                                            ✓ Paid

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                badge
                                                badge-pending
                                            "
                                        >

                                            ⏳

                                            <?= h(
                                                ucfirst(
                                                    $booking[
                                                        'payment_status'
                                                    ]
                                                    ??
                                                    'Pending'
                                                )
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


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
                                                badge-assigned
                                            "
                                        >

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
                                                badge-unassigned
                                            "
                                        >

                                            Unassigned

                                        </span>


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php endif; ?>


            </div>


        </div>



        <!-- =============================================
             RIGHT COLUMN
        ============================================== -->

        <div class="right-column">


            <!-- =========================================
                 BOOKING OVERVIEW
            ========================================== -->

            <div class="panel">


                <div class="panel-header">


                    <h2>

                        Booking Overview

                    </h2>


                    <span
                        style="
                            font-size:10px;
                            color:#98a2b3;
                        "
                    >

                        Current

                    </span>


                </div>



                <div class="overview">


                    <!-- PAYMENT -->

                    <div
                        class="overview-row"
                    >


                        <div
                            class="
                                overview-title
                            "
                        >

                            <span>

                                Paid Bookings

                            </span>


                            <span>

                                <?= $paymentPercentage ?>%

                            </span>

                        </div>


                        <div
                            class="progress"
                        >

                            <div
                                class="
                                    progress-bar
                                "
                                style="
                                    width:
                                    <?= min(
                                        $paymentPercentage,
                                        100
                                    ) ?>%;
                                "
                            ></div>

                        </div>


                    </div>



                    <!-- ASSIGNMENT -->

                    <div
                        class="overview-row"
                    >


                        <div
                            class="
                                overview-title
                            "
                        >

                            <span>

                                Teacher Assignment

                            </span>


                            <span>

                                <?= $assignmentPercentage ?>%

                            </span>

                        </div>


                        <div
                            class="progress"
                        >

                            <div
                                class="
                                    progress-bar
                                "
                                style="
                                    width:
                                    <?= min(
                                        $assignmentPercentage,
                                        100
                                    ) ?>%;
                                "
                            ></div>

                        </div>


                    </div>



                    <!-- UNASSIGNED -->

                    <div
                        class="
                            overview-title
                        "
                    >

                        <span>

                            Unassigned

                        </span>


                        <span
                            style="
                                color:#b54708;
                                font-weight:800;
                            "
                        >

                            <?= number_format(
                                $unassignedBookings
                            ) ?>

                        </span>


                    </div>


                </div>


            </div>



            <!-- =========================================
                 RECENT PAYMENTS
            ========================================== -->

            <div class="panel">


                <div class="panel-header">


                    <h2>

                        Recent Payments

                    </h2>


                    <a
                        href="payments.php"
                        class="view-all"
                    >

                        View All →

                    </a>


                </div>



                <div
                    class="payment-list"
                >


                    <?php if (
                        empty(
                            $recentPayments
                        )
                    ): ?>


                        <div class="empty">

                            No payment records.

                        </div>


                    <?php else: ?>


                        <?php foreach (
                            $recentPayments
                            as $payment
                        ): ?>


                            <div
                                class="
                                    payment-item
                                "
                            >


                                <div
                                    class="
                                        payment-icon
                                    "
                                >

                                    <?php

                                    $method =
                                        strtolower(
                                            $payment[
                                                'payment_method'
                                            ]
                                            ??
                                            ''
                                        );


                                    if (
                                        strpos(
                                            $method,
                                            'momo'
                                        ) !== false
                                    ) {

                                        echo "📱";

                                    }

                                    elseif (
                                        strpos(
                                            $method,
                                            'card'
                                        ) !== false
                                        ||
                                        strpos(
                                            $method,
                                            'visa'
                                        ) !== false
                                    ) {

                                        echo "💳";

                                    }

                                    else {

                                        echo "💰";

                                    }

                                    ?>

                                </div>



                                <div
                                    class="
                                        payment-info
                                    "
                                >


                                    <div
                                        class="
                                            payment-student
                                        "
                                    >

                                        <?= h(
                                            $payment[
                                                'student_name'
                                            ]
                                        ) ?>

                                    </div>


                                    <div
                                        class="
                                            payment-reference
                                        "
                                    >

                                        <?= h(
                                            $payment[
                                                'transaction_reference'
                                            ]
                                            ??
                                            $payment[
                                                'booking_reference'
                                            ]
                                        ) ?>

                                    </div>


                                </div>



                                <div
                                    class="
                                        payment-amount
                                    "
                                >

                                    GHS
                                    <?= number_format(
                                        (float)(
                                            $payment[
                                                'amount'
                                            ]
                                            ??
                                            0
                                        ),
                                        2
                                    ) ?>

                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </section>


</main>


</body>

</html>
