<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";
$message_type = "";


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
           SUCCESS
        ================================================= */

        $message_type =
            "success";

        $message =
            "Teacher approved successfully! "
            . "Teacher ID: "
            . $teacher_id
            . " | Temporary Password: "
            . $temporary_password;


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
