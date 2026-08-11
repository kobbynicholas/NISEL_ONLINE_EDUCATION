<?php

session_start();

require "../config/db.php";

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| MODERN STUDENT DASHBOARD
| PDO VERSION
|--------------------------------------------------------------------------
*/


/* =========================================================
   INITIALIZE VARIABLES
========================================================= */

$success = "";
$error   = "";

$student = [];

$total_bookings    = 0;
$paid_bookings     = 0;
$assigned_teachers = 0;

$bookings = [];


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true ||
    !isset($_SESSION['student_id'])
) {

    header("Location: login.php");
    exit;
}


$student_id =
    (int) $_SESSION['student_id'];


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
   LOAD STUDENT
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

        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {

    die(
        "Unable to load student account."
    );
}


/* =========================================================
   STUDENT INFORMATION
========================================================= */

$student_name =
    $student['student_name']
    ??
    $_SESSION['student_name']
    ??
    'Student';


$student_email =
    $student['email']
    ??
    $_SESSION['student_email']
    ??
    '';


$student_phone =
    $student['phone']
    ??
    '';


$student_curriculum =
    $student['curriculum']
    ??
    '';


$student_class =
    $student['class_year']
    ??
    $student['class']
    ??
    '';


$student_subjects =
    $student['subjects']
    ??
    '';


/* =========================================================
   UPDATE SESSION NAME
========================================================= */

$_SESSION['student_name'] =
    $student_name;

$_SESSION['student_email'] =
    $student_email;


/* =========================================================
   CHECK WHETHER PHOTO COLUMN EXISTS
========================================================= */

try {

    $columnStmt =
        $pdo->query(
            "DESCRIBE students"
        );

    $studentColumns = [];

    while (
        $column =
        $columnStmt->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        $studentColumns[] =
            $column['Field'];
    }

} catch (PDOException $e) {

    $studentColumns = [];
}


$hasPhotoColumn =
    in_array(
        'photo',
        $studentColumns,
        true
    );


/* =========================================================
   PHOTO UPLOAD
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'update_photo'
) {

    if (!$hasPhotoColumn) {

        $error =
            "The students table does not contain a photo column.";

    } elseif (
        !isset($_FILES['photo'])
        ||
        $_FILES['photo']['error']
        === UPLOAD_ERR_NO_FILE
    ) {

        $error =
            "Please select a photo.";

    } elseif (
        $_FILES['photo']['error']
        !== UPLOAD_ERR_OK
    ) {

        $error =
            "There was an error uploading your photo.";

    } else {

        try {

            /* ---------------------------------------------
               MAXIMUM FILE SIZE
            --------------------------------------------- */

            $maxSize =
                5 * 1024 * 1024;


            if (
                $_FILES['photo']['size']
                > $maxSize
            ) {

                throw new Exception(
                    "Your photo must not exceed 5MB."
                );
            }


            /* ---------------------------------------------
               CHECK MIME TYPE
            --------------------------------------------- */

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


            /* ---------------------------------------------
               GET EXTENSION
            --------------------------------------------- */

            $extension =
                strtolower(
                    pathinfo(
                        $_FILES['photo']['name'],
                        PATHINFO_EXTENSION
                    )
                );


            /* ---------------------------------------------
               CREATE UPLOAD DIRECTORY
            --------------------------------------------- */

            $uploadDirectory =
                __DIR__
                . "/uploads/students/";


            if (
                !is_dir(
                    $uploadDirectory
                )
            ) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    throw new Exception(
                        "Unable to create the photo upload directory."
                    );
                }
            }


            /* ---------------------------------------------
               CREATE UNIQUE FILE NAME
            --------------------------------------------- */

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


            $newPhotoPath =
                $uploadDirectory
                .
                $newPhotoName;


            /* ---------------------------------------------
               MOVE UPLOADED FILE
            --------------------------------------------- */

            if (
                !move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    $newPhotoPath
                )
            ) {

                throw new Exception(
                    "Unable to save the uploaded photo."
                );
            }


            /* ---------------------------------------------
               DELETE OLD PHOTO
            --------------------------------------------- */

            $oldPhoto =
                $student['photo']
                ??
                '';


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


            /* ---------------------------------------------
               SAVE NEW PHOTO IN DATABASE
            --------------------------------------------- */

            $updatePhoto =
                $pdo->prepare("
                    UPDATE students
                    SET photo = ?
                    WHERE id = ?
                    LIMIT 1
                ");


            $updatePhoto->execute([
                $newPhotoName,
                $student_id
            ]);


            /* ---------------------------------------------
               RELOAD STUDENT
            --------------------------------------------- */

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


            /* ---------------------------------------------
               SUCCESS
            --------------------------------------------- */

            $success =
                "Your profile photo has been updated successfully.";


            /* ---------------------------------------------
               REFRESH VALUES
            --------------------------------------------- */

            $student_name =
                $student['student_name']
                ??
                'Student';

            $student_email =
                $student['email']
                ??
                '';

            $student_phone =
                $student['phone']
                ??
                '';

            $student_curriculum =
                $student['curriculum']
                ??
                '';

            $student_class =
                $student['class_year']
                ??
                ($student['class'] ?? '');

            $student_subjects =
                $student['subjects']
                ??
                '';

        } catch (Exception $e) {

            $error =
                $e->getMessage();
        }
    }
}


/* =========================================================
   STUDENT PHOTO
========================================================= */

$photoUrl = "";


if (
    $hasPhotoColumn
    &&
    !empty(
        $student['photo']
        ??
        ''
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
   STUDENT INITIAL
========================================================= */

$initial =
    strtoupper(
        substr(
            trim(
                $student_name
            ),
            0,
            1
        )
    );


/* =========================================================
   TOTAL BOOKINGS
========================================================= */

try {

    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE email = ?
        ");

    $stmt->execute([
        $student_email
    ]);

    $total_bookings =
        (int)
        $stmt->fetchColumn();

} catch (PDOException $e) {

    $total_bookings = 0;
}


/* =========================================================
   PAID BOOKINGS
========================================================= */

try {

    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE email = ?
            AND (
                LOWER(payment_status) = 'paid'
                OR
                LOWER(payment_status) = 'success'
            )
        ");

    $stmt->execute([
        $student_email
    ]);

    $paid_bookings =
        (int)
        $stmt->fetchColumn();

} catch (PDOException $e) {

    $paid_bookings = 0;
}


/* =========================================================
   ASSIGNED TEACHERS
========================================================= */

try {

    $stmt =
        $pdo->prepare("
            SELECT COUNT(*)
            FROM bookings
            WHERE email = ?
            AND teacher_id IS NOT NULL
            AND teacher_id <> ''
        ");

    $stmt->execute([
        $student_email
    ]);

    $assigned_teachers =
        (int)
        $stmt->fetchColumn();

} catch (PDOException $e) {

    $assigned_teachers = 0;
}


/* =========================================================
   RECENT BOOKINGS
========================================================= */

try {

    $stmt =
        $pdo->prepare("
            SELECT
                id,
                booking_reference,
                subjects,
                curriculum,
                class_year,
                payment_status,
                teacher_name,
                lesson_date,
                lesson_time
            FROM bookings
            WHERE email = ?
            ORDER BY id DESC
            LIMIT 6
        ");

    $stmt->execute([
        $student_email
    ]);

    $bookings =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    $bookings = [];
}


/* =========================================================
   BOOKING PAYMENT BADGE
========================================================= */

function paymentBadge($status)
{

    $status =
        strtolower(
            trim(
                $status ?? ''
            )
        );


    if (
        $status === 'paid'
        ||
        $status === 'success'
    ) {

        return
            '<span class="badge paid">
                ✓ Paid
            </span>';
    }


    return
        '<span class="badge pending">
            Pending
        </span>';
}


/* =========================================================
   FORMAT DATE
========================================================= */

function formatDateValue($date)
{

    if (
        empty($date)
    ) {

        return "Not scheduled";
    }


    $timestamp =
        strtotime($date);


    if (
        $timestamp === false
    ) {

        return h($date);
    }


    return date(
        "d M Y",
        $timestamp
    );
}


/* =========================================================
   FORMAT TIME
========================================================= */

function formatTimeValue($time)
{

    if (
        empty($time)
    ) {

        return "Not set";
    }


    $timestamp =
        strtotime($time);


    if (
        $timestamp === false
    ) {

        return h($time);
    }


    return date(
        "h:i A",
        $timestamp
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
    Student Dashboard | NISEL ONLINE EDUCATION
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
        #f1f5f9;

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

    width: 245px;

    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003b70 0%,
            #00558c 100%
        );

    color: white;

    padding:
        24px 14px;

    z-index: 1000;

    overflow-y: auto;

}


.logo {

    text-align: center;

    padding:
        5px 10px 25px;

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
        .7px;

}


.logo-small {

    font-size:
        10px;

    letter-spacing:
        2px;

    opacity:
        .65;

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
        12px;

    color:
        white;

    text-decoration:
        none;

    padding:
        13px 14px;

    border-radius:
        10px;

    font-size:
        13px;

    transition:
        .2s ease;

}


.menu a:hover {

    background:
        rgba(255,255,255,.10);

}


.menu a.active {

    background:
        rgba(255,255,255,.16);

    box-shadow:
        inset 3px 0 0 #35c4ff;

}


.menu-icon {

    width:
        22px;

    text-align:
        center;

    font-size:
        17px;

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

    min-height:
        100vh;

    padding:
        28px;

}


/* =========================================================
   TOP BAR
========================================================= */

.topbar {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        24px;

}


.topbar-left h1 {

    color:
        #0a3e70;

    font-size:
        25px;

    margin-bottom:
        5px;

}


.topbar-left p {

    color:
        #7a8796;

    font-size:
        13px;

}


.topbar-right {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

}


.small-avatar {

    width:
        43px;

    height:
        43px;

    border-radius:
        50%;

    overflow:
        hidden;

    background:
        #dbeaf5;

    color:
        #003b70;

    border:
        2px solid
        #c4d9e8;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        800;

}


.small-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding:
        13px 16px;

    border-radius:
        10px;

    margin-bottom:
        18px;

    font-size:
        13px;

}


.alert.success {

    background:
        #e9f8ef;

    color:
        #18794e;

    border:
        1px solid
        #b9e7ca;

}


.alert.error {

    background:
        #fff0f0;

    color:
        #b42318;

    border:
        1px solid
        #efb5b2;

}


/* =========================================================
   WELCOME HERO
========================================================= */

.welcome {

    position:
        relative;

    overflow:
        hidden;

    background:
        linear-gradient(
            135deg,
            #003b70,
            #0077b6
        );

    color:
        white;

    border-radius:
        18px;

    padding:
        27px 30px;

    margin-bottom:
        22px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        20px;

    box-shadow:
        0 12px 30px
        rgba(0,59,112,.18);

}


.welcome::after {

    content:
        "";

    position:
        absolute;

    width:
        230px;

    height:
        230px;

    border-radius:
        50%;

    right:
        -75px;

    top:
        -100px;

    background:
        rgba(255,255,255,.06);

}


.welcome-content {

    position:
        relative;

    z-index:
        2;

}


.welcome-content h2 {

    font-size:
        27px;

    margin-bottom:
        7px;

}


.welcome-content p {

    color:
        #dceeff;

    font-size:
        13px;

    line-height:
        1.6;

}


.profile-area {

    position:
        relative;

    z-index:
        3;

    display:
        flex;

    align-items:
        center;

    gap:
        15px;

}


.hero-avatar {

    width:
        86px;

    height:
        86px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.14);

    border:
        3px solid
        rgba(255,255,255,.65);

    overflow:
        hidden;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        32px;

    font-weight:
        800;

}


.hero-avatar img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

}


/* =========================================================
   PHOTO FORM
========================================================= */

.photo-upload {

    position:
        relative;

}


.photo-upload input[type="file"] {

    display:
        none;

}


.change-photo {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    background:
        white;

    color:
        #003b70;

    padding:
        9px 13px;

    border-radius:
        8px;

    font-size:
        11px;

    font-weight:
        700;

    cursor:
        pointer;

    text-decoration:
        none;

}


.change-photo:hover {

    background:
        #edf7ff;

}


.photo-hint {

    color:
        #d9edff;

    font-size:
        10px;

    margin-top:
        6px;

}


/* =========================================================
   STAT CARDS
========================================================= */

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap:
        17px;

    margin-bottom:
        22px;

}


.stat-card {

    background:
        white;

    border-radius:
        14px;

    padding:
        19px;

    box-shadow:
        0 7px 22px
        rgba(0,0,0,.045);

    display:
        flex;

    align-items:
        center;

    gap:
        14px;

}


.stat-icon {

    width:
        50px;

    height:
        50px;

    border-radius:
        12px;

    background:
        #edf6fc;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        23px;

}


.stat-number {

    font-size:
        25px;

    font-weight:
        800;

    color:
        #0a3e70;

}


.stat-label {

    color:
        #7a8796;

    font-size:
        11px;

    margin-top:
        3px;

}


/* =========================================================
   CONTENT GRID
========================================================= */

.content-grid {

    display:
        grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(280px, .75fr);

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
        21px;

    margin-bottom:
        22px;

    box-shadow:
        0 7px 22px
        rgba(0,0,0,.045);

}


.card-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        18px;

}


.card-title {

    color:
        #0a3e70;

    font-size:
        17px;

    font-weight:
        800;

}


.view-all {

    color:
        #0876b9;

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        700;

}


.view-all:hover {

    text-decoration:
        underline;

}


/* =========================================================
   QUICK ACTIONS
========================================================= */

.quick-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:
        12px;

}


.quick-card {

    display:
        flex;

    align-items:
        center;

    gap:
        11px;

    padding:
        14px;

    background:
        #f7fafc;

    border:
        1px solid
        #e7edf3;

    border-radius:
        11px;

    text-decoration:
        none;

    color:
        #334155;

    transition:
        .2s ease;

}


.quick-card:hover {

    transform:
        translateY(-2px);

    border-color:
        #bcd9ea;

    background:
        #f1f8fc;

}


.quick-icon {

    width:
        39px;

    height:
        39px;

    border-radius:
        9px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        #e8f4fb;

    font-size:
        18px;

}


.quick-title {

    font-weight:
        700;

    font-size:
        12px;

}


.quick-description {

    font-size:
        10px;

    color:
        #8995a4;

    margin-top:
        3px;

}


/* =========================================================
   PROFILE SUMMARY
========================================================= */

.profile-row {

    display:
        flex;

    justify-content:
        space-between;

    gap:
        20px;

    padding:
        11px 0;

    border-bottom:
        1px solid
        #edf1f5;

}


.profile-row:last-child {

    border-bottom:
        none;

}


.profile-label {

    color:
        #8995a4;

    font-size:
        11px;

}


.profile-value {

    color:
        #263238;

    font-size:
        12px;

    font-weight:
        700;

    text-align:
        right;

    word-break:
        break-word;

}


/* =========================================================
   BOOKING TABLE
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
        650px;

}


thead th {

    text-align:
        left;

    background:
        #f4f8fb;

    color:
        #526579;

    font-size:
        10px;

    text-transform:
        uppercase;

    letter-spacing:
        .4px;

    padding:
        12px 10px;

}


tbody td {

    padding:
        13px 10px;

    border-bottom:
        1px solid
        #edf1f5;

    color:
        #465667;

    font-size:
        11px;

    vertical-align:
        middle;

}


tbody tr:hover {

    background:
        #fafcff;

}


.subject-name {

    font-weight:
        800;

    color:
        #263f57;

}


.reference {

    color:
        #9aa5b1;

    font-size:
        9px;

    margin-top:
        3px;

}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:
        inline-block;

    padding:
        5px 9px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        800;

}


.badge.paid {

    background:
        #e8f7ee;

    color:
        #18794e;

}


.badge.pending {

    background:
        #fff5d9;

    color:
        #876500;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty {

    padding:
        35px 15px;

    text-align:
        center;

    color:
        #8a96a3;

}


.empty-icon {

    font-size:
        38px;

    margin-bottom:
        8px;

}


.empty p {

    font-size:
        12px;

}


/* =========================================================
   BOOKING PROMOTION
========================================================= */

.booking-promo {

    background:
        linear-gradient(
            135deg,
            #eff8ff,
            #f8fcff
        );

    border:
        1px solid
        #d8ebf7;

    border-radius:
        13px;

    padding:
        18px;

}


.booking-promo h3 {

    color:
        #003b70;

    font-size:
        15px;

    margin-bottom:
        7px;

}


.booking-promo p {

    color:
        #718096;

    font-size:
        11px;

    line-height:
        1.6;

    margin-bottom:
        12px;

}


.book-button {

    display:
        inline-flex;

    padding:
        10px 14px;

    background:
        #003b70;

    color:
        white;

    border-radius:
        8px;

    text-decoration:
        none;

    font-size:
        11px;

    font-weight:
        700;

}


.book-button:hover {

    background:
        #00599b;

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
        10px 0 20px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 1050px) {

    .content-grid {

        grid-template-columns:
            1fr;

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

    }


    .main {

        margin-left:
            0;

        padding:
            17px;

    }


    .stats {

        grid-template-columns:
            1fr;

    }


    .welcome {

        flex-direction:
            column;

        align-items:
            flex-start;

    }

}


@media(max-width: 600px) {

    .topbar {

        align-items:
            flex-start;

    }


    .topbar-right {

        display:
            none;

    }


    .welcome {

        padding:
            22px;

    }


    .welcome-content h2 {

        font-size:
            22px;

    }


    .profile-area {

        width:
            100%;

    }


    .quick-grid {

        grid-template-columns:
            1fr;

    }


    .menu {

        display:
            grid;

        grid-template-columns:
            repeat(2, 1fr);

    }


    .menu a {

        font-size:
            11px;

    }

}


@media(max-width: 400px) {

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


        <a
            href="dashboard.php"
            class="active"
        >

            <span class="menu-icon">
                🏠
            </span>

            Dashboard

        </a>


        <a href="profile.php">

            <span class="menu-icon">
                👤
            </span>

            My Profile

        </a>


        <a href="book_lesson.php">

            <span class="menu-icon">
                📚
            </span>

            Book a Lesson

        </a>


        <a href="bookings.php">

            <span class="menu-icon">
                📋
            </span>

            My Bookings

        </a>


        <a href="schedule.php">

            <span class="menu-icon">
                📅
            </span>

            My Schedule

        </a>


        <a href="payments.php">

            <span class="menu-icon">
                💳
            </span>

            My Payments

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


    <!-- TOP BAR -->

    <div class="topbar">


        <div class="topbar-left">

            <h1>
                Student Dashboard
            </h1>

            <p>
                Manage your NISEL learning activities.
            </p>

        </div>


        <div class="topbar-right">

            <div class="small-avatar">

                <?php if ($photoUrl !== ''): ?>

                    <img
                        src="<?= h($photoUrl) ?>"
                        alt="Student"
                    >

                <?php else: ?>

                    <?= h($initial) ?>

                <?php endif; ?>

            </div>


            <div>

                <strong
                    style="
                        display:block;
                        color:#30475e;
                        font-size:12px;
                    "
                >

                    <?= h($student_name) ?>

                </strong>


                <span
                    style="
                        display:block;
                        color:#8b97a4;
                        font-size:10px;
                        margin-top:3px;
                    "
                >

                    Student

                </span>

            </div>

        </div>

    </div>



    <!-- =====================================================
         ALERTS
    ====================================================== -->

    <?php if ($success !== ''): ?>

        <div class="alert success">

            ✅
            <?= h($success) ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert error">

            ⚠️
            <?= h($error) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         WELCOME HERO
    ====================================================== -->

    <section class="welcome">


        <div class="welcome-content">

            <h2>

                Welcome,
                <?= h($student_name) ?>! 👋

            </h2>


            <p>

                Welcome back to
                <strong>
                    NISEL ONLINE EDUCATION
                </strong>.
                Your learning journey starts here.

            </p>

        </div>



        <div class="profile-area">


            <div class="hero-avatar">

                <?php if ($photoUrl !== ''): ?>

                    <img
                        src="<?= h($photoUrl) ?>"
                        alt="Student Photo"
                    >

                <?php else: ?>

                    <?= h($initial) ?>

                <?php endif; ?>

            </div>


            <?php if ($hasPhotoColumn): ?>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                    class="photo-upload"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="update_photo"
                    >


                    <input
                        type="file"
                        id="dashboardPhoto"
                        name="photo"
                        accept="
                            image/jpeg,
                            image/png,
                            image/webp
                        "
                        onchange="
                            this.form.submit()
                        "
                    >


                    <label
                        for="dashboardPhoto"
                        class="change-photo"
                    >

                        📷
                        Change Photo

                    </label>


                    <div class="photo-hint">

                        JPG, PNG or WEBP · Max 5MB

                    </div>

                </form>

            <?php endif; ?>


        </div>


    </section>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="stats">


        <div class="stat-card">

            <div class="stat-icon">
                📚
            </div>


            <div>

                <div class="stat-number">
                    <?= $total_bookings ?>
                </div>

                <div class="stat-label">
                    Total Bookings
                </div>

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                💳
            </div>


            <div>

                <div class="stat-number">
                    <?= $paid_bookings ?>
                </div>

                <div class="stat-label">
                    Paid Bookings
                </div>

            </div>

        </div>



        <div class="stat-card">

            <div class="stat-icon">
                👨‍🏫
            </div>


            <div>

                <div class="stat-number">
                    <?= $assigned_teachers ?>
                </div>

                <div class="stat-label">
                    Assigned Teachers
                </div>

            </div>

        </div>


    </section>



    <!-- =====================================================
         CONTENT GRID
    ====================================================== -->

    <div class="content-grid">


        <!-- =================================================
             LEFT COLUMN
        ================================================= -->

        <div>


            <!-- RECENT BOOKINGS -->

            <section class="card">


                <div class="card-header">

                    <div class="card-title">

                        📚 Recent Bookings

                    </div>


                    <a
                        href="bookings.php"
                        class="view-all"
                    >

                        View All →

                    </a>

                </div>



                <?php if (!empty($bookings)): ?>


                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Subject
                                    </th>

                                    <th>
                                        Curriculum
                                    </th>

                                    <th>
                                        Class
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Teacher
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $bookings
                                as $booking
                            ): ?>


                                <tr>


                                    <td>

                                        <div
                                            class="subject-name"
                                        >

                                            <?= h(
                                                $booking['subjects']
                                                ?? ''
                                            ) ?>

                                        </div>


                                        <div
                                            class="reference"
                                        >

                                            <?= h(
                                                $booking['booking_reference']
                                                ?? ''
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= h(
                                            $booking['curriculum']
                                            ?? ''
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= h(
                                            $booking['class_year']
                                            ?? ''
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= paymentBadge(
                                            $booking['payment_status']
                                            ?? ''
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php

                                        $teacher =
                                            trim(
                                                $booking['teacher_name']
                                                ?? ''
                                            );

                                        ?>


                                        <?php if (
                                            $teacher !== ''
                                        ): ?>

                                            <strong>

                                                <?= h(
                                                    $teacher
                                                ) ?>

                                            </strong>

                                        <?php else: ?>

                                            <span
                                                style="
                                                    color:#9aa5b1;
                                                "
                                            >

                                                Not assigned

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty">

                        <div class="empty-icon">
                            📚
                        </div>

                        <p>
                            You have no bookings yet.
                        </p>

                    </div>


                <?php endif; ?>


            </section>



            <!-- QUICK ACTIONS -->

            <section class="card">


                <div class="card-header">

                    <div class="card-title">

                        ⚡ Quick Actions

                    </div>

                </div>


                <div class="quick-grid">


                    <a
                        href="book_lesson.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            📚
                        </div>


                        <div>

                            <div class="quick-title">
                                Book a Lesson
                            </div>

                            <div
                                class="quick-description"
                            >
                                Choose a subject

                            </div>

                        </div>

                    </a>



                    <a
                        href="schedule.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            📅
                        </div>


                        <div>

                            <div class="quick-title">
                                My Schedule
                            </div>

                            <div
                                class="quick-description"
                            >
                                View your classes

                            </div>

                        </div>

                    </a>



                    <a
                        href="payments.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            💳
                        </div>


                        <div>

                            <div class="quick-title">
                                Payments
                            </div>

                            <div
                                class="quick-description"
                            >
                                View payment history

                            </div>

                        </div>

                    </a>



                    <a
                        href="profile.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            👤
                        </div>


                        <div>

                            <div class="quick-title">
                                My Profile
                            </div>

                            <div
                                class="quick-description"
                            >
                                Update your details

                            </div>

                        </div>

                    </a>


                </div>


            </section>


        </div>



        <!-- =================================================
             RIGHT COLUMN
        ================================================= -->

        <div>


            <!-- PROFILE SUMMARY -->

            <section class="card">


                <div class="card-header">

                    <div class="card-title">

                        👤 My Profile

                    </div>


                    <a
                        href="profile.php"
                        class="view-all"
                    >

                        Edit

                    </a>

                </div>


                <div class="profile-row">

                    <div class="profile-label">
                        Full Name
                    </div>

                    <div class="profile-value">
                        <?= h(
                            $student_name
                        ) ?>
                    </div>

                </div>


                <div class="profile-row">

                    <div class="profile-label">
                        Email
                    </div>

                    <div class="profile-value">
                        <?= h(
                            $student_email
                        ) ?>
                    </div>

                </div>


                <div class="profile-row">

                    <div class="profile-label">
                        Phone
                    </div>

                    <div class="profile-value">

                        <?= h(
                            $student_phone
                            ?: 'Not provided'
                        ) ?>

                    </div>

                </div>


                <div class="profile-row">

                    <div class="profile-label">
                        Curriculum
                    </div>

                    <div class="profile-value">

                        <?= h(
                            $student_curriculum
                            ?: 'Not provided'
                        ) ?>

                    </div>

                </div>


                <div class="profile-row">

                    <div class="profile-label">
                        Class / Year
                    </div>

                    <div class="profile-value">

                        <?= h(
                            $student_class
                            ?: 'Not provided'
                        ) ?>

                    </div>

                </div>


            </section>



            <!-- BOOK A SUBJECT -->

            <section class="card">


                <div class="booking-promo">

                    <h3>

                        📚 Book a New Subject

                    </h3>


                    <p>

                        Choose your preferred subject
                        and create your monthly lesson
                        package.

                    </p>


                    <p
                        style="
                            margin-bottom:12px;
                            color:#42627b;
                        "
                    >

                        <strong>
                            2 lessons per week
                        </strong>
                        ·
                        <strong>
                            8 lessons per month
                        </strong>
                        ·
                        <strong>
                            GHS 1,000
                        </strong>

                    </p>


                    <a
                        href="book_lesson.php"
                        class="book-button"
                    >

                        ➕ Book a Subject

                    </a>

                </div>


            </section>



            <!-- SUBJECTS -->

            <section class="card">


                <div class="card-header">

                    <div class="card-title">

                        🎓 My Subjects

                    </div>

                </div>


                <?php if (
                    trim(
                        $student_subjects
                    ) !== ''
                ): ?>


                    <div
                        style="
                            background:#f5f9fc;
                            border-left:
                                4px solid #0876b9;
                            padding:14px;
                            border-radius:8px;
                            color:#526579;
                            font-size:12px;
                            line-height:1.7;
                        "
                    >

                        <?= nl2br(
                            h(
                                $student_subjects
                            )
                        ) ?>

                    </div>


                <?php else: ?>


                    <div class="empty">

                        <div
                            class="empty-icon"
                            style="
                                font-size:30px;
                            "
                        >
                            🎓
                        </div>

                        <p>
                            No subjects registered yet.
                        </p>

                    </div>


                <?php endif; ?>


            </section>


        </div>


    </div>



    <!-- FOOTER -->

    <div class="footer">

        © <?= date('Y') ?>
        NISEL ONLINE EDUCATION.
        Student Portal.

    </div>


</main>



</body>

</html>
