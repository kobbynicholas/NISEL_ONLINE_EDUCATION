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

$student_name =
    $_SESSION['student_name'] ?? "Student";

$student_email =
    $_SESSION['student_email'] ?? "";


/* =========================================================
   GET STUDENT INFORMATION
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
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


    /* =====================================================
       TOTAL BOOKINGS
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE email = ?
    ");

    $stmt->execute([
        $student_email
    ]);

    $total_bookings =
        (int) $stmt->fetchColumn();


    /* =====================================================
       PAID BOOKINGS
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE email = ?
        AND (
            payment_status = 'Paid'
            OR payment_status = 'success'
        )
    ");

    $stmt->execute([
        $student_email
    ]);

    $paid_bookings =
        (int) $stmt->fetchColumn();


    /* =====================================================
       ASSIGNED TEACHERS
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM bookings
        WHERE email = ?
        AND teacher_id IS NOT NULL
        AND teacher_id <> ''
    ");

    $stmt->execute([
        $student_email
    ]);

    $assigned_teachers =
        (int) $stmt->fetchColumn();


    /* =====================================================
       RECENT BOOKINGS
    ===================================================== */

    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE email = ?
        ORDER BY id DESC
        LIMIT 5
    ");

    $stmt->execute([
        $student_email
    ]);

    $bookings =
        $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load student dashboard: "
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
Student Dashboard | NISEL ONLINE EDUCATION
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
   MAIN CONTENT
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

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
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(200px, 1fr));

    gap: 20px;

    margin-bottom: 30px;

}


.card {

    background: white;

    padding: 22px;

    border-radius: 10px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.card h3 {

    margin: 0;

    color: #666;

    font-size: 15px;

}


.number {

    font-size: 32px;

    font-weight: bold;

    color: #003366;

    margin-top: 10px;

}


/* =====================================================
   PROFILE
===================================================== */

.profile {

    background: white;

    padding: 25px;

    border-radius: 10px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.profile h2 {

    margin-top: 0;

    color: #003366;

}


.profile-row {

    display: grid;

    grid-template-columns:
        180px 1fr;

    padding: 10px 0;

    border-bottom: 1px solid #eee;

}


.profile-label {

    font-weight: bold;

    color: #555;

}


/* =====================================================
   QUICK LINKS
===================================================== */

.quick-links {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

    margin-bottom: 30px;

}


.quick-links a {

    text-decoration: none;

    background: #003366;

    color: white;

    padding: 15px;

    text-align: center;

    border-radius: 7px;

}


.quick-links a:hover {

    background: #0055aa;

}


/* =====================================================
   BOOKINGS
===================================================== */

.section {

    background: white;

    padding: 25px;

    border-radius: 10px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.section h2 {

    margin-top: 0;

    color: #003366;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #003366;

    color: white;

    padding: 11px;

    text-align: left;

}


td {

    padding: 11px;

    border-bottom: 1px solid #ddd;

}


/* =====================================================
   STATUS
===================================================== */

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


.assigned {

    background: #d1ecf1;

    color: #0c5460;

}


.not-assigned {

    background: #f8d7da;

    color: #721c24;

}


.empty {

    text-align: center;

    padding: 25px;

    color: #777;

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

    }


    .profile-row {

        grid-template-columns: 1fr;

        gap: 5px;

    }



/* =====================================================
   BOOKING CARD
===================================================== */

.booking-card {

    background: linear-gradient(
        135deg,
        #003366,
        #0055a5
    );

    color: white;

    padding: 25px;

    border-radius: 14px;

    margin-bottom: 25px;

    display: flex;

    align-items: center;

    gap: 25px;

    box-shadow:
        0 5px 18px rgba(0,0,0,.12);

}


.booking-icon {

    width: 75px;

    height: 75px;

    background: rgba(255,255,255,.15);

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 36px;

    flex-shrink: 0;

}


.booking-content {

    flex: 1;

}


.booking-content h2 {

    margin: 0 0 7px;

    font-size: 24px;

}


.booking-content p {

    margin: 0 0 15px;

    color: #e8f2ff;

}


.booking-info {

    display: flex;

    flex-wrap: wrap;

    gap: 15px;

    margin-bottom: 18px;

}


.booking-info span {

    background: rgba(255,255,255,.12);

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 13px;

}


.booking-button {

    display: inline-block;

    padding: 12px 22px;

    background: white;

    color: #003366;

    text-decoration: none;

    border-radius: 7px;

    font-weight: bold;

    transition: .2s;

}


.booking-button:hover {

    background: #f0f0f0;

    transform: translateY(-1px);

}


@media(max-width:650px) {

    .booking-card {

        flex-direction: column;

        align-items: flex-start;

    }


    .booking-info {

        flex-direction: column;

        gap: 8px;

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


<a
    href="dashboard.php"
    class="active"
>
🏠 Dashboard
</a>

   
<a href="profile.php">
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
     MAIN
===================================================== -->

<div class="main">


<!-- HEADER -->

<div class="header">

<h1>

Welcome,
<?php echo htmlspecialchars($student_name); ?>!

</h1>

<p>

Welcome to your NISEL ONLINE EDUCATION
student portal.

</p>



<!-- =====================================================
     BOOK A SUBJECT
===================================================== -->

<div class="booking-card">

    <div class="booking-icon">
        📚
    </div>

    <div class="booking-content">

        <h2>
            Book a Subject
        </h2>

        <p>
            Choose a subject and create your
            monthly lesson package.
        </p>

        <div class="booking-info">

            <span>
                📅 2 lessons per week
            </span>

            <span>
                📚 8 lessons per month
            </span>

            <span>
                💰 GHS 1,000
            </span>

        </div>

        <a
            href="book_lesson.php"
            class="booking-button"
        >

            ➕ Book a Subject

        </a>

    </div>

</div>


 
</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


<div class="card">

<h3>
Total Bookings
</h3>

<div class="number">

<?php echo $total_bookings; ?>

</div>

</div>


<div class="card">

<h3>
Paid Bookings
</h3>

<div class="number">

<?php echo $paid_bookings; ?>

</div>

</div>


<div class="card">

<h3>
Assigned Teachers
</h3>

<div class="number">

<?php echo $assigned_teachers; ?>

</div>

</div>


</div>


<!-- =====================================================
     PROFILE SUMMARY
===================================================== -->

<div class="profile">


<h2>
My Profile
</h2>


<div class="profile-row">

<div class="profile-label">
Name
</div>

<div>

<?php

echo htmlspecialchars(
    $student['student_name']
);

?>

</div>

</div>


<div class="profile-row">

<div class="profile-label">
Email
</div>

<div>

<?php

echo htmlspecialchars(
    $student['email']
);

?>

</div>

</div>


<div class="profile-row">

<div class="profile-label">
Phone
</div>

<div>

<?php

echo htmlspecialchars(
    $student['phone'] ?? ''
);

?>

</div>

</div>


</div>


<!-- =====================================================
     QUICK LINKS
===================================================== -->

<div class="quick-links">


<a href="bookings.php">
📚 Book a Lesson
</a>


<a href="schedule.php">
📅 View Schedule
</a>


<a href="payments.php">
💳 Payment History
</a>


<a href="profile.php">
👤 Edit Profile
</a>


</div>


<!-- =====================================================
     RECENT BOOKINGS
===================================================== -->

<div class="section">


<h2>
My Recent Bookings
</h2>


<div class="table-wrapper">


<table>


<tr>

<th>Subject(s)</th>

<th>Curriculum</th>

<th>Class / Year</th>

<th>Payment</th>

<th>Teacher</th>

</tr>


<?php if (count($bookings) > 0): ?>


<?php foreach ($bookings as $booking): ?>


<tr>


<td>

<?php

echo htmlspecialchars(
    $booking['subjects'] ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $booking['curriculum'] ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $booking['class_year']
    ?? $booking['class']
    ?? ''
);

?>

</td>


<td>


<?php

$payment_status =
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    );


if (
    $payment_status === 'paid'
    ||
    $payment_status === 'success'
) {

    echo
    '<span class="badge paid">
        Paid
    </span>';

} else {

    echo
    '<span class="badge pending">
        Pending
    </span>';

}

?>


</td>


<td>


<?php

if (
    !empty(
        $booking['teacher_name']
    )
) {

    echo
    '<span class="badge assigned">'
    .
    htmlspecialchars(
        $booking['teacher_name']
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


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="5"
    class="empty"
>

You do not have any bookings yet.

</td>

</tr>


<?php endif; ?>


</table>


</div>


</div>


</div>


</body>

</html>
