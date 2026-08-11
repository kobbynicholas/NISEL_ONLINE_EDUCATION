<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT PAYMENTS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['student_id']) ||
    empty($_SESSION['student_id'])
) {

    header("Location: login.php");
    exit;

}


$student_id =
    (int) $_SESSION['student_id'];


/*
|--------------------------------------------------------------------------
| PAYSTACK SECRET KEY
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Keep your existing Paystack TEST secret key here.
| Do NOT put the secret key in JavaScript or HTML.
|
|--------------------------------------------------------------------------
*/

$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";


/*
|--------------------------------------------------------------------------
| CURRICULUM / PROGRAM PRICES
|--------------------------------------------------------------------------
*/

$prices = [

    'Cambridge' => 1000,

    'IB' => 1200,

    'GES' => 800,

    'SAT' => 850

];


/*
|--------------------------------------------------------------------------
| GET BOOKING ID
|--------------------------------------------------------------------------
*/

$booking_id =
    isset($_GET['booking_id'])
    ? (int) $_GET['booking_id']
    : 0;


/*
|--------------------------------------------------------------------------
| ALSO CHECK SESSION
|--------------------------------------------------------------------------
*/

if (
    $booking_id <= 0 &&
    isset(
        $_SESSION['pending_booking_id']
    )
) {

    $booking_id =
        (int)
        $_SESSION['pending_booking_id'];

}


/*
|--------------------------------------------------------------------------
| BOOKING ID REQUIRED
|--------------------------------------------------------------------------
*/

if ($booking_id <= 0) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
            text-align:center;
        '>

            <h2>
                Payment Error
            </h2>

            <p>
                No booking was selected for payment.
            </p>

            <a href='book_lesson.php'>
                Return to Book a Lesson
            </a>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| GET BOOKING
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $pdo->prepare("

            SELECT

                id,

                booking_reference,

                student_id,

                student_name,

                dob,

                phone,

                email,

                curriculum,

                class_year,

                subjects,

                amount,

                payment_status,

                paystack_reference

            FROM bookings

            WHERE id = ?

            AND student_id = ?

            LIMIT 1

        ");


    $stmt->execute([

        $booking_id,

        $student_id

    ]);


    $booking =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Error
            </h2>

            <p>
                Unable to load your booking.
            </p>

            <pre>"
            .
            htmlspecialchars(
                $e->getMessage()
            )
            .
            "</pre>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| BOOKING NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$booking) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
            text-align:center;
        '>

            <h2>
                Booking Not Found
            </h2>

            <p>
                We could not find this booking.
            </p>

            <a href='book_lesson.php'>
                Book a Lesson
            </a>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| ALREADY PAID
|--------------------------------------------------------------------------
*/

if (
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    )
    ===
    'paid'
) {

    header(
        "Location: success.php?booking="
        .
        urlencode(
            $booking['booking_reference']
        )
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| GET PROGRAM / CURRICULUM
|--------------------------------------------------------------------------
*/

$curriculum =
    trim(
        $booking['curriculum']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| FIND CORRECT PRICE
|--------------------------------------------------------------------------
*/

$amount = 0;


/*
|--------------------------------------------------------------------------
| CASE-INSENSITIVE PRICE LOOKUP
|--------------------------------------------------------------------------
*/

foreach (
    $prices
    as $program =>
    $programPrice
) {

    if (
        strtolower($program)
        ===
        strtolower($curriculum)
    ) {

        $amount =
            (float)
            $programPrice;

        break;

    }

}


/*
|--------------------------------------------------------------------------
| INVALID PROGRAM
|--------------------------------------------------------------------------
*/

if ($amount <= 0) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
            text-align:center;
        '>

            <h2>
                Payment Error
            </h2>

            <p>
                We could not determine the price
                for this booking.
            </p>

            <p>

                Selected program:
                <strong>"
                .
                htmlspecialchars(
                    $curriculum
                )
                .
                "</strong>

            </p>

            <a href='book_lesson.php'>
                Return to Booking
            </a>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| FORCE CORRECT AMOUNT INTO BOOKING
|--------------------------------------------------------------------------
|
| We don't trust an amount sent by the browser.
|
|--------------------------------------------------------------------------
*/

try {

    $updateAmount =
        $pdo->prepare("

            UPDATE bookings

            SET amount = ?

            WHERE id = ?

            AND student_id = ?

        ");


    $updateAmount->execute([

        $amount,

        $booking_id,

        $student_id

    ]);


} catch (PDOException $e) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Error
            </h2>

            <p>
                Unable to prepare your booking
                for payment.
            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| GENERATE UNIQUE PAYSTACK REFERENCE
|--------------------------------------------------------------------------
|
| Paystack references may contain alphanumeric characters
| and certain punctuation. We use a simple safe format.
|
|--------------------------------------------------------------------------
*/

$paystackReference =
    'NISEL-'
    .
    date('YmdHis')
    .
    '-'
    .
    strtoupper(
        bin2hex(
            random_bytes(4)
        )
    );


/*
|--------------------------------------------------------------------------
| AMOUNT IN PESEWAS
|--------------------------------------------------------------------------
|
| Paystack requires the amount in the currency subunit.
|
| GHS 1,000 = 100000
| GHS 1,200 = 120000
| GHS   800 = 80000
| GHS   850 = 85000
|
|--------------------------------------------------------------------------
*/

$paystackAmount =
    (int)
    round(
        $amount * 100
    );


/*
|--------------------------------------------------------------------------
| CALLBACK URL
|--------------------------------------------------------------------------
|
| Paystack redirects the student here after checkout.
|
|--------------------------------------------------------------------------
*/

$callbackUrl =
    "http://localhost/online/verify_payment.php";


/*
|--------------------------------------------------------------------------
| INITIALIZE PAYSTACK TRANSACTION
|--------------------------------------------------------------------------
*/

$payload = [

    'email' =>
        $booking['email'],

    'amount' =>
        $paystackAmount,

    'currency' =>
        'GHS',

    'reference' =>
        $paystackReference,

    'callback_url' =>
        $callbackUrl,

    'metadata' => [

        'booking_id' =>
            (string)
            $booking['id'],

        'booking_reference' =>
            $booking['booking_reference'],

        'student_id' =>
            (string)
            $booking['student_id'],

        'student_name' =>
            $booking['student_name'],

        'curriculum' =>
            $curriculum,

        'subject' =>
            $booking['subjects']

    ]

];


$ch =
    curl_init(
        "https://api.paystack.co/transaction/initialize"
    );


curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [

        "Authorization: Bearer "
        .
        $secretKey,

        "Content-Type: application/json",

        "Cache-Control: no-cache"

    ]
);


curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    json_encode(
        $payload
    )
);


/*
|--------------------------------------------------------------------------
| SEND REQUEST
|--------------------------------------------------------------------------
*/

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


/*
|--------------------------------------------------------------------------
| CURL ERROR
|--------------------------------------------------------------------------
*/

if (
    $response === false
    ||
    $curlError !== ''
) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Initialization Failed
            </h2>

            <p>
                Unable to connect to Paystack.
            </p>

            <p>

                Error:
                "
                .
                htmlspecialchars(
                    $curlError
                )
                .
                "

            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| DECODE PAYSTACK RESPONSE
|--------------------------------------------------------------------------
*/

$result =
    json_decode(
        $response,
        true
    );


/*
|--------------------------------------------------------------------------
| INVALID RESPONSE
|--------------------------------------------------------------------------
*/

if (
    !is_array($result)
) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Initialization Failed
            </h2>

            <p>
                Paystack returned an invalid response.
            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| PAYSTACK INITIALIZATION FAILED
|--------------------------------------------------------------------------
*/

if (
    empty(
        $result['status']
    )
) {

    $paystackMessage =
        $result['message']
        ??
        'Unable to initialize payment.';


    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Initialization Failed
            </h2>

            <p>"
            .
            htmlspecialchars(
                $paystackMessage
            )
            .
            "</p>

            <p>
                HTTP Status:
                "
                .
                htmlspecialchars(
                    (string)
                    $httpCode
                )
                .
                "
            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| GET AUTHORIZATION URL
|--------------------------------------------------------------------------
*/

$authorizationUrl =
    $result['data']
    ['authorization_url']
    ??
    '';


$paystackAccessCode =
    $result['data']
    ['access_code']
    ??
    '';


$actualPaystackReference =
    $result['data']
    ['reference']
    ??
    $paystackReference;


/*
|--------------------------------------------------------------------------
| AUTHORIZATION URL REQUIRED
|--------------------------------------------------------------------------
*/

if (
    $authorizationUrl === ''
) {

    die("

        <div style='
            font-family:Arial;
            max-width:700px;
            margin:60px auto;
            padding:30px;
        '>

            <h2>
                Payment Initialization Failed
            </h2>

            <p>
                Paystack did not return a checkout URL.
            </p>

        </div>

    ");

}


/*
|--------------------------------------------------------------------------
| SAVE PAYSTACK REFERENCE
|--------------------------------------------------------------------------
*/

try {

    $saveReference =
        $pdo->prepare("

            UPDATE bookings

            SET paystack_reference = ?

            WHERE id = ?

            AND student_id = ?

        ");


    $saveReference->execute([

        $actualPaystackReference,

        $booking_id,

        $student_id

    ]);


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Don't stop the payment page if this fails.
    |--------------------------------------------------------------------------
    |
    | The reference is also passed to Paystack and will
    | be returned to verify_payment.php.
    |
    */

}


/*
|--------------------------------------------------------------------------
| SAVE SESSION INFORMATION
|--------------------------------------------------------------------------
*/

$_SESSION[
    'pending_booking_id'
] =
    $booking_id;


$_SESSION[
    'pending_booking_reference'
] =
    $booking['booking_reference'];


$_SESSION[
    'paystack_reference'
] =
    $actualPaystackReference;


/*
|--------------------------------------------------------------------------
| DISPLAY DATA
|--------------------------------------------------------------------------
*/

$bookingReference =
    $booking['booking_reference'];


$studentName =
    $booking['student_name']
    ?? '';


$email =
    $booking['email']
    ?? '';


$subject =
    $booking['subjects']
    ?? '';


$classYear =
    $booking['class_year']
    ?? '';

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

    font-family:
        Arial,
        sans-serif;

    background:
        #eef3f8;

    color:
        #333;

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
        0 5px 20px
        rgba(0,0,0,.10);

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

    border:
        1px solid #ddd;

    border-radius: 10px;

    overflow: hidden;

    margin-bottom: 25px;

}


.row {

    display: flex;

    justify-content:
        space-between;

    gap: 20px;

    padding: 14px 18px;

    border-bottom:
        1px solid #eee;

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

    background:
        #f4f8fc;

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

    background:
        #003366;

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

    background:
        #0055a5;

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

    background:
        #fff8e1;

    border-left:
        4px solid #f0ad4e;

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

            Complete your payment for your
            subject lesson package.

        </p>



        <div class="details">


            <div class="row">

                <span class="label">
                    Booking Reference
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $bookingReference
                    ) ?>

                </span>

            </div>



            <div class="row">

                <span class="label">
                    Student
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $studentName
                    ) ?>

                </span>

            </div>



            <div class="row">

                <span class="label">
                    Email
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $email
                    ) ?>

                </span>

            </div>



            <div class="row">

                <span class="label">
                    Curriculum
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $curriculum
                    ) ?>

                </span>

            </div>



            <div class="row">

                <span class="label">
                    Class / Year
                </span>

                <span class="value">

                    <?= htmlspecialchars(
                        $classYear
                    ) ?>

                </span>

            </div>



            <div class="row">

                <span class="label">
                    Subject
                </span>

                <span class="value">

                    <strong>

                        <?= htmlspecialchars(
                            $subject
                        ) ?>

                    </strong>

                </span>

            </div>


        </div>



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



        <div class="amount">

            <small>
                Monthly Subject Package
            </small>


            <strong>

                GHS

                <?= number_format(
                    $amount,
                    2
                ) ?>

            </strong>

        </div>



        <a
            href="<?= htmlspecialchars(
                $authorizationUrl
            ) ?>"
            class="pay-button"
        >

            💳 Pay GHS

            <?= number_format(
                $amount,
                2
            ) ?>

        </a>



        <div class="notice">

            <strong>
                Important:
            </strong>

            You are paying for the selected
            subject only.

            This booking provides 8 lessons
            per month, with 2 lessons scheduled
            each week.

            If you want another subject,
            create a separate booking.

        </div>



        <a
            href="book_lesson.php"
            class="back"
        >

            ← Back to Book a Subject

        </a>


    </div>

</div>


</body>

</html>
