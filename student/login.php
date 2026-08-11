<?php

session_start();

require "../config/db.php";

$message = "";
$message_type = "";

$email = "";


/* =========================================================
   STUDENT LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    /* =====================================================
       VALIDATION
    ===================================================== */

    if ($email === "" || $password === "") {

        $message =
            "Please enter your email address and password.";

        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message =
            "Please enter a valid email address.";

        $message_type = "error";

    } else {

        try {

            /* =================================================
               FIND STUDENT
            ================================================= */

            $stmt = $pdo->prepare("
                SELECT *
                FROM students
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);

            $student = $stmt->fetch(PDO::FETCH_ASSOC);


            /* =================================================
               CHECK ACCOUNT
            ================================================= */

            if (!$student) {

                $message =
                    "Invalid email or password.";

                $message_type = "error";

            } elseif (
                !password_verify(
                    $password,
                    $student['password']
                )
            ) {

                $message =
                    "Invalid email or password.";

                $message_type = "error";

            } else {

                /* =============================================
                   LOGIN SUCCESS
                ============================================= */

                session_regenerate_id(true);


                $_SESSION['student_logged_in'] = true;

                $_SESSION['student_id'] =
                    $student['id'];

                $_SESSION['student_name'] =
                    $student['student_name'];

                $_SESSION['student_email'] =
                    $student['email'];


                /* =============================================
                   REDIRECT TO DASHBOARD
                ============================================= */

                header(
                    "Location: dashboard.php"
                );

                exit;
            }


        } catch (PDOException $e) {

            /*
             * Do not expose database errors
             * to the student.
             */

            $message =
                "Unable to sign you in at the moment. Please try again.";

            $message_type = "error";
        }
    }
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

<meta
    name="theme-color"
    content="#063b73"
>

<title>
    Student Login | NISEL Online Education
</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {

    box-sizing:
        border-box;

    margin:
        0;

    padding:
        0;

}


/* =========================================================
   BODY
========================================================= */

body {

    min-height:
        100vh;

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

    color:
        #172033;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        25px;

}


/* =========================================================
   LOGIN WRAPPER
========================================================= */

.login-wrapper {

    width:
        100%;

    max-width:
        1000px;

    min-height:
        600px;

    display:
        grid;

    grid-template-columns:
        .9fr 1.1fr;

    background:
        #ffffff;

    border-radius:
        24px;

    overflow:
        hidden;

    box-shadow:
        0 25px 70px
        rgba(20,55,95,.15);

}


/* =========================================================
   LEFT BRAND PANEL
========================================================= */

.brand-panel {

    position:
        relative;

    padding:
        50px 45px;

    background:
        linear-gradient(
            145deg,
            #063b73,
            #075da8
        );

    color:
        white;

    display:
        flex;

    flex-direction:
        column;

    justify-content:
        space-between;

    overflow:
        hidden;

}


/* =========================================================
   DECORATIVE CIRCLE
========================================================= */

.brand-panel::before {

    content:
        "";

    position:
        absolute;

    width:
        300px;

    height:
        300px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.07);

    right:
        -120px;

    top:
        -100px;

}


.brand-panel::after {

    content:
        "";

    position:
        absolute;

    width:
        230px;

    height:
        230px;

    border-radius:
        50%;

    background:
        rgba(255,255,255,.05);

    left:
        -100px;

    bottom:
        -100px;

}


/* =========================================================
   BRAND
========================================================= */

.brand-logo {

    position:
        relative;

    z-index:
        2;

    display:
        flex;

    align-items:
        center;

    gap:
        13px;

}


.logo-icon {

    width:
        53px;

    height:
        53px;

    border-radius:
        15px;

    background:
        rgba(255,255,255,.14);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        25px;

    border:
        1px solid
        rgba(255,255,255,.2);

}


.logo-text strong {

    display:
        block;

    font-size:
        20px;

    letter-spacing:
        1.5px;

}


.logo-text span {

    display:
        block;

    font-size:
        10px;

    opacity:
        .72;

    letter-spacing:
        2px;

    margin-top:
        3px;

}


/* =========================================================
   BRAND CONTENT
========================================================= */

.brand-content {

    position:
        relative;

    z-index:
        2;

    margin-top:
        45px;

}


.brand-content h1 {

    font-size:
        38px;

    line-height:
        1.15;

    margin-bottom:
        18px;

    font-weight:
        750;

}


.brand-content h1 span {

    color:
        #8fd3ff;

}


.brand-content p {

    color:
        rgba(255,255,255,.82);

    line-height:
        1.7;

    font-size:
        14px;

    max-width:
        380px;

}


/* =========================================================
   BENEFITS
========================================================= */

.benefits {

    margin-top:
        32px;

    position:
        relative;

    z-index:
        2;

}


.benefit {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;

    margin-bottom:
        16px;

    font-size:
        13px;

    color:
        rgba(255,255,255,.9);

}


.benefit-icon {

    width:
        30px;

    height:
        30px;

    border-radius:
        9px;

    background:
        rgba(255,255,255,.13);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        #8fd3ff;

    font-weight:
        bold;

}


/* =========================================================
   BRAND FOOTER
========================================================= */

.brand-footer {

    position:
        relative;

    z-index:
        2;

    color:
        rgba(255,255,255,.62);

    font-size:
        11px;

}


/* =========================================================
   FORM PANEL
========================================================= */

.form-panel {

    padding:
        50px 55px;

    display:
        flex;

    flex-direction:
        column;

    justify-content:
        center;

}


/* =========================================================
   FORM HEADER
========================================================= */

.form-header {

    margin-bottom:
        30px;

}


.small-title {

    color:
        #0867b2;

    font-size:
        11px;

    font-weight:
        700;

    text-transform:
        uppercase;

    letter-spacing:
        1.5px;

    margin-bottom:
        8px;

}


.form-header h2 {

    font-size:
        30px;

    color:
        #172033;

    margin-bottom:
        8px;

}


.form-header p {

    color:
        #718096;

    font-size:
        13px;

    line-height:
        1.6;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding:
        14px 16px;

    border-radius:
        12px;

    margin-bottom:
        22px;

    font-size:
        13px;

    display:
        flex;

    align-items:
        flex-start;

    gap:
        10px;

    line-height:
        1.5;

}


.message.error {

    background:
        #fff1f2;

    border:
        1px solid #fecdd3;

    color:
        #be123c;

}


.message.success {

    background:
        #ecfdf3;

    border:
        1px solid #b7ebcd;

    color:
        #18794e;

}


.message-icon {

    font-weight:
        bold;

    font-size:
        16px;

}


/* =========================================================
   FORM GROUP
========================================================= */

.form-group {

    margin-bottom:
        19px;

}


.form-group label {

    display:
        block;

    font-size:
        13px;

    font-weight:
        650;

    color:
        #273449;

    margin-bottom:
        8px;

}


/* =========================================================
   INPUT WRAPPER
========================================================= */

.input-wrapper {

    position:
        relative;

}


.input-icon {

    position:
        absolute;

    left:
        15px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        #8a98ab;

    font-size:
        16px;

    pointer-events:
        none;

}


.input-wrapper input {

    width:
        100%;

    height:
        51px;

    border:
        1px solid #d9e1eb;

    border-radius:
        12px;

    background:
        #f9fbfd;

    padding:
        0 48px 0 45px;

    font-size:
        14px;

    color:
        #172033;

    outline:
        none;

    transition:
        border-color .2s,
        box-shadow .2s,
        background .2s;

}


.input-wrapper input::placeholder {

    color:
        #a0acbb;

}


.input-wrapper input:hover {

    border-color:
        #b9c7d8;

}


.input-wrapper input:focus {

    background:
        #ffffff;

    border-color:
        #0875c1;

    box-shadow:
        0 0 0 4px
        rgba(8,117,193,.10);

}


/* =========================================================
   PASSWORD TOGGLE
========================================================= */

.password-toggle {

    position:
        absolute;

    right:
        13px;

    top:
        50%;

    transform:
        translateY(-50%);

    border:
        none;

    background:
        transparent;

    color:
        #0867b2;

    font-size:
        11px;

    font-weight:
        700;

    cursor:
        pointer;

    padding:
        5px;

}


.password-toggle:hover {

    color:
        #003b70;

}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-button {

    width:
        100%;

    height:
        52px;

    border:
        none;

    border-radius:
        12px;

    background:
        linear-gradient(
            135deg,
            #063b73,
            #0875c1
        );

    color:
        #ffffff;

    font-size:
        15px;

    font-weight:
        700;

    cursor:
        pointer;

    box-shadow:
        0 8px 20px
        rgba(6,59,115,.20);

    transition:
        transform .2s,
        box-shadow .2s,
        opacity .2s;

}


.login-button:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 12px 25px
        rgba(6,59,115,.28);

}


.login-button:active {

    transform:
        translateY(0);

}


.login-button:disabled {

    opacity:
        .7;

    cursor:
        not-allowed;

}


/* =========================================================
   REGISTER
========================================================= */

.register-area {

    text-align:
        center;

    margin-top:
        24px;

    padding-top:
        22px;

    border-top:
        1px solid #edf1f5;

    color:
        #718096;

    font-size:
        13px;

}


.register-area a {

    color:
        #0867b2;

    text-decoration:
        none;

    font-weight:
        700;

    margin-left:
        4px;

}


.register-area a:hover {

    text-decoration:
        underline;

}


/* =========================================================
   HOME LINK
========================================================= */

.home-link {

    text-align:
        center;

    margin-top:
        17px;

}


.home-link a {

    color:
        #7b8798;

    text-decoration:
        none;

    font-size:
        12px;

    transition:
        .2s;

}


.home-link a:hover {

    color:
        #0867b2;

}


/* =========================================================
   SECURITY
========================================================= */

.security-note {

    text-align:
        center;

    margin-top:
        22px;

    color:
        #9aa6b5;

    font-size:
        10px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 850px) {

    .login-wrapper {

        grid-template-columns:
            1fr;

        max-width:
            600px;

    }


    .brand-panel {

        padding:
            35px;

    }


    .brand-content {

        margin-top:
            30px;

    }


    .brand-content h1 {

        font-size:
            30px;

    }


    .benefits {

        display:
            grid;

        grid-template-columns:
            1fr 1fr;

        gap:
            8px;

    }


    .benefit {

        margin-bottom:
            5px;

    }


    .brand-footer {

        margin-top:
            25px;

    }

}


@media (max-width: 560px) {

    body {

        padding:
            0;

    }


    .login-wrapper {

        min-height:
            100vh;

        border-radius:
            0;

        box-shadow:
            none;

    }


    .brand-panel {

        padding:
            28px 22px;

    }


    .brand-content h1 {

        font-size:
            28px;

    }


    .benefits {

        grid-template-columns:
            1fr;

    }


    .form-panel {

        padding:
            35px 22px;

    }


    .form-header h2 {

        font-size:
            25px;

    }

}


/* =========================================================
   ACCESSIBILITY
========================================================= */

input:focus-visible,
button:focus-visible,
a:focus-visible {

    outline:
        3px solid
        rgba(8,117,193,.25);

    outline-offset:
        2px;

}

</style>

</head>


<body>


<div class="login-wrapper">


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

                    <strong>
                        NISEL
                    </strong>

                    <span>
                        ONLINE EDUCATION
                    </span>

                </div>

            </div>



            <!-- CONTENT -->

            <div class="brand-content">

                <h1>

                    Welcome
                    <span>back.</span>

                </h1>


                <p>

                    Sign in to your NISEL student
                    account and continue your
                    learning journey.

                </p>


                <div class="benefits">


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Access your online lessons
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            View your class schedule
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Manage your bookings
                        </span>

                    </div>


                    <div class="benefit">

                        <div class="benefit-icon">
                            ✓
                        </div>

                        <span>
                            Track your payments
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
         LOGIN FORM
    ====================================================== -->

    <section class="form-panel">


        <div class="form-header">

            <div class="small-title">
                Student Portal
            </div>


            <h2>
                Sign in to your account
            </h2>


            <p>

                Enter your registered email address
                and password to continue.

            </p>

        </div>



        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ""): ?>

            <div
                class="message
                <?= htmlspecialchars($message_type); ?>"
            >

                <div class="message-icon">

                    <?=
                        $message_type === "success"
                        ? "✓"
                        : "!"
                    ?>

                </div>


                <div>

                    <?= htmlspecialchars($message); ?>

                </div>

            </div>

        <?php endif; ?>



        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            action=""
            id="loginForm"
            autocomplete="on"
        >


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">

                    Email Address

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



            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">

                    Password

                </label>


                <div class="input-wrapper">


                    <span class="input-icon">
                        🔒
                    </span>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="passwordToggle"
                    >
                        Show
                    </button>


                </div>

            </div>



            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-button"
                id="loginButton"
            >

                Sign In to Student Portal

            </button>


        </form>



        <!-- =================================================
             REGISTER
        ================================================== -->

        <div class="register-area">

            Don't have a NISEL student account?

            <a href="register.php">
                Create Account
            </a>

        </div>



        <!-- =================================================
             HOME
        ================================================== -->

        <div class="home-link">

            <a href="../index.html">

                ← Return to NISEL Online Education

            </a>

        </div>



        <!-- =================================================
             SECURITY
        ================================================== -->

        <div class="security-note">

            🔒 Your login information is securely protected.

        </div>


    </section>


</div>



<script>

/* =========================================================
   PASSWORD SHOW / HIDE
========================================================= */

const passwordInput =
    document.getElementById("password");

const passwordToggle =
    document.getElementById("passwordToggle");


passwordToggle.addEventListener(
    "click",
    function () {

        if (
            passwordInput.type === "password"
        ) {

            passwordInput.type =
                "text";

            passwordToggle.textContent =
                "Hide";

        } else {

            passwordInput.type =
                "password";

            passwordToggle.textContent =
                "Show";

        }

    }
);


/* =========================================================
   LOGIN BUTTON
========================================================= */

const loginForm =
    document.getElementById("loginForm");

const loginButton =
    document.getElementById("loginButton");


loginForm.addEventListener(
    "submit",
    function () {

        loginButton.disabled =
            true;

        loginButton.textContent =
            "Signing In...";

    }
);


/* =========================================================
   EMAIL FOCUS
========================================================= */

window.addEventListener(
    "load",
    function () {

        const email =
            document.getElementById("email");

        if (email.value === "") {

            email.focus();

        }

    }
);

</script>


</body>

</html>
