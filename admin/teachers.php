<?php

require "../admin/auth.php";

require "../config/db.php";


if(isset($_POST['save'])){


$teacher_id="TCH".rand(1000,9999);


$name=$_POST['name'];

$email=$_POST['email'];

$phone=$_POST['phone'];

$qualification=$_POST['qualification'];

$subjects=$_POST['subjects'];

$curriculum=$_POST['curriculum'];

$experience=$_POST['experience'];



$sql="INSERT INTO teachers

(
teacher_id,
teacher_name,
phone,
email,
qualification,
subjects,
curriculum,
experience
)

VALUES

(
?,?,?,?,?,?,?,?
)

";


$stmt=$conn->prepare($sql);


$stmt->bind_param(

"ssssssss",

$teacher_id,
$name,
$phone,
$email,
$qualification,
$subjects,
$curriculum,
$experience

);


$stmt->execute();


}


$teachers=$conn->query("SELECT * FROM teachers");


?>


<!DOCTYPE html>

<html>

<head>

<title>Teachers</title>


<style>

body{
font-family:Arial;
padding:30px;
background:#eef3f8;
}


input{

padding:10px;
width:300px;
margin:5px;

}


button{

background:#003366;
color:white;
padding:12px;
border:0;

}


table{

width:100%;
margin-top:30px;
background:white;

}


td,th{

padding:12px;

}


th{

background:#003366;
color:white;

}

</style>


</head>


<body>


<h2>Register Teacher</h2>


<form method="POST">


<input name="name" placeholder="Teacher Name">

<input name="email" placeholder="Email">

<input name="phone" placeholder="Phone">


<input name="qualification" placeholder="Qualification">


<input name="subjects" placeholder="Subjects">


<input name="curriculum" placeholder="Curriculum">


<input name="experience" placeholder="Experience">


<button name="save">

Save Teacher

</button>


</form>



<table>


<tr>

<th>Name</th>
<th>Email</th>
<th>Subjects</th>

</tr>


<?php while($t=$teachers->fetch_assoc()){ ?>

<tr>

<td>
<?=$t['teacher_name']?>
</td>

<td>
<?=$t['email']?>
</td>

<td>
<?=$t['subjects']?>
</td>

</tr>

<?php } ?>


</table>


</body>

</html>
