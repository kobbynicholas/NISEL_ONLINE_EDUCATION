<?php

require "../admin_auth.php";
require "../config/db.php";


/* =========================================================
   HELPER
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   APPLICATION ID
========================================================= */

$application_id = 0;

if (isset($_GET['id'])) {

    $application_id = (int)$_GET['id'];

} elseif (isset($_POST['application_id'])) {

    $application_id = (int)$_POST['application_id'];

}


if ($application_id <= 0) {

    die("Invalid teacher application ID.");

}


$message = "";
$message_type = "";

$temporary_password = "";
$new_teacher_id = "";


/* =========================================================
   APPROVE APPLICATION
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'approve'
) {

    try {

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


        $status =
            strtolower(
                trim(
                    $application['application_status']
                    ?? ''
                )
            );


        if ($status === 'approved') {

            throw new Exception(
                "This teacher application has already been approved."
            );

        }


        /* =================================================
           APPLICATION INFORMATION
        ================================================= */

        $teacher_name =
            trim(
                $application['full_name']
                ?? ''
            );

        $phone =
            trim(
                $application['phone']
                ?? ''
            );

        $email =
            trim(
                $application['email']
                ?? ''
            );

        $qualification =
            trim(
                $application['qualification']
                ?? ''
            );

        $subjects =
            trim(
                $application['subjects']
                ?? ''
            );

        $curriculum =
            trim(
                $application['curricula']
                ?? ''
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


        /* =================================================
           PHOTO

           Check both fields.
        ================================================= */

        $photo = "";


        if (
            isset($application['photo_filename'])
            &&
            trim(
                $application['photo_filename']
            ) !== ''
        ) {

            $photo =
                trim(
                    $application['photo_filename']
                );

        } elseif (
            isset($application['photo'])
            &&
            trim(
                $application['photo']
            ) !== ''
        ) {

            $photo =
                trim(
                    $application['photo']
                );

        }


        if ($photo !== '') {

            $photo =
                basename($photo);

        }


        /* =================================================
           AVAILABILITY
        ================================================= */

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


        if (
            $preferred_days !== ''
            &&
            $preferred_times !== ''
        ) {

            $availability =
                $preferred_days
                . " | "
                . $preferred_times;

        } elseif ($preferred_days !== '') {

            $availability =
                $preferred_days;

        } else {

            $availability =
                $preferred_times;

        }


        /* =================================================
           CHECK EMAIL
        ================================================= */

        $checkEmail = $pdo->prepare("
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
            $checkEmail->fetch(PDO::FETCH_ASSOC);


        if ($existingTeacher) {

            throw new Exception(
                "A teacher account already exists "
                . "for this email. Teacher ID: "
                . $existingTeacher['teacher_id']
            );

        }


        /* =================================================
           GENERATE TEACHER ID
        ================================================= */

        do {

            $new_teacher_id =
                "NISEL-T-"
                .
                strtoupper(
                    bin2hex(
                        random_bytes(4)
                    )
                );


            $checkID = $pdo->prepare("
                SELECT id
                FROM teachers
                WHERE teacher_id = ?
                LIMIT 1
            ");

            $checkID->execute([
                $new_teacher_id
            ]);

            $teacherExists =
                $checkID->fetch(PDO::FETCH_ASSOC);

        } while ($teacherExists);


        /* =================================================
           PASSWORD
        ================================================= */

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
                "Unable to generate password."
            );

        }


        /* =================================================
           TRANSACTION
        ================================================= */

        $pdo->beginTransaction();


        /* =================================================
           INSERT TEACHER
        ================================================= */

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

            $new_teacher_id,

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


        /* =================================================
           VERIFY TEACHER
        ================================================= */

        $verify = $pdo->prepare("
            SELECT id, teacher_id
            FROM teachers
            WHERE teacher_id = ?
            LIMIT 1
        ");

        $verify->execute([
            $new_teacher_id
        ]);

        $createdTeacher =
            $verify->fetch(PDO::FETCH_ASSOC);


        if (!$createdTeacher) {

            throw new Exception(
                "Teacher account was not created."
            );

        }


        /* =================================================
           APPROVE APPLICATION
        ================================================= */

        $update = $pdo->prepare("
            UPDATE teacher_applications
            SET application_status = 'Approved'
            WHERE id = ?
        ");

        $update->execute([
            $application_id
        ]);


        $pdo->commit();


        $message_type =
            "success";

        $message =
            "Teacher approved successfully.";


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


/* =========================================================
   REJECT
========================================================= */

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


/* =========================================================
   RETURN TO PENDING
========================================================= */

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
            "Application returned to Pending.";

        $message_type =
            "success";


    } catch (PDOException $e) {

        $message =
            "Unable to update application: "
            .
            $e->getMessage();

        $message_type =
            "error";

    }

}


/* =========================================================
   LOAD APPLICATION
========================================================= */

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

    die("Teacher application not found.");

}


/* =========================================================
   STATUS
========================================================= */

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


/* =========================================================
   PHOTO
========================================================= */

/*
    VERY IMPORTANT

    The actual photo upload folder is:

    C:\xampp\htdocs\online\teacher\uploads\teachers\

    The browser URL is:

    /online/teacher/uploads/teachers/

    We intentionally use an absolute URL here instead of
    ../teacher/... because that eliminates the relative-path
    problem.
*/


$photoFile = "";


if (
    isset($application['photo_filename'])
    &&
    trim(
        $application['photo_filename']
    ) !== ''
) {

    $photoFile =
        trim(
            $application['photo_filename']
        );

} elseif (
    isset($application['photo'])
    &&
    trim(
        $application['photo']
    ) !== ''
) {

    $photoFile =
        trim(
            $application['photo']
        );

}


$photoFile =
    basename($photoFile);


$photoUrl = "";

$photoExists = false;


if ($photoFile !== '') {


    /*
    ---------------------------------------------------------
    ACTUAL WINDOWS FILE
    ---------------------------------------------------------
    */

    $actualPhoto =
        __DIR__
        . DIRECTORY_SEPARATOR
        . ".."
        . DIRECTORY_SEPARATOR
        . "teacher"
        . DIRECTORY_SEPARATOR
        . "uploads"
        . DIRECTORY_SEPARATOR
        . "teachers"
        . DIRECTORY_SEPARATOR
        . $photoFile;


    if (is_file($actualPhoto)) {

        $photoExists = true;


        /*
        -----------------------------------------------------
        ABSOLUTE BROWSER URL
        -----------------------------------------------------
        */

        $photoUrl =
            "/online/teacher/uploads/teachers/"
            .
            rawurlencode(
                $photoFile
            );

    }


    /*
    ---------------------------------------------------------
    FALLBACK OLD LOCATION
    ---------------------------------------------------------
    */

    if (!$photoExists) {

        $oldPhoto =
            __DIR__
            . DIRECTORY_SEPARATOR
            . ".."
            . DIRECTORY_SEPARATOR
            . "uploads"
            . DIRECTORY_SEPARATOR
            . "teachers"
            . DIRECTORY_SEPARATOR
            . "photos"
            . DIRECTORY_SEPARATOR
            . $photoFile;


        if (is_file($oldPhoto)) {

            $photoExists = true;


            $photoUrl =
                "/online/uploads/teachers/photos/"
                .
                rawurlencode(
                    $photoFile
                );

        }

    }

}


/* =========================================================
   CV
========================================================= */

$cvFile =
    trim(
        $application['cv_filename']
        ?? ''
    );


$cvUrl = "";


if ($cvFile !== '') {

    $cvFile =
        basename($cvFile);


    $possibleCV = [

        __DIR__
        . "/../teacher/uploads/teachers/cv/"
        . $cvFile,

        __DIR__
        . "/../uploads/teachers/cv/"
        . $cvFile

    ];


    $possibleCVUrls = [

        "/online/teacher/uploads/teachers/cv/"
        . rawurlencode($cvFile),

        "/online/uploads/teachers/cv/"
        . rawurlencode($cvFile)

    ];


    foreach (
        $possibleCV
        as $key => $path
    ) {

        if (is_file($path)) {

            $cvUrl =
                $possibleCVUrls[$key];

            break;

        }

    }

}


/* =========================================================
   SUBJECTS
========================================================= */

$subjects =
    preg_split(
        '/[,;]+/',
        $application['subjects'] ?? ''
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
   GENERAL
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
   TOPBAR
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
   PROFILE HEADER
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

}


.message.error {

    background: #fee2e2;

    color: #991b1b;

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

}


/* =====================================================
   INFO
===================================================== */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(2,minmax(0,1fr));

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


.photo-warning {

    margin-top: 12px;

    background: #fff3cd;

    color: #856404;

    padding: 12px;

    border-radius: 8px;

    font-size: 12px;

    line-height: 1.5;

    word-break: break-word;

}


/* =====================================================
   DOCUMENT
===================================================== */

.document {

    display: block;

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

}


/* =====================================================
   ACTIONS
===================================================== */

.action-card {

    position: sticky;

    top: 20px;

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

    margin-bottom: 10px;

}


.approve {

    background: #198754;

}


.reject {

    background: #dc3545;

}


.pending-button {

    background: #d99400;

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

    margin-top: 0;

    color: #065f46;

}


.credential {

    display: flex;

    justify-content: space-between;

    gap: 10px;

    padding: 8px 0;

    border-bottom:
        1px solid
        #d1fae5;

}


.credential-value {

    font-weight: 800;

    color: #064e3b;

    word-break: break-word;

}


.no-data {

    color: #94a3b8;

    font-size: 14px;

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

@media(max-width:1000px) {

    .content-grid {

        grid-template-columns: 1fr;

    }

    .action-card {

        position: static;

    }

}


@media(max-width:750px) {

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


            <?php if ($photoExists): ?>

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
                        ?? ''
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

            <?php if (
                $new_teacher_id !== ''
            ): ?>

                <br><br>

                <strong>
                    Teacher ID:
                </strong>

                <?= h(
                    $new_teacher_id
                ) ?>

                <br>

                <strong>
                    Temporary Password:
                </strong>

                <?= h(
                    $temporary_password
                ) ?>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <div class="content-grid">


        <!-- =================================================
             LEFT
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
                                ?? ''
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
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
                                ?? 'Not provided'
                            ) ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- STATEMENT -->

            <div class="card">

                <h3 class="card-title">

                    📝 Professional Statement

                </h3>


                <div class="statement">

                    <?= nl2br(
                        h(
                            $application[
                                'professional_statement'
                            ]
                            ?? 'No statement provided.'
                        )
                    ) ?>

                </div>

            </div>


            <!-- ZOOM -->

            <div class="card">

                <h3 class="card-title">

                    🎥 Zoom Meeting

                </h3>


                <?php if (
                    !empty(
                        $application['zoom_link']
                    )
                ): ?>

                    <a
                        href="<?= h(
                            $application['zoom_link']
                        ) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="zoom"
                    >

                        🎥 Open Zoom Meeting

                        <br><br>

                        <?= h(
                            $application['zoom_link']
                        ) ?>

                    </a>

                <?php else: ?>

                    <div class="no-data">

                        No Zoom link was provided.

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


                <?php if ($photoExists): ?>

                    <img
                        src="<?= h($photoUrl) ?>"
                        class="large-photo"
                        alt="Teacher Applicant Photo"
                    >

                <?php else: ?>

                    <div class="no-photo">
                        👤
                    </div>


                    <div class="photo-warning">

                        <strong>
                            Photo not found
                        </strong>

                        <br><br>

                        <?php if (
                            $photoFile !== ''
                        ): ?>

                            The database contains:

                            <br>

                            <strong>
                                <?= h(
                                    $photoFile
                                ) ?>
                            </strong>

                            <br><br>

                            but the image file could not
                            be found in:

                            <br>

                            <code>
                                teacher/uploads/teachers/
                            </code>

                        <?php else: ?>

                            No photo filename is stored
                            for this application.

                        <?php endif; ?>

                    </div>

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

                        📄 View / Open CV

                    </a>

                <?php else: ?>

                    <div class="no-data">

                        No CV file was found.

                    </div>

                <?php endif; ?>

            </div>


            <!-- APPLICATION DETAILS -->

            <div class="card">

                <h3 class="card-title">

                    📋 Application Details

                </h3>


                <div class="info">

                    <span class="info-label">

                        Application ID

                    </span>

                    <div class="info-value">

                        #<?= h(
                            $application_id
                        ) ?>

                    </div>

                </div>


                <br>


                <div class="info">

                    <span class="info-label">

                        Reference

                    </span>

                    <div class="info-value">

                        <?= h(
                            $application[
                                'application_reference'
                            ]
                            ?? 'N/A'
                        ) ?>

                    </div>

                </div>

            </div>


            <!-- ACTIONS -->

            <div class="card action-card">

                <h3 class="card-title">

                    ⚡ Application Actions

                </h3>


                <?php if (
                    $status !== 'approved'
                ): ?>


                    <form
                        method="POST"
                        onsubmit="
                            return confirm(
                                'Are you sure you want to approve this teacher application?'
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= h(
                                $application_id
                            ) ?>"
                        >


                        <button
                            type="submit"
                            name="action"
                            value="approve"
                            class="
                                action-button
                                approve
                            "
                        >

                            ✓ Approve Application

                        </button>

                    </form>


                    <form
                        method="POST"
                        onsubmit="
                            return confirm(
                                'Are you sure you want to reject this application?'
                            );
                        "
                    >

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= h(
                                $application_id
                            ) ?>"
                        >


                        <button
                            type="submit"
                            name="action"
                            value="reject"
                            class="
                                action-button
                                reject
                            "
                        >

                            ✕ Reject Application

                        </button>

                    </form>


                <?php endif; ?>


                <?php if (
                    $status === 'rejected'
                ): ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="application_id"
                            value="<?= h(
                                $application_id
                            ) ?>"
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
                    $new_teacher_id !== ''
                ): ?>

                    <div class="credentials">

                        <h4>

                            🎉 Teacher Account Created

                        </h4>


                        <div class="credential">

                            <span>
                                Teacher ID
                            </span>

                            <span
                                class="credential-value"
                            >

                                <?= h(
                                    $new_teacher_id
                                ) ?>

                            </span>

                        </div>


                        <div class="credential">

                            <span>
                                Temporary Password
                            </span>

                            <span
                                class="credential-value"
                            >

                                <?= h(
                                    $temporary_password
                                ) ?>

                            </span>

                        </div>

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
