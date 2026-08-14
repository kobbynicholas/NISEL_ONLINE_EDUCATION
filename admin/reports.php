<?php

session_start();

require "../config/db.php";

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
| PDO ERROR MODE
|--------------------------------------------------------------------------
*/

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
| ADMIN NAME
|--------------------------------------------------------------------------
*/

$adminName =
    $_SESSION['admin_name']
    ?? 'Administrator';


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$startDate =
    trim($_GET['start_date'] ?? '');

$endDate =
    trim($_GET['end_date'] ?? '');

$curriculum =
    trim($_GET['curriculum'] ?? '');

$paymentStatus =
    trim($_GET['payment_status'] ?? '');

$assignmentStatus =
    trim($_GET['assignment_status'] ?? '');


/*
|--------------------------------------------------------------------------
| BUILD BOOKING FILTER
|--------------------------------------------------------------------------
*/

$where = [];

$params = [];


/*
|--------------------------------------------------------------------------
| DATE FILTER
|--------------------------------------------------------------------------
*/

if ($startDate !== '') {

    $where[] =
        "DATE(b.lesson_date) >= :start_date";

    $params[':start_date'] =
        $startDate;

}


if ($endDate !== '') {

    $where[] =
        "DATE(b.lesson_date) <= :end_date";

    $params[':end_date'] =
        $endDate;

}


/*
|--------------------------------------------------------------------------
| CURRICULUM
|--------------------------------------------------------------------------
*/

if ($curriculum !== '') {

    $where[] =
        "LOWER(b.curriculum) = LOWER(:curriculum)";

    $params[':curriculum'] =
        $curriculum;

}


/*
|--------------------------------------------------------------------------
| PAYMENT
|--------------------------------------------------------------------------
*/

if ($paymentStatus !== '') {

    $where[] =
        "LOWER(b.payment_status) = LOWER(:payment_status)";

    $params[':payment_status'] =
        $paymentStatus;

}


/*
|--------------------------------------------------------------------------
| ASSIGNMENT
|--------------------------------------------------------------------------
*/

if ($assignmentStatus === 'assigned') {

    $where[] = "
        b.teacher_id IS NOT NULL
        AND b.teacher_id <> ''
    ";

}


if ($assignmentStatus === 'unassigned') {

    $where[] = "
        (
            b.teacher_id IS NULL
            OR b.teacher_id = ''
        )
    ";

}


/*
|--------------------------------------------------------------------------
| WHERE SQL
|--------------------------------------------------------------------------
*/

$whereSQL = '';

if (!empty($where)) {

    $whereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $where
        );

}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$totalBookings = 0;

$paidBookings = 0;

$pendingBookings = 0;

$assignedBookings = 0;

$unassignedBookings = 0;

$totalRevenue = 0;

$totalStudents = 0;

$totalTeachers = 0;

$totalPayments = 0;

$completedLessons = 0;

$scheduledLessons = 0;

$cancelledLessons = 0;

$curriculumData = [];

$subjectData = [];

$recentBookings = [];

$recentPayments = [];


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
        WHERE LOWER(status) = 'active'
    ");

    $totalTeachers =
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
    | TOTAL BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $whereSQL
        ");

    $stmt->execute($params);

    $totalBookings =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PAID BOOKINGS
    |--------------------------------------------------------------------------
    */

    $paidWhere =
        $where;

    $paidWhere[] = "
        (
            LOWER(b.payment_status) = 'paid'
            OR
            LOWER(b.payment_status) = 'success'
        )
    ";

    $paidWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $paidWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $paidWhereSQL
        ");

    $stmt->execute($params);

    $paidBookings =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | PENDING BOOKINGS
    |--------------------------------------------------------------------------
    */

    $pendingWhere =
        $where;

    $pendingWhere[] = "
        LOWER(b.payment_status) = 'pending'
    ";

    $pendingWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $pendingWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $pendingWhereSQL
        ");

    $stmt->execute($params);

    $pendingBookings =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | ASSIGNED BOOKINGS
    |--------------------------------------------------------------------------
    */

    $assignedWhere =
        $where;

    $assignedWhere[] = "
        b.teacher_id IS NOT NULL
        AND b.teacher_id <> ''
    ";

    $assignedWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $assignedWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $assignedWhereSQL
        ");

    $stmt->execute($params);

    $assignedBookings =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | UNASSIGNED BOOKINGS
    |--------------------------------------------------------------------------
    */

    $unassignedWhere =
        $where;

    $unassignedWhere[] = "
        (
            b.teacher_id IS NULL
            OR
            b.teacher_id = ''
        )
    ";

    $unassignedWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $unassignedWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $unassignedWhereSQL
        ");

    $stmt->execute($params);

    $unassignedBookings =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | REVENUE
    |--------------------------------------------------------------------------
    */

    $revenueWhere =
        $where;

    $revenueWhere[] = "
        (
            LOWER(b.payment_status) = 'paid'
            OR
            LOWER(b.payment_status) = 'success'
        )
    ";

    $revenueWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $revenueWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT
                COALESCE(
                    SUM(b.amount),
                    0
                )

            FROM bookings b

            $revenueWhereSQL
        ");

    $stmt->execute($params);

    $totalRevenue =
        (float)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | COMPLETED LESSONS
    |--------------------------------------------------------------------------
    */

    $completedWhere =
        $where;

    $completedWhere[] = "
        LOWER(b.lesson_status) = 'completed'
    ";

    $completedWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $completedWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $completedWhereSQL
        ");

    $stmt->execute($params);

    $completedLessons =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | SCHEDULED LESSONS
    |--------------------------------------------------------------------------
    */

    $scheduledWhere =
        $where;

    $scheduledWhere[] = "
        LOWER(b.lesson_status) = 'scheduled'
    ";

    $scheduledWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $scheduledWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $scheduledWhereSQL
        ");

    $stmt->execute($params);

    $scheduledLessons =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | CANCELLED LESSONS
    |--------------------------------------------------------------------------
    */

    $cancelledWhere =
        $where;

    $cancelledWhere[] = "
        LOWER(b.lesson_status) = 'cancelled'
    ";

    $cancelledWhereSQL =
        ' WHERE '
        .
        implode(
            ' AND ',
            $cancelledWhere
        );


    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings b
            $cancelledWhereSQL
        ");

    $stmt->execute($params);

    $cancelledLessons =
        (int)$stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | CURRICULUM BREAKDOWN
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare("
            SELECT

                COALESCE(
                    NULLIF(
                        TRIM(b.curriculum),
                        ''
                    ),
                    'Not Specified'
                ) AS curriculum_name,

                COUNT(*) AS total

            FROM bookings b

            $whereSQL

            GROUP BY curriculum_name

            ORDER BY total DESC
        ");

    $stmt->execute($params);

    $curriculumData =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | SUBJECT BREAKDOWN
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare("
            SELECT

                COALESCE(
                    NULLIF(
                        TRIM(b.subjects),
                        ''
                    ),
                    'Not Specified'
                ) AS subject_name,

                COUNT(*) AS total

            FROM bookings b

            $whereSQL

            GROUP BY subject_name

            ORDER BY total DESC

            LIMIT 10
        ");

    $stmt->execute($params);

    $subjectData =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | RECENT BOOKINGS
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare("
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

                b.assignment_status,

                b.lesson_date,

                b.lesson_time,

                b.lesson_status

            FROM bookings b

            $whereSQL

            ORDER BY b.id DESC

            LIMIT 15
        ");

    $stmt->execute($params);

    $recentBookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | RECENT PAYMENTS
    |--------------------------------------------------------------------------
    |
    | Payments table is intentionally not filtered by
    | booking date because it contains its own transaction
    | records and may not have lesson_date.
    |
    */

    $paymentFilter = [];

    $paymentParams = [];


    if ($startDate !== '') {

        $paymentFilter[] =
            "DATE(p.created_at) >= :p_start";

        $paymentParams[':p_start'] =
            $startDate;

    }


    if ($endDate !== '') {

        $paymentFilter[] =
            "DATE(p.created_at) <= :p_end";

        $paymentParams[':p_end'] =
            $endDate;

    }


    if ($paymentStatus !== '') {

        $paymentFilter[] =
            "LOWER(p.status) = LOWER(:p_status)";

        $paymentParams[':p_status'] =
            $paymentStatus;

    }


    $paymentWhereSQL = '';

    if (!empty($paymentFilter)) {

        $paymentWhereSQL =
            ' WHERE '
            .
            implode(
                ' AND ',
                $paymentFilter
            );

    }


    /*
    |--------------------------------------------------------------------------
    | RECENT PAYMENTS
    |--------------------------------------------------------------------------
    */

    $stmt =
        $pdo->prepare("
            SELECT

                p.id,

                p.booking_reference,

                p.student_name,

                p.email,

                p.amount,

                p.payment_method,

                p.transaction_reference,

                p.status

            FROM payments p

            $paymentWhereSQL

            ORDER BY p.id DESC

            LIMIT 10
        ");

    $stmt->execute(
        $paymentParams
    );

    $recentPayments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


}
catch (PDOException $e) {

    $databaseError =
        $e->getMessage();

}


/*
|--------------------------------------------------------------------------
| PERCENTAGES
|--------------------------------------------------------------------------
*/

$paidPercentage = 0;

$assignedPercentage = 0;

$completedPercentage = 0;


if ($totalBookings > 0) {

    $paidPercentage =
        round(
            (
                $paidBookings
                /
                $totalBookings
            ) * 100
        );


    $assignedPercentage =
        round(
            (
                $assignedBookings
                /
                $totalBookings
            ) * 100
        );


    $completedPercentage =
        round(
            (
                $completedLessons
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

NISEL Admin Reports

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

    width: 245px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70,
            #002b55
        );

    color: white;

    padding: 24px 14px;

    overflow-y: auto;

}


.logo {

    text-align: center;

    padding:
        10px 5px 25px;

    margin-bottom: 20px;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

}


.logo-icon {

    width: 50px;

    height: 50px;

    margin: 0 auto 10px;

    border-radius: 14px;

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

    font-size: 25px;

}


.logo h2 {

    font-size: 18px;

}


.logo small {

    display: block;

    margin-top: 6px;

    color:
        rgba(
            255,
            255,
            255,
            .55
        );

    font-size: 9px;

    letter-spacing: 1.8px;

}


.menu-title {

    color:
        rgba(
            255,
            255,
            255,
            .4
        );

    font-size: 9px;

    letter-spacing: 1.5px;

    text-transform: uppercase;

    padding:
        0 12px 8px;

}


.sidebar a {

    display: flex;

    align-items: center;

    gap: 11px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration: none;

    padding:
        12px;

    margin-bottom: 4px;

    border-radius: 9px;

    font-size: 12px;

    transition: .2s;

}


.sidebar a:hover {

    background:
        rgba(
            255,
            255,
            255,
            .1
        );

    color: white;

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

    font-weight: bold;

    box-shadow:
        inset 3px 0 #38bdf8;

}


.icon {

    width: 22px;

    text-align: center;

    font-size: 16px;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 245px;

    padding: 25px;

}


/* =====================================================
   HEADER
===================================================== */

.header {

    background: white;

    border-radius: 16px;

    padding:
        22px 25px;

    margin-bottom: 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

}


.header h1 {

    color: #003b70;

    font-size: 26px;

}


.header p {

    margin-top: 5px;

    color: #667085;

    font-size: 12px;

}


.admin-badge {

    background: #edf6ff;

    color: #005fa3;

    border-radius: 20px;

    padding:
        9px 14px;

    font-size: 11px;

    font-weight: bold;

}


/* =====================================================
   ERROR
===================================================== */

.error-box {

    background: #fee2e2;

    color: #991b1b;

    border:
        1px solid #fecaca;

    padding: 15px;

    border-radius: 10px;

    margin-bottom: 20px;

}


/* =====================================================
   FILTERS
===================================================== */

.filter-card {

    background: white;

    border-radius: 15px;

    padding: 20px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

}


.filter-title {

    color: #003b70;

    font-weight: 800;

    font-size: 15px;

    margin-bottom: 15px;

}


.filters {

    display: grid;

    grid-template-columns:
        1fr
        1fr
        1fr
        1fr
        1fr
        auto
        auto;

    gap: 10px;

}


.filters label {

    display: block;

    font-size: 9px;

    color: #667085;

    font-weight: bold;

    margin-bottom: 5px;

    text-transform: uppercase;

}


.filters input,
.filters select {

    width: 100%;

    padding:
        10px;

    border:
        1px solid #d0d5dd;

    border-radius: 8px;

    background: white;

    outline: none;

    font-size: 11px;

}


.filters input:focus,
.filters select:focus {

    border-color: #0077b6;

}


.filter-btn {

    border: none;

    padding:
        10px 16px;

    border-radius: 8px;

    background: #003b70;

    color: white;

    cursor: pointer;

    font-weight: bold;

}


.clear-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    padding:
        10px 15px;

    background: #f2f4f7;

    color: #475467;

    text-decoration: none;

    border-radius: 8px;

    font-size: 11px;

    font-weight: bold;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            1fr
        );

    gap: 15px;

    margin-bottom: 20px;

}


.stat {

    background: white;

    padding: 18px;

    border-radius: 14px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

    position: relative;

    overflow: hidden;

}


.stat::after {

    content: "";

    position: absolute;

    width: 65px;

    height: 65px;

    border-radius: 50%;

    right: -20px;

    top: -20px;

    background:
        rgba(
            0,
            102,
            166,
            .04
        );

}


.stat-icon {

    font-size: 21px;

    margin-bottom: 8px;

}


.stat-label {

    color: #667085;

    font-size: 10px;

    font-weight: 700;

}


.stat-number {

    color: #003b70;

    font-size: 25px;

    font-weight: 800;

    margin-top: 7px;

}


.stat-sub {

    color: #98a2b3;

    font-size: 9px;

    margin-top: 4px;

}


.stat.revenue {

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0069a8
        );

}


.stat.revenue
.stat-label,
.stat.revenue
.stat-number,
.stat.revenue
.stat-sub {

    color: white;

}


/* =====================================================
   PROGRESS CARDS
===================================================== */

.progress-grid {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap: 15px;

    margin-bottom: 20px;

}


.progress-card {

    background: white;

    border-radius: 14px;

    padding: 18px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

}


.progress-head {

    display: flex;

    justify-content: space-between;

    margin-bottom: 9px;

    font-size: 11px;

    font-weight: bold;

}


.progress-head span:last-child {

    color: #003b70;

}


.progress {

    height: 8px;

    background: #edf1f5;

    border-radius: 20px;

    overflow: hidden;

}


.progress-bar {

    height: 100%;

    background:
        linear-gradient(
            90deg,
            #003b70,
            #36a9e1
        );

    border-radius: 20px;

}


/* =====================================================
   TWO COLUMN
===================================================== */

.two-column {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 20px;

    margin-bottom: 20px;

}


.panel {

    background: white;

    border-radius: 15px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

    overflow: hidden;

}


.panel-header {

    padding:
        17px 20px;

    border-bottom:
        1px solid #eaecf0;

    display: flex;

    align-items: center;

    justify-content: space-between;

}


.panel-header h2 {

    font-size: 15px;

    color: #003b70;

}


.panel-header span {

    font-size: 9px;

    color: #98a2b3;

}


/* =====================================================
   BREAKDOWN
===================================================== */

.breakdown {

    padding:
        12px 20px 20px;

}


.breakdown-item {

    padding:
        12px 0;

    border-bottom:
        1px solid #f0f2f5;

}


.breakdown-item:last-child {

    border-bottom: none;

}


.breakdown-top {

    display: flex;

    justify-content: space-between;

    font-size: 11px;

    margin-bottom: 7px;

}


.breakdown-name {

    font-weight: 700;

    color: #344054;

}


.breakdown-count {

    color: #003b70;

    font-weight: 800;

}


.small-progress {

    height: 6px;

    background: #edf1f5;

    border-radius: 10px;

    overflow: hidden;

}


.small-progress div {

    height: 100%;

    background: #0077b6;

}


/* =====================================================
   TABLE
===================================================== */

.table-panel {

    background: white;

    border-radius: 15px;

    overflow: hidden;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

    margin-bottom: 20px;

}


.table-wrap {

    overflow-x: auto;

}


table {

    width: 100%;

    min-width: 900px;

    border-collapse:
        collapse;

}


th {

    background: #f8fafc;

    color: #667085;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding:
        12px 14px;

    text-align: left;

}


td {

    padding:
        13px 14px;

    border-top:
        1px solid #f0f2f5;

    font-size: 10px;

    color: #344054;

}


tr:hover td {

    background: #fafcff;

}


.student {

    font-weight: 800;

    color: #172b4d;

}


.reference {

    margin-top: 3px;

    color: #98a2b3;

    font-size: 8px;

}


.badge {

    display: inline-flex;

    padding:
        5px 8px;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 800;

    white-space: nowrap;

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

    background: #dbeafe;

    color: #1d4ed8;

}


.badge.unassigned {

    background: #f2f4f7;

    color: #667085;

}


.badge.completed {

    background: #dcfce7;

    color: #166534;

}


.badge.scheduled {

    background: #dbeafe;

    color: #1d4ed8;

}


.badge.cancelled {

    background: #fee2e2;

    color: #991b1b;

}


/* =====================================================
   PAYMENT TABLE
===================================================== */

.payment-method {

    font-weight: 700;

}


.amount {

    font-weight: 800;

    color: #003b70;

}


/* =====================================================
   PRINT
===================================================== */

.print-btn {

    border: none;

    background: #003b70;

    color: white;

    padding:
        10px 15px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: bold;

    font-size: 10px;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 35px;

    color: #98a2b3;

    font-size: 11px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    color: #98a2b3;

    font-size: 10px;

    padding:
        10px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:1200px) {

    .stats {

        grid-template-columns:
            repeat(
                2,
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

        width: 75px;

    }


    .logo h2,
    .logo small,
    .menu-title {

        display: none;

    }


    .sidebar a {

        justify-content: center;

        font-size: 0;

    }


    .sidebar a .icon {

        font-size: 18px;

    }


    .main {

        margin-left: 75px;

    }


    .two-column,
    .progress-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width:650px) {

    .main {

        padding: 12px;

    }


    .header {

        align-items:
            flex-start;

        gap: 12px;

        flex-direction: column;

    }


    .stats {

        grid-template-columns: 1fr;

    }


    .filters {

        grid-template-columns: 1fr;

    }

}


/* =====================================================
   PRINT STYLE
===================================================== */

@media print {

    .sidebar,
    .filter-card,
    .admin-badge,
    .print-btn {

        display: none !important;

    }


    .main {

        margin: 0;

        padding: 10px;

    }


    body {

        background: white;

    }


    .header {

        box-shadow: none;

        border: 1px solid #ddd;

    }


    .stat,
    .panel,
    .table-panel {

        box-shadow: none;

        border:
            1px solid #ddd;

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

    <section class="header">


        <div>

            <h1>

                📊 Reports & Analytics

            </h1>


            <p>

                NISEL ONLINE EDUCATION
                administrative performance report.

            </p>

        </div>


        <div>

            <span class="admin-badge">

                👤
                <?= h($adminName) ?>

            </span>

        </div>


    </section>



    <!-- DATABASE ERROR -->

    <?php if (
        isset($databaseError)
    ): ?>

        <div class="error-box">

            <strong>
                Unable to load report.
            </strong>

            <br><br>

            <?= h($databaseError) ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <section class="filter-card">


        <div class="filter-title">

            🔎 Filter Report

        </div>


        <form
            method="GET"
            class="filters"
        >


            <div>

                <label>
                    Start Date
                </label>

                <input
                    type="date"
                    name="start_date"
                    value="<?= h(
                        $startDate
                    ) ?>"
                >

            </div>



            <div>

                <label>
                    End Date
                </label>

                <input
                    type="date"
                    name="end_date"
                    value="<?= h(
                        $endDate
                    ) ?>"
                >

            </div>



            <div>

                <label>
                    Curriculum
                </label>

                <select
                    name="curriculum"
                >

                    <option value="">
                        All Curricula
                    </option>

                    <option
                        value="Cambridge"
                        <?= $curriculum ===
                            'Cambridge'
                            ? 'selected'
                            : '' ?>
                    >
                        Cambridge
                    </option>

                    <option
                        value="IB"
                        <?= $curriculum === 'IB'
                            ? 'selected'
                            : '' ?>
                    >
                        IB
                    </option>

                    <option
                        value="GES"
                        <?= $curriculum === 'GES'
                            ? 'selected'
                            : '' ?>
                    >
                        GES
                    </option>

                    <option
                        value="SAT"
                        <?= $curriculum === 'SAT'
                            ? 'selected'
                            : '' ?>
                    >
                        SAT
                    </option>

                </select>

            </div>



            <div>

                <label>
                    Payment
                </label>

                <select
                    name="payment_status"
                >

                    <option value="">
                        All Payments
                    </option>

                    <option
                        value="Paid"
                        <?= $paymentStatus ===
                            'Paid'
                            ? 'selected'
                            : '' ?>
                    >
                        Paid
                    </option>

                    <option
                        value="Pending"
                        <?= $paymentStatus ===
                            'Pending'
                            ? 'selected'
                            : '' ?>
                    >
                        Pending
                    </option>

                </select>

            </div>



            <div>

                <label>
                    Assignment
                </label>

                <select
                    name="assignment_status"
                >

                    <option value="">
                        All
                    </option>

                    <option
                        value="assigned"
                        <?= $assignmentStatus ===
                            'assigned'
                            ? 'selected'
                            : '' ?>
                    >
                        Assigned
                    </option>

                    <option
                        value="unassigned"
                        <?= $assignmentStatus ===
                            'unassigned'
                            ? 'selected'
                            : '' ?>
                    >
                        Unassigned
                    </option>

                </select>

            </div>



            <div
                style="
                    display:flex;
                    align-items:end;
                "
            >

                <button
                    type="submit"
                    class="filter-btn"
                >

                    Apply

                </button>

            </div>



            <div
                style="
                    display:flex;
                    align-items:end;
                "
            >

                <a
                    href="report.php"
                    class="clear-btn"
                >

                    Clear

                </a>

            </div>


        </form>


    </section>



    <!-- =================================================
         MAIN STATISTICS
    ================================================== -->

    <section class="stats">


        <!-- BOOKINGS -->

        <div class="stat">


            <div class="stat-icon">

                📚

            </div>


            <div class="stat-label">

                TOTAL BOOKINGS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalBookings
                ) ?>

            </div>


            <div class="stat-sub">

                Student lesson bookings

            </div>


        </div>



        <!-- STUDENTS -->

        <div class="stat">


            <div class="stat-icon">

                👨‍🎓

            </div>


            <div class="stat-label">

                TOTAL STUDENTS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalStudents
                ) ?>

            </div>


            <div class="stat-sub">

                Registered students

            </div>


        </div>



        <!-- TEACHERS -->

        <div class="stat">


            <div class="stat-icon">

                👨‍🏫

            </div>


            <div class="stat-label">

                ACTIVE TEACHERS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $totalTeachers
                ) ?>

            </div>


            <div class="stat-sub">

                Active teaching staff

            </div>


        </div>



        <!-- REVENUE -->

        <div
            class="
                stat
                revenue
            "
        >


            <div class="stat-icon">

                💰

            </div>


            <div class="stat-label">

                TOTAL REVENUE

            </div>


            <div class="stat-number">

                GHS
                <?= number_format(
                    $totalRevenue,
                    2
                ) ?>

            </div>


            <div class="stat-sub">

                Paid bookings

            </div>


        </div>


    </section>



    <!-- =================================================
         SECONDARY STATISTICS
    ================================================== -->

    <section class="stats">


        <div class="stat">


            <div class="stat-icon">

                ✅

            </div>


            <div class="stat-label">

                PAID BOOKINGS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $paidBookings
                ) ?>

            </div>


            <div class="stat-sub">

                Successful bookings

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                ⏳

            </div>


            <div class="stat-label">

                PENDING BOOKINGS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $pendingBookings
                ) ?>

            </div>


            <div class="stat-sub">

                Awaiting payment

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                👨‍🏫

            </div>


            <div class="stat-label">

                ASSIGNED BOOKINGS

            </div>


            <div class="stat-number">

                <?= number_format(
                    $assignedBookings
                ) ?>

            </div>


            <div class="stat-sub">

                Teacher assigned

            </div>


        </div>



        <div class="stat">


            <div class="stat-icon">

                ⚠️

            </div>


            <div class="stat-label">

                UNASSIGNED

            </div>


            <div class="stat-number">

                <?= number_format(
                    $unassignedBookings
                ) ?>

            </div>


            <div class="stat-sub">

                Need teacher assignment

            </div>


        </div>


    </section>



    <!-- =================================================
         PROGRESS
    ================================================== -->

    <section class="progress-grid">


        <div class="progress-card">


            <div class="progress-head">

                <span>
                    Payment Completion
                </span>

                <span>
                    <?= $paidPercentage ?>%
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar"
                    style="
                        width:
                        <?= min(
                            $paidPercentage,
                            100
                        ) ?>%;
                    "
                ></div>

            </div>


        </div>



        <div class="progress-card">


            <div class="progress-head">

                <span>
                    Teacher Assignment
                </span>

                <span>
                    <?= $assignedPercentage ?>%
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar"
                    style="
                        width:
                        <?= min(
                            $assignedPercentage,
                            100
                        ) ?>%;
                    "
                ></div>

            </div>


        </div>



        <div class="progress-card">


            <div class="progress-head">

                <span>
                    Lesson Completion
                </span>

                <span>
                    <?= $completedPercentage ?>%
                </span>

            </div>


            <div class="progress">

                <div
                    class="progress-bar"
                    style="
                        width:
                        <?= min(
                            $completedPercentage,
                            100
                        ) ?>%;
                    "
                ></div>

            </div>


        </div>


    </section>



    <!-- =================================================
         BREAKDOWNS
    ================================================== -->

    <section class="two-column">


        <!-- CURRICULUM -->

        <div class="panel">


            <div class="panel-header">

                <h2>
                    📚 Curriculum Breakdown
                </h2>

                <span>
                    Bookings
                </span>

            </div>


            <div class="breakdown">


                <?php if (
                    empty(
                        $curriculumData
                    )
                ): ?>


                    <div class="empty">

                        No curriculum data.

                    </div>


                <?php else: ?>


                    <?php foreach (
                        $curriculumData
                        as $row
                    ): ?>


                        <?php

                        $percentage = 0;

                        if (
                            $totalBookings > 0
                        ) {

                            $percentage =
                                round(
                                    (
                                        (int)$row[
                                            'total'
                                        ]
                                        /
                                        $totalBookings
                                    ) * 100
                                );

                        }

                        ?>


                        <div
                            class="
                                breakdown-item
                            "
                        >


                            <div
                                class="
                                    breakdown-top
                                "
                            >

                                <span
                                    class="
                                        breakdown-name
                                    "
                                >

                                    <?= h(
                                        $row[
                                            'curriculum_name'
                                        ]
                                    ) ?>

                                </span>


                                <span
                                    class="
                                        breakdown-count
                                    "
                                >

                                    <?= number_format(
                                        $row[
                                            'total'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div
                                class="
                                    small-progress
                                "
                            >

                                <div
                                    style="
                                        width:
                                        <?= min(
                                            $percentage,
                                            100
                                        ) ?>%;
                                    "
                                ></div>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </div>



        <!-- SUBJECTS -->

        <div class="panel">


            <div class="panel-header">

                <h2>
                    📖 Top Subjects
                </h2>

                <span>
                    Top 10
                </span>

            </div>


            <div class="breakdown">


                <?php if (
                    empty(
                        $subjectData
                    )
                ): ?>


                    <div class="empty">

                        No subject data.

                    </div>


                <?php else: ?>


                    <?php

                    $maxSubject =
                        max(
                            array_column(
                                $subjectData,
                                'total'
                            )
                        );

                    ?>


                    <?php foreach (
                        $subjectData
                        as $row
                    ): ?>


                        <?php

                        $subjectPercentage = 0;

                        if (
                            $maxSubject > 0
                        ) {

                            $subjectPercentage =
                                round(
                                    (
                                        (int)$row[
                                            'total'
                                        ]
                                        /
                                        $maxSubject
                                    ) * 100
                                );

                        }

                        ?>


                        <div
                            class="
                                breakdown-item
                            "
                        >


                            <div
                                class="
                                    breakdown-top
                                "
                            >

                                <span
                                    class="
                                        breakdown-name
                                    "
                                >

                                    <?= h(
                                        $row[
                                            'subject_name'
                                        ]
                                    ) ?>

                                </span>


                                <span
                                    class="
                                        breakdown-count
                                    "
                                >

                                    <?= number_format(
                                        $row[
                                            'total'
                                        ]
                                    ) ?>

                                </span>

                            </div>


                            <div
                                class="
                                    small-progress
                                "
                            >

                                <div
                                    style="
                                        width:
                                        <?= min(
                                            $subjectPercentage,
                                            100
                                        ) ?>%;
                                    "
                                ></div>

                            </div>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </div>


    </section>



    <!-- =================================================
         LESSON STATUS
    ================================================== -->

    <section class="two-column">


        <div class="panel">


            <div class="panel-header">

                <h2>
                    📅 Lesson Status
                </h2>

                <span>
                    Current report
                </span>

            </div>


            <div
                class="breakdown"
            >


                <div
                    class="
                        breakdown-item
                    "
                >

                    <div
                        class="
                            breakdown-top
                        "
                    >

                        <span
                            class="
                                breakdown-name
                            "
                        >

                            Scheduled

                        </span>


                        <span
                            class="
                                breakdown-count
                            "
                        >

                            <?= number_format(
                                $scheduledLessons
                            ) ?>

                        </span>

                    </div>

                </div>



                <div
                    class="
                        breakdown-item
                    "
                >

                    <div
                        class="
                            breakdown-top
                        "
                    >

                        <span
                            class="
                                breakdown-name
                            "
                        >

                            Completed

                        </span>


                        <span
                            class="
                                breakdown-count
                            "
                        >

                            <?= number_format(
                                $completedLessons
                            ) ?>

                        </span>

                    </div>

                </div>



                <div
                    class="
                        breakdown-item
                    "
                >

                    <div
                        class="
                            breakdown-top
                        "
                    >

                        <span
                            class="
                                breakdown-name
                            "
                        >

                            Cancelled

                        </span>


                        <span
                            class="
                                breakdown-count
                            "
                        >

                            <?= number_format(
                                $cancelledLessons
                            ) ?>

                        </span>

                    </div>

                </div>


            </div>


        </div>



        <!-- REPORT ACTIONS -->

        <div class="panel">


            <div class="panel-header">

                <h2>
                    🖨️ Report Actions
                </h2>

            </div>


            <div
                style="
                    padding:25px;
                "
            >


                <p
                    style="
                        color:#667085;
                        font-size:12px;
                        line-height:1.7;
                        margin-bottom:20px;
                    "
                >

                    Use the filters above to
                    generate a specific report.
                    You can print or save the
                    report as a PDF from your
                    browser.

                </p>


                <button
                    onclick="window.print()"
                    class="print-btn"
                    style="
                        width:100%;
                        padding:13px;
                        font-size:12px;
                    "
                >

                    🖨️ Print / Save as PDF

                </button>


            </div>


        </div>


    </section>



    <!-- =================================================
         RECENT BOOKINGS
    ================================================== -->

    <section class="table-panel">


        <div class="panel-header">

            <h2>
                📚 Booking Report
            </h2>

            <span>

                <?= count(
                    $recentBookings
                ) ?>
                records shown

            </span>

        </div>


        <div class="table-wrap">


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
                            Class / Year
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
                            Lesson
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    empty(
                        $recentBookings
                    )
                ): ?>


                    <tr>

                        <td
                            colspan="8"
                            class="empty"
                        >

                            No bookings found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $recentBookings
                        as $row
                    ): ?>


                        <tr>


                            <td>

                                <div
                                    class="student"
                                >

                                    <?= h(
                                        $row[
                                            'student_name'
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="
                                        reference
                                    "
                                >

                                    <?= h(
                                        $row[
                                            'booking_reference'
                                        ]
                                    ) ?>

                                </div>

                            </td>



                            <td>

                                <?= h(
                                    $row[
                                        'subjects'
                                    ]
                                ) ?>

                            </td>



                            <td>

                                <?= h(
                                    $row[
                                        'curriculum'
                                    ]
                                ) ?>

                            </td>



                            <td>

                                <?= h(
                                    $row[
                                        'class_year'
                                    ]
                                ) ?>

                            </td>



                            <td>

                                <strong>

                                    GHS
                                    <?= number_format(
                                        (float)(
                                            $row[
                                                'amount'
                                            ]
                                            ??
                                            0
                                        ),
                                        2
                                    ) ?>

                                </strong>

                            </td>



                            <td>

                                <?php

                                $status =
                                    strtolower(
                                        trim(
                                            $row[
                                                'payment_status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );

                                ?>


                                <?php if (
                                    $status === 'paid'
                                    ||
                                    $status === 'success'
                                ): ?>


                                    <span
                                        class="
                                            badge
                                            paid
                                        "
                                    >

                                        ✓ Paid

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            pending
                                        "
                                    >

                                        <?= h(
                                            ucfirst(
                                                $row[
                                                    'payment_status'
                                                ]
                                                ??
                                                'Pending'
                                            )
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </td>



                            <td>


                                <?php if (
                                    !empty(
                                        $row[
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

                                        <?= h(
                                            $row[
                                                'teacher_name'
                                            ]
                                        ) ?>

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            unassigned
                                        "
                                    >

                                        Unassigned

                                    </span>


                                <?php endif; ?>


                            </td>



                            <td>


                                <?php

                                $lessonStatus =
                                    strtolower(
                                        trim(
                                            $row[
                                                'lesson_status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );

                                ?>


                                <?php if (
                                    $lessonStatus
                                    ===
                                    'completed'
                                ): ?>


                                    <span
                                        class="
                                            badge
                                            completed
                                        "
                                    >

                                        Completed

                                    </span>


                                <?php elseif (
                                    $lessonStatus
                                    ===
                                    'cancelled'
                                ): ?>


                                    <span
                                        class="
                                            badge
                                            cancelled
                                        "
                                    >

                                        Cancelled

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            scheduled
                                        "
                                    >

                                        <?= h(
                                            $row[
                                                'lesson_status'
                                            ]
                                            ??
                                            'Scheduled'
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>



    <!-- =================================================
         RECENT PAYMENTS
    ================================================== -->

    <section class="table-panel">


        <div class="panel-header">

            <h2>
                💳 Recent Payments
            </h2>

            <span>
                Payment records
            </span>

        </div>


        <div class="table-wrap">


            <table>


                <thead>

                    <tr>

                        <th>
                            Student
                        </th>

                        <th>
                            Booking
                        </th>

                        <th>
                            Amount
                        </th>

                        <th>
                            Method
                        </th>

                        <th>
                            Transaction
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    empty(
                        $recentPayments
                    )
                ): ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            No payment records
                            found.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $recentPayments
                        as $payment
                    ): ?>


                        <tr>


                            <td>

                                <div
                                    class="student"
                                >

                                    <?= h(
                                        $payment[
                                            'student_name'
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="
                                        reference
                                    "
                                >

                                    <?= h(
                                        $payment[
                                            'email'
                                        ]
                                    ) ?>

                                </div>

                            </td>



                            <td>

                                <?= h(
                                    $payment[
                                        'booking_reference'
                                    ]
                                ) ?>

                            </td>



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



                            <td>

                                <span
                                    class="
                                        payment-method
                                    "
                                >

                                    <?= h(
                                        $payment[
                                            'payment_method'
                                        ]
                                    ) ?>

                                </span>

                            </td>



                            <td>

                                <span
                                    style="
                                        font-size:9px;
                                    "
                                >

                                    <?= h(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    ) ?>

                                </span>

                            </td>



                            <td>

                                <?php

                                $pStatus =
                                    strtolower(
                                        trim(
                                            $payment[
                                                'status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );

                                ?>


                                <?php if (
                                    $pStatus === 'paid'
                                    ||
                                    $pStatus === 'success'
                                ): ?>


                                    <span
                                        class="
                                            badge
                                            paid
                                        "
                                    >

                                        ✓ Paid

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            badge
                                            pending
                                        "
                                    >

                                        <?= h(
                                            ucfirst(
                                                $payment[
                                                    'status'
                                                ]
                                                ??
                                                'Pending'
                                            )
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>



    <div class="footer">

        NISEL ONLINE EDUCATION
        • Administrator Reports

    </div>


</main>


</body>

</html>
