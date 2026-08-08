<?php

require "../admin_auth.php";
require "../config/db.php";


/* =========================================================
   GET TEACHER APPLICATIONS
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM teacher_applications
        ORDER BY id DESC
    ");

    $stmt->execute();

    $applications = $stmt->fetchAll();

} catch (PDOException $e) {

    die(
        "Unable to load teacher applications: "
        . $e->getMessage()
    );

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Teacher Applications | NISEL ONLINE EDUCATION</title>

<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #eef3f8;
}

.container {
    width: 95%;
    margin: 30px auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,.1);
}

h2 {
    color: #003366;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #003366;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 13px;
}

.pending {
    background: #fff3cd;
    color: #856404;
}

.approved {
    background: #d4edda;
    color: #155724;
}

.rejected {
    background: #f8d7da;
    color: #721c24;
}

.view {
    display: inline-block;
    padding: 7px 12px;
    background: #003366;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.view:hover {
    background: #0055aa;
}

</style>

</head>

<body>

<div class="container">

<h2>Teacher Applications</h2>

<table>

<tr>

<th>ID</th>

<th>Application Reference</th>

<th>Name</th>

<th>Email</th>

<th>Phone</th>

<th>Subjects</th>

<th>Curriculum</th>

<th>Status</th>

<th>Action</th>

</tr>


<?php if (count($applications) > 0): ?>

<?php foreach ($applications as $application): ?>

<tr>

<td>
<?php echo htmlspecialchars($application['id']); ?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['application_reference'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['full_name'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['email'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['phone'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['subjects'] ?? ''
);
?>
</td>

<td>
<?php
echo htmlspecialchars(
    $application['curricula'] ?? ''
);
?>
</td>

<td>

<?php

$status =
    $application['application_status']
    ?? 'Pending';

$statusClass =
    strtolower($status);

?>

<span class="status <?php echo $statusClass; ?>">

<?php
echo htmlspecialchars($status);
?>

</span>

</td>

<td>

<a
    class="view"
    href="teacher_application_view.php?id=<?php echo $application['id']; ?>"
>
    View
</a>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="9" style="text-align:center;">

No teacher applications found.

</td>

</tr>

<?php endif; ?>

</table>

</div>

</body>

</html>
