<?php

require "../admin_auth.php";

require "../config/db.php";


// ASSIGN TEACHER

if(isset($_POST['assign'])){


$booking_id=$_POST['booking_id'];

$teacher_id=$_POST['teacher_id'];


// GET TEACHER NAME

$getTeacher=$conn->prepare(
"SELECT teacher_name FROM teachers WHERE teacher_id=?"
);

$getTeacher->bind_param(
"s",
$teacher_id
);

$getTeacher->execute();

$result=$getTeacher->get_result();


$teacher=$result->fetch_assoc();


$teacher_name=$teacher['teacher_name'];



// UPDATE BOOKING


$sql=$conn->prepare("

UPDATE bookings SET

teacher_id=?,

teacher_name=?,

assignment_status='Assigned'

WHERE id=?

");


$sql->bind_param(

"ssi",

$teacher_id,

$teacher_name,

$booking_id

);


$sql->execute();


}



// GET TEACHERS

$teachers=$conn->query(

"SELECT teacher_id, teacher_name 
FROM teachers
WHERE status='Active'"

);



// GET BOOKINGS

$bookings=$conn->query("

SELECT *

FROM bookings

ORDER BY id DESC

");


?>



<!DOCTYPE html>

<html>

<head>

<title>Assign Teachers</title>


<style>


body{

font-family:Arial;

background:#eef3f8;

padding:30px;

}



.container{

background:white;

padding:25px;

border-radius:15px;

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

padding:12px;

border-bottom:1px solid #ddd;

}



select,button{

padding:8px;

}



button{

background:#003366;

color:white;

border:0;

border-radius:5px;

}



.status{

color:green;

font-weight:bold;

}


</style>


</head>


<body>


<div class="container">


<h2>
Student Lesson Bookings
</h2>


<br>


<table>


<tr>

<th>Student</th>

<th>Subject</th>

<th>Curriculum</th>

<th>Payment</th>

<th>Assigned Teacher</th>

<th>Action</th>

</tr>



<?php while($b=$bookings->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $b['student_name']; ?>

<br>

<?php echo $b['email']; ?>

</td>



<td>

<?php echo $b['subjects']; ?>

</td>



<td>

<?php echo $b['curriculum']; ?>

</td>



<td>

<?php echo $b['payment_status']; ?>

</td>



<td>


<?php

if($b['teacher_name']){

echo $b['teacher_name'];

}

else{

echo "Not Assigned";

}

?>


</td>



<td>


<form method="POST">


<input type="hidden"

name="booking_id"

value="<?php echo $b['id'];?>">


<select name="teacher_id">


<option>
Select Teacher
</option>


<?php


$teachers=$conn->query(

"SELECT teacher_id,teacher_name FROM teachers"

);


while($t=$teachers->fetch_assoc()){


?>


<option value="<?php echo $t['teacher_id'];?>">


<?php echo $t['teacher_name'];?>


</option>


<?php } ?>


</select>


<button name="assign">

Assign

</button>


</form>


</td>


</tr>


<?php } ?>


</table>


</div>


</body>


</html>
