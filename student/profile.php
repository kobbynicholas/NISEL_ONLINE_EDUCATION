<?php

session_start();

require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT PROFILE
| PDO VERSION
|--------------------------------------------------------------------------
*/

/* =========================================================
   INITIALIZE MESSAGES
========================================================= */

$success = "";
$error   = "";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true ||
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   GET LOGGED-IN STUDENT
========================================================= */

$student_id = (int) $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name'] ?? "Student";

$student_email =
    $_SESSION['student_email'] ?? "";


/* =========================================================
   HELPER FUNCTION
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
   DETECT AVAILABLE STUDENT COLUMNS
========================================================= */

try {

    $columnsStmt = $pdo->query(
        "DESCRIBE students"
    );

    $columns = [];

    while ($column = $columnsStmt->fetch(PDO::FETCH_ASSOC)) {

        $columns[] = $column['Field'];

    }

} catch (PDOException $e) {

    die(
        "Unable to inspect the student database table."
    );
}


/* =========================================================
   CHECK OPTIONAL COLUMNS
========================================================= */

$hasPhotoColumn =
    in_array('photo', $columns, true);

$hasPasswordColumn =
    in_array('password', $columns, true);

$hasPhoneColumn =
    in_array('phone', $columns, true);

$hasDobColumn =
    in_array('dob', $columns, true);


/* =========================================================
   GET CURRENT STUDENT
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM students
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $student_id
    ]);

    $student =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$student) {

        session_destroy();

        header(
            "Location: login.php"
        );

        exit;
    }

} catch (PDOException $e) {

    die(
        "Unable to load student profile."
    );
}


/* =========================================================
   PROCESS PROFILE UPDATE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action =
        $_POST['action'] ?? '';


    /* =====================================================
       UPDATE PROFILE
    ===================================================== */

    if ($action === 'update_profile') {

        $new_name =
            trim(
                $_POST['student_name'] ?? ''
            );

        $new_phone =
            trim(
                $_POST['phone'] ?? ''
            );

        $new_dob =
            trim(
                $_POST['dob'] ?? ''
            );


        /* -------------------------------------------------
           VALIDATION
        ------------------------------------------------- */

        if ($new_name === '') {

            $error =
                "Student name cannot be empty.";

        } elseif (
            strlen($new_name) < 2
        ) {

            $error =
                "Please enter a valid student name.";

        } else {

            try {

                /*
                ------------------------------------------------
                BUILD UPDATE QUERY
                ------------------------------------------------
                */

                $updateFields = [];

                $updateValues = [];


                /*
                STUDENT NAME
                */

                if (
                    in_array(
                        'student_name',
                        $columns,
                        true
                    )
                ) {

                    $updateFields[] =
                        "student_name = ?";

                    $updateValues[] =
                        $new_name;
                }


                /*
                PHONE
                */

                if (
                    $hasPhoneColumn
                ) {

                    $updateFields[] =
                        "phone = ?";

                    $updateValues[] =
                        $new_phone;
                }


                /*
                DATE OF BIRTH
                */

                if (
                    $hasDobColumn
                ) {

                    $updateFields[] =
                        "dob = ?";

                    $updateValues[] =
                        ($new_dob !== '')
                            ? $new_dob
                            : null;
                }


                /*
                MAKE SURE THERE IS SOMETHING TO UPDATE
                */

                if (
                    empty($updateFields)
                ) {

                    $error =
                        "No profile fields are available for updating.";

                } else {

                    $updateValues[] =
                        $student_id;


                    $sql = "
                        UPDATE students
                        SET "
                        .
                        implode(
                            ", ",
                            $updateFields
                        )
                        .
                        "
                        WHERE id = ?
                        LIMIT 1
                    ";


                    $update =
                        $pdo->prepare($sql);


                    $update->execute(
                        $updateValues
                    );


                    /*
                    ------------------------------------------------
                    UPDATE SESSION
                    ------------------------------------------------
                    */

                    $_SESSION[
                        'student_name'
                    ] = $new_name;


                    /*
                    ------------------------------------------------
                    SUCCESS MESSAGE
                    ------------------------------------------------
                    */

                    $success =
                        "Your profile has been updated successfully.";


                    /*
                    ------------------------------------------------
                    RELOAD STUDENT DATA
                    ------------------------------------------------
                    */

                    $stmt =
                        $pdo->prepare("
                            SELECT *
                            FROM students
                            WHERE id = ?
                            LIMIT 1
                        ");

                    $stmt->execute([
                        $student_id
                    ]);

                    $student =
                        $stmt->fetch(
                            PDO::FETCH_ASSOC
                        );
                }

            } catch (PDOException $e) {

                $error =
                    "Unable to update your profile. "
                    .
                    "Please try again.";
            }
        }
    }


    /* =====================================================
       CHANGE PASSWORD
    ===================================================== */

    elseif (
        $action === 'change_password'
    ) {

        if (!$hasPasswordColumn) {

            $error =
                "Password changes are not available for this student table.";

        } else {

            $current_password =
                $_POST['current_password']
                ?? '';

            $new_password =
                $_POST['new_password']
                ?? '';

            $confirm_password =
                $_POST['confirm_password']
                ?? '';


            if (
                $current_password === ''
                ||
                $new_password === ''
                ||
                $confirm_password === ''
            ) {

                $error =
                    "Please complete all password fields.";

            } elseif (
                strlen($new_password) < 8
            ) {

                $error =
                    "Your new password must contain at least 8 characters.";

            } elseif (
                $new_password !==
                $confirm_password
            ) {

                $error =
                    "The new passwords do not match.";

            } else {

                try {

                    /*
                    --------------------------------------------
                    GET CURRENT PASSWORD
                    --------------------------------------------
                    */

                    $passwordStmt =
                        $pdo->prepare("
                            SELECT password
                            FROM students
                            WHERE id = ?
                            LIMIT 1
                        ");

                    $passwordStmt->execute([
                        $student_id
                    ]);

                    $passwordData =
                        $passwordStmt->fetch(
                            PDO::FETCH_ASSOC
                        );


                    if (!$passwordData) {

                        $error =
                            "Student account could not be found.";

                    } elseif (
                        !password_verify(
                            $current_password,
                            $passwordData['password']
                        )
                    ) {

                        $error =
                            "Your current password is incorrect.";

                    } else {

                        /*
                        ----------------------------------------
                        HASH NEW PASSWORD
                        ----------------------------------------
                        */

                        $hashedPassword =
                            password_hash(
                                $new_password,
                                PASSWORD_DEFAULT
                            );


                        /*
                        ----------------------------------------
                        UPDATE PASSWORD
                        ----------------------------------------
                        */

                        $updatePassword =
                            $pdo->prepare("
                                UPDATE students
                                SET password = ?
                                WHERE id = ?
                                LIMIT 1
                            ");

                        $updatePassword->execute([
                            $hashedPassword,
                            $student_id
                        ]);


                        $success =
                            "Your password has been changed successfully.";
                    }

                } catch (PDOException $e) {

                    $error =
                        "Unable to change your password. "
                        .
                        "Please try again.";
                }
            }
        }
    }


    /* =====================================================
       PROFILE PHOTO
    ===================================================== */

    elseif (
        $action === 'update_photo'
    ) {

        if (!$hasPhotoColumn) {

            $error =
                "Photo upload is not available because the students table does not contain a photo column.";

        } elseif (
            !isset($_FILES['photo'])
            ||
            $_FILES['photo']['error']
            === UPLOAD_ERR_NO_FILE
        ) {

            $error =
                "Please select a profile photo.";

        } elseif (
            $_FILES['photo']['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                "There was an error uploading the photo.";

        } else {

            try {

                /*
                --------------------------------------------
                FILE SIZE
                --------------------------------------------
                */

                $maxSize =
                    5 * 1024 * 1024;


                if (
                    $_FILES['photo']['size']
                    > $maxSize
                ) {

                    throw new Exception(
                        "Photo must not exceed 5MB."
                    );
                }


                /*
                --------------------------------------------
                CHECK MIME TYPE
                --------------------------------------------
                */

                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                $fileType =
                    mime_content_type(
                        $_FILES['photo']['tmp_name']
                    );


                if (
                    !in_array(
                        $fileType,
                        $allowedTypes,
                        true
                    )
                ) {

                    throw new Exception(
                        "Only JPG, PNG and WEBP images are allowed."
                    );
                }


                /*
                --------------------------------------------
                CREATE DIRECTORY
                --------------------------------------------
                */

                $uploadDirectory =
                    __DIR__
                    . "/uploads/students/";


                if (
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );
                }


                /*
                --------------------------------------------
                FILE EXTENSION
                --------------------------------------------
                */

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES['photo']['name'],
                            PATHINFO_EXTENSION
                        )
                    );


                /*
                --------------------------------------------
                UNIQUE FILE NAME
                --------------------------------------------
                */

                $newPhotoName =
                    "student_"
                    .
                    $student_id
                    .
                    "_"
                    .
                    time()
                    .
                    "."
                    .
                    $extension;


                $photoPath =
                    $uploadDirectory
                    .
                    $newPhotoName;


                /*
                --------------------------------------------
                MOVE FILE
                --------------------------------------------
                */

                if (
                    !move_uploaded_file(
                        $_FILES['photo']['tmp_name'],
                        $photoPath
                    )
                ) {

                    throw new Exception(
                        "Unable to save the uploaded photo."
                    );
                }


                /*
                --------------------------------------------
                DELETE OLD PHOTO
                --------------------------------------------
                */

                $oldPhoto =
                    $student['photo']
                    ?? '';


                if (
                    !empty($oldPhoto)
                ) {

                    $oldPhotoPath =
                        $uploadDirectory
                        .
                        basename(
                            $oldPhoto
                        );


                    if (
                        is_file(
                            $oldPhotoPath
                        )
                    ) {

                        @unlink(
                            $oldPhotoPath
                        );
                    }
                }


                /*
                --------------------------------------------
                SAVE PHOTO NAME
                --------------------------------------------
                */

                $photoUpdate =
                    $pdo->prepare("
                        UPDATE students
                        SET photo = ?
                        WHERE id = ?
                        LIMIT 1
                    ");

                $photoUpdate->execute([
                    $newPhotoName,
                    $student_id
                ]);


                /*
                --------------------------------------------
                RELOAD STUDENT
                --------------------------------------------
                */

                $stmt =
                    $pdo->prepare("
                        SELECT *
                        FROM students
                        WHERE id = ?
                        LIMIT 1
                    ");

                $stmt->execute([
                    $student_id
                ]);

                $student =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                $success =
                    "Your profile photo has been updated successfully.";

            } catch (Exception $e) {

                $error =
                    $e->getMessage();
            }
        }
    }
}


/* =========================================================
   GET DISPLAY VALUES
========================================================= */

$displayName =
    $student['student_name']
    ??
    $student_name
    ??
    'Student';


$displayEmail =
    $student['email']
    ??
    $student_email
    ??
    '';


$displayPhone =
    $student['phone']
    ??
    '';


$displayDob =
    $student['dob']
    ??
    '';


$displayCurriculum =
    $student['curriculum']
    ??
    '';


$displayClass =
    $student['class_year']
    ??
    $student['class']
    ??
    '';


$displayStudentId =
    $student['student_id']
    ??
    $student['id']
    ??
    $student_id;


$displaySubjects =
    $student['subjects']
    ??
    '';


/* =========================================================
   PHOTO
========================================================= */

$photoUrl = "";

if (
    $hasPhotoColumn
    &&
    !empty(
        $student['photo']
        ?? ''
    )
) {

    $photoFile =
        basename(
            $student['photo']
        );

    $photoPath =
        __DIR__
        .
        "/uploads/students/"
        .
        $photoFile;


    if (
        is_file(
            $photoPath
        )
    ) {

        $photoUrl =
            "uploads/students/"
            .
            rawurlencode(
                $photoFile
            );
    }
}


/* =========================================================
   INITIAL
========================================================= */

$initial =
    strtoupper(
        substr(
            trim(
                $displayName
            ),
            0,
            1
        )
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
    My Profile | NISEL ONLINE EDUCATION
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================================================
   BODY
========================================================= */

body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #eef3f8;

    color:
        #243447;

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 235px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70,
            #063d70
        );

    color: white;

    padding:
        25px 14px;

    z-index: 1000;

}


.logo {

    text-align: center;

    padding:
        0 10px 24px;

    margin-bottom: 18px;

    border-bottom:
        1px solid
        rgba(255,255,255,.15);

}


.logo-title {

    font-size: 20px;

    font-weight: 800;

    letter-spacing:
        .5px;

}


.logo-subtitle {

    font-size: 10px;

    letter-spacing:
        2px;

    opacity: .65;

    margin-top: 4px;

}


.menu {

    display:
        flex;

    flex-direction:
        column;

    gap: 7px;

}


.menu a {

    display:
        flex;

    align-items:
        center;

    gap: 11px;

    padding:
        13px 14px;

    border-radius:
        9px;

    color:
        white;

    text-decoration:
        none;

    font-size:
        13px;

    transition:
        .2s ease;

}


.menu a:hover {

    background:
        rgba(255,255,255,.12);

    transform:
        translateX(2px);

}


.menu a.active {

    background:
        rgba(255,255,255,.16);

    box-shadow:
        inset 3px 0 0 #39bdf8;

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left:
        235px;

    padding:
        28px;

    min-height:
        100vh;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    background:
        white;

    border-radius:
        16px;

    padding:
        24px 28px;

    margin-bottom:
        22px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.05);

}


.page-header h1 {

    color:
        #003b70;

    font-size:
        28px;

    margin-bottom:
        7px;

}


.page-header p {

    color:
        #718096;

    font-size:
        14px;

}


/* =========================================================
   ALERTS
========================================================= */

.alert {

    padding:
        14px 17px;

    border-radius:
        10px;

    margin-bottom:
        18px;

    font-size:
        14px;

    line-height:
        1.5;

}


.alert-success {

    background:
        #e8f8ef;

    color:
        #18794e;

    border:
        1px solid
        #bce8ce;

}


.alert-error {

    background:
        #fff0f0;

    color:
        #b42318;

    border:
        1px solid
        #f2b8b5;

}


/* =========================================================
   PROFILE HERO
========================================================= */

.profile-hero {

    background:
        linear-gradient(
            135deg,
            #00457e,
            #0876b9
        );

    border-radius:
        17px;

    padding:
        28px;

    color:
        white;

    display:
        flex;

    align-items:
        center;

    gap:
        24px;

    margin-bottom:
        22px;

    box-shadow:
        0 12px 28px
        rgba(0,61,112,.18);

}


.avatar {

    width:
        105px;

    height:
        105px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.12);

    border:
        4px solid
        rgba(255,255,255,.55);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    overflow:
        hidden;

    flex-shrink:
        0;

    font-size:
        43px;

    font-weight:
        700;

}


.avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.profile-hero h2 {

    font-size:
        25px;

    margin-bottom:
        8px;

}


.profile-email {

    opacity:
        .9;

    font-size:
        13px;

    margin-bottom:
        8px;

}


.student-id {

    display:
        inline-block;

    padding:
        6px 10px;

    border-radius:
        20px;

    background:
        rgba(255,255,255,.13);

    font-size:
        11px;

}


/* =========================================================
   GRID
========================================================= */

.content-grid {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.45fr)
        minmax(300px, .75fr);

    gap:
        22px;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:
        white;

    border-radius:
        15px;

    padding:
        23px;

    margin-bottom:
        22px;

    box-shadow:
        0 7px 22px
        rgba(0,0,0,.05);

}


.card-title {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    color:
        #003b70;

    font-size:
        18px;

    font-weight:
        700;

    padding-bottom:
        15px;

    margin-bottom:
        18px;

    border-bottom:
        1px solid
        #edf1f5;

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap:
        17px;

}


.form-group {

    display:
        flex;

    flex-direction:
        column;

    gap:
        7px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


.form-group label {

    font-size:
        12px;

    font-weight:
        700;

    color:
        #4a5568;

}


.form-group input {

    width:
        100%;

    padding:
        12px 13px;

    border:
        1px solid
        #d7dee8;

    border-radius:
        9px;

    outline:
        none;

    font-size:
        14px;

    color:
        #263238;

    background:
        #fff;

    transition:
        .2s ease;

}


.form-group input:focus {

    border-color:
        #0876b9;

    box-shadow:
        0 0 0 3px
        rgba(8,118,185,.10);

}


.form-group input[readonly] {

    background:
        #f5f7fa;

    color:
        #718096;

}


.form-note {

    font-size:
        11px;

    color:
        #8a94a6;

    margin-top:
        1px;

}


/* =========================================================
   BUTTON
========================================================= */

.btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        8px;

    border:
        none;

    border-radius:
        9px;

    padding:
        12px 18px;

    cursor:
        pointer;

    font-size:
        13px;

    font-weight:
        700;

    text-decoration:
        none;

    transition:
        .2s ease;

}


.btn-primary {

    background:
        #003b70;

    color:
        white;

}


.btn-primary:hover {

    background:
        #00599b;

    transform:
        translateY(-1px);

}


.btn-light {

    background:
        #edf5fb;

    color:
        #003b70;

}


.btn-light:hover {

    background:
        #dfeefa;

}


.form-actions {

    margin-top:
        20px;

    display:
        flex;

    justify-content:
        flex-end;

}


/* =========================================================
   PROFILE INFORMATION
========================================================= */

.info-row {

    display:
        flex;

    justify-content:
        space-between;

    gap:
        20px;

    padding:
        12px 0;

    border-bottom:
        1px solid
        #edf1f5;

}


.info-row:last-child {

    border-bottom:
        none;

}


.info-label {

    color:
        #718096;

    font-size:
        12px;

}


.info-value {

    color:
        #263238;

    font-size:
        13px;

    font-weight:
        600;

    text-align:
        right;

    word-break:
        break-word;

}


/* =========================================================
   SUBJECT BOX
========================================================= */

.subject-box {

    background:
        #f4f8fc;

    border-left:
        4px solid
        #0876b9;

    border-radius:
        8px;

    padding:
        14px;

    font-size:
        13px;

    line-height:
        1.7;

    color:
        #425466;

}


/* =========================================================
   PHOTO CARD
========================================================= */

.photo-preview {

    width:
        135px;

    height:
        135px;

    border-radius:
        50%;

    overflow:
        hidden;

    margin:
        0 auto 17px;

    background:
        #edf3f8;

    border:
        4px solid
        #dce8f2;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #003b70;

    font-size:
        42px;

    font-weight:
        700;

}


.photo-preview img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


.photo-form input[type="file"] {

    width:
        100%;

    font-size:
        12px;

    padding:
        10px;

    border:
        1px dashed
        #b8c8d8;

    border-radius:
        9px;

    background:
        #f8fafc;

}


.photo-note {

    color:
        #7a8796;

    font-size:
        11px;

    line-height:
        1.5;

    margin:
        9px 0 15px;

}


/* =========================================================
   PASSWORD
========================================================= */

.password-group {

    position:
        relative;

}


.password-group input {

    padding-right:
        55px;

}


.toggle-password {

    position:
        absolute;

    right:
        12px;

    bottom:
        12px;

    color:
        #0876b9;

    font-size:
        11px;

    cursor:
        pointer;

    font-weight:
        700;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:
        center;

    color:
        #8a94a6;

    font-size:
        11px;

    padding:
        8px 0 20px;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 950px) {

    .content-grid {

        grid-template-columns:
            1fr;

    }

}


@media (max-width: 760px) {

    .sidebar {

        position:
            relative;

        width:
            100%;

        height:
            auto;

        padding:
            16px;

    }


    .logo {

        margin-bottom:
            12px;

    }


    .menu {

        display:
            grid;

        grid-template-columns:
            repeat(2, 1fr);

    }


    .main {

        margin-left:
            0;

        padding:
            15px;

    }


    .profile-hero {

        flex-direction:
            column;

        text-align:
            center;

    }


    .form-grid {

        grid-template-columns:
            1fr;

    }


    .form-group.full {

        grid-column:
            auto;

    }

}


@media (max-width: 480px) {

    .menu {

        grid-template-columns:
            1fr;

    }


    .page-header h1 {

        font-size:
            23px;

    }


    .profile-hero {

        padding:
            22px 15px;

    }


    .avatar {

        width:
            85px;

        height:
            85px;

        font-size:
            34px;

    }


    .card {

        padding:
            17px;

    }


    .info-row {

        flex-direction:
            column;

        gap:
            4px;

    }


    .info-value {

        text-align:
            left;

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

        <div class="logo-title">
            NISEL
        </div>

        <div class="logo-subtitle">
            ONLINE EDUCATION
        </div>

    </div>


    <nav class="menu">

        <a href="dashboard.php">

            🏠
            <span>Dashboard</span>

        </a>


        <a href="schedule.php">

            📅
            <span>My Schedule</span>

        </a>


        <a href="book_lesson.php">

            📚
            <span>Book a Lesson</span>

        </a>


        <a href="payments.php">

            💳
            <span>My Payments</span>

        </a>


        <a
            href="profile.php"
            class="active"
        >

            👤
            <span>My Profile</span>

        </a>


        <a href="logout.php">

            🚪
            <span>Logout</span>

        </a>

    </nav>

</aside>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- PAGE HEADER -->

    <section class="page-header">

        <h1>
            👤 My Profile
        </h1>

        <p>
            Manage your NISEL student account and personal information.
        </p>

    </section>



    <!-- =====================================================
         ALERTS
    ====================================================== -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            ✅

            <?= h($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-error">

            ⚠️

            <?= h($error) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         PROFILE HERO
    ====================================================== -->

    <section class="profile-hero">


        <div class="avatar">

            <?php if ($photoUrl !== ''): ?>

                <img
                    src="<?= h($photoUrl) ?>"
                    alt="Student Photo"
                >

            <?php else: ?>

                <?= h($initial) ?>

            <?php endif; ?>

        </div>


        <div>

            <h2>
                <?= h($displayName) ?>
            </h2>


            <div class="profile-email">

                📧
                <?= h($displayEmail) ?>

            </div>


            <span class="student-id">

                Student ID:
                <?= h($displayStudentId) ?>

            </span>

        </div>


    </section>



    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <div class="content-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================= -->

        <div>


            <!-- PROFILE DETAILS -->

            <section class="card">

                <div class="card-title">

                    👤
                    Personal Information

                </div>


                <form
                    method="POST"
                    action=""
                >

                    <input
                        type="hidden"
                        name="action"
                        value="update_profile"
                    >


                    <div class="form-grid">


                        <!-- NAME -->

                        <div class="form-group">

                            <label for="student_name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                id="student_name"
                                name="student_name"
                                value="<?= h($displayName) ?>"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input
                                type="email"
                                id="email"
                                value="<?= h($displayEmail) ?>"
                                readonly
                            >

                            <div class="form-note">
                                Your login email is managed by your NISEL account.
                            </div>

                        </div>


                        <!-- PHONE -->

                        <?php if ($hasPhoneColumn): ?>

                            <div class="form-group">

                                <label for="phone">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    id="phone"
                                    name="phone"
                                    value="<?= h($displayPhone) ?>"
                                    placeholder="Enter your phone number"
                                >

                            </div>

                        <?php endif; ?>


                        <!-- DOB -->

                        <?php if ($hasDobColumn): ?>

                            <div class="form-group">

                                <label for="dob">
                                    Date of Birth
                                </label>

                                <input
                                    type="date"
                                    id="dob"
                                    name="dob"
                                    value="<?= h($displayDob) ?>"
                                >

                            </div>

                        <?php endif; ?>


                    </div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            💾
                            Save Changes

                        </button>

                    </div>

                </form>

            </section>



            <!-- ACADEMIC INFORMATION -->

            <section class="card">

                <div class="card-title">

                    🎓
                    Academic Information

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Curriculum
                    </div>

                    <div class="info-value">
                        <?= h(
                            $displayCurriculum
                            ?: 'Not provided'
                        ) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Class / Year
                    </div>

                    <div class="info-value">
                        <?= h(
                            $displayClass
                            ?: 'Not provided'
                        ) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Student ID
                    </div>

                    <div class="info-value">
                        <?= h($displayStudentId) ?>
                    </div>

                </div>


                <?php if ($displaySubjects !== ''): ?>

                    <div
                        style="
                            margin-top:18px;
                        "
                    >

                        <div
                            class="info-label"
                            style="
                                margin-bottom:8px;
                            "
                        >
                            Registered Subject(s)
                        </div>


                        <div class="subject-box">

                            <?= nl2br(
                                h($displaySubjects)
                            ) ?>

                        </div>

                    </div>

                <?php endif; ?>


            </section>



            <!-- PASSWORD -->

            <?php if ($hasPasswordColumn): ?>

                <section class="card">

                    <div class="card-title">

                        🔐
                        Change Password

                    </div>


                    <form
                        method="POST"
                        action=""
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="change_password"
                        >


                        <div class="form-grid">


                            <div class="form-group password-group">

                                <label for="current_password">
                                    Current Password
                                </label>

                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    required
                                >

                                <span
                                    class="toggle-password"
                                    onclick="
                                        togglePassword(
                                            'current_password',
                                            this
                                        )
                                    "
                                >
                                    Show
                                </span>

                            </div>


                            <div class="form-group password-group">

                                <label for="new_password">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="8"
                                    required
                                >

                                <span
                                    class="toggle-password"
                                    onclick="
                                        togglePassword(
                                            'new_password',
                                            this
                                        )
                                    "
                                >
                                    Show
                                </span>

                            </div>


                            <div class="form-group password-group">

                                <label for="confirm_password">
                                    Confirm New Password
                                </label>

                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    required
                                >

                                <span
                                    class="toggle-password"
                                    onclick="
                                        togglePassword(
                                            'confirm_password',
                                            this
                                        )
                                    "
                                >
                                    Show
                                </span>

                            </div>


                        </div>


                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                🔐
                                Update Password

                            </button>

                        </div>

                    </form>

                </section>

            <?php endif; ?>


        </div>



        <!-- =================================================
             RIGHT COLUMN
        ================================================= -->

        <div>


            <!-- PHOTO -->

            <?php if ($hasPhotoColumn): ?>

                <section class="card">

                    <div class="card-title">

                        📷
                        Profile Photo

                    </div>


                    <div class="photo-preview">

                        <?php if ($photoUrl !== ''): ?>

                            <img
                                src="<?= h($photoUrl) ?>"
                                alt="Student Photo"
                            >

                        <?php else: ?>

                            <?= h($initial) ?>

                        <?php endif; ?>

                    </div>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        class="photo-form"
                    >

                        <input
                            type="hidden"
                            name="action"
                            value="update_photo"
                        >


                        <input
                            type="file"
                            name="photo"
                            accept="
                                image/jpeg,
                                image/png,
                                image/webp
                            "
                            required
                        >


                        <div class="photo-note">

                            JPG, PNG or WEBP only.
                            Maximum file size: 5MB.

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                            style="width:100%;"
                        >

                            📷
                            Update Photo

                        </button>

                    </form>

                </section>

            <?php endif; ?>



            <!-- ACCOUNT INFORMATION -->

            <section class="card">

                <div class="card-title">

                    ℹ️
                    Account Information

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Account Type
                    </div>

                    <div class="info-value">
                        Student
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Account ID
                    </div>

                    <div class="info-value">
                        <?= h($displayStudentId) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Email
                    </div>

                    <div class="info-value">
                        <?= h($displayEmail) ?>
                    </div>

                </div>


                <div class="info-row">

                    <div class="info-label">
                        Curriculum
                    </div>

                    <div class="info-value">
                        <?= h(
                            $displayCurriculum
                            ?: 'Not provided'
                        ) ?>
                    </div>

                </div>


            </section>



            <!-- QUICK LINKS -->

            <section class="card">

                <div class="card-title">

                    ⚡
                    Quick Actions

                </div>


                <a
                    href="dashboard.php"
                    class="btn btn-light"
                    style="
                        width:100%;
                        margin-bottom:9px;
                    "
                >

                    🏠
                    Student Dashboard

                </a>


                <a
                    href="schedule.php"
                    class="btn btn-light"
                    style="
                        width:100%;
                        margin-bottom:9px;
                    "
                >

                    📅
                    My Schedule

                </a>


                <a
                    href="book_lesson.php"
                    class="btn btn-light"
                    style="
                        width:100%;
                        margin-bottom:9px;
                    "
                >

                    📚
                    Book a Lesson

                </a>


                <a
                    href="payments.php"
                    class="btn btn-light"
                    style="
                        width:100%;
                    "
                >

                    💳
                    My Payments

                </a>

            </section>


        </div>


    </div>



    <div class="footer">

        © <?= date('Y') ?>
        NISEL ONLINE EDUCATION.
        Student Portal.

    </div>


</main>



<script>

/* =========================================================
   PASSWORD VISIBILITY
========================================================= */

function togglePassword(
    inputId,
    element
) {

    const input =
        document.getElementById(
            inputId
        );


    if (!input) {
        return;
    }


    if (
        input.type === "password"
    ) {

        input.type =
            "text";

        element.textContent =
            "Hide";

    } else {

        input.type =
            "password";

        element.textContent =
            "Show";
    }
}

</script>


</body>

</html>
