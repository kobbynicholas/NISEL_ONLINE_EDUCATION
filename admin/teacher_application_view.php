<?php

require "../admin_auth.php";
require "../config/db.php";


/* =========================================================
   GET APPLICATION ID
========================================================= */

$application_id = intval($_GET['id'] ?? 0);

if ($application_id <= 0) {
    die("Invalid application ID.");
}


/* =========================================================
   APPROVE / REJECT / PENDING APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? "";


    /* =====================================================
       APPROVE APPLICATION
    ===================================================== */

    if ($action === "approve") {

        /*
         * Get the application
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

            $message =
                "Teacher application not found.";

            $message_type =
                "error";

            $stmt->close();

        } else {

            $application =
                $result->fetch_assoc();

            $stmt->close();


            /* =============================================
               CHECK WHETHER ALREADY APPROVED
            ============================================= */

            if (
                $application['application_status']
                === "Approved"
            ) {

                $message =
                    "This application has already been approved.";

                $message_type =
                    "error";

            } else {


                /* =========================================
                   GET APPLICATION INFORMATION
                ========================================= */

                $teacher_name =
                    trim(
                        $application['full_name']
                    );

                $email =
                    trim(
                        $application['email']
                    );

                $phone =
                    trim(
                        $application['phone']
                    );

                $qualification =
                    trim(
                        $application['qualification']
                    );

                $subjects =
                    trim(
                        $application['subjects']
                    );

                $curriculum =
                    trim(
                        $application['curricula']
                    );

                $experience =
                    trim(
                        $application['teaching_experience']
                    );

                $bio =
                    trim(
                        $application['professional_statement']
                    );


                /*
                 * Your application table may contain
                 * preferred_days / preferred_times.
                 *
                 * We combine them into the teachers
                 * availability field.
                 */

                $preferred_days =
                    trim(
                        $application['preferred_days'] ?? ""
                    );

                $preferred_times =
                    trim(
                        $application['preferred_times'] ?? ""
                    );


                $availability = "";


                if (
                    $preferred_days !== "" &&
                    $preferred_times !== ""
                ) {

                    $availability =
                        $preferred_days .
                        " | " .
                        $preferred_times;

                } elseif (
                    $preferred_days !== ""
                ) {

                    $availability =
                        $preferred_days;

                } elseif (
                    $preferred_times !== ""
                ) {

                    $availability =
                        $preferred_times;

                }


                /* =========================================
                   PHOTO
                ========================================= */

                $photo =
                    trim(
                        $application['photo_filename'] ?? ""
                    );


                /* =========================================
                   CHECK EXISTING TEACHER EMAIL
                ========================================= */

                $check = $conn->prepare("
                    SELECT teacher_id
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
                        . "for this email. Teacher ID: "
                        . $existingTeacher['teacher_id'];

                    $message_type =
                        "error";


                } else {

                    $check->close();


                    /* =====================================
                       GENERATE TEACHER ID
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


                        $idCheck =
                            $conn->prepare("
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
                        random_int(
                            1000,
                            9999
                        );


                    /*
                     * NEVER store the password as plain text.
                     */

                    $password_hash =
                        password_hash(
                            $temporary_password,
                            PASSWORD_DEFAULT
                        );


                    /* =====================================
                       CREATE TEACHER ACCOUNT
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
                       EXECUTE
                    ===================================== */

                    if ($teacher->execute()) {

                        $teacher->close();


                        /* =================================
                           UPDATE APPLICATION
                        ================================= */

                        $update =
                            $conn->prepare("
                                UPDATE teacher_applications
                                SET application_status = 'Approved'
                                WHERE id = ?
                            ");

                        $update->bind_param(
                            "i",
                            $application_id
                        );

                        $update->execute();

                        $update->close();


                        /* =================================
                           SUCCESS
                        ================================= */

                        $message =
                            "Teacher account created successfully!"
                            . " Teacher ID: "
                            . $teacher_id
                            . " | Temporary Password: "
                            . $temporary_password;

                        $message_type =
                            "success";


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
                "error";

        } else {

            $message =
                "Unable to reject the application.";

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
                "Unable to update the application.";

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

    $stmt->close();

    die("Teacher application not found.");

}

$application = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   VARIABLES
========================================================= */

$status =
    $application['application_status'];

$status_class =
    strtolower($status);


$photo_file =
    $application['photo_filename'];

$cv_file =
    $application['cv_filename'];


$photo_path =
    "../uploads/teacher_applications/photos/"
    . $photo_file;


$cv_path =
    "../uploads/teacher_applications/cv/"
    . $cv_file;


$photo_exists =
    !empty($photo_file) &&
    file_exists(
        __DIR__ .
        "/../uploads/teacher_applications/photos/"
        . $photo_file
    );


$cv_exists =
    !empty($cv_file) &&
    file_exists(
        __DIR__ .
        "/../uploads/teacher_applications/cv/"
        . $cv_file
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
Teacher Application |
NISEL ONLINE EDUCATION
</title>


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    font-family:
        Arial,
        sans-serif;

    background:
        #eef3f8;

    color: #333;

}


/* =========================================================
   HEADER
========================================================= */

.header {

    background:
        #003366;

    color: white;

    padding: 20px 30px;

}


.header h1 {

    margin: 0;

    font-size: 25px;

}


.header p {

    margin:
        7px 0 0;

    color:
        #dceaf6;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 94%;

    max-width: 1100px;

    margin:
        30px auto;

}


/* =========================================================
   TOP BAR
========================================================= */

.top-bar {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    margin-bottom: 20px;

}


.back-button {

    background:
        #003366;

    color: white;

    text-decoration: none;

    padding:
        10px 18px;

    border-radius:
        6px;

}


.back-button:hover {

    background:
        #0055a5;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding:
        15px 18px;

    border-radius:
        7px;

    margin-bottom:
        20px;

    font-weight:
        bold;

}


.message.success {

    background:
        #d4edda;

    color:
        #155724;

}


.message.error {

    background:
        #f8d7da;

    color:
        #721c24;

}


/* =========================================================
   PROFILE HEADER
========================================================= */

.profile-header {

    background:
        white;

    border-radius:
        12px;

    padding:
        30px;

    display:
        flex;

    align-items:
        center;

    gap:
        25px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.08);

    margin-bottom:
        20px;

}


.profile-photo {

    width:
        130px;

    height:
        130px;

    border-radius:
        50%;

    object-fit:
        cover;

    border:
        4px solid #e1e7ed;

}


.no-photo {

    width:
        130px;

    height:
        130px;

    border-radius:
        50%;

    background:
        #e8eef4;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        50px;

}


.profile-info h2 {

    margin:
        0 0 8px;

    color:
        #003366;

    font-size:
        28px;

}


.reference {

    color:
        #777;

    margin-bottom:
        10px;

}


.status {

    display:
        inline-block;

    padding:
        7px 15px;

    border-radius:
        20px;

    font-size:
        13px;

    font-weight:
        bold;

}


.status.pending {

    background:
        #fff3cd;

    color:
        #856404;

}


.status.approved {

    background:
        #d4edda;

    color:
        #155724;

}


.status.rejected {

    background:
        #f8d7da;

    color:
        #721c24;

}


/* =========================================================
   CARD
========================================================= */

.card {

    background:
        white;

    border-radius:
        12px;

    padding:
        28px;

    margin-bottom:
        20px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.07);

}


.card h2 {

    color:
        #003366;

    font-size:
        20px;

    margin:
        0 0 20px;

    border-bottom:
        2px solid #eee;

    padding-bottom:
        10px;

}


/* =========================================================
   INFORMATION GRID
========================================================= */

.info-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap:
        18px 30px;

}


.info-item {

    border-bottom:
        1px solid #eee;

    padding-bottom:
        12px;

}


.info-label {

    font-size:
        12px;

    color:
        #777;

    margin-bottom:
        5px;

    text-transform:
        uppercase;

    font-weight:
        bold;

}


.info-value {

    font-size:
        15px;

    color:
        #333;

    line-height:
        1.6;

}


/* =========================================================
   STATEMENT
========================================================= */

.statement {

    background:
        #f6f9fc;

    border-left:
        4px solid #003366;

    padding:
        18px;

    line-height:
        1.8;

    white-space:
        pre-wrap;

}


/* =========================================================
   DOCUMENTS
========================================================= */

.documents {

    display:
        flex;

    gap:
        15px;

    flex-wrap:
        wrap;

}


.document-button {

    display:
        inline-block;

    padding:
        12px 20px;

    border-radius:
        6px;

    text-decoration:
        none;

    color:
        white;

    background:
        #003366;

}


.document-button:hover {

    background:
        #0055a5;

}


.document-disabled {

    color:
        #888;

    background:
        #eee;

    padding:
        12px 20px;

    border-radius:
        6px;

}


/* =========================================================
   ACTIONS
========================================================= */

.actions {

    background:
        white;

    border-radius:
        12px;

    padding:
        25px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.07);

    display:
        flex;

    gap:
        12px;

    flex-wrap:
        wrap;

}


.action-button {

    border:
        none;

    padding:
        13px 24px;

    border-radius:
        6px;

    color:
        white;

    cursor:
        pointer;

    font-size:
        14px;

    font-weight:
        bold;

}


.approve {

    background:
        #198754;

}


.reject {

    background:
        #dc3545;

}


.pending-button {

    background:
        #f0ad4e;

}


.action-button:hover {

    opacity:
        .85;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:
        center;

    color:
        #777;

    padding:
        25px;

    font-size:
        13px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width: 700px) {

    .profile-header {

        flex-direction:
            column;

        text-align:
            center;

    }


    .info-grid {

        grid-template-columns:
            1fr;

    }


    .top-bar {

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            15px;

    }


    .profile-photo,
    .no-photo {

        width:
            110px;

        height:
            110px;

    }

}

</style>

</head>


<body>


<!-- =======================================================
     HEADER
======================================================= -->

<div class="header">

    <h1>
        NISEL ONLINE EDUCATION
    </h1>

    <p>
        Teacher Application Review
    </p>

</div>



<div class="container">


<!-- =======================================================
     TOP BAR
======================================================= -->

<div class="top-bar">

<a
href="teacher_applications.php"
class="back-button"
>

← Back to Applications

</a>

</div>



<!-- =======================================================
     MESSAGE
======================================================= -->

<?php if ($message !== ""): ?>

<div class="message
<?php echo htmlspecialchars(
    $message_type
);
?>">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>



<!-- =======================================================
     PROFILE HEADER
======================================================= -->

<div class="profile-header">


<?php if ($photo_exists): ?>

<img
src="<?php
echo htmlspecialchars(
    $photo_path
);
?>"
class="profile-photo"
alt="Teacher Photograph"
>

<?php else: ?>

<div class="no-photo">

👤

</div>

<?php endif; ?>



<div class="profile-info">

<h2>

<?php

echo htmlspecialchars(
    $application['full_name']
);

?>

</h2>


<div class="reference">

Application Reference:

<strong>

<?php

echo htmlspecialchars(
    $application[
        'application_reference'
    ]
);

?>

</strong>

</div>


<span class="status
<?php
echo $status_class;
?>">

<?php

echo htmlspecialchars(
    $status
);

?>

</span>

</div>


</div>



<!-- =======================================================
     PERSONAL INFORMATION
======================================================= -->

<div class="card">

<h2>
Personal Information
</h2>


<div class="info-grid">


<div class="info-item">

<div class="info-label">
Full Name
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application['full_name']
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Date of Birth
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application['dob']
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Gender
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application['gender']
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Phone
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application['phone']
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Email
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application['email']
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Location
</div>

<div class="info-value">

<?php

echo !empty(
    $application['location']
)
    ? htmlspecialchars(
        $application['location']
    )
    : "Not provided";

?>

</div>

</div>


</div>

</div>



<!-- =======================================================
     PROFESSIONAL INFORMATION
======================================================= -->

<div class="card">

<h2>
Professional Information
</h2>


<div class="info-grid">


<div class="info-item">

<div class="info-label">
Institution / School
</div>

<div class="info-value">

<?php

echo !empty(
    $application['institution']
)
    ? htmlspecialchars(
        $application['institution']
    )
    : "Not provided";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Highest Qualification
</div>

<div class="info-value">

<?php

echo htmlspecialchars(
    $application[
        'qualification'
    ]
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Teaching Experience
</div>

<div class="info-value">

<?php

echo !empty(
    $application[
        'teaching_experience'
    ]
)
    ? htmlspecialchars(
        $application[
            'teaching_experience'
        ]
    )
    : "Not provided";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Application Date
</div>

<div class="info-value">

<?php

echo date(
    "d F Y, h:i A",
    strtotime(
        $application[
            'application_date'
        ]
    )
);

?>

</div>

</div>


</div>

</div>



<!-- =======================================================
     CURRICULUM AND SUBJECTS
======================================================= -->

<div class="card">

<h2>
Teaching Information
</h2>


<div class="info-grid">


<div class="info-item">

<div class="info-label">
Curriculum(s)
</div>

<div class="info-value">

<?php

echo nl2br(
    htmlspecialchars(
        $application[
            'curricula'
        ]
    )
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Subjects
</div>

<div class="info-value">

<?php

echo nl2br(
    htmlspecialchars(
        $application[
            'subjects'
        ]
    )
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Classes / Years / Grades
</div>

<div class="info-value">

<?php

echo nl2br(
    htmlspecialchars(
        $application[
            'classes_taught'
        ]
    )
);

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Preferred Teaching Days
</div>

<div class="info-value">

<?php

echo !empty(
    $application[
        'preferred_days'
    ]
)
    ? nl2br(
        htmlspecialchars(
            $application[
                'preferred_days'
            ]
        )
    )
    : "Not provided";

?>

</div>

</div>


<div class="info-item">

<div class="info-label">
Preferred Teaching Times
</div>

<div class="info-value">

<?php

echo !empty(
    $application[
        'preferred_times'
    ]
)
    ? htmlspecialchars(
        $application[
            'preferred_times'
        ]
    )
    : "Not provided";

?>

</div>

</div>


</div>

</div>



<!-- =======================================================
     PROFESSIONAL STATEMENT
======================================================= -->

<div class="card">

<h2>
Professional Statement
</h2>


<div class="statement">

<?php

echo htmlspecialchars(
    $application[
        'professional_statement'
    ]
);

?>

</div>

</div>



<!-- =======================================================
     DOCUMENTS
======================================================= -->

<div class="card">

<h2>
Supporting Documents
</h2>


<div class="documents">


<?php if ($cv_exists): ?>

<a
href="<?php
echo htmlspecialchars(
    $cv_path
);
?>"
target="_blank"
class="document-button"
>

View / Download CV

</a>

<?php else: ?>

<span class="document-disabled">

No CV uploaded

</span>

<?php endif; ?>



<?php if ($photo_exists): ?>

<a
href="<?php
echo htmlspecialchars(
    $photo_path
);
?>"
target="_blank"
class="document-button"
>

View Photograph

</a>

<?php endif; ?>


</div>

</div>



<!-- =======================================================
     ADMIN ACTIONS
======================================================= -->

<div class="actions">


<?php if ($status !== "Approved"): ?>

<form
method="POST"
>

<button
type="submit"
name="action"
value="approve"
class="action-button approve"
onclick="
return confirm(
'Are you sure you want to approve this teacher application?'
);
"
>

✓ Approve Application

</button>

</form>

<?php endif; ?>



<?php if ($status !== "Rejected"): ?>

<form
method="POST"
>

<button
type="submit"
name="action"
value="reject"
class="action-button reject"
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



<?php if ($status !== "Pending"): ?>

<form
method="POST"
>

<button
type="submit"
name="action"
value="pending"
class="action-button pending-button"
>

↻ Return to Pending

</button>

</form>

<?php endif; ?>


</div>


</div>



<!-- =======================================================
     FOOTER
======================================================= -->

<div class="footer">

© <?php echo date("Y"); ?>

NISEL ONLINE EDUCATION

<br>

Teacher Application Management

</div>


</body>

</html>


<?php

$conn->close();

?>
