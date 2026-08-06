<?php

require "../admin_auth.php";

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

$bio=$_POST['bio'];

$availability=$_POST['availability'];



// IMAGE UPLOAD

$photo="";


if(isset($_FILES['photo'])){


$file=$_FILES['photo'];


$filename=time()."_".$file['name'];


$location="../uploads/teachers/".$filename;



if(move_uploaded_file(
$file['tmp_name'],
$location
)){


$photo=$filename;


}


}




$sql="INSERT INTO teachers

(
teacher_id,
teacher_name,
photo,
phone,
email,
qualification,
subjects,
curriculum,
experience,
bio,
availability
)

VALUES
(?,?,?,?,?,?,?,?,?,?,?)

";



$stmt=$conn->prepare($sql);



$stmt->bind_param(

"sssssssssss",

$teacher_id,

$name,

$photo,

$phone,

$email,

$qualification,

$subjects,

$curriculum,

$experience,

$bio,

$availability

);



$stmt->execute();


}




$teachers=$conn->query(

"SELECT * FROM teachers ORDER BY id DESC"

);


?>



<!DOCTYPE html>

<html>

<head>

<title>NISEL Teachers</title>


<style>

body{

font-family:Arial;

background:#eef3f8;

padding:30px;

}


.container{

background:white;

padding:30px;

border-radius:15px;

}



input,textarea{

width:100%;

padding:12px;

margin:8px 0;

}



button{

background:#003366;

color:white;

padding:12px 20px;

border:0;

}



table{

width:100%;

margin-top:30px;

border-collapse:collapse;

}



th{

background:#003366;

color:white;

}



td,th{

padding:12px;

border:1px solid #ddd;

}



.teacher-img{

width:70px;

height:70px;

border-radius:50%;

object-fit:cover;

}


</style>


</head>


<body>


<div class="container">


<h2>
Register Teacher
</h2>


<form method="POST" enctype="multipart/form-data">


<input name="name" placeholder="Teacher Name" required>


<input name="email" placeholder="Email" required>


<input name="phone" placeholder="Phone Number">


<input name="qualification" placeholder="Qualification">


<input name="subjects" placeholder="Subjects">


<input name="curriculum" placeholder="Curriculum (Cambridge/IB/GES)">



<input name="experience" placeholder="Teaching Experience">



<textarea name="bio"
placeholder="Teacher Profile Description"></textarea>



<input name="availability"
placeholder="Available Days/Times">



<label>
Teacher Photo
</label>


<input type="file" name="photo" required>



<button name="save">

Register Teacher

</button>



</form>





<h2>
Registered Teachers
</h2>


<table>


<tr>

<th>Photo</th>

<th>Name</th>

<th>Subjects</th>

<th>Curriculum</th>

<th>Email</th>

</tr>


<?php while($t=$teachers->fetch_assoc()){ ?>


<tr>


<td>


<img class="teacher-img"

src="../uploads/teachers/<?php echo $t['photo'];?>">


</td>


<td>

<?php echo $t['teacher_name'];?>

</td>


<td>

<?php echo $t['subjects'];?>

</td>


<td>

<?php echo $t['curriculum'];?>

</td>


<td>

<?php echo $t['email'];?>

</td>


</tr>


<?php } ?>


</table>


</div>


</body>

</html>
