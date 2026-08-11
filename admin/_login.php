<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| ADMIN LOGIN - PDO
|--------------------------------------------------------------------------
*/


$error = "";

$email = "";


/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
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
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $email =
        trim(
            $_POST['email'] ?? ''
        );

    $password =
        $_POST['password'] ?? '';


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
            "Please enter your email and password.";

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
            | PDO QUERY
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
            | GET ADMIN
            |--------------------------------------------------------------------------
            */

            $admin =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | VERIFY ADMIN
            |--------------------------------------------------------------------------
            */

            if (!$admin) {

                $error =
                    "Admin account not found.";

            }

            elseif (
                !password_verify(
                    $password,
                    $admin['password']
                )
            ) {

                $error =
                    "Incorrect password.";

            }

            else {

                /*
                |--------------------------------------------------------------------------
                | SECURE SESSION
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                $_SESSION['admin_id'] =
                    $admin['id'];


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

            }


        } catch (
            PDOException $e
        ) {

            $error =
                "Database error: "
                .
                $e->getMessage();

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

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    min-height: 100vh;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0074b7
        );

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

}


.login-wrapper {

    width: 100%;

    max-width: 430px;

}


.login-card {

    background: white;

    padding: 40px;

    border-radius: 20px;

    box-shadow:
        0 20px 50px
        rgba(0,0,0,.25);

}


.logo {

    text-align: center;

    margin-bottom: 25px;

}


.logo-icon {

    width: 70px;

    height: 70px;

    margin: auto;

    border-radius: 18px;

    background:
        #003366;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

}


.logo h2 {

    margin:
        15px 0 3px;

    color:
        #003366;

}


.logo p {

    margin: 0;

    color: #888;

    font-size: 12px;

    letter-spacing: 2px;

}


.heading {

    text-align: center;

    margin-bottom: 25px;

}


.heading h1 {

    margin: 0 0 8px;

    color: #172b4d;

    font-size: 25px;

}


.heading p {

    margin: 0;

    color: #777;

    font-size: 14px;

}


.error {

    background:
        #fff0f0;

    border:
        1px solid #f3b5b5;

    color:
        #b42318;

    padding:
        13px;

    border-radius:
        8px;

    margin-bottom:
        20px;

    font-size:
        14px;

}


.form-group {

    margin-bottom:
        20px;

}


label {

    display:
        block;

    margin-bottom:
        8px;

    font-weight:
        bold;

    color:
        #344054;

    font-size:
        14px;

}


input {

    width:
        100%;

    height:
        50px;

    padding:
        0 15px;

    border:
        1px solid #d0d5dd;

    border-radius:
        9px;

    font-size:
        15px;

    outline:
        none;

}


input:focus {

    border-color:
        #0074b7;

    box-shadow:
        0 0 0 3px
        rgba(
            0,
            116,
            183,
            .10
        );

}


button {

    width:
        100%;

    height:
        52px;

    border:
        none;

    border-radius:
        9px;

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
        bold;

    cursor:
        pointer;

}


button:hover {

    opacity:
        .92;

}


.footer {

    text-align:
        center;

    color:
        rgba(
            255,
            255,
            255,
            .8
        );

    font-size:
        12px;

    margin-top:
        20px;

}

</style>

</head>


<body>


<div class="login-wrapper">


    <div class="login-card">


        <div class="logo">

            <div class="logo-icon">

                🎓

            </div>


            <h2>

                NISEL

            </h2>


            <p>

                ONLINE EDUCATION

            </p>

        </div>


        <div class="heading">

            <h1>

                Administrator Login

            </h1>


            <p>

                Sign in to access the
                administration portal.

            </p>

        </div>


        <?php if (
            $error !== ''
        ): ?>

            <div class="error">

                ⚠️

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
        >


            <div class="form-group">

                <label for="email">

                    Email Address

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Admin email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="password">

                    Password

                </label>


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Admin password"
                    required
                >

            </div>


            <button
                type="submit"
                name="login"
            >

                🔐 Sign In

            </button>


        </form>


    </div>


    <div class="footer">

        © <?= date('Y') ?>

        NISEL ONLINE EDUCATION

        <br>

        Administrator Portal

    </div>


</div>


</body>

</html>
