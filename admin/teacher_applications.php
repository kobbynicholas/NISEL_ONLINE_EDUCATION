<?php

require "../admin_auth.php";
require "../config/db.php";

$message = "";
$message_type = "";


/* =========================================================
   APPROVE / DENY APPLICATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['action'])
    &&
    isset($_POST['application_id'])
) {

    $application_id = (int) $_POST['application_id'];
    $action = $_POST['action'];


    /* =====================================================
       APPROVE
    ===================================================== */

    if ($action === "approve") {

        /*
         * Send the administrator to the detailed approval
         * page where the teacher account will be created.
         */

        header(
            "Location: teacher_application_view.php?id="
            . $application_id
        );

        exit;
    }


    /* =====================================================
       DENY
    ===================================================== */

    if ($action === "deny") {

        try {

            $stmt = $pdo->prepare("
                UPDATE teacher_applications
                SET application_status = 'Rejected'
                WHERE id = ?
            ");

            $stmt->execute([
                $application_id
            ]);

            $message =
                "Teacher application denied successfully.";

            $message_type = "success";

        } catch (PDOException $e) {

            $message =
                "Unable to deny application: "
                . $e->getMessage();

            $message_type = "error";
        }
    }


    /* =====================================================
       SET PENDING
    ===================================================== */

    if ($action === "pending") {

        try {

            $stmt = $pdo->prepare("
                UPDATE teacher_applications
                SET application_status = 'Pending'
                WHERE id = ?
            ");

            $stmt->execute([
                $application_id
            ]);

            $message =
                "Application returned to Pending.";

            $message_type = "success";

        } catch (PDOException $e) {

            $message =
                "Unable to change application status: "
                . $e->getMessage();

            $message_type = "error";
        }
    }

}


/* =========================================================
   GET APPLICATIONS
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

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Teacher Applications |
NISEL ONLINE EDUCATION
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


.container {

    width: 96%;

    max-width: 1400px;

    margin: 30px auto;

    background: white;

    padding: 25px;

    border-radius: 12px;

    box-shadow:
        0 5px 20px rgba(0,0,0,.10);

}


.header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

}


h2 {

    margin: 0;

    color: #003366;

}


.message {

    padding: 12px;

    border-radius: 6px;

    margin-bottom: 20px;

}


.success {

    background: #d4edda;

    color: #155724;

}


.error {

    background: #f8d7da;

    color: #721c24;

}


table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 11px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* =========================
   STATUS
========================= */

.status {

    display: inline-block;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

}


.status-pending {

    background: #fff3cd;

    color: #856404;

}


.status-approved {

    background: #d4edda;

    color: #155724;

}


.status-rejected {

    background: #f8d7da;

    color: #721c24;

}


/* =========================
   BUTTONS
========================= */

.actions {

    display: flex;

    gap: 6px;

    flex-wrap: wrap;

}


button {

    border: none;

    padding: 8px 12px;

    border-radius: 5px;

    cursor: pointer;

    color: white;

    font-size: 13px;

}


button:hover {

    opacity: .85;

}


.btn-approve {

    background: #198754;

}


.btn-pending {

    background: #f0ad4e;

}


.btn-deny {

    background: #dc3545;

}


.btn-view {

    display: inline-block;

    padding: 8px 12px;

    background: #003366;

    color: white;

    text-decoration: none;

    border-radius: 5px;

    font-size: 13px;

}


.btn-view:hover {

    background: #0055aa;

}


.empty {

    text-align: center;

    padding: 30px;

    color: #777;

}


/* =========================
   RESPONSIVE
========================= */

.table-wrapper {

    overflow-x: auto;

}

</style>

</head>


<body>


<div class="container">


<div class="header">

<h2>
Teacher Applications
</h2>

</div>


<?php if ($message !== ""): ?>

<div class="message <?php echo $message_type; ?>">

<?php
echo htmlspecialchars($message);
?>

</div>

<?php endif; ?>


<div class="table-wrapper">


<table>


<thead>

<tr>

<th>ID</th>

<th>Application Reference</th>

<th>Teacher Name</th>

<th>Email</th>

<th>Phone</th>

<th>Subjects</th>

<th>Curriculum</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php if (count($applications) > 0): ?>


<?php foreach ($applications as $application): ?>


<?php

$status =
    trim(
        $application['application_status']
        ?? 'Pending'
    );


$statusLower =
    strtolower($status);


if ($statusLower === "approved") {

    $statusClass =
        "status-approved";

} elseif (
    $statusLower === "rejected"
    ||
    $statusLower === "denied"
) {

    $statusClass =
        "status-rejected";

} else {

    $statusClass =
        "status-pending";

}

?>


<tr>


<td>

<?php

echo htmlspecialchars(
    $application['id']
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['application_reference']
    ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['full_name']
    ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['email']
    ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['phone']
    ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['subjects']
    ?? ''
);

?>

</td>


<td>

<?php

echo htmlspecialchars(
    $application['curricula']
    ?? ''
);

?>

</td>


<td>

<span class="status <?php echo $statusClass; ?>">

<?php

echo htmlspecialchars(
    $status
);

?>

</span>

</td>


<td>


<div class="actions">


<!-- VIEW -->

<a
    href="teacher_application_view.php?id=<?php echo $application['id']; ?>"
    class="btn-view"
>
    View
</a>


<!-- APPROVE -->

<form
    method="POST"
    style="display:inline;"
>

<input
    type="hidden"
    name="application_id"
    value="<?php echo $application['id']; ?>"
>


<button
    type="submit"
    name="action"
    value="approve"
    class="btn-approve"
    onclick="return confirm('Open this application for approval?');"
>
    Approve
</button>

</form>


<!-- PENDING -->

<form
    method="POST"
    style="display:inline;"
>

<input
    type="hidden"
    name="application_id"
    value="<?php echo $application['id']; ?>"
>


<button
    type="submit"
    name="action"
    value="pending"
    class="btn-pending"
    onclick="return confirm('Set this application back to Pending?');"
>
    Pending
</button>

</form>


<!-- DENY -->

<form
    method="POST"
    style="display:inline;"
>

<input
    type="hidden"
    name="application_id"
    value="<?php echo $application['id']; ?>"
>


<button
    type="submit"
    name="action"
    value="deny"
    class="btn-deny"
    onclick="return confirm('Are you sure you want to deny this application?');"
>
    Deny
</button>

</form>


</div>


</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td
    colspan="9"
    class="empty"
>

No teacher applications found.

</td>

</tr>


<?php endif; ?>


</tbody>


</table>


</div>


</div>


</body>

</html>
