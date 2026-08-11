<?php

require "_login.php";
require "../config/db.php";


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

try {

    /* TOTAL STUDENTS */

    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM students
    ");

    $total_students = (int) $stmt->fetchColumn();


    /* TOTAL TEACHERS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM teachers
    ");

    $total_teachers = (int) $stmt->fetchColumn();


    /* TOTAL BOOKINGS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
    ");

    $total_bookings = (int) $stmt->fetchColumn();


    /* TOTAL PAYMENTS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM payments
    ");

    $total_payments = (int) $stmt->fetchColumn();


    /* PENDING TEACHER APPLICATIONS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM teacher_applications
        WHERE application_status = 'Pending'
    ");

    $pending_applications =
        (int) $stmt->fetchColumn();


    /* PAID BOOKINGS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE payment_status = 'Paid'
        OR payment_status = 'success'
    ");

    $paid_bookings =
        (int) $stmt->fetchColumn();


    /* ASSIGNED BOOKINGS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE teacher_id IS NOT NULL
        AND teacher_id <> ''
    ");

    $assigned_bookings =
        (int) $stmt->fetchColumn();


    /* UNASSIGNED BOOKINGS */

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM bookings
        WHERE teacher_id IS NULL
        OR teacher_id = ''
    ");

    $unassigned_bookings =
        (int) $stmt->fetchColumn();


    /* =====================================================
       RECENT BOOKINGS
    ===================================================== */

    $stmt = $pdo->query("
        SELECT
            id,
            student_name,
            email,
            subjects,
            curriculum,
            payment_status,
            teacher_name
        FROM bookings
        ORDER BY id DESC
        LIMIT 10
    ");

    $recent_bookings =
        $stmt->fetchAll();


    /* =====================================================
       RECENT TEACHER APPLICATIONS
    ===================================================== */

    $stmt = $pdo->query("
        SELECT
            id,
            full_name,
            email,
            subjects,
            curricula,
            application_status
        FROM teacher_applications
        ORDER BY id DESC
        LIMIT 10
    ");

    $recent_applications =
        $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Dashboard database error: "
        . $e->getMessage()
    );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
NISEL ONLINE EDUCATION | Admin Dashboard
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

}


/* =====================================================
   SIDEBAR
===================================================== */

.sidebar {

    position: fixed;

    left: 0;

    top: 0;

    width: 240px;

    height: 100vh;

    background: #003366;

    color: white;

    padding: 25px 15px;

}


.sidebar h2 {

    text-align: center;

    font-size: 20px;

    margin-bottom: 30px;

}


.sidebar a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 6px;

}


.sidebar a:hover {

    background: #0055aa;

}


.sidebar .logout {

    background: #dc3545;

    margin-top: 25px;

}


.sidebar .logout:hover {

    background: #bb2d3b;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


.header {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 10px rgba(0,0,0,.08);

}


.header h1 {

    margin: 0;

    color: #003366;

}


.header p {

    color: #666;

}


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(200px, 1fr));

    gap: 20px;

    margin-bottom: 30px;

}


.card {

    background: white;

    padding: 22px;

    border-radius: 10px;

    box-shadow:
        0 3px 12px rgba(0,0,0,.08);

}


.card h3 {

    margin: 0;

    color: #666;

    font-size: 15px;

}


.card .number {

    font-size: 32px;

    font-weight: bold;

    color: #003366;

    margin-top: 10px;

}


/* =====================================================
   QUICK LINKS
===================================================== */

.quick-links {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

    margin-bottom: 30px;

}


.quick-links a {

    background: #003366;

    color: white;

    text-decoration: none;

    padding: 15px;

    text-align: center;

    border-radius: 7px;

}


.quick-links a:hover {

    background: #0055aa;

}


/* =====================================================
   TABLE
===================================================== */

.section {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 10px rgba(0,0,0,.08);

}


.section h2 {

    color: #003366;

    margin-top: 0;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #003366;

    color: white;

    padding: 11px;

    text-align: left;

}


td {

    padding: 10px;

    border-bottom: 1px solid #ddd;

}


/* =====================================================
   STATUS
===================================================== */

.badge {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 15px;

    font-size: 12px;

    font-weight: bold;

}


.paid {

    background: #d4edda;

    color: #155724;

}


.pending {

    background: #fff3cd;

    color: #856404;

}


.assigned {

    background: #d1ecf1;

    color: #0c5460;

}


.unassigned {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }

    .main {

        margin-left: 0;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

<h2>
NISEL ONLINE EDUCATION
</h2>


<a href="dashboard.php">
🏠 Dashboard
</a>


<a href="students.php">
👨‍🎓 Students
</a>


<a href="teachers.php">
👨‍🏫 Teachers
</a>


<a href="teacher_applications.php">
📋 Teacher Applications
</a>


<a href="bookings.php">
📚 Bookings
</a>


<a href="payments.php">
💳 Payments
</a>


<a href="reports.php">
📊 Reports
</a>

<a href="schedules.php">
📅 Schedules
</a>
   

<a href="settings.php">
⚙ Settings
</a>


<a
    href="logout.php"
    class="logout"
>
🚪 Logout
</a>

</div>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main">


<div class="header">

<h1>
Admin Dashboard
</h1>

<p>
Welcome to the NISEL ONLINE EDUCATION
Administration Panel.
</p>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="stats">


<div class="card">

<h3>
Total Students
</h3>

<div class="number">
<?php echo $total_students; ?>
</div>

</div>


<div class="card">

<h3>
Total Teachers
</h3>

<div class="number">
<?php echo $total_teachers; ?>
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
Payments
</h3>

<div class="number">
<?php echo $total_payments; ?>
</div>

</div>


<div class="card">

<h3>
Pending Teacher Applications
</h3>

<div class="number">
<?php echo $pending_applications; ?>
</div>

</div>


<div class="card">

<h3>
Paid Bookings
</h3>

<div class="number">
<?php echo $paid_bookings; ?>
</div>

</div>


<div class="card">

<h3>
Assigned Bookings
</h3>

<div class="number">
<?php echo $assigned_bookings; ?>
</div>

</div>


<div class="card">

<h3>
Unassigned Bookings
</h3>

<div class="number">
<?php echo $unassigned_bookings; ?>
</div>

</div>

</div>


<!-- =====================================================
     QUICK LINKS
===================================================== -->

<div class="quick-links">

<a href="students.php">
Manage Students
</a>

<a href="teachers.php">
Manage Teachers
</a>

<a href="teacher_applications.php">
Teacher Applications
</a>

<a href="bookings.php">
Assign Teachers
</a>

<a href="payments.php">
Payment Records
</a>

<a href="reports.php">
View Reports
</a>

</div>


<!-- =====================================================
     RECENT BOOKINGS
===================================================== -->

<div class="section">

<h2>
Recent Student Bookings
</h2>


<div class="table-wrapper">

<table>

<tr>

<th>Student</th>

<th>Email</th>

<th>Subjects</th>

<th>Curriculum</th>

<th>Payment</th>

<th>Teacher</th>

</tr>


<?php if (count($recent_bookings) > 0): ?>


<?php foreach ($recent_bookings as $booking): ?>


<tr>

<td>
<?php

echo htmlspecialchars(
    $booking['student_name']
);

?>
</td>


<td>
<?php

echo htmlspecialchars(
    $booking['email']
);

?>
</td>


<td>
<?php

echo htmlspecialchars(
    $booking['subjects']
);

?>
</td>


<td>
<?php

echo htmlspecialchars(
    $booking['curriculum']
);

?>
</td>


<td>

<?php

$payment =
    strtolower(
        trim(
            $booking['payment_status']
            ?? ''
        )
    );

if (
    $payment === 'paid'
    ||
    $payment === 'success'
) {

    echo
    '<span class="badge paid">Paid</span>';

} else {

    echo
    '<span class="badge pending">Pending</span>';

}

?>

</td>


<td>

<?php

if (
    empty(
        $booking['teacher_name']
    )
) {

    echo
    '<span class="badge unassigned">
    Not Assigned
    </span>';

} else {

    echo htmlspecialchars(
        $booking['teacher_name']
    );

}

?>

</td>

</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="6"
    style="text-align:center;"
>

No bookings found.

</td>

</tr>


<?php endif; ?>


</table>

</div>

</div>


<!-- =====================================================
     RECENT TEACHER APPLICATIONS
===================================================== -->

<div class="section">

<h2>
Recent Teacher Applications
</h2>


<div class="table-wrapper">

<table>

<tr>

<th>Name</th>

<th>Email</th>

<th>Subjects</th>

<th>Curriculum</th>

<th>Status</th>

</tr>


<?php if (count($recent_applications) > 0): ?>


<?php foreach (
    $recent_applications
    as $application
): ?>


<tr>

<td>

<?php

echo htmlspecialchars(
    $application['full_name']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['email']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['subjects']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['curricula']
);

?>

</td>


<td>

<?php

$status =
    $application['application_status']
    ?? 'Pending';


$statusLower =
    strtolower($status);


if ($statusLower === 'approved') {

    echo
    '<span class="badge paid">
    Approved
    </span>';

} elseif (
    $statusLower === 'rejected'
) {

    echo
    '<span class="badge unassigned">
    Rejected
    </span>';

} else {

    echo
    '<span class="badge pending">
    Pending
    </span>';

}

?>

</td>

</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="5"
    style="text-align:center;"
>

No teacher applications found.

</td>

</tr>


<?php endif; ?>


</table>

</div>

</div>


</div>


</body>

</html>
