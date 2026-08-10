<?php

session_start();

require "../config/db.php";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {

    header("Location: login.php");
    exit;

}


$student_id = $_SESSION['student_id'];

$message = "";
$message_type = "";


/* =========================================================
   UPDATE PROFILE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $student_name = trim(
        $_POST['student_name'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );


    if (
        $student_name === "" ||
        $email === "" ||
        $phone === ""
    ) {

        $message =
            "Please complete all the fields.";

        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message =
            "Please enter a valid email address.";

        $message_type = "error";

    } else {

        try {

            /* =============================================
               CHECK WHETHER EMAIL BELONGS TO ANOTHER
               STUDENT
            ============================================= */

            $stmt = $pdo->prepare("
                SELECT id
                FROM students
                WHERE email = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $email,
                $student_id
            ]);


            if ($stmt->fetch()) {

                $message =
                    "This email address is already being used.";

                $message_type = "error";

            } else {

                /* =========================================
                   UPDATE STUDENT
                ========================================= */

                $stmt = $pdo->prepare("
                    UPDATE students
                    SET
                        student_name = ?,
                        email = ?,
                        phone = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $student_name,
                    $email,
                    $phone,
                    $student_id
                ]);


                /* =========================================
                   UPDATE SESSION
                ========================================= */

                $_SESSION['student_name'] =
                    $student_name;

                $_SESSION['student_email'] =
                    $email;


                $message =
                    "Your profile has been updated successfully.";

                $message_type = "success";

            }

        } catch (PDOException $e) {

            $message =
                "Unable to update profile: "
                . $e->getMessage();

            $message_type = "error";
        }
    }
}


/* =========================================================
   GET CURRENT STUDENT
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            student_name,
            email,
            phone
        FROM students
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $student_id
    ]);

    $student = $stmt->fetch();


    if (!$student) {

        session_destroy();

        header("Location: login.php");
        exit;

    }

} catch (PDOException $e) {

    die(
        "Unable to load student profile: "
        . $e->getMessage()
    );

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
My Profile | NISEL ONLINE EDUCATION
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


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

}


.logo {

    text-align: center;

    font-size: 19px;

    font-weight: bold;

    margin-bottom: 30px;

}


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 6px;

}


.sidebar a:hover {

    background: #0055aa;

}


.sidebar a.active {

    background: #0055aa;

}


.logout {

    background: #dc3545;

    margin-top: 25px;

}


.logout:hover {

    background: #bb2d3b !important;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


.container {

    max-width: 800px;

    margin: auto;

}


.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.header p {

    margin: 0;

    color: #666;

}


/* =====================================================
   FORM
===================================================== */

.form-card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.form-card h2 {

    margin-top: 0;

    color: #003366;

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


.readonly {

    background: #f2f2f2;

    color: #666;

}


button {

    width: 100%;

    padding: 14px;

    border: none;

    background: #003366;

    color: white;

    border-radius: 7px;

    font-size: 16px;

    cursor: pointer;

}


button:hover {

    background: #0055aa;

}


/* =====================================================
   MESSAGES
===================================================== */

.message {

    padding: 13px;

    border-radius: 7px;

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


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 20px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<div class="logo">

NISEL ONLINE EDUCATION

</div>


<a href="dashboard.php">
🏠 Dashboard
</a>


<a
    href="profile.php"
    class="active"
>
👤 My Profile
</a>


<a href="bookings.php">
📚 My Bookings
</a>


<a href="schedule.php">
📅 My Schedule
</a>


<a href="payments.php">
💳 Payments
</a>


<a
    href="logout.php"
    class="logout"
>
🚪 Logout
</a>


</div>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


<div class="container">


<div class="header">

<h1>
My Profile
</h1>

<p>
View and update your NISEL ONLINE EDUCATION
student account information.
</p>

</div>


<div class="form-card">


<h2>
Student Information
</h2>


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


<!-- STUDENT ID -->

<div class="form-group">

<label>
Student ID
</label>

<input
    type="text"
    value="<?php
        echo htmlspecialchars(
            $student['id']
        );
    ?>"
    class="readonly"
    readonly
>

</div>


<!-- NAME -->

<div class="form-group">

<label>
Student Name
</label>

<input
    type="text"
    name="student_name"
    value="<?php
        echo htmlspecialchars(
            $student['student_name']
        );
    ?>"
    required
>

</div>


<!-- EMAIL -->

<div class="form-group">

<label>
Email Address
</label>

<input
    type="email"
    name="email"
    value="<?php
        echo htmlspecialchars(
            $student['email']
        );
    ?>"
    required
>

</div>


<!-- PHONE -->

<div class="form-group">

<label>
Phone Number
</label>

<input
    type="tel"
    name="phone"
    value="<?php
        echo htmlspecialchars(
            $student['phone'] ?? ''
        );
    ?>"
    required
>

</div>


<button type="submit">

Save Changes

</button>


</form>


</div>


</div>


</div>


</body>

</html>
