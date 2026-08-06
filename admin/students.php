<?php

require "../config/db.php";


$result=$conn->query("SELECT * FROM students ORDER BY id DESC");


?>


<!DOCTYPE html>

<html>

<head>

<title>Manage Students | NISEL Admin</title>


<style>

body{
font-family:Arial;
background:#f2f5f8;
padding:30px;
}


.container{

background:white;
padding:30px;
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

padding:12px;
border-bottom:1px solid #ddd;

}


a{

text-decoration:none;
color:red;

}

</style>


</head>


<body>


<div class="container">


<h2>NISEL Students</h2>

<br>


<table>


<tr>

<th>ID</th>

<th>Name</th>

<th>Email</th>

<th>Phone</th>

<th>Curriculum</th>

<th>Class</th>

<th>Action</th>

</tr>



<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>
<?php echo $row['student_id']; ?>
</td>


<td>
<?php echo $row['student_name']; ?>
</td>


<td>
<?php echo $row['email']; ?>
</td>


<td>
<?php echo $row['phone']; ?>
</td>


<td>
<?php echo $row['curriculum']; ?>
</td>


<td>
<?php echo $row['class_year']; ?>
</td>


<td>

<a href="#">
Delete
</a>

</td>


</tr>


<?php } ?>


</table>


</div>


</body>

</html>
