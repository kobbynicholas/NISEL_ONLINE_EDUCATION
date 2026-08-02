<?php

$host="localhost";
$user="root";
$password="";
$database="nisel_online_education";

$conn=new mysqli($host,$user,$password,$database);

if($conn->connect_error){
die("Connection Failed");
}

$message="";

if(isset($_POST['register'])){

$name=trim($_POST['fullname']);
$dob=$_POST['dob'];
$institution=trim($_POST['institution']);
$phone=trim($_POST['phone']);
$email=trim($_POST['email']);
$class=trim($_POST['class_year']);

$stmt=$conn->prepare("INSERT INTO students(fullname,dob,institution,phone,email,class_year)
VALUES(?,?,?,?,?,?)");

$stmt->bind_param("ssssss",$name,$dob,$institution,$phone,$email,$class);

if($stmt->execute()){

$message="<div class='success'>
Student Registered Successfully.
</div>";

}else{

$message="<div class='error'>
".$stmt->error."
</div>";

}

$stmt->close();

}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>NISEL ONLINE EDUCATION</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

*{

margin:0;
padding:0;
box-sizing:border-box;
font-family:Arial, Helvetica, sans-serif;

}

body{

background:#edf3fa;

}

.container{

width:450px;

background:white;

margin:40px auto;

padding:30px;

border-radius:12px;

box-shadow:0 5px 20px rgba(0,0,0,.15);

}

h1{

text-align:center;

color:#003366;

margin-bottom:10px;

}

p{

text-align:center;

margin-bottom:30px;

color:#666;

}

label{

font-weight:bold;

display:block;

margin-top:15px;

}

input,select{

width:100%;

padding:12px;

margin-top:5px;

border:1px solid #ccc;

border-radius:5px;

font-size:15px;

}

button{

width:100%;

padding:15px;

background:#003366;

color:white;

border:none;

margin-top:25px;

font-size:18px;

cursor:pointer;

border-radius:5px;

}

button:hover{

background:#0059b3;

}

.success{

background:#d4edda;

color:green;

padding:15px;

margin-bottom:20px;

border-radius:5px;

}

.error{

background:#f8d7da;

color:red;

padding:15px;

margin-bottom:20px;

border-radius:5px;

}

</style>

</head>

<body>

<div class="container">

<h1>NISEL ONLINE EDUCATION</h1>

<p>Student Registration Form</p>

<?php echo $message; ?>

<form method="POST">

<label>Full Name</label>

<input type="text" name="fullname" required>

<label>Date of Birth</label>

<input type="date" name="dob" required>

<label>Institution / School</label>

<input type="text" name="institution" required>

<label>Phone Number</label>

<input type="tel" name="phone" required>

<label>Email Address</label>

<input type="email" name="email" required>

<label>Class / Year / Grade</label>

<select name="class_year" required>

<option value="">Select</option>

<option>Cambridge Lower Primary</option>

<option>Cambridge Lower Secondary</option>

<option>Cambridge Upper Secondary</option>

<option>Cambridge AS Level</option>

<option>Cambridge A Level</option>

<option>IB Grade 3</option>

<option>IB Grade 4</option>

<option>IB Grade 5</option>

<option>IB Grade 6</option>

<option>IB Grade 7</option>

<option>IB Grade 8</option>

<option>IB Grade 9</option>

<option>IB Grade 10</option>

<option>IB Grade 11</option>

<option>IB Grade 12</option>

<option>GES Class 4</option>

<option>GES Class 5</option>

<option>GES Class 6</option>

<option>GES JHS 1</option>

<option>GES JHS 2</option>

<option>GES JHS 3</option>

<option>GES SHS 1</option>

<option>GES SHS 2</option>

<option>GES SHS 3</option>

</select>

<button name="register">

Register Student

</button>

</form>

</div>

</body>

</html>
