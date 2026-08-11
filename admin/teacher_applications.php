<?php

require "../admin_auth.php";
require "../config/db.php";


/* =========================================================
   NISEL ONLINE EDUCATION
   TEACHER APPLICATION MANAGEMENT
   PDO VERSION
========================================================= */

$message = "";
$message_type = "";

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$curriculum_filter = trim($_GET['curriculum'] ?? '');


/* =========================================================
   APPROVE / DENY / PENDING
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    isset($_POST['application_id'])
) {

    $application_id =
        (int) $_POST['application_id'];

    $action =
        $_POST['action'];


    /* =====================================================
       APPROVE
    ===================================================== */

    if ($action === "approve") {

        header(
            "Location: teacher_application_view.php?id="
            . $application_id
        );

        exit;
    }


    /* =====================================================
       DENY
    ===================================================== */

    if ($action === "deny") {

        try {

            $stmt = $pdo->prepare("
                UPDATE teacher_applications
                SET application_status = 'Rejected'
                WHERE id = ?
            ");

            $stmt->execute([
                $application_id
            ]);

            $message =
                "Teacher application rejected successfully.";

            $message_type =
                "success";

        } catch (PDOException $e) {

            $message =
                "Unable to update the application.";

            $message_type =
                "error";
        }
    }


    /* =====================================================
       SET PENDING
    ===================================================== */

    if ($action === "pending") {

        try {

            $stmt = $pdo->prepare("
                UPDATE teacher_applications
                SET application_status = 'Pending'
                WHERE id = ?
            ");

            $stmt->execute([
                $application_id
            ]);

            $message =
                "Application returned to Pending.";

            $message_type =
                "success";

        } catch (PDOException $e) {

            $message =
                "Unable to change application status.";

            $message_type =
                "error";
        }
    }

}


/* =========================================================
   LOAD APPLICATIONS
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM teacher_applications
        ORDER BY id DESC
    ");

    $stmt->execute();

    $all_applications =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die(
        "Unable to load teacher applications."
    );
}


/* =========================================================
   FILTER APPLICATIONS
========================================================= */

$applications = [];


foreach (
    $all_applications
    as $application
) {

    $application_status =
        trim(
            $application['application_status']
            ?? 'Pending'
        );


    $application_curriculum =
        trim(
            $application['curricula']
            ?? ''
        );


    $teacher_name =
        trim(
            $application['full_name']
            ?? ''
        );


    $teacher_email =
        trim(
            $application['email']
            ?? ''
        );


    /* SEARCH */

    if ($search !== '') {

        $searchText =
            strtolower(
                $teacher_name
                . ' '
                .
                $teacher_email
                . ' '
                .
                (
                    $application['application_reference']
                    ?? ''
                )
                . ' '
                .
                (
                    $application['subjects']
                    ?? ''
                )
            );


        if (
            strpos(
                $searchText,
                strtolower($search)
            ) === false
        ) {

            continue;
        }
    }


    /* STATUS FILTER */

    if (
        $status_filter !== ''
        &&
        strtolower(
            $application_status
        )
        !==
        strtolower(
            $status_filter
        )
    ) {

        continue;
    }


    /* CURRICULUM FILTER */

    if (
        $curriculum_filter !== ''
        &&
        stripos(
            $application_curriculum,
            $curriculum_filter
        ) === false
    ) {

        continue;
    }


    $applications[] =
        $application;
}


/* =========================================================
   STATISTICS
========================================================= */

$total_applications =
    count(
        $all_applications
    );


$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;


foreach (
    $all_applications
    as $application
) {

    $status =
        strtolower(
            trim(
                $application['application_status']
                ?? 'Pending'
            )
        );


    if ($status === 'approved') {

        $approved_count++;

    } elseif (
        $status === 'rejected'
        ||
        $status === 'denied'
    ) {

        $rejected_count++;

    } else {

        $pending_count++;
    }
}


/* =========================================================
   HELPER
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   GET INITIALS
========================================================= */

function getInitials($name)
{

    $name =
        trim($name);


    if ($name === '') {

        return 'T';
    }


    $parts =
        preg_split(
            '/\s+/',
            $name
        );


    if (count($parts) >= 2) {

        return strtoupper(
            substr(
                $parts[0],
                0,
                1
            )
            .
            substr(
                $parts[count($parts) - 1],
                0,
                1
            )
        );
    }


    return strtoupper(
        substr(
            $name,
            0,
            2
        )
    );
}


/* =========================================================
   STATUS CLASS
========================================================= */

function statusClass($status)
{

    $status =
        strtolower(
            trim($status)
        );


    if ($status === 'approved') {

        return 'approved';

    }


    if (
        $status === 'rejected'
        ||
        $status === 'denied'
    ) {

        return 'rejected';

    }


    return 'pending';
}


/* =========================================================
   UNIQUE CURRICULA
========================================================= */

$curricula = [];


foreach (
    $all_applications
    as $application
) {

    $curriculum =
        trim(
            $application['curricula']
            ?? ''
        );


    if (
        $curriculum !== ''
        &&
        !in_array(
            $curriculum,
            $curricula,
            true
        )
    ) {

        $curricula[] =
            $curriculum;
    }
}


sort($curricula);

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
Teacher Applications |
NISEL Admin
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    box-sizing:
        border-box;

    margin:
        0;

    padding:
        0;

}


body {

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        #f1f5f9;

    color:
        #25364a;

}


/* =========================================================
   SIDEBAR
========================================================= */

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
            #063b73,
            #075a91
        );

    color:
        white;

    padding:
        24px 14px;

    z-index:
        1000;

}


.logo {

    text-align:
        center;

    padding:
        4px 10px 24px;

    margin-bottom:
        18px;

    border-bottom:
        1px solid
        rgba(255,255,255,.14);

}


.logo-main {

    font-size:
        22px;

    font-weight:
        800;

    letter-spacing:
        1px;

}


.logo-small {

    font-size:
        9px;

    letter-spacing:
        2px;

    opacity:
        .7;

    margin-top:
        4px;

}


.menu {

    display:
        flex;

    flex-direction:
        column;

    gap:
        5px;

}


.menu a {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    color:
        white;

    text-decoration:
        none;

    padding:
        12px 14px;

    border-radius:
        9px;

    font-size:
        13px;

    transition:
        .2s;

}


.menu a:hover {

    background:
        rgba(255,255,255,.1);

}


.menu a.active {

    background:
        rgba(255,255,255,.17);

    box-shadow:
        inset 3px 0 0 #4bc9ff;

}


.menu-icon {

    width:
        22px;

    text-align:
        center;

    font-size:
        16px;

}


.logout {

    margin-top:
        18px;

    border-top:
        1px solid
        rgba(255,255,255,.12);

    padding-top:
        18px !important;

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:
        245px;

    padding:
        28px;

    min-height:
        100vh;

}


/* =========================================================
   TOP HEADER
========================================================= */

.top-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        23px;

}


.header-title h1 {

    font-size:
        26px;

    color:
        #073b70;

    margin-bottom:
        5px;

}


.header-title p {

    color:
        #7a8796;

    font-size:
        13px;

}


.admin-badge {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    background:
        white;

    padding:
        8px 12px;

    border-radius:
        10px;

    box-shadow:
        0 4px 15px
        rgba(0,0,0,.05);

}


.admin-avatar {

    width:
        34px;

    height:
        34px;

    border-radius:
        50%;

    background:
        #e7f2fb;

    color:
        #063b73;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

    font-size:
        12px;

}


.admin-text strong {

    display:
        block;

    font-size:
        11px;

    color:
        #34495e;

}


.admin-text span {

    display:
        block;

    font-size:
        9px;

    color:
        #8b97a5;

    margin-top:
        2px;

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        16px;

    margin-bottom:
        22px;

}


.stat-card {

    background:
        white;

    border-radius:
        14px;

    padding:
        18px;

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

    box-shadow:
        0 6px 20px
        rgba(0,0,0,.045);

}


.stat-icon {

    width:
        48px;

    height:
        48px;

    border-radius:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        21px;

    background:
        #edf6fc;

}


.stat-number {

    font-size:
        23px;

    font-weight:
        800;

    color:
        #063b73;

}


.stat-label {

    color:
        #8290a0;

    font-size:
        10px;

    margin-top:
        3px;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding:
        13px 16px;

    border-radius:
        10px;

    margin-bottom:
        18px;

    font-size:
        13px;

}


.message.success {

    background:
        #eaf8ef;

    border:
        1px solid #bce8cd;

    color:
        #18794e;

}


.message.error {

    background:
        #fff0f0;

    border:
        1px solid #efc0bd;

    color:
        #b42318;

}


/* =========================================================
   MAIN CARD
========================================================= */

.card {

    background:
        white;

    border-radius:
        16px;

    box-shadow:
        0 7px 24px
        rgba(0,0,0,.045);

    overflow:
        hidden;

}


/* =========================================================
   CARD HEADER
========================================================= */

.card-header {

    padding:
        20px 22px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        15px;

    border-bottom:
        1px solid
        #edf1f5;

}


.card-title {

    color:
        #073b70;

    font-size:
        17px;

    font-weight:
        800;

}


.card-subtitle {

    color:
        #8a96a4;

    font-size:
        11px;

    margin-top:
        4px;

}


/* =========================================================
   FILTER AREA
========================================================= */

.filters {

    padding:
        17px 22px;

    background:
        #fbfcfd;

    border-bottom:
        1px solid
        #edf1f5;

    display:
        grid;

    grid-template-columns:
        minmax(220px, 1.5fr)
        1fr
        1fr
        auto;

    gap:
        10px;

}


.search-box {

    position:
        relative;

}


.search-box span {

    position:
        absolute;

    left:
        13px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        #9aa6b5;

}


.filters input,
.filters select {

    width:
        100%;

    height:
        40px;

    border:
        1px solid
        #dce3eb;

    border-radius:
        9px;

    background:
        white;

    padding:
        0 12px;

    font-size:
        12px;

    color:
        #425466;

    outline:
        none;

}


.search-box input {

    padding-left:
        36px;

}


.filters input:focus,
.filters select:focus {

    border-color:
        #0875c1;

    box-shadow:
        0 0 0 3px
        rgba(8,117,193,.08);

}


.filter-button {

    height:
        40px;

    padding:
        0 15px;

    border:
        none;

    border-radius:
        9px;

    background:
        #063b73;

    color:
        white;

    font-size:
        12px;

    font-weight:
        700;

    cursor:
        pointer;

}


.clear-button {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        0 13px;

    height:
        40px;

    border-radius:
        9px;

    background:
        #edf3f8;

    color:
        #526579;

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        700;

}


/* =========================================================
   TABLE
========================================================= */

.table-wrapper {

    overflow-x:
        auto;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

    min-width:
        1050px;

}


thead th {

    background:
        #f7f9fb;

    color:
        #617286;

    text-align:
        left;

    font-size:
        10px;

    text-transform:
        uppercase;

    letter-spacing:
        .45px;

    padding:
        13px 14px;

    border-bottom:
        1px solid
        #e7edf3;

    white-space:
        nowrap;

}


tbody td {

    padding:
        14px;

    border-bottom:
        1px solid
        #edf1f5;

    font-size:
        11px;

    color:
        #526579;

    vertical-align:
        middle;

}


tbody tr {

    transition:
        background .15s;

}


tbody tr:hover {

    background:
        #fafcff;

}


/* =========================================================
   TEACHER CELL
========================================================= */

.teacher-cell {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    min-width:
        190px;

}


.teacher-avatar {

    width:
        40px;

    height:
        40px;

    border-radius:
        11px;

    background:
        linear-gradient(
            135deg,
            #e7f3fb,
            #d6eaf8
        );

    color:
        #07558c;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

    font-size:
        12px;

    flex-shrink:
        0;

}


.teacher-name {

    color:
        #263f57;

    font-weight:
        750;

    font-size:
        12px;

}


.teacher-email {

    color:
        #96a1ad;

    font-size:
        9px;

    margin-top:
        3px;

}


/* =========================================================
   REFERENCE
========================================================= */

.reference {

    font-family:
        monospace;

    color:
        #617286;

    background:
        #f4f7fa;

    padding:
        5px 7px;

    border-radius:
        5px;

    font-size:
        9px;

    white-space:
        nowrap;

}


/* =========================================================
   SUBJECT
========================================================= */

.subject {

    max-width:
        180px;

    color:
        #425466;

    line-height:
        1.5;

}


.curriculum {

    display:
        inline-block;

    background:
        #edf6fc;

    color:
        #0867b2;

    padding:
        5px 8px;

    border-radius:
        6px;

    font-size:
        9px;

    font-weight:
        700;

}


/* =========================================================
   STATUS
========================================================= */

.status {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        5px;

    padding:
        6px 10px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        800;

    white-space:
        nowrap;

}


.status::before {

    content:
        "";

    width:
        6px;

    height:
        6px;

    border-radius:
        50%;

}


.status.pending {

    background:
        #fff6dc;

    color:
        #8a6800;

}


.status.pending::before {

    background:
        #e6b400;

}


.status.approved {

    background:
        #e9f8ef;

    color:
        #18794e;

}


.status.approved::before {

    background:
        #20a35a;

}


.status.rejected {

    background:
        #fff0f0;

    color:
        #b42318;

}


.status.rejected::before {

    background:
        #dc3545;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    flex-wrap:
        wrap;

}


.action {

    border:
        none;

    padding:
        7px 9px;

    border-radius:
        7px;

    font-size:
        9px;

    font-weight:
        700;

    cursor:
        pointer;

    text-decoration:
        none;

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    white-space:
        nowrap;

}


.action-view {

    background:
        #edf5fb;

    color:
        #07558c;

}


.action-approve {

    background:
        #e8f7ee;

    color:
        #18794e;

}


.action-pending {

    background:
        #fff4d7;

    color:
        #8a6800;

}


.action-deny {

    background:
        #fff0f0;

    color:
        #b42318;

}


.action:hover {

    opacity:
        .78;

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding:
        65px 20px;

    text-align:
        center;

    color:
        #8a96a4;

}


.empty-icon {

    font-size:
        42px;

    margin-bottom:
        10px;

}


.empty h3 {

    color:
        #526579;

    font-size:
        15px;

    margin-bottom:
        5px;

}


.empty p {

    font-size:
        11px;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:
        center;

    color:
        #98a3af;

    font-size:
        10px;

    padding:
        18px 0 5px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .filters {

        grid-template-columns:
            1fr 1fr;

    }

}


@media(max-width: 800px) {

    .sidebar {

        position:
            relative;

        width:
            100%;

        height:
            auto;

        padding:
            15px;

    }


    .main {

        margin-left:
            0;

        padding:
            18px;

    }


    .menu {

        display:
            grid;

        grid-template-columns:
            repeat(2,1fr);

    }


    .logout {

        margin-top:
            0;

        border-top:
            none;

        padding-top:
            12px !important;

    }


    .top-header {

        align-items:
            flex-start;

    }


    .admin-badge {

        display:
            none;

    }

}


@media(max-width: 600px) {

    .stats {

        grid-template-columns:
            1fr;

    }


    .filters {

        grid-template-columns:
            1fr;

    }


    .top-header {

        margin-bottom:
            18px;

    }


    .header-title h1 {

        font-size:
            22px;

    }


    .card-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }

}


@media(max-width: 430px) {

    .menu {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar">


    <div class="logo">

        <div class="logo-main">
            NISEL
        </div>

        <div class="logo-small">
            ONLINE EDUCATION
        </div>

    </div>


    <nav class="menu">


        <a href="dashboard.php">

            <span class="menu-icon">
                🏠
            </span>

            Dashboard

        </a>


        <a href="students.php">

            <span class="menu-icon">
                👨‍🎓
            </span>

            Students

        </a>


        <a href="teachers.php">

            <span class="menu-icon">
                👨‍🏫
            </span>

            Teachers

        </a>


        <a
            href="teacher_application.php"
            class="active"
        >

            <span class="menu-icon">
                📄
            </span>

            Teacher Applications

        </a>


        <a href="bookings.php">

            <span class="menu-icon">
                📚
            </span>

            Bookings

        </a>


        <a href="payments.php">

            <span class="menu-icon">
                💳
            </span>

            Payments

        </a>


        <a href="reports.php">

            <span class="menu-icon">
                📊
            </span>

            Reports

        </a>


        <a href="settings.php">

            <span class="menu-icon">
                ⚙️
            </span>

            Settings

        </a>


        <a
            href="logout.php"
            class="logout"
        >

            <span class="menu-icon">
                🚪
            </span>

            Logout

        </a>


    </nav>

</aside>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- HEADER -->

    <div class="top-header">


        <div class="header-title">

            <h1>
                Teacher Applications
            </h1>

            <p>
                Review and manage teacher applications.
            </p>

        </div>


        <div class="admin-badge">

            <div class="admin-avatar">
                A
            </div>

            <div class="admin-text">

                <strong>
                    Administrator
                </strong>

                <span>
                    NISEL Admin Portal
                </span>

            </div>

        </div>


    </div>



    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="message
            <?= h($message_type); ?>"
        >

            <?= $message_type === 'success'
                ? '✓'
                : '⚠'; ?>

            &nbsp;

            <?= h($message); ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                📄
            </div>

            <div>

                <div class="stat-number">
                    <?= $total_applications; ?>
                </div>

                <div class="stat-label">
                    Total Applications
                </div>

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <div class="stat-number">
                    <?= $pending_count; ?>
                </div>

                <div class="stat-label">
                    Pending Review
                </div>

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <div class="stat-number">
                    <?= $approved_count; ?>
                </div>

                <div class="stat-label">
                    Approved
                </div>

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                ✕
            </div>

            <div>

                <div class="stat-number">
                    <?= $rejected_count; ?>
                </div>

                <div class="stat-label">
                    Rejected
                </div>

            </div>

        </div>


    </section>



    <!-- =====================================================
         APPLICATION CARD
    ====================================================== -->

    <section class="card">


        <!-- CARD HEADER -->

        <div class="card-header">


            <div>

                <div class="card-title">
                    Applications
                </div>

                <div class="card-subtitle">

                    <?= count($applications); ?>
                    application(s) currently displayed

                </div>

            </div>


        </div>



        <!-- =================================================
             FILTERS
        ================================================== -->

        <form
            method="GET"
            class="filters"
        >


            <!-- SEARCH -->

            <div class="search-box">

                <span>
                    🔍
                </span>

                <input
                    type="text"
                    name="search"
                    value="<?= h($search); ?>"
                    placeholder="Search name, email, reference or subject..."
                >

            </div>


            <!-- STATUS -->

            <select
                name="status"
            >

                <option value="">
                    All Statuses
                </option>

                <option
                    value="Pending"
                    <?= $status_filter === 'Pending'
                        ? 'selected'
                        : ''; ?>
                >
                    Pending
                </option>

                <option
                    value="Approved"
                    <?= $status_filter === 'Approved'
                        ? 'selected'
                        : ''; ?>
                >
                    Approved
                </option>

                <option
                    value="Rejected"
                    <?= $status_filter === 'Rejected'
                        ? 'selected'
                        : ''; ?>
                >
                    Rejected
                </option>

            </select>


            <!-- CURRICULUM -->

            <select
                name="curriculum"
            >

                <option value="">
                    All Curricula
                </option>


                <?php foreach (
                    $curricula
                    as $curriculum
                ): ?>

                    <option
                        value="<?= h($curriculum); ?>"
                        <?= $curriculum_filter === $curriculum
                            ? 'selected'
                            : ''; ?>
                    >

                        <?= h($curriculum); ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- FILTER BUTTON -->

            <button
                type="submit"
                class="filter-button"
            >

                Filter

            </button>


            <?php if (
                $search !== ''
                ||
                $status_filter !== ''
                ||
                $curriculum_filter !== ''
            ): ?>

                <a
                    href="teacher_application.php"
                    class="clear-button"
                >

                    Clear

                </a>

            <?php endif; ?>


        </form>



        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="table-wrapper">


            <?php if (
                count($applications) > 0
            ): ?>


                <table>


                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Teacher
                            </th>

                            <th>
                                Reference
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Subjects
                            </th>

                            <th>
                                Curriculum
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $applications
                        as $application
                    ): ?>


                        <?php

                        $status =
                            trim(
                                $application['application_status']
                                ??
                                'Pending'
                            );


                        $status_class =
                            statusClass(
                                $status
                            );


                        $name =
                            trim(
                                $application['full_name']
                                ??
                                'Teacher'
                            );


                        $email =
                            trim(
                                $application['email']
                                ??
                                ''
                            );


                        $reference =
                            trim(
                                $application['application_reference']
                                ??
                                ''
                            );


                        $phone =
                            trim(
                                $application['phone']
                                ??
                                ''
                            );


                        $subjects =
                            trim(
                                $application['subjects']
                                ??
                                ''
                            );


                        $curriculum =
                            trim(
                                $application['curricula']
                                ??
                                ''
                            );


                        $initials =
                            getInitials(
                                $name
                            );

                        ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <strong
                                    style="
                                        color:#718096;
                                    "
                                >

                                    #
                                    <?= h(
                                        $application['id']
                                    ); ?>

                                </strong>

                            </td>



                            <!-- TEACHER -->

                            <td>


                                <div class="teacher-cell">


                                    <div
                                        class="teacher-avatar"
                                    >

                                        <?= h(
                                            $initials
                                        ); ?>

                                    </div>


                                    <div>

                                        <div
                                            class="teacher-name"
                                        >

                                            <?= h(
                                                $name
                                            ); ?>

                                        </div>


                                        <div
                                            class="teacher-email"
                                        >

                                            <?= h(
                                                $email
                                            ); ?>

                                        </div>

                                    </div>


                                </div>


                            </td>



                            <!-- REFERENCE -->

                            <td>

                                <?php if (
                                    $reference !== ''
                                ): ?>

                                    <span
                                        class="reference"
                                    >

                                        <?= h(
                                            $reference
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        style="
                                            color:#9aa5b1;
                                        "
                                    >
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- PHONE -->

                            <td>

                                <?= h(
                                    $phone
                                ); ?>

                            </td>



                            <!-- SUBJECT -->

                            <td>

                                <div class="subject">

                                    <?= h(
                                        $subjects
                                    ); ?>

                                </div>

                            </td>



                            <!-- CURRICULUM -->

                            <td>

                                <?php if (
                                    $curriculum !== ''
                                ): ?>

                                    <span
                                        class="curriculum"
                                    >

                                        <?= h(
                                            $curriculum
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    <span
                                        style="
                                            color:#9aa5b1;
                                        "
                                    >
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        status
                                        <?= h(
                                            $status_class
                                        ); ?>
                                    "
                                >

                                    <?= h(
                                        $status
                                    ); ?>

                                </span>

                            </td>



                            <!-- ACTIONS -->

                            <td>


                                <div class="actions">


                                    <!-- VIEW -->

                                    <a
                                        href="
                                            teacher_application_view.php?id=<?= (int)
                                            $application['id']; ?>
                                        "
                                        class="
                                            action
                                            action-view
                                        "
                                    >

                                        👁 View

                                    </a>



                                    <!-- APPROVE -->

                                    <form
                                        method="POST"
                                        style="
                                            display:inline;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= (int)
                                                $application['id']; ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="
                                                action
                                                action-approve
                                            "
                                            onclick="
                                                return confirm(
                                                    'Open this application for approval?'
                                                );
                                            "
                                        >

                                            ✓ Approve

                                        </button>

                                    </form>



                                    <!-- PENDING -->

                                    <form
                                        method="POST"
                                        style="
                                            display:inline;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= (int)
                                                $application['id']; ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="pending"
                                            class="
                                                action
                                                action-pending
                                            "
                                            onclick="
                                                return confirm(
                                                    'Return this application to Pending?'
                                                );
                                            "
                                        >

                                            ⏳ Pending

                                        </button>

                                    </form>



                                    <!-- DENY -->

                                    <form
                                        method="POST"
                                        style="
                                            display:inline;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="application_id"
                                            value="<?= (int)
                                                $application['id']; ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="deny"
                                            class="
                                                action
                                                action-deny
                                            "
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to reject this application?'
                                                );
                                            "
                                        >

                                            ✕ Reject

                                        </button>

                                    </form>


                                </div>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <div class="empty">

                    <div class="empty-icon">
                        📄
                    </div>

                    <h3>
                        No teacher applications found
                    </h3>

                    <p>
                        Try changing your search or filter settings.
                    </p>

                </div>


            <?php endif; ?>


        </div>


    </section>



    <!-- FOOTER -->

    <div class="footer">

        © <?= date('Y'); ?>
        NISEL ONLINE EDUCATION.
        Administrator Portal.

    </div>


</main>


</body>

</html>
