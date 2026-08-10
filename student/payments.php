<?php

session_start();

require __DIR__ . "/config/db.php";


/* =========================================================
   PAYSTACK SETTINGS
========================================================= */

$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";

$paystackPublicKey = "pk_test_YOUR_PUBLIC_KEY";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {

    header("Location: student/login.php");
    exit;

}


/* =========================================================
   GET BOOKING REFERENCE
========================================================= */

$bookingReference =
    trim(
        $_GET['booking'] ??
        $_SESSION['pending_booking']['booking_reference'] ??
        ''
    );


if ($bookingReference === '') {

    die("Booking reference not found.");

}


/* =========================================================
   GET BOOKING
========================================================= */

try {

    $stmt = $pdo->prepare("

        SELECT *

        FROM bookings

        WHERE booking_reference = ?

        LIMIT 1

    ");

    $stmt->execute([
        $bookingReference
    ]);

    $booking =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die(
        "Unable to load booking: "
        .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}


/* =========================================================
   CHECK BOOKING
========================================================= */

if (!$booking) {

    die("The requested booking could not be found.");

}


/* =========================================================
   MAKE SURE BOOKING BELONGS TO LOGGED-IN STUDENT
========================================================= */

$sessionEmail =
    $_SESSION['student_email'] ?? '';


if (
    !empty($sessionEmail)
    &&
    !empty($booking['email'])
    &&
    strtolower(
        trim($sessionEmail)
    )
    !==
    strtolower(
        trim($booking['email'])
    )
) {

    die("You are not authorized to pay for this booking.");

}


/* =========================================================
   CHECK PAYMENT STATUS
========================================================= */

$paymentStatus =
    strtolower(
        trim(
            $booking['payment_status'] ?? ''
        )
    );


if (
    $paymentStatus === 'paid'
    ||
    $paymentStatus === 'success'
) {

    header(
        "Location: verify_payment.php?reference="
        .
        urlencode(
            $bookingReference
        )
    );

    exit;

}


/* =========================================================
   BOOKING DETAILS
========================================================= */

$studentName =
    $booking['student_name'] ?? '';

$email =
    $booking['email'] ?? '';

$phone =
    $booking['phone'] ?? '';

$subject =
    $booking['subjects'] ?? '';

$curriculum =
    $booking['curriculum'] ?? '';

$classYear =
    $booking['class_year'] ?? '';


/* =========================================================
   PACKAGE PRICE
========================================================= */

/*
 * One subject booking:
 *
 * 2 lessons per week
 * 8 lessons per month
 *
 * Price = GHS 1,000
 */

$amount =
    1000;


/*
 * Paystack requires the amount in pesewas.
 */

$amountInPesewas =
    $amount * 100;


/* =========================================================
   UPDATE BOOKING AMOUNT
========================================================= */

try {

    $updateAmount =
        $pdo->prepare("

            UPDATE bookings

            SET amount = ?

            WHERE booking_reference = ?

        ");

    $updateAmount->execute([

        $amount,

        $bookingReference

    ]);


} catch (PDOException $e) {

    die(
        "Unable to prepare booking payment: "
        .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}


/* =========================================================
   PAYSTACK INITIALIZATION
========================================================= */

$paystackUrl =
    "https://api.paystack.co/transaction/initialize";


/*
 * Metadata is very important.
 *
 * verify_payment.php will use booking_reference
 * to identify the exact subject booking.
 */

$metadata = [

    "booking_reference" =>
        $bookingReference,

    "booking_id" =>
        $booking['id'],

    "student_name" =>
        $studentName,

    "student_email" =>
        $email,

    "subject" =>
        $subject,

    "curriculum" =>
        $curriculum,

    "class_year" =>
        $classYear,

    "lesson_package" =>
        "8 lessons per month",

    "lesson_frequency" =>
        "2 lessons per week"

];


/* =========================================================
   INITIALIZE PAYSTACK
========================================================= */

$paymentData = [

    "email" =>
        $email,

    "amount" =>
        $amountInPesewas,

    "currency" =>
        "GHS",

    "reference" =>
        $bookingReference,

    "callback_url" =>
        "http://localhost/online/verify_payment.php",

    "metadata" =>
        $metadata

];


$ch =
    curl_init(
        $paystackUrl
    );


curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);


curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode(
        $paymentData
    )
);


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [

        "Authorization: Bearer "
        . $secretKey,

        "Content-Type: application/json",

        "Cache-Control: no-cache"

    ]
);


$response =
    curl_exec($ch);


if (
    curl_errno($ch)
) {

    $curlError =
        curl_error($ch);

    curl_close($ch);

    die(
        "Unable to connect to Paystack: "
        .
        htmlspecialchars(
            $curlError
        )
    );

}


curl_close($ch);


/* =========================================================
   DECODE PAYSTACK RESPONSE
========================================================= */

$result =
    json_decode(
        $response,
        true
    );


/* =========================================================
   CHECK PAYSTACK RESPONSE
========================================================= */

if (
    !isset(
        $result['status']
    )
    ||
    $result['status'] !== true
) {

    $errorMessage =
        $result['message']
        ??
        "Unable to initialize payment.";

    die(
        "Payment initialization failed: "
        .
        htmlspecialchars(
            $errorMessage
        )
    );

}


if (
    !isset(
        $result['data']['authorization_url']
    )
) {

    die(
        "Paystack did not return a payment URL."
    );

}


$authorizationUrl =
    $result['data']['authorization_url'];

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">


<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<title>

Payment |
NISEL ONLINE EDUCATION

</title>


<style>

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

    color: #333;

}


.container {

    width: 92%;

    max-width: 800px;

    margin: 50px auto;

}


.card {

    background: white;

    padding: 35px;

    border-radius: 14px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.10);

}


.logo {

    text-align: center;

    color: #003366;

    font-size: 24px;

    font-weight: bold;

    line-height: 1.4;

    margin-bottom: 25px;

}


h1 {

    color: #003366;

    text-align: center;

    margin-bottom: 10px;

}


.subtitle {

    text-align: center;

    color: #666;

    margin-bottom: 30px;

}


.details {

    border: 1px solid #ddd;

    border-radius: 10px;

    overflow: hidden;

    margin-bottom: 25px;

}


.row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 14px 18px;

    border-bottom: 1px solid #eee;

}


.row:last-child {

    border-bottom: none;

}


.label {

    font-weight: bold;

    color: #555;

}


.value {

    text-align: right;

}


.package {

    background: #f4f8fc;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

}


.package h3 {

    margin-top: 0;

    color: #003366;

}


.package ul {

    margin-bottom: 0;

    line-height: 1.8;

}


.amount {

    text-align: center;

    margin: 25px 0;

}


.amount small {

    display: block;

    color: #666;

}


.amount strong {

    display: block;

    color: #003366;

    font-size: 36px;

    margin-top: 5px;

}


.pay-button {

    display: block;

    width: 100%;

    padding: 16px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 7px;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

    text-align: center;

    text-decoration: none;

}


.pay-button:hover {

    background: #0055a5;

}


.back {

    display: block;

    text-align: center;

    margin-top: 18px;

    color: #003366;

    text-decoration: none;

}


.notice {

    margin-top: 20px;

    padding: 14px;

    background: #fff8e1;

    border-left: 4px solid #f0ad4e;

    color: #66512c;

    line-height: 1.5;

}


@media(max-width:600px) {

    .container {

        width: 95%;

        margin: 20px auto;

    }


    .card {

        padding: 22px;

    }


    .row {

        flex-direction: column;

        gap: 5px;

    }


    .value {

        text-align: left;

    }

}

</style>

</head>


<body>


<div class="container">


<div class="card">


<div class="logo">

NISEL<br>

ONLINE EDUCATION

</div>


<h1>

💳 Payment

</h1>


<p class="subtitle">

Complete your payment for your subject
lesson package.

</p>


<!-- =====================================================
     BOOKING DETAILS
===================================================== -->

<div class="details">


<div class="row">

<span class="label">

Booking Reference

</span>


<span class="value">

<?php

echo htmlspecialchars(
    $bookingReference
);

?>

</span>

</div>



<div class="row">

<span class="label">

Student

</span>


<span class="value">

<?php

echo htmlspecialchars(
    $studentName
);

?>

</span>

</div>



<div class="row">

<span class="label">

Email

</span>


<span class="value">

<?php

echo htmlspecialchars(
    $email
);

?>

</span>

</div>



<div class="row">

<span class="label">

Curriculum

</span>


<span class="value">

<?php

echo htmlspecialchars(
    $curriculum
);

?>

</span>

</div>



<div class="row">

<span class="label">

Class / Year

</span>


<span class="value">

<?php

echo htmlspecialchars(
    $classYear
);

?>

</span>

</div>



<div class="row">

<span class="label">

Subject

</span>


<span class="value">

<strong>

<?php

echo htmlspecialchars(
    $subject
);

?>

</strong>

</span>

</div>


</div>



<!-- =====================================================
     PACKAGE
===================================================== -->

<div class="package">


<h3>

📚 Your Lesson Package

</h3>


<ul>

<li>

<strong>
2 lessons per week
</strong>

</li>


<li>

<strong>
8 lessons per month
</strong>

</li>


<li>

One subject per booking

</li>


<li>

Teacher assigned by NISEL administration

</li>


</ul>


</div>



<!-- =====================================================
     AMOUNT
===================================================== -->

<div class="amount">


<small>

Monthly Subject Package

</small>


<strong>

GHS

<?php

echo number_format(
    $amount,
    2
);

?>

</strong>


</div>



<!-- =====================================================
     PAYMENT BUTTON
===================================================== -->

<a
    href="<?php

        echo htmlspecialchars(
            $authorizationUrl
        );

    ?>"
    class="pay-button"
>

    💳 Pay GHS

    <?php

    echo number_format(
        $amount,
        2
    );

    ?>

</a>



<div class="notice">

<strong>

Important:

</strong>

You are paying for the selected subject only.
This booking provides 8 lessons per month,
with 2 lessons scheduled each week.

If you want another subject, create a
separate booking for that subject.

</div>



<a
    href="student/book_lesson.php"
    class="back"
>

    ← Back to Book a Subject

</a>


</div>


</div>


</body>

</html>
