<?php

require "../admin_auth.php";

require "../config/db.php";



// STUDENTS

$students=$conn->query(

"SELECT COUNT(*) total FROM students"

)->fetch_assoc()['total'];



// TEACHERS

$teachers=$conn->query(

"SELECT COUNT(*) total FROM teachers"

)->fetch_assoc()['total'];



// BOOKINGS

$bookings=$conn->query(

"SELECT COUNT(*) total FROM bookings"

)->fetch_assoc()['total'];



// PAYMENTS

$revenue=$conn->query(

"

SELECT SUM(amount) total

FROM payments

WHERE status='success'

"

)->fetch_assoc()['total'];


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

GHC <?php echo number_format($revenue ?? 0); ?>

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
