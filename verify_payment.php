<?php

session_start();

require "config/db.php";


/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| PAYSTACK PAYMENT VERIFICATION
|--------------------------------------------------------------------------
|
| This file verifies payments after Paystack redirects
| the student back to the website.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| PAYSTACK SECRET KEY
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Put your CURRENT Paystack secret key here.
|
| TEST:
| sk_test_...
|
| LIVE:
| sk_live_...
|
| DO NOT use pk_test_...
|
| DO NOT share your secret key publicly.
|
|--------------------------------------------------------------------------
*/

$secretKey = "YOUR_CURRENT_PAYSTACK_SECRET_KEY";


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
| GET PAYSTACK REFERENCE
|--------------------------------------------------------------------------
|
| Paystack normally returns:
|
| ?reference=XXXX
|
| Some integrations may also return:
|
| ?trxref=XXXX
|
|--------------------------------------------------------------------------
*/

$reference =
    trim(
        $_GET['reference']
        ??
        $_GET['trxref']
        ??
        ''
    );


/*
|--------------------------------------------------------------------------
| REFERENCE REQUIRED
|--------------------------------------------------------------------------
*/

if (
    $reference === ''
) {

    showError(
        "Payment Verification Failed",
        "Paystack reference was not received."
    );

}


/*
|--------------------------------------------------------------------------
| VERIFY PAYMENT WITH PAYSTACK
|--------------------------------------------------------------------------
*/

$url =
    "https://api.paystack.co/transaction/verify/"
    .
    urlencode(
        $reference
    );


$ch =
    curl_init(
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

        "Authorization: Bearer "
        .
        $secretKey,

        "Cache-Control: no-cache",

        "Content-Type: application/json"

    ]
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


$response =
    curl_exec(
        $ch
    );


$curlError =
    curl_error(
        $ch
    );


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $ch
);


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

    showError(
        "Payment Verification Failed",
        "Unable to connect to Paystack.",
        $curlError
    );

}


/*
|--------------------------------------------------------------------------
| DECODE RESPONSE
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

    showError(
        "Payment Verification Failed",
        "Paystack returned an invalid response.",
        $response
    );

}


/*
|--------------------------------------------------------------------------
| PAYSTACK API ERROR
|--------------------------------------------------------------------------
*/

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
        'Paystack could not verify this payment.';


    showError(
        "Payment Verification Failed",
        $paystackMessage,
        "HTTP Status: " . $httpCode
    );

}


/*
|--------------------------------------------------------------------------
| GET TRANSACTION DATA
|--------------------------------------------------------------------------
*/

$data =
    $result['data']
    ??
    [];


$transactionStatus =
    strtolower(
        trim(
            $data['status']
            ??
            ''
        )
    );


$paystackReference =
    $data['reference']
    ??
    $reference;


$amountPaid =
    (
        (float)
        (
            $data['amount']
            ??
            0
        )
    )
    /
    100;


$paymentChannel =
    $data['channel']
    ??
    'Paystack';


$paidCurrency =
    strtoupper(
        $data['currency']
        ??
        'GHS'
    );


/*
|--------------------------------------------------------------------------
| CHECK TRANSACTION STATUS
|--------------------------------------------------------------------------
*/

if (
    $transactionStatus
    !==
    'success'
) {

    showError(
        "Payment Verification Failed",

        "Paystack reports that this payment was not successful.",

        "Payment status: "
        .
        $transactionStatus
    );

}


/*
|--------------------------------------------------------------------------
| CHECK CURRENCY
|--------------------------------------------------------------------------
*/

if (
    $paidCurrency !== 'GHS'
) {

    showError(
        "Payment Verification Failed",

        "The payment currency is not GHS.",

        "Currency received: "
        .
        $paidCurrency
    );

}


/*
|--------------------------------------------------------------------------
| GET BOOKING REFERENCE
|--------------------------------------------------------------------------
|
| We normally use the Paystack reference to find the booking.
|
| We also check Paystack metadata when available.
|
|--------------------------------------------------------------------------
*/

$bookingReference =
    $reference;


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
            trim(
                $data['metadata']
                ['booking_reference']
            );

    }

}


/*
|--------------------------------------------------------------------------
| FIND BOOKING
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $pdo->prepare("

            SELECT

                *

            FROM bookings

            WHERE

                booking_reference = ?

            LIMIT 1

        ");


    $stmt->execute([

        $bookingReference

    ]);


    $booking =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (
    PDOException $e
) {

    showError(
        "Payment Verification Failed",

        "A database error occurred while finding your booking.",

        $e->getMessage()
    );

}


/*
|--------------------------------------------------------------------------
| BOOKING NOT FOUND
|--------------------------------------------------------------------------
*/

if (
    !$booking
) {

    /*
    |--------------------------------------------------------------------------
    | FALLBACK:
    | Try the exact Paystack reference.
    |--------------------------------------------------------------------------
    */

    try {

        $stmt =
            $pdo->prepare("

                SELECT *

                FROM bookings

                WHERE
                    paystack_reference = ?

                LIMIT 1

            ");


        $stmt->execute([

            $paystackReference

        ]);


        $booking =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


    } catch (
        PDOException $e
    ) {

        showError(
            "Payment Verification Failed",

            "Unable to find your booking.",

            $e->getMessage()
        );

    }

}


/*
|--------------------------------------------------------------------------
| STILL NOT FOUND
|--------------------------------------------------------------------------
*/

if (
    !$booking
) {

    showError(

        "Payment Verification Failed",

        "Paystack confirmed the payment, but the related booking could not be found.",

        "Reference: "
        .
        $paystackReference

    );

}


/*
|--------------------------------------------------------------------------
| GET BOOKING INFORMATION
|--------------------------------------------------------------------------
*/

$bookingId =
    (int)
    $booking['id'];


$studentId =
    (int)
    (
        $booking['student_id']
        ??
        0
    );


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
    trim(
        $booking['curriculum']
        ??
        ''
    );


$classYear =
    $booking['class_year']
    ??
    '';


$bookingAmount =
    (float)
    (
        $booking['amount']
        ??
        0
    );


$currentPaymentStatus =
    strtolower(
        trim(
            $booking['payment_status']
            ??
            ''
        )
    );


/*
|--------------------------------------------------------------------------
| DETERMINE EXPECTED PRICE
|--------------------------------------------------------------------------
*/

$expectedAmount = 0;


foreach (
    $prices
    as $program =>
    $programPrice
) {

    if (
        strtolower(
            $program
        )
        ===
        strtolower(
            $curriculum
        )
    ) {

        $expectedAmount =
            (float)
            $programPrice;

        break;

    }

}


/*
|--------------------------------------------------------------------------
| INVALID CURRICULUM
|--------------------------------------------------------------------------
*/

if (
    $expectedAmount <= 0
) {

    showError(

        "Payment Verification Failed",

        "We could not determine the correct price for this booking.",

        "Curriculum: "
        .
        $curriculum

    );

}


/*
|--------------------------------------------------------------------------
| CHECK PAYSTACK AMOUNT
|--------------------------------------------------------------------------
|
| Examples:
|
| Cambridge = 1000
| IB        = 1200
| GES       = 800
| SAT       = 850
|
|--------------------------------------------------------------------------
*/

if (
    abs(
        $amountPaid
        -
        $expectedAmount
    )
    >
    0.01
) {

    showError(

        "Payment Verification Failed",

        "The payment amount does not match the selected booking.",

        "Received: GHS "
        .
        number_format(
            $amountPaid,
            2
        )
        .
        " | Expected: GHS "
        .
        number_format(
            $expectedAmount,
            2
        )

    );

}


/*
|--------------------------------------------------------------------------
| OPTIONAL BOOKING AMOUNT CHECK
|--------------------------------------------------------------------------
|
| If an existing booking has an old/wrong amount,
| we update it to the correct curriculum amount.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TRANSACTION SAFETY
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING
    |--------------------------------------------------------------------------
    */

    $updateBooking =
        $pdo->prepare("

            UPDATE bookings

            SET

                amount = ?,

                payment_status = 'Paid',

                paystack_reference = ?

            WHERE

                id = ?

        ");


    $updateBooking->execute([

        $expectedAmount,

        $paystackReference,

        $bookingId

    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK WHETHER PAYMENT RECORD ALREADY EXISTS
    |--------------------------------------------------------------------------
    */

    $paymentCheck =
        $pdo->prepare("

            SELECT

                id

            FROM payments

            WHERE

                transaction_reference = ?

            LIMIT 1

        ");


    $paymentCheck->execute([

        $paystackReference

    ]);


    $existingPayment =
        $paymentCheck->fetch(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | CREATE PAYMENT RECORD
    |--------------------------------------------------------------------------
    */

    if (
        !$existingPayment
    ) {

        $insertPayment =
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


        $insertPayment->execute([

            $bookingReference,

            $studentName,

            $email,

            $expectedAmount,

            $paymentChannel,

            $paystackReference

        ]);

    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


} catch (
    PDOException $e
) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();

    }


    showError(

        "Payment Verification Failed",

        "The payment was received, but we could not update your booking.",

        $e->getMessage()

    );

}


/*
|--------------------------------------------------------------------------
| SAVE SESSION INFORMATION
|--------------------------------------------------------------------------
*/

$_SESSION[
    'payment_success'
] = true;


$_SESSION[
    'paid_booking_reference'
] =
    $bookingReference;


$_SESSION[
    'paid_booking_id'
] =
    $bookingId;


$_SESSION[
    'paid_amount'
] =
    $expectedAmount;


/*
|--------------------------------------------------------------------------
| SUCCESS REDIRECT
|--------------------------------------------------------------------------
|
| Try success.php first.
|
|--------------------------------------------------------------------------
*/

header(

    "Location: student/success.php?booking="
    .
    urlencode(
        $bookingReference
    )

);

exit;


/*
|--------------------------------------------------------------------------
| ERROR FUNCTION
|--------------------------------------------------------------------------
*/

function showError(
    $title,
    $message,
    $details = ''
) {

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
            Payment Verification |
            NISEL ONLINE EDUCATION
        </title>


        <style>

        * {

            box-sizing:
                border-box;

        }


        body {

            margin:
                0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #eef3f8;

            color:
                #333;

        }


        .container {

            width:
                92%;

            max-width:
                650px;

            margin:
                70px auto;

        }


        .card {

            background:
                white;

            padding:
                35px;

            border-radius:
                15px;

            box-shadow:
                0 6px 25px
                rgba(
                    0,
                    0,
                    0,
                    .10
                );

            text-align:
                center;

        }


        .icon {

            width:
                70px;

            height:
                70px;

            margin:
                0 auto 20px;

            border-radius:
                50%;

            background:
                #fdecec;

            color:
                #c62828;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            font-size:
                32px;

        }


        h1 {

            color:
                #003366;

            margin:
                0 0 15px;

            font-size:
                26px;

        }


        p {

            line-height:
                1.7;

            color:
                #666;

        }


        .details {

            margin-top:
                20px;

            padding:
                15px;

            background:
                #f7f9fc;

            border-radius:
                8px;

            text-align:
                left;

            font-size:
                13px;

            color:
                #666;

            word-break:
                break-word;

        }


        .button {

            display:
                inline-block;

            margin-top:
                25px;

            padding:
                13px 22px;

            background:
                #003366;

            color:
                white;

            text-decoration:
                none;

            border-radius:
                7px;

            font-weight:
                bold;

        }


        .button:hover {

            background:
                #0055a5;

        }

        </style>

    </head>


    <body>


        <div class="container">


            <div class="card">


                <div class="icon">

                    !

                </div>


                <h1>

                    <?= htmlspecialchars(
                        $title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </h1>


                <p>

                    <?= htmlspecialchars(
                        $message,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </p>


                <?php if (
                    $details !== ''
                ): ?>

                    <div class="details">

                        <?= htmlspecialchars(
                            $details,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <a
                    href="student/dashboard.php"
                    class="button"
                >

                    Return to Student Dashboard

                </a>


            </div>


        </div>


    </body>

    </html>

    <?php

    exit;

}

?>
