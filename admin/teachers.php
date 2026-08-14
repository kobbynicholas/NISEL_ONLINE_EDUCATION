<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN TEACHER DIRECTORY
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
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

require "../config/db.php";


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
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search =
    trim($_GET['search'] ?? '');

$subject =
    trim($_GET['subject'] ?? '');

$curriculum =
    trim($_GET['curriculum'] ?? '');

$status =
    trim($_GET['status'] ?? '');


/*
|--------------------------------------------------------------------------
| GET ALL SUBJECTS
|--------------------------------------------------------------------------
|
| We retrieve subjects from the teachers table so the filter can
| automatically reflect the subjects actually used by your teachers.
|
*/

try {

    $subjectStmt = $pdo->query("
        SELECT subjects
        FROM teachers
        WHERE subjects IS NOT NULL
        AND TRIM(subjects) <> ''
    ");

    $subjectRows =
        $subjectStmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {

    $subjectRows = [];

}


/*
|--------------------------------------------------------------------------
| CREATE UNIQUE SUBJECT LIST
|--------------------------------------------------------------------------
|
| Teachers may have multiple subjects stored in one field, for example:
|
| Mathematics, Physics
|
| We separate those values for the subject filter.
|
*/

$subjectList = [];


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


/*
|--------------------------------------------------------------------------
| REMOVE DUPLICATES
|--------------------------------------------------------------------------
*/

$subjectList =
    array_values(
        array_unique(
            $subjectList
        )
    );


/*
|--------------------------------------------------------------------------
| SORT SUBJECTS
|--------------------------------------------------------------------------
*/

natcasesort(
    $subjectList
);

$subjectList =
    array_values(
        $subjectList
    );


/*
|--------------------------------------------------------------------------
| CURRICULUM LIST
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
| BUILD TEACHER QUERY
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        id,
        teacher_id,
        teacher_name,
        phone,
        email,
        qualification,
        subjects,
        curriculum,
        experience,
        bio,
        availability,
        photo,
        zoom_link,
        status

    FROM teachers

    WHERE 1 = 1

";


$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if (
    $search !== ''
) {

    $sql .= "

        AND (

            teacher_name LIKE :search
            OR email LIKE :search
            OR phone LIKE :search
            OR subjects LIKE :search
            OR teacher_id LIKE :search

        )

    ";

    $params[':search'] =
        '%' .
        $search .
        '%';

}


/*
|--------------------------------------------------------------------------
| SUBJECT FILTER
|--------------------------------------------------------------------------
|
| FIND_IN_SET handles comma-separated subjects.
| The LIKE condition also supports values such as:
|
| Mathematics, Physics
| Mathematics / Physics
| Mathematics;Physics
|
*/

if (
    $subject !== ''
) {

    $sql .= "

        AND (

            subjects LIKE :subject1
            OR subjects LIKE :subject2
            OR subjects LIKE :subject3
            OR subjects LIKE :subject4

        )

    ";

    $params[':subject1'] =
        $subject;

    $params[':subject2'] =
        $subject . ',%';

    $params[':subject3'] =
        '%,' . $subject;

    $params[':subject4'] =
        '%,' . $subject . ',%';

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

        AND curriculum LIKE :curriculum

    ";

    $params[':curriculum'] =
        '%' .
        $curriculum .
        '%';

}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if (
    $status !== ''
) {

    $sql .= "

        AND status = :status

    ";

    $params[':status'] =
        $status;

}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY
        teacher_name ASC

";


/*
|--------------------------------------------------------------------------
| GET TEACHERS
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

    $teachers =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (
    PDOException $e
) {

    die(
        "Unable to load teachers: "
        .
        h(
            $e->getMessage()
        )
    );

}


/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalTeachers =
    count($teachers);


$activeTeachers =
    0;


foreach (
    $teachers
    as $teacher
) {

    if (
        strtolower(
            trim(
                $teacher['status']
                ??
                ''
            )
        )
        ===
        'active'
    ) {

        $activeTeachers++;

    }

}


/*
|--------------------------------------------------------------------------
| PHOTO FUNCTION
|--------------------------------------------------------------------------
*/

function teacherPhoto($photo)
{

    if (
        empty($photo)
    ) {

        return '';

    }


    /*
    |--------------------------------------------------------------------------
    | POSSIBLE PHOTO LOCATIONS
    |--------------------------------------------------------------------------
    */

    $locations = [

        "../uploads/teachers/photos/"
        . $photo,

        "../uploads/teachers/"
        . $photo

    ];


    foreach (
        $locations as $location
    ) {

        $physicalPath =
            __DIR__
            .
            "/"
            .
            $location;


        if (
            file_exists(
                $physicalPath
            )
        ) {

            return $location;

        }

    }


    return '';

}


/*
|--------------------------------------------------------------------------
| AVAILABILITY
|--------------------------------------------------------------------------
*/

function formatAvailability(
    $availability
)
{

    if (
        empty(
            trim(
                $availability ?? ''
            )
        )
    ) {

        return 'Not provided';

    }


    return $availability;

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
    Teachers | NISEL ONLINE EDUCATION
</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    margin: 0;

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

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 25px;

    gap: 20px;

}


.page-title h1 {

    margin: 0;

    font-size: 29px;

}


.page-title p {

    margin:
        7px 0 0;

    color:
        #667085;

    font-size: 14px;

}


.admin-badge {

    display:
        flex;

    align-items:
        center;

    gap: 10px;

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

    font-size: 13px;

}


.admin-avatar {

    width: 35px;

    height: 35px;

    border-radius: 50%;

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
            2,
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

    display:
        flex;

    align-items:
        center;

    gap: 15px;

}


.stat-icon {

    width: 52px;

    height: 52px;

    border-radius: 12px;

    background:
        #e8f2ff;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size: 23px;

}


.stat-number {

    font-size: 24px;

    font-weight: 800;

}


.stat-label {

    margin-top: 3px;

    color:
        #667085;

    font-size: 12px;

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

    margin: 0;

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
        auto
        auto;

    gap:
        10px;

}


.input-wrapper {

    position:
        relative;

}


.input-icon {

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

    color:
        #344054;

    outline:
        none;

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
   TABLE CARD
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

    justify-content:
        space-between;

    align-items:
        center;

    padding:
        20px;

    border-bottom:
        1px solid
        #eaecf0;

}


.table-header h2 {

    margin: 0;

    font-size: 17px;

}


.teacher-count {

    color:
        #667085;

    font-size: 13px;

}


.table-wrapper {

    overflow-x:
        auto;

}


table {

    width:
        100%;

    min-width:
        1150px;

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
   TEACHER PROFILE
===================================================== */

.teacher-profile {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    min-width:
        190px;

}


.teacher-photo,
.photo-placeholder {

    width:
        52px;

    height:
        52px;

    border-radius:
        50%;

}


.teacher-photo {

    object-fit:
        cover;

    border:
        2px solid
        #e4e7ec;

}


.photo-placeholder {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #e8f2ff;

    color:
        #003366;

    font-size:
        21px;

}


.teacher-name {

    font-weight:
        700;

}


.teacher-id {

    color:
        #98a2b3;

    font-size:
        11px;

    margin-top:
        3px;

}


/* =====================================================
   SUBJECT
===================================================== */

.subject {

    max-width:
        200px;

    line-height:
        1.5;

}


.curriculum {

    display:
        inline-block;

    margin-top:
        6px;

    padding:
        4px 8px;

    border-radius:
        20px;

    background:
        #eef4ff;

    color:
        #344054;

    font-size:
        10px;

    font-weight:
        700;

}


/* =====================================================
   CONTACT
===================================================== */

.contact {

    white-space:
        nowrap;

}


.contact-item {

    margin-bottom:
        6px;

}


.contact-item:last-child {

    margin-bottom:
        0;

}


/* =====================================================
   AVAILABILITY
===================================================== */

.availability {

    max-width:
        200px;

    color:
        #475467;

    line-height:
        1.5;

}


.availability.empty {

    color:
        #98a2b3;

    font-style:
        italic;

}


/* =====================================================
   ZOOM
===================================================== */

.zoom-button {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        8px 11px;

    border-radius:
        8px;

    background:
        #eaf4ff;

    color:
        #0066a1;

    text-decoration:
        none;

    font-size:
        12px;

    font-weight:
        700;

}


.zoom-button:hover {

    background:
        #d9ecff;

}


.no-zoom {

    color:
        #98a2b3;

    font-size:
        12px;

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


.status.inactive {

    background:
        #f1f3f5;

    color:
        #667085;

}


/* =====================================================
   EMPTY
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

    margin: 0;

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
    max-width: 1100px
) {

    .filter-form {

        grid-template-columns:
            1fr
            1fr;

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
====================================================== -->

<main class="main">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="topbar">


        <div class="page-title">


            <h1>

                Teacher Directory

            </h1>


            <p>

                Manage and view NISEL teachers,
                subjects, curricula and availability.

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

                👨‍🏫

            </div>


            <div>

                <div class="stat-number">

                    <?= $totalTeachers ?>

                </div>


                <div class="stat-label">

                    Teachers Found

                </div>

            </div>


        </div>


        <div class="stat-card">


            <div class="stat-icon">

                🟢

            </div>


            <div>

                <div class="stat-number">

                    <?= $activeTeachers ?>

                </div>


                <div class="stat-label">

                    Active Teachers

                </div>

            </div>


        </div>


    </div>



    <!-- =================================================
         FILTER CARD
    ================================================== -->

    <div class="filter-card">


        <div class="filter-title">


            <h2>

                🔎 Find a Teacher

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

            <div class="input-wrapper">


                <span class="input-icon">

                    🔍

                </span>


                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Search teacher, email, phone or ID..."
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



            <!-- STATUS -->

            <select
                name="status"
                class="filter-select"
            >

                <option value="">

                    All Status

                </option>


                <option
                    value="Active"
                    <?= $status === 'Active'
                        ? 'selected'
                        : '' ?>
                >

                    Active

                </option>


                <option
                    value="Inactive"
                    <?= $status === 'Inactive'
                        ? 'selected'
                        : '' ?>
                >

                    Inactive

                </option>


            </select>



            <!-- BUTTON -->

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
            ): ?>


                <a
                    href="teachers.php"
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
            $search !== ''
            ||
            $subject !== ''
            ||
            $curriculum !== ''
            ||
            $status !== ''
        ): ?>


            <div class="active-filters">


                <span class="filter-label">

                    Active filters:

                </span>


                <?php if (
                    $search !== ''
                ): ?>


                    <span class="filter-tag">

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


                    <span class="filter-tag">

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


                    <span class="filter-tag">

                        🎓
                        Curriculum:
                        <?= h(
                            $curriculum
                        ) ?>

                    </span>


                <?php endif; ?>


                <?php if (
                    $status !== ''
                ): ?>


                    <span class="filter-tag">

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
         TEACHER TABLE
    ================================================== -->

    <div class="table-card">


        <div class="table-header">


            <h2>

                👨‍🏫 Teacher Information

            </h2>


            <div class="teacher-count">

                <?= $totalTeachers ?>

                result(s)

            </div>


        </div>


        <?php if (
            empty($teachers)
        ): ?>


            <div class="empty-state">


                <div class="empty-icon">

                    🔎

                </div>


                <h3>

                    No Teachers Found

                </h3>


                <p>

                    Try changing the subject,
                    curriculum or search filters.

                </p>


            </div>


        <?php else: ?>


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Teacher
                            </th>

                            <th>
                                Subject / Curriculum
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Available Time
                            </th>

                            <th>
                                Zoom Link
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $teachers
                        as $teacher
                    ): ?>


                        <tr>


                            <!-- TEACHER -->

                            <td>


                                <div
                                    class="teacher-profile"
                                >


                                    <?php

                                    $photoPath =
                                        teacherPhoto(
                                            $teacher['photo']
                                            ??
                                            ''
                                        );

                                    ?>


                                    <?php if (
                                        $photoPath !== ''
                                    ): ?>


                                        <img
                                            src="<?= h(
                                                $photoPath
                                            ) ?>"
                                            class="teacher-photo"
                                            alt="<?= h(
                                                $teacher['teacher_name']
                                            ) ?>"
                                        >


                                    <?php else: ?>


                                        <div
                                            class="photo-placeholder"
                                        >

                                            👤

                                        </div>


                                    <?php endif; ?>


                                    <div>


                                        <div
                                            class="teacher-name"
                                        >

                                            <?= h(
                                                $teacher['teacher_name']
                                            ) ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $teacher['teacher_id']
                                            )
                                        ): ?>


                                            <div
                                                class="teacher-id"
                                            >

                                                <?= h(
                                                    $teacher['teacher_id']
                                                ) ?>

                                            </div>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            </td>



                            <!-- SUBJECT -->

                            <td>


                                <div
                                    class="subject"
                                >

                                    <?= nl2br(
                                        h(
                                            $teacher['subjects']
                                        )
                                    ) ?>


                                    <?php if (
                                        !empty(
                                            $teacher['curriculum']
                                        )
                                    ): ?>


                                        <span
                                            class="curriculum"
                                        >

                                            <?= h(
                                                $teacher['curriculum']
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


                                </div>


                            </td>



                            <!-- EMAIL -->

                            <td>


                                <div
                                    class="contact"
                                >

                                    <?php if (
                                        !empty(
                                            $teacher['email']
                                        )
                                    ): ?>


                                        📧

                                        <?= h(
                                            $teacher['email']
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


                                </div>


                            </td>



                            <!-- PHONE -->

                            <td>


                                <?php if (
                                    !empty(
                                        $teacher['phone']
                                    )
                                ): ?>


                                    📞

                                    <?= h(
                                        $teacher['phone']
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



                            <!-- AVAILABILITY -->

                            <td>


                                <div
                                    class="
                                        availability
                                        <?= empty(
                                            trim(
                                                $teacher['availability']
                                                ??
                                                ''
                                            )
                                        )
                                            ? 'empty'
                                            : ''
                                        ?>
                                    "
                                >

                                    🕐

                                    <?= nl2br(
                                        h(
                                            formatAvailability(
                                                $teacher['availability']
                                            )
                                        )
                                    ) ?>

                                </div>


                            </td>



                            <!-- ZOOM -->

                            <td>


                                <?php if (
                                    !empty(
                                        $teacher['zoom_link']
                                    )
                                ): ?>


                                    <a
                                        href="<?= h(
                                            $teacher['zoom_link']
                                        ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="zoom-button"
                                    >

                                        🎥
                                        Open Zoom

                                    </a>


                                <?php else: ?>


                                    <span
                                        class="no-zoom"
                                    >

                                        No Zoom link

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- STATUS -->

                            <td>


                                <?php

                                $teacherStatus =
                                    strtolower(
                                        trim(
                                            $teacher['status']
                                            ??
                                            'Active'
                                        )
                                    );

                                ?>


                                <span
                                    class="
                                        status
                                        <?= $teacherStatus === 'active'
                                            ? 'active'
                                            : 'inactive'
                                        ?>
                                    "
                                >

                                    <?= h(
                                        ucfirst(
                                            $teacherStatus
                                        )
                                    ) ?>

                                </span>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php endif; ?>


    </div>



    <div class="footer">

        NISEL ONLINE EDUCATION
        ·
        Administrator Teacher Directory

    </div>


</main>


</body>

</html>
