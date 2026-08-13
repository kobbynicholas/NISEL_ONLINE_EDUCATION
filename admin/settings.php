<?php
session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN SETTINGS
| PDO VERSION
|--------------------------------------------------------------------------
*/

require "../config/db.php";

/* =========================================================
   ADMIN SECURITY
========================================================= */

if (
    !isset($_SESSION['admin_id']) ||
    empty($_SESSION['admin_id'])
) {
    header("Location: ../admin_login.php");
    exit;
}

$adminId = (int) $_SESSION['admin_id'];

$adminName =
    $_SESSION['admin_name'] ?? "Administrator";

$adminEmail =
    $_SESSION['admin_email'] ?? "";


/* =========================================================
   CREATE SETTINGS TABLE IF IT DOES NOT EXIST
========================================================= */

try {

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
    ");

} catch (PDOException $e) {

    die(
        "Unable to initialize settings: "
        . htmlspecialchars($e->getMessage())
    );
}


/* =========================================================
   DEFAULT SETTINGS
========================================================= */

$defaults = [

    /* GENERAL */

    'site_name' =>
        'NISEL ONLINE EDUCATION',

    'site_tagline' =>
        'Excellence Through Online Learning',

    'site_phone' =>
        '',

    'site_email' =>
        '',

    'site_address' =>
        '',

    'site_website' =>
        '',


    /* PAYMENT */

    'currency' =>
        'GHS',

    'cambridge_price' =>
        '1000',

    'ib_price' =>
        '1200',

    'ges_price' =>
        '800',

    'sat_price' =>
        '850',

    'paystack_public_key' =>
        '',

    'paystack_secret_key' =>
        '',

    'payment_gateway' =>
        'Paystack',

    'payment_enabled' =>
        '1',


    /* ACADEMIC */

    'lessons_per_week' =>
        '2',

    'lessons_per_month' =>
        '8',

    'default_lesson_duration' =>
        '90',


    /* WEBSITE */

    'maintenance_mode' =>
        '0',

    'student_registration' =>
        '1',

    'teacher_applications' =>
        '1',

    'website_status' =>
        '1',


    /* EMAIL */

    'notification_email' =>
        '',

    'booking_notifications' =>
        '1',

    'payment_notifications' =>
        '1',

    'teacher_notifications' =>
        '1',


    /* APPEARANCE */

    'primary_color' =>
        '#003366',

    'secondary_color' =>
        '#0074B7'
];


/* =========================================================
   INSERT DEFAULT SETTINGS
========================================================= */

try {

    $insertSetting = $pdo->prepare("
        INSERT IGNORE INTO site_settings
        (setting_key, setting_value)
        VALUES (?, ?)
    ");

    foreach ($defaults as $key => $value) {

        $insertSetting->execute([
            $key,
            $value
        ]);
    }

} catch (PDOException $e) {

    $error =
        "Unable to initialize default settings.";
}


/* =========================================================
   LOAD SETTINGS
========================================================= */

$settings = $defaults;

try {

    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM site_settings
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $settings[
            $row['setting_key']
        ] = $row['setting_value'];
    }

} catch (PDOException $e) {

    $error =
        "Unable to load settings.";
}


/* =========================================================
   MESSAGE VARIABLES
========================================================= */

$success = "";
$error = $error ?? "";


/* =========================================================
   SAVE GENERAL SETTINGS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_general'])
) {

    try {

        $data = [

            'site_name' =>
                trim($_POST['site_name'] ?? ''),

            'site_tagline' =>
                trim($_POST['site_tagline'] ?? ''),

            'site_phone' =>
                trim($_POST['site_phone'] ?? ''),

            'site_email' =>
                trim($_POST['site_email'] ?? ''),

            'site_address' =>
                trim($_POST['site_address'] ?? ''),

            'site_website' =>
                trim($_POST['site_website'] ?? '')
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "General settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save general settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   SAVE PAYMENT SETTINGS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_payment'])
) {

    try {

        $data = [

            'currency' =>
                trim($_POST['currency'] ?? 'GHS'),

            'cambridge_price' =>
                trim($_POST['cambridge_price'] ?? '1000'),

            'ib_price' =>
                trim($_POST['ib_price'] ?? '1200'),

            'ges_price' =>
                trim($_POST['ges_price'] ?? '800'),

            'sat_price' =>
                trim($_POST['sat_price'] ?? '850'),

            'paystack_public_key' =>
                trim($_POST['paystack_public_key'] ?? ''),

            'paystack_secret_key' =>
                trim($_POST['paystack_secret_key'] ?? ''),

            'payment_gateway' =>
                trim($_POST['payment_gateway'] ?? 'Paystack'),

            'payment_enabled' =>
                isset($_POST['payment_enabled'])
                    ? '1'
                    : '0'
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "Payment settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save payment settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   SAVE ACADEMIC SETTINGS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_academic'])
) {

    try {

        $data = [

            'lessons_per_week' =>
                trim($_POST['lessons_per_week'] ?? '2'),

            'lessons_per_month' =>
                trim($_POST['lessons_per_month'] ?? '8'),

            'default_lesson_duration' =>
                trim(
                    $_POST['default_lesson_duration']
                    ?? '90'
                )
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "Academic settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save academic settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   SAVE WEBSITE SETTINGS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_website'])
) {

    try {

        $data = [

            'maintenance_mode' =>
                isset($_POST['maintenance_mode'])
                    ? '1'
                    : '0',

            'student_registration' =>
                isset($_POST['student_registration'])
                    ? '1'
                    : '0',

            'teacher_applications' =>
                isset($_POST['teacher_applications'])
                    ? '1'
                    : '0',

            'website_status' =>
                isset($_POST['website_status'])
                    ? '1'
                    : '0'
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "Website settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save website settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   SAVE EMAIL SETTINGS
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_email'])
) {

    try {

        $data = [

            'notification_email' =>
                trim(
                    $_POST['notification_email']
                    ?? ''
                ),

            'booking_notifications' =>
                isset($_POST['booking_notifications'])
                    ? '1'
                    : '0',

            'payment_notifications' =>
                isset($_POST['payment_notifications'])
                    ? '1'
                    : '0',

            'teacher_notifications' =>
                isset($_POST['teacher_notifications'])
                    ? '1'
                    : '0'
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "Email settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save email settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   SAVE APPEARANCE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['save_appearance'])
) {

    try {

        $data = [

            'primary_color' =>
                trim(
                    $_POST['primary_color']
                    ?? '#003366'
                ),

            'secondary_color' =>
                trim(
                    $_POST['secondary_color']
                    ?? '#0074B7'
                )
        ];


        $stmt = $pdo->prepare("
            INSERT INTO site_settings
            (setting_key, setting_value)
            VALUES (?, ?)

            ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value)
        ");


        foreach ($data as $key => $value) {

            $stmt->execute([
                $key,
                $value
            ]);
        }


        $success =
            "Appearance settings saved successfully.";

    } catch (PDOException $e) {

        $error =
            "Unable to save appearance settings: "
            . $e->getMessage();
    }
}


/* =========================================================
   CHANGE ADMIN PASSWORD
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['change_password'])
) {

    try {

        $currentPassword =
            $_POST['current_password'] ?? '';

        $newPassword =
            $_POST['new_password'] ?? '';

        $confirmPassword =
            $_POST['confirm_password'] ?? '';


        if (
            empty($currentPassword)
            ||
            empty($newPassword)
            ||
            empty($confirmPassword)
        ) {

            throw new Exception(
                "Please complete all password fields."
            );
        }


        if ($newPassword !== $confirmPassword) {

            throw new Exception(
                "New passwords do not match."
            );
        }


        if (strlen($newPassword) < 8) {

            throw new Exception(
                "Password must contain at least 8 characters."
            );
        }


        $stmt = $pdo->prepare("
            SELECT password
            FROM admins
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $adminId
        ]);

        $admin = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$admin) {

            throw new Exception(
                "Administrator account was not found."
            );
        }


        if (
            !password_verify(
                $currentPassword,
                $admin['password']
            )
        ) {

            throw new Exception(
                "Current password is incorrect."
            );
        }


        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $update = $pdo->prepare("
            UPDATE admins
            SET password = ?
            WHERE id = ?
        ");

        $update->execute([
            $hashedPassword,
            $adminId
        ]);


        $success =
            "Administrator password changed successfully.";

    } catch (Exception $e) {

        $error =
            $e->getMessage();
    }
}


/* =========================================================
   RELOAD SETTINGS AFTER SAVE
========================================================= */

try {

    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM site_settings
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $settings[
            $row['setting_key']
        ] = $row['setting_value'];
    }

} catch (PDOException $e) {
    // Keep current values.
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
NISEL Admin Settings
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
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f7fb;

    color: #172b4d;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 250px;
    height: 100vh;

    background:
        linear-gradient(
            180deg,
            #003366,
            #00264d
        );

    color: white;

    padding: 25px 15px;

    z-index: 1000;
}


.logo {

    text-align: center;

    padding: 10px;

    margin-bottom: 30px;

    font-weight: 800;

    letter-spacing: 1px;

    font-size: 18px;
}


.logo span {

    display: block;

    font-size: 11px;

    opacity: .7;

    margin-top: 5px;

    letter-spacing: 2px;
}


.menu a {

    display: flex;

    align-items: center;

    gap: 12px;

    color: #dcecff;

    text-decoration: none;

    padding: 13px 15px;

    margin-bottom: 6px;

    border-radius: 10px;

    font-size: 14px;

    transition: .25s;
}


.menu a:hover,
.menu a.active {

    background:
        rgba(255,255,255,.13);

    color: white;

    transform: translateX(3px);
}


.menu .logout {

    margin-top: 25px;

    color: #ffcccc;
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

    border-radius: 16px;

    padding: 20px 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    box-shadow:
        0 5px 25px rgba(0,0,0,.06);

    margin-bottom: 25px;
}


.topbar h1 {

    font-size: 25px;

    color: #003366;
}


.topbar p {

    margin-top: 5px;

    color: #7b8794;

    font-size: 13px;
}


.admin-badge {

    display: flex;

    align-items: center;

    gap: 10px;

    background: #f0f5fa;

    padding: 10px 15px;

    border-radius: 30px;

    font-size: 13px;
}


.admin-icon {

    width: 35px;
    height: 35px;

    border-radius: 50%;

    background: #003366;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;
}


/* =========================================================
   ALERTS
========================================================= */

.alert {

    padding: 14px 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    font-size: 14px;
}


.success {

    background: #eaf8ef;

    color: #18794e;

    border: 1px solid #b8e5ca;
}


.error {

    background: #fff0f0;

    color: #b42318;

    border: 1px solid #f0b9b9;
}


/* =========================================================
   SETTINGS LAYOUT
========================================================= */

.settings-layout {

    display: grid;

    grid-template-columns: 220px 1fr;

    gap: 22px;

    align-items: start;
}


/* =========================================================
   SETTINGS NAVIGATION
========================================================= */

.settings-nav {

    background: white;

    border-radius: 16px;

    padding: 12px;

    box-shadow:
        0 5px 25px rgba(0,0,0,.05);

    position: sticky;

    top: 20px;
}


.settings-nav button {

    width: 100%;

    border: 0;

    background: transparent;

    padding: 13px 14px;

    text-align: left;

    border-radius: 9px;

    cursor: pointer;

    color: #52606d;

    font-size: 13px;

    margin-bottom: 4px;

    transition: .2s;
}


.settings-nav button:hover {

    background: #f0f5fa;

    color: #003366;
}


.settings-nav button.active {

    background: #003366;

    color: white;

    font-weight: 600;
}


/* =========================================================
   PANELS
========================================================= */

.panel {

    display: none;

    background: white;

    border-radius: 16px;

    padding: 28px;

    box-shadow:
        0 5px 25px rgba(0,0,0,.05);
}


.panel.active {

    display: block;
}


.panel-header {

    margin-bottom: 25px;

    padding-bottom: 18px;

    border-bottom: 1px solid #edf1f5;
}


.panel-header h2 {

    font-size: 20px;

    color: #003366;
}


.panel-header p {

    color: #7b8794;

    font-size: 13px;

    margin-top: 6px;
}


/* =========================================================
   FORM
========================================================= */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px;
}


.form-group {

    margin-bottom: 5px;
}


.form-group.full {

    grid-column: 1 / -1;
}


.form-group label {

    display: block;

    font-size: 13px;

    font-weight: 600;

    margin-bottom: 7px;

    color: #344563;
}


.form-group input,
.form-group textarea,
.form-group select {

    width: 100%;

    padding: 12px 13px;

    border: 1px solid #d9e2ec;

    border-radius: 9px;

    outline: none;

    font-size: 14px;

    background: #fff;

    transition: .2s;
}


.form-group textarea {

    min-height: 100px;

    resize: vertical;
}


.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {

    border-color: #0074b7;

    box-shadow:
        0 0 0 3px rgba(0,116,183,.10);
}


.help {

    color: #8996a6;

    font-size: 11px;

    margin-top: 5px;
}


/* =========================================================
   SAVE BUTTON
========================================================= */

.save-area {

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #edf1f5;

    display: flex;

    justify-content: flex-end;
}


.save-button {

    border: 0;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0074b7
        );

    color: white;

    padding: 12px 23px;

    border-radius: 9px;

    font-weight: 600;

    cursor: pointer;

    transition: .2s;
}


.save-button:hover {

    transform: translateY(-2px);

    box-shadow:
        0 7px 18px
        rgba(0,51,102,.2);
}


/* =========================================================
   TOGGLE
========================================================= */

.toggle-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 16px 0;

    border-bottom: 1px solid #edf1f5;
}


.toggle-info strong {

    display: block;

    font-size: 14px;

    color: #344563;
}


.toggle-info span {

    display: block;

    font-size: 12px;

    color: #8996a6;

    margin-top: 4px;
}


.switch {

    position: relative;

    width: 48px;

    height: 25px;
}


.switch input {

    display: none;
}


.slider {

    position: absolute;

    inset: 0;

    background: #ccd6e0;

    border-radius: 30px;

    cursor: pointer;

    transition: .3s;
}


.slider:before {

    content: "";

    position: absolute;

    width: 19px;

    height: 19px;

    left: 3px;

    top: 3px;

    background: white;

    border-radius: 50%;

    transition: .3s;
}


.switch input:checked + .slider {

    background: #0074b7;
}


.switch input:checked + .slider:before {

    transform:
        translateX(23px);
}


/* =========================================================
   PRICE CARDS
========================================================= */

.price-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.price-card {

    background: #f7faff;

    border: 1px solid #e3ebf3;

    border-radius: 12px;

    padding: 18px;
}


.price-card span {

    display: block;

    font-size: 12px;

    color: #718096;

    margin-bottom: 8px;
}


.price-card strong {

    font-size: 13px;

    color: #003366;
}


/* =========================================================
   COLOR INPUT
========================================================= */

.color-row {

    display: flex;

    align-items: center;

    gap: 12px;
}


.color-row input[type="color"] {

    width: 55px;

    height: 42px;

    padding: 3px;

    cursor: pointer;
}


/* =========================================================
   SYSTEM STATUS
========================================================= */

.status-box {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 25px;
}


.status-card {

    padding: 18px;

    border-radius: 12px;

    background: #f7faff;

    border: 1px solid #e5edf5;
}


.status-card .status-icon {

    font-size: 22px;

    margin-bottom: 10px;
}


.status-card strong {

    display: block;

    font-size: 14px;
}


.status-online {

    color: #18864b;
}


/* =========================================================
   DANGER
========================================================= */

.danger-box {

    margin-top: 25px;

    padding: 18px;

    border-radius: 10px;

    background: #fff6f6;

    border: 1px solid #f2c7c7;

    color: #8a1c1c;
}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 1000px) {

    .settings-layout {

        grid-template-columns: 1fr;
    }


    .settings-nav {

        position: relative;

        top: 0;

        display: flex;

        overflow-x: auto;

        gap: 5px;
    }


    .settings-nav button {

        min-width: 140px;
    }


    .price-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }


    .status-box {

        grid-template-columns:
            1fr;
    }
}


@media(max-width: 800px) {

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

        gap: 15px;
    }


    .form-grid {

        grid-template-columns: 1fr;
    }


    .form-group.full {

        grid-column: auto;
    }
}


@media(max-width: 500px) {

    .price-grid {

        grid-template-columns: 1fr;
    }


    .panel {

        padding: 20px;
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
===================================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <div>

            <h1>
                ⚙️ Admin Settings
            </h1>

            <p>
                Manage NISEL ONLINE EDUCATION system configuration.
            </p>

        </div>


        <div class="admin-badge">

            <div class="admin-icon">
                👤
            </div>

            <div>

                <strong>
                    <?php
                    echo htmlspecialchars(
                        $adminName
                    );
                    ?>
                </strong>

                <div>
                    Administrator
                </div>

            </div>

        </div>

    </div>



    <!-- ALERTS -->

    <?php if ($success !== ""): ?>

        <div class="alert success">

            ✅
            <?php
            echo htmlspecialchars(
                $success
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="alert error">

            ⚠️
            <?php
            echo htmlspecialchars(
                $error
            );
            ?>

        </div>

    <?php endif; ?>



    <!-- SETTINGS -->

    <div class="settings-layout">


        <!-- NAVIGATION -->

        <div class="settings-nav">

            <button
                class="tab-button active"
                onclick="openTab(event,'general')"
            >
                🏫 General
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'payments')"
            >
                💳 Payments
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'academic')"
            >
                📚 Academic
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'website')"
            >
                🌐 Website
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'email')"
            >
                📧 Notifications
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'appearance')"
            >
                🎨 Appearance
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'security')"
            >
                🔐 Security
            </button>


            <button
                class="tab-button"
                onclick="openTab(event,'system')"
            >
                🖥️ System
            </button>

        </div>



        <!-- =================================================
             GENERAL
        ================================================= -->

        <div
            id="general"
            class="panel active"
        >

            <div class="panel-header">

                <h2>
                    Institution Information
                </h2>

                <p>
                    Manage the basic information displayed throughout NISEL.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Institution Name
                        </label>

                        <input
                            type="text"
                            name="site_name"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['site_name']
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Tagline
                        </label>

                        <input
                            type="text"
                            name="site_tagline"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['site_tagline']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="text"
                            name="site_phone"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['site_phone']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Official Email
                        </label>

                        <input
                            type="email"
                            name="site_email"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['site_email']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="site_address"
                        ><?php
                        echo htmlspecialchars(
                            $settings['site_address']
                        );
                        ?></textarea>

                    </div>


                    <div class="form-group full">

                        <label>
                            Website
                        </label>

                        <input
                            type="text"
                            name="site_website"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['site_website']
                            );
                            ?>"
                            placeholder="https://..."
                        >

                    </div>


                </div>


                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_general"
                    >
                        💾 Save General Settings
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             PAYMENTS
        ================================================= -->

        <div
            id="payments"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Payment Configuration
                </h2>

                <p>
                    Manage curriculum prices and Paystack settings.
                </p>

            </div>


            <div class="price-grid">


                <div class="price-card">

                    <span>
                        Cambridge
                    </span>

                    <strong>
                        GHS
                        <?php
                        echo number_format(
                            (float)
                            $settings['cambridge_price'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="price-card">

                    <span>
                        IB
                    </span>

                    <strong>
                        GHS
                        <?php
                        echo number_format(
                            (float)
                            $settings['ib_price'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="price-card">

                    <span>
                        GES
                    </span>

                    <strong>
                        GHS
                        <?php
                        echo number_format(
                            (float)
                            $settings['ges_price'],
                            2
                        );
                        ?>
                    </strong>

                </div>


                <div class="price-card">

                    <span>
                        SAT
                    </span>

                    <strong>
                        GHS
                        <?php
                        echo number_format(
                            (float)
                            $settings['sat_price'],
                            2
                        );
                        ?>
                    </strong>

                </div>


            </div>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Currency
                        </label>

                        <select name="currency">

                            <option
                                value="GHS"
                                <?php
                                echo
                                $settings['currency']
                                === 'GHS'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                GHS - Ghana Cedis
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Payment Gateway
                        </label>

                        <select name="payment_gateway">

                            <option
                                value="Paystack"
                            >
                                Paystack
                            </option>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>
                            Cambridge Price
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="cambridge_price"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['cambridge_price']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            IB Price
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="ib_price"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['ib_price']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            GES Price
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="ges_price"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['ges_price']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            SAT Price
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="sat_price"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['sat_price']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Paystack Public Key
                        </label>

                        <input
                            type="text"
                            name="paystack_public_key"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['paystack_public_key']
                            );
                            ?>"
                            placeholder="pk_test_..."
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Paystack Secret Key
                        </label>

                        <input
                            type="password"
                            name="paystack_secret_key"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['paystack_secret_key']
                            );
                            ?>"
                            placeholder="sk_test_..."
                        >

                        <div class="help">
                            Keep your secret key private.
                        </div>

                    </div>


                </div>


                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Enable Payments
                        </strong>

                        <span>
                            Allow students to make online payments.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="payment_enabled"
                            <?php
                            echo
                            $settings['payment_enabled']
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_payment"
                    >
                        💾 Save Payment Settings
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             ACADEMIC
        ================================================= -->

        <div
            id="academic"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Academic Settings
                </h2>

                <p>
                    Configure NISEL lesson packages and scheduling.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Lessons Per Week
                        </label>

                        <input
                            type="number"
                            min="1"
                            name="lessons_per_week"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['lessons_per_week']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Lessons Per Month
                        </label>

                        <input
                            type="number"
                            min="1"
                            name="lessons_per_month"
                            value="<?php
                            echo htmlspecialchars(
                                $settings['lessons_per_month']
                            );
                            ?>"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Lesson Duration
                        </label>

                        <select
                            name="default_lesson_duration"
                        >

                            <option
                                value="60"
                                <?php
                                echo
                                $settings[
                                    'default_lesson_duration'
                                ] == '60'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                60 Minutes
                            </option>

                            <option
                                value="90"
                                <?php
                                echo
                                $settings[
                                    'default_lesson_duration'
                                ] == '90'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                90 Minutes
                            </option>

                            <option
                                value="120"
                                <?php
                                echo
                                $settings[
                                    'default_lesson_duration'
                                ] == '120'
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                120 Minutes
                            </option>

                        </select>

                    </div>


                </div>


                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_academic"
                    >
                        💾 Save Academic Settings
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             WEBSITE
        ================================================= -->

        <div
            id="website"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Website Controls
                </h2>

                <p>
                    Control access and registration features.
                </p>

            </div>


            <form method="POST">


                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Website Enabled
                        </strong>

                        <span>
                            Keep the NISEL website available to visitors.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="website_status"
                            <?php
                            echo
                            $settings['website_status']
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Maintenance Mode
                        </strong>

                        <span>
                            Temporarily restrict access while making changes.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="maintenance_mode"
                            <?php
                            echo
                            $settings['maintenance_mode']
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Student Registration
                        </strong>

                        <span>
                            Allow new students to create accounts.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="student_registration"
                            <?php
                            echo
                            $settings[
                                'student_registration'
                            ]
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Teacher Applications
                        </strong>

                        <span>
                            Allow teachers to submit applications.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="teacher_applications"
                            <?php
                            echo
                            $settings[
                                'teacher_applications'
                            ]
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_website"
                    >
                        💾 Save Website Settings
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             EMAIL
        ================================================= -->

        <div
            id="email"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Notifications
                </h2>

                <p>
                    Control important system notifications.
                </p>

            </div>


            <form method="POST">

                <div class="form-group">

                    <label>
                        Notification Email
                    </label>

                    <input
                        type="email"
                        name="notification_email"
                        value="<?php
                        echo htmlspecialchars(
                            $settings[
                                'notification_email'
                            ]
                        );
                        ?>"
                        placeholder="admin@example.com"
                    >

                </div>


                <br>


                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Booking Notifications
                        </strong>

                        <span>
                            Notify administration when a booking is created.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="booking_notifications"
                            <?php
                            echo
                            $settings[
                                'booking_notifications'
                            ]
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Payment Notifications
                        </strong>

                        <span>
                            Notify administration when a payment is received.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="payment_notifications"
                            <?php
                            echo
                            $settings[
                                'payment_notifications'
                            ]
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="toggle-row">

                    <div class="toggle-info">

                        <strong>
                            Teacher Notifications
                        </strong>

                        <span>
                            Notify teachers about relevant assignments.
                        </span>

                    </div>


                    <label class="switch">

                        <input
                            type="checkbox"
                            name="teacher_notifications"
                            <?php
                            echo
                            $settings[
                                'teacher_notifications'
                            ]
                            == '1'
                                ? 'checked'
                                : '';
                            ?>
                        >

                        <span class="slider"></span>

                    </label>

                </div>



                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_email"
                    >
                        💾 Save Notification Settings
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             APPEARANCE
        ================================================= -->

        <div
            id="appearance"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Appearance
                </h2>

                <p>
                    Customize NISEL's primary interface colors.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group">

                        <label>
                            Primary Color
                        </label>

                        <div class="color-row">

                            <input
                                type="color"
                                name="primary_color"
                                value="<?php
                                echo htmlspecialchars(
                                    $settings[
                                        'primary_color'
                                    ]
                                );
                                ?>"
                            >

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $settings[
                                        'primary_color'
                                    ]
                                );
                                ?>
                            </span>

                        </div>

                    </div>


                    <div class="form-group">

                        <label>
                            Secondary Color
                        </label>

                        <div class="color-row">

                            <input
                                type="color"
                                name="secondary_color"
                                value="<?php
                                echo htmlspecialchars(
                                    $settings[
                                        'secondary_color'
                                    ]
                                );
                                ?>"
                            >

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $settings[
                                        'secondary_color'
                                    ]
                                );
                                ?>
                            </span>

                        </div>

                    </div>


                </div>


                <div class="save-area">

                    <button
                        class="save-button"
                        name="save_appearance"
                    >
                        🎨 Save Appearance
                    </button>

                </div>

            </form>

        </div>



        <!-- =================================================
             SECURITY
        ================================================= -->

        <div
            id="security"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    Administrator Security
                </h2>

                <p>
                    Change the password for the current administrator account.
                </p>

            </div>


            <form method="POST">

                <div class="form-grid">


                    <div class="form-group full">

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


                <div class="save-area">

                    <button
                        class="save-button"
                        name="change_password"
                    >
                        🔐 Change Password
                    </button>

                </div>

            </form>


            <div class="danger-box">

                <strong>
                    Security Recommendation
                </strong>

                <p style="margin-top:7px;font-size:13px;">

                    Use a strong administrator password and do not share
                    your Paystack secret key with anyone.

                </p>

            </div>

        </div>



        <!-- =================================================
             SYSTEM
        ================================================= -->

        <div
            id="system"
            class="panel"
        >

            <div class="panel-header">

                <h2>
                    System Information
                </h2>

                <p>
                    Information about your NISEL installation.
                </p>

            </div>


            <div class="status-box">


                <div class="status-card">

                    <div class="status-icon">
                        🟢
                    </div>

                    <strong>
                        Database
                    </strong>

                    <span class="status-online">
                        Connected
                    </span>

                </div>


                <div class="status-card">

                    <div class="status-icon">
                        🐘
                    </div>

                    <strong>
                        PHP Version
                    </strong>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            PHP_VERSION
                        );
                        ?>
                    </span>

                </div>


                <div class="status-card">

                    <div class="status-icon">
                        🔐
                    </div>

                    <strong>
                        Login
                    </strong>

                    <span class="status-online">
                        Administrator
                    </span>

                </div>


            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Administrator
                    </label>

                    <input
                        type="text"
                        value="<?php
                        echo htmlspecialchars(
                            $adminName
                        );
                        ?>"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Administrator Email
                    </label>

                    <input
                        type="text"
                        value="<?php
                        echo htmlspecialchars(
                            $adminEmail
                        );
                        ?>"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Platform
                    </label>

                    <input
                        type="text"
                        value="NISEL ONLINE EDUCATION"
                        readonly
                    >

                </div>


                <div class="form-group">

                    <label>
                        Database Driver
                    </label>

                    <input
                        type="text"
                        value="PDO / MySQL"
                        readonly
                    >

                </div>


            </div>

        </div>


    </div>

</div>



<script>

/* =========================================================
   SETTINGS TABS
========================================================= */

function openTab(event, tabName) {

    const panels =
        document.querySelectorAll(
            '.panel'
        );

    panels.forEach(function(panel) {

        panel.classList.remove(
            'active'
        );

    });


    const buttons =
        document.querySelectorAll(
            '.tab-button'
        );

    buttons.forEach(function(button) {

        button.classList.remove(
            'active'
        );

    });


    document
        .getElementById(tabName)
        .classList.add('active');


    event.currentTarget
        .classList.add('active');
}

</script>


</body>

</html>
