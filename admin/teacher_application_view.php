<?php

require "../admin_auth.php";
require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN - TEACHER APPLICATION VIEW
| PDO VERSION
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

$application_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : (int) ($_POST['application_id'] ?? 0);


if ($application_id <= 0) {
    die("Invalid application ID.");
}


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
| CHECK IF COLUMN EXISTS
|--------------------------------------------------------------------------
*/

function columnExists($pdo, $table, $column)
{
    try {

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");

        $stmt->execute([
            $table,
            $column
        ]);

        return (int)$stmt->fetchColumn() > 0;

    } catch (PDOException $e) {

        return false;
    }
}


/*
|--------------------------------------------------------------------------
| APPROVE APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'approve'
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
                "Teacher application not found."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK IF ALREADY APPROVED
        |--------------------------------------------------------------------------
        */

        $current_status = strtolower(
            trim(
                $application['application_status'] ?? ''
            )
        );

        if ($current_status === 'approved') {

            throw new Exception(
                "This application has already been approved."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | APPLICATION INFORMATION
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
                $application['teaching_experience'] ?? ''
            );

        $bio =
            trim(
                $application['professional_statement'] ?? ''
            );

        $photo =
            trim(
                $application['photo_filename'] ?? ''
            );

        $preferred_days =
            trim(
                $application['preferred_days'] ?? ''
            );

        $preferred_times =
            trim(
                $application['preferred_times'] ?? ''
            );

        $zoom_link =
            trim(
                $application['zoom_link'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | AVAILABILITY
        |--------------------------------------------------------------------------
        */

        if (
            $preferred_days !== ''
            &&
            $preferred_times !== ''
        ) {

            $availability =
                $preferred_days
                . " | "
                . $preferred_times;

        } elseif (
            $preferred_days !== ''
        ) {

            $availability =
                $preferred_days;

        } else {

            $availability =
                $preferred_times;

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDATE EMAIL
        |--------------------------------------------------------------------------
        */

        if (
            empty($email)
            ||
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            throw new Exception(
                "The applicant does not have a valid email address."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING TEACHER EMAIL
        |--------------------------------------------------------------------------
        */

        $emailCheck = $pdo->prepare("
            SELECT
                id,
                teacher_id,
                teacher_name
            FROM teachers
            WHERE email = ?
            LIMIT 1
        ");

        $emailCheck->execute([
            $email
        ]);

        $existingTeacher =
            $emailCheck->fetch(PDO::FETCH_ASSOC);


        if ($existingTeacher) {

            throw new Exception(
                "A teacher account already exists for this email. "
                . "Teacher ID: "
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
                    bin2hex(
                        random_bytes(4)
                    )
                );

            $idCheck = $pdo->prepare("
                SELECT id
                FROM teachers
                WHERE teacher_id = ?
                LIMIT 1
            ");

            $idCheck->execute([
                $teacher_id
            ]);

            $idExists =
                $idCheck->fetch();

        } while ($idExists);


        /*
        |--------------------------------------------------------------------------
        | GENERATE TEMPORARY PASSWORD
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
                "Unable to create secure password."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK ZOOM COLUMN
        |--------------------------------------------------------------------------
        */

        $teacherHasZoom =
            columnExists(
                $pdo,
                'teachers',
                'zoom_link'
            );


        /*
        |--------------------------------------------------------------------------
        | START TRANSACTION
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();


        /*
        |--------------------------------------------------------------------------
        | INSERT TEACHER
        |--------------------------------------------------------------------------
        */

        if ($teacherHasZoom) {

            $insertTeacher = $pdo->prepare("
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
                    status,
                    zoom_link
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
                "Active",
                $zoom_link
            ]);

        } else {

            $insertTeacher = $pdo->prepare("
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

        }


        /*
        |--------------------------------------------------------------------------
        | VERIFY TEACHER WAS CREATED
        |--------------------------------------------------------------------------
        */

        $verifyTeacher = $pdo->prepare("
            SELECT id, teacher_id
            FROM teachers
            WHERE teacher_id = ?
            LIMIT 1
        ");

        $verifyTeacher->execute([
            $teacher_id
        ]);

        $createdTeacher =
            $verifyTeacher->fetch(PDO::FETCH_ASSOC);


        if (!$createdTeacher) {

            throw new Exception(
                "Teacher account could not be verified after creation."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE APPLICATION
        |--------------------------------------------------------------------------
        */

        $updateApplication = $pdo->prepare("
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

        $message =
            "Teacher application approved successfully.";

        $message_type =
            "success";

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

        $message =
            "Approval failed: "
            .
            $e->getMessage();

        $message_type =
            "error";
    }
}


/*
|--------------------------------------------------------------------------
| REJECT APPLICATION
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'reject'
) {

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
            "Teacher application has been rejected.";

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
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'pending'
) {

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
            "Application has been returned to Pending.";

        $message_type =
            "success";

    } catch (PDOException $e) {

        $message =
            "Unable to change application status: "
            .
            $e->getMessage();

        $message_type =
            "error";
    }
}


/*
|--------------------------------------------------------------------------
| LOAD APPLICATION
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

    die("
        <div style='
            font-family:Arial;
            padding:60px;
            text-align:center;
        '>
            <h2>Application Not Found</h2>
            <p>
                The requested teacher application
                does not exist.
            </p>
        </div>
    ");
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


$statusClass =
    'pending';

if ($status === 'approved') {

    $statusClass =
        'approved';

} elseif ($status === 'rejected') {

    $statusClass =
        'rejected';

}


/*
|--------------------------------------------------------------------------
| PHOTO PATH
|--------------------------------------------------------------------------
|
| Your teacher application upload is stored in:
|
| online/teacher/uploads/teachers/
|
| admin/teacher_application_view.php
| therefore uses:
|
| ../teacher/uploads/teachers/
|
|--------------------------------------------------------------------------
*/

$photoFile =
    trim(
        $application['photo_filename']
        ?? ''
    );

$photoUrl = "";

if ($photoFile !== '') {

    $photoPath =
        __DIR__
        .
        DIRECTORY_SEPARATOR
        . ".."
        . DIRECTORY_SEPARATOR
        . "teacher"
        . DIRECTORY_SEPARATOR
        . "uploads"
        . DIRECTORY_SEPARATOR
        . "teachers"
        . DIRECTORY_SEPARATOR
        . basename($photoFile);


    if (file_exists($photoPath)) {

        $photoUrl =
            "../teacher/uploads/teachers/"
            .
            rawurlencode(
                basename($photoFile)
            );

    }
}


/*
|--------------------------------------------------------------------------
| CV PATH
|--------------------------------------------------------------------------
|
| Check both common locations so the page works with
| your existing application uploads.
|
|--------------------------------------------------------------------------
*/

$cvFile =
    trim(
        $application['cv_filename']
        ?? ''
    );

$cvUrl = "";

if ($cvFile !== '') {

    $possibleCVPaths = [

        __DIR__
        . "/../teacher/uploads/teachers/cv/"
        . basename($cvFile),

        __DIR__
        . "/../teacher/uploads/teachers/"
        . basename($cvFile),

        __DIR__
        . "/../uploads/teachers/cv/"
        . basename($cvFile)

    ];


    $possibleCVUrls = [

        "../teacher/uploads/teachers/cv/"
        . rawurlencode(
            basename($cvFile)
        ),

        "../teacher/uploads/teachers/"
        . rawurlencode(
            basename($cvFile)
        ),

        "../uploads/teachers/cv/"
        . rawurlencode(
            basename($cvFile)
        )

    ];


    foreach (
        $possibleCVPaths
        as $index => $path
    ) {

        if (file_exists($path)) {

            $cvUrl =
                $possibleCVUrls[$index];

            break;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUBJECTS
|--------------------------------------------------------------------------
*/

$subjectsText =
    $application['subjects']
    ?? '';

$subjects =
    preg_split(
        '/[,;]+/',
        $subjectsText
    );


/*
|--------------------------------------------------------------------------
| ZOOM LINK
|--------------------------------------------------------------------------
*/

$applicationZoom =
    trim(
        $application['zoom_link']
        ?? ''
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
        Arial,
        Helvetica,
        sans-serif;

    background: #f3f6fa;

    color: #1e293b;

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
            #00254d
        );

    color: white;

    padding: 25px 15px;

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

    background: white;

    border-radius: 14px;

    padding: 18px 22px;

    margin-bottom: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.06);

}


.topbar h2 {

    margin: 0;

    color: #003366;

}


.admin-name {

    color: #64748b;

    font-size: 14px;

}


/* =====================================================
   BACK
===================================================== */

.back {

    display: inline-block;

    margin-bottom: 20px;

    color: #005a9c;

    text-decoration: none;

    font-weight: 700;

}


/* =====================================================
   HEADER
===================================================== */

.profile-header {

    background:
        linear-gradient(
            135deg,
            #003366,
            #08639d
        );

    color: white;

    border-radius: 16px;

    padding: 28px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    margin-bottom: 22px;

    box-shadow:
        0 10px 30px
        rgba(0,51,102,.15);

}


.profile-left {

    display: flex;

    align-items: center;

    gap: 20px;

}


.header-photo {

    width: 95px;

    height: 95px;

    object-fit: cover;

    border-radius: 50%;

    border:
        4px solid
        rgba(255,255,255,.8);

}


.header-placeholder {

    width: 95px;

    height: 95px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.15);

    font-size: 42px;

}


.profile-header h1 {

    margin: 0 0 8px;

    font-size: 27px;

}


.reference {

    font-size: 13px;

    opacity: .85;

}


/* =====================================================
   STATUS
===================================================== */

.status {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 9px 16px;

    border-radius: 30px;

    font-size: 13px;

    font-weight: 800;

    text-transform: uppercase;

}


.status.pending {

    background: #fff3cd;

    color: #856404;

}


.status.approved {

    background: #d1fae5;

    color: #065f46;

}


.status.rejected {

    background: #fee2e2;

    color: #991b1b;

}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 16px 18px;

    border-radius: 10px;

    margin-bottom: 22px;

    font-weight: 600;

}


.message.success {

    background: #d1fae5;

    color: #065f46;

    border:
        1px solid
        #a7f3d0;

}


.message.error {

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

    margin-bottom: 22px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.06);

}


.card-title {

    margin: 0 0 20px;

    color: #003366;

    font-size: 18px;

    display: flex;

    align-items: center;

    gap: 9px;

}


/* =====================================================
   INFORMATION
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


.info {

    background: #f8fafc;

    border:
        1px solid
        #e2e8f0;

    border-radius: 10px;

    padding: 15px;

}


.info.full {

    grid-column: 1 / -1;

}


.info-label {

    display: block;

    color: #64748b;

    font-size: 11px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .5px;

    margin-bottom: 7px;

}


.info-value {

    font-weight: 600;

    line-height: 1.5;

    word-break: break-word;

}


/* =====================================================
   SUBJECT TAGS
===================================================== */

.tags {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

}


.tag {

    display: inline-block;

    background: #e8f2fb;

    color: #075a94;

    padding: 7px 12px;

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
   PHOTO
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
        #dbe2ea;

}


.no-photo {

    width: 100%;

    max-width: 300px;

    height: 250px;

    margin: auto;

    border-radius: 14px;

    background: #f1f5f9;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 65px;

    color: #94a3b8;

}


/* =====================================================
   DOCUMENT
===================================================== */

.document {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px;

    background: #f8fafc;

    border:
        1px solid
        #e2e8f0;

    border-radius: 10px;

    color: #075a94;

    text-decoration: none;

    font-weight: 700;

}


.document:hover {

    background: #eef6ff;

}


/* =====================================================
   ZOOM
===================================================== */

.zoom {

    display: block;

    background: #eff6ff;

    border:
        1px solid
        #bfdbfe;

    color: #075a94;

    padding: 15px;

    border-radius: 10px;

    text-decoration: none;

    word-break: break-all;

    font-weight: 700;

}


.no-data {

    color: #94a3b8;

    font-size: 14px;

}


/* =====================================================
   ACTION CARD
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

    border-radius: 9px;

    padding: 14px;

    color: white;

    font-size: 15px;

    font-weight: 800;

    cursor: pointer;

    transition: .2s;

}


.action-button:hover {

    transform: translateY(-1px);

}


.approve {

    background: #198754;

}


.approve:hover {

    background: #157347;

}


.reject {

    background: #dc3545;

}


.reject:hover {

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

    margin-top: 18px;

    padding: 18px;

    border-radius: 12px;

    background: #ecfdf5;

    border:
        1px solid
        #a7f3d0;

}


.credentials h4 {

    margin: 0 0 15px;

    color: #065f46;

}


.credential {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding: 9px 0;

    border-bottom:
        1px solid
        #d1fae5;

}


.credential:last-child {

    border-bottom: none;

}


.credential-label {

    color: #64748b;

    font-size: 13px;

}


.credential-value {

    color: #064e3b;

    font-weight: 800;

    text-align: right;

    word-break: break-word;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align: center;

    color: #94a3b8;

    padding: 20px;

    font-size: 13px;

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


    .profile-header {

        flex-direction: column;

        align-items: flex-start;

    }


    .info-grid {

        grid-template-columns: 1fr;

    }


    .info.full {

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


    <div class="topbar">

        <h2>

            📋 Teacher Application

        </h2>


        <div class="admin-name">

            NISEL Administrator

        </div>

    </div>


    <a
        href="teacher_applications.php"
        class="back"
    >

        ← Back to Teacher Applications

    </a>


    <!-- =================================================
         PROFILE HEADER
    ================================================== -->

    <div class="profile-header">


        <div class="profile-left">


            <?php if ($photoUrl !== ''): ?>

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


                <div class="reference">

                    Application Reference:

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
                class="status <?= h($statusClass) ?>"
            >

                <?php if ($status === 'approved'): ?>

                    ✓

                <?php elseif ($status === 'rejected'): ?>

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


    <!-- =================================================
         MESSAGE
    ================================================== -->

    <?php if ($message !== ''): ?>

        <div
            class="
                message
                <?= $message_type === 'success'
                    ? 'success'
                    : 'error'
                ?>
            "
        >

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <div class="content-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================== -->

        <div>


            <!-- PERSONAL -->

            <div class="card">

                <h3 class="card-title">

                    👤 Personal Information

                </h3>


                <div class="info-grid">


                    <div class="info">

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


                    <div class="info">

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


                    <div class="info">

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


                    <div class="info">

                        <span class="info-label">

                            Phone

                        </span>

                        <div class="info-value">

                            <?= h(
                                $application[
                                    'phone'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>

                    </div>


                    <div class="info">

                        <span class="info-label">

                            Email

                        </span>

                        <div class="info-value">

                            <?= h(
                                $application[
                                    'email'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>

                    </div>


                    <div class="info">

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


                    <div class="info full">

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


            <!-- PROFESSIONAL -->

            <div class="card">

                <h3 class="card-title">

                    🎓 Professional Information

                </h3>


                <div class="info-grid">


                    <div class="info">

                        <span class="info-label">

                            Qualification

                        </span>

                        <div class="info-value">

                            <?= h(
                                $application[
                                    'qualification'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>

                    </div>


                    <div class="info">

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


                    <div class="info">

                        <span class="info-label">

                            Curriculum

                        </span>

                        <div class="info-value">

                            <?= h(
                                $application[
                                    'curricula'
                                ]
                                ??
                                'Not provided'
                            ) ?>

                        </div>

                    </div>


                    <div class="info">

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


                    <div class="info full">

                        <span class="info-label">

                            Subjects

                        </span>


                        <div class="tags">


                            <?php foreach (
                                $subjects
                                as $subject
                            ): ?>


                                <?php

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


                    <div class="info">

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


                    <div class="info">

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

                    🎥 Zoom Meeting

                </h3>


                <?php if ($applicationZoom !== ''): ?>

                    <a
                        href="<?= h(
                            $applicationZoom
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="zoom"
                    >

                        🎥 Open Zoom Meeting

                        <br>

                        <small>

                            <?= h(
                                $applicationZoom
                            ) ?>

                        </small>

                    </a>

                <?php else: ?>

                    <div class="no-data">

                        No Zoom link was provided.

                    </div>

                <?php endif; ?>

            </div>


        </div>


        <!-- =================================================
             RIGHT COLUMN
        ================================================== -->

        <div>


            <!-- PHOTO -->

            <div class="card photo-card">

                <h3 class="card-title">

                    📷 Applicant Photo

                </h3>


                <?php if ($photoUrl !== ''): ?>

                    <img
                        src="<?= h($photoUrl) ?>"
                        class="large-photo"
                        alt="Teacher Applicant Photo"
                    >

                <?php else: ?>

                    <div class="no-photo">

                        👤

                    </div>


                    <p class="no-data">

                        No applicant photo was found.

                    </p>

                <?php endif; ?>

            </div>


            <!-- CV -->

            <div class="card">

                <h3 class="card-title">

                    📄 Curriculum Vitae

                </h3>


                <?php if ($cvUrl !== ''): ?>

                    <a
                        href="<?= h($cvUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="document"
                    >

                        📄

                        <span>

                            View / Open CV

                        </span>

                    </a>

                <?php else: ?>

                    <div class="no-data">

                        No CV file was found.

                    </div>

                <?php endif; ?>

            </div>


            <!-- APPLICATION -->

            <div class="card">

                <h3 class="card-title">

                    📋 Application Details

                </h3>


                <div class="info">

                    <span class="info-label">

                        Application ID

                    </span>

                    <div class="info-value">

                        #<?= (int)$application_id ?>

                    </div>

                </div>


                <br>


                <div class="info">

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
                        $application['created_at']
                    )
                ): ?>

                    <br>

                    <div class="info">

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
                    carefully before making a decision.

                </p>


                <?php if (
                    $status !== 'approved'
                ): ?>


                    <!-- APPROVE -->

                    <form
                        method="POST"
                        class="action-form"
                        onsubmit="
                            return confirm(
                                'Are you sure you want to approve this teacher application? A teacher account and temporary password will be created.'
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= (int)$application_id ?>"
                        >


                        <button
                            type="submit"
                            name="action"
                            value="approve"
                            class="action-button approve"
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
                                'Are you sure you want to reject this teacher application?'
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= (int)$application_id ?>"
                        >


                        <button
                            type="submit"
                            name="action"
                            value="reject"
                            class="action-button reject"
                        >

                            ✕ Reject Application

                        </button>

                    </form>


                <?php endif; ?>


                <?php if (
                    $status === 'rejected'
                ): ?>


                    <form
                        method="POST"
                        class="action-form"
                    >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= (int)$application_id ?>"
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
                    isset($approved_teacher_id)
                ): ?>


                    <div class="credentials">

                        <h4>

                            🎉 Teacher Account Created

                        </h4>


                        <div class="credential">

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


                        <div class="credential">

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

                            Save these credentials before
                            leaving this page. The password
                            is stored securely as a hash.

                        </p>

                    </div>


                <?php elseif (
                    $status === 'approved'
                ): ?>


                    <div class="credentials">

                        <h4>

                            ✓ Application Approved

                        </h4>


                        <p
                            style="
                                font-size:13px;
                                color:#475569;
                                line-height:1.6;
                                margin:0;
                            "
                        >

                            This application has already
                            been converted into a teacher
                            account.

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
