<?php

require "../admin_auth.php";
require "../config/db.php";

$payments = $conn->query("
SELECT *
FROM payments
ORDER BY payment_date DESC
");

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Payment Records</title>

<style>

body{
    font-family:Arial;
    background:#eef3f8;
    padding:30px;
}

.container{
    background:white;
    padding:25px;
    border-radius:10px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#003366;
    color:white;
    padding:12px;
}

td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

.paid{
    color:green;
    font-weight:bold;
}

.pending{
    color:orange;
    font-weight:bold;
}

.failed{
    color:red;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="container">

<h2>Payment Records</h2>

<table>

<tr>

<th>Student</th>

<th>Amount</th>

<th>Method</th>

<th>Status</th>

<th>Date</th>

</tr>

<?php

if($payments->num_rows > 0){

while($p = $payments->fetch_assoc()){

?>

<tr>

<td><?php echo htmlspecialchars($p['student_name']); ?></td>

<td>GHC <?php echo number_format($p['amount'],2); ?></td>

<td><?php echo htmlspecialchars($p['payment_method']); ?></td>

<td>

<?php

$status = strtolower($p['status']);

if($status=="success" || $status=="paid"){

echo "<span class='paid'>Paid</span>";

}elseif($status=="pending"){

echo "<span class='pending'>Pending</span>";

}else{

echo "<span class='failed'>Failed</span>";

}

?>

</td>

<td><?php echo htmlspecialchars($p['payment_date']); ?></td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="5" style="text-align:center;">
No payment records found.
</td>

</tr>

<?php

}

?>

</table>

</div>

</body>

</html>

</table>
