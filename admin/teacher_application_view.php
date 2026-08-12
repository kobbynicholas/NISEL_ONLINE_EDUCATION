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
                    "info@niseleducation.online";

                $mail->Password =
                    "YOUR_GMAIL_APP_PASSWORD";

                $mail->SMTPSecure =
                    \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

                $mail->Port =
                    587;


                $mail->setFrom(
                    "info@niseleducation.online",
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

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>
Teacher Application | NISEL ONLINE EDUCATION
</title>


<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:Arial,sans-serif;

    background:#eef3f8;

    color:#333;

}

.container{

    width:95%;

    max-width:1000px;

    margin:30px auto;

}

.card{

    background:white;

    padding:30px;

    border-radius:12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.1);

}

h1{

    color:#003366;

    margin-top:0;

}

h2{

    color:#003366;

    border-bottom:
        1px solid #ddd;

    padding-bottom:10px;

}

.message{

    padding:15px;

    border-radius:7px;

    margin-bottom:20px;

}

.success{

    background:#d4edda;

    color:#155724;

}

.error{

    background:#f8d7da;

    color:#721c24;

}

.grid{

    display:grid;

    grid-template-columns:
        repeat(2,1fr);

    gap:20px;

}

.field{

    background:#f8f9fa;

    padding:15px;

    border-radius:7px;

}

.field strong{

    display:block;

    color:#003366;

    margin-bottom:6px;

}

.full{

    grid-column:
        1 / -1;

}

.actions{

    display:flex;

    gap:10px;

    margin-top:30px;

    flex-wrap:wrap;

}

button{

    border:none;

    padding:12px 22px;

    border-radius:6px;

    color:white;

    cursor:pointer;

    font-size:15px;

}

.approve{

    background:#198754;

}

.reject{

    background:#dc3545;

}

.pending{

    background:#f0ad4e;

}

.back{

    display:inline-block;

    margin-bottom:20px;

    text-decoration:none;

    color:#003366;

    font-weight:bold;

}

.photo{

    width:150px;

    height:150px;

    object-fit:cover;

    border-radius:10px;

    border:1px solid #ddd;

}

.status{

    display:inline-block;

    padding:6px 12px;

    border-radius:20px;

    background:#eee;

}

@media(max-width:700px){

    .grid{

        grid-template-columns:1fr;

    }

}

</style>

</head>


<body>


<div class="container">


<a
href="teacher_applications.php"
class="back"
>
← Back to Teacher Applications
</a>


<div class="card">


<h1>
NISEL ONLINE EDUCATION
</h1>

<p>
Teacher Application Details
</p>


<?php if ($message !== ""): ?>

<div
class="message
<?php echo $message_type === 'success'
    ? 'success'
    : 'error'; ?>"
>

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<h2>
Applicant Information
</h2>


<div class="grid">


<div class="field">

<strong>
Application Reference
</strong>

<?php
echo htmlspecialchars(
    $application['application_reference']
);
?>

</div>


<div class="field">

<strong>
Application Status
</strong>

<span class="status">

<?php
echo htmlspecialchars(
    $application['application_status']
);
?>

</span>

</div>


<div class="field">

<strong>
Full Name
</strong>

<?php
echo htmlspecialchars(
    $application['full_name']
);
?>

</div>


<div class="field">

<strong>
Date of Birth
</strong>

<?php
echo htmlspecialchars(
    $application['dob']
);
?>

</div>


<div class="field">

<strong>
Gender
</strong>

<?php
echo htmlspecialchars(
    $application['gender']
);
?>

</div>


<div class="field">

<strong>
Phone
</strong>

<?php
echo htmlspecialchars(
    $application['phone']
);
?>

</div>


<div class="field">

<strong>
Email
</strong>

<?php
echo htmlspecialchars(
    $application['email']
);
?>

</div>


<div class="field">

<strong>
Location
</strong>

<?php
echo htmlspecialchars(
    $application['location'] ?? ""
);
?>

</div>


<div class="field">

<strong>
Institution
</strong>

<?php
echo htmlspecialchars(
    $application['institution'] ?? ""
);
?>

</div>


<div class="field">

<strong>
Qualification
</strong>

<?php
echo htmlspecialchars(
    $application['qualification']
);
?>

</div>


<div class="field">

<strong>
Teaching Experience
</strong>

<?php
echo htmlspecialchars(
    $application['teaching_experience'] ?? ""
);
?>

</div>


<div class="field">

<strong>
Curriculum
</strong>

<?php
echo htmlspecialchars(
    $application['curricula']
);
?>

</div>


<div class="field full">

<strong>
Subjects
</strong>

<?php
echo htmlspecialchars(
    $application['subjects']
);
?>

</div>


<div class="field full">

<strong>
Classes Taught
</strong>

<?php
echo htmlspecialchars(
    $application['classes_taught']
);
?>

</div>


<div class="field">

<strong>
Preferred Days
</strong>

<?php
echo htmlspecialchars(
    $application['preferred_days'] ?? ""
);
?>

</div>


<div class="field">

<strong>
Preferred Times
</strong>

<?php
echo htmlspecialchars(
    $application['preferred_times'] ?? ""
);
?>

</div>


<div class="field full">

<strong>
Professional Statement
</strong>

<?php
echo nl2br(
    htmlspecialchars(
        $application['professional_statement']
    )
);
?>

</div>


<div class="field full">

<strong>
CV

</strong>

<?php

if (
    !empty(
        $application['cv_filename']
    )
) {

?>

<a
href="../uploads/teachers/cv/<?php
echo rawurlencode(
    $application['cv_filename']
);
?>"
target="_blank"
>

View CV

</a>

<?php

} else {

echo "No CV uploaded.";

}

?>

</div>


<div class="field full">

<strong>
Photo
</strong>

<?php

if (
    !empty(
        $application['photo_filename']
    )
) {

?>

<br>

<img
src="../uploads/teachers/photos/<?php
echo rawurlencode(
    $application['photo_filename']
);
?>"
class="photo"
alt="Teacher Photo"
>

<?php

} else {

echo "No photo uploaded.";

}

?>

</div>


</div>




<div class="detail-item">

    <strong>
        Zoom Meeting Link
    </strong>

    <p>

        <?php if (!empty($application['zoom_link'])): ?>

            <a
                href="<?php
                    echo htmlspecialchars(
                        $application['zoom_link']
                    );
                ?>"
                target="_blank"
                rel="noopener noreferrer"
            >

                🎥 Open Zoom Link

            </a>

        <?php else: ?>

            <span>
                No Zoom link provided
            </span>

        <?php endif; ?>

    </p>

</div>




   
<!-- =====================================================
     ACTION BUTTONS
===================================================== -->

<?php

if (
    strtolower(
        trim(
            $application['application_status']
        )
    ) !== "approved"
) {

?>

<div class="actions">


<form method="POST">

    <input
        type="hidden"
        name="application_id"
        value="<?php echo $application_id; ?>"
    >

    <button
        type="submit"
        name="action"
        value="approve"
        class="approve"
    >
        ✓ Approve Application
    </button>

</form>


<form method="POST">

<input
type="hidden"
name="application_id"
value="<?php
echo $application_id;
?>"
>

<button
type="submit"
name="action"
value="reject"
class="reject"
onclick="
return confirm(
'Are you sure you want to reject this application?'
);
"
>

✕ Reject Application

</button>

</form>


<?php

}

if (
    strtolower(
        trim(
            $application['application_status']
        )
    ) === "rejected"
) {

?>

<form method="POST">

<input
type="hidden"
name="application_id"
value="<?php
echo $application_id;
?>"
>

<button
type="submit"
name="action"
value="pending"
class="pending"
>

Return to Pending

</button>

</form>

<?php

}

?>


</div>


</div>

</div>


</body>

</html>
