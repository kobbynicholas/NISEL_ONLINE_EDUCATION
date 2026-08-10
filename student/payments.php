<?php

session_start();

require "../config/db.php";


/* =========================================================
   CHECK STUDENT LOGIN
========================================================= */

if (
    !isset($_SESSION['student_logged_in']) ||
    $_SESSION['student_logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}


$student_id = $_SESSION['student_id'];

$student_name =
    $_SESSION['student_name'] ?? "Student";

$student_email =
    $_SESSION['student_email'] ?? "";


/* =========================================================
   GET PAYMENT RECORDS
========================================================= */

try {

    /*
     * Your payment records are linked to the student's
     * email in the existing project.
     */

    $stmt = $pdo->prepare("
        SELECT *
        FROM payments
        WHERE student_name IN (
            SELECT student_name
            FROM students
            WHERE id = ?
        )
        OR email = ?
        ORDER BY id DESC
    ");

    $stmt->execute([
        $student_id,
        $student_email
    ]);

    $payments = $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load payment records: "
        . $e->getMessage()
    );

}


/* =========================================================
   CALCULATE TOTAL PAID
========================================================= */

$total_paid = 0;

foreach ($payments as $payment) {

    $status = strtolower(
        trim(
            $payment['status'] ?? ''
        )
    );

    if (
        $status === 'paid' ||
        $status === 'success' ||
        $status === 'successful'
    ) {

        $total_paid +=
            (float) (
                $payment['amount'] ?? 0
            );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Payments | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

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

    margin-bottom: 30px;

}


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 6px;

}


.sidebar a:hover {

    background: #0055aa;

}


.sidebar a.active {

    background: #0055aa;

}


.logout {

    background: #dc3545;

    margin-top: 25px;

}


.logout:hover {

    background: #bb2d3b !important;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


.header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.header p {

    margin: 0;

    color: #666;

}


/* =====================================================
   SUMMARY CARD
===================================================== */

.summary {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(220px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.card {

    background: white;

    padding: 22px;

    border-radius: 10px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.card-title {

    color: #666;

    font-size: 14px;

    margin-bottom: 8px;

}


.card-value {

    font-size: 28px;

    font-weight: bold;

    color: #003366;

}


/* =====================================================
   PAYMENT TABLE
===================================================== */

.payment-container {

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.08);

}


.payment-container h2 {

    margin-top: 0;

    color: #003366;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 900px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

}


tr:hover {

    background: #f7f9fc;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.paid {

    background: #d4edda;

    color: #155724;

}


.pending {

    background: #fff3cd;

    color: #856404;

}


.failed {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   REFERENCE
===================================================== */

.reference {

    font-family: monospace;

    color: #003366;

    font-weight: bold;

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty {

    text-align: center;

    padding: 50px 20px;

    color: #777;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 10px;

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

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">


<div class="logo">

NISEL ONLINE EDUCATION

</div>


<a href="dashboard.php">

🏠 Dashboard

</a>


<a href="profile.php">

👤 My Profile

</a>


<a href="bookings.php">

📚 My Bookings

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


<a
    href="logout.php"
    class="logout"
>

🚪 Logout

</a>


</div>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


<div class="header">

<h1>
Payment History
</h1>

<p>

View your NISEL ONLINE EDUCATION
payment records.

</p>

</div>


<!-- =====================================================
     SUMMARY
===================================================== -->

<div class="summary">


<div class="card">

<div class="card-title">

Total Payments

</div>

<div class="card-value">

<?php

echo count($payments);

?>

</div>

</div>


<div class="card">

<div class="card-title">

Total Amount Paid

</div>

<div class="card-value">

GHC

<?php

echo number_format(
    $total_paid,
    2
);

?>

</div>

</div>


</div>


<!-- =====================================================
     PAYMENT RECORDS
===================================================== -->

<div class="payment-container">


<h2>
My Payment Records
</h2>


<?php if (count($payments) > 0): ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>
Student
</th>

<th>
Amount
</th>

<th>
Payment Method
</th>

<th>
Status
</th>

<th>
Payment Date
</th>

<th>
Reference
</th>

</tr>

</thead>


<tbody>


<?php foreach ($payments as $payment): ?>


<tr>


<!-- STUDENT -->

<td>

<?php

echo htmlspecialchars(
    $payment['student_name'] ?? ''
);

?>

</td>


<!-- AMOUNT -->

<td>

GHC

<?php

echo number_format(
    (float) (
        $payment['amount'] ?? 0
    ),
    2
);

?>

</td>


<!-- PAYMENT METHOD -->

<td>

<?php

echo htmlspecialchars(
    $payment['payment_method']
    ?? 'Paystack'
);

?>

</td>


<!-- STATUS -->

<td>

<?php

$status = strtolower(
    trim(
        $payment['status'] ?? ''
    )
);


if (
    $status === 'paid' ||
    $status === 'success' ||
    $status === 'successful'
) {

    echo '
        <span class="badge paid">
            PAID
        </span>
    ';

} elseif (
    $status === 'pending'
) {

    echo '
        <span class="badge pending">
            PENDING
        </span>
    ';

} else {

    echo '
        <span class="badge failed">
            ' .
            htmlspecialchars(
                strtoupper(
                    $payment['status']
                    ?? 'UNKNOWN'
                )
            )
            . '
        </span>
    ';

}

?>

</td>


<!-- DATE -->

<td>

<?php

$payment_date =
    $payment['payment_date']
    ?? $payment['created_at']
    ?? '';


if ($payment_date !== '') {

    $timestamp =
        strtotime($payment_date);

    if ($timestamp !== false) {

        echo htmlspecialchars(
            date(
                "d M Y, h:i A",
                $timestamp
            )
        );

    } else {

        echo htmlspecialchars(
            $payment_date
        );

    }

} else {

    echo "N/A";

}

?>

</td>


<!-- REFERENCE -->

<td>

<?php

$reference =
    $payment['paystack_reference']
    ?? $payment['reference']
    ?? 'N/A';


echo '<span class="reference">'
    . htmlspecialchars($reference)
    . '</span>';

?>

</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


<?php else: ?>


<div class="empty">


<div class="empty-icon">
💳
</div>


<h3>
No Payment Records
</h3>


<p>
You currently have no payment records.
</p>


</div>


<?php endif; ?>


</div>


</div>


</body>

</html>
