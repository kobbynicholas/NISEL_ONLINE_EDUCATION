<?php

require "../admin_auth.php";

require "../config/db.php";


$result=$conn->query("SELECT * FROM payments ORDER BY id DESC");


?>


<table border="1" width="100%">


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
