<?php

require "../admin_auth.php";
require "../config/db.php";

if($_SERVER["REQUEST_METHOD"]!="POST"){

header("Location:booking.html");

exit();

}


$name = trim($_POST['student_name']);

$dob = $_POST['dob'];

$phone = trim($_POST['phone']);

$email = trim($_POST['email']);

$curriculum = trim($_POST['curriculum']);

$class = trim($_POST['class']);

$subjects = trim($_POST['subjects']);

$subjectArray = array_filter(explode(",", $subjects));

$amount = count($subjectArray) * 1000;

$bookingReference = "NISEL-BK-" . date("YmdHis") . rand(100,999);



$sql="INSERT INTO bookings
(
booking_reference,
student_name,
dob,
phone,
email,
curriculum,
class_year,
subjects,
amount,
payment_status
)

VALUES
(
?,?,?,?,?,?,?,?,?,
'Pending'
)";


$stmt=$conn->prepare($sql);

$stmt->bind_param(

"ssssssssi",

$bookingReference,

$name,

$dob,

$phone,

$email,

$curriculum,

$class,

$subjects,

$amount

);


if($stmt->execute()){


header("Location: payment.php?booking=" . urlencode($bookingReference));
exit();


}else{


echo "Booking Failed";

}




?>






















