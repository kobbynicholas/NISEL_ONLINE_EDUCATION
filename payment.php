<?php

require "db.php";

//===============================
// PAYSTACK SECRET KEY
//===============================

// NEVER upload your real secret key to GitHub.
// Store it in an environment variable in production.
$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";


//===============================
// GET BOOKING REFERENCE
//===============================

if(!isset($_GET['booking'])){

    die("Invalid Booking Reference.");

}

$bookingReference = $_GET['booking'];


//===============================
// FETCH BOOKING
//===============================

$stmt = $conn->prepare("

SELECT *

FROM bookings

WHERE booking_reference=?

LIMIT 1

");

$stmt->bind_param("s",$bookingReference);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){

    die("Booking not found.");

}

$booking = $result->fetch_assoc();

$stmt->close();


//===============================
// CHECK PAYMENT STATUS
//===============================

if($booking['payment_status']=="Paid"){

    header("Location: success.php?booking=".$bookingReference);

    exit();

}


//===============================
// PAYMENT DETAILS
//===============================

$email = $booking['email'];

$amount = $booking['amount'] * 100;   // Convert GHS to pesewas


//===============================
// PAYSTACK REQUEST
//===============================

$url = "https://api.paystack.co/transaction/initialize";


$data = [

    "email"=>$email,

    "amount"=>$amount,

    "currency"=>"GHS",

    "reference"=>$bookingReference,

    "callback_url"=>"http://localhost/online/verify_payment.php",

    "metadata"=>[

        "booking_reference"=>$bookingReference,

        "student"=>$booking['student_name'],

        "subjects"=>$booking['subjects']

    ]

];


$fields = json_encode($data);


$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,$url);

curl_setopt($ch,CURLOPT_POST,true);

curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);

curl_setopt($ch,CURLOPT_HTTPHEADER,[

"Authorization: Bearer ".$secretKey,

"Content-Type: application/json"

]);

curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$response = curl_exec($ch);

if(curl_errno($ch)){

    die("Curl Error : ".curl_error($ch));

}

curl_close($ch);

$result = json_decode($response,true);


//===============================
// REDIRECT TO PAYSTACK
//===============================

if(isset($result['status']) && $result['status']==true){

    header("Location: ".$result['data']['authorization_url']);

    exit();

}else{

    echo "<h2>Unable to initialize payment.</h2>";

    echo "<pre>";

    print_r($result);

    echo "</pre>";

}

?>
