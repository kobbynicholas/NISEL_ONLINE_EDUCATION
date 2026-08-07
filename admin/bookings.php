<?php

require "../admin_auth.php";

require "../config/db.php";


if(isset($_POST['assign'])){


$id=$_POST['id'];

$tutor=$_POST['tutor'];



$conn->query("

UPDATE bookings

SET tutor_name='$tutor'

WHERE id='$id'

");


}


$bookings=$conn->query("SELECT * FROM bookings");


?>


<h2>Student Bookings</h2>


<table border="1" width="100%">



<?php while($b=$bookings->fetch_assoc()){ ?>


<tr>

<td><?php echo htmlspecialchars($b['student_name']); ?></td>

<td><?php echo htmlspecialchars($b['subjects']); ?></td>

<td><?php echo htmlspecialchars($b['curriculum']); ?></td>

<td><?php echo htmlspecialchars($b['payment_status']); ?></td>

</tr>


<form method="POST">


<input type="hidden" name="id"
value="<?=$b['id']?>">


<input name="tutor"
placeholder="Tutor name">


<button name="assign">

Assign

</button>


</form>


</td>


</tr>


<?php } ?>


</table>
