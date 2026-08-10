<?php

require "../admin_auth.php";
require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| MODERN TEACHER APPLICATION VIEW
| PDO VERSION
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

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
| APPLICATION ID
|--------------------------------------------------------------------------
*/

$application_id = 0;

if (isset($_GET['id'])) {

    $application_id =
        (int) $_GET['id'];

} elseif (isset($_POST['application_id'])) {

    $application_id =
        (int) $_POST['application_id'];

}


if ($application_id <= 0) {

    die("Invalid teacher application ID.");

}


/*
|--------------------------------------------------------------------------
| APPROVE APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "approve"
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | GET APPLICATION
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("

            SELECT *
            FROM teacher_applications
            WHERE id = ?
            LIMIT 1

        ");

        $stmt->execute([
            $application_id
        ]);

        $application =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$application) {

            throw new Exception(
                "Teacher application was not found."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK STATUS
        |--------------------------------------------------------------------------
        */

        if (
            strtolower(
                trim(
                    $application['application_status']
                )
            ) === "approved"
        ) {

            throw new Exception(
                "This application has already been approved."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | APPLICATION DATA
        |--------------------------------------------------------------------------
        */

        $teacher_name =
            trim(
                $application['full_name'] ?? ''
            );

        $phone =
            trim(
                $application['phone'] ?? ''
            );

        $email =
            trim(
                $application['email'] ?? ''
            );

        $qualification =
            trim(
                $application['qualification'] ?? ''
            );

        $subjects =
            trim(
                $application['subjects'] ?? ''
            );

        $curriculum =
            trim(
                $application['curricula'] ?? ''
            );

        $experience =
            trim(
                $application['teaching_experience']
                ?? ''
            );

        $bio =
            trim(
                $application['professional_statement']
                ?? ''
            );

        $photo =
            trim(
                $application['photo_filename']
                ?? ''
            );

        $preferred_days =
            trim(
                $application['preferred_days']
                ?? ''
            );

        $preferred_times =
            trim(
                $application['preferred_times']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | AVAILABILITY
        |--------------------------------------------------------------------------
        */

        if (
            $preferred_days !== ""
            &&
            $preferred_times !== ""
        ) {

            $availability =
                $preferred_days
                . " | "
                . $preferred_times;

        } elseif (
            $preferred_days !== ""
        ) {

            $availability =
                $preferred_days;

        } else {

            $availability =
                $preferred_times;

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EMAIL
        |--------------------------------------------------------------------------
        */

        $checkEmail =
            $pdo->prepare("

                SELECT
                    id,
                    teacher_id,
                    teacher_name

                FROM teachers

                WHERE email = ?

                LIMIT 1

            ");

        $checkEmail->execute([
            $email
        ]);

        $existingTeacher =
            $checkEmail->fetch(
                PDO::FETCH_ASSOC
            );


        if ($existingTeacher) {

            throw new Exception(

                "A teacher account already exists "
                . "with this email. Teacher ID: "
                . $existingTeacher['teacher_id']

            );

        }


        /*
        |--------------------------------------------------------------------------
        | GENERATE TEACHER ID
        |--------------------------------------------------------------------------
        */

        do {

            $teacher_id =
                "NISEL-T-"
                .
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(4)
                        ),
                        0,
                        8
                    )
                );


            $checkID =
                $pdo->prepare("

                    SELECT id

                    FROM teachers

                    WHERE teacher_id = ?

                    LIMIT 1

                ");

            $checkID->execute([
                $teacher_id
            ]);

            $teacherExists =
                $checkID->fetch(
                    PDO::FETCH_ASSOC
                );

        } while ($teacherExists);


        /*
        |--------------------------------------------------------------------------
        | TEMPORARY PASSWORD
        |--------------------------------------------------------------------------
        */

        $temporary_password =
            "Nisel@"
            .
            random_int(
                1000,
                9999
            );


        $hashed_password =
            password_hash(
                $temporary_password,
                PASSWORD_DEFAULT
            );


        if (!$hashed_password) {

            throw new Exception(
                "Unable to generate secure password."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | INSERT TEACHER
        |--------------------------------------------------------------------------
        */

        $insertTeacher =
            $pdo->prepare("

                INSERT INTO teachers

                (
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
                    password,
                    status
                )

                VALUES

                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

            ");


        $insertTeacher->execute([

            $teacher_id,

            $teacher_name,

            $phone,

            $email,

            $qualification,

            $subjects,

            $curriculum,

            $experience,

            $bio,

            $availability,

            $photo,

            $hashed_password,

            "Active"

        ]);


        /*
        |--------------------------------------------------------------------------
        | VERIFY INSERT
        |--------------------------------------------------------------------------
        */

        $verifyTeacher =
            $pdo->prepare("

                SELECT
                    id,
                    teacher_id

                FROM teachers

                WHERE teacher_id = ?

                LIMIT 1

            ");

        $verifyTeacher->execute([
            $teacher_id
        ]);

        $createdTeacher =
            $verifyTeacher->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$createdTeacher) {

            throw new Exception(
                "Teacher account was not found after insertion."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE APPLICATION
        |--------------------------------------------------------------------------
        */

        $updateApplication =
            $pdo->prepare("

                UPDATE teacher_applications

                SET application_status = 'Approved'

                WHERE id = ?

            ");

        $updateApplication->execute([
            $application_id
        ]);


        /*
        |--------------------------------------------------------------------------
        | COMMIT
        |--------------------------------------------------------------------------
        */

        $pdo->commit();


        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        $message_type =
            "success";

        $message =
            "Teacher approved successfully!";

        /*
        |--------------------------------------------------------------------------
        | Store credentials for display
        |--------------------------------------------------------------------------
        */

        $approved_teacher_id =
            $teacher_id;

        $approved_password =
            $temporary_password;


    } catch (Exception $e) {


        if (
            isset($pdo)
            &&
            $pdo->inTransaction()
        ) {

            $pdo->rollBack();

        }


        $message_type =
            "error";

        $message =
            "Approval failed: "
            .
            $e->getMessage();

    }

}


/*
|--------------------------------------------------------------------------
| REJECT APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "reject"
) {

    try {

        $stmt =
            $pdo->prepare("

                UPDATE teacher_applications

                SET application_status = 'Rejected'

                WHERE id = ?

            ");

        $stmt->execute([
            $application_id
        ]);


        $message =
            "Teacher application rejected.";

        $message_type =
            "success";


    } catch (PDOException $e) {

        $message =
            "Unable to reject application: "
            .
            $e->getMessage();

        $message_type =
            "error";

    }

}


/*
|--------------------------------------------------------------------------
| RETURN TO PENDING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "pending"
) {

    try {

        $stmt =
            $pdo->prepare("

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
            "Unable to update application status: "
            .
            $e->getMessage();

        $message_type =
            "error";

    }

}


/*
|--------------------------------------------------------------------------
| GET APPLICATION
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare("

        SELECT *

        FROM teacher_applications

        WHERE id = ?

        LIMIT 1

    ");

$stmt->execute([
    $application_id
]);

$application =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$application) {

    die(
        "Teacher application not found."
    );

}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status =
    strtolower(
        trim(
            $application['application_status']
            ?? 'pending'
        )
    );


$statusClass = "pending";

if ($status === "approved") {

    $statusClass = "approved";

} elseif ($status === "rejected") {

    $statusClass = "rejected";

}


/*
|--------------------------------------------------------------------------
| PHOTO
|--------------------------------------------------------------------------
*/

$photoFile =
    trim(
        $application['photo_filename']
        ?? ''
    );


$photoUrl =
    "../teacher/uploads/teachers/"
    .
    rawurlencode(
        basename($photoFile)
    );

}


/*
|--------------------------------------------------------------------------
| CV
|--------------------------------------------------------------------------
*/

$cvFile =
    trim(
        $application['cv_filename']
        ?? ''
    );


$cvUrl = "";

if (!empty($cvFile)) {

    $cvUrl =
        "../uploads/teachers/cv/"
        .
        rawurlencode(
            basename($cvFile)
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

    Teacher Application |

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
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fb;

    color: #1f2937;

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
            #003366,
            #00264d
        );

    color: white;

    padding: 25px 16px;

    overflow-y: auto;

}


.logo {

    text-align: center;

    font-size: 20px;

    font-weight: 800;

    line-height: 1.5;

    padding-bottom: 25px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);

    margin-bottom: 25px;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 10px;

    color: white;

    text-decoration: none;

    padding: 13px 15px;

    margin-bottom: 6px;

    border-radius: 9px;

    transition: .2s;

}


.menu a:hover {

    background:
        rgba(255,255,255,.12);

}


.menu a.active {

    background: #0b6fbd;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 245px;

    padding: 30px;

    min-height: 100vh;

}


/* =====================================================
   TOP BAR
===================================================== */

.topbar {

    display: flex;

    justify-content: space-between;

    align-items: center;

    background: white;

    padding: 18px 22px;

    border-radius: 14px;

    margin-bottom: 25px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

}


.topbar h2 {

    margin: 0;

    color: #003366;

    font-size: 21px;

}


.topbar-right {

    color: #64748b;

    font-size: 14px;

}


/* =====================================================
   BREADCRUMB
===================================================== */

.breadcrumb {

    margin-bottom: 20px;

}


.breadcrumb a {

    color: #005a9c;

    text-decoration: none;

    font-weight: 600;

}


/* =====================================================
   HEADER
===================================================== */

.application-header {

    background:
        linear-gradient(
            135deg,
            #003366,
            #075a94
        );

    color: white;

    border-radius: 16px;

    padding: 28px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 25px;

    margin-bottom: 22px;

    box-shadow:
        0 10px 30px
        rgba(0,51,102,.15);

}


.header-left {

    display: flex;

    align-items: center;

    gap: 20px;

}


.header-photo {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    object-fit: cover;

    border:
        4px solid
        rgba(255,255,255,.7);

}


.header-placeholder {

    width: 90px;

    height: 90px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.15);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 38px;

}


.application-header h1 {

    margin: 0 0 6px;

    font-size: 26px;

}


.application-reference {

    opacity: .85;

    font-size: 13px;

}


/* =====================================================
   STATUS
===================================================== */

.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 9px 15px;

    border-radius: 30px;

    font-size: 13px;

    font-weight: 800;

    text-transform: uppercase;

}


.status-badge.pending {

    background: #fff3cd;

    color: #856404;

}


.status-badge.approved {

    background: #d1fae5;

    color: #065f46;

}


.status-badge.rejected {

    background: #fee2e2;

    color: #991b1b;

}


/* =====================================================
   ALERT
===================================================== */

.alert {

    padding: 16px 18px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-weight: 600;

}


.alert.success {

    background: #d1fae5;

    color: #065f46;

    border:
        1px solid
        #a7f3d0;

}


.alert.error {

    background: #fee2e2;

    color: #991b1b;

    border:
        1px solid
        #fecaca;

}


/* =====================================================
   GRID
===================================================== */

.content-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 2fr)
        minmax(280px, 1fr);

    gap: 22px;

}


.card {

    background: white;

    border-radius: 14px;

    padding: 24px;

    box-shadow:
        0 5px 20px
        rgba(15,23,42,.06);

    margin-bottom: 22px;

}


.card-title {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #003366;

    margin: 0 0 20px;

    font-size: 18px;

}


/* =====================================================
   INFORMATION GRID
===================================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap: 14px;

}


.info-item {

    background: #f8fafc;

    border:
        1px solid
        #e5e7eb;

    padding: 15px;

    border-radius: 10px;

}


.info-item.full {

    grid-column: 1 / -1;

}


.info-label {

    display: block;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: #64748b;

    margin-bottom: 7px;

}


.info-value {

    color: #1e293b;

    font-weight: 600;

    line-height: 1.5;

}


/* =====================================================
   TAGS
===================================================== */

.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

}


.tag {

    background: #e8f2fb;

    color: #075a94;

    padding: 7px 11px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: 700;

}


/* =====================================================
   STATEMENT
===================================================== */

.statement {

    background: #f8fafc;

    border-left:
        4px solid
        #0b6fbd;

    padding: 18px;

    border-radius: 8px;

    line-height: 1.8;

    color: #475569;

}


/* =====================================================
   PROFILE PHOTO
===================================================== */

.photo-card {

    text-align: center;

}


.large-photo {

    width: 100%;

    max-width: 300px;

    height: 330px;

    object-fit: cover;

    border-radius: 14px;

    border:
        1px solid
        #e2e8f0;

}


.no-photo {

    width: 100%;

    max-width: 300px;

    height: 250px;

    margin: auto;

    background: #f1f5f9;

    border-radius: 14px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 65px;

    color: #94a3b8;

}


/* =====================================================
   DOCUMENT
===================================================== */

.document-link {

    display: flex;

    align-items: center;

    gap: 12px;

    background: #f8fafc;

    border:
        1px solid
        #e2e8f0;

    padding: 15px;

    border-radius: 10px;

    color: #005a9c;

    text-decoration: none;

    font-weight: 700;

}


.document-link:hover {

    background: #eef6ff;

}


/* =====================================================
   ZOOM
===================================================== */

.zoom-link {

    display: block;

    padding: 14px;

    background: #eff6ff;

    border:
        1px solid
        #bfdbfe;

    border-radius: 10px;

    color: #075a94;

    text-decoration: none;

    font-weight: 700;

    word-break: break-all;

}


/* =====================================================
   ACTIONS
===================================================== */

.action-card {

    position: sticky;

    top: 20px;

}


.action-description {

    color: #64748b;

    font-size: 14px;

    line-height: 1.6;

    margin-bottom: 20px;

}


.action-form {

    margin-bottom: 10px;

}


.action-button {

    width: 100%;

    border: none;

    padding: 14px 18px;

    border-radius: 9px;

    color: white;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;

    transition: .2s;

}


.action-button:hover {

    transform: translateY(-1px);

}


.approve-button {

    background: #198754;

}


.approve-button:hover {

    background: #157347;

}


.reject-button {

    background: #dc3545;

}


.reject-button:hover {

    background: #bb2d3b;

}


.pending-button {

    background: #d99400;

}


.pending-button:hover {

    background: #b77900;

}


/* =====================================================
   CREDENTIALS
===================================================== */

.credentials {

    background:
        linear-gradient(
            135deg,
            #ecfdf5,
            #f0fdf4
        );

    border:
        1px solid
        #a7f3d0;

    border-radius: 12px;

    padding: 18px;

    margin-top: 18px;

}


.credentials h4 {

    color: #065f46;

    margin: 0 0 15px;

}


.credential-row {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 9px 0;

    border-bottom:
        1px solid
        #d1fae5;

}


.credential-row:last-child {

    border-bottom: none;

}


.credential-label {

    color: #64748b;

    font-size: 13px;

}


.credential-value {

    font-weight: 800;

    color: #064e3b;

    text-align: right;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    color: #94a3b8;

    font-size: 13px;

    padding: 20px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 1000px) {

    .content-grid {

        grid-template-columns: 1fr;

    }


    .action-card {

        position: static;

    }

}


@media(max-width: 750px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .application-header {

        flex-direction: column;

        align-items: flex-start;

    }


    .info-grid {

        grid-template-columns: 1fr;

    }


    .info-item.full {

        grid-column: auto;

    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="students.php">

            👨‍🎓 Students

        </a>


        <a href="teachers.php">

            👨‍🏫 Teachers

        </a>


        <a
            href="teacher_applications.php"
            class="active"
        >

            📋 Applications

        </a>


        <a href="bookings.php">

            📅 Bookings

        </a>


        <a href="payments.php">

            💳 Payments

        </a>


        <a href="reports.php">

            📊 Reports

        </a>


        <a href="settings.php">

            ⚙️ Settings

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>


    </div>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">


        <h2>

            📋 Teacher Application

        </h2>


        <div class="topbar-right">

            NISEL Administrator

        </div>


    </div>


    <!-- BREADCRUMB -->

    <div class="breadcrumb">

        <a href="teacher_applications.php">

            ← Back to Teacher Applications

        </a>

    </div>


    <!-- =================================================
         APPLICATION HEADER
    ================================================== -->

    <div class="application-header">


        <div class="header-left">


            <?php if (
                !empty($photoUrl)
            ): ?>


                <img

                    src="<?= h($photoUrl) ?>"

                    class="header-photo"

                    alt="Teacher Photo"

                >


            <?php else: ?>


                <div class="header-placeholder">

                    👤

                </div>


            <?php endif; ?>


            <div>


                <h1>

                    <?= h(
                        $application['full_name']
                    ) ?>

                </h1>


                <div
                    class="application-reference"
                >

                    Application:

                    <?= h(
                        $application[
                            'application_reference'
                        ]
                        ??
                        'N/A'
                    ) ?>


                </div>


            </div>


        </div>


        <div>


            <span
                class="status-badge
                <?= h($statusClass) ?>"
            >

                <?php if (
                    $status === "approved"
                ): ?>

                    ✓

                <?php elseif (
                    $status === "rejected"
                ): ?>

                    ✕

                <?php else: ?>

                    ●

                <?php endif; ?>


                <?= h(
                    ucfirst($status)
                ) ?>


            </span>


        </div>


    </div>


    <!-- ALERT -->

    <?php if (
        !empty($message)
    ): ?>


        <div
            class="alert
            <?= $message_type === 'success'
                ? 'success'
                : 'error'
            ?>"
        >

            <?= h($message) ?>


        </div>


    <?php endif; ?>


    <!-- =================================================
         CONTENT
    ================================================== -->

    <div class="content-grid">


        <!-- =================================================
             LEFT
        ================================================== -->

        <div>


            <!-- PERSONAL INFORMATION -->

            <div class="card">


                <h3 class="card-title">

                    👤 Personal Information

                </h3>


                <div class="info-grid">


                    <div class="info-item">


                        <span class="info-label">

                            Full Name

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'full_name'
                                ]
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Gender

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'gender'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Date of Birth

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'dob'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Phone

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'phone'
                                ]
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Email

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'email'
                                ]
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Location

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'location'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item full">


                        <span class="info-label">

                            Institution

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'institution'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                </div>


            </div>


            <!-- PROFESSIONAL INFORMATION -->

            <div class="card">


                <h3 class="card-title">

                    🎓 Professional Information

                </h3>


                <div class="info-grid">


                    <div class="info-item">


                        <span class="info-label">

                            Qualification

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'qualification'
                                ]
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Teaching Experience

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'teaching_experience'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Curriculum

                        </span>


                        <div class="info-value">

                            <span class="tag">

                                <?= h(
                                    $application[
                                        'curricula'
                                    ]
                                ) ?>

                            </span>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Classes Taught

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'classes_taught'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item full">


                        <span class="info-label">

                            Subjects

                        </span>


                        <div class="tags">


                            <?php

                            $subjectText =
                                $application[
                                    'subjects'
                                ]
                                ??
                                '';

                            $subjectList =
                                preg_split(
                                    '/[,;]+/',
                                    $subjectText
                                );


                            foreach (
                                $subjectList
                                as $subject
                            ):

                                $subject =
                                    trim($subject);

                                if (
                                    $subject === ''
                                ) {
                                    continue;
                                }

                            ?>

                                <span class="tag">

                                    <?= h(
                                        $subject
                                    ) ?>

                                </span>

                            <?php endforeach; ?>


                        </div>


                    </div>


                </div>


            </div>


            <!-- AVAILABILITY -->

            <div class="card">


                <h3 class="card-title">

                    🕐 Teaching Availability

                </h3>


                <div class="info-grid">


                    <div class="info-item">


                        <span class="info-label">

                            Preferred Days

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'preferred_days'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                    <div class="info-item">


                        <span class="info-label">

                            Preferred Times

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'preferred_times'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>


                    </div>


                </div>


            </div>


            <!-- PROFESSIONAL STATEMENT -->

            <div class="card">


                <h3 class="card-title">

                    📝 Professional Statement

                </h3>


                <div class="statement">

                    <?php if (
                        !empty(
                            $application[
                                'professional_statement'
                            ]
                        )
                    ): ?>


                        <?= nl2br(
                            h(
                                $application[
                                    'professional_statement'
                                ]
                            )
                        ) ?>


                    <?php else: ?>


                        No professional statement
                        was provided.


                    <?php endif; ?>


                </div>


            </div>


            <!-- ZOOM -->

            <div class="card">


                <h3 class="card-title">

                    🎥 Zoom Meeting Link

                </h3>


                <?php if (
                    !empty(
                        $application['zoom_link']
                    )
                ): ?>


                    <a

                        href="<?= h(
                            $application[
                                'zoom_link'
                            ]
                        ) ?>"

                        target="_blank"

                        rel="noopener noreferrer"

                        class="zoom-link"

                    >

                        🎥 Open Zoom Meeting

                        <br>

                        <small>

                            <?= h(
                                $application[
                                    'zoom_link'
                                ]
                            ) ?>

                        </small>

                    </a>


                <?php else: ?>


                    <div class="info-item">

                        No Zoom meeting link
                        was provided.

                    </div>


                <?php endif; ?>


            </div>


        </div>


        <!-- =================================================
             RIGHT
        ================================================== -->

        <div>


            <!-- PHOTO -->

            <div class="card photo-card">


                <h3 class="card-title">

                    📷 Applicant Photo

                </h3>


                <?php if (
                    !empty($photoUrl)
                ): ?>


                    <img

                        src="<?= h(
                            $photoUrl
                        ) ?>"

                        class="large-photo"

                        alt="Applicant Photo"

                    >


                <?php else: ?>


                    <div class="no-photo">

                        👤

                    </div>


                    <p>

                        No photo uploaded.

                    </p>


                <?php endif; ?>


            </div>


            <!-- CV -->

            <div class="card">


                <h3 class="card-title">

                    📄 Curriculum Vitae

                </h3>


                <?php if (
                    !empty($cvUrl)
                ): ?>


                    <a

                        href="<?= h(
                            $cvUrl
                        ) ?>"

                        target="_blank"

                        class="document-link"

                    >

                        📄

                        <span>

                            View / Open CV

                        </span>

                    </a>


                <?php else: ?>


                    <div class="info-item">

                        No CV uploaded.

                    </div>


                <?php endif; ?>


            </div>


            <!-- APPLICATION DETAILS -->

            <div class="card">


                <h3 class="card-title">

                    📋 Application Details

                </h3>


                <div class="info-item">


                    <span class="info-label">

                        Application ID

                    </span>


                    <div class="info-value">

                        #<?= (int)
                            $application_id ?>

                    </div>


                </div>


                <br>


                <div class="info-item">


                    <span class="info-label">

                        Application Reference

                    </span>


                    <div class="info-value">

                        <?= h(
                            $application[
                                'application_reference'
                            ]
                            ??
                            'N/A'
                        ) ?>

                    </div>


                </div>


                <?php if (
                    !empty(
                        $application[
                            'created_at'
                        ]
                    )
                ): ?>


                    <br>


                    <div class="info-item">


                        <span class="info-label">

                            Submitted

                        </span>


                        <div class="info-value">

                            <?= h(
                                $application[
                                    'created_at'
                                ]
                            ) ?>

                        </div>


                    </div>


                <?php endif; ?>


            </div>


            <!-- ACTIONS -->

            <div class="card action-card">


                <h3 class="card-title">

                    ⚡ Application Actions

                </h3>


                <p class="action-description">

                    Review the applicant's information
                    before making a decision.

                </p>


                <?php if (
                    $status !== "approved"
                ): ?>


                    <!-- APPROVE -->

                    <form
                        method="POST"
                        class="action-form"
                        onsubmit="
                            return confirm(
                                'Approve this teacher application? A teacher account and temporary password will be created.'
                            );
                        "
                    >


                        <input

                            type="hidden"

                            name="application_id"

                            value="<?= (int)
                                $application_id ?>"

                        >


                        <button

                            type="submit"

                            name="action"

                            value="approve"

                            class="
                                action-button
                                approve-button
                            "

                        >

                            ✓ Approve Application

                        </button>


                    </form>


                    <!-- REJECT -->

                    <form
                        method="POST"
                        class="action-form"
                        onsubmit="
                            return confirm(
                                'Are you sure you want to reject this application?'
                            );
                        "
                    >


                        <input

                            type="hidden"

                            name="application_id"

                            value="<?= (int)
                                $application_id ?>"

                        >


                        <button

                            type="submit"

                            name="action"

                            value="reject"

                            class="
                                action-button
                                reject-button
                            "

                        >

                            ✕ Reject Application

                        </button>


                    </form>


                <?php endif; ?>


                <?php if (
                    $status === "rejected"
                ): ?>


                    <form
                        method="POST"
                        class="action-form"
                    >


                        <input

                            type="hidden"

                            name="application_id"

                            value="<?= (int)
                                $application_id ?>"

                        >


                        <button

                            type="submit"

                            name="action"

                            value="pending"

                            class="
                                action-button
                                pending-button
                            "

                        >

                            ↻ Return to Pending

                        </button>


                    </form>


                <?php endif; ?>


                <?php if (
                    isset(
                        $approved_teacher_id
                    )
                ): ?>


                    <div class="credentials">


                        <h4>

                            🎉 Teacher Account Created

                        </h4>


                        <div
                            class="credential-row"
                        >

                            <span
                                class="credential-label"
                            >

                                Teacher ID

                            </span>


                            <span
                                class="credential-value"
                            >

                                <?= h(
                                    $approved_teacher_id
                                ) ?>

                            </span>

                        </div>


                        <div
                            class="credential-row"
                        >

                            <span
                                class="credential-label"
                            >

                                Temporary Password

                            </span>


                            <span
                                class="credential-value"
                            >

                                <?= h(
                                    $approved_password
                                ) ?>

                            </span>

                        </div>


                        <p
                            style="
                                font-size:12px;
                                color:#64748b;
                                line-height:1.5;
                                margin-bottom:0;
                            "
                        >

                            Save these credentials
                            before leaving this page.
                            The password is securely
                            stored as a hash in the
                            database.

                        </p>


                    </div>


                <?php endif; ?>


                <?php if (
                    $status === "approved"
                ): ?>


                    <div
                        class="credentials"
                        style="
                            background:#eff6ff;
                            border-color:#bfdbfe;
                        "
                    >

                        <h4
                            style="
                                color:#075a94;
                            "
                        >

                            ✓ Application Approved

                        </h4>


                        <p
                            style="
                                color:#475569;
                                line-height:1.6;
                                font-size:13px;
                            "
                        >

                            This applicant has already
                            been converted into an active
                            teacher account.

                        </p>


                    </div>


                <?php endif; ?>


            </div>


        </div>


    </div>


    <div class="footer">

        © <?= date('Y') ?>

        NISEL ONLINE EDUCATION

        • Teacher Application Management

    </div>


</div>


</body>

</html>
