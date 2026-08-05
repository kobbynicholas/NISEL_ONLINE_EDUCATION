<?php

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

$secretKey = "YOUR_PAYSTACK_SECRET_KEY";


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
    //
    // Add your PHPMailer code here.
    //
    // Send to:
    // $student['email']
    //
    // Subject:
    // Welcome to NISEL ONLINE EDUCATION
    //
    // Body:
    // Welcome to NISEL ONLINE EDUCATION.
    // You can now book for a lesson.
    // A personal tutor will be assigned to you.
    // Enjoy a quality online education.
    // Thank You.
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

?>
