<?php
session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| Teacher Registration
|--------------------------------------------------------------------------
*/

// ===============================
// DATABASE CONFIGURATION
// ===============================

$host="localhost";
$user="root";
$password="";
$database="nisel_online_education";

$conn=new mysqli($host,$user,$password,$database);

if($conn->connect_error){
die("Connection Failed");
}


// ===============================
// VARIABLES
// ===============================

$message = "";
$message_type = "";


// ===============================
// PROCESS REGISTRATION
// ===============================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --------------------------------
    // GET FORM DATA
    // --------------------------------

    $teacher_name = trim($_POST['teacher_name'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $subjects     = trim($_POST['subjects'] ?? '');
    $curriculum   = trim($_POST['curriculum'] ?? '');
    $experience   = trim($_POST['experience'] ?? '');
    $bio          = trim($_POST['bio'] ?? '');
    $availability = trim($_POST['availability'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --------------------------------
    // BASIC VALIDATION
    // --------------------------------

    if (
        empty($teacher_name) ||
        empty($phone) ||
        empty($email) ||
        empty($qualification) ||
        empty($subjects) ||
        empty($curriculum) ||
        empty($experience) ||
        empty($availability) ||
        empty($password)
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";
        $message_type = "error";

    } else {

        // --------------------------------
        // CHECK FOR EXISTING EMAIL
        // --------------------------------

        $check = $pdo->prepare(
            "SELECT id FROM teachers WHERE email = ? LIMIT 1"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "A teacher account with this email already exists.";
            $message_type = "error";

        } else {

            // --------------------------------
            // GENERATE UNIQUE TEACHER ID
            // --------------------------------

            do {

                $teacher_id = "NISEL-T-" . strtoupper(
                    substr(bin2hex(random_bytes(4)), 0, 8)
                );

                $check_id = $pdo->prepare(
                    "SELECT id FROM teachers WHERE teacher_id = ? LIMIT 1"
                );

                $check_id->execute([$teacher_id]);

            } while ($check_id->fetch());


            // --------------------------------
            // HANDLE PHOTO UPLOAD
            // --------------------------------

            $photo_name = null;

            if (
                isset($_FILES['photo']) &&
                $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {

                    $message = "There was an error uploading the photo.";
                    $message_type = "error";

                } else {

                    $allowed_types = [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];

                    $file_type = mime_content_type(
                        $_FILES['photo']['tmp_name']
                    );

                    $file_size = $_FILES['photo']['size'];

                    // Maximum 5 MB
                    if ($file_size > 5 * 1024 * 1024) {

                        $message = "Teacher photo must not exceed 5MB.";
                        $message_type = "error";

                    } elseif (!in_array($file_type, $allowed_types)) {

                        $message = "Only JPG, PNG and WEBP images are allowed.";
                        $message_type = "error";

                    } else {

                        // --------------------------------
                        // CREATE UPLOAD DIRECTORY
                        // --------------------------------

                        $upload_directory = "uploads/teachers/";

                        if (!is_dir($upload_directory)) {
                            mkdir(
                                $upload_directory,
                                0755,
                                true
                            );
                        }

                        // --------------------------------
                        // CREATE UNIQUE FILE NAME
                        // --------------------------------

                        $extension = strtolower(
                            pathinfo(
                                $_FILES['photo']['name'],
                                PATHINFO_EXTENSION
                            )
                        );

                        $photo_name =
                            $teacher_id . "_" .
                            time() . "." .
                            $extension;

                        $photo_path =
                            $upload_directory . $photo_name;


                        // --------------------------------
                        // MOVE PHOTO
                        // --------------------------------

                        if (!move_uploaded_file(
                            $_FILES['photo']['tmp_name'],
                            $photo_path
                        )) {

                            $message = "Unable to save teacher photo.";
                            $message_type = "error";

                            $photo_name = null;
                        }
                    }
                }
            }


            // --------------------------------
            // ONLY CONTINUE IF NO ERROR
            // --------------------------------

            if ($message_type !== "error") {

                // --------------------------------
                // HASH PASSWORD
                // --------------------------------

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                // --------------------------------
                // INSERT TEACHER
                // --------------------------------

                try {

                    $sql = "
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
                            :teacher_id,
                            :teacher_name,
                            :phone,
                            :email,
                            :qualification,
                            :subjects,
                            :curriculum,
                            :experience,
                            :bio,
                            :availability,
                            :photo,
                            :password,
                            'Active'
                        )
                    ";

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ':teacher_id'     => $teacher_id,
                        ':teacher_name'   => $teacher_name,
                        ':phone'          => $phone,
                        ':email'          => $email,
                        ':qualification' => $qualification,
                        ':subjects'      => $subjects,
                        ':curriculum'    => $curriculum,
                        ':experience'    => $experience,
                        ':bio'           => $bio,
                        ':availability'  => $availability,
                        ':photo'         => $photo_name,
                        ':password'      => $hashed_password
                    ]);


                    // --------------------------------
                    // SUCCESS
                    // --------------------------------

                    $message =
                        "Teacher registration successful! " .
                        "Your Teacher ID is: " .
                        $teacher_id;

                    $message_type = "success";


                    // --------------------------------
                    // CLEAR FORM
                    // --------------------------------

                    $teacher_name = "";
                    $phone = "";
                    $email = "";
                    $qualification = "";
                    $subjects = "";
                    $curriculum = "";
                    $experience = "";
                    $bio = "";
                    $availability = "";

                } catch (PDOException $e) {

                    $message =
                        "Registration failed. Please try again.";

                    $message_type = "error";
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

<title>Teacher Registration | Nisel Online Education</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f7fb;
    color: #222;
}

.header {
    background: linear-gradient(
        135deg,
        #073b8c,
        #0b63ce
    );

    color: white;
    padding: 25px 20px;
    text-align: center;
}

.header h1 {
    font-size: 30px;
    margin-bottom: 6px;
}

.header p {
    font-size: 15px;
    opacity: .9;
}

.container {
    width: 95%;
    max-width: 1000px;
    margin: 35px auto;
}

.card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,.08);
    overflow: hidden;
}

.card-header {
    padding: 25px 30px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    color: #073b8c;
    margin-bottom: 5px;
}

.card-header p {
    color: #777;
    font-size: 14px;
}

form {
    padding: 30px;
}

.section-title {
    color: #073b8c;
    font-size: 18px;
    font-weight: bold;
    margin: 25px 0 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e8eef7;
}

.section-title:first-child {
    margin-top: 0;
}

.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: bold;
    color: #333;
}

.required {
    color: red;
}

input,
select,
textarea {
    width: 100%;
    padding: 13px 14px;
    border: 1px solid #d6dce5;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
    background: white;
    transition: .2s;
}

input:focus,
select:focus,
textarea:focus {
    border-color: #0b63ce;
    box-shadow: 0 0 0 3px rgba(11,99,206,.1);
}

textarea {
    min-height: 120px;
    resize: vertical;
}

.photo-box {
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    background: #f8fafc;
}

.photo-box input {
    border: none;
    padding: 10px;
}

.photo-note {
    color: #777;
    font-size: 12px;
    margin-top: 7px;
}

.alert {
    margin: 20px 30px 0;
    padding: 15px 18px;
    border-radius: 8px;
    font-size: 14px;
}

.alert.success {
    background: #e7f7ed;
    color: #176b36;
    border: 1px solid #a9dfbb;
}

.alert.error {
    background: #fdecec;
    color: #a42323;
    border: 1px solid #efb2b2;
}

.password-wrapper {
    position: relative;
}

.password-wrapper input {
    padding-right: 50px;
}

.toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #666;
    font-size: 13px;
}

.submit-area {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.btn {
    border: none;
    padding: 14px 28px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.btn-primary {
    background: #0b63ce;
    color: white;
}

.btn-primary:hover {
    background: #074d9e;
}

.btn-secondary {
    background: #eef2f7;
    color: #333;
    text-decoration: none;
}

.btn-secondary:hover {
    background: #dfe5ec;
}

.footer {
    text-align: center;
    padding: 25px;
    color: #777;
    font-size: 13px;
}

@media(max-width: 700px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    form {
        padding: 20px;
    }

    .card-header {
        padding: 20px;
    }

    .alert {
        margin-left: 20px;
        margin-right: 20px;
    }

    .submit-area {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        width: 100%;
    }

}

</style>

</head>

<body>


<!-- ===================================
     HEADER
==================================== -->

<header class="header">

    <h1>Nisel Online Education</h1>

    <p>Teacher Registration Portal</p>

</header>


<div class="container">

<div class="card">


<!-- ===================================
     CARD HEADER
==================================== -->

<div class="card-header">

    <h2>Create Teacher Account</h2>

    <p>
        Complete the form below to register as a teacher
        on Nisel Online Education.
    </p>

</div>


<!-- ===================================
     ALERT
==================================== -->

<?php if (!empty($message)): ?>

<div class="alert <?= htmlspecialchars($message_type) ?>">

    <?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<!-- ===================================
     REGISTRATION FORM
==================================== -->

<form
    method="POST"
    action=""
    enctype="multipart/form-data"
    autocomplete="off"
>


<!-- PERSONAL INFORMATION -->

<div class="section-title">
    Personal Information
</div>


<div class="grid">

    <div class="form-group">

        <label>
            Full Name
            <span class="required">*</span>
        </label>

        <input
            type="text"
            name="teacher_name"
            value="<?= htmlspecialchars($teacher_name ?? '') ?>"
            placeholder="Enter full name"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Phone Number
            <span class="required">*</span>
        </label>

        <input
            type="tel"
            name="phone"
            value="<?= htmlspecialchars($phone ?? '') ?>"
            placeholder="e.g. 0599363266"
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
            value="<?= htmlspecialchars($email ?? '') ?>"
            placeholder="teacher@example.com"
            required
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
            value="<?= htmlspecialchars($qualification ?? '') ?>"
            placeholder="e.g. BSc, MEd, MSc"
            required
        >

    </div>

</div>


<!-- PROFESSIONAL INFORMATION -->

<div class="section-title">
    Professional Information
</div>


<div class="grid">

    <div class="form-group">

        <label>
            Subjects
            <span class="required">*</span>
        </label>

        <input
            type="text"
            name="subjects"
            value="<?= htmlspecialchars($subjects ?? '') ?>"
            placeholder="e.g. Mathematics, Physics"
            required
        >

    </div>


    <div class="form-group">

        <label>
            Curriculum
            <span class="required">*</span>
        </label>

        <select
            name="curriculum"
            required
        >

            <option value="">
                Select Curriculum
            </option>

            <option value="Cambridge IGCSE"
                <?= (($curriculum ?? '') == 'Cambridge IGCSE')
                    ? 'selected' : '' ?>>
                Cambridge IGCSE
            </option>

            <option value="Cambridge Checkpoint"
                <?= (($curriculum ?? '') == 'Cambridge Checkpoint')
                    ? 'selected' : '' ?>>
                Cambridge Checkpoint
            </option>

            <option value="Cambridge AS/A Level"
                <?= (($curriculum ?? '') == 'Cambridge AS/A Level')
                    ? 'selected' : '' ?>>
                Cambridge AS/A Level
            </option>

            <option value="GES"
                <?= (($curriculum ?? '') == 'GES')
                    ? 'selected' : '' ?>>
                GES
            </option>

            <option value="Other"
                <?= (($curriculum ?? '') == 'Other')
                    ? 'selected' : '' ?>>
                Other
            </option>

        </select>

    </div>


    <div class="form-group">

        <label>
            Teaching Experience
            <span class="required">*</span>
        </label>

        <select
            name="experience"
            required
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


    <div class="form-group">

        <label>
            Availability
            <span class="required">*</span>
        </label>

        <select
            name="availability"
            required
        >

            <option value="">
                Select Availability
            </option>

            <option value="Weekdays">
                Weekdays
            </option>

            <option value="Weekends">
                Weekends
            </option>

            <option value="Weekdays and Weekends">
                Weekdays & Weekends
            </option>

            <option value="Flexible">
                Flexible
            </option>

        </select>

    </div>


    <div class="form-group full">

        <label>
            Professional Biography
        </label>

        <textarea
            name="bio"
            placeholder="Tell students about your teaching experience, expertise and teaching approach..."
        ><?= htmlspecialchars($bio ?? '') ?></textarea>

    </div>

</div>


<!-- TEACHER PHOTO -->

<div class="section-title">
    Teacher Profile Photo
</div>


<div class="photo-box">

    <label>
        Upload Profile Photo
    </label>

    <input
        type="file"
        name="photo"
        accept="image/jpeg,image/png,image/webp"
    >

    <div class="photo-note">
        JPG, PNG or WEBP. Maximum file size: 5MB.
    </div>

</div>


<!-- LOGIN INFORMATION -->

<div class="section-title">
    Login Information
</div>


<div class="grid">

    <div class="form-group">

        <label>
            Password
            <span class="required">*</span>
        </label>

        <div class="password-wrapper">

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimum 8 characters"
                required
            >

            <span
                class="toggle-password"
                onclick="togglePassword('password', this)"
            >
                Show
            </span>

        </div>

    </div>


    <div class="form-group">

        <label>
            Confirm Password
            <span class="required">*</span>
        </label>

        <div class="password-wrapper">

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Repeat password"
                required
            >

            <span
                class="toggle-password"
                onclick="togglePassword('confirm_password', this)"
            >
                Show
            </span>

        </div>

    </div>

</div>


<!-- BUTTONS -->

<div class="submit-area">

    <a
        href="index.php"
        class="btn btn-secondary"
    >
        ← Back
    </a>


    <button
        type="submit"
        class="btn btn-primary"
    >
        Register as Teacher
    </button>

</div>


</form>

</div>

</div>


<!-- ===================================
     FOOTER
==================================== -->

<div class="footer">

    © <?= date('Y') ?> Nisel Online Education.
    All Rights Reserved.

</div>


<script>

function togglePassword(id, element) {

    const input = document.getElementById(id);

    if (input.type === "password") {

        input.type = "text";

        element.textContent = "Hide";

    } else {

        input.type = "password";

        element.textContent = "Show";

    }

}

</script>


</body>
</html>
