<?php

require "../admin_auth.php";

require "../config/db.php";


$result=$pdo->query("SELECT * FROM students ORDER BY id DESC");


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


<?php while ($row = $result->fetch(PDO::FETCH_ASSOC)) { ?>

<tr>

    <td>
        <?php echo htmlspecialchars($row['student_id']); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['student_name']); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['email']); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['phone']); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['curriculum']); ?>
    </td>

    <td>
        <?php echo htmlspecialchars($row['class_year']); ?>
    </td>

    <td>
        <a href="delete_student.php?id=<?php echo urlencode($row['student_id']); ?>"
           onclick="return confirm('Are you sure you want to delete this student?');">
            Delete
        </a>
    </td>

</tr>

<?php } ?>


</table>


</div>


</body>

</html>
