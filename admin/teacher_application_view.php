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

        /* =================================================
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

        $application = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$application) {

            throw new Exception(
                "Teacher application was not found."
            );

        }


        /* =================================================
           CHECK APPLICATION STATUS
        ================================================= */

        if (
            strtolower(
                trim(
                    $application['application_status'] ?? ''
                )
            ) === "approved"
        ) {

            throw new Exception(
                "This application has already been approved."
            );

        }


        /* =================================================
           GET APPLICATION DATA
        ================================================= */

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


        /*
        -----------------------------------------------------
        APPLICATION TABLE
        -----------------------------------------------------

        curriculum field is called:

            curricula

        TEACHERS TABLE

        curriculum field is called:

            curriculum
        */

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


        /* =================================================
           GET PHOTO

           IMPORTANT:
           Some records may use:

               photo_filename

           while others may use:

               photo

        ================================================= */

        $photo = "";


        if (
            isset($application['photo_filename'])
            &&
            trim($application['photo_filename']) !== ''
        ) {

            $photo =
                trim(
                    $application['photo_filename']
                );

        } elseif (
            isset($application['photo'])
            &&
            trim($application['photo']) !== ''
        ) {

            $photo =
                trim(
                    $application['photo']
                );

        }


        /*
        -----------------------------------------------------
        Remove directory information if it exists.
        This keeps only the actual filename.
        -----------------------------------------------------
        */

        if ($photo !== '') {

            $photo = basename($photo);

        }


        /* =================================================
           AVAILABILITY
        ================================================= */

        $preferred_days =
            trim(
                $application['preferred_days'] ?? ''
            );

        $preferred_times =
            trim(
                $application['preferred_times'] ?? ''
            );


        if (
            $preferred_days !== ""
            &&
            $preferred_times !== ""
        ) {

            $availability =
                $preferred_days
                . " | "
                . $preferred_times;

        } elseif ($preferred_days !== "") {

            $availability =
                $preferred_days;

        } elseif ($preferred_times !== "") {

            $availability =
                $preferred_times;

        } else {

            $availability = "";

        }


        /* =================================================
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
            $checkEmail->fetch(PDO::FETCH_ASSOC);


        if ($existingTeacher) {

            throw new Exception(
                "A teacher account already exists "
                . "with this email. Teacher ID: "
                . $existingTeacher['teacher_id']
            );

        }


        /* =================================================
           GENERATE UNIQUE TEACHER ID
        ================================================= */

        do {

            $teacher_id =
                "NISEL-T-"
                .
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
                $checkID->fetch(PDO::FETCH_ASSOC);

        } while ($teacherExists);


        /* =================================================
           GENERATE TEMPORARY PASSWORD
        ================================================= */

        $temporary_password =
            "Nisel@"
            .
            random_int(
                1000,
                9999
            );


        /* =================================================
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


        /* =================================================
           START TRANSACTION
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

            /*
            IMPORTANT:
            Correct photo filename is stored here.
            */
            $photo,

            $hashed_password,

            "Active"

        ]);


        /* =================================================
           VERIFY TEACHER WAS INSERTED
        ================================================= */

        $verifyTeacher = $pdo->prepare("
            SELECT
                id,
                teacher_id,
                photo
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
                "Teacher account was not found "
                . "after insertion."
            );

        }


        /* =================================================
           UPDATE APPLICATION STATUS
        ================================================= */

        $updateApplication = $pdo->prepare("
            UPDATE teacher_applications
            SET application_status = 'Approved'
            WHERE id = ?
        ");

        $updateApplication->execute([
            $application_id
        ]);


        /* =================================================
           COMMIT
        ================================================= */

        $pdo->commit();


        /* =================================================
           SUCCESS MESSAGE
        ================================================= */

        $message_type =
            "success";

        $message =
            "Teacher approved successfully! "
            .
            "Teacher ID: "
            .
            $teacher_id
            .
            " | Temporary Password: "
            .
            $temporary_password;


    } catch (Exception $e) {


        /* =================================================
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
            .
            $e->getMessage();

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
            .
            $e->getMessage();

        $message_type =
            "error";

    }

}


/* =========================================================
   RETURN REJECTED APPLICATION TO PENDING
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === "pending"
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
            "Application returned to pending.";

        $message_type =
            "success";


    } catch (PDOException $e) {

        $message =
            "Unable to return application to pending: "
            .
            $e->getMessage();

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
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$application) {

    die(
        "Teacher application not found."
    );

}


/* =========================================================
   DETERMINE PHOTO FILE
========================================================= */

/*
   The application may contain either:

       photo_filename

   or:

       photo

   We check both.
*/

$photoFile = "";


if (
    isset($application['photo_filename'])
    &&
    trim($application['photo_filename']) !== ''
) {

    $photoFile =
        trim(
            $application['photo_filename']
        );

} elseif (
    isset($application['photo'])
    &&
    trim($application['photo']) !== ''
) {

    $photoFile =
        trim(
            $application['photo']
        );

}


/*
-------------------------------------------------------------
Keep only the filename.
-------------------------------------------------------------
*/

if ($photoFile !== '') {

    $photoFile =
        basename($photoFile);

}


/* =========================================================
   FIND ACTUAL PHOTO
========================================================= */

$photoUrl = "";

$photoFound = false;


/*
-------------------------------------------------------------
POSSIBLE PHOTO LOCATIONS

Based on your current registration code, the first location
is the important one:

teacher/uploads/teachers/

We also check the older locations.
-------------------------------------------------------------
*/

$possiblePhotoPaths = [

    __DIR__
    . "/../teacher/uploads/teachers/"
    . $photoFile,


    __DIR__
    . "/../uploads/teachers/photos/"
    . $photoFile,


    __DIR__
    . "/../uploads/teachers/"
    . $photoFile

];


$possiblePhotoUrls = [

    "../teacher/uploads/teachers/"
    . rawurlencode($photoFile),


    "../uploads/teachers/photos/"
    . rawurlencode($photoFile),


    "../uploads/teachers/"
    . rawurlencode($photoFile)

];


if ($photoFile !== '') {

    foreach (
        $possiblePhotoPaths
        as $index => $physicalPath
    ) {

        if (
            is_file(
                $physicalPath
            )
        ) {

            $photoUrl =
                $possiblePhotoUrls[$index];

            $photoFound =
                true;

            break;

        }

    }

}


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

* {

    box-sizing:
        border-box;

}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #eef3f8;

    color:
        #333;

}


.container {

    width:
        95%;

    max-width:
        1000px;

    margin:
        30px auto;

}


.card {

    background:
        white;

    padding:
        30px;

    border-radius:
        12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.10);

}


h1 {

    color:
        #003366;

    margin-top:
        0;

}


h2 {

    color:
        #003366;

    border-bottom:
        1px solid #ddd;

    padding-bottom:
        10px;

}


.message {

    padding:
        15px;

    border-radius:
        7px;

    margin-bottom:
        20px;

}


.success {

    background:
        #d4edda;

    color:
        #155724;

}


.error {

    background:
        #f8d7da;

    color:
        #721c24;

}


.grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:
        20px;

}


.field {

    background:
        #f8f9fa;

    padding:
        15px;

    border-radius:
        7px;

    overflow-wrap:
        anywhere;

}


.field strong {

    display:
        block;

    color:
        #003366;

    margin-bottom:
        6px;

}


.full {

    grid-column:
        1 / -1;

}


.actions {

    display:
        flex;

    gap:
        10px;

    margin-top:
        30px;

    flex-wrap:
        wrap;

}


button {

    border:
        none;

    padding:
        12px 22px;

    border-radius:
        6px;

    color:
        white;

    cursor:
        pointer;

    font-size:
        15px;

}


.approve {

    background:
        #198754;

}


.reject {

    background:
        #dc3545;

}


.pending {

    background:
        #f0ad4e;

}


.back {

    display:
        inline-block;

    margin-bottom:
        20px;

    text-decoration:
        none;

    color:
        #003366;

    font-weight:
        bold;

}


.photo-container {

    text-align:
        center;

    padding:
        20px;

}


.photo {

    width:
        180px;

    height:
        180px;

    object-fit:
        cover;

    border-radius:
        12px;

    border:
        3px solid #003366;

    display:
        block;

    margin:
        15px auto;

}


.no-photo {

    width:
        180px;

    height:
        180px;

    margin:
        15px auto;

    border-radius:
        12px;

    background:
        #e9ecef;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        70px;

    color:
        #6c757d;

    border:
        2px dashed #adb5bd;

}


.photo-error {

    background:
        #fff3cd;

    color:
        #856404;

    padding:
        12px;

    border-radius:
        7px;

    max-width:
        500px;

    margin:
        10px auto;

    font-size:
        14px;

}


.status {

    display:
        inline-block;

    padding:
        6px 12px;

    border-radius:
        20px;

    background:
        #eee;

}


.status-approved {

    background:
        #d4edda;

    color:
        #155724;

}


.status-rejected {

    background:
        #f8d7da;

    color:
        #721c24;

}


.status-pending {

    background:
        #fff3cd;

    color:
        #856404;

}


.debug-photo {

    margin-top:
        10px;

    font-size:
        12px;

    color:
        #666;

    word-break:
        break-all;

}


@media(max-width:700px) {

    .grid {

        grid-template-columns:
            1fr;

    }

    .card {

        padding:
            20px;

    }

    .actions {

        flex-direction:
            column;

    }

    button {

        width:
            100%;

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
    <?php
        echo $message_type === 'success'
            ? 'success'
            : 'error';
    ?>"
>

    <?= h($message) ?>

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

<?= h(
    $application['application_reference']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Application Status
</strong>

<?php

$status =
    strtolower(
        trim(
            $application['application_status']
            ?? ''
        )
    );

$statusClass = '';

if ($status === 'approved') {

    $statusClass =
        'status-approved';

} elseif ($status === 'rejected') {

    $statusClass =
        'status-rejected';

} elseif ($status === 'pending') {

    $statusClass =
        'status-pending';

}

?>

<span
    class="status <?= h($statusClass) ?>"
>

<?= h(
    $application['application_status']
    ?? ''
) ?>

</span>

</div>


<div class="field">

<strong>
    Full Name
</strong>

<?= h(
    $application['full_name']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Date of Birth
</strong>

<?= h(
    $application['dob']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Gender
</strong>

<?= h(
    $application['gender']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Phone
</strong>

<?= h(
    $application['phone']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Email
</strong>

<?= h(
    $application['email']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Location
</strong>

<?= h(
    $application['location']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Institution
</strong>

<?= h(
    $application['institution']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Qualification
</strong>

<?= h(
    $application['qualification']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Teaching Experience
</strong>

<?= h(
    $application['teaching_experience']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Curriculum
</strong>

<?= h(
    $application['curricula']
    ?? ''
) ?>

</div>


<div class="field full">

<strong>
    Subjects
</strong>

<?= h(
    $application['subjects']
    ?? ''
) ?>

</div>


<div class="field full">

<strong>
    Classes Taught
</strong>

<?= h(
    $application['classes_taught']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Preferred Days
</strong>

<?= h(
    $application['preferred_days']
    ?? ''
) ?>

</div>


<div class="field">

<strong>
    Preferred Times
</strong>

<?= h(
    $application['preferred_times']
    ?? ''
) ?>

</div>


<div class="field full">

<strong>
    Professional Statement
</strong>

<?= nl2br(
    h(
        $application['professional_statement']
        ?? ''
    )
) ?>

</div>


<!-- =====================================================
     CV
===================================================== -->

<div class="field full">

<strong>
    CV
</strong>


<?php if (
    !empty(
        $application['cv_filename']
    )
): ?>

<a
    href="../uploads/teachers/cv/<?= rawurlencode(
        basename(
            $application['cv_filename']
        )
    ) ?>"
    target="_blank"
    rel="noopener noreferrer"
>

    📄 View CV

</a>


<?php else: ?>

    No CV uploaded.

<?php endif; ?>


</div>


<!-- =====================================================
     TEACHER PHOTO
===================================================== -->

<div class="field full">

<strong>
    Teacher Photo
</strong>


<div class="photo-container">


<?php if ($photoFound): ?>


<img
    src="<?= h($photoUrl) ?>"
    class="photo"
    alt="Teacher Photo"
    onerror="
        this.style.display='none';
        document.getElementById('photoError').style.display='block';
    "
>


<div
    id="photoError"
    class="photo-error"
    style="display:none;"
>

    ⚠️ The photo file could not be loaded.

</div>


<?php else: ?>


<div class="no-photo">
    👤
</div>


<div class="photo-error">

    <?php if ($photoFile !== ''): ?>

        ⚠️ Photo filename found:

        <strong>
            <?= h($photoFile) ?>
        </strong>

        <br><br>

        But the actual image file could not be found
        in the expected upload folders.

    <?php else: ?>

        ⚠️ No teacher photo was saved
        with this application.

    <?php endif; ?>

</div>


<?php endif; ?>


<?php if ($photoFile !== ''): ?>

<div class="debug-photo">

    Stored photo filename:
    <strong>
        <?= h($photoFile) ?>
    </strong>

</div>

<?php endif; ?>


</div>

</div>


</div>


<!-- =====================================================
     ZOOM LINK
===================================================== -->

<div class="field full">

<strong>
    Zoom Meeting Link
</strong>


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
>

    🎥 Open Zoom Link

</a>


<?php else: ?>

    No Zoom link provided.

<?php endif; ?>


</div>


</div>


<!-- =====================================================
     ACTION BUTTONS
===================================================== -->

<?php

$currentStatus =
    strtolower(
        trim(
            $application['application_status']
            ?? ''
        )
    );

?>


<div class="actions">


<?php if (
    $currentStatus !== "approved"
): ?>


<form method="POST">

<input
    type="hidden"
    name="application_id"
    value="<?= h($application_id) ?>"
>


<button
    type="submit"
    name="action"
    value="approve"
    class="approve"
    onclick="
        return confirm(
            'Are you sure you want to approve this teacher application?'
        );
    "
>

    ✓ Approve Application

</button>

</form>


<form method="POST">

<input
    type="hidden"
    name="application_id"
    value="<?= h($application_id) ?>"
>


<button
    type="submit"
    name="action"
    value="reject"
    class="reject"
    onclick="
        return confirm(
            'Are you sure you want to reject this teacher application?'
        );
    "
>

    ✕ Reject Application

</button>

</form>


<?php endif; ?>


<?php if (
    $currentStatus === "rejected"
): ?>


<form method="POST">

<input
    type="hidden"
    name="application_id"
    value="<?= h($application_id) ?>"
>


<button
    type="submit"
    name="action"
    value="pending"
    class="pending"
>

    ↻ Return to Pending

</button>

</form>


<?php endif; ?>


</div>


</div>


</div>


</body>

</html>
