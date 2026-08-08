<?php

session_start();

require "../config/db.php";


/* =========================================================
   IF TEACHER IS ALREADY LOGGED IN
========================================================= */

if (isset($_SESSION['teacher_id'])) {

    header("Location: dashboard.php");
    exit;

}


/* =========================================================
   VARIABLES
========================================================= */

$error = "";


/* =========================================================
   LOGIN PROCESS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST['login'] ?? "");
    $password = $_POST['password'] ?? "";


    /* =============================================
       VALIDATION
    ============================================= */

    if ($login === "" || $password === "") {

        $error =
            "Please enter your Teacher ID/email and password.";

    } else {


        /* =============================================
           FIND TEACHER
           LOGIN CAN BE TEACHER ID OR EMAIL
        ============================================= */

        $stmt = $conn->prepare("
            SELECT
                id,
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
            FROM teachers
            WHERE teacher_id = ?
               OR email = ?
            LIMIT 1
        ");


        $stmt->bind_param(
            "ss",
            $login,
            $login
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        /* =============================================
           CHECK ACCOUNT
        ============================================= */

        if ($result->num_rows === 1) {

            $teacher =
                $result->fetch_assoc();


            /* =========================================
               CHECK ACCOUNT STATUS
            ========================================= */

            if (
                strtolower(
                    trim($teacher['status'])
                ) !== "active"
            ) {

                $error =
                    "Your teacher account is not active. "
                    . "Please contact NISEL ONLINE EDUCATION.";

            }


            /* =========================================
               CHECK PASSWORD
            ========================================= */

            elseif (
                password_verify(
                    $password,
                    $teacher['password']
                )
            ) {


                /* =====================================
                   CREATE TEACHER SESSION
                ===================================== */

                session_regenerate_id(true);


                $_SESSION['teacher_id'] =
                    $teacher['teacher_id'];

                $_SESSION['teacher_db_id'] =
                    $teacher['id'];

                $_SESSION['teacher_name'] =
                    $teacher['teacher_name'];

                $_SESSION['teacher_email'] =
                    $teacher['email'];

                $_SESSION['teacher_subjects'] =
                    $teacher['subjects'];

                $_SESSION['teacher_curriculum'] =
                    $teacher['curriculum'];

                $_SESSION['teacher_photo'] =
                    $teacher['photo'];


                /* =====================================
                   LOGIN SUCCESS
                ===================================== */

                header(
                    "Location: dashboard.php"
                );

                exit;

            } else {

                $error =
                    "Incorrect Teacher ID/email or password.";

            }

        } else {

            $error =
                "Incorrect Teacher ID/email or password.";

        }


        $stmt->close();

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
Teacher Login | NISEL ONLINE EDUCATION
</title>


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Arial,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0055a5
        );

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        20px;

}


/* =========================================================
   LOGIN CONTAINER
========================================================= */

.login-container {

    width:
        100%;

    max-width:
        430px;

}


/* =========================================================
   LOGIN CARD
========================================================= */

.login-card {

    background:
        white;

    border-radius:
        15px;

    padding:
        40px;

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.25);

}


/* =========================================================
   LOGO
========================================================= */

.logo {

    text-align:
        center;

    margin-bottom:
        25px;

}


.logo-icon {

    width:
        70px;

    height:
        70px;

    margin:
        0 auto 15px;

    border-radius:
        50%;

    background:
        #003366;

    color:
        white;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        30px;

}


.logo h1 {

    margin:
        0;

    color:
        #003366;

    font-size:
        24px;

}


.logo p {

    margin:
        7px 0 0;

    color:
        #777;

    font-size:
        14px;

}


/* =========================================================
   TITLE
========================================================= */

.login-title {

    text-align:
        center;

    color:
        #333;

    margin-bottom:
        25px;

}


.login-title h2 {

    margin:
        0 0 7px;

    font-size:
        22px;

}


.login-title p {

    margin:
        0;

    color:
        #777;

    font-size:
        14px;

}


/* =========================================================
   ERROR
========================================================= */

.error {

    background:
        #f8d7da;

    color:
        #721c24;

    border:
        1px solid #f5c6cb;

    padding:
        12px 15px;

    border-radius:
        7px;

    margin-bottom:
        20px;

    font-size:
        14px;

}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-bottom:
        20px;

}


.form-group label {

    display:
        block;

    margin-bottom:
        8px;

    font-weight:
        bold;

    color:
        #333;

}


.form-group input {

    width:
        100%;

    padding:
        13px;

    border:
        1px solid #ccc;

    border-radius:
        7px;

    font-size:
        15px;

    outline:
        none;

}


.form-group input:focus {

    border-color:
        #003366;

    box-shadow:
        0 0 0 3px
        rgba(0,51,102,.1);

}


/* =========================================================
   PASSWORD
========================================================= */

.password-wrapper {

    position:
        relative;

}


.password-wrapper input {

    padding-right:
        75px;

}


.show-password {

    position:
        absolute;

    right:
        10px;

    top:
        50%;

    transform:
        translateY(-50%);

    border:
        none;

    background:
        none;

    color:
        #003366;

    cursor:
        pointer;

    font-weight:
        bold;

}


/* =========================================================
   LOGIN BUTTON
========================================================= */

.login-button {

    width:
        100%;

    padding:
        14px;

    background:
        #003366;

    color:
        white;

    border:
        none;

    border-radius:
        7px;

    font-size:
        16px;

    font-weight:
        bold;

    cursor:
        pointer;

}


.login-button:hover {

    background:
        #0055a5;

}


/* =========================================================
   HELP
========================================================= */

.help {

    text-align:
        center;

    margin-top:
        20px;

    font-size:
        13px;

    color:
        #777;

}


.help strong {

    color:
        #003366;

}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    text-align:
        center;

    color:
        rgba(255,255,255,.8);

    margin-top:
        20px;

    font-size:
        12px;

}


/* =========================================================
   MOBILE
========================================================= */

@media(max-width:480px) {

    .login-card {

        padding:
            30px 22px;

    }

}

</style>

</head>


<body>


<div class="login-container">


<div class="login-card">


<!-- =======================================================
     LOGO
======================================================= -->

<div class="logo">

<div class="logo-icon">
N
</div>

<h1>
NISEL ONLINE EDUCATION
</h1>

<p>
Quality Online Education
</p>

</div>



<!-- =======================================================
     TITLE
======================================================= -->

<div class="login-title">

<h2>
Teacher Login
</h2>

<p>
Sign in to access your Teacher Dashboard
</p>

</div>



<!-- =======================================================
     ERROR
======================================================= -->

<?php if ($error !== ""): ?>

<div class="error">

<?php

echo htmlspecialchars(
    $error
);

?>

</div>

<?php endif; ?>



<!-- =======================================================
     LOGIN FORM
======================================================= -->

<form
method="POST"
action=""
>


<div class="form-group">

<label for="login">

Teacher ID or Email

</label>

<input
type="text"
id="login"
name="login"
placeholder="Enter Teacher ID or email"
autocomplete="username"
required
>

</div>



<div class="form-group">

<label for="password">

Password

</label>


<div class="password-wrapper">

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
class="show-password"
onclick="togglePassword()"
>

Show

</button>

</div>

</div>



<button
type="submit"
class="login-button"
>

Login to Teacher Dashboard

</button>


</form>



<!-- =======================================================
     HELP
======================================================= -->

<div class="help">

<p>

Forgot your login details?

</p>

<p>

Please contact
<strong>
NISEL ONLINE EDUCATION
</strong>
administration.

</p>

</div>


</div>


<div class="footer">

© <?php echo date("Y"); ?>

NISEL ONLINE EDUCATION

</div>


</div>



<script>

function togglePassword() {

    const password =
        document.getElementById(
            "password"
        );

    const button =
        document.querySelector(
            ".show-password"
        );


    if (
        password.type === "password"
    ) {

        password.type =
            "text";

        button.textContent =
            "Hide";

    } else {

        password.type =
            "password";

        button.textContent =
            "Show";

    }

}

</script>


</body>

</html>


<?php

$conn->close();

?>
