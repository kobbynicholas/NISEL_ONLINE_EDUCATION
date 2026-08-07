<?php
require "../admin_auth.php";
require "../config/db.php";

/* ============================
   ASSIGN TEACHER
============================ */

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign'])) {

    $booking_id = intval($_POST['booking_id']);
    $teacher_id = trim($_POST['teacher_id']);

    // Get teacher details
    $teacher = $conn->prepare("SELECT teacher_name FROM teachers WHERE teacher_id=?");

if (!$teacher) {
    die("Teacher query error: " . $conn->error);
}
    $teacher->bind_param("s", $teacher_id);
    $teacher->execute();

    $teacherResult = $teacher->get_result();

    if ($teacherResult->num_rows > 0) {

        $teacherRow = $teacherResult->fetch_assoc();

        $teacher_name = $teacherRow['teacher_name'];

        $update = $conn->prepare("
            UPDATE bookings
            SET
                teacher_id=?,
                teacher_name=?,
                assignment_status='Assigned'
            WHERE id=?
        ");

        $update->bind_param(
            "ssi",
            $teacher_id,
            $teacher_name,
            $booking_id
        );

        if ($update->execute()) {

            $message = "Teacher assigned successfully.";

        } else {

            $message = "Assignment failed.";

        }

    }

}

/* ============================
   GET BOOKINGS
============================ */

$bookings = $conn->query("
SELECT *
FROM bookings
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Booking Management</title>

<style>

body{

    margin:0;
    background:#eef3f8;
    font-family:Arial;

}

.container{

    width:95%;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);

}

h2{

    color:#003366;

}

.success{

    background:#d4edda;
    color:#155724;
    padding:12px;
    margin-bottom:20px;
    border-radius:6px;

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

    padding:10px;
    border-bottom:1px solid #ddd;

}

select{

    padding:8px;
    width:180px;

}

button{

    padding:9px 18px;
    background:#003366;
    color:white;
    border:none;
    border-radius:5px;
    cursor:pointer;

}

button:hover{

    background:#0055aa;

}

.badge{

    padding:5px 10px;
    border-radius:20px;
    color:white;
    font-size:13px;

}

.paid{

    background:green;

}

.pending{

    background:orange;

}

</style>

</head>

<body>

<div class="container">

<h2>Student Lesson Bookings</h2>

<?php if($message!=""){ ?>

<div class="success">

<?php echo $message; ?>

</div>

<?php } ?>

<table>

<tr>

<th>Student</th>

<th>Email</th>

<th>Curriculum</th>

<th>Class</th>

<th>Subjects</th>

<th>Payment</th>

<th>Assigned Teacher</th>

<th>Assign Teacher</th>

</tr>

<?php while($row = $bookings->fetch_assoc()){ ?>

<tr>

<td><?php echo htmlspecialchars($row['student_name']); ?></td>

<td><?php echo htmlspecialchars($row['email']); ?></td>

<td><?php echo htmlspecialchars($row['curriculum']); ?></td>

<td><?php echo htmlspecialchars($row['class_year']); ?></td>

<td><?php echo htmlspecialchars($row['subjects']); ?></td>

<td>

<?php

if($row['payment_status']=="success" || $row['payment_status']=="Paid"){

    echo "<span class='badge paid'>Paid</span>";

}else{

    echo "<span class='badge pending'>Pending</span>";

}

?>

</td>

<td>

<?php

if(empty($row['teacher_name'])){

    echo "Not Assigned";

}else{

    echo htmlspecialchars($row['teacher_name']);

}

?>

</td>

<td>

<form method="POST">

<input
type="hidden"
name="booking_id"
value="<?php echo $row['id']; ?>">

<select name="teacher_id" required>

<option value="">Select Teacher</option>

<?php

$teachers = $conn->query("
SELECT teacher_id, teacher_name
FROM teachers
WHERE status='Active'
ORDER BY teacher_name
");

while($teacher = $teachers->fetch_assoc()){

?>

<option value="<?php echo $teacher['teacher_id']; ?>">

<?php echo htmlspecialchars($teacher['teacher_name']); ?>

</option>

<?php } ?>

</select>

<br><br>

<button
type="submit"
name="assign">

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
