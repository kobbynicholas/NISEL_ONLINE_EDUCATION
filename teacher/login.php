<?php

session_start();

require_once "../config/db.php";


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

$error = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $loginPassword = $_POST["password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | CHECK EMPTY FIELDS
    |--------------------------------------------------------------------------
    */

    if ($login === "" || $loginPassword === "") {

        $error = "Please enter your Teacher ID/email and password.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | FIND TEACHER
        |--------------------------------------------------------------------------
        */

        $sql = "
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
                status,
                created_at
            FROM teachers
            WHERE teacher_id = :teacher_id
               OR email = :email
            LIMIT 1
        ";


        try {

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":teacher_id" => $login,
                ":email" => $login
            ]);

            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);


        } catch (PDOException $e) {

            $error = "A database error occurred. Please contact NISEL ONLINE EDUCATION administration.";

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK TEACHER
        |--------------------------------------------------------------------------
        */

        if (empty($error)) {

            if (!$teacher) {

                $error = "Invalid Teacher ID/email or password.";

            } elseif (empty($teacher["password"])) {

                $error = "This teacher account does not have a valid password.";

            } elseif (!password_verify($loginPassword, $teacher["password"])) {

                $error = "Invalid Teacher ID/email or password.";

            } elseif (strtolower(trim($teacher["status"])) !== "active") {

                $error = "Your teacher account is not active. Please contact NISEL ONLINE EDUCATION administration.";

            } else {


                /*
                |--------------------------------------------------------------------------
                | LOGIN SUCCESSFUL
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                $_SESSION["teacher_logged_in"] = true;

                $_SESSION["teacher_db_id"] = $teacher["id"];

                $_SESSION["teacher_id"] = $teacher["teacher_id"];

                $_SESSION["teacher_name"] = $teacher["teacher_name"];

                $_SESSION["teacher_email"] = $teacher["email"];

                $_SESSION["teacher_phone"] = $teacher["phone"];

                $_SESSION["teacher_subjects"] = $teacher["subjects"];

                $_SESSION["teacher_curriculum"] = $teacher["curriculum"];

                $_SESSION["teacher_photo"] = $teacher["photo"];

                $_SESSION["teacher_status"] = $teacher["status"];


                /*
                |--------------------------------------------------------------------------
                | OPEN TEACHER DASHBOARD
                |--------------------------------------------------------------------------
                */

                header("Location: dashboard.php");

                exit();
            }
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

            padding: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f7fb;

            display: flex;

            justify-content: center;

            align-items: center;

            min-height: 100vh;
        }


        .login-container {

            width: 100%;

            max-width: 430px;

            padding: 20px;
        }


        .login-box {

            background: #ffffff;

            padding: 35px;

            border-radius: 12px;

            box-shadow:
                0 5px 25px
                rgba(0, 0, 0, 0.10);
        }


        .logo-title {

            text-align: center;

            margin-bottom: 30px;
        }


        .logo-title h1 {

            margin: 0;

            color: #123c69;

            font-size: 26px;
        }


        .logo-title p {

            margin-top: 8px;

            color: #777;

            font-size: 14px;
        }


        .form-group {

            margin-bottom: 20px;
        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

            color: #333;
        }


        input {

            width: 100%;

            padding: 13px;

            border: 1px solid #ccc;

            border-radius: 6px;

            font-size: 15px;

            outline: none;
        }


        input:focus {

            border-color: #123c69;
        }


        .password-wrapper {

            position: relative;
        }


        .password-wrapper input {

            padding-right: 80px;
        }


        .show-password {

            position: absolute;

            right: 8px;

            top: 7px;

            border: none;

            background: transparent;

            color: #123c69;

            cursor: pointer;

            padding: 7px;

            font-weight: bold;
        }


        .login-button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 6px;

            background: #123c69;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;
        }


        .login-button:hover {

            background: #0d2d50;
        }


        .error-message {

            background: #ffe5e5;

            color: #b30000;

            padding: 12px;

            border-radius: 6px;

            margin-bottom: 20px;

            text-align: center;

            font-size: 14px;

            line-height: 1.5;
        }


        .forgot {

            text-align: center;

            margin-top: 20px;

            color: #555;

            font-size: 14px;

            line-height: 1.6;
        }


        .admin-name {

            font-weight: bold;

            color: #123c69;
        }


        .copyright {

            text-align: center;

            margin-top: 25px;

            color: #888;

            font-size: 13px;
        }


        @media (max-width: 480px) {

            .login-box {

                padding: 25px;
            }


            .logo-title h1 {

                font-size: 22px;
            }

        }

    </style>

</head>


<body>


<div class="login-container">


    <div class="login-box">


        <div class="logo-title">

            <h1>
                NISEL ONLINE EDUCATION
            </h1>

            <p>
                Teacher Login
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars($error);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST" action="">


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
                    value="<?php
                        echo htmlspecialchars(
                            $_POST["login"] ?? ""
                        );
                    ?>"
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


        <div class="forgot">

            Forgot your login details?

            <br>

            Please contact

            <span class="admin-name">
                NISEL ONLINE EDUCATION
            </span>

            administration.

        </div>


        <div class="copyright">

            ©
            <?php echo date("Y"); ?>

            NISEL ONLINE EDUCATION

        </div>


    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const button =
        document.querySelector(".show-password");


    if (password.type === "password") {

        password.type = "text";

        button.textContent = "Hide";

    } else {

        password.type = "password";

        button.textContent = "Show";

    }

}

</script>


</body>

</html>
