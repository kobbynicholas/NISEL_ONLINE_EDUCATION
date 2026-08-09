<?php

require "../admin_auth.php";
require "../config/db.php";


/* =============================
   SEARCH & FILTER
============================= */

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$method = $_GET['method'] ?? '';

$sql = "SELECT * FROM payments WHERE 1=1";

$params = [];


if ($search != "") {

    $sql .= " AND (student_name LIKE ? OR email LIKE ?)";

    $searchLike = "%$search%";

    $params[] = $searchLike;
    $params[] = $searchLike;

}


if ($status != "") {

    $sql .= " AND status=?";

    $params[] = $status;

}


if ($method != "") {

    $sql .= " AND payment_method=?";

    $params[] = $method;

}


$sql .= " ORDER BY payment_date DESC";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);


$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =============================
   DASHBOARD CARDS
============================= */


// TOTAL REVENUE

$revenueQuery = $pdo->query("

    SELECT SUM(amount) AS total

    FROM payments

    WHERE status='success'

");

$totalRevenue = $revenueQuery->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


// TOTAL PAYMENTS

$totalPaymentsQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM payments

");

$totalPayments = $totalPaymentsQuery->fetch(PDO::FETCH_ASSOC)['total'];


// TOTAL PAID

$totalPaidQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM payments

    WHERE status='success'

");

$totalPaid = $totalPaidQuery->fetch(PDO::FETCH_ASSOC)['total'];


// TOTAL PENDING

$totalPendingQuery = $pdo->query("

    SELECT COUNT(*) AS total

    FROM payments

    WHERE status='pending'

");

$totalPending = $totalPendingQuery->fetch(PDO::FETCH_ASSOC)['total'];


?>


<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>NISEL Payment Records</title>


<style>

body{

    margin:0;
    background:#eef3f8;
    font-family:Arial;

}

.container{

    width:95%;
    margin:25px auto;

}

.cards{

    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:25px;

}

.card{

    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 10px rgba(0,0,0,.1);

}

.card h3{

    color:#003366;
    margin:0;

}

.card h1{

    margin-top:10px;

}

.filter-box{

    background:white;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;

}

input,select{

    padding:10px;
    margin:5px;

}

button{

    padding:10px 20px;
    background:#003366;
    color:white;
    border:none;
    cursor:pointer;

}

button:hover{

    background:#0055aa;

}

table{

    width:100%;
    border-collapse:collapse;
    background:white;

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

.success{

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

.top-buttons{

    margin-bottom:20px;

}

</style>


</head>


<body>


<div class="container">


<h2>Payment Management</h2>


<div class="cards">


<div class="card">

<h3>Total Revenue</h3>

<h1>

GHC <?php echo number_format($totalRevenue,2); ?>

</h1>

</div>


<div class="card">

<h3>Total Transactions</h3>

<h1>

<?php echo $totalPayments; ?>

</h1>

</div>


<div class="card">

<h3>Paid</h3>

<h1>

<?php echo $totalPaid; ?>

</h1>

</div>


<div class="card">

<h3>Pending</h3>

<h1>

<?php echo $totalPending; ?>

</h1>

</div>


</div>


<div class="filter-box">


<form method="GET">


<input

type="text"

name="search"

placeholder="Search Student"

value="<?php echo htmlspecialchars($search); ?>">


<select name="status">


<option value="">All Status</option>

<option value="success"
<?php echo ($status == 'success') ? 'selected' : ''; ?>>
Paid
</option>

<option value="pending"
<?php echo ($status == 'pending') ? 'selected' : ''; ?>>
Pending
</option>

<option value="failed"
<?php echo ($status == 'failed') ? 'selected' : ''; ?>>
Failed
</option>


</select>


<select name="method">


<option value="">All Methods</option>

<option value="mobile_money"
<?php echo ($method == 'mobile_money') ? 'selected' : ''; ?>>
MTN MoMo
</option>

<option value="Visa"
<?php echo ($method == 'Visa') ? 'selected' : ''; ?>>
Visa Card
</option>


</select>


<button type="submit">

Search

</button>


</form>


</div>


<div class="top-buttons">


<button onclick="window.print()">

🖨 Print

</button>


</div>


<table>


<tr>

<th>Reference</th>

<th>Student</th>

<th>Email</th>

<th>Amount</th>

<th>Method</th>

<th>Status</th>

<th>Date</th>

</tr>


<?php if (count($payments) > 0) { ?>


<?php foreach ($payments as $p) { ?>


<tr>


<td>

<?php echo htmlspecialchars($p['transaction_reference']); ?>

</td>


<td>

<?php echo htmlspecialchars($p['student_name']); ?>

</td>


<td>

<?php echo htmlspecialchars($p['email']); ?>

</td>


<td>

GHC <?php echo number_format($p['amount'],2); ?>

</td>


<td>

<?php echo htmlspecialchars($p['payment_method']); ?>

</td>


<td>


<?php

$paymentStatus = strtolower($p['status']);


if ($paymentStatus == "success") {

    echo "<span class='success'>Paid</span>";

} elseif ($paymentStatus == "pending") {

    echo "<span class='pending'>Pending</span>";

} else {

    echo "<span class='failed'>Failed</span>";

}

?>


</td>


<td>

<?php echo htmlspecialchars($p['payment_date']); ?>

</td>


</tr>


<?php } ?>


<?php } else { ?>


<tr>


<td colspan="7" align="center">

No payment records found.

</td>


</tr>


<?php } ?>


</table>


</div>


</body>


</html>
