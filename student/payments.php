<?php

session_start();

require "../config/db.php";
require "../config/pricing.php";


if (
    !isset($_SESSION['student_id'])
) {

    header(
        "Location: login.php"
    );

    exit;

}


$student_id =
    (int) $_SESSION['student_id'];


/* =========================================================
   PAYSTACK SECRET KEY
========================================================= */

/*
 * PUT YOUR CURRENT PAYSTACK SECRET KEY HERE.
 *
 * TEST MODE:
 * sk_test_...
 *
 * LIVE MODE:
 * sk_live_...
 *
 * DO NOT put pk_test_... here.
 */

$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";


/* =========================================================
   GET PAYSTACK REFERENCE
========================================================= */

$reference =
    trim(
        $_GET['reference']
        ??
        $_GET['trxref']
        ??
        ''
    );


if ($reference === '') {

    die("
        <h2>Payment Verification Failed</h2>
        <p>Paystack reference was not received.</p>
    ");

}


/* =========================================================
   VERIFY WITH PAYSTACK
========================================================= */

$url =
    "https://api.paystack.co/transaction/verify/"
    .
    urlencode($reference);


$ch = curl_init();


curl_setopt(
    $ch,
    CURLOPT_URL,
    $url
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

        "Authorization: Bearer " . $secretKey,

        "Cache-Control: no-cache",

        "Content-Type: application/json"

    ]
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


/* =========================================================
   EXECUTE CURL
========================================================= */

$response =
    curl_exec($ch);


$curlError =
    curl_error($ch);


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/* =========================================================
   CURL ERROR
========================================================= */

if ($response === false || $curlError !== '') {

    die("

        <h2>Payment Verification Failed</h2>

        <p>
            Unable to contact Paystack.
        </p>

        <p>
            CURL Error:
            "
            .
            htmlspecialchars(
                $curlError
            )
            .
            "
        </p>

    ");

}


/* =========================================================
   DECODE PAYSTACK RESPONSE
========================================================= */

$result =
    json_decode(
        $response,
        true
    );


/* =========================================================
   DEBUG PAYSTACK RESPONSE
========================================================= */

if (
    !is_array($result)
) {

    die("

        <h2>Payment Verification Failed</h2>

        <p>
            Paystack returned an invalid response.
        </p>

        <pre>"
        .
        htmlspecialchars(
            $response
        )
        .
        "</pre>

    ");

}


/* =========================================================
   PAYSTACK ERROR
========================================================= */

if (
    !isset(
        $result['status']
    )
    ||
    $result['status'] !== true
) {

    $paystackMessage =
        $result['message']
        ??
        'Unknown Paystack error.';


    die("

        <h2>Payment Verification Failed</h2>

        <p>
            Paystack could not verify this payment.
        </p>

        <p>

            <strong>
                Paystack response:
            </strong>

            "
            .
            htmlspecialchars(
                $paystackMessage
            )
            .
            "

        </p>

        <p>

            HTTP Status:
            "
            .
            htmlspecialchars(
                (string)$httpCode
            )
            .
            "

        </p>

        <p>

            Reference:
            "
            .
            htmlspecialchars(
                $reference
            )
            .
            "

        </p>

    ");

}


/* =========================================================
   TRANSACTION DATA
========================================================= */

$data =
    $result['data']
    ??
    [];


$transactionStatus =
    $data['status']
    ??
    '';


$paystackReference =
    $data['reference']
    ??
    $reference;


$amountPaid =
    (
        (float)(
            $data['amount']
            ??
            0
        )
        / 100
    );


$paymentChannel =
    $data['channel']
    ??
    'Unknown';


/* =========================================================
   CHECK PAYMENT STATUS
========================================================= */

if (
    $transactionStatus !== 'success'
) {

    die("

        <h2>Payment Verification Failed</h2>

        <p>

            Payment status from Paystack:

            <strong>
                "
                .
                htmlspecialchars(
                    $transactionStatus
                )
                .
                "
            </strong>

        </p>

    ");

}


/* =========================================================
   CHECK AMOUNT
========================================================= */

if (
    $amountPaid < 1000
) {

    die("

        <h2>Payment Verification Failed</h2>

        <p>

            The payment amount received was:

            <strong>
                GHS "
                .
                number_format(
                    $amountPaid,
                    2
                )
                .
                "
            </strong>

        </p>

        <p>

            Expected:

            <strong>
                GHS 1,000.00
            </strong>

        </p>

    ");

}


/* =========================================================
   GET BOOKING REFERENCE
========================================================= */

$bookingReference =
    $reference;


/*
 * Check metadata as well.
 */

if (
    isset(
        $data['metadata']
    )
    &&
    is_array(
        $data['metadata']
    )
) {

    if (
        !empty(
            $data['metadata']
            ['booking_reference']
        )
    ) {

        $bookingReference =
            $data['metadata']
            ['booking_reference'];

    }

}


/* =========================================================
   FIND BOOKING
========================================================= */

try {

    $stmt =
        $pdo->prepare("

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

    die("

        <h2>Payment Verification Failed</h2>

        <p>

            Database error while finding
            the booking.

        </p>

        <p>"
        .
        htmlspecialchars(
            $e->getMessage()
        )
        .
        "</p>

    ");

}


/* =========================================================
   BOOKING NOT FOUND
========================================================= */

if (!$booking) {

    die("

        <h2>Payment Verification Failed</h2>

        <p>

            Payment was verified by Paystack,
            but the booking could not be found.

        </p>

        <p>

            Booking Reference:

            <strong>
                "
                .
                htmlspecialchars(
                    $bookingReference
                )
                .
                "
            </strong>

        </p>

    ");

}


/* =========================================================
   BOOKING INFORMATION
========================================================= */

$bookingId =
    $booking['id'];


$studentName =
    $booking['student_name']
    ??
    '';


$email =
    $booking['email']
    ??
    '';


$subject =
    $booking['subjects']
    ??
    '';


$curriculum =
    $booking['curriculum']
    ??
    '';


$classYear =
    $booking['class_year']
    ??
    '';


/* =========================================================
   UPDATE BOOKING
========================================================= */

try {

    $stmt =
        $pdo->prepare("

            UPDATE bookings

            SET

                payment_status = 'Paid',

                paystack_reference = ?,

                amount = ?

            WHERE id = ?

        ");


    $stmt->execute([

        $paystackReference,

        $amountPaid,

        $bookingId

    ]);


} catch (PDOException $e) {

    die("

        <h2>Payment Verification Failed</h2>

        <p>

            Payment was verified, but the booking
            could not be updated.

        </p>

        <p>"
        .
        htmlspecialchars(
            $e->getMessage()
        )
        .
        "</p>

    ");

}


/* =========================================================
   SAVE PAYMENT RECORD
========================================================= */

try {

    $stmt =
        $pdo->prepare("

            SELECT id

            FROM payments

            WHERE transaction_reference = ?

            LIMIT 1

        ");


    $stmt->execute([
        $paystackReference
    ]);


    $existingPayment =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$existingPayment) {

        $insert =
            $pdo->prepare("

                INSERT INTO payments (

                    booking_reference,

                    student_name,

                    email,

                    amount,

                    payment_method,

                    transaction_reference,

                    status

                )

                VALUES (

                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Paid'

                )

            ");


        $insert->execute([

            $bookingReference,

            $studentName,

            $email,

            $amountPaid,

            $paymentChannel,

            $paystackReference

        ]);

    }


} catch (PDOException $e) {

    /*
     * Don't mark the whole payment as failed
     * if the booking itself was already updated.
     */

}


/* =========================================================
   REDIRECT TO SUCCESS
========================================================= */

header(

    "Location: success.php?booking="
    .
    urlencode(
        $bookingReference
    )

);

exit;

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
