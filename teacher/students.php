<?php

require "../teacher_auth.php";
require "../config/db.php";

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

/*
==================================================
SEARCH
==================================================
*/

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/*
==================================================
GET ASSIGNED STUDENTS
==================================================
*/

if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT
            id,
            booking_reference,
            student_name,
            dob,
            phone,
            email,
            curriculum,
            class_year,
            subjects,
            payment_status,
            teacher_name
        FROM bookings
        WHERE teacher_id = ?
        AND (
            student_name LIKE ?
            OR email LIKE ?
            OR phone LIKE ?
            OR subjects LIKE ?
            OR curriculum LIKE ?
            OR class_year LIKE ?
        )
        ORDER BY id DESC
    ");

    $searchTerm = "%" . $search . "%";

    $stmt->bind_param(
        "sssssss",
        $teacher_id,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

} else {

    $stmt = $conn->prepare("
        SELECT
            id,
            booking_reference,
            student_name,
            dob,
            phone,
            email,
            curriculum,
            class_year,
            subjects,
            payment_status,
            teacher_name
        FROM bookings
        WHERE teacher_id = ?
        ORDER BY id DESC
    ");

    $stmt->bind_param("s", $teacher_id);
}

$stmt->execute();

$students = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
My Students | NISEL ONLINE EDUCATION
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


/* ==========================================
   SIDEBAR
========================================== */

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


/* ==========================================
   MAIN CONTENT
========================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* ==========================================
   TOP BAR
========================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}

.topbar h2 {

    margin: 0;

    color: #003366;

}

.teacher {

    color: #666;

}


/* ==========================================
   PAGE HEADER
========================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}

.page-header h3 {

    margin: 0 0 8px 0;

    color: #003366;

}

.page-header p {

    margin: 0;

    color: #666;

}


/* ==========================================
   SEARCH
========================================== */

.search-box {

    background: white;

    padding: 20px;

    border-radius: 12px;

    margin-bottom: 20px;

}

.search-form {

    display: flex;

    gap: 10px;

}

.search-form input {

    flex: 1;

    padding: 12px;

    border: 1px solid #ccc;

    border-radius: 7px;

    font-size: 15px;

}

.search-form button {

    padding: 12px 20px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 7px;

    cursor: pointer;

}

.search-form button:hover {

    background: #0055a5;

}

.clear {

    display: inline-block;

    padding: 12px 20px;

    background: #777;

    color: white;

    text-decoration: none;

    border-radius: 7px;

}


/* ==========================================
   TABLE
========================================== */

.table-container {

    background: white;

    padding: 20px;

    border-radius: 12px;

    overflow-x: auto;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

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

    white-space: nowrap;

}

td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}

tr:hover {

    background: #f7faff;

}


/* ==========================================
   STATUS
========================================== */

.badge {

    display: inline-block;

    padding: 5px 10px;

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

.other {

    background: #e2e3e5;

    color: #383d41;

}


/* ==========================================
   VIEW BUTTON
========================================== */

.view-btn {

    display: inline-block;

    padding: 8px 12px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 13px;

}

.view-btn:hover {

    background: #0055a5;

}


/* ==========================================
   NO STUDENTS
========================================== */

.no-students {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}

.no-students h3 {

    color: #003366;

}


/* ==========================================
   MOBILE
========================================== */

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

    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;

    }

    .search-form {

        flex-direction: column;

    }

    .search-form button,
    .clear {

        width: 100%;

        text-align: center;

    }

}

</style>

</head>


<body>


<!-- ==========================================
     SIDEBAR
========================================== -->

<div class="sidebar">

    <div class="logo">

        NISEL<br>
        ONLINE EDUCATION

    </div>


    <div class="menu">

        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="students.php" class="active">

            👨‍🎓 My Students

        </a>


        <a href="schedule.php">

            📅 My Schedule

        </a>


        <a href="profile.php">

            👤 My Profile

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>

    </div>

</div>



<!-- ==========================================
     MAIN
========================================== -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h2>

            My Students

        </h2>


        <div class="teacher">

            Welcome,

            <strong>

                <?php
                echo htmlspecialchars($teacher_name);
                ?>

            </strong>

        </div>

    </div>



    <!-- PAGE HEADER -->

    <div class="page-header">

        <h3>

            Assigned Students

        </h3>

        <p>

            Below are the students currently assigned
            to you by the NISEL administrator.

        </p>

    </div>



    <!-- SEARCH -->

    <div class="search-box">

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                placeholder="Search student, email, subject, curriculum or class..."
                value="<?php echo htmlspecialchars($search); ?>"
            >


            <button type="submit">

                🔍 Search

            </button>


            <?php if ($search !== ""): ?>

                <a
                    href="students.php"
                    class="clear"
                >

                    Clear

                </a>

            <?php endif; ?>

        </form>

    </div>



    <!-- STUDENTS TABLE -->

    <div class="table-container">


        <?php if ($students->num_rows > 0): ?>


        <table>

            <tr>

                <th>
                    Student
                </th>

                <th>
                    Contact
                </th>

                <th>
                    Date of Birth
                </th>

                <th>
                    Curriculum
                </th>

                <th>
                    Class / Year / Grade
                </th>

                <th>
                    Subject(s)
                </th>

                <th>
                    Payment
                </th>

                <th>
                    Action
                </th>

            </tr>


            <?php while ($student = $students->fetch_assoc()): ?>


            <tr>


                <!-- STUDENT -->

                <td>

                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $student['student_name']
                        );

                        ?>

                    </strong>

                    <br>

                    <small>

                        Ref:

                        <?php

                        echo htmlspecialchars(
                            $student['booking_reference']
                        );

                        ?>

                    </small>

                </td>



                <!-- CONTACT -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $student['email']
                    );

                    ?>

                    <br>

                    <?php

                    echo htmlspecialchars(
                        $student['phone']
                    );

                    ?>

                </td>



                <!-- DOB -->

                <td>

                    <?php

                    if (!empty($student['dob'])) {

                        echo htmlspecialchars(
                            date(
                                "d M Y",
                                strtotime($student['dob'])
                            )
                        );

                    } else {

                        echo "N/A";

                    }

                    ?>

                </td>



                <!-- CURRICULUM -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $student['curriculum']
                    );

                    ?>

                </td>



                <!-- CLASS -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $student['class_year']
                    );

                    ?>

                </td>



                <!-- SUBJECTS -->

                <td>

                    <?php

                    echo htmlspecialchars(
                        $student['subjects']
                    );

                    ?>

                </td>



                <!-- PAYMENT -->

                <td>


                    <?php

                    $status =
                        strtolower(
                            trim(
                                $student['payment_status']
                            )
                        );


                    if (
                        $status === "paid" ||
                        $status === "success"
                    ) {

                        echo '<span class="badge paid">
                                PAID
                              </span>';

                    } elseif (
                        $status === "pending"
                    ) {

                        echo '<span class="badge pending">
                                PENDING
                              </span>';

                    } else {

                        echo '<span class="badge other">'
                            . htmlspecialchars(
                                $student['payment_status']
                            )
                            . '</span>';

                    }

                    ?>

                </td>



                <!-- ACTION -->

                <td>

                    <a
                        href="student_details.php?id=<?php
                            echo (int)$student['id'];
                        ?>"
                        class="view-btn"
                    >

                        View Details

                    </a>

                </td>


            </tr>


            <?php endwhile; ?>


        </table>


        <?php else: ?>


            <div class="no-students">

                <h3>

                    No Students Found

                </h3>

                <p>

                    <?php if ($search !== ""): ?>

                        No students matched your search.

                    <?php else: ?>

                        You currently have no students assigned
                        to you.

                    <?php endif; ?>

                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>

<?php

$stmt->close();

$conn->close();

?>
