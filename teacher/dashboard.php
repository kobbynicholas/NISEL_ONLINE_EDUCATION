<?php

require "../teacher_auth.php";
require "../config/db.php";

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

/*
=================================
COUNT ASSIGNED STUDENTS
=================================
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE teacher_id = ?
    AND payment_status = 'Paid'
");

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$total_students =
    $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt->close();


/*
=================================
COUNT BOOKINGS
=================================
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE teacher_id = ?
");

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$total_bookings =
    $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$stmt->close();


/*
=================================
GET ASSIGNED STUDENTS
=================================
*/

$stmt = $conn->prepare("
    SELECT
        id,
        student_name,
        email,
        curriculum,
        class_year,
        subjects,
        payment_status,
        booking_reference
    FROM bookings
    WHERE teacher_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("s", $teacher_id);

$stmt->execute();

$students = $stmt->get_result();

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Teacher Dashboard | NISEL ONLINE EDUCATION
</title>

<style>

*{
    box-sizing:border-box;
}

body{

    margin:0;

    font-family:Arial,sans-serif;

    background:#eef3f8;

}

/* SIDEBAR */

.sidebar{

    position:fixed;

    left:0;

    top:0;

    width:240px;

    height:100vh;

    background:#003366;

    color:white;

    padding:25px 15px;

}

.logo{

    text-align:center;

    font-size:19px;

    font-weight:bold;

    margin-bottom:35px;

}

.menu a{

    display:block;

    color:white;

    text-decoration:none;

    padding:13px;

    margin-bottom:7px;

    border-radius:7px;

}

.menu a:hover{

    background:#0055a5;

}

/* MAIN */

.main{

    margin-left:240px;

    padding:30px;

}

.topbar{

    background:white;

    padding:20px;

    border-radius:10px;

    margin-bottom:25px;

    display:flex;

    justify-content:space-between;

    align-items:center;

}

.topbar h2{

    margin:0;

    color:#003366;

}

.teacher{

    color:#555;

}

/* CARDS */

.cards{

    display:grid;

    grid-template-columns:
    repeat(auto-fit,minmax(200px,1fr));

    gap:20px;

    margin-bottom:30px;

}

.card{

    background:white;

    padding:25px;

    border-radius:12px;

    box-shadow:
    0 4px 12px rgba(0,0,0,.08);

}

.card h3{

    margin:0;

    color:#666;

    font-size:16px;

}

.number{

    margin-top:12px;

    font-size:32px;

    font-weight:bold;

    color:#003366;

}

/* TABLE */

.table-box{

    background:white;

    padding:25px;

    border-radius:12px;

    overflow-x:auto;

}

.table-box h3{

    color:#003366;

}

table{

    width:100%;

    border-collapse:collapse;

}

th{

    background:#003366;

    color:white;

    padding:12px;

    text-align:left;

}

td{

    padding:11px;

    border-bottom:1px solid #ddd;

}

.badge{

    padding:5px 9px;

    border-radius:15px;

    font-size:12px;

}

.paid{

    background:#d4edda;

    color:#155724;

}

/* MOBILE */

@media(max-width:800px){

    .sidebar{

        position:relative;

        width:100%;

        height:auto;

    }

    .main{

        margin-left:0;

    }

}

</style>

</head>

<body>


<div class="sidebar">

<div class="logo">

NISEL<br>

ONLINE EDUCATION

</div>

<div class="menu">

<a href="dashboard.php">
🏠 Dashboard
</a>

<a href="students.php">
👨‍🎓 My Students
</a>

<a href="schedule.php">
📅 My Schedule
</a>

<a href="profile.php">
👤 My Profile
</a>

<a href="logout.php">
🚪 Logout
</a>

</div>

</div>


<div class="main">


<div class="topbar">

<h2>

Teacher Dashboard

</h2>

<div class="teacher">

Welcome,

<strong>

<?php echo htmlspecialchars($teacher_name); ?>

</strong>

</div>

</div>


<div class="cards">


<div class="card">

<h3>
Assigned Students
</h3>

<div class="number">

<?php echo $total_students; ?>

</div>

</div>


<div class="card">

<h3>
Total Bookings
</h3>

<div class="number">

<?php echo $total_bookings; ?>

</div>

</div>


<div class="card">

<h3>
Teaching Status
</h3>

<div class="number"
style="font-size:20px;">

Active

</div>

</div>


</div>


<div class="table-box">

<h3>

My Assigned Students

</h3>

<table>

<tr>

<th>Student</th>

<th>Curriculum</th>

<th>Class / Grade</th>

<th>Subjects</th>

<th>Payment</th>

<th>Booking Reference</th>

</tr>


<?php if($students->num_rows > 0): ?>

<?php while($student = $students->fetch_assoc()): ?>

<tr>

<td>

<?php
echo htmlspecialchars(
    $student['student_name']
);
?>

</td>

<td>

<?php
echo htmlspecialchars(
    $student['curriculum']
);
?>

</td>

<td>

<?php
echo htmlspecialchars(
    $student['class_year']
);
?>

</td>

<td>

<?php
echo htmlspecialchars(
    $student['subjects']
);
?>

</td>

<td>

<span class="badge paid">

<?php
echo htmlspecialchars(
    $student['payment_status']
);
?>

</span>

</td>

<td>

<?php
echo htmlspecialchars(
    $student['booking_reference']
);
?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6"
style="text-align:center;padding:30px;">

No students have been assigned to you yet.

</td>

</tr>

<?php endif; ?>

</table>

</div>


</div>

</body>

</html>
