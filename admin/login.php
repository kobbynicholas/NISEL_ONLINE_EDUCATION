<?php

session_start();

require "config/db.php";


if(isset($_POST['login'])){


$email=$_POST['email'];

$password=$_POST['password'];



$stmt=$conn->prepare(

"SELECT * FROM admins WHERE email=?"

);


$stmt->bind_param(
"s",
$email
);


$stmt->execute();


$result=$stmt->get_result();


if($result->num_rows==1){


$admin=$result->fetch_assoc();



if(password_verify(
$password,
$admin['password']
)){


$_SESSION['admin_id']=$admin['id'];

$_SESSION['admin_name']=$admin['username'];



header(
"Location: admin/dashboard.php"
);

exit();


}

else{

$error="Incorrect password";

}


}

else{

$error="Admin account not found";

}


}

?>


<!DOCTYPE html>

<html>

<head>

<title>NISEL Admin Login</title>


<style>

body{

font-family:Arial;
background:#eef3f8;

}


.login{

width:400px;
background:white;
padding:30px;
margin:100px auto;
border-radius:15px;

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


.error{

color:red;

}

</style>


</head>


<body>


<div class="login">


<h2>
NISEL ADMIN LOGIN
</h2>


<?php

if(isset($error)){

echo "<p class='error'>$error</p>";

}

?>


<form method="POST">


<input 
type="email"
name="email"
placeholder="Admin Email"
required>



<input
type="password"
name="password"
placeholder="Password"
required>



<button name="login">

Login

</button>


</form>


</div>


</body>

</html>
