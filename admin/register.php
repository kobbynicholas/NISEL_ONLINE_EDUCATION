<?php

require "config/db.php";


if(isset($_POST['register'])){


$username=$_POST['username'];

$email=$_POST['email'];

$password=password_hash(
$_POST['password'],
PASSWORD_DEFAULT
);



$sql="INSERT INTO admins
(username,email,password)
VALUES(?,?,?)";


$stmt=$conn->prepare($sql);


$stmt->bind_param(
"sss",
$username,
$email,
$password
);


if($stmt->execute()){

echo "Admin account created successfully";

}
else{

echo "Error creating account";

}


}


?>


<!DOCTYPE html>

<html>

<head>

<title>Create Admin Account</title>


<style>

body{

font-family:Arial;
background:#eef3f8;

}


.box{

width:400px;
background:white;
padding:30px;
margin:80px auto;
border-radius:10px;

}


input{

width:100%;
padding:12px;
margin:10px 0;

}


button{

width:100%;
padding:12px;
background:#003366;
color:white;
border:0;

}


</style>


</head>


<body>


<div class="box">


<h2>NISEL Admin Registration</h2>


<form method="POST">


<input 
type="text"
name="username"
placeholder="Username"
required>


<input
type="email"
name="email"
placeholder="Email"
required>


<input
type="password"
name="password"
placeholder="Password"
required>


<button name="register">

Create Admin

</button>


</form>


</div>


</body>

</html>
