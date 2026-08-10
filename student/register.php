<?php

require "../config/db.php";

$message = "";
$message_type = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_name = trim($_POST['student_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';


    if (
        $student_name === "" ||
        $email === "" ||
        $phone === "" ||
        $password === ""
    ) {

        $message = "Please complete all required fields.";
        $message_type = "error";

    } elseif ($password !== $confirm) {

        $message = "Passwords do not match.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must contain at least 6 characters.";
        $message_type = "error";

    } else {

        try {

            /* Check existing student */

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

                /* Hash password */

                $hashed_password =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /* Create student */

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
                    "Registration successful. You can now log in.";

                $message_type = "success";

            }

        } catch (PDOException $e) {

            $message =
                "Registration failed: "
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
Student Registration | NISEL ONLINE EDUCATION
</title>

<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #eef3f8;
}

.container {
    width: 90%;
    max-width: 500px;
    margin: 50px auto;
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,.12);
}

.logo {
    text-align: center;
    color: #003366;
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 10px;
}

.subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 7px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 13px;
    background: #003366;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 16px;
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

.login {
    text-align: center;
    margin-top: 20px;
}

.login a {
    color: #003366;
    font-weight: bold;
    text-decoration: none;
}

</style>

</head>

<body>


<div class="container">


<div class="logo">
NISEL ONLINE EDUCATION
</div>

<div class="subtitle">
Student Registration
</div>


<?php if ($message !== ""): ?>

<div class="message <?php echo $message_type; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<form method="POST">


<div class="form-group">

<label>
Student Name
</label>

<input
    type="text"
    name="student_name"
    required
>

</div>


<div class="form-group">

<label>
Email Address
</label>

<input
    type="email"
    name="email"
    required
>

</div>


<div class="form-group">

<label>
Phone Number
</label>

<input
    type="tel"
    name="phone"
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
    required
>

</div>


<div class="form-group">

<label>
Confirm Password
</label>

<input
    type="password"
    name="confirm_password"
    required
>

</div>


<button type="submit">
Create Student Account
</button>


</form>


<div class="login">

Already have an account?

<a href="login.php">
Login
</a>

</div>


</div>

</body>

</html>
