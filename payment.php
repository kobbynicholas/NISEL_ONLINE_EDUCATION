<?php

// DATABASE CONNECTION

$host = "localhost";
$user = "root";
$password = "";
$database = "nisel_online_education";


$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);


if($conn->connect_error){

    die("Database connection failed");

}


// PAYSTACK SECRET KEY
// Store this safely in an environment variable in production

$secret_key = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";


// RECEIVE FORM DATA


$name = $_POST['student_name'];

$dob = $_POST['dob'];

$phone = $_POST['phone'];

$email = $_POST['email'];

$curriculum = $_POST['curriculum'];

$class = $_POST['class'];

$subjects = $_POST['subjects'];



// CALCULATE PAYMENT

$subject_array = explode(",", $subjects);

$number_of_subjects = count($subject_array);


$price_per_subject = 1000;


$amount = $number_of_subjects * $price_per_subject;


// Convert Ghana Cedis to Pesewas for Paystack

$paystack_amount = $amount * 100;



// CREATE BOOKING REFERENCE


$booking_reference = "NISEL-BK-" . date("YmdHis");



// SAVE BOOKING


$sql = "

INSERT INTO bookings

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
?,
?,
?,
?,
?,
?,
?,
?,
?,
'Pending'
)

";


$stmt = $conn->prepare($sql);


$stmt->bind_param(

"ssssssssi",

$booking_reference,
$name,
$dob,
$phone,
$email,
$curriculum,
$class,
$subjects,
$amount

);



if(!$stmt->execute()){

die("Booking failed");

}



// INITIALIZE PAYSTACK PAYMENT


$url = "https://api.paystack.co/transaction/initialize";


$data = [

"email" => $email,

"amount" => $paystack_amount,

"currency" => "GHS",

"callback_url" => 
"http://localhost/nisel/verify_payment.php",

"metadata" => [

"booking_reference" => $booking_reference,

"student_name" => $name,

"subjects" => $subjects

]

];



$fields = json_encode($data);



$ch = curl_init();


curl_setopt($ch,CURLOPT_URL,$url);

curl_setopt($ch,CURLOPT_POST,true);

curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);

curl_setopt($ch,CURLOPT_HTTPHEADER,[

"Authorization: Bearer ".$secret_key,

"Content-Type: application/json"

]);


curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);



$response = curl_exec($ch);



curl_close($ch);



$result = json_decode($response,true);



// REDIRECT TO PAYSTACK CHECKOUT


if($result['status']==true){


$payment_url = $result['data']['authorization_url'];


header("Location: ".$payment_url);

exit();


}

else{


echo "Unable to start payment";

}



?>
