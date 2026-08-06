<?php

require "../config/db.php";


$result=$conn->query("SELECT * FROM payments ORDER BY id DESC");


?>


<h2>Payment Records</h2>


<table border="1" width="100%">


<tr>

<th>Student</th>

<th>Amount</th>

<th>Method</th>

<th>Status</th>

<th>Date</th>

</tr>


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
