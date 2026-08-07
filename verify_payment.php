<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/config/db.php";

require_once __DIR__ . "/PHPMailer/src/Exception.php";
require_once __DIR__ . "/PHPMailer/src/PHPMailer.php";
require_once __DIR__ . "/PHPMailer/src/SMTP.php";

/*
==========================================
PAYSTACK SECRET KEY
==========================================
*/

$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";

/*
==========================================
CHECK PAYMENT REFERENCE
==========================================
*/

if (!isset($_GET['reference'])) {
    die("Payment reference not found.");
}

$reference = $_GET['reference'];

/*
==========================================
VERIFY PAYMENT WITH PAYSTACK
==========================================
*/

$url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $secretKey,
    "Cache-Control: no-cache"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Unable to connect to Paystack.");
}

curl_close($ch);

$result = json_decode($response, true);

/*
==========================================
CHECK PAYMENT STATUS
==========================================
*/

if (

    isset($result['status']) &&
    $result['status'] === true &&
    isset($result['data']['status']) &&
    $result['data']['status'] === "success"

) {

    $bookingReference = $result['data']['metadata']['booking_reference'];

    $paystackReference = $result['data']['reference'];

    $paymentMethod = $result['data']['channel'];

    $amountPaid = $result['data']['amount'] / 100;

    /*
    ==========================================
    UPDATE BOOKINGS TABLE
    ==========================================
    */

    $stmt = $conn->prepare("
        UPDATE bookings
        SET
            payment_status='Paid',
            paystack_reference=?,
            amount=?
        WHERE booking_reference=?
    ");

    $stmt->bind_param(
        "sds",
        $paystackReference,
        $amountPaid,
        $bookingReference
    );

    $stmt->execute();

    $stmt->close();

    /*
    ==========================================
    GET STUDENT DETAILS
    ==========================================
    */

    $stmt = $conn->prepare("
        SELECT *
        FROM bookings
        WHERE booking_reference=?
    ");

    $stmt->bind_param("s", $bookingReference);

    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$student) {
        die("Booking record not found.");
    }

    /*
    ==========================================
    PREVENT DUPLICATE PAYMENT RECORDS
    ==========================================
    */

    $check = $conn->prepare("
        SELECT id
        FROM payments
        WHERE transaction_reference=?
    ");

    $check->bind_param("s", $paystackReference);

    $check->execute();

    $exists = $check->get_result();

    if ($exists->num_rows == 0) {

        /*
        ==========================================
        INSERT PAYMENT RECORD
        ==========================================
        */

        $status = "success";

        $insert = $conn->prepare("
            INSERT INTO payments
            (
                booking_reference,
                student_name,
                email,
                amount,
                payment_method,
                transaction_reference,
                status
            )
            VALUES
            (?,?,?,?,?,?,?)
        ");

        $insert->bind_param(

            "ssdssss",

            $bookingReference,

            $student['student_name'],

            $student['email'],

            $amountPaid,

            $paymentMethod,

            $paystackReference,

            $status

        );

        $insert->execute();

        $insert->close();

    }

    $check->close();

    /*
    ==========================================
    STUDENT DETAILS
    ==========================================
    */

    $name = $student['student_name'];

    $email = $student['email'];

    $subjects = $student['subjects'];

    $curriculum = $student['curriculum'];




    /*
    ==========================================
    SEND CONFIRMATION EMAIL
    ==========================================
    */

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();

        $mail->Host = 'smtp.gmail.com';

        $mail->SMTPAuth = true;

        $mail->Username = 'kobbynicholas.kn@gmail.com';

        $mail->Password = '';

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = 587;

        $mail->setFrom(
            'kobbynicholas.kn@gmail.com',
            'NISEL ONLINE EDUCATION'
        );

        $mail->addAddress($email, $name);

        $mail->isHTML(true);

        $mail->Subject = "Payment Successful - NISEL ONLINE EDUCATION";

        $mail->Body = "

        <h2>Welcome to NISEL ONLINE EDUCATION</h2>

        <p>Dear <strong>$name</strong>,</p>

        <p>

        Thank you for your successful payment.

        </p>

        <p>

        Your lesson booking has been confirmed.

        </p>

        <table
        border='1'
        cellpadding='8'
        cellspacing='0'
        style='border-collapse:collapse;'>

        <tr>

        <td><strong>Booking Reference</strong></td>

        <td>$bookingReference</td>

        </tr>

        <tr>

        <td><strong>Subjects</strong></td>

        <td>$subjects</td>

        </tr>

        <tr>

        <td><strong>Curriculum</strong></td>

        <td>$curriculum</td>

        </tr>

        <tr>

        <td><strong>Amount Paid</strong></td>

        <td>GHS " . number_format($amountPaid,2) . "</td>

        </tr>

        </table>

        <br>

        <p>

        A qualified tutor will be assigned to you shortly.

        </p>

        <p>

        Thank you for choosing

        <strong>NISEL ONLINE EDUCATION.</strong>

        </p>

        ";

        $mail->send();

    }

    catch(Exception $e){

        // Ignore email errors

    }

    $conn->close();

    header("Location: success.php?booking=" . urlencode($bookingReference));

    exit();

}

/*
==========================================
PAYMENT FAILED
==========================================
*/

$conn->close();

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Payment Failed</title>

<style>

body{

font-family:Arial;

background:#f4f4f4;

display:flex;

justify-content:center;

align-items:center;

height:100vh;

margin:0;

}

.card{

background:white;

padding:40px;

border-radius:10px;

box-shadow:0 5px 15px rgba(0,0,0,.2);

text-align:center;

max-width:500px;

}

h1{

color:#d9534f;

}

a{

display:inline-block;

margin-top:25px;

padding:12px 25px;

background:#003366;

color:white;

text-decoration:none;

border-radius:5px;

}

</style>

</head>

<body>

<div class="card">

<h1>Payment Verification Failed</h1>

<p>

Unfortunately we could not verify your payment.

</p>

<p>

If your account has already been debited,

please contact NISEL ONLINE EDUCATION.

</p>

<a href="payment.php">

Try Again

</a>

</div>

</body>

</html>

