<?php

require "../admin_auth.php";

require "../config/db.php";



// UPDATE SETTINGS

if (isset($_POST['save'])) {


    $site = $_POST['site_name'];

    $email = $_POST['email'];

    $phone = $_POST['phone'];

    $address = $_POST['address'];

    $currency = $_POST['currency'];

    $description = $_POST['description'];



    $sql = $pdo->prepare("

        UPDATE settings SET

        site_name = ?,

        email = ?,

        phone = ?,

        address = ?,

        currency = ?,

        description = ?

        WHERE id = 1

    ");



    $sql->execute([

        $site,

        $email,

        $phone,

        $address,

        $currency,

        $description

    ]);



    $message = "Settings updated successfully";

}



// GET SETTINGS

$result = $pdo->query("

    SELECT *

    FROM settings

    WHERE id = 1

");



$data = $result->fetch(PDO::FETCH_ASSOC);


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

cursor:pointer;

}



button:hover{

background:#0055aa;

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

if (isset($message)) {

    echo "<p class='success'>" . htmlspecialchars($message) . "</p>";

}

?>


<form method="POST">


<label>

Platform Name

</label>


<input

type="text"

name="site_name"

value="<?php echo htmlspecialchars($data['site_name'] ?? ''); ?>">



<label>

Email

</label>


<input

type="email"

name="email"

value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">



<label>

Phone

</label>


<input

type="text"

name="phone"

value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>">



<label>

Address

</label>


<textarea name="address"><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>



<label>

Currency

</label>


<input

type="text"

name="currency"

value="<?php echo htmlspecialchars($data['currency'] ?? ''); ?>">



<label>

Description

</label>


<textarea name="description"><?php echo htmlspecialchars($data['description'] ?? ''); ?></textarea>



<button type="submit" name="save">

Save Settings

</button>


</form>


</div>


</body>

</html>
