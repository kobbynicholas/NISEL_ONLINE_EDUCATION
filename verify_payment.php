<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/config/db.php";

require __DIR__ . "/PHPMailer/src/Exception.php";
require __DIR__ . "/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/PHPMailer/src/SMTP.php";


// =============================
// DATABASE CONNECTION
// =============================

$host = "localhost";
$user = "root";
$password = "";
$database = "nisel_online_education";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed.");
}

// =============================
// PAYSTACK SECRET KEY
// =============================

$secretKey = "sk_test_90ec51eccfbefe07902468f713bba1ba663d7a28";


// =============================
// GET PAYSTACK REFERENCE
// =============================

if (!isset($_GET['reference'])) {
    die("Payment reference not found.");
}

$reference = $_GET['reference'];


// =============================
// VERIFY PAYMENT WITH PAYSTACK
// =============================

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
    die("Unable to contact Paystack.");
}

curl_close($ch);

$result = json_decode($response, true);


// =============================
// CHECK PAYMENT STATUS
// =============================

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



    // =============================
    // UPDATE BOOKING
    // =============================

    $stmt = $conn->prepare(

        "UPDATE bookings
         SET payment_status='Paid',
             paystack_reference=?,
             amount=?
         WHERE booking_reference=?"

    );

    $stmt->bind_param(
        "sds",
        $paystackReference,
        $amountPaid,
        $bookingReference
    );

    $stmt->execute();

    $stmt->close();



    // =============================
    // GET STUDENT DETAILS
    // =============================

    $stmt = $conn->prepare(

        "SELECT * FROM bookings
         WHERE booking_reference=?"

    );

    $stmt->bind_param("s", $bookingReference);

    $stmt->execute();

    $student = $stmt->get_result()->fetch_assoc();

    $stmt->close();



    // =============================
    // SEND EMAIL
    // =============================

require __DIR__ . "/PHPMailer/src/Exception.php";
require __DIR__ . "/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/PHPMailer/src/SMTP.php";

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;

    $mail->Username   = 'kobbynicholas.kn@gmail.com';
    $mail->Password   = 'Kwabenawusu.1';

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('kobbynicholas.kn@gmail.com', 'NISEL ONLINE EDUCATION');

    $mail->addAddress($email, $name);

    $mail->isHTML(true);

    $mail->Subject = 'Welcome to NISEL ONLINE EDUCATION';

    $mail->Body = '
    <h2>Welcome to NISEL ONLINE EDUCATION</h2>

    <p>Dear <strong>'.$name.'</strong>,</p>

    <p>
    Welcome to <strong>NISEL ONLINE EDUCATION</strong>.
    </p>

    <p>
    You can now book for a lesson.
    </p>

    <p>
    A personal tutor will be assigned to you after booking.
    </p>

    <p>
    Enjoy a quality online education at NISEL.
    </p>

    <br>

    <p>
    <strong>Thank You.</strong>
    </p>

    <hr>

    <p>
    NISEL ONLINE EDUCATION<br>
    Empowering Learners Worldwide
    </p>

    ';

    $mail->send();

}
catch (Exception $e) {

}
    //
    // =============================


    ?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Payment Successful</title>

<style>

body{

font-family:Arial;
background:#eef7ee;
text-align:center;
padding:60px;

}

.box{

background:white;
padding:40px;
max-width:700px;
margin:auto;
border-radius:10px;
box-shadow:0 5px 20px rgba(0,0,0,.15);

}

h1{

color:green;

}

table{

width:100%;
margin-top:25px;
border-collapse:collapse;

}

td{

padding:12px;
border-bottom:1px solid #ddd;
text-align:left;

}

.button{

display:inline-block;
margin-top:30px;
padding:15px 30px;
background:#003366;
color:white;
text-decoration:none;
border-radius:5px;

}

</style>

</head>

<body>

<div class="box">

<h1>Payment Successful</h1>

<p>
Thank you for booking with
<strong>NISEL ONLINE EDUCATION</strong>.
</p>

<table>

<tr>

<td><strong>Booking Reference</strong></td>

<td><?php echo htmlspecialchars($bookingReference); ?></td>

</tr>

<tr>

<td><strong>Student</strong></td>

<td><?php echo htmlspecialchars($student['student_name']); ?></td>

</tr>

<tr>

<td><strong>Email</strong></td>

<td><?php echo htmlspecialchars($student['email']); ?></td>

</tr>

<tr>

<td><strong>Subjects</strong></td>

<td><?php echo htmlspecialchars($student['subjects']); ?></td>

</tr>

<tr>

<td><strong>Curriculum</strong></td>

<td><?php echo htmlspecialchars($student['curriculum']); ?></td>

</tr>

<tr>

<td><strong>Amount Paid</strong></td>

<td>GHS <?php echo number_format($amountPaid,2); ?></td>

</tr>

<tr>

<td><strong>Payment Method</strong></td>

<td><?php echo htmlspecialchars($paymentMethod); ?></td>

</tr>

<tr>

<td><strong>Status</strong></td>

<td><span style="color:green;font-weight:bold;">PAID</span></td>

</tr>

</table>

<a href="index.html" class="button">

Return to Home

</a>

</div>

</body>

</html>

<?php

} else {

?>

<!DOCTYPE html>

<html>

<head>

<title>Payment Failed</title>

<style>

body{

font-family:Arial;
background:#fff4f4;
text-align:center;
padding:60px;

}

.box{

background:white;
padding:40px;
max-width:600px;
margin:auto;
border-radius:10px;
box-shadow:0 5px 20px rgba(0,0,0,.15);

}

h1{

color:red;

}

</style>

</head>

<body>

<div class="box">

<h1>Payment Failed</h1>

<p>

Unfortunately your payment could not be verified.

</p>

<p>

Please try again.

</p>

</div>

</body>

</html>

<?php

}

$conn->close();

header("Location: success.php?booking=".$bookingReference);
exit();

?>
