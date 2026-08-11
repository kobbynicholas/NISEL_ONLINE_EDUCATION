<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN - STUDENTS
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
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$curriculum =
    trim($_GET['curriculum'] ?? '');

$subject =
    trim($_GET['subject'] ?? '');

$status =
    trim($_GET['status'] ?? '');

$payment =
    trim($_GET['payment'] ?? '');


/*
|--------------------------------------------------------------------------
| GET SUBJECTS FROM BOOKINGS
|--------------------------------------------------------------------------
*/

$subjectList = [];

try {

    $subjectStmt = $pdo->query("
        SELECT subjects
        FROM bookings
        WHERE subjects IS NOT NULL
        AND TRIM(subjects) <> ''
    ");

    $subjectRows =
        $subjectStmt->fetchAll(
            PDO::FETCH_COLUMN
        );


    foreach (
        $subjectRows as $subjectRow
    ) {

        $parts =
            preg_split(
                '/[,;|]+/',
                $subjectRow
            );


        foreach (
            $parts as $part
        ) {

            $part =
                trim($part);


            if (
                $part !== ''
            ) {

                $subjectList[] =
                    $part;

            }

        }

    }


    $subjectList =
        array_values(
            array_unique(
                $subjectList
            )
        );


    natcasesort(
        $subjectList
    );


    $subjectList =
        array_values(
            $subjectList
        );

} catch (
    PDOException $e
) {

    $subjectList = [];

}


/*
|--------------------------------------------------------------------------
| CURRICULUM OPTIONS
|--------------------------------------------------------------------------
*/

$curriculumList = [

    'Cambridge',
    'IB',
    'GES',
    'SAT'

];


/*
|--------------------------------------------------------------------------
| BUILD STUDENT QUERY
|--------------------------------------------------------------------------
|
| We use students as the main table.
|
| Bookings are joined so the administrator can also see:
|
| - Subjects
| - Curriculum
| - Payment status
| - Assigned teacher
|
| GROUP BY prevents a student with multiple bookings from appearing
| multiple times.
|
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        s.id,
        s.student_name,
        s.email,
        s.phone,
        s.dob,

        COUNT(b.id) AS total_bookings,

        MAX(
            CASE
                WHEN
                    LOWER(
                        TRIM(
                            COALESCE(
                                b.payment_status,
                                ''
                            )
                        )
                    )
                    IN ('paid','success')
                THEN 1
                ELSE 0
            END
        ) AS has_paid_booking,

        GROUP_CONCAT(
            DISTINCT b.subjects
            SEPARATOR ', '
        ) AS booking_subjects,

        GROUP_CONCAT(
            DISTINCT b.curriculum
            SEPARATOR ', '
        ) AS booking_curricula,

        GROUP_CONCAT(
            DISTINCT b.class_year
            SEPARATOR ', '
        ) AS class_years,

        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN
                    b.teacher_name IS NOT NULL
                    AND
                    TRIM(b.teacher_name) <> ''
                THEN b.teacher_name
            END
            SEPARATOR ', '
        ) AS assigned_teachers,

        GROUP_CONCAT(
            DISTINCT
            b.payment_status
            SEPARATOR ', '
        ) AS payment_statuses

    FROM students s

    LEFT JOIN bookings b
        ON (
            b.student_id = s.id
            OR
            (
                (
                    b.student_id IS NULL
                    OR
                    b.student_id = 0
                )
                AND
                b.email = s.email
            )
        )

    WHERE 1 = 1

";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if (
    $search !== ''
) {

    $sql .= "

        AND (

            s.student_name LIKE :search

            OR s.email LIKE :search

            OR s.phone LIKE :search

            OR CAST(
                s.id AS CHAR
            ) LIKE :search

            OR b.subjects LIKE :search

            OR b.curriculum LIKE :search

            OR b.teacher_name LIKE :search

        )

    ";

    $params[':search'] =
        '%' .
        $search .
        '%';

}


/*
|--------------------------------------------------------------------------
| CURRICULUM FILTER
|--------------------------------------------------------------------------
*/

if (
    $curriculum !== ''
) {

    $sql .= "

        AND b.curriculum LIKE :curriculum

    ";

    $params[':curriculum'] =
        '%' .
        $curriculum .
        '%';

}


/*
|--------------------------------------------------------------------------
| SUBJECT FILTER
|--------------------------------------------------------------------------
*/

if (
    $subject !== ''
) {

    $sql .= "

        AND b.subjects LIKE :subject

    ";

    $params[':subject'] =
        '%' .
        $subject .
        '%';

}


/*
|--------------------------------------------------------------------------
| PAYMENT FILTER
|--------------------------------------------------------------------------
*/

if (
    $payment !== ''
) {

    if (
        $payment === 'Paid'
    ) {

        $sql .= "

            AND LOWER(
                TRIM(
                    COALESCE(
                        b.payment_status,
                        ''
                    )
                )
            )
            IN ('paid','success')

        ";

    }

    elseif (
        $payment === 'Pending'
    ) {

        $sql .= "

            AND LOWER(
                TRIM(
                    COALESCE(
                        b.payment_status,
                        ''
                    )
                )
            )
            = 'pending'

        ";

    }

}


/*
|--------------------------------------------------------------------------
| GROUP
|--------------------------------------------------------------------------
*/

$sql .= "

    GROUP BY

        s.id,
        s.student_name,
        s.email,
        s.phone,
        s.dob

";


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
|
| Students don't appear to have a dedicated status field in the
| student structure currently used by the portal.
|
| Therefore:
|
| Active = has at least one booking
| New = no booking yet
|
|--------------------------------------------------------------------------
*/

if (
    $status !== ''
) {

    /*
    |--------------------------------------------------------------------------
    | This filter is applied using HAVING because total_bookings is an
    | aggregate value.
    |--------------------------------------------------------------------------
    */

    if (
        $status === 'Active'
    ) {

        $sql .= "

            HAVING total_bookings > 0

        ";

    }

    elseif (
        $status === 'New'
    ) {

        $sql .= "

            HAVING total_bookings = 0

        ";

    }

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY
        s.student_name ASC

";


/*
|--------------------------------------------------------------------------
| GET STUDENTS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $pdo->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $students =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (
    PDOException $e
) {

    die(
        "Unable to load students: "
        .
        h(
            $e->getMessage()
        )
    );

}


/*
|--------------------------------------------------------------------------
| TOTAL
|--------------------------------------------------------------------------
*/

$totalStudents =
    count(
        $students
    );


/*
|--------------------------------------------------------------------------
| ACTIVE STUDENTS
|--------------------------------------------------------------------------
*/

$activeStudents = 0;


/*
|--------------------------------------------------------------------------
| NEW STUDENTS
|--------------------------------------------------------------------------
*/

$newStudents = 0;


/*
|--------------------------------------------------------------------------
| PAID STUDENTS
|--------------------------------------------------------------------------
*/

$paidStudents = 0;


foreach (
    $students
    as $student
) {

    $bookingCount =
        (int)(
            $student['total_bookings']
            ??
            0
        );


    if (
        $bookingCount > 0
    ) {

        $activeStudents++;

    } else {

        $newStudents++;

    }


    if (
        !empty(
            $student['has_paid_booking']
        )
    ) {

        $paidStudents++;

    }

}


/*
|--------------------------------------------------------------------------
| DATE FORMAT
|--------------------------------------------------------------------------
*/

function formatDate(
    $date
)
{

    if (
        empty($date)
    ) {

        return 'Not provided';

    }


    $timestamp =
        strtotime(
            $date
        );


    if (
        $timestamp === false
    ) {

        return h($date);

    }


    return date(
        'd M Y',
        $timestamp
    );

}


/*
|--------------------------------------------------------------------------
| INITIAL LETTERS
|--------------------------------------------------------------------------
*/

function initials(
    $name
)
{

    $name =
        trim(
            $name
        );


    if (
        $name === ''
    ) {

        return 'S';

    }


    $words =
        preg_split(
            '/\s+/',
            $name
        );


    $result = '';


    foreach (
        $words as $word
    ) {

        if (
            $word !== ''
        ) {

            $result .=
                strtoupper(
                    substr(
                        $word,
                        0,
                        1
                    )
                );

        }


        if (
            strlen($result) >= 2
        ) {

            break;

        }

    }


    return $result;

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
    Students |
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
        #f4f7fb;

    color:
        #172b4d;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position:
        fixed;

    left:
        0;

    top:
        0;

    width:
        245px;

    height:
        100vh;

    background:
        linear-gradient(
            180deg,
            #003366,
            #00264d
        );

    color:
        white;

    padding:
        25px 15px;

    z-index:
        1000;

}


.brand {

    text-align:
        center;

    padding-bottom:
        25px;

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

    width:
        58px;

    height:
        58px;

    margin:
        0 auto 10px;

    border-radius:
        16px;

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        28px;

}


.brand h2 {

    margin:
        0;

    font-size:
        20px;

}


.brand p {

    margin:
        5px 0 0;

    font-size:
        9px;

    letter-spacing:
        2px;

    opacity:
        .7;

}


.nav {

    margin-top:
        25px;

}


.nav a {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    padding:
        13px 14px;

    margin-bottom:
        7px;

    border-radius:
        9px;

    color:
        rgba(
            255,
            255,
            255,
            .82
        );

    text-decoration:
        none;

    font-size:
        14px;

}


.nav a:hover,
.nav a.active {

    background:
        rgba(
            255,
            255,
            255,
            .12
        );

    color:
        white;

}


.nav-icon {

    width:
        23px;

    text-align:
        center;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left:
        245px;

    min-height:
        100vh;

    padding:
        30px;

}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        20px;

    margin-bottom:
        25px;

}


.page-title h1 {

    margin:
        0;

    font-size:
        29px;

}


.page-title p {

    margin:
        7px 0 0;

    color:
        #667085;

    font-size:
        14px;

}


.admin-badge {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    background:
        white;

    padding:
        9px 14px;

    border-radius:
        10px;

    box-shadow:
        0 3px 12px
        rgba(
            0,
            0,
            0,
            .06
        );

    font-size:
        13px;

}


.admin-avatar {

    width:
        35px;

    height:
        35px;

    border-radius:
        50%;

    background:
        #e8f2ff;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap:
        18px;

    margin-bottom:
        25px;

}


.stat-card {

    background:
        white;

    padding:
        20px;

    border-radius:
        14px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

    display:
        flex;

    align-items:
        center;

    gap:
        14px;

}


.stat-icon {

    width:
        50px;

    height:
        50px;

    border-radius:
        12px;

    background:
        #e8f2ff;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        22px;

}


.stat-number {

    font-size:
        23px;

    font-weight:
        800;

}


.stat-label {

    margin-top:
        3px;

    color:
        #667085;

    font-size:
        11px;

}


/* =====================================================
   FILTER CARD
===================================================== */

.filter-card {

    background:
        white;

    border-radius:
        15px;

    padding:
        20px;

    margin-bottom:
        20px;

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

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        16px;

}


.filter-title h2 {

    margin:
        0;

    font-size:
        17px;

}


.filter-title span {

    color:
        #98a2b3;

    font-size:
        12px;

}


.filter-form {

    display:
        grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        1fr
        auto
        auto;

    gap:
        10px;

}


.search-wrapper {

    position:
        relative;

}


.search-icon {

    position:
        absolute;

    left:
        13px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        #98a2b3;

}


.search-input,
.filter-select {

    width:
        100%;

    height:
        45px;

    border:
        1px solid
        #d0d5dd;

    border-radius:
        9px;

    background:
        white;

    outline:
        none;

    color:
        #344054;

    font-size:
        13px;

}


.search-input {

    padding:
        0 12px 0 40px;

}


.filter-select {

    padding:
        0 10px;

}


.search-input:focus,
.filter-select:focus {

    border-color:
        #0074b7;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            116,
            183,
            .08
        );

}


.filter-button {

    height:
        45px;

    padding:
        0 18px;

    border:
        none;

    border-radius:
        9px;

    background:
        #003366;

    color:
        white;

    font-weight:
        700;

    cursor:
        pointer;

}


.clear-button {

    height:
        45px;

    padding:
        0 15px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        9px;

    background:
        #f2f4f7;

    color:
        #344054;

    text-decoration:
        none;

    font-size:
        13px;

}


/* =====================================================
   ACTIVE FILTERS
===================================================== */

.active-filters {

    display:
        flex;

    align-items:
        center;

    flex-wrap:
        wrap;

    gap:
        7px;

    margin-top:
        15px;

}


.filter-label {

    color:
        #667085;

    font-size:
        12px;

}


.filter-tag {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        5px 9px;

    border-radius:
        20px;

    background:
        #eef4ff;

    color:
        #003366;

    font-size:
        11px;

    font-weight:
        700;

}


/* =====================================================
   TABLE
===================================================== */

.table-card {

    background:
        white;

    border-radius:
        15px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

    overflow:
        hidden;

}


.table-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        20px;

    border-bottom:
        1px solid
        #eaecf0;

}


.table-header h2 {

    margin:
        0;

    font-size:
        17px;

}


.student-count {

    color:
        #667085;

    font-size:
        13px;

}


.table-wrapper {

    overflow-x:
        auto;

}


table {

    width:
        100%;

    min-width:
        1200px;

    border-collapse:
        collapse;

}


thead {

    background:
        #f8fafc;

}


th {

    padding:
        14px 16px;

    text-align:
        left;

    font-size:
        10px;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;

    color:
        #667085;

    white-space:
        nowrap;

}


td {

    padding:
        16px;

    border-top:
        1px solid
        #f0f2f5;

    vertical-align:
        middle;

    font-size:
        13px;

}


tbody tr:hover {

    background:
        #f8fbff;

}


/* =====================================================
   STUDENT
===================================================== */

.student-profile {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    min-width:
        200px;

}


.student-avatar {

    width:
        50px;

    height:
        50px;

    border-radius:
        50%;

    background:
        linear-gradient(
            135deg,
            #e8f2ff,
            #d9ecff
        );

    color:
        #003366;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        15px;

    font-weight:
        800;

}


.student-name {

    font-weight:
        700;

    color:
        #172b4d;

}


.student-id {

    color:
        #98a2b3;

    font-size:
        11px;

    margin-top:
        3px;

}


/* =====================================================
   CONTACT
===================================================== */

.contact {

    line-height:
        1.7;

}


.contact-email {

    color:
        #344054;

}


.contact-phone {

    color:
        #667085;

}


/* =====================================================
   ACADEMIC
===================================================== */

.academic {

    max-width:
        210px;

    line-height:
        1.5;

}


.curriculum-badge {

    display:
        inline-block;

    margin-bottom:
        5px;

    padding:
        4px 8px;

    border-radius:
        20px;

    background:
        #eef4ff;

    color:
        #003366;

    font-size:
        10px;

    font-weight:
        700;

}


.subject-text {

    color:
        #475467;

}


/* =====================================================
   TEACHER
===================================================== */

.teacher-text {

    max-width:
        150px;

    color:
        #475467;

    line-height:
        1.5;

}


.no-teacher {

    color:
        #98a2b3;

    font-style:
        italic;

}


/* =====================================================
   STATUS
===================================================== */

.status {

    display:
        inline-block;

    padding:
        5px 10px;

    border-radius:
        20px;

    font-size:
        10px;

    font-weight:
        700;

}


.status.active {

    background:
        #e7f7ed;

    color:
        #16803d;

}


.status.new {

    background:
        #eef4ff;

    color:
        #175cd3;

}


/* =====================================================
   PAYMENT
===================================================== */

.payment {

    display:
        inline-block;

    padding:
        5px 10px;

    border-radius:
        20px;

    font-size:
        10px;

    font-weight:
        700;

}


.payment.paid {

    background:
        #e7f7ed;

    color:
        #16803d;

}


.payment.pending {

    background:
        #fff4e5;

    color:
        #b54708;

}


.payment.none {

    background:
        #f2f4f7;

    color:
        #667085;

}


/* =====================================================
   BOOKINGS
===================================================== */

.booking-number {

    font-size:
        17px;

    font-weight:
        800;

    color:
        #003366;

}


.booking-label {

    color:
        #98a2b3;

    font-size:
        10px;

}


/* =====================================================
   VIEW BUTTON
===================================================== */

.view-button {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        8px 11px;

    border-radius:
        8px;

    background:
        #003366;

    color:
        white;

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        700;

}


.view-button:hover {

    background:
        #0055a5;

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {

    padding:
        70px 20px;

    text-align:
        center;

}


.empty-icon {

    font-size:
        45px;

    margin-bottom:
        12px;

}


.empty-state h3 {

    margin:
        0 0 7px;

}


.empty-state p {

    margin:
        0;

    color:
        #98a2b3;

    font-size:
        13px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align:
        center;

    padding:
        25px 0 5px;

    color:
        #98a2b3;

    font-size:
        11px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (
    max-width: 1200px
) {

    .stats {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }


    .filter-form {

        grid-template-columns:
            repeat(
                2,
                1fr
            );

    }

}


@media (
    max-width: 900px
) {

    .sidebar {

        width:
            70px;

        padding:
            20px 8px;

    }


    .brand h2,
    .brand p,
    .nav-text {

        display:
            none;

    }


    .brand {

        border:
            none;

    }


    .nav a {

        justify-content:
            center;

    }


    .main {

        margin-left:
            70px;

        padding:
            20px;

    }

}


@media (
    max-width: 600px
) {

    .main {

        padding:
            15px;

    }


    .topbar {

        flex-direction:
            column;

        align-items:
            flex-start;

    }


    .stats {

        grid-template-columns:
            1fr;

    }


    .filter-form {

        grid-template-columns:
            1fr;

    }


    .filter-button,
    .clear-button {

        width:
            100%;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
====================================================== -->

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
====================================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">


        <div class="page-title">


            <h1>

                Student Directory

            </h1>


            <p>

                View students, academic information,
                bookings and payment status.

            </p>


        </div>


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
         STATISTICS
    ================================================== -->

    <div class="stats">


        <div class="stat-card">


            <div class="stat-icon">

                👨‍🎓

            </div>


            <div>

                <div class="stat-number">

                    <?= $totalStudents ?>

                </div>


                <div class="stat-label">

                    Students Found

                </div>

            </div>


        </div>


        <div class="stat-card">


            <div class="stat-icon">

                🟢

            </div>


            <div>

                <div class="stat-number">

                    <?= $activeStudents ?>

                </div>


                <div class="stat-label">

                    Active Students

                </div>

            </div>


        </div>


        <div class="stat-card">


            <div class="stat-icon">

                🆕

            </div>


            <div>

                <div class="stat-number">

                    <?= $newStudents ?>

                </div>


                <div class="stat-label">

                    Students Without Bookings

                </div>

            </div>


        </div>


        <div class="stat-card">


            <div class="stat-icon">

                💳

            </div>


            <div>

                <div class="stat-number">

                    <?= $paidStudents ?>

                </div>


                <div class="stat-label">

                    With Paid Booking

                </div>

            </div>


        </div>


    </div>



    <!-- =================================================
         FILTERS
    ================================================== -->

    <div class="filter-card">


        <div class="filter-title">


            <h2>

                🔎 Find Students

            </h2>


            <span>

                Combine filters to narrow the results

            </span>


        </div>


        <form
            method="GET"
            class="filter-form"
        >


            <!-- SEARCH -->

            <div
                class="search-wrapper"
            >


                <span
                    class="search-icon"
                >

                    🔍

                </span>


                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search name, email, phone, ID..."
                    value="<?= h(
                        $search
                    ) ?>"
                >


            </div>



            <!-- SUBJECT -->

            <select
                name="subject"
                class="filter-select"
            >

                <option value="">

                    All Subjects

                </option>


                <?php foreach (
                    $subjectList
                    as $subjectOption
                ): ?>


                    <option
                        value="<?= h(
                            $subjectOption
                        ) ?>"
                        <?= $subject ===
                            $subjectOption
                            ? 'selected'
                            : '' ?>
                    >

                        <?= h(
                            $subjectOption
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>



            <!-- CURRICULUM -->

            <select
                name="curriculum"
                class="filter-select"
            >

                <option value="">

                    All Curricula

                </option>


                <?php foreach (
                    $curriculumList
                    as $curriculumOption
                ): ?>


                    <option
                        value="<?= h(
                            $curriculumOption
                        ) ?>"
                        <?= $curriculum ===
                            $curriculumOption
                            ? 'selected'
                            : '' ?>
                    >

                        <?= h(
                            $curriculumOption
                        ) ?>

                    </option>


                <?php endforeach; ?>


            </select>



            <!-- PAYMENT -->

            <select
                name="payment"
                class="filter-select"
            >

                <option value="">

                    All Payments

                </option>


                <option
                    value="Paid"
                    <?= $payment === 'Paid'
                        ? 'selected'
                        : '' ?>
                >

                    Paid

                </option>


                <option
                    value="Pending"
                    <?= $payment === 'Pending'
                        ? 'selected'
                        : '' ?>
                >

                    Pending

                </option>


            </select>



            <!-- STATUS -->

            <select
                name="status"
                class="filter-select"
            >

                <option value="">

                    All Students

                </option>


                <option
                    value="Active"
                    <?= $status === 'Active'
                        ? 'selected'
                        : '' ?>
                >

                    Active / Booked

                </option>


                <option
                    value="New"
                    <?= $status === 'New'
                        ? 'selected'
                        : '' ?>
                >

                    New / No Booking

                </option>


            </select>



            <!-- FILTER -->

            <button
                type="submit"
                class="filter-button"
            >

                Filter

            </button>



            <!-- CLEAR -->

            <?php if (
                $search !== ''
                ||
                $subject !== ''
                ||
                $curriculum !== ''
                ||
                $status !== ''
                ||
                $payment !== ''
            ): ?>


                <a
                    href="students.php"
                    class="clear-button"
                >

                    Clear

                </a>


            <?php else: ?>


                <span></span>


            <?php endif; ?>


        </form>



        <!-- ACTIVE FILTERS -->

        <?php if (
            $search !== ''
            ||
            $subject !== ''
            ||
            $curriculum !== ''
            ||
            $status !== ''
            ||
            $payment !== ''
        ): ?>


            <div
                class="active-filters"
            >


                <span
                    class="filter-label"
                >

                    Active filters:

                </span>


                <?php if (
                    $search !== ''
                ): ?>


                    <span
                        class="filter-tag"
                    >

                        🔍

                        Search:
                        <?= h(
                            $search
                        ) ?>

                    </span>


                <?php endif; ?>


                <?php if (
                    $subject !== ''
                ): ?>


                    <span
                        class="filter-tag"
                    >

                        📚

                        Subject:
                        <?= h(
                            $subject
                        ) ?>

                    </span>


                <?php endif; ?>


                <?php if (
                    $curriculum !== ''
                ): ?>


                    <span
                        class="filter-tag"
                    >

                        🎓

                        Curriculum:
                        <?= h(
                            $curriculum
                        ) ?>

                    </span>


                <?php endif; ?>


                <?php if (
                    $payment !== ''
                ): ?>


                    <span
                        class="filter-tag"
                    >

                        💳

                        Payment:
                        <?= h(
                            $payment
                        ) ?>

                    </span>


                <?php endif; ?>


                <?php if (
                    $status !== ''
                ): ?>


                    <span
                        class="filter-tag"
                    >

                        ●

                        Status:
                        <?= h(
                            $status
                        ) ?>

                    </span>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         STUDENT TABLE
    ================================================== -->

    <div class="table-card">


        <div class="table-header">


            <h2>

                👨‍🎓 Student Information

            </h2>


            <div
                class="student-count"
            >

                <?= $totalStudents ?>

                result(s)

            </div>


        </div>


        <?php if (
            empty($students)
        ): ?>


            <div
                class="empty-state"
            >


                <div
                    class="empty-icon"
                >

                    🔎

                </div>


                <h3>

                    No Students Found

                </h3>


                <p>

                    Try changing your search
                    or filter options.

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
                                Contact
                            </th>

                            <th>
                                Date of Birth
                            </th>

                            <th>
                                Curriculum / Subject
                            </th>

                            <th>
                                Class / Year
                            </th>

                            <th>
                                Teacher
                            </th>

                            <th>
                                Bookings
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Status
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
                                    class="student-profile"
                                >


                                    <div
                                        class="student-avatar"
                                    >

                                        <?= h(
                                            initials(
                                                $student[
                                                    'student_name'
                                                ]
                                            )
                                        ) ?>

                                    </div>


                                    <div>


                                        <div
                                            class="student-name"
                                        >

                                            <?= h(
                                                $student[
                                                    'student_name'
                                                ]
                                            ) ?>

                                        </div>


                                        <div
                                            class="student-id"
                                        >

                                            Student ID:
                                            <?= h(
                                                $student[
                                                    'id'
                                                ]
                                            ) ?>

                                        </div>


                                    </div>


                                </div>


                            </td>



                            <!-- CONTACT -->

                            <td>


                                <div
                                    class="contact"
                                >


                                    <?php if (
                                        !empty(
                                            $student['email']
                                        )
                                    ): ?>


                                        <div
                                            class="contact-email"
                                        >

                                            📧
                                            <?= h(
                                                $student['email']
                                            ) ?>

                                        </div>


                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $student['phone']
                                        )
                                    ): ?>


                                        <div
                                            class="contact-phone"
                                        >

                                            📞
                                            <?= h(
                                                $student['phone']
                                            ) ?>

                                        </div>


                                    <?php endif; ?>


                                    <?php if (
                                        empty(
                                            $student['email']
                                        )
                                        &&
                                        empty(
                                            $student['phone']
                                        )
                                    ): ?>


                                        <span
                                            style="
                                                color:#98a2b3;
                                            "
                                        >

                                            Not provided

                                        </span>


                                    <?php endif; ?>


                                </div>


                            </td>



                            <!-- DOB -->

                            <td>


                                <?= formatDate(
                                    $student['dob']
                                ) ?>


                            </td>



                            <!-- ACADEMIC -->

                            <td>


                                <div
                                    class="academic"
                                >


                                    <?php

                                    $curricula =
                                        trim(
                                            $student[
                                                'booking_curricula'
                                            ]
                                            ??
                                            ''
                                        );

                                    ?>


                                    <?php if (
                                        $curricula !== ''
                                    ): ?>


                                        <span
                                            class="
                                                curriculum-badge
                                            "
                                        >

                                            🎓
                                            <?= h(
                                                $curricula
                                            ) ?>

                                        </span>


                                    <?php else: ?>


                                        <span
                                            style="
                                                color:#98a2b3;
                                            "
                                        >

                                            No curriculum

                                        </span>


                                    <?php endif; ?>


                                    <div
                                        class="
                                            subject-text
                                        "
                                    >

                                        <?php

                                        $subjects =
                                            trim(
                                                $student[
                                                    'booking_subjects'
                                                ]
                                                ??
                                                ''
                                            );

                                        ?>


                                        <?php if (
                                            $subjects !== ''
                                        ): ?>


                                            📚
                                            <?= h(
                                                $subjects
                                            ) ?>


                                        <?php else: ?>


                                            No subjects

                                        <?php endif; ?>


                                    </div>


                                </div>


                            </td>



                            <!-- CLASS -->

                            <td>


                                <?php

                                $classes =
                                    trim(
                                        $student[
                                            'class_years'
                                        ]
                                        ??
                                        ''
                                    );

                                ?>


                                <?php if (
                                    $classes !== ''
                                ): ?>


                                    <?= h(
                                        $classes
                                    ) ?>


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



                            <!-- TEACHER -->

                            <td>


                                <?php

                                $teachers =
                                    trim(
                                        $student[
                                            'assigned_teachers'
                                        ]
                                        ??
                                        ''
                                    );

                                ?>


                                <?php if (
                                    $teachers !== ''
                                ): ?>


                                    <div
                                        class="teacher-text"
                                    >

                                        👨‍🏫

                                        <?= h(
                                            $teachers
                                        ) ?>

                                    </div>


                                <?php else: ?>


                                    <span
                                        class="no-teacher"
                                    >

                                        Not assigned

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- BOOKINGS -->

                            <td>


                                <div
                                    class="booking-number"
                                >

                                    <?= h(
                                        $student[
                                            'total_bookings'
                                        ]
                                    ) ?>

                                </div>


                                <div
                                    class="booking-label"
                                >

                                    booking(s)

                                </div>


                            </td>



                            <!-- PAYMENT -->

                            <td>


                                <?php

                                $paymentStatuses =
                                    strtolower(
                                        trim(
                                            $student[
                                                'payment_statuses'
                                            ]
                                            ??
                                            ''
                                        )
                                    );

                                ?>


                                <?php if (
                                    !empty(
                                        $student[
                                            'has_paid_booking'
                                        ]
                                    )
                                ): ?>


                                    <span
                                        class="
                                            payment
                                            paid
                                        "
                                    >

                                        ✓ Paid

                                    </span>


                                <?php elseif (
                                    strpos(
                                        $paymentStatuses,
                                        'pending'
                                    ) !== false
                                ): ?>


                                    <span
                                        class="
                                            payment
                                            pending
                                        "
                                    >

                                        ⏳ Pending

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            payment
                                            none
                                        "
                                    >

                                        No payment

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php if (
                                    (int)
                                    $student[
                                        'total_bookings'
                                    ]
                                    >
                                    0
                                ): ?>


                                    <span
                                        class="
                                            status
                                            active
                                        "
                                    >

                                        Active

                                    </span>


                                <?php else: ?>


                                    <span
                                        class="
                                            status
                                            new
                                        "
                                    >

                                        New

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- ACTION -->

                            <td>


                                <a
                                    href="student_details.php?id=<?= h(
                                        $student['id']
                                    ) ?>"
                                    class="view-button"
                                >

                                    👁 View

                                </a>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php endif; ?>


    </div>



    <div
        class="footer"
    >

        NISEL ONLINE EDUCATION
        ·
        Administrator Student Directory

    </div>


</main>


</body>

</html>
