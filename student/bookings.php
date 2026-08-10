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
   GET STUDENT BOOKINGS
========================================================= */

try {

    /*
     * We use the student's email because your existing
     * bookings table stores the student's email.
     */

    $stmt = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE email = ?
        ORDER BY id DESC
    ");

    $stmt->execute([
        $student_email
    ]);

    $bookings = $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load your bookings: "
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
My Bookings | NISEL ONLINE EDUCATION
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
   BOOKING CARD
===================================================== */

.booking-container {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.booking-container h2 {

    margin-top: 0;

    color: #003366;

}


/* =====================================================
   TABLE
===================================================== */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 950px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

    vertical-align: top;

}


tr:hover {

    background: #f7f9fc;

}


/* =====================================================
   STATUS BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 6px 11px;

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


/* =====================================================
   EMPTY STATE
===================================================== */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 10px;

}


.book-button {

    display: inline-block;

    margin-top: 15px;

    padding: 12px 22px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 6px;

}


.book-button:hover {

    background: #0055aa;

}


/* =====================================================
   BOOKING REFERENCE
===================================================== */

.reference {

    font-family: monospace;

    font-weight: bold;

    color: #003366;

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


<a href="profile.php">

👤 My Profile

</a>


<a
    href="bookings.php"
    class="active"
>

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


<div class="header">

<h1>

My Bookings

</h1>

<p>

View your lesson bookings, payment status
and assigned teacher.

</p>

</div>


<div class="booking-container">


<h2>

Lesson Bookings

</h2>


<?php if (count($bookings) > 0): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
Booking Reference
</th>

<th>
Subjects
</th>

<th>
Curriculum
</th>

<th>
Class / Year
</th>

<th>
Amount
</th>

<th>
Payment
</th>

<th>
Teacher
</th>

<th>
Assignment
</th>

</tr>

</thead>


<tbody>


<?php foreach ($bookings as $booking): ?>


<tr>


<!-- BOOKING REFERENCE -->

<td>

<span class="reference">

<?php

echo htmlspecialchars(
    $booking['booking_reference']
    ?? $booking['id']
    ?? ''
);

?>

</span>

</td>


<!-- SUBJECTS -->

<td>

<?php

echo htmlspecialchars(
    $booking['subjects'] ?? ''
);

?>

</td>


<!-- CURRICULUM -->

<td>

<?php

echo htmlspecialchars(
    $booking['curriculum'] ?? ''
);

?>

</td>


<!-- CLASS -->

<td>

<?php

$class_year =
    $booking['class_year']
    ?? $booking['class']
    ?? '';

echo htmlspecialchars(
    $class_year
);

?>

</td>


<!-- AMOUNT -->

<td>

GHC

<?php

$amount =
    $booking['amount'] ?? 0;

echo number_format(
    (float) $amount,
    2
);

?>

</td>


<!-- PAYMENT STATUS -->

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
    $payment_status === "paid" ||
    $payment_status === "success"
) {

    echo '
        <span class="badge paid">
            Paid
        </span>
    ';

} else {

    echo '
        <span class="badge pending">
            Pending
        </span>
    ';

}

?>

</td>


<!-- TEACHER -->

<td>

<?php

if (
    !empty(
        $booking['teacher_name']
    )
) {

    echo htmlspecialchars(
        $booking['teacher_name']
    );

} else {

    echo "Not assigned";

}

?>

</td>


<!-- ASSIGNMENT STATUS -->

<td>

<?php

if (
    !empty(
        $booking['teacher_name']
    )
) {

    echo '
        <span class="badge assigned">
            Assigned
        </span>
    ';

} else {

    echo '
        <span class="badge not-assigned">
            Not Assigned
        </span>
    ';

}

?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty">


<div class="empty-icon">

📚

</div>


<h3>
No Bookings Yet
</h3>


<p>
You have not made a lesson booking yet.
</p>


<a
    href="../booking.php"
    class="book-button"
>

Book a Lesson

</a>


</div>


<?php endif; ?>


</div>


</div>


</body>

</html>
