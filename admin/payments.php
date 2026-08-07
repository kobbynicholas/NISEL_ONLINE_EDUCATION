<?php

require "../admin_auth.php";

require "../config/db.php";


$result=$conn->query("SELECT * FROM payments ORDER BY id DESC");


?>


<h2>Payment Records</h2>


<table border="1" width="100%">


$payments = $conn->query("
SELECT student_name,amount,payment_method,status
FROM payments
ORDER BY id DESC
LIMIT 10
");

while($p = $payments->fetch_assoc()){

?>

<tr>

<td><?php echo htmlspecialchars($p['student_name']); ?></td>

<td>GHC <?php echo number_format($p['amount'],2); ?></td>

<td><?php echo htmlspecialchars($p['payment_method']); ?></td>

<td><?php echo htmlspecialchars($p['status']); ?></td>

</tr>

<?php

}

<?php while($p=$result->fetch_assoc()){ ?>


<tr>

<td><?=$p['student_name']?></td>

<td>GHC <?=$p['amount']?></td>

<td><?=$p['payment_method']?></td>

<td><?=$p['status']?></td>

<td><?=$p['payment_date']?></td>


</tr>


<?php } ?>


</table>
