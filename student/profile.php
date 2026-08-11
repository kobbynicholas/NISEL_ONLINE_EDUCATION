<?php

session_start();

require "../config/db.php";


/* =========================================================
   STUDENT AUTHENTICATION
========================================================= */

if (
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {
    header("Location: login.php");
    exit;
}


$studentId = $_SESSION['student_id'];

$success = "";
$error = "";


/* =========================================================
   HELPER
========================================================= */

function h($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   LOAD STUDENT
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM students
        WHERE student_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $studentId
    ]);

    $student = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$student) {

        session_destroy();

        header("Location: profile.php");

        exit;
    }

} catch (PDOException $e) {

    die(
        "Unable to load profile: "
        . h($e->getMessage())
    );
}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["update_profile"])
) {

    $studentName =
        trim($_POST["student_name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $dob =
        trim($_POST["dob"] ?? "");

    $curriculum =
        trim($_POST["curriculum"] ?? "");

    $classYear =
        trim($_POST["class_year"] ?? "");


    if ($studentName === "") {

        $error =
            "Student name is required.";

    } elseif ($email === "") {

        $error =
            "Email address is required.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {

            /*
            -------------------------------------------------
            CHECK WHETHER EMAIL IS ALREADY USED
            -------------------------------------------------
            */

            $checkStmt = $pdo->prepare("
                SELECT student_id
                FROM students
                WHERE email = ?
                AND student_id <> ?
                LIMIT 1
            ");

            $checkStmt->execute([
                $email,
                $studentId
            ]);


            if ($checkStmt->fetch()) {

                $error =
                    "This email address is already being used by another student.";

            } else {

                /*
                -------------------------------------------------
                UPDATE
                -------------------------------------------------
                */

                $updateStmt = $pdo->prepare("
                    UPDATE students

                    SET
                        student_name = ?,
                        email = ?,
                        phone = ?,
                        dob = ?,
                        curriculum = ?,
                        class_year = ?

                    WHERE student_id = ?
                ");


                $updateStmt->execute([

                    $studentName,
                    $email,
                    $phone,
                    $dob !== ""
                        ? $dob
                        : null,
                    $curriculum,
                    $classYear,
                    $studentId

                ]);


                /*
                -------------------------------------------------
                UPDATE SESSION NAME
                -------------------------------------------------
                */

                $_SESSION["student_name"] =
                    $studentName;


                $success =
                    "Your profile has been updated successfully.";


                /*
                -------------------------------------------------
                RELOAD STUDENT
                -------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM students
                    WHERE student_id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $studentId
                ]);

                $student =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );
            }

        } catch (PDOException $e) {

            $error =
                "Unable to update profile: "
                . $e->getMessage();
        }
    }
}


/* =========================================================
   PROFILE PHOTO UPLOAD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["upload_photo"])
) {

    if (
        !isset($_FILES["profile_photo"])
        ||
        $_FILES["profile_photo"]["error"]
        !== UPLOAD_ERR_OK
    ) {

        $error =
            "Please select a valid photo.";

    } else {

        $file =
            $_FILES["profile_photo"];

        $maxSize =
            3 * 1024 * 1024;


        if ($file["size"] > $maxSize) {

            $error =
                "Photo must not exceed 3MB.";

        } else {

            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];


            $finfo =
                new finfo(
                    FILEINFO_MIME_TYPE
                );

            $mime =
                $finfo->file(
                    $file["tmp_name"]
                );


            if (
                !in_array(
                    $mime,
                    $allowedTypes,
                    true
                )
            ) {

                $error =
                    "Only JPG, PNG and WEBP images are allowed.";

            } else {

                /*
                -------------------------------------------------
                UPLOAD DIRECTORY
                -------------------------------------------------
                */

                $uploadDir =
                    __DIR__
                    . "/../uploads/students/";


                if (
                    !is_dir($uploadDir)
                ) {

                    mkdir(
                        $uploadDir,
                        0755,
                        true
                    );
                }


                /*
                -------------------------------------------------
                FILE EXTENSION
                -------------------------------------------------
                */

                $extension = match ($mime) {

                    "image/jpeg" => "jpg",

                    "image/png" => "png",

                    "image/webp" => "webp",

                    default => "jpg"
                };


                /*
                -------------------------------------------------
                UNIQUE FILE NAME
                -------------------------------------------------
                */

                $fileName =
                    "student_"
                    . preg_replace(
                        "/[^A-Za-z0-9_-]/",
                        "",
                        $studentId
                    )
                    . "_"
                    . time()
                    . "."
                    . $extension;


                $destination =
                    $uploadDir
                    . $fileName;


                if (
                    move_uploaded_file(
                        $file["tmp_name"],
                        $destination
                    )
                ) {

                    /*
                    -------------------------------------------------
                    OLD PHOTO
                    -------------------------------------------------
                    */

                    $oldPhoto =
                        $student["photo"]
                        ?? "";


                    /*
                    -------------------------------------------------
                    SAVE PHOTO PATH
                    -------------------------------------------------
                    */

                    $photoPath =
                        "uploads/students/"
                        . $fileName;


                    $photoStmt =
                        $pdo->prepare("
                            UPDATE students
                            SET photo = ?
                            WHERE student_id = ?
                        ");


                    $photoStmt->execute([
                        $photoPath,
                        $studentId
                    ]);


                    /*
                    -------------------------------------------------
                    DELETE OLD PHOTO
                    -------------------------------------------------
                    */

                    if (
                        !empty($oldPhoto)
                    ) {

                        $oldFile =
                            __DIR__
                            . "/../"
                            . ltrim(
                                $oldPhoto,
                                "/\\"
                            );


                        if (
                            is_file(
                                $oldFile
                            )
                        ) {

                            @unlink(
                                $oldFile
                            );
                        }
                    }


                    $success =
                        "Profile photo updated successfully.";


                    /*
                    -------------------------------------------------
                    RELOAD STUDENT
                    -------------------------------------------------
                    */

                    $stmt =
                        $pdo->prepare("
                            SELECT *
                            FROM students
                            WHERE student_id = ?
                            LIMIT 1
                        ");

                    $stmt->execute([
                        $studentId
                    ]);

                    $student =
                        $stmt->fetch(
                            PDO::FETCH_ASSOC
                        );

                } else {

                    $error =
                        "Unable to upload the photo.";
                }
            }
        }
    }
}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["change_password"])
) {

    $currentPassword =
        $_POST["current_password"]
        ?? "";

    $newPassword =
        $_POST["new_password"]
        ?? "";

    $confirmPassword =
        $_POST["confirm_password"]
        ?? "";


    try {

        if (
            $currentPassword === ""
            ||
            $newPassword === ""
            ||
            $confirmPassword === ""
        ) {

            throw new Exception(
                "Please complete all password fields."
            );
        }


        if (
            strlen($newPassword) < 8
        ) {

            throw new Exception(
                "New password must contain at least 8 characters."
            );
        }


        if (
            $newPassword !==
            $confirmPassword
        ) {

            throw new Exception(
                "New passwords do not match."
            );
        }


        /*
        -------------------------------------------------
        GET PASSWORD
        -------------------------------------------------
        */

        $passwordStmt =
            $pdo->prepare("
                SELECT password
                FROM students
                WHERE student_id = ?
                LIMIT 1
            ");

        $passwordStmt->execute([
            $studentId
        ]);

        $account =
            $passwordStmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$account) {

            throw new Exception(
                "Student account was not found."
            );
        }


        if (
            !password_verify(
                $currentPassword,
                $account["password"]
            )
        ) {

            throw new Exception(
                "Current password is incorrect."
            );
        }


        /*
        -------------------------------------------------
        HASH PASSWORD
        -------------------------------------------------
        */

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        /*
        -------------------------------------------------
        UPDATE PASSWORD
        -------------------------------------------------
        */

        $updatePassword =
            $pdo->prepare("
                UPDATE students
                SET password = ?
                WHERE student_id = ?
            ");

        $updatePassword->execute([

            $hashedPassword,
            $studentId

        ]);


        $success =
            "Your password has been changed successfully.";

    } catch (Exception $e) {

        $error =
            $e->getMessage();
    }
}


/* =========================================================
   STATISTICS
========================================================= */

$totalBookings = 0;

$completedLessons = 0;

$upcomingLessons = 0;


try {

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE student_id = ?
    ");

    $stmt->execute([
        $studentId
    ]);

    $totalBookings =
        (int)$stmt->fetchColumn();


    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE student_id = ?
        AND LOWER(lesson_status) = 'completed'
    ");

    $stmt->execute([
        $studentId
    ]);

    $completedLessons =
        (int)$stmt->fetchColumn();


    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE student_id = ?
        AND lesson_date >= CURDATE()
    ");

    $stmt->execute([
        $studentId
    ]);

    $upcomingLessons =
        (int)$stmt->fetchColumn();

} catch (PDOException $e) {

    // Statistics are optional.
}


/* =========================================================
   PHOTO
========================================================= */

$photo =
    trim(
        $student["photo"] ?? ""
    );


if ($photo !== "") {

    $photoUrl =
        "../"
        . ltrim(
            $photo,
            "/\\"
        );

} else {

    $photoUrl =
        "";
}


/* =========================================================
   INITIAL
========================================================= */

$name =
    $student["student_name"]
    ?? "Student";


$initial =
    strtoupper(
        substr(
            trim($name),
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
My Profile |
NISEL ONLINE EDUCATION
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


body {

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #f3f7fb;

    color:
        #1e293b;

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70,
            #002b52
        );

    color: white;

    padding: 25px 14px;

    z-index: 100;
}


.logo {

    text-align: center;

    padding-bottom: 25px;

    margin-bottom: 20px;

    border-bottom:
        1px solid
        rgba(
            255,
            255,
            255,
            .12
        );

}


.logo h2 {

    font-size: 19px;

}


.logo span {

    display: block;

    margin-top: 5px;

    font-size: 9px;

    letter-spacing: 2px;

    opacity: .6;

}


.menu a {

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 13px 14px;

    margin-bottom: 5px;

    color:
        rgba(
            255,
            255,
            255,
            .8
        );

    text-decoration: none;

    border-radius: 9px;

    font-size: 13px;

    transition:
        .2s;

}


.menu a:hover {

    background:
        rgba(
            255,
            255,
            255,
            .1
        );

    color: white;

}


.menu a.active {

    background:
        rgba(
            255,
            255,
            255,
            .16
        );

    color: white;

    font-weight: bold;

    box-shadow:
        inset 3px 0 #38bdf8;

}


/* =========================================================
   MAIN
========================================================= */

.main {

    margin-left: 240px;

    padding: 28px;

}


/* =========================================================
   HEADER
========================================================= */

.header {

    background: white;

    border-radius: 16px;

    padding: 22px 25px;

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 20px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

}


.header h1 {

    color:
        #003b70;

    font-size: 25px;

}


.header p {

    color:
        #718096;

    font-size: 12px;

    margin-top: 5px;

}


.student-badge {

    display: flex;

    align-items: center;

    gap: 10px;

    background:
        #f0f6fb;

    padding:
        8px 13px;

    border-radius:
        25px;

    font-size: 11px;

    color:
        #003b70;

    font-weight: bold;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 13px;

}


.alert.success {

    background:
        #dcfce7;

    color:
        #166534;

    border:
        1px solid #bbf7d0;

}


.alert.error {

    background:
        #fee2e2;

    color:
        #991b1b;

    border:
        1px solid #fecaca;

}


/* =========================================================
   PROFILE HERO
========================================================= */

.profile-hero {

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0074b7
        );

    border-radius: 18px;

    padding: 30px;

    color: white;

    display: flex;

    align-items: center;

    gap: 25px;

    margin-bottom: 20px;

    position: relative;

    overflow: hidden;

}


.profile-hero::after {

    content: "";

    position: absolute;

    width: 220px;

    height: 220px;

    border-radius: 50%;

    right: -70px;

    top: -100px;

    background:
        rgba(
            255,
            255,
            255,
            .08
        );

}


.profile-photo {

    width: 105px;

    height: 105px;

    border-radius: 50%;

    background:
        rgba(
            255,
            255,
            255,
            .16
        );

    border:
        4px solid
        rgba(
            255,
            255,
            255,
            .55
        );

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 40px;

    font-weight: 800;

    overflow: hidden;

    flex-shrink: 0;

}


.profile-photo img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.profile-info {

    position: relative;

    z-index: 2;

}


.profile-info h2 {

    font-size: 25px;

    margin-bottom: 6px;

}


.profile-info p {

    opacity: .82;

    font-size: 12px;

    margin-bottom: 5px;

}


.profile-id {

    display: inline-block;

    margin-top: 8px;

    padding: 6px 10px;

    border-radius: 20px;

    background:
        rgba(
            255,
            255,
            255,
            .13
        );

    font-size: 10px;

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            1fr
        );

    gap: 15px;

    margin-bottom: 20px;

}


.stat {

    background: white;

    padding: 18px;

    border-radius: 14px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

}


.stat-icon {

    font-size: 22px;

    margin-bottom: 8px;

}


.stat-label {

    color:
        #718096;

    font-size: 10px;

    font-weight: 700;

    text-transform:
        uppercase;

}


.stat-number {

    color:
        #003b70;

    font-size: 25px;

    font-weight: 800;

    margin-top: 5px;

}


/* =========================================================
   CONTENT
========================================================= */

.content-grid {

    display: grid;

    grid-template-columns:
        1.2fr
        .8fr;

    gap: 20px;

}


.card {

    background: white;

    border-radius: 16px;

    padding: 23px;

    box-shadow:
        0 5px 20px
        rgba(
            15,
            23,
            42,
            .05
        );

    margin-bottom: 20px;

}


.card-title {

    color:
        #003b70;

    font-size: 17px;

    font-weight: 800;

    margin-bottom: 20px;

    padding-bottom: 14px;

    border-bottom:
        1px solid #edf1f5;

}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 17px;

}


.form-group.full {

    grid-column:
        1 / -1;

}


.form-group label {

    display: block;

    font-size: 12px;

    font-weight: 700;

    color:
        #475569;

    margin-bottom: 7px;

}


.form-group input,
.form-group select {

    width: 100%;

    padding: 11px 12px;

    border:
        1px solid #d5dde7;

    border-radius: 8px;

    font-size: 13px;

    outline: none;

}


.form-group input:focus,
.form-group select:focus {

    border-color:
        #0074b7;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            116,
            183,
            .1
        );

}


.form-group input[readonly] {

    background:
        #f7f9fb;

    color:
        #7b8794;

}


/* =========================================================
   BUTTON
========================================================= */

.save-btn {

    margin-top: 20px;

    border: none;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0074b7
        );

    color: white;

    padding:
        12px 20px;

    border-radius: 8px;

    cursor: pointer;

    font-weight: 700;

    font-size: 12px;

}


.save-btn:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 6px 15px
        rgba(
            0,
            59,
            112,
            .2
        );

}


/* =========================================================
   PHOTO UPLOAD
========================================================= */

.photo-upload {

    text-align:
        center;

}


.photo-preview {

    width: 125px;

    height: 125px;

    border-radius: 50%;

    margin: 0 auto 15px;

    overflow: hidden;

    background:
        #eaf1f7;

    display: flex;

    align-items: center;

    justify-content: center;

    color:
        #003b70;

    font-size: 40px;

    font-weight: bold;

}


.photo-preview img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}


.file-input {

    width: 100%;

    padding: 10px;

    border:
        1px dashed #b7c6d6;

    border-radius: 8px;

    background:
        #f8fafc;

}


/* =========================================================
   INFO LIST
========================================================= */

.info-list {

    list-style: none;

}


.info-list li {

    display: flex;

    justify-content:
        space-between;

    gap: 15px;

    padding: 13px 0;

    border-bottom:
        1px solid #edf1f5;

    font-size: 12px;

}


.info-list li:last-child {

    border-bottom: none;

}


.info-label {

    color:
        #7b8794;

}


.info-value {

    color:
        #334155;

    font-weight: 700;

    text-align: right;

}


.badge {

    display: inline-block;

    padding:
        5px 9px;

    border-radius:
        20px;

    font-size: 9px;

    font-weight: 800;

}


.badge.active {

    background:
        #dcfce7;

    color:
        #166534;

}


/* =========================================================
   PASSWORD
========================================================= */

.password-note {

    background:
        #f0f7ff;

    color:
        #155e8a;

    border:
        1px solid #cce5f7;

    border-radius: 9px;

    padding: 12px;

    font-size: 11px;

    margin-bottom: 18px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:1000px) {

    .content-grid {

        grid-template-columns: 1fr;

    }

}


@media(max-width:800px) {

    .sidebar {

        position:
            relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .header {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap: 12px;

    }


    .profile-hero {

        flex-direction:
            column;

        text-align:
            center;

    }


    .stats {

        grid-template-columns:
            1fr;

    }

}


@media(max-width:550px) {

    .form-grid {

        grid-template-columns:
            1fr;

    }


    .form-group.full {

        grid-column:
            auto;

    }


    .profile-hero {

        padding: 25px 15px;

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

        <h2>
            NISEL
        </h2>

        <span>
            ONLINE EDUCATION
        </span>

    </div>


    <nav class="menu">


        <a href="dashboard.php">

            🏠
            Dashboard

        </a>


        <a href="schedule.php">

            📅
            My Schedule

        </a>


        <a href="book_lesson.php">

            📚
            Book a Lesson

        </a>


        <a href="payments.php">

            💳
            My Payments

        </a>


        <a
            href="profile.php"
            class="active"
        >

            👤
            My Profile

        </a>


        <a href="logout.php">

            🚪
            Logout

        </a>


    </nav>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <header class="header">


        <div>

            <h1>
                👤 My Profile
            </h1>

            <p>
                Manage your NISEL student account.
            </p>

        </div>


        <div class="student-badge">

            🎓
            Student Account

        </div>


    </header>



    <!-- ALERTS -->

    <?php if (
        $success !== ""
    ): ?>

        <div class="alert success">

            ✅
            <?= h($success) ?>

        </div>

    <?php endif; ?>


    <?php if (
        $error !== ""
    ): ?>

        <div class="alert error">

            ⚠️
            <?= h($error) ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         PROFILE HERO
    ================================================== -->

    <section class="profile-hero">


        <div class="profile-photo">


            <?php if (
                $photoUrl !== ""
                &&
                file_exists(
                    __DIR__
                    . "/../"
                    . ltrim(
                        $photo,
                        "/\\"
                    )
                )
            ): ?>


                <img
                    src="<?= h(
                        $photoUrl
                    ) ?>"
                    alt="Student Photo"
                >


            <?php else: ?>


                <?= h(
                    $initial
                ) ?>


            <?php endif; ?>


        </div>


        <div class="profile-info">


            <h2>

                <?= h(
                    $student[
                        "student_name"
                    ]
                ) ?>

            </h2>


            <p>

                📧
                <?= h(
                    $student[
                        "email"
                    ]
                ) ?>

            </p>


            <p>

                🎓
                <?= h(
                    $student[
                        "curriculum"
                    ]
                    ??
                    "Student"
                ) ?>

            </p>


            <span class="profile-id">

                Student ID:
                <?= h(
                    $student[
                        "student_id"
                    ]
                ) ?>

            </span>


        </div>


    </section>



    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="stats">


        <div class="stat">

            <div class="stat-icon">
                📚
            </div>

            <div class="stat-label">
                Total Bookings
            </div>

            <div class="stat-number">
                <?= number_format(
                    $totalBookings
                ) ?>
            </div>

        </div>


        <div class="stat">

            <div class="stat-icon">
                ✅
            </div>

            <div class="stat-label">
                Completed Lessons
            </div>

            <div class="stat-number">
                <?= number_format(
                    $completedLessons
                ) ?>
            </div>

        </div>


        <div class="stat">

            <div class="stat-icon">
                📅
            </div>

            <div class="stat-label">
                Upcoming Lessons
            </div>

            <div class="stat-number">
                <?= number_format(
                    $upcomingLessons
                ) ?>
            </div>

        </div>


    </section>



    <!-- =================================================
         CONTENT
    ================================================== -->

    <div class="content-grid">


        <!-- LEFT -->

        <div>


            <!-- PERSONAL INFORMATION -->

            <section class="card">


                <div class="card-title">

                    ✏️ Personal Information

                </div>


                <form method="POST">


                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="student_name"
                                value="<?php
                                echo h(
                                    $student[
                                        "student_name"
                                    ]
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?php
                                echo h(
                                    $student[
                                        "email"
                                    ]
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                value="<?php
                                echo h(
                                    $student[
                                        "phone"
                                    ]
                                    ?? ""
                                );
                                ?>"
                                placeholder="0240000000"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Date of Birth
                            </label>

                            <input
                                type="date"
                                name="dob"
                                value="<?php
                                echo h(
                                    $student[
                                        "dob"
                                    ]
                                    ?? ""
                                );
                                ?>"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Curriculum
                            </label>

                            <select
                                name="curriculum"
                            >

                                <option value="">
                                    Select Curriculum
                                </option>


                                <option
                                    value="Cambridge"
                                    <?php
                                    echo
                                    (
                                        $student[
                                            "curriculum"
                                        ]
                                        ??
                                        ""
                                    )
                                    ===
                                    "Cambridge"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    Cambridge
                                </option>


                                <option
                                    value="IB"
                                    <?php
                                    echo
                                    (
                                        $student[
                                            "curriculum"
                                        ]
                                        ??
                                        ""
                                    )
                                    ===
                                    "IB"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    IB
                                </option>


                                <option
                                    value="GES"
                                    <?php
                                    echo
                                    (
                                        $student[
                                            "curriculum"
                                        ]
                                        ??
                                        ""
                                    )
                                    ===
                                    "GES"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    GES
                                </option>


                                <option
                                    value="SAT"
                                    <?php
                                    echo
                                    (
                                        $student[
                                            "curriculum"
                                        ]
                                        ??
                                        ""
                                    )
                                    ===
                                    "SAT"
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    SAT
                                </option>


                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Class / Year
                            </label>

                            <input
                                type="text"
                                name="class_year"
                                value="<?php
                                echo h(
                                    $student[
                                        "class_year"
                                    ]
                                    ?? ""
                                );
                                ?>"
                                placeholder="e.g. Year 11"
                            >

                        </div>


                    </div>


                    <button
                        type="submit"
                        name="update_profile"
                        class="save-btn"
                    >

                        💾
                        Save Profile Changes

                    </button>


                </form>


            </section>



            <!-- PASSWORD -->

            <section class="card">


                <div class="card-title">

                    🔐 Change Password

                </div>


                <div class="password-note">

                    For your security, use a password
                    containing at least 8 characters.

                </div>


                <form method="POST">


                    <div class="form-grid">


                        <div
                            class="
                                form-group
                                full
                            "
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


                        <div class="form-group">

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


                        <div class="form-group">

                            <label>
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                minlength="8"
                                required
                            >

                        </div>


                    </div>


                    <button
                        type="submit"
                        name="change_password"
                        class="save-btn"
                    >

                        🔐
                        Change Password

                    </button>


                </form>


            </section>


        </div>



        <!-- RIGHT -->

        <div>


            <!-- PHOTO -->

            <section class="card">


                <div class="card-title">

                    📸 Profile Photo

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                >


                    <div
                        class="photo-upload"
                    >


                        <div
                            class="
                                photo-preview
                            "
                        >


                            <?php if (
                                $photoUrl !== ""
                                &&
                                file_exists(
                                    __DIR__
                                    . "/../"
                                    . ltrim(
                                        $photo,
                                        "/\\"
                                    )
                                )
                            ): ?>


                                <img
                                    src="<?php
                                    echo h(
                                        $photoUrl
                                    );
                                    ?>"
                                    alt="Profile Photo"
                                >


                            <?php else: ?>


                                <?= h(
                                    $initial
                                ) ?>


                            <?php endif; ?>


                        </div>


                        <input
                            type="file"
                            name="profile_photo"
                            class="file-input"
                            accept="
                                image/jpeg,
                                image/png,
                                image/webp
                            "
                            required
                        >


                        <p
                            style="
                                margin-top:10px;
                                color:#8996a6;
                                font-size:10px;
                            "
                        >

                            JPG, PNG or WEBP.
                            Maximum 3MB.

                        </p>


                        <button
                            type="submit"
                            name="upload_photo"
                            class="save-btn"
                        >

                            📷
                            Update Photo

                        </button>


                    </div>


                </form>


            </section>



            <!-- ACCOUNT INFORMATION -->

            <section class="card">


                <div class="card-title">

                    ℹ️ Account Information

                </div>


                <ul class="info-list">


                    <li>

                        <span
                            class="info-label"
                        >
                            Student ID
                        </span>

                        <span
                            class="info-value"
                        >

                            <?= h(
                                $student[
                                    "student_id"
                                ]
                            ) ?>

                        </span>

                    </li>


                    <li>

                        <span
                            class="info-label"
                        >
                            Email
                        </span>

                        <span
                            class="info-value"
                        >

                            <?= h(
                                $student[
                                    "email"
                                ]
                            ) ?>

                        </span>

                    </li>


                    <li>

                        <span
                            class="info-label"
                        >
                            Curriculum
                        </span>

                        <span
                            class="info-value"
                        >

                            <?= h(
                                $student[
                                    "curriculum"
                                ]
                                ??
                                "Not specified"
                            ) ?>

                        </span>

                    </li>


                    <li>

                        <span
                            class="info-label"
                        >
                            Class / Year
                        </span>

                        <span
                            class="info-value"
                        >

                            <?= h(
                                $student[
                                    "class_year"
                                ]
                                ??
                                "Not specified"
                            ) ?>

                        </span>

                    </li>


                    <li>

                        <span
                            class="info-label"
                        >
                            Account Status
                        </span>

                        <span
                            class="
                                badge
                                active
                            "
                        >

                            Active

                        </span>

                    </li>


                </ul>


            </section>


        </div>


    </div>


</main>


</body>

</html>
