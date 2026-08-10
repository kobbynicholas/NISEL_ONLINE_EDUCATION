<?php

require "../teacher_auth.php";
require "../config/db.php";


/* =========================================================
   TEACHER SESSION
========================================================= */

$teacher_id =
    $_SESSION['teacher_id'] ?? '';

$teacher_name =
    $_SESSION['teacher_name'] ?? 'Teacher';


if (empty($teacher_id)) {

    header("Location: login.php");
    exit;

}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   LOAD TEACHER
========================================================= */

try {

    $stmt = $pdo->prepare("

        SELECT *

        FROM teachers

        WHERE teacher_id = ?

        LIMIT 1

    ");

    $stmt->execute([
        $teacher_id
    ]);

    $teacher =
        $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die(
        "Unable to load profile: "
        .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}


if (!$teacher) {

    die(
        "Teacher profile could not be found."
    );

}


/* =========================================================
   UPDATE PROFILE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['update_profile'])
) {


    $name =
        trim(
            $_POST['teacher_name'] ?? ''
        );


    $email =
        trim(
            $_POST['email'] ?? ''
        );


    $phone =
        trim(
            $_POST['phone'] ?? ''
        );


    $qualification =
        trim(
            $_POST['qualification'] ?? ''
        );


    $specialization =
        trim(
            $_POST['specialization'] ?? ''
        );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $name === ''
        ||
        $email === ''
    ) {

        $message =
            "Teacher name and email are required.";

        $message_type =
            "error";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Please enter a valid email address.";

        $message_type =
            "error";

    } else {


        try {

            /*
             * Update the teacher record.
             *
             * We only update columns that are normally
             * available in the teacher table.
             */

            $update = $pdo->prepare("

                UPDATE teachers

                SET
                    teacher_name = ?,
                    email = ?,
                    phone = ?,
                    qualification = ?,
                    specialization = ?

                WHERE teacher_id = ?

            ");


            $update->execute([

                $name,

                $email,

                $phone,

                $qualification,

                $specialization,

                $teacher_id

            ]);


            /*
             * Update the session so the new name
             * appears immediately throughout the
             * teacher dashboard.
             */

            $_SESSION[
                'teacher_name'
            ] = $name;


            $teacher_name =
                $name;


            $message =
                "Your profile has been updated successfully.";

            $message_type =
                "success";


            /*
             * Reload teacher information.
             */

            $stmt = $pdo->prepare("

                SELECT *

                FROM teachers

                WHERE teacher_id = ?

                LIMIT 1

            ");


            $stmt->execute([
                $teacher_id
            ]);


            $teacher =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


        } catch (PDOException $e) {

            $message =
                "Unable to update profile: "
                .
                $e->getMessage();

            $message_type =
                "error";

        }

    }

}


/* =========================================================
   SAFE VALUES
========================================================= */

$display_name =
    $teacher['teacher_name']
    ?? '';

$display_email =
    $teacher['email']
    ?? '';

$display_phone =
    $teacher['phone']
    ?? '';

$display_qualification =
    $teacher['qualification']
    ?? '';

$display_specialization =
    $teacher['specialization']
    ?? '';

$teacher_status =
    $teacher['status']
    ?? 'Active';


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

My Profile |
NISEL ONLINE EDUCATION

</title>


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

    color: #333;

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

    line-height: 1.5;

    margin-bottom: 35px;

}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.menu a:hover {

    background: #0055a5;

}


.menu a.active {

    background: #0055a5;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* =====================================================
   HEADER
===================================================== */

.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

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
   MESSAGE
===================================================== */

.message {

    padding: 14px;

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
   PROFILE LAYOUT
===================================================== */

.profile-layout {

    display: grid;

    grid-template-columns:
        280px 1fr;

    gap: 25px;

}


/* =====================================================
   PROFILE CARD
===================================================== */

.profile-card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    text-align: center;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.avatar {

    width: 100px;

    height: 100px;

    margin: 0 auto 20px;

    border-radius: 50%;

    background: #003366;

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 38px;

    font-weight: bold;

}


.profile-card h2 {

    margin: 10px 0;

    color: #003366;

}


.profile-card p {

    color: #777;

}


.status {

    display: inline-block;

    padding: 7px 15px;

    border-radius: 20px;

    background: #d4edda;

    color: #155724;

    font-size: 13px;

    font-weight: bold;

}


/* =====================================================
   FORM
===================================================== */

.form-card {

    background: white;

    padding: 30px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.form-card h2 {

    margin-top: 0;

    color: #003366;

}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;

}


.form-group label {

    display: block;

    margin-bottom: 7px;

    font-weight: bold;

}


.form-group input {

    width: 100%;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 14px;

}


.form-group input:focus {

    outline: none;

    border-color: #003366;

}


.full {

    grid-column: 1 / -1;

}


.update-button {

    margin-top: 20px;

    padding: 12px 25px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;

}


.update-button:hover {

    background: #0055a5;

}


/* =====================================================
   READ ONLY
===================================================== */

.readonly {

    background: #f1f3f5;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:850px) {

    .profile-layout {

        grid-template-columns: 1fr;

    }

}


@media(max-width:800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .form-grid {

        grid-template-columns: 1fr;

    }


    .full {

        grid-column: auto;

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

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="students.php">

            👨‍🎓 My Students

        </a>


        <a href="schedule.php">

            📅 My Schedule

        </a>


        <a
            href="profile.php"
            class="active"
        >

            👤 My Profile

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>


    </div>


</div>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <div class="header">


        <h1>

            👤 My Profile

        </h1>


        <p>

            Manage your NISEL ONLINE EDUCATION
            teacher information.

        </p>


    </div>



    <?php if (
        $message !== ''
    ): ?>


        <div class="message
            <?php

            echo htmlspecialchars(
                $message_type
            );

            ?>
        ">

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>


    <?php endif; ?>



    <div class="profile-layout">


        <!-- =============================================
             PROFILE SUMMARY
        ============================================== -->

        <div class="profile-card">


            <div class="avatar">


                <?php

                echo strtoupper(
                    substr(
                        $display_name,
                        0,
                        1
                    )
                );

                ?>


            </div>


            <h2>

                <?php

                echo htmlspecialchars(
                    $display_name
                );

                ?>

            </h2>


            <p>

                Teacher

            </p>


            <p>

                <?php

                echo htmlspecialchars(
                    $display_email
                );

                ?>

            </p>


            <span class="status">

                <?php

                echo htmlspecialchars(
                    $teacher_status
                );

                ?>

            </span>


        </div>



        <!-- =============================================
             EDIT PROFILE
        ============================================== -->

        <div class="form-card">


            <h2>

                Personal Information

            </h2>


            <form method="POST">


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="form-group">


                        <label>

                            Teacher Name

                        </label>


                        <input
                            type="text"
                            name="teacher_name"
                            value="<?php

                                echo htmlspecialchars(
                                    $display_name
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
                                    $display_email
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
                            type="text"
                            name="phone"
                            value="<?php

                                echo htmlspecialchars(
                                    $display_phone
                                );

                            ?>"
                        >


                    </div>



                    <!-- QUALIFICATION -->

                    <div class="form-group">


                        <label>

                            Qualification

                        </label>


                        <input
                            type="text"
                            name="qualification"
                            value="<?php

                                echo htmlspecialchars(
                                    $display_qualification
                                );

                            ?>"
                            placeholder="e.g. BSc Education"
                        >


                    </div>



                    <!-- SPECIALIZATION -->

                    <div class="form-group full">


                        <label>

                            Subject Specialization

                        </label>


                        <input
                            type="text"
                            name="specialization"
                            value="<?php

                                echo htmlspecialchars(
                                    $display_specialization
                                );

                            ?>"
                            placeholder="e.g. Mathematics, Physics"
                        >


                    </div>


                </div>


                <button
                    type="submit"
                    name="update_profile"
                    class="update-button"
                >

                    💾 Save Changes

                </button>


            </form>


        </div>


    </div>


</div>


</body>

</html>
