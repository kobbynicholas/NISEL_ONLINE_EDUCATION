<?php

require "config/db.php";

if(!isset($_GET['booking'])){
    die("Invalid Booking.");
}

$booking = $_GET['booking'];

$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_reference=?");
$stmt->bind_param("s",$booking);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){
    die("Booking not found.");
}

$row = $result->fetch_assoc();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Booking Successful</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,sans-serif;
}

body{

background:#f2f8ff;

display:flex;

justify-content:center;

align-items:center;

min-height:100vh;

}

.card{

width:90%;

max-width:700px;

background:white;

padding:40px;

border-radius:15px;

box-shadow:0 15px 35px rgba(0,0,0,.15);

text-align:center;

}

.success{

font-size:80px;

color:#28a745;

}

h1{

color:#003366;

margin-top:15px;

margin-bottom:20px;

}

.message{

font-size:18px;

margin-bottom:30px;

color:#555;

}

table{

width:100%;

border-collapse:collapse;

margin-top:20px;

}

table td{

padding:12px;

border-bottom:1px solid #ddd;

text-align:left;

}

table td:first-child{

font-weight:bold;

width:40%;

color:#003366;

}

.notice{

margin-top:30px;

background:#eef7ff;

padding:20px;

border-left:5px solid #003366;

border-radius:8px;

text-align:left;

}

.notice h3{

margin-bottom:10px;

color:#003366;

}

.btn{

display:inline-block;

margin-top:35px;

padding:15px 35px;

background:#003366;

color:white;

text-decoration:none;

border-radius:8px;

font-weight:bold;

transition:.3s;

}

.btn:hover{

background:#0056b3;

}

footer{

margin-top:30px;

font-size:14px;

color:#777;

}

</style>

</head>

<body>

<div class="card">

<div class="success">

✓

</div>

<h1>Payment Successful</h1>

<p class="message">

Thank you for booking your lesson with

<strong>NISEL ONLINE EDUCATION</strong>.

</p>

<table>

<tr>

<td>Booking Reference</td>

<td><?php echo htmlspecialchars($row['booking_reference']); ?></td>

</tr>

<tr>

<td>Student Name</td>

<td><?php echo htmlspecialchars($row['student_name']); ?></td>

</tr>

<tr>

<td>Email Address</td>

<td><?php echo htmlspecialchars($row['email']); ?></td>

</tr>

<tr>

<td>Phone Number</td>

<td><?php echo htmlspecialchars($row['phone']); ?></td>

</tr>

<tr>

<td>Curriculum</td>

<td><?php echo htmlspecialchars($row['curriculum']); ?></td>

</tr>

<tr>

<td>Class / Year</td>

<td><?php echo htmlspecialchars($row['class_year']); ?></td>

</tr>

<tr>

<td>Subjects</td>

<td><?php echo htmlspecialchars($row['subjects']); ?></td>

</tr>

<tr>

<td>Amount Paid</td>

<td>GHC <?php echo number_format($row['amount'],2); ?></td>

</tr>

<tr>

<td>Payment Status</td>

<td style="color:green;font-weight:bold;">
<?php echo htmlspecialchars($row['payment_status']); ?>
</td>

</tr>

</table>

<div class="notice">

<h3>What's Next?</h3>

<p>

✔ Your booking has been received successfully.

</p>

<p>

✔ A personal tutor will be assigned to you shortly.

</p>

<p>

✔ You will receive an email confirmation.

</p>

<p>

✔ Your tutor will contact you before your first lesson.

</p>

</div>

<a href="index.html" class="btn">

Return to Home

</a>

<footer>

© <?php echo date("Y"); ?>

NISEL ONLINE EDUCATION

</footer>

</div>

</body>

</html>
