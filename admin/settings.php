<?php

require "../admin_auth.php";

require "../config/db.php";



// UPDATE SETTINGS

if(isset($_POST['save'])){


$site=$_POST['site_name'];

$email=$_POST['email'];

$phone=$_POST['phone'];

$address=$_POST['address'];

$currency=$_POST['currency'];

$description=$_POST['description'];



$sql=$conn->prepare("

UPDATE settings SET

site_name=?,

email=?,

phone=?,

address=?,

currency=?,

description=?

WHERE id=1

");



$sql->bind_param(

"ssssss",

$site,

$email,

$phone,

$address,

$currency,

$description

);



$sql->execute();



$message="Settings updated successfully";

}



// GET SETTINGS

$result=$conn->query(

"SELECT * FROM settings WHERE id=1"

);


$data=$result->fetch_assoc();


?>


<!DOCTYPE html>

<html>

<head>

<title>NISEL Settings</title>


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

max-width:700px;

}



input,textarea{

width:100%;

padding:12px;

margin:10px 0;

}



button{

background:#003366;

color:white;

padding:12px 25px;

border:none;

}



.success{

color:green;

}


</style>

</head>


<body>


<div class="container">


<h2>
System Settings
</h2>


<?php

if(isset($message)){

echo "<p class='success'>$message</p>";

}

?>


<form method="POST">


<label>
Platform Name
</label>


<input

name="site_name"

value="<?php echo $data['site_name']; ?>">



<label>
Email
</label>


<input

name="email"

value="<?php echo $data['email']; ?>">



<label>
Phone
</label>


<input

name="phone"

value="<?php echo $data['phone']; ?>">



<label>
Address
</label>


<textarea name="address">

<?php echo $data['address']; ?>

</textarea>



<label>
Currency
</label>


<input

name="currency"

value="<?php echo $data['currency']; ?>">



<label>
Description
</label>


<textarea name="description">

<?php echo $data['description']; ?>

</textarea>



<button name="save">

Save Settings

</button>


</form>


</div>


</body>

</html>
