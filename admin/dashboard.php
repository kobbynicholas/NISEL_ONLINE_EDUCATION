<?php

// Later we will connect this to MySQL

$total_students = 0;
$total_teachers = 0;
$total_bookings = 0;
$total_revenue = 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>NISEL Admin Dashboard</title>


<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">


<style>


*{

margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial, sans-serif;

}


body{

background:#f1f5f9;

}


/* SIDEBAR */

.sidebar{

position:fixed;

left:0;

top:0;

width:260px;

height:100vh;

background:#003366;

color:white;

padding:20px;

}


.logo{

text-align:center;

font-size:22px;

font-weight:bold;

margin-bottom:30px;

}


.sidebar a{

display:block;

color:white;

text-decoration:none;

padding:14px;

margin-bottom:8px;

border-radius:8px;

}


.sidebar a:hover{

background:#0055aa;

}


/* MAIN */

.main{

margin-left:260px;

padding:30px;

}


/* HEADER */

.header{

background:white;

padding:20px;

border-radius:10px;

display:flex;

justify-content:space-between;

margin-bottom:30px;

}


.header h2{

color:#003366;

}


.admin{

font-weight:bold;

color:#555;

}


/* CARDS */


.cards{

display:grid;

grid-template-columns:repeat(auto-fit,minmax(220px,1fr));

gap:20px;

}



.card{

background:white;

padding:25px;

border-radius:15px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

}



.card i{

font-size:35px;

color:#003366;

margin-bottom:15px;

}


.card h3{

font-size:28px;

}


.card p{

color:#777;

}



/* TABLE */

.table-box{

background:white;

padding:25px;

margin-top:30px;

border-radius:15px;

}


table{

width:100%;

border-collapse:collapse;

}


th{

background:#003366;

color:white;

padding:12px;

}


td{

padding:12px;

border-bottom:1px solid #ddd;

}


/* BUTTONS */


.actions{

margin-top:20px;

}


.btn{

display:inline-block;

background:#003366;

color:white;

padding:12px 20px;

border-radius:8px;

text-decoration:none;

margin-right:10px;

}


.btn:hover{

background:#0055aa;

}


</style>


</head>


<body>


<!-- SIDEBAR -->

<div class="sidebar">


<div class="logo">

NISEL ADMIN

</div>


<a href="dashboard.php">
<i class="fa fa-home"></i>
Dashboard
</a>


<a href="students.php">
<i class="fa fa-user-graduate"></i>
Students
</a>


<a href="teachers.php">
<i class="fa fa-chalkboard-user"></i>
Teachers
</a>


<a href="bookings.php">
<i class="fa fa-calendar"></i>
Bookings
</a>


<a href="payments.php">
<i class="fa fa-money-bill"></i>
Payments
</a>


<a href="subjects.php">
<i class="fa fa-book"></i>
Subjects
</a>


<a href="reports.php">
<i class="fa fa-chart-line"></i>
Reports
</a>


<a href="settings.php">
<i class="fa fa-gear"></i>
Settings
</a>


<a href="../logout.php">

<i class="fa fa-sign-out"></i>

Logout

</a>


</div>





<!-- MAIN CONTENT -->

<div class="main">


<div class="header">


<h2>
Welcome, Administrator
</h2>


<div class="admin">

NISEL ONLINE EDUCATION

</div>


</div>





<div class="cards">


<div class="card">

<i class="fa fa-users"></i>

<h3>

<?php echo $total_students; ?>

</h3>

<p>Total Students</p>

</div>



<div class="card">

<i class="fa fa-chalkboard-teacher"></i>

<h3>

<?php echo $total_teachers; ?>

</h3>

<p>Total Teachers</p>

</div>



<div class="card">

<i class="fa fa-calendar-check"></i>

<h3>

<?php echo $total_bookings; ?>

</h3>

<p>Total Bookings</p>

</div>



<div class="card">

<i class="fa fa-money-bill-wave"></i>

<h3>

GHC <?php echo number_format($total_revenue); ?>

</h3>

<p>Total Revenue</p>

</div>


</div>





<div class="actions">

<a class="btn" href="students.php">

Add Student

</a>


<a class="btn" href="teachers.php">

Add Teacher

</a>


<a class="btn" href="bookings.php">

View Bookings

</a>


</div>






<div class="table-box">


<h3>

Recent Bookings

</h3>


<br>


<table>


<tr>

<th>Student</th>

<th>Subject</th>

<th>Curriculum</th>

<th>Status</th>

</tr>



<tr>

<td>No data yet</td>

<td>-</td>

<td>-</td>

<td>-</td>

</tr>


</table>


</div>







<div class="table-box">


<h3>

Recent Payments

</h3>


<br>


<table>


<tr>

<th>Student</th>

<th>Amount</th>

<th>Method</th>

<th>Status</th>

</tr>


<tr>

<td>No payment yet</td>

<td>-</td>

<td>-</td>

<td>-</td>


</tr>


</table>


</div>




</div>


</body>

</html>
