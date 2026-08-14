<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN PAYMENT MANAGEMENT
| PDO VERSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADMIN LOGIN
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
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$statusFilter =
    trim(
        $_GET['status']
        ?? ''
    );


$methodFilter =
    trim(
        $_GET['method']
        ?? ''
    );


$dateFrom =
    trim(
        $_GET['date_from']
        ?? ''
    );


$dateTo =
    trim(
        $_GET['date_to']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| BUILD WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            p.student_name LIKE ?
            OR p.email LIKE ?
            OR p.booking_reference LIKE ?
            OR p.transaction_reference LIKE ?
            OR p.payment_method LIKE ?
        )
    ";

    $searchValue =
        '%' .
        $search .
        '%';


    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

if ($statusFilter !== '') {

    if (
        strtolower(
            $statusFilter
        ) === 'paid'
    ) {

        $where[] = "
            LOWER(p.status) = 'paid'
        ";

    }

    elseif (
        strtolower(
            $statusFilter
        ) === 'pending'
    ) {

        $where[] = "
            LOWER(p.status) = 'pending'
        ";

    }

    elseif (
        strtolower(
            $statusFilter
        ) === 'failed'
    ) {

        $where[] = "
            LOWER(p.status) = 'failed'
        ";

    }

}


/*
|--------------------------------------------------------------------------
| PAYMENT METHOD
|--------------------------------------------------------------------------
*/

if ($methodFilter !== '') {

    $where[] =
        "p.payment_method = ?";

    $params[] =
        $methodFilter;

}


/*
|--------------------------------------------------------------------------
| DATE FROM
|--------------------------------------------------------------------------
|
| This assumes the payments table has a created_at column.
| If your table does not have created_at, the code below
| automatically uses id ordering instead.
|
|--------------------------------------------------------------------------
*/

$dateColumnExists = false;


try {

    $columnCheck =
        $pdo->query("
            SHOW COLUMNS
            FROM payments
            LIKE 'created_at'
        ");

    $dateColumnExists =
        (bool)$columnCheck->fetch();

}

catch (PDOException $e) {

    $dateColumnExists =
        false;

}


if (
    $dateColumnExists &&
    $dateFrom !== ''
) {

    $where[] =
        "DATE(p.created_at) >= ?";

    $params[] =
        $dateFrom;

}


if (
    $dateColumnExists &&
    $dateTo !== ''
) {

    $where[] =
        "DATE(p.created_at) <= ?";

    $params[] =
        $dateTo;

}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSQL = '';


if (!empty($where)) {

    $whereSQL =
        " WHERE " .
        implode(
            " AND ",
            $where
        );

}


/*
|--------------------------------------------------------------------------
| PAYMENT ORDER
|--------------------------------------------------------------------------
*/

$orderSQL =
    $dateColumnExists
        ? "p.created_at DESC, p.id DESC"
        : "p.id DESC";


/*
|--------------------------------------------------------------------------
| GET PAYMENTS
|--------------------------------------------------------------------------
*/

try {

    $sql = "

        SELECT

            p.id,

            p.booking_reference,

            p.student_name,

            p.email,

            p.amount,

            p.payment_method,

            p.transaction_reference,

            p.status

            " .
            (
                $dateColumnExists
                    ? ", p.created_at"
                    : ""
            ) . "

        FROM payments p

        $whereSQL

        ORDER BY
            $orderSQL

    ";


    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $payments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}

catch (PDOException $e) {

    die(
        "Unable to load payments: " .
        h(
            $e->getMessage()
        )
    );

}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalTransactions =
    count($payments);


$totalPaid =
    0;


$totalPending =
    0;


$totalFailed =
    0;


$totalRevenue =
    0;


$paidAmount =
    0;


foreach (
    $payments as $payment
) {

    $status =
        strtolower(
            trim(
                $payment['status']
                ?? ''
            )
        );


    $amount =
        (float)(
            $payment['amount']
            ?? 0
        );


    if (
        $status === 'paid' ||
        $status === 'success'
    ) {

        $totalPaid++;

        $paidAmount +=
            $amount;

    }

    elseif (
        $status === 'pending'
    ) {

        $totalPending++;

    }

    elseif (
        $status === 'failed'
    ) {

        $totalFailed++;

    }


    $totalRevenue +=
        $amount;

}


/*
|--------------------------------------------------------------------------
| PAYMENT METHODS
|--------------------------------------------------------------------------
*/

$methods = [];


foreach (
    $payments as $payment
) {

    $method =
        trim(
            $payment[
                'payment_method'
            ]
            ??
            ''
        );


    if (
        $method !== ''
    ) {

        $methods[$method] =
            true;

    }

}


$methods =
    array_keys(
        $methods
    );


sort(
    $methods
);


/*
|--------------------------------------------------------------------------
| GET TOTAL REVENUE FROM DATABASE
|--------------------------------------------------------------------------
|
| If filters are being used, statistics above represent
| the filtered results.
|
|--------------------------------------------------------------------------
*/

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

    Payments |
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
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            5,
            1fr
        );

    gap: 14px;

    margin-bottom: 22px;

}


.stat {

    background: white;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.stat-icon {

    width: 40px;

    height: 40px;

    border-radius: 11px;

    background: #eef4ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

}


.stat-number {

    margin-top: 13px;

    font-size: 23px;

    font-weight: 800;

    color: #003366;

}


.stat-label {

    margin-top: 3px;

    font-size: 10px;

    color: #667085;

}


/* =====================================================
   FILTER
===================================================== */

.filter-card {

    background: white;

    border-radius: 16px;

    padding: 21px;

    margin-bottom: 22px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

}


.filter-title {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 16px;

}


.filter-title h2 {

    margin: 0;

    color: #003366;

    font-size: 17px;

}


.filter-title span {

    color: #98a2b3;

    font-size: 11px;

}


.filters {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

}


input,
select {

    width: 100%;

    padding: 12px 13px;

    border:
        1px solid
        #d0d5dd;

    border-radius: 9px;

    background: white;

    color: #344054;

    outline: none;

    font-size: 12px;

}


input:focus,
select:focus {

    border-color: #0066aa;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            102,
            170,
            .08
        );

}


.filter-btn {

    border: none;

    background: #003366;

    color: white;

    padding:
        0 18px;

    border-radius: 9px;

    font-weight: 700;

    cursor: pointer;

}


.filter-btn:hover {

    background: #0055a5;

}


.clear-btn {

    display: inline-flex;

    margin-top: 12px;

    padding:
        9px 14px;

    border-radius: 8px;

    background: #f2f4f7;

    color: #344054;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

}


/* =====================================================
   PAYMENT TABLE
===================================================== */

.table-card {

    background: white;

    border-radius: 16px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

    overflow: hidden;

}


.table-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 20px;

    border-bottom:
        1px solid
        #eaecf0;

}


.table-header h2 {

    margin: 0;

    color: #003366;

    font-size: 17px;

}


.result-count {

    color: #667085;

    font-size: 11px;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse:
        collapse;

    min-width: 1050px;

}


th {

    background: #f8fafc;

    color: #667085;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding:
        14px 15px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding:
        15px;

    border-top:
        1px solid
        #f0f2f5;

    font-size: 11px;

    color: #344054;

    vertical-align: middle;

}


tr:hover td {

    background: #fafcff;

}


/* =====================================================
   STUDENT
===================================================== */

.student {

    display: flex;

    align-items: center;

    gap: 10px;

}


.avatar {

    width: 36px;

    height: 36px;

    flex-shrink: 0;

    border-radius: 50%;

    background: #e8f2ff;

    color: #003366;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 800;

    font-size: 11px;

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


/* =====================================================
   REFERENCE
===================================================== */

.reference {

    font-family:
        monospace;

    font-size: 10px;

    color: #003366;

}


.transaction {

    font-family:
        monospace;

    font-size: 9px;

    color: #667085;

    max-width: 180px;

    word-break: break-all;

}


/* =====================================================
   AMOUNT
===================================================== */

.amount {

    font-size: 13px;

    font-weight: 800;

    color: #003366;

    white-space: nowrap;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    padding:
        5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

    white-space: nowrap;

}


.badge.paid {

    background: #e7f7ed;

    color: #16803d;

}


.badge.pending {

    background: #fff4e5;

    color: #b54708;

}


.badge.failed {

    background: #fef3f2;

    color: #b42318;

}


.badge.other {

    background: #f2f4f7;

    color: #667085;

}


/* =====================================================
   PAYMENT METHOD
===================================================== */

.method {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    color: #344054;

    font-weight: 600;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 70px 20px;

}


.empty-icon {

    font-size: 48px;

    margin-bottom: 12px;

}


.empty h3 {

    margin: 0;

    color: #003366;

}


.empty p {

    color: #98a2b3;

    font-size: 12px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    padding: 20px;

    color: #98a2b3;

    font-size: 10px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:1250px) {

    .stats {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }


    .filters {

        grid-template-columns:
            repeat(
                3,
                1fr
            );

    }

}


@media(max-width:900px) {

    .sidebar {

        width: 70px;

        padding:
            20px 8px;

    }


    .brand h2,
    .brand p,
    .nav-text {

        display: none;

    }


    .brand {

        border: none;

    }


    .nav a {

        justify-content:
            center;

    }


    .main {

        margin-left: 70px;

        padding: 18px;

    }


    .header {

        align-items:
            flex-start;

        flex-direction:
            column;

        gap: 15px;

    }

}


@media(max-width:650px) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .filters {

        grid-template-columns: 1fr;

    }

}


@media(max-width:450px) {

    .stats {

        grid-template-columns: 1fr;

    }


    .main {

        padding: 12px;

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



    <a href="bookings.php">

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
     MAIN
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="header">


        <div>

            <h1>

                💳 Payment Management

            </h1>


            <p>

                Monitor student payments,
                transactions and payment status.

            </p>

        </div>


        <div class="admin">


            <div class="admin-icon">

                👤

            </div>


            <div>


                <strong>

                    <?= h(
                        $_SESSION[
                            'admin_name'
                        ]
                        ??
                        'Administrator'
                    ) ?>

                </strong>


                <div
                    style="
                        color:#98a2b3;
                        font-size:10px;
                        margin-top:3px;
                    "
                >

                    Administrator

                </div>


            </div>


        </div>


    </div>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat">

            <div class="stat-top">

                <strong>
                    Revenue
                </strong>

                <div class="stat-icon">
                    💰
                </div>

            </div>


            <div class="stat-number">

                GHS
                <?= number_format(
                    $paidAmount,
                    2
                ) ?>

            </div>


            <div class="stat-label">

                Successful payments

            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Paid
                </strong>

                <div class="stat-icon">
                    ✓
                </div>

            </div>


            <div class="stat-number">

                <?= $totalPaid ?>

            </div>


            <div class="stat-label">

                Successful transactions

            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Pending
                </strong>

                <div class="stat-icon">
                    ⏳
                </div>

            </div>


            <div class="stat-number">

                <?= $totalPending ?>

            </div>


            <div class="stat-label">

                Awaiting payment

            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Failed
                </strong>

                <div class="stat-icon">
                    ⚠️
                </div>

            </div>


            <div class="stat-number">

                <?= $totalFailed ?>

            </div>


            <div class="stat-label">

                Failed transactions

            </div>

        </div>



        <div class="stat">

            <div class="stat-top">

                <strong>
                    Transactions
                </strong>

                <div class="stat-icon">
                    📊
                </div>

            </div>


            <div class="stat-number">

                <?= $totalTransactions ?>

            </div>


            <div class="stat-label">

                Records displayed

            </div>

        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <section class="filter-card">


        <div class="filter-title">


            <h2>

                🔎 Search Payments

            </h2>


            <span>

                Find a transaction quickly

            </span>


        </div>



        <form
            method="GET"
            action=""
        >


            <div class="filters">


                <input
                    type="text"
                    name="search"
                    value="<?= h(
                        $search
                    ) ?>"
                    placeholder="Student, booking reference, transaction reference..."
                >



                <select
                    name="status"
                >

                    <option value="">

                        All Statuses

                    </option>


                    <option
                        value="paid"
                        <?= strtolower(
                            $statusFilter
                        ) === 'paid'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Paid

                    </option>


                    <option
                        value="pending"
                        <?= strtolower(
                            $statusFilter
                        ) === 'pending'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Pending

                    </option>


                    <option
                        value="failed"
                        <?= strtolower(
                            $statusFilter
                        ) === 'failed'
                            ? 'selected'
                            : ''
                        ?>
                    >

                        Failed

                    </option>


                </select>



                <select
                    name="method"
                >

                    <option value="">

                        All Methods

                    </option>


                    <?php foreach (
                        $methods
                        as $method
                    ): ?>

                        <option
                            value="<?= h(
                                $method
                            ) ?>"
                            <?= (
                                $methodFilter
                                ===
                                $method
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= h(
                                ucfirst(
                                    $method
                                )
                            ) ?>

                        </option>

                    <?php endforeach; ?>


                </select>



                <input
                    type="date"
                    name="date_from"
                    value="<?= h(
                        $dateFrom
                    ) ?>"
                >



                <input
                    type="date"
                    name="date_to"
                    value="<?= h(
                        $dateTo
                    ) ?>"
                >



                <button
                    type="submit"
                    class="filter-btn"
                >

                    Filter

                </button>


            </div>


        </form>



        <?php if (
            $search !== ''
            ||
            $statusFilter !== ''
            ||
            $methodFilter !== ''
            ||
            $dateFrom !== ''
            ||
            $dateTo !== ''
        ): ?>


            <a
                href="payments.php"
                class="clear-btn"
            >

                ✕ Clear Filters

            </a>


        <?php endif; ?>


    </section>



    <!-- =================================================
         PAYMENT TABLE
    ================================================== -->

    <section class="table-card">


        <div class="table-header">


            <h2>

                💳 Payment Transactions

            </h2>


            <div
                class="result-count"
            >

                <?= $totalTransactions ?>

                transaction(s)

            </div>


        </div>



        <?php if (
            empty($payments)
        ): ?>


            <div class="empty">


                <div class="empty-icon">

                    💳

                </div>


                <h3>

                    No payment records found

                </h3>


                <p>

                    There are no payment transactions
                    matching your search.

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
                            Student
                        </th>

                        <th>
                            Booking Reference
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Payment Method
                        </th>

                        <th>
                            Transaction Reference
                        </th>

                        <th>
                            Status
                        </th>

                        <?php if (
                            $dateColumnExists
                        ): ?>

                            <th>
                                Date
                            </th>

                        <?php endif; ?>

                    </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $payments
                        as $payment
                    ): ?>


                        <?php


                        $studentName =
                            trim(
                                $payment[
                                    'student_name'
                                ]
                                ??
                                'Student'
                            );


                        $words =
                            preg_split(
                                '/\s+/',
                                $studentName
                            );


                        $initials = '';


                        foreach (
                            $words
                            as $word
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
                                strlen(
                                    $initials
                                ) >= 2
                            ) {
                                break;
                            }

                        }


                        if (
                            $initials === ''
                        ) {

                            $initials =
                                'ST';

                        }


                        $status =
                            strtolower(
                                trim(
                                    $payment[
                                        'status'
                                    ]
                                    ??
                                    ''
                                )
                            );


                        if (
                            $status ===
                            'paid'
                            ||
                            $status ===
                            'success'
                        ) {

                            $statusClass =
                                'paid';

                            $statusText =
                                '✓ PAID';

                        }

                        elseif (
                            $status ===
                            'pending'
                        ) {

                            $statusClass =
                                'pending';

                            $statusText =
                                '⏳ PENDING';

                        }

                        elseif (
                            $status ===
                            'failed'
                        ) {

                            $statusClass =
                                'failed';

                            $statusText =
                                '✕ FAILED';

                        }

                        else {

                            $statusClass =
                                'other';

                            $statusText =
                                strtoupper(
                                    $status
                                    ?: 'UNKNOWN'
                                );

                        }


                        ?>


                        <tr>


                            <!-- STUDENT -->

                            <td>


                                <div
                                    class="student"
                                >


                                    <div
                                        class="avatar"
                                    >

                                        <?= h(
                                            $initials
                                        ) ?>

                                    </div>


                                    <div>


                                        <div
                                            class="
                                                student-name
                                            "
                                        >

                                            <?= h(
                                                $studentName
                                            ) ?>

                                        </div>


                                        <div
                                            class="
                                                student-email
                                            "
                                        >

                                            <?= h(
                                                $payment[
                                                    'email'
                                                ]
                                            ) ?>

                                        </div>


                                    </div>


                                </div>


                            </td>



                            <!-- BOOKING REFERENCE -->

                            <td>


                                <span
                                    class="reference"
                                >

                                    <?= h(
                                        $payment[
                                            'booking_reference'
                                        ]
                                    ) ?>

                                </span>


                            </td>



                            <!-- AMOUNT -->

                            <td>


                                <span
                                    class="amount"
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

                                </span>


                            </td>



                            <!-- METHOD -->

                            <td>


                                <span
                                    class="method"
                                >


                                    <?php

                                    $method =
                                        strtolower(
                                            trim(
                                                $payment[
                                                    'payment_method'
                                                ]
                                                ??
                                                ''
                                            )
                                        );


                                    if (
                                        strpos(
                                            $method,
                                            'momo'
                                        ) !== false
                                    ) {

                                        echo '📱';

                                    }

                                    elseif (
                                        strpos(
                                            $method,
                                            'visa'
                                        ) !== false
                                        ||
                                        strpos(
                                            $method,
                                            'card'
                                        ) !== false
                                    ) {

                                        echo '💳';

                                    }

                                    else {

                                        echo '💰';

                                    }

                                    ?>


                                    <?= h(
                                        $payment[
                                            'payment_method'
                                        ]
                                        ??
                                        'Unknown'
                                    ) ?>


                                </span>


                            </td>



                            <!-- TRANSACTION -->

                            <td>


                                <?php if (
                                    !empty(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    )
                                ): ?>


                                    <span
                                        class="
                                            transaction
                                        "
                                    >

                                        <?= h(
                                            $payment[
                                                'transaction_reference'
                                            ]
                                        ) ?>

                                    </span>


                                <?php else: ?>


                                    <span
                                        style="
                                            color:#98a2b3;
                                        "
                                    >

                                        Not available

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- STATUS -->

                            <td>


                                <span
                                    class="
                                        badge
                                        <?= h(
                                            $statusClass
                                        ) ?>
                                    "
                                >

                                    <?= h(
                                        $statusText
                                    ) ?>

                                </span>


                            </td>



                            <!-- DATE -->

                            <?php if (
                                $dateColumnExists
                            ): ?>


                                <td>


                                    <?php if (
                                        !empty(
                                            $payment[
                                                'created_at'
                                            ]
                                        )
                                    ): ?>


                                        <div
                                            style="
                                                font-weight:700;
                                                color:#344054;
                                            "
                                        >

                                            <?= h(
                                                date(
                                                    'd M Y',
                                                    strtotime(
                                                        $payment[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </div>


                                        <div
                                            style="
                                                color:#98a2b3;
                                                font-size:9px;
                                                margin-top:3px;
                                            "
                                        >

                                            <?= h(
                                                date(
                                                    'h:i A',
                                                    strtotime(
                                                        $payment[
                                                            'created_at'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                        </div>


                                    <?php else: ?>


                                        <span
                                            style="
                                                color:#98a2b3;
                                            "
                                        >

                                            N/A

                                        </span>


                                    <?php endif; ?>


                                </td>


                            <?php endif; ?>


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
        Administrator Payment Management

    </div>


</main>


</body>

</html>
