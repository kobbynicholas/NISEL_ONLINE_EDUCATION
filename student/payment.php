<?php

session_start();

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| STUDENT PAYMENT
|--------------------------------------------------------------------------
| PDO + PAYSTACK
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK STUDENT LOGIN
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
    $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name']
    ?? 'Student';

$student_email =
    $_SESSION['student_email']
    ??
    $_SESSION['email']
    ??
    '';


/*
|--------------------------------------------------------------------------
| PAYSTACK SECRET KEY
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Do NOT put your live secret key in HTML or JavaScript.
|
| Replace the value below with your TEST secret key while
| developing locally.
|
| For production, move this to a configuration/environment
| file instead.
|
|--------------------------------------------------------------------------
*/

$paystackSecretKey =
    "sk_test_REPLACE_WITH_YOUR_PAYSTACK_SECRET_KEY";


/*
|--------------------------------------------------------------------------
| PAYSTACK CURRENCY
|--------------------------------------------------------------------------
*/

$currency = "GHS";


/*
|--------------------------------------------------------------------------
| DEFAULT SUBJECT PRICE
|--------------------------------------------------------------------------
|
| Your NISEL booking system currently uses GHS 1,000
| for a subject booking.
|
|--------------------------------------------------------------------------
*/

$defaultAmount = 1000;


/*
|--------------------------------------------------------------------------
| GET BOOKING REFERENCE
|--------------------------------------------------------------------------
*/

$bookingReference =
    trim(
        $_GET['booking']
        ??
        $_GET['booking_reference']
        ??
        ''
    );


/*
|--------------------------------------------------------------------------
| ERROR
|--------------------------------------------------------------------------
*/

$error = "";


/*
|--------------------------------------------------------------------------
| BOOKING INFORMATION
|--------------------------------------------------------------------------
*/

$booking = null;


/*
|--------------------------------------------------------------------------
| FIND BOOKING
|--------------------------------------------------------------------------
*/

if (
    !empty($bookingReference)
) {

    /*
    |--------------------------------------------------------------------------
    | SECURITY
    |--------------------------------------------------------------------------
    |
    | The booking must belong to the logged-in student.
    |
    | We check student_id OR email to support bookings created
    | before student_id was consistently stored.
    |
    */

    $bookingStmt = $pdo->prepare("

        SELECT

            b.id,

            b.booking_reference,

            b.student_id,

            b.student_name,

            b.email,

            b.phone,

            b.curriculum,

            b.class_year,

            b.subjects,

            b.amount,

            b.payment_status,

            b.paystack_reference,

            b.teacher_id,

            b.lesson_date,

            b.lesson_time

        FROM bookings b

        WHERE

            b.booking_reference = ?

            AND

            (

                b.student_id = ?

                OR

                LOWER(TRIM(b.email))
                =
                LOWER(TRIM(?))

            )

        LIMIT 1

    ");


    $bookingStmt->execute([

        $bookingReference,

        $student_id,

        $student_email

    ]);


    $booking =
        $bookingStmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$booking) {

        $error =
            "The booking could not be found or does not belong to your account.";

    }

}


/*
|--------------------------------------------------------------------------
| IF NO BOOKING REFERENCE
|--------------------------------------------------------------------------
*/

if (
    empty($bookingReference)
) {

    $error =
        "No booking was selected for payment.";

}


/*
|--------------------------------------------------------------------------
| DETERMINE AMOUNT
|--------------------------------------------------------------------------
*/

$amount = $defaultAmount;


if (
    $booking
    &&
    isset($booking['amount'])
    &&
    is_numeric($booking['amount'])
    &&
    (float)$booking['amount'] > 0
) {

    $amount =
        (float)$booking['amount'];

}


/*
|--------------------------------------------------------------------------
| PAYMENT ALREADY COMPLETED?
|--------------------------------------------------------------------------
*/

if (
    $booking
    &&
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    )
    === "paid"
) {

    $error =
        "This booking has already been paid for.";

}


/*
|--------------------------------------------------------------------------
| START PAYSTACK PAYMENT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['start_payment'])
) {


    /*
    |--------------------------------------------------------------------------
    | VERIFY BOOKING AGAIN
    |--------------------------------------------------------------------------
    |
    | Never trust booking information sent by the browser.
    |
    */

    $postedBookingReference =
        trim(
            $_POST['booking_reference']
            ?? ''
        );


    if (
        empty($postedBookingReference)
    ) {

        $error =
            "Invalid booking reference.";

    } elseif (
        !$booking
        ||
        $postedBookingReference
        !==
        $bookingReference
    ) {

        $error =
            "Invalid booking.";

    } elseif (
        empty($student_email)
    ) {

        $error =
            "Your student email address could not be found.";

    } elseif (
        strtolower(
            trim(
                $booking['payment_status']
                ?? ''
            )
        )
        === "paid"
    ) {

        $error =
            "This booking has already been paid for.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | CREATE UNIQUE PAYSTACK REFERENCE
        |--------------------------------------------------------------------------
        */

        $reference =

            "NISEL_"
            .
            strtoupper(
                bin2hex(
                    random_bytes(6)
                )
            );


        /*
        |--------------------------------------------------------------------------
        | AMOUNT IN PESEWAS
        |--------------------------------------------------------------------------
        |
        | GHS 1,000.00
        |
        | Paystack expects:
        |
        | 1000 × 100 = 100000
        |
        */

        $amountInPesewas =
            (int)round(
                $amount * 100
            );


        /*
        |--------------------------------------------------------------------------
        | CALLBACK URL
        |--------------------------------------------------------------------------
        |
        | This points to the existing verification page.
        |
        */

        $callbackUrl =

            "http://localhost/online/verify_payment.php";


        /*
        |--------------------------------------------------------------------------
        | PAYSTACK REQUEST
        |--------------------------------------------------------------------------
        */

        $payload = [

            "email" =>
                $booking['email']
                ??
                $student_email,

            "amount" =>
                $amountInPesewas,

            "currency" =>
                $currency,

            "reference" =>
                $reference,

            "callback_url" =>
                $callbackUrl,

            "channels" => [

                "card",

                "mobile_money"

            ],

            "metadata" => [

                "booking_reference" =>
                    $booking['booking_reference'],

                "student_id" =>
                    $student_id,

                "student_name" =>
                    $booking['student_name'],

                "subject" =>
                    $booking['subjects'],

                "curriculum" =>
                    $booking['curriculum'],

                "amount" =>
                    $amount,

                "cancel_action" =>
                    "http://localhost/online/student/payment.php?booking="
                    .
                    urlencode(
                        $booking['booking_reference']
                    )

            ]

        ];


        /*
        |--------------------------------------------------------------------------
        | CURL
        |--------------------------------------------------------------------------
        */

        $ch =
            curl_init(
                "https://api.paystack.co/transaction/initialize"
            );


        curl_setopt_array(

            $ch,

            [

                CURLOPT_RETURNTRANSFER =>
                    true,

                CURLOPT_POST =>
                    true,

                CURLOPT_POSTFIELDS =>
                    json_encode(
                        $payload
                    ),

                CURLOPT_HTTPHEADER => [

                    "Authorization: Bearer "
                    .
                    $paystackSecretKey,

                    "Content-Type: application/json",

                    "Cache-Control: no-cache"

                ],

                CURLOPT_TIMEOUT =>
                    30

            ]

        );


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
            !empty($curlError)
        ) {

            $error =
                "Unable to connect to Paystack. Please try again.";

        } else {


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
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            if (

                $httpCode >= 200

                &&

                $httpCode < 300

                &&

                isset(
                    $result['status']
                )

                &&

                $result['status'] === true

                &&

                !empty(
                    $result['data']['authorization_url']
                )

            ) {


                /*
                |--------------------------------------------------------------------------
                | SAVE PAYSTACK REFERENCE
                |--------------------------------------------------------------------------
                */

                $saveReference =
                    $pdo->prepare("

                        UPDATE bookings

                        SET

                            paystack_reference = ?

                        WHERE

                            booking_reference = ?

                            AND

                            (

                                student_id = ?

                                OR

                                LOWER(TRIM(email))
                                =
                                LOWER(TRIM(?))

                            )

                    ");


                $saveReference->execute([

                    $reference,

                    $booking['booking_reference'],

                    $student_id,

                    $student_email

                ]);


                /*
                |--------------------------------------------------------------------------
                | REDIRECT TO PAYSTACK
                |--------------------------------------------------------------------------
                */

                header(

                    "Location: "
                    .
                    $result[
                        'data'
                    ][
                        'authorization_url'
                    ]

                );

                exit;

            } else {


                /*
                |--------------------------------------------------------------------------
                | PAYSTACK ERROR
                |--------------------------------------------------------------------------
                */

                $paystackMessage =
                    $result['message']
                    ??
                    "Payment initialization failed.";


                $error =
                    $paystackMessage;

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| FORMAT MONEY
|--------------------------------------------------------------------------
*/

function h($value)
{

    return htmlspecialchars(

        (string)$value,

        ENT_QUOTES,

        'UTF-8'

    );

}


function money($amount)
{

    return number_format(

        (float)$amount,

        2

    );

}

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

    Make Payment |

    NISEL ONLINE EDUCATION

</title>


<style>

/* =====================================================
   GENERAL
===================================================== */

* {

    box-sizing: border-box;

}


body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #eef3f8;

    color: #333;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

}


.logo {

    text-align: center;

    font-size: 19px;

    font-weight: bold;

    line-height: 1.5;

    margin-bottom: 35px;

}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.menu a:hover {

    background: #0055a5;

}


.menu a.active {

    background: #0055a5;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    min-height: 100vh;

    padding: 40px;

}


/* =====================================================
   PAYMENT CARD
===================================================== */

.payment-card {

    max-width: 800px;

    margin: 20px auto;

    background: white;

    border-radius: 15px;

    padding: 35px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.08);

}


.payment-header {

    text-align: center;

    margin-bottom: 30px;

}


.payment-header h1 {

    color: #003366;

    margin-bottom: 8px;

}


.payment-header p {

    color: #777;

}


/* =====================================================
   ERROR
===================================================== */

.error {

    background: #f8d7da;

    color: #721c24;

    padding: 15px;

    border-radius: 8px;

    margin-bottom: 20px;

    text-align: center;

}


/* =====================================================
   DETAILS
===================================================== */

.details {

    border:
        1px solid #e0e6ed;

    border-radius: 10px;

    overflow: hidden;

    margin-bottom: 25px;

}


.detail-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 15px 18px;

    border-bottom:
        1px solid #e5e5e5;

}


.detail-row:last-child {

    border-bottom: none;

}


.detail-label {

    color: #666;

    font-weight: 600;

}


.detail-value {

    text-align: right;

    font-weight: 600;

    color: #003366;

}


.amount-row {

    background: #f3f8fd;

}


.amount {

    color: #008000;

    font-size: 24px;

}


/* =====================================================
   PAYMENT METHODS
===================================================== */

.methods {

    margin-bottom: 25px;

}


.methods h3 {

    color: #003366;

    margin-bottom: 15px;

}


.method-list {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            1fr
        );

    gap: 15px;

}


.method {

    border:
        1px solid #dbe2ea;

    border-radius: 10px;

    padding: 18px;

    text-align: center;

    background: #fafcff;

}


.method-icon {

    font-size: 30px;

    margin-bottom: 8px;

}


.method strong {

    display: block;

    color: #003366;

}


.method span {

    display: block;

    color: #777;

    font-size: 13px;

    margin-top: 5px;

}


/* =====================================================
   PAY BUTTON
===================================================== */

.pay-button {

    display: block;

    width: 100%;

    border: none;

    background: #008000;

    color: white;

    padding: 16px;

    border-radius: 8px;

    font-size: 17px;

    font-weight: bold;

    cursor: pointer;

}


.pay-button:hover {

    background: #006b00;

}


.pay-button:disabled {

    background: #999;

    cursor: not-allowed;

}


/* =====================================================
   SECURITY
===================================================== */

.security {

    margin-top: 20px;

    padding: 15px;

    background: #f7f9fb;

    border-radius: 8px;

    color: #777;

    font-size: 13px;

    line-height: 1.6;

    text-align: center;

}


/* =====================================================
   BACK
===================================================== */

.back {

    display: inline-block;

    margin-top: 20px;

    color: #003366;

    text-decoration: none;

    font-weight: bold;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 20px;

    }


    .payment-card {

        padding: 25px;

    }


    .method-list {

        grid-template-columns: 1fr;

    }


    .detail-row {

        flex-direction: column;

        gap: 5px;

    }


    .detail-value {

        text-align: left;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


    <div class="logo">

        NISEL<br>

        ONLINE EDUCATION

    </div>


    <div class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="schedule.php">

            📅 My Schedule

        </a>


        <a
            href="payments.php"
            class="active"
        >

            💳 Payments

        </a>


        <a href="profile.php">

            👤 My Profile

        </a>


        <a href="logout.php">

            🚪 Logout

        </a>


    </div>


</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <div class="payment-card">


        <div class="payment-header">


            <h1>

                💳 Make Payment

            </h1>


            <p>

                NISEL ONLINE EDUCATION

            </p>


        </div>


        <?php if (
            !empty($error)
        ): ?>


            <div class="error">

                <?= h($error) ?>

            </div>


            <a
                href="dashboard.php"
                class="back"
            >

                ← Back to Dashboard

            </a>


        <?php elseif ($booking): ?>


            <!-- =================================================
                 BOOKING DETAILS
            ================================================== -->

            <div class="details">


                <div class="detail-row">


                    <div class="detail-label">

                        Student

                    </div>


                    <div class="detail-value">

                        <?= h(

                            $booking[
                                'student_name'
                            ]

                        ) ?>

                    </div>


                </div>


                <div class="detail-row">


                    <div class="detail-label">

                        Booking Reference

                    </div>


                    <div class="detail-value">

                        <?= h(

                            $booking[
                                'booking_reference'
                            ]

                        ) ?>

                    </div>


                </div>


                <div class="detail-row">


                    <div class="detail-label">

                        Subject

                    </div>


                    <div class="detail-value">

                        <?= h(

                            $booking[
                                'subjects'
                            ]

                        ) ?>

                    </div>


                </div>


                <div class="detail-row">


                    <div class="detail-label">

                        Curriculum

                    </div>


                    <div class="detail-value">

                        <?= h(

                            $booking[
                                'curriculum'
                            ]

                        ) ?>

                    </div>


                </div>


                <div class="detail-row">


                    <div class="detail-label">

                        Class / Year

                    </div>


                    <div class="detail-value">

                        <?= h(

                            $booking[
                                'class_year'
                            ]

                            ??

                            'N/A'

                        ) ?>

                    </div>


                </div>


                <div
                    class="detail-row amount-row"
                >


                    <div class="detail-label">

                        Amount to Pay

                    </div>


                    <div
                        class="detail-value amount"
                    >

                        GHS

                        <?= money(
                            $amount
                        ) ?>

                    </div>


                </div>


            </div>


            <!-- =================================================
                 PAYMENT METHODS
            ================================================== -->

            <div class="methods">


                <h3>

                    Choose your payment method

                </h3>


                <div class="method-list">


                    <div class="method">


                        <div class="method-icon">

                            💳

                        </div>


                        <strong>

                            Visa / Card

                        </strong>


                        <span>

                            Pay securely using your
                            bank card.

                        </span>


                    </div>


                    <div class="method">


                        <div class="method-icon">

                            📱

                        </div>


                        <strong>

                            Mobile Money

                        </strong>


                        <span>

                            Pay with supported Ghana
                            Mobile Money providers.

                        </span>


                    </div>


                </div>


            </div>


            <!-- =================================================
                 PAYMENT FORM
            ================================================== -->

            <form
                method="POST"
                id="paymentForm"
            >


                <input

                    type="hidden"

                    name="booking_reference"

                    value="<?= h(

                        $booking[
                            'booking_reference'
                        ]

                    ) ?>"

                >


                <button

                    type="submit"

                    name="start_payment"

                    class="pay-button"

                    id="payButton"

                >

                    🔒 Pay GHS

                    <?= money(
                        $amount
                    ) ?>

                </button>


            </form>


            <div class="security">

                🔒 Your payment is processed securely
                by Paystack.

                <br>

                NISEL ONLINE EDUCATION does not
                receive or store your card PIN.

            </div>


            <a
                href="dashboard.php"
                class="back"
            >

                ← Back to Dashboard

            </a>


        <?php endif; ?>


    </div>


</div>


<script>

/*
|--------------------------------------------------------------------------
| PREVENT DOUBLE SUBMISSION
|--------------------------------------------------------------------------
*/

const paymentForm =
    document.getElementById(
        "paymentForm"
    );


if (paymentForm) {

    paymentForm.addEventListener(
        "submit",
        function()
        {

            const button =
                document.getElementById(
                    "payButton"
                );


            if (button) {

                button.disabled =
                    true;


                button.innerHTML =
                    "Connecting to Paystack...";

            }

        }
    );

}

</script>


</body>

</html>
