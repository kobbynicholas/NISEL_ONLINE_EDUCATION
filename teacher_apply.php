<?php

require __DIR__ . "/config/db.php";

/*
==================================================
INITIAL VARIABLES
==================================================
*/

$message = "";
$message_type = "";

$application_reference = "";


/*
==================================================
PROCESS APPLICATION
==================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    ==============================================
    GET FORM DATA
    ==============================================
    */

    $full_name = trim($_POST['full_name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');

    $institution = trim($_POST['institution'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $teaching_experience = trim(
        $_POST['teaching_experience'] ?? ''
    );

    $curricula = $_POST['curricula'] ?? [];
    $subjects = $_POST['subjects'] ?? [];
    $classes_taught = $_POST['classes_taught'] ?? [];

    $preferred_days = $_POST['preferred_days'] ?? [];

    $preferred_times = trim(
        $_POST['preferred_times'] ?? ''
    );

    $professional_statement = trim(
        $_POST['professional_statement'] ?? ''
    );


    /*
    ==============================================
    BASIC VALIDATION
    ==============================================
    */

    if (
        $full_name === "" ||
        $dob === "" ||
        $gender === "" ||
        $phone === "" ||
        $email === "" ||
        $qualification === "" ||
        empty($curricula) ||
        empty($subjects) ||
        empty($classes_taught) ||
        $professional_statement === ""
    ) {

        $message =
            "Please complete all required fields.";

        $message_type = "error";

    }


    /*
    ==============================================
    EMAIL VALIDATION
    ==============================================
    */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message =
            "Please enter a valid email address.";

        $message_type = "error";

    }


    /*
    ==============================================
    CHECK EXISTING APPLICATION
    ==============================================
    */

    else {

        $check = $conn->prepare("
            SELECT id
            FROM teacher_applications
            WHERE email = ?
            AND application_status IN
            ('Pending', 'Approved')
            LIMIT 1
        ");

        $check->bind_param(
            "s",
            $email
        );

        $check->execute();

        $check_result = $check->get_result();


        if ($check_result->num_rows > 0) {

            $message =
                "An application already exists for this email address.";

            $message_type = "error";

        }

        $check->close();


        /*
        ==========================================
        CREATE APPLICATION
        ==========================================
        */

        if ($message === "") {

            /*
            ======================================
            APPLICATION REFERENCE
            ======================================
            */

            $application_reference =
                "NISEL-TCH-" .
                date("Ymd") .
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


            /*
            ======================================
            CONVERT ARRAYS TO TEXT
            ======================================
            */

            $curricula_text =
                implode(", ", $curricula);

            $subjects_text =
                implode(", ", $subjects);

            $classes_text =
                implode(", ", $classes_taught);

            $days_text =
                implode(", ", $preferred_days);


            /*
            ======================================
            FILE UPLOAD DIRECTORIES
            ======================================
            */

            $upload_directory =
                __DIR__ . "/uploads/teacher_applications/";

            $cv_directory =
                $upload_directory . "cv/";

            $photo_directory =
                $upload_directory . "photos/";


            /*
            ======================================
            CREATE DIRECTORIES
            ======================================
            */

            if (!is_dir($cv_directory)) {

                mkdir(
                    $cv_directory,
                    0755,
                    true
                );

            }

            if (!is_dir($photo_directory)) {

                mkdir(
                    $photo_directory,
                    0755,
                    true
                );

            }


            /*
            ======================================
            CV UPLOAD
            ======================================
            */

            $cv_filename = "";

            if (
                isset($_FILES['cv']) &&
                $_FILES['cv']['error'] !==
                UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['cv']['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was a problem uploading your CV.";

                    $message_type = "error";

                } else {

                    $cv_extension =
                        strtolower(
                            pathinfo(
                                $_FILES['cv']['name'],
                                PATHINFO_EXTENSION
                            )
                        );


                    $allowed_cv =
                        [
                            "pdf",
                            "doc",
                            "docx"
                        ];


                    if (
                        !in_array(
                            $cv_extension,
                            $allowed_cv,
                            true
                        )
                    ) {

                        $message =
                            "CV must be a PDF, DOC or DOCX file.";

                        $message_type = "error";

                    } else {

                        $cv_filename =
                            $application_reference .
                            "_CV." .
                            $cv_extension;


                        $cv_target =
                            $cv_directory .
                            $cv_filename;


                        if (
                            !move_uploaded_file(
                                $_FILES['cv']['tmp_name'],
                                $cv_target
                            )
                        ) {

                            $message =
                                "Unable to save the CV.";

                            $message_type = "error";

                        }

                    }

                }

            }


            /*
            ======================================
            PHOTO UPLOAD
            ======================================
            */

            $photo_filename = "";

            if (
                $message === "" &&
                isset($_FILES['photo']) &&
                $_FILES['photo']['error'] !==
                UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $_FILES['photo']['error'] !==
                    UPLOAD_ERR_OK
                ) {

                    $message =
                        "There was a problem uploading the photograph.";

                    $message_type = "error";

                } else {

                    $photo_extension =
                        strtolower(
                            pathinfo(
                                $_FILES['photo']['name'],
                                PATHINFO_EXTENSION
                            )
                        );


                    $allowed_photo =
                        [
                            "jpg",
                            "jpeg",
                            "png",
                            "webp"
                        ];


                    if (
                        !in_array(
                            $photo_extension,
                            $allowed_photo,
                            true
                        )
                    ) {

                        $message =
                            "Photo must be JPG, JPEG, PNG or WEBP.";

                        $message_type = "error";

                    } else {

                        $photo_filename =
                            $application_reference .
                            "_PHOTO." .
                            $photo_extension;


                        $photo_target =
                            $photo_directory .
                            $photo_filename;


                        if (
                            !move_uploaded_file(
                                $_FILES['photo']['tmp_name'],
                                $photo_target
                            )
                        ) {

                            $message =
                                "Unable to save the photograph.";

                            $message_type = "error";

                        }

                    }

                }

            }


            /*
            ======================================
            SAVE APPLICATION
            ======================================
            */

            if ($message === "") {

                $stmt = $conn->prepare("

                    INSERT INTO teacher_applications (

                        application_reference,

                        full_name,
                        dob,
                        gender,

                        phone,
                        email,
                        location,

                        institution,
                        qualification,
                        teaching_experience,

                        curricula,
                        subjects,
                        classes_taught,

                        preferred_days,
                        preferred_times,

                        professional_statement,

                        cv_filename,
                        photo_filename,

                        application_status

                    )

                    VALUES (

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
                        ?,

                        ?,
                        ?,

                        ?,

                        ?,
                        ?,

                        'Pending'

                    )

                ");


                if (!$stmt) {

                    $message =
                        "Database error: " .
                        $conn->error;

                    $message_type = "error";

                } else {

                    $stmt->bind_param(

                        "ssssssssssssssssss",

                        $application_reference,

                        $full_name,
                        $dob,
                        $gender,

                        $phone,
                        $email,
                        $location,

                        $institution,
                        $qualification,
                        $teaching_experience,

                        $curricula_text,
                        $subjects_text,
                        $classes_text,

                        $days_text,
                        $preferred_times,

                        $professional_statement,

                        $cv_filename,
                        $photo_filename

                    );


                    if ($stmt->execute()) {

                        $message =
                            "Your teaching application has been submitted successfully.";

                        $message_type =
                            "success";

                    } else {

                        $message =
                            "Unable to submit your application. Please try again.";

                        $message_type =
                            "error";

                    }

                    $stmt->close();

                }

            }

        }

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

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


/* ==========================================
   HEADER
========================================== */

.header {

    background:
        #003366;

    color: white;

    padding: 22px;

    text-align: center;

}

.header h1 {

    margin: 0;

    font-size: 27px;

}

.header p {

    margin: 8px 0 0;

    color: #dbe9f5;

}


/* ==========================================
   CONTAINER
========================================== */

.container {

    width: 94%;

    max-width: 1000px;

    margin: 35px auto;

}


/* ==========================================
   FORM CARD
========================================== */

.form-card {

    background: white;

    padding: 35px;

    border-radius: 14px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.10);

}


/* ==========================================
   INTRODUCTION
========================================== */

.introduction {

    background:
        #f1f7fc;

    border-left:
        5px solid #003366;

    padding: 18px;

    margin-bottom: 30px;

    line-height: 1.7;

}


/* ==========================================
   SECTION
========================================== */

.form-section {

    margin-bottom: 35px;

}

.form-section h2 {

    color:
        #003366;

    font-size: 21px;

    border-bottom:
        2px solid #e5e5e5;

    padding-bottom: 10px;

    margin-bottom: 22px;

}


/* ==========================================
   GRID
========================================== */

.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 20px;

}


/* ==========================================
   FORM GROUP
========================================== */

.form-group {

    margin-bottom: 18px;

}

.full {

    grid-column:
        1 / -1;

}

label {

    display: block;

    font-weight: bold;

    margin-bottom: 8px;

}

.required {

    color: red;

}

input,
select,
textarea {

    width: 100%;

    padding: 12px;

    border:
        1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

    outline: none;

}

input:focus,
select:focus,
textarea:focus {

    border-color:
        #003366;

}

textarea {

    min-height: 130px;

    resize: vertical;

}


/* ==========================================
   CHECKBOX GROUP
========================================== */

.checkbox-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 10px;

}

.checkbox-item {

    background:
        #f7f9fb;

    border:
        1px solid #ddd;

    padding: 10px;

    border-radius: 6px;

}

.checkbox-item label {

    display: flex;

    align-items: center;

    gap: 8px;

    margin: 0;

    font-weight: normal;

}

.checkbox-item input {

    width: auto;

}


/* ==========================================
   FILE INPUT
========================================== */

.file-note {

    font-size: 13px;

    color: #777;

    margin-top: 6px;

}


/* ==========================================
   MESSAGE
========================================== */

.message {

    padding: 15px;

    margin-bottom: 25px;

    border-radius: 7px;

    font-weight: bold;

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


/* ==========================================
   SUBMIT
========================================== */

.submit-button {

    width: 100%;

    padding: 16px;

    background:
        #003366;

    color: white;

    border: none;

    border-radius: 7px;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

}

.submit-button:hover {

    background:
        #0055a5;

}


/* ==========================================
   FOOTER
========================================== */

.footer {

    text-align: center;

    margin-top: 25px;

    color: #777;

    font-size: 14px;

}


/* ==========================================
   MOBILE
========================================== */

@media(max-width: 750px) {

    .form-grid {

        grid-template-columns: 1fr;

    }

    .checkbox-grid {

        grid-template-columns: 1fr;

    }

    .form-card {

        padding: 22px;

    }

}

</style>

</head>


<body>


<!-- ==========================================
     HEADER
========================================== -->

<div class="header">

    <h1>
        NISEL ONLINE EDUCATION
    </h1>

    <p>
        Teacher Application Form
    </p>

</div>



<div class="container">


<div class="form-card">


<!-- ==========================================
     INTRODUCTION
========================================== -->

<div class="introduction">

    <strong>
        Join the NISEL Teaching Team
    </strong>

    <p>

        NISEL ONLINE EDUCATION is looking for
        qualified, dedicated and passionate
        teachers to provide quality online
        education to students.

    </p>

    <p>

        Please complete this application form
        accurately. Your application will be
        reviewed by the NISEL administration
        team before a teaching position is
        offered.

    </p>

</div>



<!-- ==========================================
     MESSAGE
========================================== -->

<?php if ($message !== ""): ?>

<div class="message
    <?php echo
        $message_type === "success"
        ? "success"
        : "error";
    ?>">

    <?php

    echo htmlspecialchars(
        $message
    );

    ?>

    <?php if (
        $message_type === "success"
    ): ?>

        <?php if (
            $application_reference !== ""
        ): ?>

            <br><br>

            Application Reference:

            <strong>

                <?php

                echo htmlspecialchars(
                    $application_reference
                );

                ?>

            </strong>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php endif; ?>



<form
    method="POST"
    enctype="multipart/form-data"
>


<!-- ==========================================
     PERSONAL INFORMATION
========================================== -->

<div class="form-section">

<h2>
    1. Personal Information
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Full Name
    <span class="required">*</span>
</label>

<input
    type="text"
    name="full_name"
    placeholder="Enter your full name"
    required
>

</div>


<div class="form-group">

<label>
    Date of Birth
    <span class="required">*</span>
</label>

<input
    type="date"
    name="dob"
    required
>

</div>


<div class="form-group">

<label>
    Gender
    <span class="required">*</span>
</label>

<select
    name="gender"
    required
>

<option value="">
    Select Gender
</option>

<option value="Male">
    Male
</option>

<option value="Female">
    Female
</option>

</select>

</div>


<div class="form-group">

<label>
    Phone Number
    <span class="required">*</span>
</label>

<input
    type="tel"
    name="phone"
    placeholder="e.g. 0240000000"
    required
>

</div>


<div class="form-group">

<label>
    Email Address
    <span class="required">*</span>
</label>

<input
    type="email"
    name="email"
    placeholder="example@email.com"
    required
>

</div>


<div class="form-group">

<label>
    Location
</label>

<input
    type="text"
    name="location"
    placeholder="City / Region"
>

</div>


</div>

</div>



<!-- ==========================================
     PROFESSIONAL INFORMATION
========================================== -->

<div class="form-section">

<h2>
    2. Professional Information
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Institution / School
</label>

<input
    type="text"
    name="institution"
    placeholder="Current or most recent institution"
>

</div>


<div class="form-group">

<label>
    Highest Qualification
    <span class="required">*</span>
</label>

<input
    type="text"
    name="qualification"
    placeholder="e.g. BSc, BA, MEd, PGDE"
    required
>

</div>


<div class="form-group">

<label>
    Teaching Experience
</label>

<select
    name="teaching_experience"
>

<option value="">
    Select Experience
</option>

<option value="Less than 1 year">
    Less than 1 year
</option>

<option value="1 - 3 years">
    1 - 3 years
</option>

<option value="4 - 6 years">
    4 - 6 years
</option>

<option value="7 - 10 years">
    7 - 10 years
</option>

<option value="More than 10 years">
    More than 10 years
</option>

</select>

</div>


</div>

</div>



<!-- ==========================================
     CURRICULUM
========================================== -->

<div class="form-section">

<h2>
    3. Curriculum(s) You Teach
</h2>


<div class="checkbox-grid">


<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="curricula[]"
    value="Cambridge"
>

Cambridge Curriculum

</label>

</div>


<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="curricula[]"
    value="IB"
>

IB Curriculum

</label>

</div>


<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="curricula[]"
    value="GES"
>

GES Curriculum

</label>

</div>


</div>

</div>



<!-- ==========================================
     SUBJECTS
========================================== -->

<div class="form-section">

<h2>
    4. Subjects You Can Teach
</h2>


<div class="checkbox-grid">


<?php

$subjects = [

    "Mathematics",

    "English Language",

    "Physics",

    "Chemistry",

    "Biology",

    "ICT",

    "Economics",

    "Business Studies",

    "Accounting",

    "Geography",

    "History",

    "Government",

    "French",

    "Computer Science",

    "Science"

];


foreach ($subjects as $subject):

?>

<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="subjects[]"
    value="<?php
        echo htmlspecialchars(
            $subject
        );
    ?>"
>

<?php

echo htmlspecialchars(
    $subject
);

?>

</label>

</div>

<?php endforeach; ?>


</div>

</div>



<!-- ==========================================
     CLASSES
========================================== -->

<div class="form-section">

<h2>
    5. Classes / Years / Grades You Can Teach
</h2>


<div class="checkbox-grid">


<?php

$classes = [

    "Cambridge Lower Primary",

    "Cambridge Lower Secondary",

    "Cambridge IGCSE",

    "Cambridge AS Level",

    "Cambridge A Level",

    "IB Grade 3 - 5",

    "IB Grade 6 - 8",

    "IB Grade 9 - 10",

    "IB Grade 11 - 12",

    "GES Primary",

    "GES JHS",

    "GES SHS"

];


foreach ($classes as $class):

?>

<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="classes_taught[]"
    value="<?php
        echo htmlspecialchars(
            $class
        );
    ?>"
>

<?php

echo htmlspecialchars(
    $class
);

?>

</label>

</div>

<?php endforeach; ?>


</div>

</div>



<!-- ==========================================
     AVAILABILITY
========================================== -->

<div class="form-section">

<h2>
    6. Teaching Availability
</h2>


<div class="form-group">

<label>
    Preferred Teaching Days
</label>


<div class="checkbox-grid">


<?php

$days = [

    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday"

];


foreach ($days as $day):

?>

<div class="checkbox-item">

<label>

<input
    type="checkbox"
    name="preferred_days[]"
    value="<?php
        echo $day;
    ?>"
>

<?php

echo $day;

?>

</label>

</div>

<?php endforeach; ?>


</div>

</div>


<div class="form-group">

<label>
    Preferred Teaching Times
</label>

<input
    type="text"
    name="preferred_times"
    placeholder="Example: 4:00 PM - 8:00 PM"
>

</div>

</div>



<!-- ==========================================
     PROFESSIONAL STATEMENT
========================================== -->

<div class="form-section">

<h2>
    7. Professional Statement
</h2>


<div class="form-group">

<label>

    Tell us about yourself and why you
    would like to teach at NISEL ONLINE
    EDUCATION.

    <span class="required">*</span>

</label>


<textarea
    name="professional_statement"
    placeholder="Write your professional statement here..."
    required
></textarea>

</div>

</div>



<!-- ==========================================
     DOCUMENTS
========================================== -->

<div class="form-section">

<h2>
    8. Supporting Documents
</h2>


<div class="form-grid">


<div class="form-group">

<label>
    Upload CV
</label>

<input
    type="file"
    name="cv"
    accept=".pdf,.doc,.docx"
>

<div class="file-note">

    Accepted formats:
    PDF, DOC, DOCX

</div>

</div>


<div class="form-group">

<label>
    Profile Photograph
</label>

<input
    type="file"
    name="photo"
    accept=".jpg,.jpeg,.png,.webp"
>

<div class="file-note">

    Accepted formats:
    JPG, JPEG, PNG, WEBP

</div>

</div>


</div>

</div>



<!-- ==========================================
     DECLARATION
========================================== -->

<div class="form-section">

<h2>
    9. Declaration
</h2>


<div class="checkbox-item">

<label>

<input
    type="checkbox"
    required
>

I confirm that the information provided
in this application is true and accurate
to the best of my knowledge.

</label>

</div>

</div>



<!-- ==========================================
     SUBMIT
========================================== -->

<button
    type="submit"
    class="submit-button"
>

    Submit Teaching Application

</button>


</form>


</div>


<div class="footer">

    © <?php echo date("Y"); ?>

    NISEL ONLINE EDUCATION

    <br>

    Quality Online Education

</div>


</div>


</body>

</html>


<?php

$conn->close();

?>
