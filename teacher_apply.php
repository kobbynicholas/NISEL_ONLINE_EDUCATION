<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| Teacher Application
|--------------------------------------------------------------------------
| This file uses PDO and is designed to work with:
| C:\xampp\htdocs\online\teacher\_apply.php
|
| Database connection:
| C:\xampp\htdocs\online\config\db.php
|
| IMPORTANT:
| The database table teacher_applications should contain a
| zoom_link column if you want the Zoom link to be saved.
|--------------------------------------------------------------------------
*/

require "config/db.php";


/* =========================================================
   INITIAL VALUES
========================================================= */

$message = "";
$message_type = "";
$application_reference = "";


/* =========================================================
   FORM DATA
========================================================= */

$form = [
    "full_name" => "",
    "dob" => "",
    "gender" => "",
    "phone" => "",
    "email" => "",
    "location" => "",
    "institution" => "",
    "qualification" => "",
    "teaching_experience" => "",
    "preferred_times" => "",
    "professional_statement" => "",
    "zoom_link" => ""
];

$selected_curricula = [];
$selected_subjects = [];
$selected_classes = [];
$selected_days = [];


/* =========================================================
   OPTIONS
========================================================= */

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

$curricula = [
    "Cambridge",
    "IB",
    "GES"
];

$days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday"
];


/* =========================================================
   PROCESS APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $form["full_name"] = trim($_POST["full_name"] ?? "");
    $form["dob"] = trim($_POST["dob"] ?? "");
    $form["gender"] = trim($_POST["gender"] ?? "");
    $form["phone"] = trim($_POST["phone"] ?? "");
    $form["email"] = trim($_POST["email"] ?? "");
    $form["location"] = trim($_POST["location"] ?? "");
    $form["institution"] = trim($_POST["institution"] ?? "");
    $form["qualification"] = trim($_POST["qualification"] ?? "");
    $form["teaching_experience"] = trim($_POST["teaching_experience"] ?? "");
    $form["preferred_times"] = trim($_POST["preferred_times"] ?? "");
    $form["professional_statement"] = trim($_POST["professional_statement"] ?? "");
    $form["zoom_link"] = trim($_POST["zoom_link"] ?? "");

    $selected_curricula = $_POST["curricula"] ?? [];
    $selected_subjects = $_POST["subjects"] ?? [];
    $selected_classes = $_POST["classes_taught"] ?? [];
    $selected_days = $_POST["preferred_days"] ?? [];

    /* Make sure checkbox values are arrays */
    $selected_curricula = is_array($selected_curricula) ? $selected_curricula : [];
    $selected_subjects = is_array($selected_subjects) ? $selected_subjects : [];
    $selected_classes = is_array($selected_classes) ? $selected_classes : [];
    $selected_days = is_array($selected_days) ? $selected_days : [];


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $form["full_name"] === "" ||
        $form["dob"] === "" ||
        $form["gender"] === "" ||
        $form["phone"] === "" ||
        $form["email"] === "" ||
        $form["qualification"] === "" ||
        empty($selected_curricula) ||
        empty($selected_subjects) ||
        empty($selected_classes) ||
        $form["professional_statement"] === ""
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif (!filter_var($form["email"], FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (
        $form["zoom_link"] !== "" &&
        !filter_var($form["zoom_link"], FILTER_VALIDATE_URL)
    ) {

        $message = "Please enter a valid Zoom meeting URL.";
        $message_type = "error";

    } else {

        try {

            /* =================================================
               CHECK EXISTING APPLICATION
            ================================================= */

            $check = $pdo->prepare("
                SELECT id
                FROM teacher_applications
                WHERE email = ?
                AND application_status IN ('Pending', 'Approved')
                LIMIT 1
            ");

            $check->execute([
                $form["email"]
            ]);

            if ($check->fetch(PDO::FETCH_ASSOC)) {

                $message =
                    "An application already exists for this email address.";

                $message_type = "error";

            } else {

                /* =================================================
                   APPLICATION REFERENCE
                ================================================= */

                $application_reference =
                    "NISEL-TCH-" .
                    date("Ymd") .
                    "-" .
                    strtoupper(
                        substr(
                            bin2hex(random_bytes(4)),
                            0,
                            6
                        )
                    );


                /* =================================================
                   CONVERT ARRAYS TO TEXT
                ================================================= */

                $curricula_text =
                    implode(", ", $selected_curricula);

                $subjects_text =
                    implode(", ", $selected_subjects);

                $classes_text =
                    implode(", ", $selected_classes);

                $days_text =
                    implode(", ", $selected_days);


                /* =================================================
                   UPLOAD DIRECTORIES
                ================================================= */

                $upload_directory =
                    __DIR__ . "/uploads/teacher_applications/";

                $cv_directory =
                    $upload_directory . "cv/";

                $photo_directory =
                    $upload_directory . "photos/";


                if (!is_dir($cv_directory)) {
                    mkdir($cv_directory, 0755, true);
                }

                if (!is_dir($photo_directory)) {
                    mkdir($photo_directory, 0755, true);
                }


                /* =================================================
                   CV UPLOAD
                ================================================= */

                $cv_filename = "";

                if (
                    isset($_FILES["cv"]) &&
                    $_FILES["cv"]["error"] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES["cv"]["error"] !== UPLOAD_ERR_OK) {

                        $message =
                            "There was a problem uploading your CV.";

                        $message_type = "error";

                    } else {

                        $cv_extension =
                            strtolower(
                                pathinfo(
                                    $_FILES["cv"]["name"],
                                    PATHINFO_EXTENSION
                                )
                            );

                        $allowed_cv = [
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
                                    $_FILES["cv"]["tmp_name"],
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


                /* =================================================
                   PHOTO UPLOAD
                ================================================= */

                $photo_filename = "";

                if (
                    $message === "" &&
                    isset($_FILES["photo"]) &&
                    $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
                ) {

                    if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {

                        $message =
                            "There was a problem uploading the photograph.";

                        $message_type = "error";

                    } else {

                        $photo_extension =
                            strtolower(
                                pathinfo(
                                    $_FILES["photo"]["name"],
                                    PATHINFO_EXTENSION
                                )
                            );

                        $allowed_photo = [
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
                                    $_FILES["photo"]["tmp_name"],
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


                /* =================================================
                   SAVE APPLICATION
                ================================================= */

                if ($message === "") {

                    /*
                     * zoom_link is included because the application
                     * form now allows the applicant to provide their
                     * online teaching Zoom link.
                     */
                    $stmt = $pdo->prepare("
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
                            zoom_link,
                            application_status
                        )
                        VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending'
                        )
                    ");

                    $stmt->execute([
                        $application_reference,
                        $form["full_name"],
                        $form["dob"],
                        $form["gender"],
                        $form["phone"],
                        $form["email"],
                        $form["location"],
                        $form["institution"],
                        $form["qualification"],
                        $form["teaching_experience"],
                        $curricula_text,
                        $subjects_text,
                        $classes_text,
                        $days_text,
                        $form["preferred_times"],
                        $form["professional_statement"],
                        $cv_filename,
                        $photo_filename,
                        $form["zoom_link"]
                    ]);

                    $message =
                        "Your teaching application has been submitted successfully.";

                    $message_type = "success";

                    /*
                     * Clear form values after successful submission.
                     */
                    $form = [
                        "full_name" => "",
                        "dob" => "",
                        "gender" => "",
                        "phone" => "",
                        "email" => "",
                        "location" => "",
                        "institution" => "",
                        "qualification" => "",
                        "teaching_experience" => "",
                        "preferred_times" => "",
                        "professional_statement" => "",
                        "zoom_link" => ""
                    ];

                    $selected_curricula = [];
                    $selected_subjects = [];
                    $selected_classes = [];
                    $selected_days = [];
                }
            }

        } catch (PDOException $e) {

            $message =
                "Unable to submit your application. Please check your database structure and try again.";

            $message_type = "error";

            /*
             * For development only, uncomment the following line
             * if you need to see the exact database error:
             *
             * $message .= " " . $e->getMessage();
             */
        }
    }
}


/* =========================================================
   HTML HELPERS
========================================================= */

function oldValue(array $form, string $key): string
{
    return htmlspecialchars(
        $form[$key] ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

function checkedValue(array $values, string $value): string
{
    return in_array($value, $values, true)
        ? "checked"
        : "";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Teacher Application | NISEL ONLINE EDUCATION
</title>

<style>

:root {
    --primary: #003366;
    --primary-light: #0b73c9;
    --accent: #11a8ff;
    --background: #f4f8fc;
    --card: #ffffff;
    --text: #182230;
    --muted: #6b7785;
    --border: #dce5ee;
    --success: #198754;
    --danger: #dc3545;
    --shadow: 0 15px 45px rgba(15, 35, 55, .09);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        Inter,
        "Segoe UI",
        Arial,
        sans-serif;
    background:
        radial-gradient(
            circle at top right,
            #e7f5ff 0,
            transparent 30%
        ),
        var(--background);
    color: var(--text);
}

a {
    text-decoration: none;
}

.page-header {
    background:
        linear-gradient(
            135deg,
            #00284f,
            #005b9e,
            #0788d5
        );
    color: white;
    padding: 55px 20px 80px;
    position: relative;
    overflow: hidden;
}

.page-header::after {
    content: "";
    position: absolute;
    width: 350px;
    height: 350px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    right: -100px;
    top: -170px;
}

.header-inner {
    max-width: 1180px;
    margin: auto;
    position: relative;
    z-index: 1;
}

.brand {
    font-size: 14px;
    font-weight: 900;
    letter-spacing: 2px;
    color: #9de2ff;
    margin-bottom: 15px;
}

.page-header h1 {
    margin: 0;
    font-size: clamp(32px, 5vw, 52px);
    line-height: 1.08;
}

.page-header p {
    max-width: 720px;
    color: #d9efff;
    line-height: 1.7;
    font-size: 16px;
    margin: 18px 0 0;
}

.container {
    width: min(94%, 1180px);
    margin: -40px auto 50px;
    position: relative;
    z-index: 2;
}

.form-card {
    background: var(--card);
    border: 1px solid rgba(255,255,255,.7);
    border-radius: 24px;
    padding: clamp(22px, 4vw, 45px);
    box-shadow: var(--shadow);
}

.intro-card {
    display: grid;
    grid-template-columns: 70px 1fr;
    gap: 20px;
    padding: 22px;
    background: linear-gradient(135deg,#f0f9ff,#f8fcff);
    border: 1px solid #d9eefb;
    border-radius: 18px;
    margin-bottom: 35px;
}

.intro-icon {
    width: 65px;
    height: 65px;
    border-radius: 18px;
    background: linear-gradient(135deg,#003366,#0b83d5);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
}

.intro-card h2 {
    margin: 0 0 7px;
    color: var(--primary);
    font-size: 21px;
}

.intro-card p {
    margin: 0;
    color: var(--muted);
    line-height: 1.7;
}

.message {
    padding: 18px 20px;
    border-radius: 15px;
    margin-bottom: 28px;
    font-weight: 700;
    line-height: 1.5;
}

.message.success {
    background: #e8f8ef;
    color: #126c3d;
    border: 1px solid #bce8cf;
}

.message.error {
    background: #fff0f1;
    color: #9b2632;
    border: 1px solid #f3c3c8;
}

.reference {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 12px;
    border-radius: 9px;
    background: rgba(25,135,84,.1);
}

.form-section {
    margin-top: 40px;
    padding-top: 10px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
}

.section-number {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9f5ff;
    color: var(--primary-light);
    font-weight: 900;
}

.section-title h2 {
    margin: 0;
    color: var(--primary);
    font-size: 21px;
}

.section-title p {
    margin: 3px 0 0;
    color: var(--muted);
    font-size: 13px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 20px;
}

.full {
    grid-column: 1 / -1;
}

.form-group {
    margin-bottom: 5px;
}

label.field-label {
    display: block;
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 8px;
    color: #273546;
}

.required {
    color: var(--danger);
}

input[type="text"],
input[type="email"],
input[type="tel"],
input[type="date"],
input[type="url"],
input[type="file"],
select,
textarea {
    width: 100%;
    border: 1px solid var(--border);
    background: #fbfdff;
    color: var(--text);
    border-radius: 12px;
    padding: 13px 14px;
    font: inherit;
    transition: .2s ease;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: var(--primary-light);
    box-shadow: 0 0 0 4px rgba(11,115,201,.10);
    background: white;
}

textarea {
    min-height: 150px;
    resize: vertical;
}

.help-text {
    display: block;
    margin-top: 7px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.5;
}

.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 12px;
}

.checkbox-item {
    position: relative;
}

.checkbox-item input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.checkbox-item label {
    display: flex;
    align-items: center;
    min-height: 52px;
    padding: 12px 14px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: #fbfdff;
    cursor: pointer;
    color: #354354;
    font-size: 13px;
    font-weight: 700;
    transition: .2s ease;
}

.checkbox-item label::before {
    content: "✓";
    width: 22px;
    height: 22px;
    margin-right: 9px;
    border: 1px solid #c8d4df;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    flex: 0 0 22px;
    background: white;
}

.checkbox-item input:checked + label {
    background: #eef8ff;
    border-color: #74bce9;
    color: var(--primary);
}

.checkbox-item input:checked + label::before {
    background: var(--primary-light);
    border-color: var(--primary-light);
    color: white;
}

.file-box {
    padding: 16px;
    border: 1px dashed #b9ccdc;
    border-radius: 14px;
    background: #f9fcff;
}

.declaration {
    margin-top: 35px;
    padding: 18px;
    border-radius: 15px;
    background: #f7fafc;
    border: 1px solid var(--border);
}

.declaration label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    color: #425064;
    font-size: 14px;
    line-height: 1.6;
}

.declaration input {
    margin-top: 4px;
    accent-color: var(--primary-light);
}

.submit-button {
    width: 100%;
    margin-top: 28px;
    border: 0;
    border-radius: 14px;
    padding: 17px 22px;
    background: linear-gradient(135deg,#003366,#087dce);
    color: white;
    font-size: 16px;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(0,70,130,.2);
    transition: .2s ease;
}

.submit-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(0,70,130,.27);
}

.bottom-note {
    text-align: center;
    color: var(--muted);
    margin-top: 20px;
    font-size: 13px;
}

@media (max-width: 900px) {

    .checkbox-grid {
        grid-template-columns: repeat(2, minmax(0,1fr));
    }

}

@media (max-width: 700px) {

    .page-header {
        padding: 40px 18px 70px;
    }

    .container {
        width: 94%;
        margin-top: -30px;
    }

    .form-card {
        padding: 22px;
        border-radius: 18px;
    }

    .intro-card {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: auto;
    }

    .checkbox-grid {
        grid-template-columns: 1fr;
    }

    .section-title h2 {
        font-size: 18px;
    }

}

</style>

</head>

<body>


<header class="page-header">

<div class="header-inner">

    <div class="brand">
        NISEL ONLINE EDUCATION
    </div>

    <h1>
        Become a NISEL Teacher
    </h1>

    <p>
        Join our teaching community and help students
        succeed through quality online education.
        Complete the application below and our
        administration team will review your application.
    </p>

</div>

</header>


<main class="container">

<div class="form-card">


<!-- INTRODUCTION -->

<div class="intro-card">

    <div class="intro-icon">
        🎓
    </div>

    <div>

        <h2>
            Join the NISEL Teaching Team
        </h2>

        <p>
            NISEL ONLINE EDUCATION is looking for
            qualified, dedicated and passionate teachers
            to provide quality online education to
            Cambridge, IB and GES students.
        </p>

    </div>

</div>


<!-- MESSAGE -->

<?php if ($message !== ""): ?>

<div class="message <?php echo htmlspecialchars($message_type); ?>">

    <?php echo htmlspecialchars($message); ?>

    <?php if (
        $message_type === "success" &&
        $application_reference !== ""
    ): ?>

        <div class="reference">
            Application Reference:
            <strong>
                <?php echo htmlspecialchars($application_reference); ?>
            </strong>
        </div>

    <?php endif; ?>

</div>

<?php endif; ?>


<form
    method="POST"
    enctype="multipart/form-data"
    autocomplete="on"
>


<!-- =====================================================
     1. PERSONAL INFORMATION
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">1</div>

    <div>
        <h2>Personal Information</h2>
        <p>Tell us about yourself.</p>
    </div>

</div>

<div class="form-grid">


<div class="form-group">

<label class="field-label">
    Full Name <span class="required">*</span>
</label>

<input
    type="text"
    name="full_name"
    value="<?php echo oldValue($form, "full_name"); ?>"
    placeholder="Enter your full name"
    required
>

</div>


<div class="form-group">

<label class="field-label">
    Date of Birth <span class="required">*</span>
</label>

<input
    type="date"
    name="dob"
    value="<?php echo oldValue($form, "dob"); ?>"
    required
>

</div>


<div class="form-group">

<label class="field-label">
    Gender <span class="required">*</span>
</label>

<select name="gender" required>

<option value="">Select Gender</option>

<option
    value="Male"
    <?php echo $form["gender"] === "Male" ? "selected" : ""; ?>
>
    Male
</option>

<option
    value="Female"
    <?php echo $form["gender"] === "Female" ? "selected" : ""; ?>
>
    Female
</option>

</select>

</div>


<div class="form-group">

<label class="field-label">
    Phone Number <span class="required">*</span>
</label>

<input
    type="tel"
    name="phone"
    value="<?php echo oldValue($form, "phone"); ?>"
    placeholder="e.g. 0240000000"
    required
>

</div>


<div class="form-group">

<label class="field-label">
    Email Address <span class="required">*</span>
</label>

<input
    type="email"
    name="email"
    value="<?php echo oldValue($form, "email"); ?>"
    placeholder="example@email.com"
    required
>

</div>


<div class="form-group">

<label class="field-label">
    Location
</label>

<input
    type="text"
    name="location"
    value="<?php echo oldValue($form, "location"); ?>"
    placeholder="City / Region"
>

</div>


</div>

</section>


<!-- =====================================================
     2. PROFESSIONAL INFORMATION
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">2</div>

    <div>
        <h2>Professional Information</h2>
        <p>Tell us about your teaching background.</p>
    </div>

</div>

<div class="form-grid">


<div class="form-group">

<label class="field-label">
    Institution / School
</label>

<input
    type="text"
    name="institution"
    value="<?php echo oldValue($form, "institution"); ?>"
    placeholder="Current or most recent institution"
>

</div>


<div class="form-group">

<label class="field-label">
    Highest Qualification <span class="required">*</span>
</label>

<input
    type="text"
    name="qualification"
    value="<?php echo oldValue($form, "qualification"); ?>"
    placeholder="e.g. BSc, BA, MEd, PGDE"
    required
>

</div>


<div class="form-group">

<label class="field-label">
    Teaching Experience
</label>

<select name="teaching_experience">

<option value="">Select Experience</option>

<option value="Less than 1 year"
<?php echo $form["teaching_experience"] === "Less than 1 year" ? "selected" : ""; ?>>
    Less than 1 year
</option>

<option value="1 - 3 years"
<?php echo $form["teaching_experience"] === "1 - 3 years" ? "selected" : ""; ?>>
    1 - 3 years
</option>

<option value="4 - 6 years"
<?php echo $form["teaching_experience"] === "4 - 6 years" ? "selected" : ""; ?>>
    4 - 6 years
</option>

<option value="7 - 10 years"
<?php echo $form["teaching_experience"] === "7 - 10 years" ? "selected" : ""; ?>>
    7 - 10 years
</option>

<option value="More than 10 years"
<?php echo $form["teaching_experience"] === "More than 10 years" ? "selected" : ""; ?>>
    More than 10 years
</option>

</select>

</div>


<div class="form-group">

<label class="field-label">
    Zoom Meeting Link
</label>

<input
    type="url"
    name="zoom_link"
    value="<?php echo oldValue($form, "zoom_link"); ?>"
    placeholder="https://zoom.us/j/123456789"
>

<span class="help-text">
    Optional. Provide the Zoom link you normally use
    for online lessons.
</span>

</div>


</div>

</section>


<!-- =====================================================
     3. CURRICULUM
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">3</div>

    <div>
        <h2>Curriculum(s) You Teach</h2>
        <p>Select all applicable curricula.</p>
    </div>

</div>

<div class="checkbox-grid">

<?php foreach ($curricula as $curriculum): ?>

<div class="checkbox-item">

<input
    type="checkbox"
    id="curriculum_<?php echo htmlspecialchars($curriculum); ?>"
    name="curricula[]"
    value="<?php echo htmlspecialchars($curriculum); ?>"
    <?php echo checkedValue($selected_curricula, $curriculum); ?>
>

<label
    for="curriculum_<?php echo htmlspecialchars($curriculum); ?>"
>
    <?php echo htmlspecialchars($curriculum); ?>
</label>

</div>

<?php endforeach; ?>

</div>

</section>


<!-- =====================================================
     4. SUBJECTS
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">4</div>

    <div>
        <h2>Subjects You Can Teach</h2>
        <p>Select every subject you are qualified to teach.</p>
    </div>

</div>

<div class="checkbox-grid">

<?php foreach ($subjects as $subject): ?>

<div class="checkbox-item">

<input
    type="checkbox"
    id="subject_<?php echo md5($subject); ?>"
    name="subjects[]"
    value="<?php echo htmlspecialchars($subject); ?>"
    <?php echo checkedValue($selected_subjects, $subject); ?>
>

<label
    for="subject_<?php echo md5($subject); ?>"
>
    <?php echo htmlspecialchars($subject); ?>
</label>

</div>

<?php endforeach; ?>

</div>

</section>


<!-- =====================================================
     5. CLASSES
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">5</div>

    <div>
        <h2>Classes / Years / Grades</h2>
        <p>Select the levels you can teach.</p>
    </div>

</div>

<div class="checkbox-grid">

<?php foreach ($classes as $class): ?>

<div class="checkbox-item">

<input
    type="checkbox"
    id="class_<?php echo md5($class); ?>"
    name="classes_taught[]"
    value="<?php echo htmlspecialchars($class); ?>"
    <?php echo checkedValue($selected_classes, $class); ?>
>

<label
    for="class_<?php echo md5($class); ?>"
>
    <?php echo htmlspecialchars($class); ?>
</label>

</div>

<?php endforeach; ?>

</div>

</section>


<!-- =====================================================
     6. AVAILABILITY
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">6</div>

    <div>
        <h2>Teaching Availability</h2>
        <p>Tell us when you are available for online lessons.</p>
    </div>

</div>


<div class="form-group">

<label class="field-label">
    Preferred Teaching Days
</label>

<div class="checkbox-grid">

<?php foreach ($days as $day): ?>

<div class="checkbox-item">

<input
    type="checkbox"
    id="day_<?php echo strtolower($day); ?>"
    name="preferred_days[]"
    value="<?php echo htmlspecialchars($day); ?>"
    <?php echo checkedValue($selected_days, $day); ?>
>

<label
    for="day_<?php echo strtolower($day); ?>"
>
    <?php echo htmlspecialchars($day); ?>
</label>

</div>

<?php endforeach; ?>

</div>

</div>


<div class="form-group" style="margin-top:20px;">

<label class="field-label">
    Preferred Teaching Times
</label>

<input
    type="text"
    name="preferred_times"
    value="<?php echo oldValue($form, "preferred_times"); ?>"
    placeholder="Example: 4:00 PM - 8:00 PM"
>

</div>

</section>


<!-- =====================================================
     7. PROFESSIONAL STATEMENT
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">7</div>

    <div>
        <h2>Professional Statement</h2>
        <p>Tell us why you would be a good fit for NISEL.</p>
    </div>

</div>

<div class="form-group">

<label class="field-label">

    Tell us about yourself and why you
    would like to teach at NISEL ONLINE EDUCATION.

    <span class="required">*</span>

</label>

<textarea
    name="professional_statement"
    placeholder="Write your professional statement here..."
    required
><?php echo oldValue($form, "professional_statement"); ?></textarea>

</div>

</section>


<!-- =====================================================
     8. DOCUMENTS
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">8</div>

    <div>
        <h2>Supporting Documents</h2>
        <p>Upload your CV and a professional photograph.</p>
    </div>

</div>

<div class="form-grid">


<div class="form-group file-box">

<label class="field-label">
    Upload CV
</label>

<input
    type="file"
    name="cv"
    accept=".pdf,.doc,.docx"
>

<span class="help-text">
    Accepted formats: PDF, DOC, DOCX
</span>

</div>


<div class="form-group file-box">

<label class="field-label">
    Profile Photograph
</label>

<input
    type="file"
    name="photo"
    accept=".jpg,.jpeg,.png,.webp"
>

<span class="help-text">
    Accepted formats: JPG, JPEG, PNG, WEBP
</span>

</div>


</div>

</section>


<!-- =====================================================
     9. DECLARATION
===================================================== -->

<section class="form-section">

<div class="section-title">

    <div class="section-number">9</div>

    <div>
        <h2>Declaration</h2>
        <p>Please confirm the information provided.</p>
    </div>

</div>

<div class="declaration">

<label>

<input
    type="checkbox"
    name="declaration"
    value="1"
    required
>

<span>
    I confirm that the information provided in this
    application is true and accurate to the best of
    my knowledge.
</span>

</label>

</div>

</section>


<button
    type="submit"
    class="submit-button"
>
    Submit Teaching Application →
</button>


</form>


<div class="bottom-note">
    © <?php echo date("Y"); ?> NISEL ONLINE EDUCATION
    · Quality Online Education
</div>


</div>

</main>

</body>
</html>
