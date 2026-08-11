<?php

require "../config/db.php";

$message = "";
$message_type = "";

$student_name = "";
$email = "";
$phone = "";


/* =========================================================
   STUDENT REGISTRATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_name = trim($_POST['student_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $student_name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === "" ||
        $confirm === ""
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif ($password !== $confirm) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } else {

        try {

            /* =================================================
               CHECK EXISTING EMAIL
            ================================================= */

            $stmt = $pdo->prepare("
                SELECT id
                FROM students
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            if ($stmt->fetch()) {

                $message =
                    "An account with this email already exists.";

                $message_type = "error";

            } else {

                /* =================================================
                   HASH PASSWORD
                ================================================= */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /* =================================================
                   CREATE STUDENT
                ================================================= */

                $stmt = $pdo->prepare("
                    INSERT INTO students
                    (
                        student_name,
                        email,
                        phone,
                        password
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $student_name,
                    $email,
                    $phone,
                    $hashed_password
                ]);


                $message =
                    "Registration successful! Your student account has been created.";

                $message_type = "success";


                /* Clear form after successful registration */

                $student_name = "";
                $email = "";
                $phone = "";
            }

        } catch (PDOException $e) {

            /*
             * Do not expose database errors to students.
             */

            $message =
                "Registration failed. Please try again later.";

            $message_type = "error";
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

<meta name="theme-color" content="#063b73">

<title>
    Student Registration | NISEL Online Education
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


/* =========================================================
   BODY
========================================================= */

body {

    min-height: 100vh;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #eef5ff 0%,
            #f7faff 50%,
            #e9f1fb 100%
        );

    color: #172033;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 30px 15px;

}


/* =========================================================
   MAIN WRAPPER
========================================================= */

.register-wrapper {

    width: 100%;
    max-width: 1050px;

    display: grid;

    grid-template-columns: 0.9fr 1.1fr;

    background: #ffffff;

    border-radius: 24px;

    overflow: hidden;

    box-shadow:
        0 25px 70px rgba(20, 55, 95, 0.15);

}


/* =========================================================
   LEFT BRAND PANEL
========================================================= */

.brand-panel {

    position: relative;

    padding: 55px 45px;

    background:
        linear-gradient(
            145deg,
            #063b73,
            #075da8
        );

    color: #ffffff;

    display: flex;

    flex-direction: column;

    justify-content: space-between;

    overflow: hidden;
}


/* Decorative circles */

.brand-panel::before {

    content: "";

    position: absolute;

    width: 280px;
    height: 280px;

    border-radius: 50%;

    background: rgba(255,255,255,0.07);

    top: -100px;
    right: -100px;
}


.brand-panel::after {

    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    border-radius: 50%;

    background: rgba(255,255,255,0.05);

    bottom: -90px;
    left: -90px;
}


/* =========================================================
   LOGO
========================================================= */

.brand-logo {

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    gap: 13px;

}


.logo-icon {

    width: 52px;
    height: 52px;

    border-radius: 15px;

    background: rgba(255,255,255,0.15);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 25px;

    border: 1px solid rgba(255,255,255,0.2);

}


.logo-text strong {

    display: block;

    font-size: 20px;

    letter-spacing: 1.5px;

}


.logo-text span {

    display: block;

    font-size: 11px;

    opacity: 0.75;

    letter-spacing: 1.5px;

    margin-top: 3px;

}


/* =========================================================
   BRAND CONTENT
========================================================= */

.brand-content {

    position: relative;

    z-index: 2;

    margin-top: 50px;

}


.brand-content h1 {

    font-size: 39px;

    line-height: 1.15;

    margin-bottom: 18px;

    font-weight: 750;

}


.brand-content h1 span {

    color: #8fd3ff;

}


.brand-content p {

    color: rgba(255,255,255,0.82);

    line-height: 1.7;

    font-size: 15px;

    max-width: 390px;

}


/* =========================================================
   BENEFITS
========================================================= */

.benefits {

    position: relative;

    z-index: 2;

    margin-top: 35px;

}


.benefit {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 17px;

    font-size: 14px;

    color: rgba(255,255,255,0.9);

}


.benefit-icon {

    width: 30px;
    height: 30px;

    flex-shrink: 0;

    border-radius: 9px;

    background: rgba(255,255,255,0.13);

    display: flex;

    align-items: center;
    justify-content: center;

    color: #8fd3ff;

    font-weight: bold;

}


/* =========================================================
   BRAND FOOTER
========================================================= */

.brand-footer {

    position: relative;

    z-index: 2;

    color: rgba(255,255,255,0.65);

    font-size: 12px;

}


/* =========================================================
   FORM PANEL
========================================================= */

.form-panel {

    padding: 45px;

    background: #ffffff;

}


/* =========================================================
   FORM HEADER
========================================================= */

.form-header {

    margin-bottom: 30px;

}


.form-header .small-title {

    color: #0867b2;

    font-size: 12px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 8px;

}


.form-header h2 {

    font-size: 29px;

    color: #172033;

    margin-bottom: 8px;

}


.form-header p {

    color: #718096;

    font-size: 14px;

    line-height: 1.6;

}


/* =========================================================
   ALERT
========================================================= */

.alert {

    padding: 14px 16px;

    border-radius: 12px;

    margin-bottom: 22px;

    font-size: 13px;

    display: flex;

    align-items: flex-start;

    gap: 10px;

    line-height: 1.5;

}


.alert.success {

    background: #ecfdf3;

    border: 1px solid #b7ebcd;

    color: #18794e;

}


.alert.error {

    background: #fff1f2;

    border: 1px solid #fecdd3;

    color: #be123c;

}


.alert-icon {

    font-weight: bold;

    font-size: 16px;

}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom: 19px;

}


.form-group label {

    display: flex;

    align-items: center;

    gap: 4px;

    font-size: 13px;

    font-weight: 650;

    color: #273449;

    margin-bottom: 8px;

}


.required {

    color: #e11d48;

}


/* =========================================================
   INPUT WRAPPER
========================================================= */

.input-wrapper {

    position: relative;

}


.input-icon {

    position: absolute;

    left: 15px;

    top: 50%;

    transform: translateY(-50%);

    color: #8a98ab;

    font-size: 16px;

    pointer-events: none;

}


.input-wrapper input {

    width: 100%;

    height: 50px;

    border: 1px solid #d9e1eb;

    border-radius: 12px;

    background: #f9fbfd;

    padding: 0 15px 0 45px;

    font-size: 14px;

    color: #172033;

    outline: none;

    transition:
        border-color .2s,
        box-shadow .2s,
        background .2s;

}


.input-wrapper input::placeholder {

    color: #a0acbb;

}


.input-wrapper input:hover {

    border-color: #b9c7d8;

}


.input-wrapper input:focus {

    background: #ffffff;

    border-color: #0875c1;

    box-shadow:
        0 0 0 4px rgba(8,117,193,0.10);

}


/* =========================================================
   PASSWORD INPUT
========================================================= */

.password-wrapper input {

    padding-right: 70px;

}


.password-toggle {

    position: absolute;

    right: 13px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: transparent;

    color: #0867b2;

    font-size: 12px;

    font-weight: 650;

    cursor: pointer;

    padding: 5px;

}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

.password-strength {

    margin-top: 8px;

    display: none;

}


.strength-bar {

    height: 4px;

    width: 100%;

    background: #e7ecf2;

    border-radius: 10px;

    overflow: hidden;

}


.strength-progress {

    height: 100%;

    width: 0;

    transition: width .25s;

}


.strength-text {

    font-size: 11px;

    color: #7b8798;

    margin-top: 5px;

}


/* =========================================================
   FORM ROW
========================================================= */

.form-row {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 15px;

}


/* =========================================================
   TERMS
========================================================= */

.terms {

    display: flex;

    align-items: flex-start;

    gap: 9px;

    margin: 7px 0 22px;

    color: #718096;

    font-size: 12px;

    line-height: 1.5;

}


.terms input {

    margin-top: 2px;

    accent-color: #0867b2;

}


.terms a {

    color: #0867b2;

    text-decoration: none;

    font-weight: 600;

}


/* =========================================================
   REGISTER BUTTON
========================================================= */

.register-button {

    width: 100%;

    height: 52px;

    border: none;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #063b73,
            #0875c1
        );

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 8px 20px rgba(6,59,115,0.20);

    transition:
        transform .2s,
        box-shadow .2s,
        opacity .2s;

}


.register-button:hover {

    transform: translateY(-1px);

    box-shadow:
        0 12px 25px rgba(6,59,115,0.28);

}


.register-button:active {

    transform: translateY(0);

}


.register-button:disabled {

    opacity: .7;

    cursor: not-allowed;

}


/* =========================================================
   LOGIN
========================================================= */

.login-area {

    text-align: center;

    margin-top: 24px;

    padding-top: 22px;

    border-top: 1px solid #edf1f5;

    color: #718096;

    font-size: 13px;

}


.login-area a {

    color: #0867b2;

    text-decoration: none;

    font-weight: 700;

    margin-left: 4px;

}


.login-area a:hover {

    text-decoration: underline;

}


/* =========================================================
   SECURITY NOTE
========================================================= */

.security-note {

    margin-top: 18px;

    text-align: center;

    color: #9aa6b5;

    font-size: 11px;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 850px) {

    .register-wrapper {

        grid-template-columns: 1fr;

        max-width: 600px;

    }

    .brand-panel {

        padding: 30px;

    }

    .brand-content {

        margin-top: 35px;

    }

    .brand-content h1 {

        font-size: 30px;

    }

    .benefits {

        display: grid;

        grid-template-columns: 1fr 1fr;

        gap: 8px;

    }

    .benefit {

        margin-bottom: 5px;

    }

    .brand-footer {

        margin-top: 25px;

    }

}


@media (max-width: 560px) {

    body {

        padding: 0;

    }

    .register-wrapper {

        min-height: 100vh;

        border-radius: 0;

        box-shadow: none;

    }

    .brand-panel {

        padding: 28px 22px;

    }

    .brand-content {

        margin-top: 30px;

    }

    .brand-content h1 {

        font-size: 28px;

    }

    .benefits {

        grid-template-columns: 1fr;

    }

    .form-panel {

        padding: 30px 22px;

    }

    .form-header h2 {

        font-size: 25px;

    }

    .form-row {

        grid-template-columns: 1fr;

        gap: 0;

    }

}


/* =========================================================
   ACCESSIBILITY
========================================================= */

input:focus-visible,
button:focus-visible,
a:focus-visible {

    outline: 3px solid rgba(8,117,193,0.25);

    outline-offset: 2px;

}

</style>

</head>


<body>


<div class="register-wrapper">


    <!-- =====================================================
         BRAND PANEL
    ====================================================== -->

    <section class="brand-panel">


        <div>


            <!-- LOGO -->

            <div class="brand-logo">

                <div class="logo-icon">
                    🎓
                </div>

                <div class="logo-text">

                    <strong>NISEL</strong>

                    <span>
                        ONLINE EDUCATION
                    </span>

                </div>

            </div>


            <!-- CONTENT -->

            <div class="brand-content">

                <h1>
                    Start your
                    <span>learning journey.</span>
                </h1>

                <p>
                    Create your NISEL student account and
                    gain access to your online learning
                    experience, lessons and academic resources.
                </p>


                <div class="benefits">

                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Access online lessons
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Connect with teachers
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Manage your classes
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Track your learning
                        </span>

                    </div>

                </div>

            </div>

        </div>


        <div class="brand-footer">

            © <?= date('Y'); ?>
            Nisel Online Education.
            All Rights Reserved.

        </div>


    </section>



    <!-- =====================================================
         FORM PANEL
    ====================================================== -->

    <section class="form-panel">


        <div class="form-header">

            <div class="small-title">
                Student Portal
            </div>

            <h2>
                Create your account
            </h2>

            <p>
                Fill in your details below to create
                your NISEL student account.
            </p>

        </div>



        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ""): ?>

            <div class="alert <?= htmlspecialchars($message_type); ?>">

                <div class="alert-icon">

                    <?= $message_type === "success"
                        ? "✓"
                        : "!" ?>

                </div>

                <div>

                    <?= htmlspecialchars($message); ?>

                    <?php if ($message_type === "success"): ?>

                        <div style="margin-top:5px;">

                            <a
                                href="login.php"
                                style="
                                    color:#18794e;
                                    font-weight:700;
                                    text-decoration:underline;
                                "
                            >
                                Continue to Login
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             REGISTRATION FORM
        ================================================== -->

        <form
            method="POST"
            action=""
            autocomplete="on"
            id="registrationForm"
        >


            <!-- STUDENT NAME -->

            <div class="form-group">

                <label for="student_name">

                    Student Name

                    <span class="required">*</span>

                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        👤
                    </span>

                    <input
                        type="text"
                        id="student_name"
                        name="student_name"
                        value="<?= htmlspecialchars($student_name); ?>"
                        placeholder="Enter your full name"
                        autocomplete="name"
                        maxlength="100"
                        required
                    >

                </div>

            </div>



            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">

                    Email Address

                    <span class="required">*</span>

                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        ✉
                    </span>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($email); ?>"
                        placeholder="you@example.com"
                        autocomplete="email"
                        maxlength="150"
                        required
                    >

                </div>

            </div>



            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">

                    Phone Number

                    <span class="required">*</span>

                </label>

                <div class="input-wrapper">

                    <span class="input-icon">
                        ☎
                    </span>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($phone); ?>"
                        placeholder="e.g. 0599363266"
                        autocomplete="tel"
                        maxlength="20"
                        required
                    >

                </div>

            </div>



            <!-- PASSWORD ROW -->

            <div class="form-row">


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">

                        Password

                        <span class="required">*</span>

                    </label>

                    <div class="input-wrapper password-wrapper">

                        <span class="input-icon">
                            🔒
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 6 characters"
                            autocomplete="new-password"
                            minlength="6"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword(
                                'password',
                                this
                            )"
                        >
                            Show
                        </button>

                    </div>


                    <div
                        class="password-strength"
                        id="passwordStrength"
                    >

                        <div class="strength-bar">

                            <div
                                class="strength-progress"
                                id="strengthProgress"
                            ></div>

                        </div>

                        <div
                            class="strength-text"
                            id="strengthText"
                        >
                            Password strength
                        </div>

                    </div>

                </div>



                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">

                        Confirm Password

                        <span class="required">*</span>

                    </label>

                    <div class="input-wrapper password-wrapper">

                        <span class="input-icon">
                            🔐
                        </span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Repeat password"
                            autocomplete="new-password"
                            minlength="6"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword(
                                'confirm_password',
                                this
                            )"
                        >
                            Show
                        </button>

                    </div>

                </div>


            </div>



            <!-- TERMS -->

            <div class="terms">

                <input
                    type="checkbox"
                    id="terms"
                    required
                >

                <label for="terms">

                    I confirm that the information provided
                    is accurate and I agree to use the NISEL
                    Online Education platform responsibly.

                </label>

            </div>



            <!-- BUTTON -->

            <button
                type="submit"
                class="register-button"
                id="registerButton"
            >

                Create Student Account

            </button>


        </form>



        <!-- LOGIN -->

        <div class="login-area">

            Already have a NISEL account?

            <a href="login.php">
                Login here
            </a>

        </div>


        <div class="security-note">

            🔒 Your password is securely encrypted.

        </div>


    </section>


</div>



<script>

/* =========================================================
   SHOW / HIDE PASSWORD
========================================================= */

function togglePassword(id, button) {

    const input = document.getElementById(id);

    if (input.type === "password") {

        input.type = "text";

        button.textContent = "Hide";

    } else {

        input.type = "password";

        button.textContent = "Show";

    }

}


/* =========================================================
   PASSWORD STRENGTH
========================================================= */

const passwordInput =
    document.getElementById("password");

const strengthBox =
    document.getElementById("passwordStrength");

const strengthProgress =
    document.getElementById("strengthProgress");

const strengthText =
    document.getElementById("strengthText");


passwordInput.addEventListener("input", function () {

    const password = this.value;

    if (password.length === 0) {

        strengthBox.style.display = "none";

        return;

    }


    strengthBox.style.display = "block";


    let score = 0;


    /* Length */

    if (password.length >= 6) {
        score++;
    }

    if (password.length >= 10) {
        score++;
    }


    /* Lowercase */

    if (/[a-z]/.test(password)) {
        score++;
    }


    /* Uppercase */

    if (/[A-Z]/.test(password)) {
        score++;
    }


    /* Number */

    if (/[0-9]/.test(password)) {
        score++;
    }


    /* Special character */

    if (/[^A-Za-z0-9]/.test(password)) {
        score++;
    }


    const percentage =
        Math.min(score / 6 * 100, 100);

    strengthProgress.style.width =
        percentage + "%";


    if (score <= 2) {

        strengthText.textContent =
            "Weak password";

    } else if (score <= 4) {

        strengthText.textContent =
            "Moderate password";

    } else {

        strengthText.textContent =
            "Strong password";

    }

});


/* =========================================================
   FORM SUBMISSION
========================================================= */

document
    .getElementById("registrationForm")
    .addEventListener("submit", function () {

        const button =
            document.getElementById("registerButton");

        button.disabled = true;

        button.textContent =
            "Creating Account...";

    });


/* =========================================================
   PASSWORD MATCH CHECK
========================================================= */

const confirmPassword =
    document.getElementById("confirm_password");


confirmPassword.addEventListener("input", function () {

    const password =
        passwordInput.value;

    if (
        this.value !== "" &&
        password !== this.value
    ) {

        this.style.borderColor =
            "#e11d48";

    } else {

        this.style.borderColor =
            "";

    }

});

</script>


</body>

</html>
