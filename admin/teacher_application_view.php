<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";
$message_type = "";


/* =========================================================
   GET APPLICATION ID
========================================================= */

$application_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : intval($_POST['application_id'] ?? 0);


if ($application_id <= 0) {

    die("Invalid teacher application.");

}


/* =========================================================
   APPROVE / REJECT APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";


    /* =====================================================
       APPROVE APPLICATION
    ===================================================== */

    if ($action === "approve") {

        /*
         * Get application
         */

        $stmt = $conn->prepare("
            SELECT *
            FROM teacher_applications
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $application_id
        );

        $stmt->execute();

        $result = $stmt->get_result();


        if ($result->num_rows === 0) {

            $message = "Teacher application not found.";
            $message_type = "error";

            $stmt->close();

        } else {

            $application = $result->fetch_assoc();

            $stmt->close();


            /* =============================================
               CHECK IF ALREADY APPROVED
            ============================================= */

            if (
                strtolower(
                    trim($application['application_status'])
                ) === "approved"
            ) {

                $message =
                    "This teacher application has already been approved.";

                $message_type = "error";

            } else {


                /* =========================================
                   GET APPLICATION DATA
                ========================================= */

                $teacher_name =
                    trim($application['full_name']);

                $phone =
                    trim($application['phone']);

                $email =
                    trim($application['email']);

                $qualification =
                    trim($application['qualification']);

                $subjects =
                    trim($application['subjects']);

                $curriculum =
                    trim($application['curricula']);

                $experience =
                    trim($application['teaching_experience']);

                $bio =
                    trim($application['professional_statement']);


                /* =========================================
                   CREATE AVAILABILITY
                ========================================= */

                $preferred_days =
                    trim($application['preferred_days'] ?? "");

                $preferred_times =
                    trim($application['preferred_times'] ?? "");


                if (
                    $preferred_days !== "" &&
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


                /* =========================================
                   PHOTO
                ========================================= */

                $photo =
                    trim($application['photo_filename'] ?? "");


                /* =========================================
                   CHECK EXISTING EMAIL
                ========================================= */

                $check = $conn->prepare("
                    SELECT id, teacher_id
                    FROM teachers
                    WHERE email = ?
                    LIMIT 1
                ");

                $check->bind_param(
                    "s",
                    $email
                );

                $check->execute();

                $existing =
                    $check->get_result();


                if ($existing->num_rows > 0) {

                    $existingTeacher =
                        $existing->fetch_assoc();

                    $check->close();


                    $message =
                        "A teacher account already exists "
                        . "with this email. Teacher ID: "
                        . $existingTeacher['teacher_id'];

                    $message_type = "error";


                } else {

                    $check->close();


                    /* =====================================
                       GENERATE UNIQUE TEACHER ID
                    ===================================== */

                    do {

                        $teacher_id =
                            "NISEL-T-" .
                            date("Y") .
                            "-" .
                            strtoupper(
                                substr(
                                    bin2hex(
                                        random_bytes(4)
                                    ),
                                    0,
                                    6
                                )
                            );


                        $idCheck = $conn->prepare("
                            SELECT id
                            FROM teachers
                            WHERE teacher_id = ?
                            LIMIT 1
                        ");

                        $idCheck->bind_param(
                            "s",
                            $teacher_id
                        );

                        $idCheck->execute();

                        $idResult =
                            $idCheck->get_result();

                        $idExists =
                            $idResult->num_rows > 0;

                        $idCheck->close();

                    } while ($idExists);


                    /* =====================================
                       GENERATE TEMPORARY PASSWORD
                    ===================================== */

                    $temporary_password =
                        "Nisel@" .
                        random_int(1000, 9999);


                    /* =====================================
                       HASH PASSWORD
                    ===================================== */

                    $password_hash =
                        password_hash(
                            $temporary_password,
                            PASSWORD_DEFAULT
                        );


                    /* =====================================
                       INSERT TEACHER
                    ===================================== */

                    $teacher = $conn->prepare("
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
                            'Active'
                        )
                    ");


                    if (!$teacher) {

                        die(
                            "Teacher INSERT preparation failed: "
                            . htmlspecialchars($conn->error)
                        );

                    }


                    $teacher->bind_param(
                        "ssssssssssss",
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
                        $password_hash
                    );


                    /* =====================================
                       INSERT ACCOUNT
                    ===================================== */

                    if ($teacher->execute()) {

                        $teacher->close();


                        /* =================================
                           UPDATE APPLICATION
                        ================================= */

                        $update = $conn->prepare("
                            UPDATE teacher_applications
                            SET application_status = 'Approved'
                            WHERE id = ?
                        ");


                        if (!$update) {

                            die(
                                "Application update preparation failed: "
                                . htmlspecialchars($conn->error)
                            );

                        }


                        $update->bind_param(
                            "i",
                            $application_id
                        );


                        if ($update->execute()) {

                            $message =
                                "Teacher application approved successfully.";

                            $message_type =
                                "success";


                        } else {

                            $message =
                                "Teacher account was created, "
                                . "but application status could not be updated.";

                            $message_type =
                                "error";

                        }


                        $update->close();


                    } else {

                        $message =
                            "Unable to create teacher account: "
                            . $teacher->error;

                        $message_type =
                            "error";

                        $teacher->close();

                    }

                }

            }

        }

    }


    /* =====================================================
       REJECT APPLICATION
    ===================================================== */

    elseif ($action === "reject") {

        $stmt = $conn->prepare("
            UPDATE teacher_applications
            SET application_status = 'Rejected'
            WHERE id = ?
        ");


        $stmt->bind_param(
            "i",
            $application_id
        );


        if ($stmt->execute()) {

            $message =
                "Teacher application rejected.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to reject application.";

            $message_type =
                "error";

        }


        $stmt->close();

    }


    /* =====================================================
       RETURN TO PENDING
    ===================================================== */

    elseif ($action === "pending") {

        $stmt = $conn->prepare("
            UPDATE teacher_applications
            SET application_status = 'Pending'
            WHERE id = ?
        ");


        $stmt->bind_param(
            "i",
            $application_id
        );


        if ($stmt->execute()) {

            $message =
                "Application returned to Pending.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to update application.";

            $message_type =
                "error";

        }


        $stmt->close();

    }

}


/* =========================================================
   GET APPLICATION
========================================================= */

$stmt = $conn->prepare("
    SELECT *
    FROM teacher_applications
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $application_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    die("Teacher application not found.");

}


$application =
    $result->fetch_assoc();

$stmt->close();

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
value="<?php
echo $application_id;
?>"
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
