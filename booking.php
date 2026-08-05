<?php

require "db.php";

if($_SERVER["REQUEST_METHOD"]!="POST"){

header("Location:booking.html");

exit();

}


$name=$_POST['student_name'];

$dob=$_POST['dob'];

$phone=$_POST['phone'];

$email=$_POST['email'];

$curriculum=$_POST['curriculum'];

$class = $_POST['class_year'];

$subjects = implode(", ", $_POST['subjects']);

$pricePerSubject = 1000;

$subjectCount = count($_POST['subjects']);

$amount = $subjectCount * $pricePerSubject;



$bookingReference="NISEL".date("YmdHis").rand(100,999);



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






















