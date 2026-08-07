<?php

require "../admin_auth.php";

require "../config/db.php";


// ADD SUBJECT

if(isset($_POST['add'])){


$subject_name=$_POST['subject_name'];

$curriculum=$_POST['curriculum'];

$level=$_POST['level'];



$stmt=$conn->prepare("

INSERT INTO subjects

(subject_name,curriculum,level)

VALUES(?,?,?)

");


$stmt->bind_param(

"sss",

$subject_name,

$curriculum,

$level

);


$stmt->execute();


}


// DELETE SUBJECT

if(isset($_GET['delete'])){


$id=$_GET['delete'];


$conn->query(

"DELETE FROM subjects WHERE id='$id'"

);


header("Location: subjects.php");

exit();

}




// GET SUBJECTS

$result=$conn->query(

"SELECT * FROM subjects ORDER BY id DESC"

);


?>


<!DOCTYPE html>

<html>

<head>

<title>NISEL Subjects Management</title>


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



input,select{

padding:12px;

width:250px;

margin:5px;

}



button{

padding:12px 20px;

background:#003366;

color:white;

border:none;

cursor:pointer;

}



table{

width:100%;

margin-top:30px;

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



.delete{

color:red;

text-decoration:none;

}



</style>


</head>


<body>


<div class="container">


<h2>
NISEL Subject Management
</h2>


<br>


<form method="POST">


<input 

type="text"

name="subject_name"

placeholder="Subject Name"

required>



<select name="curriculum" required>


<option value="">

Select Curriculum

</option>


<option>
Cambridge
</option>


<option>
IB
</option>


<option>
GES
</option>


</select>



<input

type="text"

name="level"

placeholder="Level/Class"

required>



<button name="add">

Add Subject

</button>


</form>





<h3>

Available Subjects

</h3>


<table>


<tr>

<th>Subject</th>

<th>Curriculum</th>

<th>Level</th>

<th>Action</th>

</tr>



<?php while($row=$result->fetch_assoc()){ ?>


<tr>


<td>

<?php echo $row['subject_name']; ?>

</td>


<td>

<?php echo $row['curriculum']; ?>

</td>


<td>

<?php echo $row['level']; ?>

</td>


<td>


<a class="delete"

href="subjects.php?delete=<?php echo $row['id']; ?>"

onclick="return confirm('Delete this subject?')">

Delete

</a>


</td>


</tr>


<?php } ?>


</table>



</div>


</body>

</html>
