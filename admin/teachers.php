<?php

require "../admin_auth.php";

require "../config/db.php";


// ASSIGN TEACHER

if (isset($_POST['assign'])) {

    $booking_id = $_POST['booking_id'];
    $teacher_id = $_POST['teacher_id'];


    // GET TEACHER NAME

    $getTeacher = $pdo->prepare(
        "SELECT teacher_name
         FROM teachers
         WHERE teacher_id = ?"
    );

    $getTeacher->execute([
        $teacher_id
    ]);

    $teacher = $getTeacher->fetch(PDO::FETCH_ASSOC);


    if ($teacher) {

        $teacher_name = $teacher['teacher_name'];


        // UPDATE BOOKING

        $sql = $pdo->prepare("

            UPDATE bookings SET

            teacher_id = ?,

            teacher_name = ?,

            assignment_status = 'Assigned'

            WHERE id = ?

        ");

        $sql->execute([

            $teacher_id,
            $teacher_name,
            $booking_id

        ]);

    }

}



// GET TEACHERS

$teacherQuery = $pdo->query("

    SELECT teacher_id, teacher_name

    FROM teachers

    WHERE status = 'Active'

    ORDER BY teacher_name ASC

");

$teachers = $teacherQuery->fetchAll(PDO::FETCH_ASSOC);



// GET BOOKINGS

$bookingQuery = $pdo->query("

    SELECT *

    FROM bookings

    ORDER BY id DESC

");

$bookings = $bookingQuery->fetchAll(PDO::FETCH_ASSOC);


?>


<!DOCTYPE html>

<html>

<head>

<title>Assign Teachers</title>


<style>

body{

font-family:Arial;

background:#eef3f8;

padding:30px;

}

.container{

background:white;

padding:25px;

border-radius:15px;

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

select,button{

padding:8px;

}

button{

background:#003366;

color:white;

border:0;

border-radius:5px;

cursor:pointer;

}

button:hover{

background:#00509e;

}

.status{

color:green;

font-weight:bold;

}

</style>

</head>


<body>


<div class="container">


<h2>
Student Lesson Bookings
</h2>


<br>


<table>


<tr>

<th>Student</th>

<th>Subject</th>

<th>Curriculum</th>

<th>Payment</th>

<th>Assigned Teacher</th>

<th>Action</th>

</tr>


<?php foreach ($bookings as $b) { ?>


<tr>


<td>

<?php echo htmlspecialchars($b['student_name']); ?>

<br>

<?php echo htmlspecialchars($b['email']); ?>

</td>


<td>

<?php echo htmlspecialchars($b['subjects']); ?>

</td>


<td>

<?php echo htmlspecialchars($b['curriculum']); ?>

</td>


<td>

<?php echo htmlspecialchars($b['payment_status']); ?>

</td>


<td>

<?php

if (!empty($b['teacher_name'])) {

    echo htmlspecialchars($b['teacher_name']);

} else {

    echo "Not Assigned";

}

?>

</td>


<td>


<form method="POST">


<input type="hidden"

name="booking_id"

value="<?php echo htmlspecialchars($b['id']); ?>">


<select name="teacher_id" required>


<option value="">

Select Teacher

</option>


<?php foreach ($teachers as $t) { ?>


<option value="<?php echo htmlspecialchars($t['teacher_id']); ?>">

<?php echo htmlspecialchars($t['teacher_name']); ?>

</option>


<?php } ?>


</select>


<button type="submit" name="assign">

Assign

</button>


</form>


</td>


</tr>


<?php } ?>


</table>


</div>


</body>


</html>
