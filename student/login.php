<?php

session_start();

require "../config/db.php";

$message = "";
$message_type = "";


/* =========================================================
   STUDENT LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";
        $message_type = "error";

    } else {

        try {

            /* Find student */

            $stmt = $pdo->prepare("
                SELECT *
                FROM students
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([
                $email
            ]);

            $student = $stmt->fetch();


            /* Check account */

            if (!$student) {

                $message = "Invalid email or password.";
                $message_type = "error";

            } elseif (
                !password_verify(
                    $password,
                    $student['password']
                )
            ) {

                $message = "Invalid email or password.";
                $message_type = "error";

            } else {

                /* =================================================
                   LOGIN SUCCESS
                ================================================= */

                session_regenerate_id(true);


                $_SESSION['student_logged_in'] = true;

                $_SESSION['student_id'] =
                    $student['id'];

                $_SESSION['student_name'] =
                    $student['student_name'];

                $_SESSION['student_email'] =
                    $student['email'];


                header(
                    "Location: dashboard.php"
                );

                exit;

            }

        } catch (PDOException $e) {

            $message =
                "Login error: "
                . $e->getMessage();

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

<title>
Student Login | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

}


.container {

    width: 90%;

    max-width: 450px;

    margin: 70px auto;

    background: white;

    padding: 35px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.12);

}


.logo {

    text-align: center;

    color: #003366;

    font-size: 25px;

    font-weight: bold;

    margin-bottom: 8px;

}


.subtitle {

    text-align: center;

    color: #666;

    margin-bottom: 30px;

}


.form-group {

    margin-bottom: 20px;

}


label {

    display: block;

    font-weight: bold;

    margin-bottom: 8px;

}


input {

    width: 100%;

    padding: 13px;

    border: 1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

}


input:focus {

    outline: none;

    border-color: #003366;

}


button {

    width: 100%;

    padding: 14px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 7px;

    font-size: 16px;

    cursor: pointer;

}


button:hover {

    background: #0055aa;

}


.message {

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

}


.success {

    background: #d4edda;

    color: #155724;

}


.error {

    background: #f8d7da;

    color: #721c24;

}


.register {

    text-align: center;

    margin-top: 22px;

    color: #666;

}


.register a {

    color: #003366;

    font-weight: bold;

    text-decoration: none;

}


.home {

    text-align: center;

    margin-top: 15px;

}


.home a {

    color: #555;

    text-decoration: none;

    font-size: 14px;

}

</style>

</head>


<body>


<div class="container">


<div class="logo">

NISEL ONLINE EDUCATION

</div>


<div class="subtitle">

Student Login

</div>


<?php if ($message !== ""): ?>

<div class="message <?php echo $message_type; ?>">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>
Email Address
</label>

<input
    type="email"
    name="email"
    autocomplete="email"
    required
>

</div>


<div class="form-group">

<label>
Password
</label>

<input
    type="password"
    name="password"
    autocomplete="current-password"
    required
>

</div>


<button type="submit">

Login to Student Portal

</button>


</form>


<div class="register">

Don't have a student account?

<a href="register.php">
Register
</a>

</div>


<div class="home">

<a href="../index.html">
← Return to NISEL ONLINE EDUCATION
</a>

</div>


</div>


</body>

</html>
