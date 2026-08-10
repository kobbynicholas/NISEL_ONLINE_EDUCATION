<?php

require "../teacher_auth.php";
require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER PROFILE
| PDO VERSION
|--------------------------------------------------------------------------
*/

$teacher_id = $_SESSION['teacher_id'] ?? '';

if (empty($teacher_id)) {
    header("Location: login.php");
    exit;
}

$message = "";
$message_type = "";


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
| GET TEACHER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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
        password,
        status
    FROM teachers
    WHERE teacher_id = ?
    LIMIT 1
");

$stmt->execute([
    $teacher_id
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$teacher) {

    die("
        <div style='
            font-family:Arial;
            text-align:center;
            padding:60px;
        '>
            <h2 style='color:#003366;'>
                Teacher Account Not Found
            </h2>

            <p>
                Your teacher account could not be found.
            </p>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$teacher_name  = $teacher['teacher_name'] ?? '';
$phone         = $teacher['phone'] ?? '';
$email         = $teacher['email'] ?? '';
$qualification = $teacher['qualification'] ?? '';
$subjects      = $teacher['subjects'] ?? '';
$curriculum    = $teacher['curriculum'] ?? '';
$experience    = $teacher['experience'] ?? '';
$bio           = $teacher['bio'] ?? '';
$availability  = $teacher['availability'] ?? '';
$current_photo = $teacher['photo'] ?? '';

/*
|--------------------------------------------------------------------------
| ZOOM LINK
|--------------------------------------------------------------------------
|
| Your student schedule uses teachers.zoom_link.
|
| We check whether the column exists before using it.
| This prevents the profile from crashing if it has not
| yet been added to your teachers table.
|
*/

$zoom_column_exists = false;

try {

    $columnCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'teachers'
            AND COLUMN_NAME = 'zoom_link'
    ");

    $columnCheck->execute();

    $zoom_column_exists =
        ((int)$columnCheck->fetchColumn() > 0);

} catch (PDOException $e) {

    $zoom_column_exists = false;

}


$zoom_link = '';

if ($zoom_column_exists) {

    $zoomStmt = $pdo->prepare("
        SELECT zoom_link
        FROM teachers
        WHERE teacher_id = ?
        LIMIT 1
    ");

    $zoomStmt->execute([
        $teacher_id
    ]);

    $zoom_link =
        $zoomStmt->fetchColumn() ?? '';

}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['update_profile'])
) {

    $teacher_name =
        trim(
            $_POST['teacher_name'] ?? ''
        );

    $phone =
        trim(
            $_POST['phone'] ?? ''
        );

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $qualification =
        trim(
            $_POST['qualification'] ?? ''
        );

    $subjects =
        trim(
            $_POST['subjects'] ?? ''
        );

    $curriculum =
        trim(
            $_POST['curriculum'] ?? ''
        );

    $experience =
        trim(
            $_POST['experience'] ?? ''
        );

    $bio =
        trim(
            $_POST['bio'] ?? ''
        );

    $availability =
        trim(
            $_POST['availability'] ?? ''
        );

    $zoom_link =
        trim(
            $_POST['zoom_link'] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        empty($teacher_name)
        ||
        empty($phone)
        ||
        empty($email)
        ||
        empty($qualification)
        ||
        empty($subjects)
        ||
        empty($curriculum)
        ||
        empty($experience)
        ||
        empty($availability)
    ) {

        $message =
            "Please complete all required fields.";

        $message_type =
            "error";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $message_type =
            "error";

    } elseif (
        !empty($zoom_link)
        &&
        !filter_var(
            $zoom_link,
            FILTER_VALIDATE_URL
        )
    ) {

        $message =
            "Please enter a valid Zoom meeting URL.";

        $message_type =
            "error";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CHECK EMAIL
        |--------------------------------------------------------------------------
        |
        | Don't allow another teacher to use this email.
        |
        */

        $emailCheck = $pdo->prepare("
            SELECT id
            FROM teachers
            WHERE
                email = ?
                AND teacher_id != ?
            LIMIT 1
        ");

        $emailCheck->execute([
            $email,
            $teacher_id
        ]);


        if ($emailCheck->fetch()) {

            $message =
                "Another teacher account is already using this email.";

            $message_type =
                "error";

        } else {


            /*
            |--------------------------------------------------------------------------
            | PHOTO HANDLING
            |--------------------------------------------------------------------------
            */

            $new_photo_name = $current_photo;

            $uploaded_new_photo = false;

            $new_photo_path = '';

            $old_photo_path = '';


            if (
                isset($_FILES['photo'])
                &&
                $_FILES['photo']['error']
                !==
                UPLOAD_ERR_NO_FILE
            ) {


                if (
                    $_FILES['photo']['error']
                    !==
                    UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was an error uploading the photo.";

                    $message_type =
                        "error";

                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | ALLOWED TYPES
                    |--------------------------------------------------------------------------
                    */

                    $allowed_types = [

                        'image/jpeg',
                        'image/png',
                        'image/webp'

                    ];


                    $file_type =
                        mime_content_type(
                            $_FILES['photo']['tmp_name']
                        );


                    $file_size =
                        (int)
                        $_FILES['photo']['size'];


                    if (
                        $file_size
                        >
                        5 * 1024 * 1024
                    ) {

                        $message =
                            "Teacher photo must not exceed 5MB.";

                        $message_type =
                            "error";

                    } elseif (
                        !in_array(
                            $file_type,
                            $allowed_types,
                            true
                        )
                    ) {

                        $message =
                            "Only JPG, PNG and WEBP images are allowed.";

                        $message_type =
                            "error";

                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | PHOTO DIRECTORY
                        |--------------------------------------------------------------------------
                        |
                        | teacher/profile.php is inside /teacher
                        |
                        | Therefore:
                        |
                        | teacher/uploads/teachers/
                        |
                        */

                        $upload_directory =
                            __DIR__
                            .
                            DIRECTORY_SEPARATOR
                            .
                            "uploads"
                            .
                            DIRECTORY_SEPARATOR
                            .
                            "teachers"
                            .
                            DIRECTORY_SEPARATOR;


                        if (
                            !is_dir(
                                $upload_directory
                            )
                        ) {

                            if (
                                !mkdir(
                                    $upload_directory,
                                    0755,
                                    true
                                )
                            ) {

                                $message =
                                    "Unable to create the teacher photo directory.";

                                $message_type =
                                    "error";

                            }

                        }


                        if (
                            $message_type
                            !==
                            "error"
                        ) {


                            /*
                            |--------------------------------------------------------------------------
                            | FILE EXTENSION
                            |--------------------------------------------------------------------------
                            */

                            $extension =
                                strtolower(
                                    pathinfo(
                                        $_FILES['photo']['name'],
                                        PATHINFO_EXTENSION
                                    )
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | UNIQUE PHOTO NAME
                            |--------------------------------------------------------------------------
                            */

                            $new_photo_name =

                                $teacher_id
                                .
                                "_"
                                .
                                time()
                                .
                                "_"
                                .
                                bin2hex(
                                    random_bytes(4)
                                )
                                .
                                "."
                                .
                                $extension;


                            $new_photo_path =

                                $upload_directory
                                .
                                $new_photo_name;


                            /*
                            |--------------------------------------------------------------------------
                            | MOVE NEW PHOTO
                            |--------------------------------------------------------------------------
                            */

                            if (
                                move_uploaded_file(
                                    $_FILES['photo']['tmp_name'],
                                    $new_photo_path
                                )
                            ) {

                                $uploaded_new_photo =
                                    true;


                                /*
                                |--------------------------------------------------------------------------
                                | OLD PHOTO PATH
                                |--------------------------------------------------------------------------
                                */

                                if (
                                    !empty(
                                        $current_photo
                                    )
                                ) {

                                    $old_photo_path =

                                        $upload_directory
                                        .
                                        basename(
                                            $current_photo
                                        );

                                }

                            } else {

                                $message =
                                    "Unable to save the new teacher photo.";

                                $message_type =
                                    "error";

                                $new_photo_name =
                                    $current_photo;

                            }

                        }

                    }

                }

            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE DATABASE
            |--------------------------------------------------------------------------
            */

            if (
                $message_type
                !==
                "error"
            ) {

                try {


                    /*
                    |--------------------------------------------------------------------------
                    | START TRANSACTION
                    |--------------------------------------------------------------------------
                    */

                    $pdo->beginTransaction();


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE WITH ZOOM LINK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $zoom_column_exists
                    ) {

                        $update = $pdo->prepare("

                            UPDATE teachers

                            SET

                                teacher_name = :teacher_name,

                                phone = :phone,

                                email = :email,

                                qualification = :qualification,

                                subjects = :subjects,

                                curriculum = :curriculum,

                                experience = :experience,

                                bio = :bio,

                                availability = :availability,

                                photo = :photo,

                                zoom_link = :zoom_link

                            WHERE

                                teacher_id = :teacher_id

                        ");


                        $update->execute([

                            ':teacher_name'
                                => $teacher_name,

                            ':phone'
                                => $phone,

                            ':email'
                                => $email,

                            ':qualification'
                                => $qualification,

                            ':subjects'
                                => $subjects,

                            ':curriculum'
                                => $curriculum,

                            ':experience'
                                => $experience,

                            ':bio'
                                => $bio,

                            ':availability'
                                => $availability,

                            ':photo'
                                => $new_photo_name,

                            ':zoom_link'
                                => $zoom_link,

                            ':teacher_id'
                                => $teacher_id

                        ]);

                    } else {


                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE WITHOUT ZOOM LINK
                        |--------------------------------------------------------------------------
                        */

                        $update = $pdo->prepare("

                            UPDATE teachers

                            SET

                                teacher_name = :teacher_name,

                                phone = :phone,

                                email = :email,

                                qualification = :qualification,

                                subjects = :subjects,

                                curriculum = :curriculum,

                                experience = :experience,

                                bio = :bio,

                                availability = :availability,

                                photo = :photo

                            WHERE

                                teacher_id = :teacher_id

                        ");


                        $update->execute([

                            ':teacher_name'
                                => $teacher_name,

                            ':phone'
                                => $phone,

                            ':email'
                                => $email,

                            ':qualification'
                                => $qualification,

                            ':subjects'
                                => $subjects,

                            ':curriculum'
                                => $curriculum,

                            ':experience'
                                => $experience,

                            ':bio'
                                => $bio,

                            ':availability'
                                => $availability,

                            ':photo'
                                => $new_photo_name,

                            ':teacher_id'
                                => $teacher_id

                        ]);

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | COMMIT
                    |--------------------------------------------------------------------------
                    */

                    $pdo->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | DELETE OLD PHOTO
                    |--------------------------------------------------------------------------
                    |
                    | Only delete the old photo AFTER the database
                    | has successfully been updated.
                    |
                    */

                    if (
                        $uploaded_new_photo
                        &&
                        !empty(
                            $old_photo_path
                        )
                        &&
                        file_exists(
                            $old_photo_path
                        )
                    ) {

                        @unlink(
                            $old_photo_path
                        );

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE SESSION
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION[
                        'teacher_name'
                    ] =
                        $teacher_name;


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE LOCAL VALUES
                    |--------------------------------------------------------------------------
                    */

                    $current_photo =
                        $new_photo_name;


                    $teacher['teacher_name'] =
                        $teacher_name;

                    $teacher['phone'] =
                        $phone;

                    $teacher['email'] =
                        $email;

                    $teacher['qualification'] =
                        $qualification;

                    $teacher['subjects'] =
                        $subjects;

                    $teacher['curriculum'] =
                        $curriculum;

                    $teacher['experience'] =
                        $experience;

                    $teacher['bio'] =
                        $bio;

                    $teacher['availability'] =
                        $availability;

                    $teacher['photo'] =
                        $current_photo;


                    /*
                    |--------------------------------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        "Your profile has been updated successfully.";

                    if (
                        $uploaded_new_photo
                    ) {

                        $message .=
                            " Your profile photo has also been updated.";

                    }

                    $message_type =
                        "success";


                } catch (
                    PDOException $e
                ) {


                    /*
                    |--------------------------------------------------------------------------
                    | ROLLBACK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $pdo->inTransaction()
                    ) {

                        $pdo->rollBack();

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | REMOVE NEW PHOTO IF DATABASE UPDATE FAILED
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $uploaded_new_photo
                        &&
                        !empty(
                            $new_photo_path
                        )
                        &&
                        file_exists(
                            $new_photo_path
                        )
                    ) {

                        @unlink(
                            $new_photo_path
                        );

                    }


                    $message =
                        "Unable to update profile: "
                        .
                        $e->getMessage();

                    $message_type =
                        "error";

                }

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| PROFILE PHOTO URL
|--------------------------------------------------------------------------
*/

$photo_url = '';

if (
    !empty($current_photo)
) {

    $photo_url =
        "uploads/teachers/"
        .
        rawurlencode(
            basename(
                $current_photo
            )
        );

}


/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['change_password'])
) {

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
        empty($current_password)
        ||
        empty($new_password)
        ||
        empty($confirm_password)
    ) {

        $message =
            "Please complete all password fields.";

        $message_type =
            "error";

    } elseif (
        strlen($new_password) < 8
    ) {

        $message =
            "New password must contain at least 8 characters.";

        $message_type =
            "error";

    } elseif (
        $new_password
        !==
        $confirm_password
    ) {

        $message =
            "The new passwords do not match.";

        $message_type =
            "error";

    } else {


        /*
        |--------------------------------------------------------------------------
        | GET CURRENT HASH
        |--------------------------------------------------------------------------
        */

        $passwordStmt = $pdo->prepare("

            SELECT password

            FROM teachers

            WHERE teacher_id = ?

            LIMIT 1

        ");


        $passwordStmt->execute([
            $teacher_id
        ]);


        $currentHash =
            $passwordStmt->fetchColumn();


        if (
            !$currentHash
            ||
            !password_verify(
                $current_password,
                $currentHash
            )
        ) {

            $message =
                "Your current password is incorrect.";

            $message_type =
                "error";

        } else {


            /*
            |--------------------------------------------------------------------------
            | HASH NEW PASSWORD
            |--------------------------------------------------------------------------
            */

            $newHash =
                password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );


            $passwordUpdate =
                $pdo->prepare("

                    UPDATE teachers

                    SET password = ?

                    WHERE teacher_id = ?

                ");


            $passwordUpdate->execute([

                $newHash,

                $teacher_id

            ]);


            $message =
                "Your password has been changed successfully.";

            $message_type =
                "success";

        }

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
    My Profile | NISEL ONLINE EDUCATION
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

    background: #eef3f8;

    color: #333;
}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

    overflow-y: auto;
}


.logo {

    text-align: center;

    font-size: 19px;

    font-weight: bold;

    line-height: 1.5;

    margin-bottom: 35px;
}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

    transition: .2s;
}


.menu a:hover {

    background: #0055a5;
}


.menu a.active {

    background: #0055a5;
}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* =====================================================
   TOPBAR
===================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);
}


.topbar h2 {

    margin: 0;

    color: #003366;
}


.teacher-name {

    color: #666;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);
}


.page-header h2 {

    margin: 0 0 8px;

    color: #003366;
}


.page-header p {

    margin: 0;

    color: #777;
}


/* =====================================================
   ALERTS
===================================================== */

.alert {

    padding: 15px 18px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-weight: 600;
}


.alert.success {

    background: #d4edda;

    color: #155724;

}


.alert.error {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   PROFILE GRID
===================================================== */

.profile-grid {

    display: grid;

    grid-template-columns:
        280px
        minmax(0, 1fr);

    gap: 25px;

    align-items: start;
}


/* =====================================================
   CARDS
===================================================== */

.card {

    background: white;

    border-radius: 12px;

    padding: 25px;

    box-shadow:
        0 4px 12px
        rgba(0,0,0,.06);

    margin-bottom: 25px;
}


.card h3 {

    color: #003366;

    margin-top: 0;

    margin-bottom: 20px;
}


/* =====================================================
   PHOTO
===================================================== */

.photo-card {

    text-align: center;
}


.profile-photo {

    width: 180px;

    height: 180px;

    border-radius: 50%;

    object-fit: cover;

    border:
        5px solid #e4edf6;

    margin-bottom: 15px;
}


.photo-placeholder {

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background: #e8f0f8;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 15px;

    color: #003366;

    font-size: 70px;

    font-weight: bold;
}


.teacher-id {

    font-size: 13px;

    color: #777;

    margin-bottom: 20px;
}


.photo-upload {

    text-align: left;

    margin-top: 20px;
}


.photo-upload label {

    display: block;

    font-weight: bold;

    color: #003366;

    margin-bottom: 8px;
}


.photo-upload input {

    width: 100%;

    font-size: 13px;
}


/* =====================================================
   FORM
===================================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0,1fr)
        );

    gap: 18px;
}


.form-group {

    display: flex;

    flex-direction: column;

    gap: 7px;
}


.form-group.full {

    grid-column: 1 / -1;
}


.form-group label {

    font-weight: bold;

    color: #003366;
}


input,
select,
textarea {

    width: 100%;

    padding: 12px;

    border:
        1px solid #ccd5df;

    border-radius: 7px;

    font-family: inherit;

    font-size: 15px;

    outline: none;
}


input:focus,
select:focus,
textarea:focus {

    border-color: #0055a5;

    box-shadow:
        0 0 0 3px
        rgba(0,85,165,.1);
}


textarea {

    min-height: 130px;

    resize: vertical;
}


/* =====================================================
   ZOOM
===================================================== */

.zoom-box {

    background: #f2f7fc;

    border:
        1px solid #d9e5f0;

    border-radius: 8px;

    padding: 15px;

    margin-top: 5px;
}


.zoom-box strong {

    color: #003366;
}


.zoom-help {

    font-size: 13px;

    color: #777;

    margin-top: 6px;

    line-height: 1.5;
}


/* =====================================================
   BUTTON
===================================================== */

.button-row {

    display: flex;

    gap: 12px;

    margin-top: 10px;

    flex-wrap: wrap;
}


.btn {

    display: inline-block;

    border: none;

    padding: 13px 22px;

    border-radius: 7px;

    cursor: pointer;

    font-weight: bold;

    text-decoration: none;

    font-size: 15px;
}


.btn-primary {

    background: #003366;

    color: white;
}


.btn-primary:hover {

    background: #0055a5;
}


.btn-secondary {

    background: #e8edf3;

    color: #003366;
}


.btn-danger {

    background: #8b0000;

    color: white;
}


/* =====================================================
   PASSWORD
===================================================== */

.password-card {

    max-width: 900px;
}


.password-note {

    background: #fff8e1;

    color: #6b5600;

    padding: 12px;

    border-radius: 7px;

    margin-bottom: 20px;

    font-size: 13px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 900px) {

    .profile-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width: 700px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;

    }


    .form-grid {

        grid-template-columns: 1fr;

    }


    .form-group.full {

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

            👨‍🎓 My Students

        </a>


        <a href="schedule.php">

            📅 My Schedule

        </a>


        <a
            href="profile.php"
            class="active"
        >

            👤 My Profile

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

            👤 My Profile

        </h2>


        <div class="teacher-name">

            Welcome,

            <strong>

                <?= h(
                    $teacher_name
                ) ?>

            </strong>

        </div>


    </div>


    <div class="page-header">


        <h2>

            Manage Your Profile

        </h2>


        <p>

            Update your NISEL ONLINE EDUCATION
            teacher information, profile photo,
            teaching information and Zoom classroom link.

        </p>


    </div>


    <?php if (
        !empty($message)
    ): ?>


        <div
            class="alert
            <?= h(
                $message_type
            ) ?>"
        >

            <?= h($message) ?>

        </div>


    <?php endif; ?>


    <!-- =================================================
         PROFILE
    ================================================== -->

    <form
        method="POST"
        enctype="multipart/form-data"
    >


        <div class="profile-grid">


            <!-- =================================================
                 PHOTO
            ================================================== -->

            <div class="card photo-card">


                <h3>

                    Profile Photo

                </h3>


                <?php if (
                    !empty($photo_url)
                ): ?>


                    <img

                        src="<?= h(
                            $photo_url
                        ) ?>?v=<?= time() ?>"

                        class="profile-photo"

                        alt="Teacher Profile Photo"

                    >


                <?php else: ?>


                    <div
                        class="photo-placeholder"
                    >

                        👤

                    </div>


                <?php endif; ?>


                <div class="teacher-id">

                    Teacher ID:

                    <strong>

                        <?= h(
                            $teacher_id
                        ) ?>

                    </strong>

                </div>


                <div class="photo-upload">


                    <label>

                        Change Profile Photo

                    </label>


                    <input

                        type="file"

                        name="photo"

                        accept="
                            image/jpeg,
                            image/png,
                            image/webp
                        "

                    >


                    <small>

                        JPG, PNG or WEBP.
                        Maximum 5MB.

                    </small>


                </div>


            </div>


            <!-- =================================================
                 INFORMATION
            ================================================== -->

            <div>


                <div class="card">


                    <h3>

                        👤 Personal Information

                    </h3>


                    <div class="form-grid">


                        <div
                            class="form-group"
                        >

                            <label>

                                Full Name

                            </label>


                            <input

                                type="text"

                                name="teacher_name"

                                value="<?= h(
                                    $teacher_name
                                ) ?>"

                                required

                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Phone Number

                            </label>


                            <input

                                type="text"

                                name="phone"

                                value="<?= h(
                                    $phone
                                ) ?>"

                                required

                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Email Address

                            </label>


                            <input

                                type="email"

                                name="email"

                                value="<?= h(
                                    $email
                                ) ?>"

                                required

                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Qualification

                            </label>


                            <input

                                type="text"

                                name="qualification"

                                value="<?= h(
                                    $qualification
                                ) ?>"

                                placeholder="e.g. BSc, MSc, MEd"

                                required

                            >

                        </div>


                    </div>


                </div>


                <!-- =================================================
                     PROFESSIONAL
                ================================================== -->

                <div class="card">


                    <h3>

                        🎓 Professional Information

                    </h3>


                    <div class="form-grid">


                        <div
                            class="form-group"
                        >

                            <label>

                                Subjects

                            </label>


                            <input

                                type="text"

                                name="subjects"

                                value="<?= h(
                                    $subjects
                                ) ?>"

                                placeholder="
                                    Mathematics, Physics
                                "

                                required

                            >

                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Curriculum

                            </label>


                            <select
                                name="curriculum"
                                required
                            >


                                <option value="">

                                    Select Curriculum

                                </option>


                                <?php

                                $curriculumOptions = [

                                    "Cambridge IGCSE",

                                    "Cambridge Checkpoint",

                                    "Cambridge AS/A Level",

                                    "GES",

                                    "IB",

                                    "Other"

                                ];

                                ?>


                                <?php foreach (
                                    $curriculumOptions
                                    as $option
                                ): ?>


                                    <option

                                        value="<?= h(
                                            $option
                                        ) ?>"

                                        <?= (
                                            $curriculum
                                            ===
                                            $option
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>

                                    >

                                        <?= h(
                                            $option
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Teaching Experience

                            </label>


                            <select
                                name="experience"
                                required
                            >


                                <option value="">

                                    Select Experience

                                </option>


                                <?php

                                $experienceOptions = [

                                    "Less than 1 year",

                                    "1 - 2 years",

                                    "3 - 5 years",

                                    "6 - 10 years",

                                    "More than 10 years"

                                ];

                                ?>


                                <?php foreach (
                                    $experienceOptions
                                    as $option
                                ): ?>


                                    <option

                                        value="<?= h(
                                            $option
                                        ) ?>"

                                        <?= (
                                            $experience
                                            ===
                                            $option
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>

                                    >

                                        <?= h(
                                            $option
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <div
                            class="form-group"
                        >

                            <label>

                                Availability

                            </label>


                            <select
                                name="availability"
                                required
                            >


                                <option value="">

                                    Select Availability

                                </option>


                                <?php

                                $availabilityOptions = [

                                    "Weekdays",

                                    "Weekends",

                                    "Weekdays and Weekends",

                                    "Flexible"

                                ];

                                ?>


                                <?php foreach (
                                    $availabilityOptions
                                    as $option
                                ): ?>


                                    <option

                                        value="<?= h(
                                            $option
                                        ) ?>"

                                        <?= (
                                            $availability
                                            ===
                                            $option
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>

                                    >

                                        <?= h(
                                            $option
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <div
                            class="form-group full"
                        >

                            <label>

                                Professional Biography

                            </label>


                            <textarea

                                name="bio"

                                placeholder="
                                    Tell students about your
                                    teaching experience,
                                    expertise and approach...
                                "

                            ><?= h(
                                $bio
                            ) ?></textarea>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     ZOOM
                ================================================== -->

                <div class="card">


                    <h3>

                        🎥 Zoom Classroom

                    </h3>


                    <?php if (
                        !$zoom_column_exists
                    ): ?>


                        <div
                            class="alert error"
                        >

                            The
                            <strong>
                                zoom_link
                            </strong>
                            column does not yet exist
                            in your teachers table.

                            <br><br>

                            Run this SQL in phpMyAdmin:

                            <br><br>

                            <code>

                                ALTER TABLE teachers
                                ADD COLUMN zoom_link
                                VARCHAR(500) NULL;

                            </code>

                        </div>


                    <?php endif; ?>


                    <div
                        class="zoom-box"
                    >


                        <strong>

                            Student Meeting Link

                        </strong>


                        <p
                            class="zoom-help"
                        >

                            Enter the Zoom meeting link
                            that students should use when
                            joining your scheduled lessons.

                            The link will be available to
                            students from their schedule.

                        </p>


                        <input

                            type="url"

                            name="zoom_link"

                            value="<?= h(
                                $zoom_link
                            ) ?>"

                            placeholder="
                                https://zoom.us/j/123456789
                            "

                            <?= !$zoom_column_exists
                                ? 'disabled'
                                : ''
                            ?>

                        >


                    </div>


                </div>


                <!-- =================================================
                     SAVE
                ================================================== -->

                <div class="card">


                    <div class="button-row">


                        <button

                            type="submit"

                            name="update_profile"

                            class="btn btn-primary"

                        >

                            💾 Save Profile Changes

                        </button>


                        <a

                            href="dashboard.php"

                            class="btn btn-secondary"

                        >

                            Cancel

                        </a>


                    </div>


                </div>


            </div>


        </div>


    </form>


    <!-- =================================================
         PASSWORD
    ================================================== -->

    <div class="card password-card">


        <h3>

            🔐 Change Password

        </h3>


        <div
            class="password-note"
        >

            For security, enter your current password
            before choosing a new password.

        </div>


        <form
            method="POST"
        >


            <div class="form-grid">


                <div
                    class="form-group"
                >

                    <label>

                        Current Password

                    </label>


                    <input

                        type="password"

                        name="current_password"

                        required

                    >

                </div>


                <div
                    class="form-group"
                >

                    <label>

                        New Password

                    </label>


                    <input

                        type="password"

                        name="new_password"

                        minlength="8"

                        required

                    >

                </div>


                <div
                    class="form-group"
                >

                    <label>

                        Confirm New Password

                    </label>


                    <input

                        type="password"

                        name="confirm_password"

                        minlength="8"

                        required

                    >

                </div>


            </div>


            <div class="button-row">


                <button

                    type="submit"

                    name="change_password"

                    class="btn btn-primary"

                >

                    🔐 Change Password

                </button>


            </div>


        </form>


    </div>


</div>


</body>

</html>
