<?php

require "../admin_auth.php";

require "../config/db.php";



// ============================
// STUDENTS
// ============================

$studentsQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM students

");

$students = $studentsQuery->fetch(PDO::FETCH_ASSOC)['total'];



// ============================
// TEACHERS
// ============================

$teachersQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM teachers

");

$teachers = $teachersQuery->fetch(PDO::FETCH_ASSOC)['total'];



// ============================
// BOOKINGS
// ============================

$bookingsQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM bookings

");

$bookings = $bookingsQuery->fetch(PDO::FETCH_ASSOC)['total'];



// ============================
// PAYMENTS / REVENUE
// ============================

$revenueQuery = $pdo->query("

    SELECT SUM(amount) AS total

    FROM payments

    WHERE status = 'success'

");

$revenue = $revenueQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


?>


<!DOCTYPE html>

<html>

<head>

<title>NISEL Reports</title>


<style>


body{

font-family:Arial;

background:#eef3f8;

padding:30px;

}


.cards{

display:grid;

grid-template-columns:repeat(4,1fr);

gap:20px;

}



.card{

background:white;

padding:30px;

border-radius:15px;

text-align:center;

}



.number{

font-size:35px;

font-weight:bold;

color:#003366;

}



.table{

background:white;

margin-top:30px;

padding:20px;

}



</style>


</head>


<body>


<h2>

NISEL ONLINE EDUCATION REPORTS

</h2>


<br>


<div class="cards">


<div class="card">

<p>Total Students</p>

<div class="number">

<?php echo $students; ?>

</div>

</div>



<div class="card">

<p>Total Teachers</p>

<div class="number">

<?php echo $teachers; ?>

</div>

</div>



<div class="card">

<p>Total Bookings</p>

<div class="number">

<?php echo $bookings; ?>

</div>

</div>



<div class="card">

<p>Total Revenue</p>

<div class="number">

GHC <?php echo number_format($revenue); ?>

</div>

</div>


</div>



<div class="table">


<h3>

Generate Reports

</h3>


<br>


<a href="students.php">

Student Report

</a>


<br><br>


<a href="teachers.php">

Teacher Report

</a>


<br><br>


<a href="payments.php">

Payment Report

</a>


<br><br>


<a href="bookings.php">

Booking Report

</a>


</div>


</body>

</html>
