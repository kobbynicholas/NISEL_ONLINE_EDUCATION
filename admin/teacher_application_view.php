<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";

$message_type = "";


/* =========================================================
   SEND TEACHER APPROVAL EMAIL
========================================================= */

function sendTeacherApprovalEmail(
    string $recipientEmail,
    string $teacherName,
    string $teacherId,
    string $temporaryPassword
): array {

    $subject =
        "NISEL Online Education - Teacher Account Approved";

    $safeName =
        htmlspecialchars(
            $teacherName,
            ENT_QUOTES,
            "UTF-8"
        );

    $safeId =
        htmlspecialchars(
            $teacherId,
            ENT_QUOTES,
            "UTF-8"
        );

    $safePassword =
        htmlspecialchars(
            $temporaryPassword,
            ENT_QUOTES,
            "UTF-8"
        );


    /*
     * ---------------------------------------------------------
     * PHPMailer
     * ---------------------------------------------------------
     * If PHPMailer is installed at:
     *
     * C:\xampp\htdocs\online\vendor\autoload.php
     *
     * it will be used automatically.
     *
     * IMPORTANT:
     * Replace the SMTP settings below with the email account
     * you want NISEL to send from.
     * ---------------------------------------------------------
     */

    $autoload =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        "vendor" .
        DIRECTORY_SEPARATOR .
        "autoload.php";


    if (file_exists($autoload)) {

        require_once $autoload;


        if (
            class_exists(
                "\\PHPMailer\\PHPMailer\\PHPMailer"
            )
        ) {

            try {

                $mail =
                    new \PHPMailer\PHPMailer\PHPMailer(
                        true
                    );


                $mail->isSMTP();

                /*
                 * CHANGE THESE SETTINGS
                 * to your actual SMTP provider.
                 */
                $mail->Host =
                    "smtp.gmail.com";

                $mail->SMTPAuth =
                    true;

                $mail->Username =
                    "YOUR_NISEL_EMAIL@gmail.com";

                $mail->Password =
                    "YOUR_GMAIL_APP_PASSWORD";

                $mail->SMTPSecure =
                    \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port =
                    587;


                $mail->setFrom(
                    "YOUR_NISEL_EMAIL@gmail.com",
                    "NISEL ONLINE EDUCATION"
                );

                $mail->addAddress(
                    $recipientEmail,
                    $teacherName
                );


                $mail->isHTML(true);

                $mail->Subject =
                    $subject;


                $mail->Body = "
                    <div style=\"
                        font-family:Arial,sans-serif;
                        background:#f4f8fc;
                        padding:30px;
                    \">

                        <div style=\"
                            max-width:600px;
                            margin:auto;
                            background:#ffffff;
                            border-radius:16px;
                            overflow:hidden;
                            box-shadow:0 8px 30px rgba(0,0,0,.08);
                        \">

                            <div style=\"
                                background:#003366;
                                color:#ffffff;
                                padding:28px;
                                text-align:center;
                            \">

                                <h1 style=\"
                                    margin:0;
                                    font-size:24px;
                                \">
                                    NISEL ONLINE EDUCATION
                                </h1>

                                <p style=\"
                                    margin:8px 0 0;
                                    color:#d9efff;
                                \">
                                    Teacher Portal
                                </p>

                            </div>

                            <div style=\"padding:30px;\">

                                <h2 style=\"color:#003366;\">
                                    Congratulations, {$safeName}!
                                </h2>

                                <p style=\"
                                    color:#4b5d70;
                                    line-height:1.7;
                                \">
                                    Your application to become a teacher
                                    with NISEL ONLINE EDUCATION has been
                                    approved.
                                </p>

                                <p style=\"
                                    color:#4b5d70;
                                    line-height:1.7;
                                \">
                                    Your teacher account has now been
                                    created. Please use the login details
                                    below to access your teacher portal.
                                </p>

                                <div style=\"
                                    background:#eef7fc;
                                    border:1px solid #cfe7f5;
                                    border-radius:12px;
                                    padding:20px;
                                    margin:22px 0;
                                \">

                                    <p style=\"margin:7px 0;\">
                                        <strong>Teacher ID:</strong>
                                        {$safeId}
                                    </p>

                                    <p style=\"margin:7px 0;\">
                                        <strong>Email:</strong>
                                        {$recipientEmail}
                                    </p>

                                    <p style=\"margin:7px 0;\">
                                        <strong>Temporary Password:</strong>
                                        {$safePassword}
                                    </p>

                                </div>

                                <p style=\"
                                    color:#b42318;
                                    background:#fff4f4;
                                    border-radius:10px;
                                    padding:13px;
                                    line-height:1.6;
                                \">
                                    For security, please change your
                                    temporary password after your first
                                    successful login.
                                </p>

                                <div style=\"text-align:center;margin:25px 0;\">

                                    <a
                                        href=\"http://localhost/online/teacher/login.php\"
                                        style=\"
                                            display:inline-block;
                                            background:#003366;
                                            color:#ffffff;
                                            padding:13px 22px;
                                            border-radius:9px;
                                            text-decoration:none;
                                            font-weight:bold;
                                        \"
                                    >
                                        Login to Teacher Portal
                                    </a>

                                </div>

                                <p style=\"
                                    color:#7a8796;
                                    font-size:12px;
                                    line-height:1.6;
                                \">
                                    If you did not expect this message,
                                    please contact NISEL ONLINE EDUCATION
                                    administration.
                                </p>

                            </div>

                            <div style=\"
                                background:#f7f9fb;
                                padding:18px;
                                text-align:center;
                                color:#8996a4;
                                font-size:11px;
                            \">
                                © " . date("Y") . "
                                NISEL ONLINE EDUCATION.
                                All Rights Reserved.
                            </div>

                        </div>

                    </div>
                ";


                $mail->AltBody =
                    "Congratulations {$teacherName}.\n\n" .
                    "Your NISEL teacher application has been approved.\n\n" .
                    "Teacher ID: {$teacherId}\n" .
                    "Email: {$recipientEmail}\n" .
                    "Temporary Password: {$temporaryPassword}\n\n" .
                    "Please change your temporary password after your first login.";


                $mail->send();


                return [
                    "success" => true,
                    "message" =>
                        "Approval email sent successfully."
                ];

            } catch (
                \PHPMailer\PHPMailer\Exception $e
            ) {

                return [
                    "success" => false,
                    "message" =>
                        "Teacher account was created, but the approval email could not be sent. " .
                        $e->getMessage()
                ];
            }
        }
    }


    /*
     * ---------------------------------------------------------
     * FALLBACK
     * ---------------------------------------------------------
     * If PHPMailer is not installed, use PHP mail().
     *
     * On XAMPP this normally requires mail/SMTP configuration
     * in php.ini or sendmail.
     * ---------------------------------------------------------
     */

    $headers = [];

    $headers[] =
        "MIME-Version: 1.0";

    $headers[] =
        "Content-Type: text/html; charset=UTF-8";

    $headers[] =
        "From: NISEL ONLINE EDUCATION <noreply@niseleducation.online>";


    $body = "
        <html>
        <body style=\"font-family:Arial,sans-serif;\">

            <h2 style=\"color:#003366;\">
                NISEL ONLINE EDUCATION
            </h2>

            <p>
                Dear {$safeName},
            </p>

            <p>
                Congratulations. Your teacher application has
                been approved.
            </p>

            <p>
                <strong>Teacher ID:</strong>
                {$safeId}
            </p>

            <p>
                <strong>Email:</strong>
                {$recipientEmail}
            </p>

            <p>
                <strong>Temporary Password:</strong>
                {$safePassword}
            </p>

            <p>
                Please change your temporary password after
                your first login.
            </p>

            <p>
                NISEL ONLINE EDUCATION
            </p>

        </body>
        </html>
    ";


    $sent =
        mail(
            $recipientEmail,
            $subject,
            $body,
            implode(
                "\r\n",
                $headers
            )
        );


    if ($sent) {

        return [
            "success" => true,
            "message" =>
                "Approval email sent successfully."
        ];

    }


    return [
        "success" => false,
        "message" =>
            "Teacher account was created, but the email could not be sent. " .
            "Configure PHPMailer SMTP or XAMPP mail settings."
    ];
}



/* =========================================================
   APPLICATION ID
========================================================= */

$application_id = 0;

if (isset($_GET['id'])) {

    $application_id = (int) $_GET['id'];

} elseif (isset($_POST['application_id'])) {

    $application_id = (int) $_POST['application_id'];

}


if ($application_id <= 0) {

    die("Invalid teacher application ID.");

}


/* =========================================================
   APPROVE APPLICATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "approve"
) {

    try {

        /* ================================================
           GET APPLICATION
        ================================================= */

        $stmt = $pdo->prepare("
            SELECT *
            FROM teacher_applications
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $application_id
        ]);

        $application = $stmt->fetch();


        if (!$application) {

            throw new Exception(
                "Teacher application was not found."
            );

        }


        /* ================================================
           CHECK APPLICATION STATUS
        ================================================= */

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


        /* ================================================
           GET APPLICATION DATA
        ================================================= */

        $teacher_name =
            trim(
                $application['full_name']
            );

        $phone =
            trim(
                $application['phone']
            );

        $email =
            trim(
                $application['email']
            );

        $qualification =
            trim(
                $application['qualification']
            );

        $subjects =
            trim(
                $application['subjects']
            );

        /*
         * IMPORTANT:
         * Application table uses "curricula"
         * Teachers table uses "curriculum"
         */

        $curriculum =
            trim(
                $application['curricula']
            );

        $experience =
            trim(
                $application['teaching_experience']
                ?? ""
            );

        $bio =
            trim(
                $application['professional_statement']
                ?? ""
            );

        $photo =
            trim(
                $application['photo_filename']
                ?? ""
            );


        /* ================================================
           AVAILABILITY
        ================================================= */

        $preferred_days =
            trim(
                $application['preferred_days']
                ?? ""
            );

        $preferred_times =
            trim(
                $application['preferred_times']
                ?? ""
            );


        if (
            $preferred_days !== ""
            &&
            $preferred_times !== ""
        ) {

            $availability =
                $preferred_days .
                " | " .
                $preferred_times;

        } elseif ($preferred_days !== "") {

            $availability =
                $preferred_days;

        } elseif ($preferred_times !== "") {

            $availability =
                $preferred_times;

        } else {

            $availability = "";

        }


        /* ================================================
           CHECK EXISTING EMAIL
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
            $checkEmail->fetch();


        if ($existingTeacher) {

            throw new Exception(
                "A teacher account already exists "
                . "with this email. Teacher ID: "
                . $existingTeacher['teacher_id']
            );

        }


        /* ================================================
           GENERATE UNIQUE TEACHER ID
        ================================================= */

        do {

            $teacher_id =
                "NISEL-T-" .
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(4)
                        ),
                        0,
                        8
                    )
                );


            $checkID = $pdo->prepare("
                SELECT id
                FROM teachers
                WHERE teacher_id = ?
                LIMIT 1
            ");

            $checkID->execute([
                $teacher_id
            ]);

            $teacherExists =
                $checkID->fetch();

        } while ($teacherExists);


        /* ================================================
           GENERATE TEMPORARY PASSWORD
        ================================================= */

        $temporary_password =
            "Nisel@" .
            random_int(
                1000,
                9999
            );


        /* ================================================
           HASH PASSWORD
        ================================================= */

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


        /* ================================================
           START DATABASE TRANSACTION
        ================================================= */

        $pdo->beginTransaction();


        /* ================================================
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


        /* ================================================
           VERIFY TEACHER WAS INSERTED
        ================================================= */

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
            $verifyTeacher->fetch();


        if (!$createdTeacher) {

            throw new Exception(
                "Teacher account was not found "
                . "after insertion."
            );

        }


        /* ================================================
           UPDATE APPLICATION
        ================================================= */

        $updateApplication = $pdo->prepare("

            UPDATE teacher_applications

            SET application_status = 'Approved'

            WHERE id = ?

        ");


        $updateApplication->execute([
            $application_id
        ]);


        /* ================================================
           VERIFY APPLICATION UPDATE
        ================================================= */

        if (
            $updateApplication->rowCount() === 0
        ) {

            /*
             * If it was already approved, this can be 0.
             * But we already checked the status above.
             */

            throw new Exception(
                "Teacher was created but "
                . "application status could not be updated."
            );

        }


        /* ================================================
           COMMIT
        ================================================= */

        $pdo->commit();


        /* ================================================
           SEND APPROVAL EMAIL
        ================================================= */
        /*
         * The database transaction has already been committed.
         * Therefore an email failure will NOT undo the newly
         * created teacher account.
         */
        $emailResult =
            sendTeacherApprovalEmail(
                $email,
                $teacher_name,
                $teacher_id,
                $temporary_password
            );


        /* ================================================
           SUCCESS MESSAGE
        ================================================= */

        if ($emailResult["success"]) {

            $message_type =
                "success";

            $message =
                "Teacher approved successfully. " .
                "Teacher ID: " .
                $teacher_id .
                ". The teacher's login details have been " .
                "sent to " .
                $email .
                ".";

        } else {

            /*
             * The teacher account was still created successfully.
             * Do not display the temporary password on the page
             * unless the administrator needs it for recovery.
             */
            $message_type =
                "success";

            $message =
                "Teacher approved successfully. " .
                "Teacher ID: " .
                $teacher_id .
                ". However, the approval email could not be sent. " .
                $emailResult["message"];
        }


    } catch (Exception $e) {


        /* ================================================
           ROLLBACK
        ================================================= */

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
            . $e->getMessage();

    }

}


/* =========================================================
   REJECT APPLICATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "reject"
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
            . $e->getMessage();

        $message_type =
            "error";

    }

}


/* =========================================================
   GET APPLICATION FOR DISPLAY
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
    $stmt->fetch();


if (!$application) {

    die(
        "Teacher application not found."
    );

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
    Teacher Application | NISEL ONLINE EDUCATION
</title>

<style>
:root{
    --primary:#063b66;
    --primary-2:#0b6fb3;
    --accent:#16a3ff;
    --success:#16835b;
    --danger:#c0392b;
    --warning:#b7791f;
    --dark:#102a43;
    --muted:#6b7c93;
    --bg:#f4f7fb;
    --card:#ffffff;
    --border:#e5ebf2;
    --shadow:0 18px 50px rgba(16,42,67,.09);
    --radius:20px;
}

*{
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{
    margin:0;
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
    background:var(--bg);
    color:var(--dark);
}

a{
    color:inherit;
    text-decoration:none;
}

button{
    font:inherit;
}

.app-shell{
    min-height:100vh;
}

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar{
    position:fixed;
    left:0;
    top:0;
    bottom:0;
    width:260px;
    padding:24px 16px;
    background:
        linear-gradient(
            180deg,
            #062f52 0%,
            #073f6c 55%,
            #062f52 100%
        );
    color:#fff;
    z-index:50;
    overflow-y:auto;
}

.brand{
    display:flex;
    align-items:center;
    gap:12px;
    padding:6px 10px 28px;
}

.brand-mark{
    width:44px;
    height:44px;
    display:grid;
    place-items:center;
    border-radius:13px;
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.15);
    font-size:21px;
}

.brand-text strong{
    display:block;
    font-size:17px;
    letter-spacing:.4px;
}

.brand-text span{
    display:block;
    margin-top:2px;
    font-size:10px;
    color:#bcd8ec;
    letter-spacing:1.4px;
}

.menu-title{
    padding:14px 12px 8px;
    color:#8fb6d2;
    font-size:10px;
    font-weight:800;
    letter-spacing:1.5px;
    text-transform:uppercase;
}

.menu{
    display:grid;
    gap:6px;
}

.menu a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 13px;
    border-radius:12px;
    color:#dcecf7;
    font-size:13px;
    font-weight:650;
    transition:.2s ease;
}

.menu a:hover,
.menu a.active{
    background:rgba(255,255,255,.12);
    color:#fff;
    transform:translateX(2px);
}

.menu-icon{
    width:22px;
    text-align:center;
    font-size:17px;
}

.admin-box{
    margin-top:30px;
    padding:15px;
    border-radius:15px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.10);
}

.admin-box small{
    display:block;
    color:#9fc3da;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.admin-box strong{
    display:block;
    margin-top:5px;
    font-size:13px;
}

/* =========================================================
   MAIN
========================================================= */

.main{
    margin-left:260px;
    min-height:100vh;
}

.topbar{
    height:74px;
    padding:0 34px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:rgba(255,255,255,.88);
    backdrop-filter:blur(14px);
    border-bottom:1px solid var(--border);
    position:sticky;
    top:0;
    z-index:30;
}

.breadcrumb{
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--muted);
    font-size:12px;
}

.breadcrumb strong{
    color:var(--dark);
}

.top-actions{
    display:flex;
    align-items:center;
    gap:10px;
}

.top-pill{
    display:flex;
    align-items:center;
    gap:7px;
    padding:8px 12px;
    border:1px solid var(--border);
    background:#fff;
    border-radius:999px;
    color:var(--muted);
    font-size:12px;
}

.avatar{
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    border-radius:50%;
    background:#e7f3fb;
    color:var(--primary);
    font-weight:800;
}

/* =========================================================
   CONTENT
========================================================= */

.content{
    width:min(1400px, calc(100% - 48px));
    margin:0 auto;
    padding:34px 0 60px;
}

.page-heading{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:25px;
    margin-bottom:24px;
}

.eyebrow{
    display:inline-flex;
    align-items:center;
    gap:7px;
    color:var(--primary-2);
    font-size:11px;
    font-weight:900;
    letter-spacing:1.2px;
    text-transform:uppercase;
}

.page-heading h1{
    margin:8px 0 7px;
    font-size:32px;
    line-height:1.1;
    letter-spacing:-.8px;
}

.page-heading p{
    margin:0;
    color:var(--muted);
    font-size:14px;
}

.status-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:9px 13px;
    border-radius:999px;
    font-size:11px;
    font-weight:900;
}

.status-approved{
    background:#e9f8f1;
    color:#13734f;
}

.status-pending{
    background:#fff6df;
    color:#9a6a16;
}

.status-rejected{
    background:#fff0ef;
    color:#a72f26;
}

/* =========================================================
   ALERT
========================================================= */

.alert{
    display:flex;
    gap:13px;
    align-items:flex-start;
    margin-bottom:22px;
    padding:16px 18px;
    border-radius:15px;
    border:1px solid;
}

.alert.success{
    background:#effaf5;
    border-color:#ccefe0;
    color:#126a49;
}

.alert.error{
    background:#fff3f2;
    border-color:#f4cfca;
    color:#a12e25;
}

.alert-icon{
    font-size:19px;
}

.alert strong{
    display:block;
    margin-bottom:3px;
    font-size:13px;
}

.alert span{
    font-size:12px;
    line-height:1.6;
}

/* =========================================================
   SUMMARY CARDS
========================================================= */

.summary-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:16px;
    margin-bottom:22px;
}

.summary-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:17px;
    padding:18px;
    box-shadow:0 8px 25px rgba(16,42,67,.04);
}

.summary-card .label{
    color:var(--muted);
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:1px;
    font-weight:800;
}

.summary-card .value{
    margin-top:8px;
    font-size:17px;
    font-weight:850;
    color:var(--dark);
    word-break:break-word;
}

.summary-card .icon{
    float:right;
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    border-radius:10px;
    background:#eef7fc;
    font-size:16px;
}

/* =========================================================
   PROFILE + DETAILS
========================================================= */

.profile-layout{
    display:grid;
    grid-template-columns:330px minmax(0,1fr);
    gap:22px;
}

.card{
    background:var(--card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
}

.profile-card{
    overflow:hidden;
    position:sticky;
    top:98px;
    align-self:start;
}

.profile-cover{
    height:112px;
    background:
        radial-gradient(circle at 15% 30%, rgba(255,255,255,.25), transparent 25%),
        linear-gradient(135deg,#063b66,#0a76b9);
}

.profile-body{
    padding:0 23px 25px;
}

.profile-photo{
    width:106px;
    height:106px;
    margin-top:-53px;
    border:5px solid #fff;
    border-radius:25px;
    overflow:hidden;
    background:#eaf2f7;
    box-shadow:0 12px 30px rgba(0,0,0,.12);
    display:grid;
    place-items:center;
}

.profile-photo img{
    width:100%;
    height:100%;
    object-fit:cover;
}

.profile-placeholder{
    font-size:38px;
}

.profile-body h2{
    margin:15px 0 5px;
    font-size:21px;
}

.profile-role{
    color:var(--primary-2);
    font-size:12px;
    font-weight:800;
}

.profile-contact{
    display:grid;
    gap:10px;
    margin-top:20px;
}

.contact-item{
    display:flex;
    gap:10px;
    align-items:flex-start;
    color:var(--muted);
    font-size:12px;
    line-height:1.5;
}

.contact-item b{
    color:var(--dark);
}

.contact-icon{
    width:27px;
    height:27px;
    flex:0 0 27px;
    display:grid;
    place-items:center;
    border-radius:8px;
    background:#f0f6fa;
}

.profile-divider{
    height:1px;
    background:var(--border);
    margin:20px 0;
}

.profile-meta{
    display:grid;
    gap:11px;
}

.meta-row{
    display:flex;
    justify-content:space-between;
    gap:15px;
    font-size:11px;
}

.meta-row span:first-child{
    color:var(--muted);
}

.meta-row span:last-child{
    color:var(--dark);
    font-weight:750;
    text-align:right;
}

/* =========================================================
   DETAILS
========================================================= */

.details-column{
    display:grid;
    gap:20px;
}

.section-card{
    padding:24px;
}

.section-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-bottom:19px;
    padding-bottom:16px;
    border-bottom:1px solid var(--border);
}

.section-head h3{
    margin:0;
    font-size:15px;
}

.section-head p{
    margin:4px 0 0;
    color:var(--muted);
    font-size:11px;
}

.section-number{
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    border-radius:10px;
    background:#edf6fc;
    color:var(--primary-2);
    font-weight:900;
    font-size:12px;
}

.info-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

.info-item{
    padding:14px;
    border:1px solid #edf0f4;
    background:#fafcfe;
    border-radius:13px;
}

.info-item.full{
    grid-column:1/-1;
}

.info-item label{
    display:block;
    margin-bottom:7px;
    color:var(--muted);
    font-size:10px;
    font-weight:800;
    letter-spacing:.7px;
    text-transform:uppercase;
}

.info-item .value{
    color:var(--dark);
    font-size:13px;
    line-height:1.65;
    white-space:pre-wrap;
    word-break:break-word;
}

.tag-list{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
}

.tag{
    padding:7px 10px;
    border-radius:999px;
    background:#eef7fc;
    border:1px solid #d9ebf6;
    color:#075783;
    font-size:10px;
    font-weight:750;
}

/* =========================================================
   DOCUMENTS
========================================================= */

.document-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:12px;
}

.document{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:14px;
    border:1px solid var(--border);
    border-radius:13px;
    background:#fbfdff;
}

.document-left{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
}

.document-icon{
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    border-radius:10px;
    background:#edf5fb;
    font-size:17px;
}

.document-name{
    min-width:0;
}

.document-name strong{
    display:block;
    font-size:12px;
}

.document-name span{
    display:block;
    margin-top:3px;
    color:var(--muted);
    font-size:10px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.btn-small{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 11px;
    border-radius:9px;
    background:#edf5fb;
    color:#075783;
    font-size:10px;
    font-weight:850;
    white-space:nowrap;
}

/* =========================================================
   ACTION AREA
========================================================= */

.action-card{
    padding:22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    background:
        linear-gradient(
            135deg,
            #ffffff,
            #f4f9fd
        );
}

.action-copy h3{
    margin:0 0 5px;
    font-size:16px;
}

.action-copy p{
    margin:0;
    color:var(--muted);
    font-size:11px;
    line-height:1.6;
}

.actions{
    display:flex;
    gap:9px;
    flex-wrap:wrap;
}

.btn{
    border:0;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:11px 15px;
    border-radius:11px;
    font-size:11px;
    font-weight:850;
    transition:.2s ease;
}

.btn:hover{
    transform:translateY(-1px);
}

.btn-primary{
    background:linear-gradient(135deg,#063b66,#0a74b6);
    color:#fff;
    box-shadow:0 8px 18px rgba(6,59,102,.2);
}

.btn-danger{
    background:#fff0ef;
    color:#a22f26;
    border:1px solid #f3d0cc;
}

.btn-neutral{
    background:#fff;
    color:#36536b;
    border:1px solid var(--border);
}

/* =========================================================
   MODAL
========================================================= */

.modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:100;
    background:rgba(7,31,52,.62);
    backdrop-filter:blur(6px);
    padding:20px;
    align-items:center;
    justify-content:center;
}

.modal.open{
    display:flex;
}

.modal-card{
    width:min(500px,100%);
    background:#fff;
    border-radius:22px;
    padding:26px;
    box-shadow:0 30px 80px rgba(0,0,0,.25);
}

.modal-card h3{
    margin:0 0 8px;
    font-size:19px;
}

.modal-card p{
    color:var(--muted);
    font-size:12px;
    line-height:1.6;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:9px;
    margin-top:22px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:1100px){

    .summary-grid{
        grid-template-columns:repeat(2,1fr);
    }

    .profile-layout{
        grid-template-columns:1fr;
    }

    .profile-card{
        position:relative;
        top:auto;
    }
}

@media(max-width:800px){

    .sidebar{
        position:relative;
        width:100%;
        height:auto;
    }

    .main{
        margin-left:0;
    }

    .topbar{
        padding:0 18px;
    }

    .content{
        width:min(100% - 28px,1400px);
        padding-top:24px;
    }

    .page-heading{
        align-items:flex-start;
        flex-direction:column;
    }

    .page-heading h1{
        font-size:27px;
    }

    .summary-grid{
        grid-template-columns:1fr;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .info-item.full{
        grid-column:auto;
    }

    .document-grid{
        grid-template-columns:1fr;
    }

    .action-card{
        align-items:flex-start;
        flex-direction:column;
    }

    .breadcrumb{
        display:none;
    }
}

@media(max-width:520px){

    .topbar{
        height:auto;
        padding:12px 15px;
    }

    .top-pill{
        display:none;
    }

    .section-card,
    .action-card{
        padding:18px;
    }

    .profile-body{
        padding-left:18px;
        padding-right:18px;
    }
}
</style>
</head>

<body>

<div class="app-shell">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-mark">
                🎓
            </div>

            <div class="brand-text">

                <strong>
                    NISEL
                </strong>

                <span>
                    ONLINE EDUCATION
                </span>

            </div>

        </div>


        <div class="menu-title">
            Administration
        </div>

        <nav class="menu">

            <a href="dashboard.php">
                <span class="menu-icon">🏠</span>
                Dashboard
            </a>

            <a href="students.php">
                <span class="menu-icon">👨‍🎓</span>
                Students
            </a>

            <a href="teachers.php">
                <span class="menu-icon">👨‍🏫</span>
                Teachers
            </a>

            <a href="bookings.php">
                <span class="menu-icon">📚</span>
                Bookings
            </a>

            <a href="payments.php">
                <span class="menu-icon">💳</span>
                Payments
            </a>

            <a href="teacher_applications.php" class="active">
                <span class="menu-icon">📝</span>
                Teacher Applications
            </a>

            <a href="reports.php">
                <span class="menu-icon">📊</span>
                Reports
            </a>

        </nav>


        <div class="menu-title">
            System
        </div>

        <nav class="menu">

            <a href="settings.php">
                <span class="menu-icon">⚙️</span>
                Settings
            </a>

            <a href="logout.php">
                <span class="menu-icon">🚪</span>
                Logout
            </a>

        </nav>


        <div class="admin-box">

            <small>
                Administrator
            </small>

            <strong>
                <?= htmlspecialchars(
                    $_SESSION['admin_name'] ?? 'Administrator',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </div>

    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">

        <header class="topbar">

            <div class="breadcrumb">

                <span>
                    Administration
                </span>

                <span>›</span>

                <span>
                    Teacher Applications
                </span>

                <span>›</span>

                <strong>
                    Application #<?= (int)$application_id ?>
                </strong>

            </div>


            <div class="top-actions">

                <div class="top-pill">
                    🛡️ Admin Portal
                </div>

                <div class="avatar">
                    <?= strtoupper(
                        substr(
                            $_SESSION['admin_name'] ?? 'A',
                            0,
                            1
                        )
                    ) ?>
                </div>

            </div>

        </header>


        <div class="content">

            <!-- =================================================
                 PAGE HEADING
            ================================================== -->

            <div class="page-heading">

                <div>

                    <div class="eyebrow">
                        <span>●</span>
                        Teacher Application Review
                    </div>

                    <h1>
                        <?= htmlspecialchars(
                            $application['full_name'] ?? 'Teacher Application',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h1>

                    <p>
                        Review the applicant's information and make
                        an approval decision.
                    </p>

                </div>


                <?php
                    $status =
                        trim(
                            $application['application_status']
                            ?? 'Pending'
                        );

                    $statusClass =
                        strtolower($status) === 'approved'
                            ? 'status-approved'
                            : (
                                strtolower($status) === 'rejected'
                                    ? 'status-rejected'
                                    : 'status-pending'
                            );
                ?>

                <div class="status-badge <?= $statusClass ?>">

                    <span>●</span>

                    <?= htmlspecialchars(
                        $status,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>


            <!-- =================================================
                 MESSAGE
            ================================================== -->

            <?php if ($message !== ''): ?>

                <div class="alert <?= $message_type === 'error' ? 'error' : 'success' ?>">

                    <div class="alert-icon">

                        <?= $message_type === 'error'
                            ? '⚠️'
                            : '✓'
                        ?>

                    </div>

                    <div>

                        <strong>
                            <?= $message_type === 'error'
                                ? 'Action could not be completed'
                                : 'Action completed'
                            ?>
                        </strong>

                        <span>
                            <?= htmlspecialchars(
                                $message,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="summary-grid">

                <div class="summary-card">

                    <span class="icon">
                        🪪
                    </span>

                    <div class="label">
                        Application ID
                    </div>

                    <div class="value">
                        #<?= (int)$application_id ?>
                    </div>

                </div>


                <div class="summary-card">

                    <span class="icon">
                        ✉️
                    </span>

                    <div class="label">
                        Email
                    </div>

                    <div class="value">
                        <?= htmlspecialchars(
                            $application['email'] ?? '—',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>


                <div class="summary-card">

                    <span class="icon">
                        📱
                    </span>

                    <div class="label">
                        Phone
                    </div>

                    <div class="value">
                        <?= htmlspecialchars(
                            $application['phone'] ?? '—',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>


                <div class="summary-card">

                    <span class="icon">
                        📅
                    </span>

                    <div class="label">
                        Status
                    </div>

                    <div class="value">
                        <?= htmlspecialchars(
                            $status,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- =================================================
                 PROFILE + DETAILS
            ================================================== -->

            <div class="profile-layout">


                <!-- PROFILE CARD -->

                <section class="card profile-card">

                    <div class="profile-cover"></div>

                    <div class="profile-body">

                        <div class="profile-photo">

                            <?php
                            $photo =
                                trim(
                                    $application['photo_filename']
                                    ?? ''
                                );

                            $photoUrl = '';

                            if ($photo !== '') {

                                $photoUrl =
                                    "../teacher/uploads/teacher_applications/photos/" .
                                    rawurlencode($photo);
                            }
                            ?>

                            <?php if ($photoUrl !== ''): ?>

                                <img
                                    src="<?= htmlspecialchars(
                                        $photoUrl,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="Applicant photo"
                                    onerror="
                                        this.style.display='none';
                                        this.nextElementSibling.style.display='grid';
                                    "
                                >

                                <div
                                    class="profile-placeholder"
                                    style="display:none;"
                                >
                                    👤
                                </div>

                            <?php else: ?>

                                <div class="profile-placeholder">
                                    👤
                                </div>

                            <?php endif; ?>

                        </div>


                        <h2>
                            <?= htmlspecialchars(
                                $application['full_name'] ?? '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h2>

                        <div class="profile-role">
                            Teacher Applicant
                        </div>


                        <div class="profile-contact">

                            <div class="contact-item">

                                <div class="contact-icon">
                                    ✉
                                </div>

                                <div>
                                    <b>Email</b><br>
                                    <?= htmlspecialchars(
                                        $application['email'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="contact-item">

                                <div class="contact-icon">
                                    ☎
                                </div>

                                <div>
                                    <b>Phone</b><br>
                                    <?= htmlspecialchars(
                                        $application['phone'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="contact-item">

                                <div class="contact-icon">
                                    📍
                                </div>

                                <div>
                                    <b>Location</b><br>
                                    <?= htmlspecialchars(
                                        $application['location'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>

                        </div>


                        <div class="profile-divider"></div>


                        <div class="profile-meta">

                            <div class="meta-row">

                                <span>
                                    Qualification
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        $application['qualification'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>


                            <div class="meta-row">

                                <span>
                                    Curriculum
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        $application['curricula'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>


                            <div class="meta-row">

                                <span>
                                    Experience
                                </span>

                                <span>
                                    <?= htmlspecialchars(
                                        $application['teaching_experience'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </section>


                <!-- DETAILS -->

                <div class="details-column">


                    <!-- PERSONAL INFORMATION -->

                    <section class="card section-card">

                        <div class="section-head">

                            <div>

                                <h3>
                                    Personal Information
                                </h3>

                                <p>
                                    Basic applicant details
                                </p>

                            </div>

                            <div class="section-number">
                                01
                            </div>

                        </div>


                        <div class="info-grid">

                            <div class="info-item">

                                <label>
                                    Full Name
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['full_name'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Date of Birth
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['dob'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Gender
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['gender'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Location
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['location'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- PROFESSIONAL INFORMATION -->

                    <section class="card section-card">

                        <div class="section-head">

                            <div>

                                <h3>
                                    Professional Information
                                </h3>

                                <p>
                                    Teaching qualifications and expertise
                                </p>

                            </div>

                            <div class="section-number">
                                02
                            </div>

                        </div>


                        <div class="info-grid">

                            <div class="info-item">

                                <label>
                                    Institution
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['institution'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Qualification
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['qualification'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Teaching Experience
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['teaching_experience'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Curriculum
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['curricula'] ?? '—',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item full">

                                <label>
                                    Subjects
                                </label>

                                <div class="tag-list">

                                    <?php
                                    $subjectsText =
                                        trim(
                                            $application['subjects'] ?? ''
                                        );

                                    $subjectItems =
                                        $subjectsText !== ''
                                            ? preg_split(
                                                '/\s*,\s*/',
                                                $subjectsText
                                            )
                                            : [];
                                    ?>

                                    <?php if ($subjectItems): ?>

                                        <?php foreach ($subjectItems as $subject): ?>

                                            <span class="tag">
                                                <?= htmlspecialchars(
                                                    $subject,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </span>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <span class="tag">
                                            Not provided
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- AVAILABILITY -->

                    <section class="card section-card">

                        <div class="section-head">

                            <div>

                                <h3>
                                    Teaching Availability
                                </h3>

                                <p>
                                    Applicant's preferred teaching schedule
                                </p>

                            </div>

                            <div class="section-number">
                                03
                            </div>

                        </div>


                        <div class="info-grid">

                            <div class="info-item">

                                <label>
                                    Preferred Days
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['preferred_days'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>


                            <div class="info-item">

                                <label>
                                    Preferred Times
                                </label>

                                <div class="value">
                                    <?= htmlspecialchars(
                                        $application['preferred_times'] ?? 'Not provided',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- PROFESSIONAL STATEMENT -->

                    <section class="card section-card">

                        <div class="section-head">

                            <div>

                                <h3>
                                    Professional Statement
                                </h3>

                                <p>
                                    Applicant's statement and teaching approach
                                </p>

                            </div>

                            <div class="section-number">
                                04
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="value">
                                <?= nl2br(
                                    htmlspecialchars(
                                        $application['professional_statement'] ?? 'No statement provided.',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>
                            </div>

                        </div>

                    </section>


                    <!-- DOCUMENTS -->

                    <section class="card section-card">

                        <div class="section-head">

                            <div>

                                <h3>
                                    Supporting Documents
                                </h3>

                                <p>
                                    Submitted application documents
                                </p>

                            </div>

                            <div class="section-number">
                                05
                            </div>

                        </div>


                        <div class="document-grid">

                            <?php
                            $cv =
                                trim(
                                    $application['cv_filename'] ?? ''
                                );
                            ?>

                            <div class="document">

                                <div class="document-left">

                                    <div class="document-icon">
                                        📄
                                    </div>

                                    <div class="document-name">

                                        <strong>
                                            Curriculum Vitae
                                        </strong>

                                        <span>
                                            <?= $cv !== ''
                                                ? htmlspecialchars(
                                                    $cv,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : 'No CV uploaded'
                                            ?>
                                        </span>

                                    </div>

                                </div>

                                <?php if ($cv !== ''): ?>

                                    <a
                                        class="btn-small"
                                        target="_blank"
                                        href="../teacher/uploads/teacher_applications/cv/<?= rawurlencode($cv) ?>"
                                    >
                                        View
                                    </a>

                                <?php endif; ?>

                            </div>


                            <div class="document">

                                <div class="document-left">

                                    <div class="document-icon">
                                        🖼️
                                    </div>

                                    <div class="document-name">

                                        <strong>
                                            Profile Photograph
                                        </strong>

                                        <span>
                                            <?= $photo !== ''
                                                ? htmlspecialchars(
                                                    $photo,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                                : 'No photo uploaded'
                                            ?>
                                        </span>

                                    </div>

                                </div>

                                <?php if ($photo !== ''): ?>

                                    <a
                                        class="btn-small"
                                        target="_blank"
                                        href="<?= htmlspecialchars(
                                            $photoUrl,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >
                                        View
                                    </a>

                                <?php endif; ?>

                            </div>

                        </div>

                    </section>


                    <!-- ACTION -->

                    <?php if (
                        strtolower(
                            trim(
                                $application['application_status']
                                ?? ''
                            )
                        ) !== 'approved'
                        &&
                        strtolower(
                            trim(
                                $application['application_status']
                                ?? ''
                            )
                        ) !== 'rejected'
                    ): ?>

                        <section class="card action-card">

                            <div class="action-copy">

                                <h3>
                                    Ready to make a decision?
                                </h3>

                                <p>
                                    Approving creates the teacher account
                                    and sends the login credentials by email.
                                </p>

                            </div>


                            <div class="actions">

                                <button
                                    type="button"
                                    class="btn btn-danger"
                                    onclick="openRejectModal()"
                                >
                                    ✕ Reject Application
                                </button>


                                <form
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="
                                        return confirm(
                                            'Approve this teacher application? A teacher account will be created and login credentials will be emailed to the applicant.'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="application_id"
                                        value="<?= (int)$application_id ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                    >
                                        ✓ Approve & Email Credentials
                                    </button>

                                </form>

                            </div>

                        </section>

                    <?php else: ?>

                        <section class="card action-card">

                            <div class="action-copy">

                                <h3>
                                    Application decision completed
                                </h3>

                                <p>
                                    This application has already been
                                    <?= htmlspecialchars(
                                        strtolower($status),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>.
                                </p>

                            </div>

                            <div class="actions">

                                <a
                                    href="teacher_applications.php"
                                    class="btn btn-neutral"
                                >
                                    ← Back to Applications
                                </a>

                            </div>

                        </section>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </main>

</div>


<!-- =========================================================
     REJECT MODAL
========================================================== -->

<div
    class="modal"
    id="rejectModal"
    onclick="closeRejectModal(event)"
>

    <div
        class="modal-card"
        onclick="event.stopPropagation()"
    >

        <h3>
            Reject Teacher Application?
        </h3>

        <p>
            This will mark the application as rejected.
            The applicant will not receive teacher login
            credentials.
        </p>


        <form method="POST">

            <input
                type="hidden"
                name="application_id"
                value="<?= (int)$application_id ?>"
            >

            <input
                type="hidden"
                name="action"
                value="reject"
            >


            <div class="modal-actions">

                <button
                    type="button"
                    class="btn btn-neutral"
                    onclick="closeRejectModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn btn-danger"
                >
                    Confirm Rejection
                </button>

            </div>

        </form>

    </div>

</div>


<script>

function openRejectModal(){

    document
        .getElementById("rejectModal")
        .classList
        .add("open");

}

function closeRejectModal(event){

    if (
        event &&
        event.target !==
        document.getElementById("rejectModal")
    ){

        return;

    }

    document
        .getElementById("rejectModal")
        .classList
        .remove("open");

}

document.addEventListener(
    "keydown",
    function(event){

        if(event.key === "Escape"){

            closeRejectModal();

        }

    }
);

</script>

</body>
</html>
