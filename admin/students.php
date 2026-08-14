<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN STUDENTS DIRECTORY
| PDO VERSION
|--------------------------------------------------------------------------
*/


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
| DATABASE
|--------------------------------------------------------------------------
*/

require "../config/db.php";


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
| INITIALS
|--------------------------------------------------------------------------
*/

function getInitials($name)
{
    $name = trim((string)$name);

    if ($name === '') {
        return 'ST';
    }

    $words = preg_split(
        '/\s+/',
        $name
    );

    $initials = '';

    foreach ($words as $word) {

        if ($word === '') {
            continue;
        }

        $initials .= strtoupper(
            substr($word, 0, 1)
        );

        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials ?: 'ST';
}


/*
|--------------------------------------------------------------------------
| FORMAT DATE
|--------------------------------------------------------------------------
*/

function formatDate($date)
{
    if (empty($date)) {
        return 'Not provided';
    }

    $time = strtotime($date);

    if ($time === false) {
        return $date;
    }

    return date(
        'd M Y',
        $time
    );
}


/*
|--------------------------------------------------------------------------
| SPLIT MULTIPLE VALUES
|--------------------------------------------------------------------------
|
| Handles:
|
| Mathematics, Physics
| Mathematics;Physics
| Mathematics|Physics
|
|--------------------------------------------------------------------------
*/

function splitValues($value)
{
    if (
        $value === null ||
        trim($value) === ''
    ) {
        return [];
    }

    $parts = preg_split(
        '/[,;|]+/',
        $value
    );

    $result = [];

    foreach ($parts as $part) {

        $part = trim($part);

        if ($part === '') {
            continue;
        }

        if (!in_array(
            strtolower($part),
            array_map(
                'strtolower',
                $result
            )
        )) {
            $result[] = $part;
        }
    }

    return $result;
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$curriculum =
    trim($_GET['curriculum'] ?? '');

$subject =
    trim($_GET['subject'] ?? '');

$payment =
    trim($_GET['payment'] ?? '');

$status =
    trim($_GET['status'] ?? '');


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
| GET SUBJECT LIST
|--------------------------------------------------------------------------
*/

$subjectList = [];

try {

    $subjectQuery = $pdo->query("
        SELECT subjects
        FROM bookings
        WHERE subjects IS NOT NULL
        AND TRIM(subjects) <> ''
    ");

    $subjectRows =
        $subjectQuery->fetchAll(
            PDO::FETCH_COLUMN
        );


    foreach (
        $subjectRows as $row
    ) {

        $values =
            splitValues($row);

        foreach (
            $values as $value
        ) {

            $subjectList[] =
                $value;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE DUPLICATES
    |--------------------------------------------------------------------------
    */

    $subjectList =
        array_values(
            array_unique(
                $subjectList,
                SORT_NATURAL
            )
        );


    natcasesort(
        $subjectList
    );


    $subjectList =
        array_values(
            $subjectList
        );

} catch (PDOException $e) {

    $subjectList = [];
}


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| The previous version joined using:
|
|     student_id OR email
|
| That can cause unrelated booking records to be pulled into the same
| student if IDs/emails do not match consistently.
|
| Your existing student dashboard uses the student's email to retrieve
| bookings, so this version follows that same relationship.
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

        COUNT(b.id)
            AS total_bookings,

        GROUP_CONCAT(
            DISTINCT b.curriculum
            ORDER BY b.curriculum
            SEPARATOR '||'
        )
            AS curricula,

        GROUP_CONCAT(
            DISTINCT b.subjects
            ORDER BY b.subjects
            SEPARATOR '||'
        )
            AS subjects,

        GROUP_CONCAT(
            DISTINCT b.class_year
            ORDER BY b.class_year
            SEPARATOR '||'
        )
            AS class_years,

        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN
                    b.teacher_name IS NOT NULL
                    AND
                    TRIM(b.teacher_name) <> ''
                THEN b.teacher_name
            END
            ORDER BY b.teacher_name
            SEPARATOR '||'
        )
            AS teachers,

        GROUP_CONCAT(
            DISTINCT b.payment_status
            ORDER BY b.payment_status
            SEPARATOR '||'
        )
            AS payment_statuses,

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
                    IN (
                        'paid',
                        'success'
                    )
                THEN 1
                ELSE 0
            END
        )
            AS has_paid

    FROM students s

    LEFT JOIN bookings b
        ON b.email = s.email

    WHERE 1 = 1

";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

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

if ($curriculum !== '') {

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

if ($subject !== '') {

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

if ($payment === 'Paid') {

    $sql .= "

        AND LOWER(
            TRIM(
                COALESCE(
                    b.payment_status,
                    ''
                )
            )
        )
        IN (
            'paid',
            'success'
        )

    ";
}


if ($payment === 'Pending') {

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
*/

if ($status === 'Active') {

    $sql .= "

        HAVING total_bookings > 0

    ";
}


if ($status === 'New') {

    $sql .= "

        HAVING total_bookings = 0

    ";
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

} catch (PDOException $e) {

    die(
        "Database error: "
        .
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

$totalStudents =
    count($students);

$activeStudents = 0;

$newStudents = 0;

$paidStudents = 0;


foreach (
    $students as $student
) {

    $bookings =
        (int)(
            $student['total_bookings']
            ?? 0
        );


    if ($bookings > 0) {

        $activeStudents++;

    } else {

        $newStudents++;
    }


    if (
        (int)(
            $student['has_paid']
            ?? 0
        ) === 1
    ) {

        $paidStudents++;
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
    Students |
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

    margin-left: 245px;

    min-height: 100vh;

    padding: 30px;
}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 25px;
}


.page-title h1 {

    margin: 0;

    font-size: 29px;
}


.page-title p {

    margin: 7px 0 0;

    color: #667085;

    font-size: 14px;
}


.admin-badge {

    display: flex;

    align-items: center;

    gap: 10px;

    background: white;

    padding: 9px 14px;

    border-radius: 10px;

    box-shadow:
        0 3px 12px
        rgba(
            0,
            0,
            0,
            .06
        );

    font-size: 13px;
}


.admin-avatar {

    width: 35px;

    height: 35px;

    border-radius: 50%;

    background: #e8f2ff;

    display: flex;

    align-items: center;

    justify-content: center;
}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap: 18px;

    margin-bottom: 25px;
}


.stat-card {

    background: white;

    padding: 20px;

    border-radius: 14px;

    box-shadow:
        0 4px 18px
        rgba(
            0,
            0,
            0,
            .05
        );

    display: flex;

    align-items: center;

    gap: 14px;
}


.stat-icon {

    width: 50px;

    height: 50px;

    border-radius: 12px;

    background: #e8f2ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;
}


.stat-number {

    font-size: 23px;

    font-weight: 800;
}


.stat-label {

    margin-top: 3px;

    color: #667085;

    font-size: 11px;
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

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}


.filter-title h2 {

    margin: 0;

    font-size: 17px;
}


.filter-title span {

    color: #98a2b3;

    font-size: 12px;
}


.filter-form {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        1fr
        auto
        auto;

    gap: 10px;
}


.search-wrapper {

    position: relative;
}


.search-icon {

    position: absolute;

    left: 13px;

    top: 50%;

    transform:
        translateY(-50%);

    color: #98a2b3;
}


.search-input,
.filter-select {

    width: 100%;

    height: 45px;

    border:
        1px solid
        #d0d5dd;

    border-radius: 9px;

    background: white;

    outline: none;

    color: #344054;

    font-size: 13px;
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

    border-color: #0074b7;

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

    height: 45px;

    padding: 0 18px;

    border: none;

    border-radius: 9px;

    background: #003366;

    color: white;

    font-weight: 700;

    cursor: pointer;
}


.clear-button {

    height: 45px;

    padding: 0 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: #f2f4f7;

    color: #344054;

    text-decoration: none;

    font-size: 13px;
}


/* =====================================================
   ACTIVE FILTERS
===================================================== */

.active-filters {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 7px;

    margin-top: 15px;
}


.filter-label {

    color: #667085;

    font-size: 12px;
}


.filter-tag {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 9px;

    border-radius: 20px;

    background: #eef4ff;

    color: #003366;

    font-size: 11px;

    font-weight: 700;
}


/* =====================================================
   TABLE CARD
===================================================== */

.table-card {

    background: white;

    border-radius: 15px;

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

    align-items: center;

    justify-content: space-between;

    padding: 20px;

    border-bottom:
        1px solid
        #eaecf0;
}


.table-header h2 {

    margin: 0;

    font-size: 17px;
}


.student-count {

    color: #667085;

    font-size: 13px;
}


.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    min-width: 1250px;

    border-collapse: collapse;
}


thead {

    background: #f8fafc;
}


th {

    padding: 14px 16px;

    text-align: left;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: #667085;

    white-space: nowrap;
}


td {

    padding: 16px;

    border-top:
        1px solid
        #f0f2f5;

    vertical-align: middle;

    font-size: 13px;
}


tbody tr:hover {

    background: #f8fbff;
}


/* =====================================================
   STUDENT PROFILE
===================================================== */

.student-profile {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 200px;
}


.student-avatar {

    width: 50px;

    height: 50px;

    flex: 0 0 50px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #e8f2ff,
            #d9ecff
        );

    color: #003366;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 15px;

    font-weight: 800;
}


.student-name {

    font-weight: 700;

    color: #172b4d;
}


.student-id {

    color: #98a2b3;

    font-size: 11px;

    margin-top: 3px;
}


/* =====================================================
   CONTACT
===================================================== */

.contact {

    line-height: 1.7;

    white-space: nowrap;
}


.contact-email {

    color: #344054;
}


.contact-phone {

    color: #667085;
}


/* =====================================================
   CURRICULUM AREA
===================================================== */

.academic-box {

    width: 270px;

    max-width: 270px;
}


/*
|--------------------------------------------------------------------------
| CURRICULUM BADGES
|--------------------------------------------------------------------------
*/

.curriculum-list {

    display: flex;

    flex-wrap: wrap;

    gap: 5px;

    margin-bottom: 8px;
}


.curriculum-badge {

    display: inline-flex;

    align-items: center;

    padding: 5px 9px;

    border-radius: 20px;

    background: #eef4ff;

    color: #003366;

    font-size: 10px;

    font-weight: 700;

    border: 1px solid #d9e8ff;
}


/*
|--------------------------------------------------------------------------
| DIFFERENT CURRICULUM COLORS
|--------------------------------------------------------------------------
*/

.curriculum-badge.cambridge {

    background: #eef4ff;

    color: #175cd3;
}


.curriculum-badge.ib {

    background: #f4ebff;

    color: #6941c6;
}


.curriculum-badge.ges {

    background: #ecfdf3;

    color: #027a48;
}


.curriculum-badge.sat {

    background: #fff4e5;

    color: #b54708;
}


/* =====================================================
   SUBJECT TAGS
===================================================== */

.subject-list {

    display: flex;

    flex-wrap: wrap;

    gap: 5px;

    max-height: 105px;

    overflow-y: auto;

    padding-right: 4px;
}


.subject-tag {

    display: inline-block;

    padding: 4px 7px;

    border-radius: 6px;

    background: #f2f4f7;

    color: #475467;

    font-size: 10px;

    line-height: 1.3;

}


/* =====================================================
   CLASS
===================================================== */

.class-list {

    display: flex;

    flex-wrap: wrap;

    gap: 5px;

    max-width: 150px;
}


.class-tag {

    padding: 5px 8px;

    border-radius: 6px;

    background: #f2f4f7;

    color: #475467;

    font-size: 10px;
}


/* =====================================================
   TEACHER
===================================================== */

.teacher-list {

    display: flex;

    flex-direction: column;

    gap: 5px;

    max-width: 160px;
}


.teacher-tag {

    display: inline-block;

    padding: 5px 8px;

    border-radius: 6px;

    background: #f9f5ff;

    color: #6941c6;

    font-size: 10px;

}


.no-teacher {

    color: #98a2b3;

    font-style: italic;
}


/* =====================================================
   BOOKINGS
===================================================== */

.booking-number {

    font-size: 18px;

    font-weight: 800;

    color: #003366;
}


.booking-label {

    color: #98a2b3;

    font-size: 10px;
}


/* =====================================================
   PAYMENT
===================================================== */

.payment {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;
}


.payment.paid {

    background: #e7f7ed;

    color: #16803d;
}


.payment.pending {

    background: #fff4e5;

    color: #b54708;
}


.payment.none {

    background: #f2f4f7;

    color: #667085;
}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;
}


.status.active {

    background: #e7f7ed;

    color: #16803d;
}


.status.new {

    background: #eef4ff;

    color: #175cd3;
}


/* =====================================================
   VIEW BUTTON
===================================================== */

.view-button {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 8px 11px;

    border-radius: 8px;

    background: #003366;

    color: white;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;
}


.view-button:hover {

    background: #0055a5;
}


/* =====================================================
   EMPTY
===================================================== */

.empty-state {

    padding: 70px 20px;

    text-align: center;
}


.empty-icon {

    font-size: 45px;

    margin-bottom: 12px;
}


.empty-state h3 {

    margin: 0 0 7px;
}


.empty-state p {

    margin: 0;

    color: #98a2b3;

    font-size: 13px;
}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    padding: 25px 0 5px;

    color: #98a2b3;

    font-size: 11px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1250px) {

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


@media (max-width: 900px) {

    .sidebar {

        width: 70px;

        padding: 20px 8px;
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

        justify-content: center;
    }


    .main {

        margin-left: 70px;

        padding: 20px;
    }

}


@media (max-width: 600px) {

    .main {

        padding: 15px;
    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;
    }


    .stats {

        grid-template-columns: 1fr;
    }


    .filter-form {

        grid-template-columns: 1fr;
    }


    .filter-button,
    .clear-button {

        width: 100%;
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


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="topbar">


        <div class="page-title">


            <h1>

                Student Directory

            </h1>


            <p>

                Manage students, curricula,
                subjects, bookings and payments.

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

                    Without Bookings

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
                    as $item
                ): ?>


                    <option
                        value="<?= h($item) ?>"
                        <?= $subject === $item
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= h($item) ?>

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
                    as $item
                ): ?>


                    <option
                        value="<?= h($item) ?>"
                        <?= $curriculum === $item
                            ? 'selected'
                            : ''
                        ?>
                    >

                        <?= h($item) ?>

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
                        : ''
                    ?>
                >

                    Paid

                </option>


                <option
                    value="Pending"
                    <?= $payment === 'Pending'
                        ? 'selected'
                        : ''
                    ?>
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
                        : ''
                    ?>
                >

                    Active / Booked

                </option>


                <option
                    value="New"
                    <?= $status === 'New'
                        ? 'selected'
                        : ''
                    ?>
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

            <?php

            $hasFilters =
                $search !== ''
                ||
                $subject !== ''
                ||
                $curriculum !== ''
                ||
                $payment !== ''
                ||
                $status !== '';

            ?>


            <?php if (
                $hasFilters
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



        <!-- =================================================
             ACTIVE FILTERS
        ================================================== -->

        <?php if (
            $hasFilters
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
                        <?= h($search) ?>

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
                        <?= h($subject) ?>

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
                        <?= h($curriculum) ?>

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
                        <?= h($payment) ?>

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
                        <?= h($status) ?>

                    </span>


                <?php endif; ?>


            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         STUDENTS TABLE
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


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | CURRICULUMS
                        |--------------------------------------------------------------------------
                        */

                        $curriculumValues =
                            splitValues(
                                str_replace(
                                    '||',
                                    ',',
                                    $student[
                                        'curricula'
                                    ]
                                    ??
                                    ''
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | SUBJECTS
                        |--------------------------------------------------------------------------
                        */

                        $subjectValues =
                            splitValues(
                                str_replace(
                                    '||',
                                    ',',
                                    $student[
                                        'subjects'
                                    ]
                                    ??
                                    ''
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | CLASSES
                        |--------------------------------------------------------------------------
                        */

                        $classValues =
                            splitValues(
                                str_replace(
                                    '||',
                                    ',',
                                    $student[
                                        'class_years'
                                    ]
                                    ??
                                    ''
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | TEACHERS
                        |--------------------------------------------------------------------------
                        */

                        $teacherValues =
                            splitValues(
                                str_replace(
                                    '||',
                                    ',',
                                    $student[
                                        'teachers'
                                    ]
                                    ??
                                    ''
                                )
                            );

                        ?>


                        <tr>


                            <!-- =================================
                                 STUDENT
                            ================================== -->

                            <td>


                                <div
                                    class="student-profile"
                                >


                                    <div
                                        class="student-avatar"
                                    >

                                        <?= h(
                                            getInitials(
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



                            <!-- =================================
                                 CONTACT
                            ================================== -->

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
                                                $student[
                                                    'email'
                                                ]
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
                                                $student[
                                                    'phone'
                                                ]
                                            ) ?>

                                        </div>


                                    <?php endif; ?>


                                </div>


                            </td>



                            <!-- =================================
                                 DOB
                            ================================== -->

                            <td>


                                <?= h(
                                    formatDate(
                                        $student[
                                            'dob'
                                        ]
                                    )
                                ) ?>


                            </td>



                            <!-- =================================
                                 CURRICULUM / SUBJECT
                            ================================== -->

                            <td>


                                <div
                                    class="academic-box"
                                >


                                    <!-- CURRICULUM BADGES -->

                                    <?php if (
                                        !empty(
                                            $curriculumValues
                                        )
                                    ): ?>


                                        <div
                                            class="curriculum-list"
                                        >


                                            <?php foreach (
                                                $curriculumValues
                                                as $cur
                                            ): ?>


                                                <?php

                                                $curClass =
                                                    strtolower(
                                                        trim(
                                                            $cur
                                                        )
                                                    );

                                                ?>


                                                <span
                                                    class="
                                                        curriculum-badge
                                                        <?= h(
                                                            $curClass
                                                        ) ?>
                                                    "
                                                >

                                                    🎓

                                                    <?= h(
                                                        $cur
                                                    ) ?>

                                                </span>


                                            <?php endforeach; ?>


                                        </div>


                                    <?php else: ?>


                                        <span
                                            style="
                                                color:#98a2b3;
                                                font-size:11px;
                                            "
                                        >

                                            No curriculum

                                        </span>


                                    <?php endif; ?>



                                    <!-- SUBJECT TAGS -->

                                    <?php if (
                                        !empty(
                                            $subjectValues
                                        )
                                    ): ?>


                                        <div
                                            class="subject-list"
                                        >


                                            <?php foreach (
                                                $subjectValues
                                                as $sub
                                            ): ?>


                                                <span
                                                    class="subject-tag"
                                                >

                                                    📚

                                                    <?= h(
                                                        $sub
                                                    ) ?>

                                                </span>


                                            <?php endforeach; ?>


                                        </div>


                                    <?php else: ?>


                                        <div
                                            style="
                                                color:#98a2b3;
                                                font-size:11px;
                                                margin-top:5px;
                                            "
                                        >

                                            No subjects

                                        </div>


                                    <?php endif; ?>


                                </div>


                            </td>



                            <!-- =================================
                                 CLASS / YEAR
                            ================================== -->

                            <td>


                                <?php if (
                                    !empty(
                                        $classValues
                                    )
                                ): ?>


                                    <div
                                        class="class-list"
                                    >


                                        <?php foreach (
                                            $classValues
                                            as $class
                                        ): ?>


                                            <span
                                                class="class-tag"
                                            >

                                                <?= h(
                                                    $class
                                                ) ?>

                                            </span>


                                        <?php endforeach; ?>


                                    </div>


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



                            <!-- =================================
                                 TEACHER
                            ================================== -->

                            <td>


                                <?php if (
                                    !empty(
                                        $teacherValues
                                    )
                                ): ?>


                                    <div
                                        class="teacher-list"
                                    >


                                        <?php foreach (
                                            $teacherValues
                                            as $teacher
                                        ): ?>


                                            <span
                                                class="teacher-tag"
                                            >

                                                👨‍🏫

                                                <?= h(
                                                    $teacher
                                                ) ?>

                                            </span>


                                        <?php endforeach; ?>


                                    </div>


                                <?php else: ?>


                                    <span
                                        class="no-teacher"
                                    >

                                        Not assigned

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- =================================
                                 BOOKINGS
                            ================================== -->

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



                            <!-- =================================
                                 PAYMENT
                            ================================== -->

                            <td>


                                <?php

                                $paymentStatuses =
                                    strtolower(
                                        str_replace(
                                            '||',
                                            ',',
                                            $student[
                                                'payment_statuses'
                                            ]
                                            ??
                                            ''
                                        )
                                    );

                                ?>


                                <?php if (
                                    (int)(
                                        $student[
                                            'has_paid'
                                        ]
                                        ??
                                        0
                                    ) === 1
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



                            <!-- =================================
                                 STATUS
                            ================================== -->

                            <td>


                                <?php if (
                                    (int)(
                                        $student[
                                            'total_bookings'
                                        ]
                                        ??
                                        0
                                    ) > 0
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



                            <!-- =================================
                                 ACTION
                            ================================== -->

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



    <!-- FOOTER -->

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
