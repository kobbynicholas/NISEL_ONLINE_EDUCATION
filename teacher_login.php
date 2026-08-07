<?php

session_start();

require "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT *
        FROM teachers
        WHERE email = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $teacher = $result->fetch_assoc();

        if (
            password_verify(
                $password,
                $teacher['password']
            )
        ) {

            if (
                isset($teacher['status']) &&
                strtolower($teacher['status']) !== "active"
            ) {

                $error = "Your teacher account is currently inactive.";

            } else {

                $_SESSION['teacher_id'] =
                    $teacher['teacher_id'];

                $_SESSION['teacher_name'] =
                    $teacher['teacher_name'];

                $_SESSION['teacher_email'] =
                    $teacher['email'];

                header("Location: teacher/dashboard.php");
                exit();

            }

        } else {

            $error = "Incorrect email or password.";

        }

    } else {

        $error = "Teacher account not found.";

    }

    $stmt->close();

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Teacher Login | NISEL ONLINE EDUCATION</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:Arial,sans-serif;

    background:#eef3f8;

    min-height:100vh;

    display:flex;

    align-items:center;

    justify-content:center;

}

.login-box{

    width:90%;

    max-width:450px;

    background:white;

    padding:40px;

    border-radius:15px;

    box-shadow:0 10px 30px rgba(0,0,0,.12);

}

.logo{

    text-align:center;

    color:#003366;

    font-size:25px;

    font-weight:bold;

    margin-bottom:10px;

}

.subtitle{

    text-align:center;

    color:#777;

    margin-bottom:30px;

}

label{

    display:block;

    font-weight:bold;

    margin-top:15px;

    margin-bottom:7px;

}

input{

    width:100%;

    padding:13px;

    border:1px solid #ccc;

    border-radius:7px;

    font-size:15px;

}

button{

    width:100%;

    margin-top:25px;

    padding:14px;

    border:0;

    border-radius:7px;

    background:#003366;

    color:white;

    font-size:16px;

    cursor:pointer;

}

button:hover{

    background:#0055a5;

}

.error{

    background:#ffe5e5;

    color:#b00000;

    padding:12px;

    border-radius:7px;

    margin-bottom:15px;

    text-align:center;

}

</style>

</head>

<body>

<div class="login-box">

<div class="logo">

NISEL ONLINE EDUCATION

</div>

<div class="subtitle">

Teacher Portal

</div>

<?php if($error != ""): ?>

<div class="error">

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>

<form method="POST">

<label>Email Address</label>

<input
type="email"
name="email"
required
autocomplete="email">

<label>Password</label>

<input
type="password"
name="password"
required
autocomplete="current-password">

<button type="submit">

Teacher Login

</button>

</form>

</div>

</body>

</html>
