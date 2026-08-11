<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";
$message_type = "";

/*
=========================================================
ASSIGN TEACHER
=========================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["assign_teacher"])) {

    $booking_id = (int)($_POST["booking_id"] ?? 0);
    $teacher_id = trim($_POST["teacher_id"] ?? "");

    if ($booking_id <= 0 || $teacher_id === "") {

        $message = "Please select a valid teacher.";
        $message_type = "error";

    } else {

        try {

            /*
            -------------------------------------------------
            GET TEACHER
            -------------------------------------------------
            */

            $teacherStmt = $pdo->prepare("
                SELECT
                    teacher_id,
                    teacher_name,
                    email,
                    phone
                FROM teachers
                WHERE teacher_id = ?
                AND status = 'Active'
                LIMIT 1
            ");

            $teacherStmt->execute([
                $teacher_id
            ]);

            $teacher = $teacherStmt->fetch(PDO::FETCH_ASSOC);


            if (!$teacher) {

                throw new Exception(
                    "Selected teacher was not found or is not active."
                );

            }


            /*
            -------------------------------------------------
            CHECK BOOKING
            -------------------------------------------------
            */

            $bookingStmt = $pdo->prepare("
                SELECT
                    id,
                    booking_reference,
                    student_name,
                    email,
                    subjects,
                    curriculum,
                    class_year,
                    payment_status
                FROM bookings
                WHERE id = ?
                LIMIT 1
            ");

            $bookingStmt->execute([
                $booking_id
            ]);

            $booking = $bookingStmt->fetch(PDO::FETCH_ASSOC);


            if (!$booking) {

                throw new Exception(
                    "Booking was not found."
                );

            }


            /*
            -------------------------------------------------
            ASSIGN TEACHER
            -------------------------------------------------
            */

            $updateStmt = $pdo->prepare("
                UPDATE bookings
                SET
                    teacher_id = ?,
                    teacher_name = ?,
                    assignment_status = 'Assigned'
                WHERE id = ?
            ");

            $updateStmt->execute([
                $teacher["teacher_id"],
                $teacher["teacher_name"],
                $booking_id
            ]);


            $message =
                "Teacher "
                . htmlspecialchars($teacher["teacher_name"])
                . " has been assigned successfully to "
                . htmlspecialchars($booking["student_name"])
                . ".";

            $message_type = "success";


        } catch (Exception $e) {

            $message =
                "Unable to assign teacher: "
                . $e->getMessage();

            $message_type = "error";

        }
    }
}


/*
=========================================================
GET TEACHERS
=========================================================
*/

$teachersStmt = $pdo->prepare("
    SELECT
        teacher_id,
        teacher_name,
        email,
        phone,
        subjects,
        curriculum,
        status
    FROM teachers
    WHERE status = 'Active'
    ORDER BY teacher_name ASC
");

$teachersStmt->execute();

$teachers = $teachersStmt->fetchAll(PDO::FETCH_ASSOC);


/*
=========================================================
GET BOOKINGS
=========================================================
*/

$bookingsStmt = $pdo->prepare("
    SELECT
        b.id,
        b.booking_reference,
        b.student_name,
        b.email,
        b.phone,
        b.curriculum,
        b.class_year,
        b.subjects,
        b.amount,
        b.payment_status,
        b.teacher_id,
        b.teacher_name,
        b.assignment_status,
        b.lesson_date,
        b.lesson_time

    FROM bookings b

    ORDER BY
        CASE
            WHEN b.teacher_id IS NULL
                 OR b.teacher_id = ''
            THEN 0
            ELSE 1
        END,

        b.id DESC
");

$bookingsStmt->execute();

$bookings =
    $bookingsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Assign Teachers |
NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #eef3f8;

    color: #333;
}


/* =========================================
   SIDEBAR
========================================= */

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

    font-size: 20px;

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


/* =========================================
   MAIN
========================================= */

.main {

    margin-left: 240px;

    padding: 30px;
}


.header {

    background: white;

    padding: 25px;

    border-radius: 10px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.08);
}


.header h1 {

    margin: 0;

    color: #003366;
}


.header p {

    margin-bottom: 0;

    color: #666;
}


/* =========================================
   MESSAGES
========================================= */

.message {

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 20px;

    font-weight: bold;
}


.message.success {

    background: #d4edda;

    color: #155724;

    border: 1px solid #c3e6cb;
}


.message.error {

    background: #f8d7da;

    color: #721c24;

    border: 1px solid #f5c6cb;
}


/* =========================================
   CARD
========================================= */

.card {

    background: white;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 12px
        rgba(0,0,0,.08);
}


/* =========================================
   TABLE
========================================= */

.table-wrapper {

    overflow-x: auto;
}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;
}


th {

    background: #003366;

    color: white;

    padding: 13px;

    text-align: left;
}


td {

    padding: 13px;

    border-bottom:
        1px solid #eee;

    vertical-align: middle;
}


tr:hover {

    background: #f8fbff;
}


/* =========================================
   STUDENT
========================================= */

.student-name {

    font-weight: bold;

    color: #003366;
}


.reference {

    font-size: 12px;

    color: #777;
}


.subject {

    font-weight: bold;
}


/* =========================================
   BADGES
========================================= */

.badge {

    display: inline-block;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;
}


.paid {

    background: #d4edda;

    color: #155724;
}


.pending {

    background: #fff3cd;

    color: #856404;
}


.assigned {

    background: #d1ecf1;

    color: #0c5460;
}


.not-assigned {

    background: #f8d7da;

    color: #721c24;
}


/* =========================================
   ASSIGN FORM
========================================= */

.assign-form {

    display: flex;

    gap: 8px;

    align-items: center;
}


.assign-form select {

    min-width: 190px;

    padding: 9px;

    border:
        1px solid #ccc;

    border-radius: 6px;

    background: white;
}


.assign-form button {

    border: none;

    padding: 9px 14px;

    background: #003366;

    color: white;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;
}


.assign-form button:hover {

    background: #0055a5;
}


/* =========================================
   MOBILE
========================================= */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }


    .main {

        margin-left: 0;

        padding: 15px;
    }


    .assign-form {

        flex-direction: column;

        align-items: stretch;
    }


    .assign-form select,
    .assign-form button {

        width: 100%;
    }

}

</style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

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
            👨‍🎓 Students
        </a>


        <a href="teachers.php">
            👨‍🏫 Teachers
        </a>


        <a href="assign_teachers.php"
           class="active">

            👨‍🏫 Assign Teachers

        </a>


        <a href="bookings.php">
            📚 Bookings
        </a>


        <a href="payments.php">
            💳 Payments
        </a>


        <a href="teacher_applications.php">
            📝 Teacher Applications
        </a>


        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</div>



<!-- =========================================
     MAIN
========================================= -->

<div class="main">


    <div class="header">

        <h1>
            👨‍🏫 Assign Teachers
        </h1>

        <p>
            Assign an available teacher to each
            student booking.
        </p>

    </div>



    <?php if ($message !== ""): ?>

        <div class="message
            <?php
            echo $message_type === "success"
                ? "success"
                : "error";
            ?>">

            <?php
            echo $message;
            ?>

        </div>

    <?php endif; ?>



    <div class="card">

        <h2>
            Student Bookings
        </h2>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Student
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Curriculum
                        </th>

                        <th>
                            Class / Year
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Current Teacher
                        </th>

                        <th>
                            Assign Teacher
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($bookings) > 0): ?>


                    <?php foreach ($bookings as $booking): ?>


                    <tr>


                        <!-- STUDENT -->

                        <td>

                            <div class="student-name">

                                <?php
                                echo htmlspecialchars(
                                    $booking["student_name"]
                                );
                                ?>

                            </div>


                            <div class="reference">

                                <?php
                                echo htmlspecialchars(
                                    $booking["booking_reference"]
                                );
                                ?>

                            </div>

                        </td>



                        <!-- SUBJECT -->

                        <td>

                            <span class="subject">

                                <?php
                                echo htmlspecialchars(
                                    $booking["subjects"]
                                    ?? ""
                                );
                                ?>

                            </span>

                        </td>



                        <!-- CURRICULUM -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["curriculum"]
                                ?? ""
                            );
                            ?>

                        </td>



                        <!-- CLASS -->

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $booking["class_year"]
                                ?? ""
                            );
                            ?>

                        </td>



                        <!-- PAYMENT -->

                        <td>

                            <?php

                            $payment =
                                strtolower(
                                    trim(
                                        $booking[
                                            "payment_status"
                                        ] ?? ""
                                    )
                                );

                            if (
                                $payment === "paid"
                                ||
                                $payment === "success"
                            ) {

                                echo
                                '<span class="badge paid">
                                    PAID
                                </span>';

                            } else {

                                echo
                                '<span class="badge pending">
                                    PENDING
                                </span>';

                            }

                            ?>

                        </td>



                        <!-- CURRENT TEACHER -->

                        <td>

                            <?php

                            if (
                                !empty(
                                    $booking[
                                        "teacher_name"
                                    ]
                                )
                            ) {

                                echo
                                '<span class="badge assigned">'
                                .
                                htmlspecialchars(
                                    $booking[
                                        "teacher_name"
                                    ]
                                )
                                .
                                '</span>';

                            } else {

                                echo
                                '<span class="badge not-assigned">
                                    Not Assigned
                                </span>';

                            }

                            ?>

                        </td>



                        <!-- ASSIGN -->

                        <td>


                            <form
                                method="POST"
                                class="assign-form"
                            >


                                <input
                                    type="hidden"
                                    name="booking_id"
                                    value="<?php
                                    echo (int)
                                        $booking["id"];
                                    ?>"
                                >


                                <select
                                    name="teacher_id"
                                    required
                                >

                                    <option value="">

                                        -- Select Teacher --

                                    </option>


                                    <?php
                                    foreach (
                                        $teachers
                                        as $teacher
                                    ):
                                    ?>

                                        <option
                                            value="<?php
                                            echo htmlspecialchars(
                                                $teacher[
                                                    "teacher_id"
                                                ]
                                            );
                                            ?>"

                                            <?php

                                            if (
                                                $booking[
                                                    "teacher_id"
                                                ]
                                                ===
                                                $teacher[
                                                    "teacher_id"
                                                ]
                                            ) {

                                                echo "selected";

                                            }

                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $teacher[
                                                    "teacher_name"
                                                ]
                                            );
                                            ?>

                                        </option>

                                    <?php
                                    endforeach;
                                    ?>

                                </select>


                                <button
                                    type="submit"
                                    name="assign_teacher"
                                    value="1"
                                >

                                    Assign

                                </button>


                            </form>


                        </td>


                    </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            style="
                                text-align:center;
                                padding:40px;
                            "
                        >

                            No student bookings
                            available for assignment.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</div>


</body>

</html>
