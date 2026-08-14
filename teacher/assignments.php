<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER ASSIGNMENTS
| MODERN PDO VERSION
|--------------------------------------------------------------------------
*/

require "../teacher_auth.php";
require "../config/db.php";


/*
|--------------------------------------------------------------------------
| TEACHER SESSION
|--------------------------------------------------------------------------
*/

$teacher_id =
    $_SESSION['teacher_id'];

$teacher_name =
    $_SESSION['teacher_name']
    ?? 'Teacher';


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = "";

$message_type = "";

$search = "";

$status_filter = "";

$curriculum_filter = "";

$subject_filter = "";


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
| PROCESS POST ACTIONS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action =
        trim(
            $_POST['action'] ?? ''
        );


    $booking_id =
        filter_input(
            INPUT_POST,
            'booking_id',
            FILTER_VALIDATE_INT
        );


    /*
    |--------------------------------------------------------------------------
    | UPDATE ASSIGNMENT STATUS
    |--------------------------------------------------------------------------
    */

    if (
        $action === 'update_status'
        &&
        $booking_id
    ) {

        $new_status =
            trim(
                $_POST['assignment_status']
                ?? ''
            );


        $allowed_statuses = [
            'Assigned',
            'Active',
            'Completed',
            'Cancelled'
        ];


        if (
            !in_array(
                $new_status,
                $allowed_statuses,
                true
            )
        ) {

            $message =
                "Invalid assignment status.";

            $message_type =
                "error";

        } else {

            try {

                /*
                --------------------------------------------------------------
                | SECURITY CHECK
                --------------------------------------------------------------
                |
                | The booking MUST belong to the logged-in teacher.
                |
                */

                $check =
                    $pdo->prepare("
                        SELECT id
                        FROM bookings
                        WHERE id = ?
                        AND teacher_id = ?
                        LIMIT 1
                    ");


                $check->execute([
                    $booking_id,
                    $teacher_id
                ]);


                if (
                    !$check->fetch(
                        PDO::FETCH_ASSOC
                    )
                ) {

                    $message =
                        "You are not authorised to update this assignment.";

                    $message_type =
                        "error";

                } else {

                    $update =
                        $pdo->prepare("
                            UPDATE bookings
                            SET assignment_status = ?
                            WHERE id = ?
                            AND teacher_id = ?
                        ");


                    $update->execute([
                        $new_status,
                        $booking_id,
                        $teacher_id
                    ]);


                    $message =
                        "Assignment status updated successfully.";

                    $message_type =
                        "success";
                }


            } catch (PDOException $e) {

                $message =
                    "Unable to update assignment.";

                $message_type =
                    "error";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| FILTERS
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search'] ?? ''
    );


$status_filter =
    trim(
        $_GET['status'] ?? ''
    );


$curriculum_filter =
    trim(
        $_GET['curriculum'] ?? ''
    );


$subject_filter =
    trim(
        $_GET['subject'] ?? ''
    );


/*
|--------------------------------------------------------------------------
| BUILD QUERY
|--------------------------------------------------------------------------
*/

$where = [
    "b.teacher_id = :teacher_id"
];


$params = [
    ':teacher_id' => $teacher_id
];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $where[] = "
        (
            b.student_name LIKE :search
            OR b.email LIKE :search
            OR b.phone LIKE :search
            OR b.booking_reference LIKE :search
            OR b.subjects LIKE :search
        )
    ";

    $params[':search'] =
        '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status_filter !== '') {

    $where[] =
        "b.assignment_status = :assignment_status";

    $params[':assignment_status'] =
        $status_filter;
}


/*
|--------------------------------------------------------------------------
| CURRICULUM FILTER
|--------------------------------------------------------------------------
*/

if ($curriculum_filter !== '') {

    $where[] =
        "b.curriculum = :curriculum";

    $params[':curriculum'] =
        $curriculum_filter;
}


/*
|--------------------------------------------------------------------------
| SUBJECT FILTER
|--------------------------------------------------------------------------
*/

if ($subject_filter !== '') {

    $where[] =
        "b.subjects LIKE :subject";

    $params[':subject'] =
        '%' . $subject_filter . '%';
}


/*
|--------------------------------------------------------------------------
| GET ASSIGNMENTS
|--------------------------------------------------------------------------
*/

$assignments = [];

try {

    $sql = "

        SELECT

            b.id,

            b.booking_reference,

            b.student_name,

            b.email,

            b.phone,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.lesson_date,

            b.lesson_time,

            b.lesson_status,

            b.payment_status,

            b.assignment_status,

            b.amount,

            b.paystack_reference,

            b.teacher_id,

            b.teacher_name

        FROM bookings b

        WHERE
            "
        . implode(
            " AND ",
            $where
        )
        . "

        ORDER BY

            CASE

                WHEN b.lesson_date IS NULL
                THEN 1

                ELSE 0

            END,

            b.lesson_date ASC,

            b.lesson_time ASC,

            b.id DESC
    ";


    $stmt =
        $pdo->prepare($sql);


    $stmt->execute(
        $params
    );


    $assignments =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    $message =
        "Unable to load assignments.";

    $message_type =
        "error";

    $assignments = [];
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$total_assignments =
    count($assignments);


$active_assignments = 0;

$completed_assignments = 0;

$pending_assignments = 0;


foreach (
    $assignments
    as $assignment
) {

    $status =
        strtolower(
            trim(
                $assignment[
                    'assignment_status'
                ]
                ??
                ''
            )
        );


    if (
        $status === 'active'
        ||
        $status === 'assigned'
    ) {

        $active_assignments++;

    }

    elseif (
        $status === 'completed'
    ) {

        $completed_assignments++;

    }

    else {

        $pending_assignments++;
    }
}


/*
|--------------------------------------------------------------------------
| CURRENT PAGE URL
|--------------------------------------------------------------------------
*/

$current_url =
    basename(
        $_SERVER['PHP_SELF']
    );

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
    My Assignments | NISEL ONLINE EDUCATION
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #f3f7fb;

    color: #1f2937;
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

/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 250px;

    min-height: 100vh;

    padding: 28px;
}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {

    background: white;

    border:
        1px solid #e4eaf0;

    border-radius: 16px;

    min-height: 72px;

    padding: 15px 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px
        rgba(16,24,40,.05);
}


.topbar-left h1 {

    margin: 0;

    color: #003b70;

    font-size: 23px;
}


.topbar-left p {

    margin: 5px 0 0;

    color: #718096;

    font-size: 13px;
}


.teacher-user {

    display: flex;

    align-items: center;

    gap: 10px;
}


.avatar {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #0068ad,
            #00a2ff
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: bold;
}


.teacher-user strong {

    color: #1f2937;

    font-size: 14px;
}


.teacher-user span {

    display: block;

    color: #7b8794;

    font-size: 11px;

    margin-top: 2px;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-heading {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;
}


.page-heading h2 {

    margin: 0;

    font-size: 28px;

    color: #003b70;
}


.page-heading p {

    margin: 7px 0 0;

    color: #6b7280;

    font-size: 14px;
}


.total-badge {

    background: #e9f5ff;

    color: #0067a9;

    padding: 9px 14px;

    border-radius: 30px;

    font-size: 13px;

    font-weight: bold;
}


/* =========================================================
   STATS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );

    gap: 16px;

    margin-bottom: 22px;
}


.stat-card {

    background: white;

    border:
        1px solid #e5ebf1;

    border-radius: 15px;

    padding: 20px;

    box-shadow:
        0 5px 20px
        rgba(16,24,40,.04);
}


.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.stat-icon {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef6ff;

    font-size: 20px;
}


.stat-card h3 {

    margin: 15px 0 3px;

    font-size: 28px;

    color: #003b70;
}


.stat-card p {

    margin: 0;

    color: #718096;

    font-size: 12px;
}


/* =========================================================
   FILTER CARD
========================================================= */

.filter-card {

    background: white;

    border:
        1px solid #e5ebf1;

    border-radius: 16px;

    padding: 20px;

    margin-bottom: 22px;

    box-shadow:
        0 5px 20px
        rgba(16,24,40,.04);
}


.filter-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 15px;
}


.filter-title h3 {

    margin: 0;

    color: #003b70;

    font-size: 16px;
}


.filter-grid {

    display: grid;

    grid-template-columns:
        2fr
        1fr
        1fr
        1fr
        auto;

    gap: 10px;

    align-items: end;
}


.form-group label {

    display: block;

    font-size: 11px;

    font-weight: bold;

    color: #64748b;

    margin-bottom: 6px;
}


.form-control {

    width: 100%;

    height: 42px;

    border:
        1px solid #d9e1e8;

    border-radius: 9px;

    padding: 0 12px;

    font-size: 13px;

    background: white;

    color: #263238;

    outline: none;
}


.form-control:focus {

    border-color: #0077b6;

    box-shadow:
        0 0 0 3px
        rgba(0,119,182,.08);
}


.btn {

    height: 42px;

    padding: 0 18px;

    border: none;

    border-radius: 9px;

    cursor: pointer;

    font-weight: bold;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    font-size: 13px;
}


.btn-primary {

    background: #003b70;

    color: white;
}


.btn-primary:hover {

    background: #00558f;
}


.btn-light {

    background: #f1f5f9;

    color: #475569;
}


.btn-light:hover {

    background: #e2e8f0;
}


/* =========================================================
   ASSIGNMENT CARD
========================================================= */

.assignment-list {

    display: grid;

    gap: 14px;
}


.assignment-card {

    background: white;

    border:
        1px solid #e4eaf0;

    border-radius: 16px;

    padding: 20px;

    box-shadow:
        0 5px 20px
        rgba(16,24,40,.04);

    transition:
        transform .2s,
        box-shadow .2s;
}


.assignment-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 28px
        rgba(16,24,40,.08);
}


.assignment-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    padding-bottom: 16px;

    border-bottom:
        1px solid #edf1f5;
}


.student-block {

    display: flex;

    align-items: center;

    gap: 13px;
}


.student-avatar {

    width: 50px;

    height: 50px;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #e6f4ff,
            #cfeaff
        );

    color: #0067a9;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

    font-weight: bold;
}


.student-block h3 {

    margin: 0;

    font-size: 16px;

    color: #17324d;
}


.student-block p {

    margin: 4px 0 0;

    color: #7b8794;

    font-size: 11px;
}


.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 7px 11px;

    border-radius: 30px;

    font-size: 11px;

    font-weight: bold;
}


.status-assigned {

    background: #eaf4ff;

    color: #0067a9;
}


.status-active {

    background: #e9f9ef;

    color: #177245;
}


.status-completed {

    background: #e9f9ef;

    color: #176b3a;
}


.status-cancelled {

    background: #fff0f0;

    color: #b42318;
}


.status-pending {

    background: #fff7e6;

    color: #9a6700;
}


/* =========================================================
   DETAILS
========================================================= */

.assignment-details {

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(0,1fr)
        );

    gap: 15px;

    padding: 18px 0;
}


.detail-item label {

    display: block;

    color: #8a96a3;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 5px;
}


.detail-item strong {

    color: #243b53;

    font-size: 13px;

    line-height: 1.5;
}


.subject-text {

    color: #0067a9;

    font-weight: bold;
}


/* =========================================================
   FOOTER ACTIONS
========================================================= */

.assignment-actions {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    padding-top: 15px;

    border-top:
        1px solid #edf1f5;
}


.lesson-info {

    display: flex;

    gap: 15px;

    flex-wrap: wrap;
}


.lesson-info span {

    font-size: 12px;

    color: #667788;
}


.lesson-info strong {

    color: #263b50;
}


.actions {

    display: flex;

    gap: 7px;

    align-items: center;
}


.action-btn {

    height: 36px;

    padding: 0 12px;

    border-radius: 8px;

    border:
        1px solid #dce5ed;

    background: white;

    color: #38536a;

    cursor: pointer;

    text-decoration: none;

    font-size: 11px;

    font-weight: bold;

    display: inline-flex;

    align-items: center;

    justify-content: center;

}


.action-btn:hover {

    background: #f5f9fc;
}


.action-btn.primary {

    background: #003b70;

    color: white;

    border-color: #003b70;
}


.action-btn.primary:hover {

    background: #00558f;
}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    background: white;

    border:
        1px solid #e4eaf0;

    border-radius: 16px;

    padding: 70px 25px;

    text-align: center;
}


.empty-icon {

    font-size: 45px;

    margin-bottom: 15px;
}


.empty h3 {

    margin: 0;

    color: #314b61;
}


.empty p {

    color: #7a8794;

    font-size: 13px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px) {

    .stats {

        grid-template-columns:
            repeat(2,1fr);

    }


    .filter-grid {

        grid-template-columns:
            1fr 1fr;

    }


    .filter-grid .search-field {

        grid-column:
            span 2;

    }


    .assignment-details {

        grid-template-columns:
            repeat(2,1fr);

    }

}


@media(max-width:800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 18px;

    }


    .menu {

        display: grid;

        grid-template-columns:
            repeat(2,1fr);

    }


    .topbar {

        align-items:
            flex-start;

        flex-direction:
            column;

    }

}


@media(max-width:600px) {

    .stats {

        grid-template-columns: 1fr;

    }


    .filter-grid {

        grid-template-columns: 1fr;

    }


    .filter-grid .search-field {

        grid-column: auto;

    }


    .assignment-details {

        grid-template-columns: 1fr;

    }


    .assignment-top {

        flex-direction: column;

    }


    .assignment-actions {

        flex-direction: column;

        align-items: flex-start;

    }


    .actions {

        width: 100%;

    }


    .action-btn {

        flex: 1;

    }

}

</style>

</head>


<body>


<!-- ==================================================
     SIDEBAR
================================================== -->

<div class="layout">

<aside class="sidebar">

    <div class="logo">
        <strong>NISEL</strong>
        <small>ONLINE EDUCATION</small>
    </div>

    <nav class="nav">

        <a
            href="dashboard.php"
            class="active"
        >
            🏠 Dashboard
        </a>

        <a href="schedule.php">
            📅 My Schedule
        </a>

        <a href="assignments.php">
            📝 Assignments
        </a>

        <a href="classroom.php">
            🎥 Classroom
        </a>

        <a href="profile.php">
            👤 My Profile
        </a>

        <a
            href="logout.php"
            class="logout"
        >
            🚪 Logout
        </a>

    </nav>

</aside>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <div class="topbar-left">

            <h1>
                My Assignments
            </h1>

            <p>
                Manage students and lessons assigned to you.
            </p>

        </div>


        <div class="teacher-user">


            <div class="avatar">

                <?= h(
                    strtoupper(
                        substr(
                            $teacher_name,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>

                <strong>

                    <?= h(
                        $teacher_name
                    ) ?>

                </strong>


                <span>
                    Teacher Portal
                </span>

            </div>


        </div>


    </div>


    <!-- PAGE HEADING -->

    <div class="page-heading">


        <div>

            <h2>
                Assigned Students
            </h2>


            <p>
                View your assigned students,
                subjects and lesson information.
            </p>

        </div>


        <div class="total-badge">

            <?= $total_assignments ?>

            Assignment(s)

        </div>


    </div>


    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="message"
            style="
                padding:14px;
                border-radius:10px;
                margin-bottom:18px;
                background:
                    <?= $message_type === 'success'
                        ? '#eaf8ef'
                        : '#fff0f0' ?>;
                color:
                    <?= $message_type === 'success'
                        ? '#176b3a'
                        : '#b42318' ?>;
            "
        >

            <?= h(
                $message
            ) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <h3>
                        <?= $total_assignments ?>
                    </h3>

                    <p>
                        Total Assignments
                    </p>

                </div>


                <div class="stat-icon">
                    📚
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <h3>
                        <?= $active_assignments ?>
                    </h3>

                    <p>
                        Active Assignments
                    </p>

                </div>


                <div class="stat-icon">
                    🟢
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <h3>
                        <?= $completed_assignments ?>
                    </h3>

                    <p>
                        Completed
                    </p>

                </div>


                <div class="stat-icon">
                    ✅
                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-top">

                <div>

                    <h3>
                        <?= $pending_assignments ?>
                    </h3>

                    <p>
                        Other Status
                    </p>

                </div>


                <div class="stat-icon">
                    ⏳
                </div>

            </div>

        </div>


    </section>


    <!-- =====================================================
         FILTERS
    ====================================================== -->

    <section class="filter-card">


        <div class="filter-title">

            <h3>
                🔎 Find Assignments
            </h3>


            <a
                href="assignments.php"
                class="btn btn-light"
                style="height:34px;"
            >
                Clear
            </a>

        </div>


        <form
            method="GET"
            action="assignments.php"
        >


            <div class="filter-grid">


                <div class="form-group search-field">

                    <label>
                        Search
                    </label>


                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Student name, email, booking reference or subject..."
                        value="<?= h(
                            $search
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Status
                    </label>


                    <select
                        name="status"
                        class="form-control"
                    >

                        <option value="">
                            All Statuses
                        </option>


                        <option
                            value="Assigned"
                            <?= $status_filter === 'Assigned'
                                ? 'selected'
                                : '' ?>
                        >
                            Assigned
                        </option>


                        <option
                            value="Active"
                            <?= $status_filter === 'Active'
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>


                        <option
                            value="Completed"
                            <?= $status_filter === 'Completed'
                                ? 'selected'
                                : '' ?>
                        >
                            Completed
                        </option>


                        <option
                            value="Cancelled"
                            <?= $status_filter === 'Cancelled'
                                ? 'selected'
                                : '' ?>
                        >
                            Cancelled
                        </option>


                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Curriculum
                    </label>


                    <select
                        name="curriculum"
                        class="form-control"
                    >

                        <option value="">
                            All Curricula
                        </option>


                        <option
                            value="Cambridge"
                            <?= $curriculum_filter === 'Cambridge'
                                ? 'selected'
                                : '' ?>
                        >
                            Cambridge
                        </option>


                        <option
                            value="IB"
                            <?= $curriculum_filter === 'IB'
                                ? 'selected'
                                : '' ?>
                        >
                            IB
                        </option>


                        <option
                            value="GES"
                            <?= $curriculum_filter === 'GES'
                                ? 'selected'
                                : '' ?>
                        >
                            GES
                        </option>


                        <option
                            value="SAT"
                            <?= $curriculum_filter === 'SAT'
                                ? 'selected'
                                : '' ?>
                        >
                            SAT
                        </option>


                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Subject
                    </label>


                    <input
                        type="text"
                        name="subject"
                        class="form-control"
                        placeholder="e.g. Mathematics"
                        value="<?= h(
                            $subject_filter
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        🔎 Filter

                    </button>

                </div>


            </div>


        </form>


    </section>


    <!-- =====================================================
         ASSIGNMENTS
    ====================================================== -->

    <?php if (
        count($assignments) > 0
    ): ?>


        <div class="assignment-list">


            <?php foreach (
                $assignments
                as $assignment
            ): ?>


                <?php

                $student_name =
                    $assignment[
                        'student_name'
                    ]
                    ?? 'Student';


                $first_letter =
                    strtoupper(
                        substr(
                            trim(
                                $student_name
                            ),
                            0,
                            1
                        )
                    );


                $assignment_status =
                    trim(
                        $assignment[
                            'assignment_status'
                        ]
                        ??
                        'Assigned'
                    );


                $status_class =
                    strtolower(
                        $assignment_status
                    );


                if (
                    !in_array(
                        $status_class,
                        [
                            'assigned',
                            'active',
                            'completed',
                            'cancelled',
                            'pending'
                        ],
                        true
                    )
                ) {

                    $status_class =
                        'pending';
                }


                ?>


                <article class="assignment-card">


                    <!-- TOP -->

                    <div class="assignment-top">


                        <div class="student-block">


                            <div class="student-avatar">

                                <?= h(
                                    $first_letter
                                ) ?>

                            </div>


                            <div>

                                <h3>

                                    <?= h(
                                        $student_name
                                    ) ?>

                                </h3>


                                <p>

                                    Booking:

                                    <?= h(
                                        $assignment[
                                            'booking_reference'
                                        ]
                                        ??
                                        'N/A'
                                    ) ?>

                                </p>

                            </div>


                        </div>


                        <span
                            class="
                                status-badge
                                status-<?= h(
                                    $status_class
                                )
                                ?>"
                        >

                            <?php

                            if (
                                $status_class ===
                                'completed'
                            ) {

                                echo '✓';

                            }

                            elseif (
                                $status_class ===
                                'cancelled'
                            ) {

                                echo '×';

                            }

                            elseif (
                                $status_class ===
                                'active'
                            ) {

                                echo '●';

                            }

                            else {

                                echo '•';

                            }

                            ?>

                            <?= h(
                                $assignment_status
                            ) ?>

                        </span>


                    </div>


                    <!-- DETAILS -->

                    <div class="assignment-details">


                        <div class="detail-item">

                            <label>
                                Subject(s)
                            </label>


                            <strong class="subject-text">

                                <?= h(
                                    $assignment[
                                        'subjects'
                                    ]
                                    ??
                                    'Not specified'
                                ) ?>

                            </strong>

                        </div>


                        <div class="detail-item">

                            <label>
                                Curriculum
                            </label>


                            <strong>

                                <?= h(
                                    $assignment[
                                        'curriculum'
                                    ]
                                    ??
                                    'Not specified'
                                ) ?>

                            </strong>

                        </div>


                        <div class="detail-item">

                            <label>
                                Class / Year
                            </label>


                            <strong>

                                <?= h(
                                    $assignment[
                                        'class_year'
                                    ]
                                    ??
                                    'Not specified'
                                ) ?>

                            </strong>

                        </div>


                        <div class="detail-item">

                            <label>
                                Payment
                            </label>


                            <strong>

                                <?php

                                $payment =
                                    strtolower(
                                        trim(
                                            $assignment[
                                                'payment_status'
                                            ]
                                            ??
                                            ''
                                        )
                                    );


                                if (
                                    $payment ===
                                    'paid'
                                    ||
                                    $payment ===
                                    'success'
                                ) {

                                    echo
                                    '<span style="color:#177245">
                                        ✓ Paid
                                    </span>';

                                } else {

                                    echo
                                    '<span style="color:#9a6700">
                                        '
                                        .
                                        h(
                                            ucfirst(
                                                $payment
                                                ?:
                                                'Pending'
                                            )
                                        )
                                        .
                                        '
                                    </span>';
                                }

                                ?>

                            </strong>

                        </div>


                    </div>


                    <!-- ACTIONS -->

                    <div class="assignment-actions">


                        <div class="lesson-info">


                            <span>

                                📅

                                <strong>

                                    <?php

                                    if (
                                        !empty(
                                            $assignment[
                                                'lesson_date'
                                            ]
                                        )
                                    ) {

                                        echo h(
                                            date(
                                                "d M Y",
                                                strtotime(
                                                    $assignment[
                                                        'lesson_date'
                                                    ]
                                                )
                                            )
                                        );

                                    } else {

                                        echo
                                            "Date not set";
                                    }

                                    ?>

                                </strong>

                            </span>


                            <span>

                                🕐

                                <strong>

                                    <?php

                                    if (
                                        !empty(
                                            $assignment[
                                                'lesson_time'
                                            ]
                                        )
                                    ) {

                                        echo h(
                                            date(
                                                "h:i A",
                                                strtotime(
                                                    $assignment[
                                                        'lesson_time'
                                                    ]
                                                )
                                            )
                                        );

                                    } else {

                                        echo
                                            "Time not set";
                                    }

                                    ?>

                                </strong>

                            </span>


                            <span>

                                📧

                                <strong>

                                    <?= h(
                                        $assignment[
                                            'email'
                                        ]
                                        ??
                                        ''
                                    ) ?>

                                </strong>

                            </span>


                        </div>


                        <div class="actions">


                            <!-- UPDATE STATUS -->

                            <form
                                method="POST"
                                style="display:flex;gap:6px;"
                            >


                                <input
                                    type="hidden"
                                    name="action"
                                    value="update_status"
                                >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?= (int)
                                        $assignment['id'] ?>"
                                >


                                <select
                                    name="assignment_status"
                                    style="
                                        height:36px;
                                        border:1px solid #dce5ed;
                                        border-radius:8px;
                                        padding:0 8px;
                                        font-size:11px;
                                    "
                                >

                                    <option
                                        value="Assigned"
                                        <?= $assignment_status === 'Assigned'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Assigned
                                    </option>


                                    <option
                                        value="Active"
                                        <?= $assignment_status === 'Active'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Active
                                    </option>


                                    <option
                                        value="Completed"
                                        <?= $assignment_status === 'Completed'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Completed
                                    </option>


                                    <option
                                        value="Cancelled"
                                        <?= $assignment_status === 'Cancelled'
                                            ? 'selected'
                                            : '' ?>
                                    >
                                        Cancelled
                                    </option>


                                </select>


                                <button
                                    type="submit"
                                    class="action-btn primary"
                                >

                                    Save

                                </button>


                            </form>


                            <!-- SCHEDULE -->

                            <a
                                href="schedule.php"
                                class="action-btn"
                            >

                                📅 Schedule

                            </a>


                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty">


            <div class="empty-icon">
                📚
            </div>


            <h3>
                No assignments found
            </h3>


            <p>

                You currently have no assignments
                matching your selected filters.

            </p>


            <?php if (
                $search !== ''
                ||
                $status_filter !== ''
                ||
                $curriculum_filter !== ''
                ||
                $subject_filter !== ''
            ): ?>


                <a
                    href="assignments.php"
                    class="btn btn-primary"
                >

                    Clear Filters

                </a>


            <?php endif; ?>


        </div>


    <?php endif; ?>


</main>


</body>

</html>
