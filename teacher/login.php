<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER LOGIN
|--------------------------------------------------------------------------
*/

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| VARIABLES
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
    isset($_SESSION['teacher_id']) &&
    !empty($_SESSION['teacher_id'])
) {

    header(
        "Location: /online/teacher/dashboard.php"
    );

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

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | FIND TEACHER
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    teacher_id,
                    teacher_name,
                    phone,
                    email,
                    password,
                    status
                FROM teachers
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);


            $teacher =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            /*
            |--------------------------------------------------------------------------
            | TEACHER NOT FOUND
            |--------------------------------------------------------------------------
            */

            if (!$teacher) {

                $error =
                    "Teacher account not found.";

            }

            /*
            |--------------------------------------------------------------------------
            | CHECK ACCOUNT STATUS
            |--------------------------------------------------------------------------
            */

            elseif (
                isset($teacher['status']) &&
                strtolower(
                    trim($teacher['status'])
                ) !== 'active'
            ) {

                $error =
                    "Your teacher account is not active. Please contact the administrator.";

            }

            /*
            |--------------------------------------------------------------------------
            | CHECK PASSWORD
            |--------------------------------------------------------------------------
            */

            elseif (
                !password_verify(
                    $password,
                    $teacher['password']
                )
            ) {

                $error =
                    "Incorrect password.";

            }

            /*
            |--------------------------------------------------------------------------
            | LOGIN SUCCESS
            |--------------------------------------------------------------------------
            */

            else {

                /*
                --------------------------------------------------------------
                | REGENERATE SESSION ID
                --------------------------------------------------------------
                */

                session_regenerate_id(true);


                /*
                --------------------------------------------------------------
                | SAVE TEACHER SESSION
                --------------------------------------------------------------
                */

                $_SESSION['teacher_id'] =
                    $teacher['teacher_id'];


                $_SESSION['teacher_name'] =
                    $teacher['teacher_name'];


                $_SESSION['teacher_email'] =
                    $teacher['email'];


                $_SESSION['teacher_phone'] =
                    $teacher['phone'] ?? '';


                $_SESSION['teacher_logged_in'] =
                    true;


                /*
                --------------------------------------------------------------
                | REDIRECT TO DASHBOARD
                --------------------------------------------------------------
                */

                header(
                    "Location: /online/teacher/dashboard.php"
                );

                exit;
            }

        } catch (PDOException $e) {

            /*
            --------------------------------------------------------------
            | DATABASE ERROR
            --------------------------------------------------------------
            */

            $error =
                "Unable to process your login. "
                . "Please try again.";
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


.login-container {

    width: 100%;

    max-width: 430px;
}


.login-card {

    background: white;

    padding: 40px;

    border-radius: 15px;

    box-shadow:
        0 15px 40px
        rgba(0,0,0,.20);
}


.logo {

    text-align: center;

    margin-bottom: 25px;
}


.logo-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 15px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #003366,
            #0074b7
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 32px;

}


.logo h1 {

    margin: 0;

    color: #003366;

    font-size: 24px;

}


.logo p {

    margin: 6px 0 0;

    color: #777;

    font-size: 14px;

}


.form-group {

    margin-bottom: 18px;
}


.form-group label {

    display: block;

    margin-bottom: 7px;

    color: #333;

    font-weight: bold;

}


.form-group input {

    width: 100%;

    padding: 13px;

    border:
        1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

    outline: none;

}


.form-group input:focus {

    border-color: #0074b7;

    box-shadow:
        0 0 0 3px
        rgba(0,116,183,.10);
}


.error {

    background: #f8d7da;

    color: #721c24;

    padding: 13px;

    border-radius: 7px;

    margin-bottom: 20px;

    font-size: 14px;

}


button {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 7px;

    background: #003366;

    color: white;

    font-size: 16px;

    font-weight: bold;

    cursor: pointer;
}


button:hover {

    background: #0055a5;
}


.back {

    text-align: center;

    margin-top: 20px;
}


.back a {

    color: #003366;

    text-decoration: none;

    font-size: 14px;
}


.back a:hover {

    text-decoration: underline;
}


.footer {

    text-align: center;

    color: rgba(255,255,255,.8);

    margin-top: 20px;

    font-size: 13px;
}


@media(max-width:500px) {

    .login-card {

        padding: 25px;

    }

}

</style>

</head>


<body>


<div class="login-container">


    <div class="login-card">


        <div class="logo">

            <div class="logo-icon">
                👨‍🏫
            </div>


            <h1>
                NISEL ONLINE EDUCATION
            </h1>


            <p>
                Teacher Portal
            </p>

        </div>


        <?php if ($error !== ""): ?>

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
                    placeholder="Enter your email"
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
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
            >

                🔐 Sign In

            </button>


        </form>


        <div class="back">

            <a href="/online/index.php">

                ← Back to NISEL Website

            </a>

        </div>


    </div>


    <div class="footer">

        © <?= date('Y') ?>

        NISEL ONLINE EDUCATION

    </div>


</div>


</body>

</html>
