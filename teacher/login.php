<?php
session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION - PDO
|--------------------------------------------------------------------------
*/

$host = "localhost";
$dbname = "nisel_online_education";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed.");
}


/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $loginPassword = $_POST["password"] ?? "";

    if (empty($login) || empty($loginPassword)) {

        $error = "Please enter your Teacher ID/email and password.";

    } else {

        $sql = "SELECT *
                FROM teachers
                WHERE teacher_id = :teacher_id
                OR email = :email
                LIMIT 1";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':teacher_id' => $login,
            ':email' => $login
        ]);

        $teacher = $stmt->fetch();

        if ($teacher && password_verify($loginPassword, $teacher["password"])) {

            $_SESSION["teacher_id"] = $teacher["teacher_id"];
            $_SESSION["teacher_name"] = $teacher["name"];
            $_SESSION["teacher_email"] = $teacher["email"];

            if (isset($teacher["status"]) && $teacher["status"] !== "approved") {

                session_unset();
                session_destroy();

                $error = "Your teacher account has not yet been approved.";

            } else {

                header("Location: teacher_dashboard.php");
                exit;
            }

        } else {

            $error = "Invalid Teacher ID/email or password.";
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

    <title>Teacher Login | NISEL ONLINE EDUCATION</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
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
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.10);
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
        }

        .forgot {
            text-align: center;
            margin-top: 20px;
            color: #555;
            font-size: 14px;
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

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-box">

        <div class="logo-title">

            <h1>NISEL ONLINE EDUCATION</h1>

            <p>Teacher Login</p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="error-message">
                <?= htmlspecialchars($error) ?>
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
                    value="<?= htmlspecialchars($_POST["login"] ?? "") ?>"
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

            © <?= date("Y") ?> NISEL ONLINE EDUCATION

        </div>

    </div>

</div>


<script>

function togglePassword() {

    const password = document.getElementById("password");
    const button = document.querySelector(".show-password");

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
