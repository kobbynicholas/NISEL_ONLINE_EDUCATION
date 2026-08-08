<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";
$message_type = "";

$application_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : intval($_POST['application_id'] ?? 0);

if ($application_id <= 0) {
    die("Invalid application ID.");
}


/* =========================================================
   APPROVE APPLICATION
========================================================= */

$isApproveRequest =
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    (
        isset($_POST['approve'])
        ||
        (
            isset($_POST['action'])
            &&
            $_POST['action'] === 'approve'
        )
    );


if ($isApproveRequest) {

    /* =====================================================
       GET APPLICATION
    ===================================================== */

    $stmt = $conn->prepare("
        SELECT *
        FROM teacher_applications
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param(
        "i",
        $application_id
    );

    if (!$stmt->execute()) {
        die("Unable to retrieve application: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("Teacher application not found.");
    }

    $application = $result->fetch_assoc();

    $stmt->close();


    /* =====================================================
       CHECK IF ALREADY APPROVED
    ===================================================== */

    if (
        strtolower(
            trim($application['application_status'])
        ) === "approved"
    ) {

        $message =
            "This application has already been approved.";

        $message_type = "error";

    } else {


        /* =================================================
           GET APPLICATION DATA
        ================================================= */

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
            trim(
                $application['teaching_experience'] ?? ""
            );

        $bio =
            trim(
                $application['professional_statement']
            );

        $photo =
            trim(
                $application['photo_filename'] ?? ""
            );


        /* =================================================
           AVAILABILITY
        ================================================= */

        $preferred_days =
            trim(
                $application['preferred_days'] ?? ""
            );

        $preferred_times =
            trim(
                $application['preferred_times'] ?? ""
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
           CHECK EXISTING TEACHER
        ================================================= */

        $check = $conn->prepare("
            SELECT id, teacher_id
            FROM teachers
            WHERE email = ?
            LIMIT 1
        ");

        if (!$check) {
            die(
                "Email check failed: "
                . $conn->error
            );
        }

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
                . "for this email. Teacher ID: "
                . $existingTeacher['teacher_id'];

            $message_type = "error";

        } else {

            $check->close();


            /* =============================================
               GENERATE TEACHER ID
            ============================================= */

            $teacher_id =
                "NISEL-T-"
                . date("Y")
                . "-"
                . strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(4)
                        ),
                        0,
                        6
                    )
                );


            /* =============================================
               GENERATE PASSWORD
            ============================================= */

            $temporary_password =
                "Nisel@"
                . random_int(1000, 9999);


            /* =============================================
               HASH PASSWORD
            ============================================= */

            $password_hash =
                password_hash(
                    $temporary_password,
                    PASSWORD_DEFAULT
                );


            /* =============================================
               STATUS
            ============================================= */

            $status = "Active";


            /* =============================================
               INSERT TEACHER
            ============================================= */

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
                    ?
                )
            ");


            if (!$teacher) {

                die(
                    "Teacher INSERT failed: "
                    . $conn->error
                );

            }


            $teacher->bind_param(
                "sssssssssssss",
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
                $password_hash,
                $status
            );


            /* =============================================
               EXECUTE INSERT
            ============================================= */

            if (!$teacher->execute()) {

                $message =
                    "Teacher account was NOT created. "
                    . "MySQL says: "
                    . $teacher->error;

                $message_type = "error";

                $teacher->close();

            } else {


                /* =========================================
                   TEACHER SUCCESSFULLY CREATED
                ========================================= */

                $new_teacher_id =
                    $teacher->insert_id;

                $teacher->close();


                /* =========================================
                   UPDATE APPLICATION
                ========================================= */

                $update = $conn->prepare("
                    UPDATE teacher_applications
                    SET application_status = 'Approved'
                    WHERE id = ?
                ");


                if (!$update) {

                    $message =
                        "Teacher account created, "
                        . "but application could not be updated: "
                        . $conn->error;

                    $message_type = "error";

                } else {

                    $update->bind_param(
                        "i",
                        $application_id
                    );

                    if ($update->execute()) {

                        $message =
                            "Teacher approved successfully! "
                            . "Teacher ID: "
                            . $teacher_id
                            . " | Temporary Password: "
                            . $temporary_password;

                        $message_type = "success";

                    } else {

                        $message =
                            "Teacher account was created, "
                            . "but application status could not "
                            . "be updated: "
                            . $update->error;

                        $message_type = "error";

                    }

                    $update->close();

                }

            }

        }

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

    $stmt = $conn->prepare("
        UPDATE teacher_applications
        SET application_status = 'Rejected'
        WHERE id = ?
    ");

    if (!$stmt) {

        $message =
            "Database error: "
            . $conn->error;

        $message_type = "error";

    } else {

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
                "Unable to reject application: "
                . $stmt->error;

            $message_type =
                "error";

        }

        $stmt->close();

    }

}


/* =========================================================
   GET APPLICATION FOR DISPLAY
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

$result =
    $stmt->get_result();


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
        value="<?php echo $application_id; ?>"
    >

    <button
        type="submit"
        name="action"
        value="approve"
        class="approve"
        onclick="return confirm('Are you sure you want to approve this teacher application?');"
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
