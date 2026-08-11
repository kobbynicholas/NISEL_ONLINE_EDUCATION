<?php

session_start();

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN LOGIN
| PDO VERSION
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| _login.php is inside:
|
| online/admin/
|
| db.php is inside:
|
| online/config/
|
|--------------------------------------------------------------------------
*/

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| IF ADMIN IS ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['admin_id']) &&
    !empty($_SESSION['admin_id'])
) {

    header("Location: dashboard.php");

    exit;

}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = "";

$email = "";


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    $email =
        trim(
            $_POST['email']
            ?? ''
        );


    $password =
        $_POST['password']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $email === '' ||
        $password === ''
    ) {

        $error =
            "Please enter your email address and password.";

    }

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }

    else {


        try {


            /*
            |--------------------------------------------------------------------------
            | FIND ADMIN ACCOUNT
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("

                SELECT *

                FROM admins

                WHERE email = ?

                LIMIT 1

            ");


            $stmt->execute([

                $email

            ]);


            /*
            |--------------------------------------------------------------------------
            | FETCH ADMIN
            |--------------------------------------------------------------------------
            */

            $admin =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | ADMIN FOUND
            |--------------------------------------------------------------------------
            */

            if ($admin) {


                /*
                |--------------------------------------------------------------------------
                | VERIFY PASSWORD
                |--------------------------------------------------------------------------
                */

                if (
                    password_verify(
                        $password,
                        $admin['password']
                    )
                ) {


                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(
                        true
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | ADMIN SESSION
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['admin_id'] =
                        $admin['id'];


                    /*
                    |--------------------------------------------------------------------------
                    | ADMIN NAME
                    |--------------------------------------------------------------------------
                    |
                    | Your existing code uses username.
                    | We therefore keep username as the
                    | primary display name.
                    |
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['admin_name'] =
                        $admin['username']
                        ??
                        'Administrator';


                    $_SESSION['admin_email'] =
                        $admin['email'];


                    $_SESSION[
                        'admin_logged_in'
                    ] = true;


                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN SUCCESS
                    |--------------------------------------------------------------------------
                    */

                    header(
                        "Location: dashboard.php"
                    );

                    exit;


                } else {


                    /*
                    |--------------------------------------------------------------------------
                    | WRONG PASSWORD
                    |--------------------------------------------------------------------------
                    */

                    $error =
                        "Incorrect password.";

                }


            } else {


                /*
                |--------------------------------------------------------------------------
                | ADMIN NOT FOUND
                |--------------------------------------------------------------------------
                */

                $error =
                    "Admin account not found.";

            }


        } catch (
            PDOException $e
        ) {


            /*
            |--------------------------------------------------------------------------
            | DATABASE ERROR
            |--------------------------------------------------------------------------
            */

            $error =
                "Unable to process your login. "
                .
                "Please try again.";

            /*
            |--------------------------------------------------------------------------
            | DEVELOPMENT ONLY
            |--------------------------------------------------------------------------
            |
            | If you need the actual database error
            | while developing on XAMPP, temporarily
            | uncomment the following:
            |
            | $error = $e->getMessage();
            |
            |--------------------------------------------------------------------------
            */

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


<title>

    NISEL Admin Login

</title>


<style>

/* =====================================================
   RESET
===================================================== */

* {

    box-sizing:
        border-box;

}


html,
body {

    margin:
        0;

    padding:
        0;

}


/* =====================================================
   BODY
===================================================== */

body {

    min-height:
        100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #001f3f 0%,
            #003f73 45%,
            #0074b7 100%
        );

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        25px;

    position:
        relative;

    overflow:
        hidden;

}


/* =====================================================
   BACKGROUND DECORATIONS
===================================================== */

body::before {

    content:
        "";

    position:
        absolute;

    width:
        420px;

    height:
        420px;

    border-radius:
        50%;

    background:
        rgba(
            255,
            255,
            255,
            .06
        );

    top:
        -170px;

    right:
        -120px;

}


body::after {

    content:
        "";

    position:
        absolute;

    width:
        330px;

    height:
        330px;

    border-radius:
        50%;

    background:
        rgba(
            255,
            255,
            255,
            .05
        );

    bottom:
        -140px;

    left:
        -100px;

}


/* =====================================================
   LOGIN WRAPPER
===================================================== */

.login-wrapper {

    width:
        100%;

    max-width:
        450px;

    position:
        relative;

    z-index:
        2;

}


/* =====================================================
   LOGIN CARD
===================================================== */

.login-card {

    background:
        rgba(
            255,
            255,
            255,
            .98
        );

    border-radius:
        22px;

    padding:
        42px;

    box-shadow:
        0 25px 70px
        rgba(
            0,
            0,
            0,
            .25
        );

}


/* =====================================================
   LOGO
===================================================== */

.logo {

    text-align:
        center;

    margin-bottom:
        25px;

}


.logo-icon {

    width:
        72px;

    height:
        72px;

    margin:
        0 auto 15px;

    border-radius:
        18px;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0074b7
        );

    color:
        white;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        32px;

    box-shadow:
        0 8px 20px
        rgba(
            0,
            51,
            102,
            .25
        );

}


.logo-name {

    color:
        #003366;

    font-size:
        24px;

    font-weight:
        800;

    letter-spacing:
        .5px;

}


.logo-subtitle {

    color:
        #777;

    font-size:
        12px;

    letter-spacing:
        2px;

    margin-top:
        4px;

}


/* =====================================================
   HEADING
===================================================== */

.heading {

    text-align:
        center;

    margin-bottom:
        28px;

}


.heading h1 {

    margin:
        0 0 8px;

    color:
        #172b4d;

    font-size:
        26px;

}


.heading p {

    margin:
        0;

    color:
        #7a869a;

    font-size:
        14px;

    line-height:
        1.5;

}


/* =====================================================
   ERROR MESSAGE
===================================================== */

.error {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        10px;

    padding:
        13px 14px;

    margin-bottom:
        20px;

    background:
        #fff1f2;

    border:
        1px solid
        #fecdd3;

    border-radius:
        10px;

    color:
        #b42318;

    font-size:
        13px;

    line-height:
        1.5;

}


.error-icon {

    flex:
        0 0 auto;

    font-size:
        16px;

}


/* =====================================================
   FORM GROUP
===================================================== */

.form-group {

    margin-bottom:
        20px;

}


.form-group label {

    display:
        block;

    margin-bottom:
        8px;

    color:
        #344054;

    font-size:
        13px;

    font-weight:
        700;

}


/* =====================================================
   INPUT WRAPPER
===================================================== */

.input-wrapper {

    position:
        relative;

}


.input-icon {

    position:
        absolute;

    left:
        14px;

    top:
        50%;

    transform:
        translateY(-50%);

    font-size:
        17px;

    color:
        #98a2b3;

    pointer-events:
        none;

}


/* =====================================================
   INPUTS
===================================================== */

.form-control {

    width:
        100%;

    height:
        52px;

    padding:
        0 15px 0 45px;

    border:
        1px solid
        #d0d5dd;

    border-radius:
        10px;

    outline:
        none;

    background:
        #fff;

    color:
        #172b4d;

    font-size:
        14px;

    transition:
        .2s;

}


.form-control::placeholder {

    color:
        #98a2b3;

}


.form-control:focus {

    border-color:
        #0074b7;

    box-shadow:
        0 0 0 4px
        rgba(
            0,
            116,
            183,
            .10
        );

}


/* =====================================================
   PASSWORD TOGGLE
===================================================== */

.password-toggle {

    position:
        absolute;

    right:
        14px;

    top:
        50%;

    transform:
        translateY(-50%);

    border:
        none;

    background:
        transparent;

    cursor:
        pointer;

    color:
        #98a2b3;

    font-size:
        16px;

    padding:
        4px;

}


.password-toggle:hover {

    color:
        #003366;

}


/* =====================================================
   LOGIN BUTTON
===================================================== */

.login-button {

    width:
        100%;

    height:
        53px;

    border:
        none;

    border-radius:
        10px;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0074b7
        );

    color:
        white;

    font-size:
        15px;

    font-weight:
        700;

    cursor:
        pointer;

    box-shadow:
        0 8px 18px
        rgba(
            0,
            51,
            102,
            .20
        );

    transition:
        .2s;

}


.login-button:hover {

    transform:
        translateY(-1px);

    box-shadow:
        0 10px 22px
        rgba(
            0,
            51,
            102,
            .28
        );

}


.login-button:active {

    transform:
        translateY(0);

}


/* =====================================================
   SECURITY NOTE
===================================================== */

.security-note {

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    margin-top:
        20px;

    color:
        #98a2b3;

    font-size:
        11px;

}


/* =====================================================
   FOOTER
===================================================== */

.footer {

    text-align:
        center;

    margin-top:
        25px;

    color:
        rgba(
            255,
            255,
            255,
            .75
        );

    font-size:
        12px;

    line-height:
        1.6;

}


/* =====================================================
   MOBILE
===================================================== */

@media(
    max-width: 500px
) {

    body {

        padding:
            15px;

    }


    .login-card {

        padding:
            30px 22px;

        border-radius:
            18px;

    }


    .logo-icon {

        width:
            62px;

        height:
            62px;

        font-size:
            27px;

    }


    .logo-name {

        font-size:
            21px;

    }


    .heading h1 {

        font-size:
            23px;

    }

}

</style>

</head>


<body>


<div class="login-wrapper">


    <div class="login-card">


        <!-- =============================================
             LOGO
        ============================================== -->

        <div class="logo">


            <div class="logo-icon">

                🎓

            </div>


            <div class="logo-name">

                NISEL

            </div>


            <div class="logo-subtitle">

                ONLINE EDUCATION

            </div>


        </div>



        <!-- =============================================
             HEADING
        ============================================== -->

        <div class="heading">

            <h1>

                Administrator Login

            </h1>


            <p>

                Sign in to manage the NISEL
                Online Education platform.

            </p>

        </div>



        <!-- =============================================
             ERROR
        ============================================== -->

        <?php if (
            $error !== ''
        ): ?>

            <div class="error">

                <span class="error-icon">

                    ⚠️

                </span>


                <span>

                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </div>

        <?php endif; ?>



        <!-- =============================================
             LOGIN FORM
        ============================================== -->

        <form
            method="POST"
            action=""
            autocomplete="on"
        >


            <!-- EMAIL -->

            <div class="form-group">


                <label for="email">

                    Email Address

                </label>


                <div class="input-wrapper">


                    <span class="input-icon">

                        ✉️

                    </span>


                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your admin email"
                        value="<?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        autocomplete="username"
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
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Show password"
                    >

                        👁️

                    </button>


                </div>


            </div>



            <!-- LOGIN -->

            <button
                type="submit"
                name="login"
                class="login-button"
            >

                🔐 &nbsp; Sign In to Admin Portal

            </button>


        </form>



        <!-- =============================================
             SECURITY
        ============================================== -->

        <div class="security-note">

            🔒 Secure Administrator Access

        </div>


    </div>



    <!-- FOOTER -->

    <div class="footer">

        © <?= date('Y') ?>

        NISEL ONLINE EDUCATION

        <br>

        Administration Portal

    </div>


</div>



<script>

/*
|--------------------------------------------------------------------------
| SHOW / HIDE PASSWORD
|--------------------------------------------------------------------------
*/

const password =
    document.getElementById(
        "password"
    );


const togglePassword =
    document.getElementById(
        "togglePassword"
    );


togglePassword.addEventListener(
    "click",
    function () {


        if (
            password.type ===
            "password"
        ) {

            password.type =
                "text";

            togglePassword.textContent =
                "🙈";

            togglePassword.setAttribute(
                "aria-label",
                "Hide password"
            );

        } else {

            password.type =
                "password";

            togglePassword.textContent =
                "👁️";

            togglePassword.setAttribute(
                "aria-label",
                "Show password"
            );

        }

    }
);

</script>


</body>

</html>
