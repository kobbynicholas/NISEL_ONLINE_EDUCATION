<?php

require "../admin_auth.php";
require "../config/db.php";


/* =========================================================
   MESSAGE
========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   UPDATE APPLICATION STATUS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $application_id = intval($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? "";

    if ($application_id > 0) {

        if ($action === "approve") {

            $stmt = $pdo->prepare("SELECT * FROM teachers");
$stmt->execute();

$teachers = $stmt->fetchAll();
            );

            if ($stmt->execute()) {

                $message =
                    "Teacher application approved successfully.";

                $message_type = "success";

            } else {

                $message =
                    "Unable to approve the application.";

                $message_type = "error";
            }

            $stmt->close();
        }


        elseif ($action === "reject") {

            $stmt = $conn->prepare("
                UPDATE teacher_applications
                SET application_status = 'Rejected'
                WHERE id = ?
            ");

            $stmt->bind_param(
                "i",
                $application_id
            );

            if ($stmt->execute()) {

                $message =
                    "Teacher application rejected.";

                $message_type = "success";

            } else {

                $message =
                    "Unable to reject the application.";

                $message_type = "error";
            }

            $stmt->close();
        }


        elseif ($action === "pending") {

            $stmt = $conn->prepare("
                UPDATE teacher_applications
                SET application_status = 'Pending'
                WHERE id = ?
            ");

            $stmt->bind_param(
                "i",
                $application_id
            );

            if ($stmt->execute()) {

                $message =
                    "Application returned to Pending.";

                $message_type = "success";

            } else {

                $message =
                    "Unable to update the application.";

                $message_type = "error";
            }

            $stmt->close();
        }
    }
}


/* =========================================================
   FILTER
========================================================= */

$status_filter =
    $_GET['status'] ?? "All";


if (
    !in_array(
        $status_filter,
        ["All", "Pending", "Approved", "Rejected"],
        true
    )
) {

    $status_filter = "All";
}


/* =========================================================
   GET APPLICATIONS
========================================================= */

if ($status_filter === "All") {

    $applications = $conn->query("
        SELECT *
        FROM teacher_applications
        ORDER BY id DESC
    ");

} else {

    $stmt = $conn->prepare("
        SELECT *
        FROM teacher_applications
        WHERE application_status = ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "s",
        $status_filter
    );

    $stmt->execute();

    $applications =
        $stmt->get_result();

    $stmt->close();
}


/* =========================================================
   APPLICATION COUNTS
========================================================= */

$total_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM teacher_applications
");

$total =
    $total_result->fetch_assoc()['total'];


$pending_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM teacher_applications
    WHERE application_status = 'Pending'
");

$pending =
    $pending_result->fetch_assoc()['total'];


$approved_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM teacher_applications
    WHERE application_status = 'Approved'
");

$approved =
    $approved_result->fetch_assoc()['total'];


$rejected_result = $conn->query("
    SELECT COUNT(*) AS total
    FROM teacher_applications
    WHERE application_status = 'Rejected'
");

$rejected =
    $rejected_result->fetch_assoc()['total'];

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

    font-family:
        Arial,
        sans-serif;

    background:
        #eef3f8;

    color: #333;

}


/* =========================================================
   HEADER
========================================================= */

.header {

    background:
        #003366;

    color: white;

    padding: 20px 30px;

}


.header h1 {

    margin: 0;

    font-size: 25px;

}


.header p {

    margin: 6px 0 0;

    color:
        #dceaf6;

}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width: 96%;

    max-width: 1400px;

    margin: 30px auto;

}


/* =========================================================
   MESSAGE
========================================================= */

.message {

    padding: 14px 18px;

    border-radius: 7px;

    margin-bottom: 20px;

    font-weight: bold;

}


.message.success {

    background:
        #d4edda;

    color:
        #155724;

}


.message.error {

    background:
        #f8d7da;

    color:
        #721c24;

}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 10px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.08);

}


.stat-card h3 {

    margin: 0 0 8px;

    color:
        #666;

    font-size: 14px;

}


.stat-number {

    font-size: 30px;

    font-weight: bold;

    color:
        #003366;

}


/* =========================================================
   FILTER
========================================================= */

.filter-box {

    background: white;

    padding: 18px;

    border-radius: 10px;

    margin-bottom: 20px;

    box-shadow:
        0 5px 15px
        rgba(0,0,0,.07);

}


.filter-box a {

    display: inline-block;

    padding: 9px 18px;

    margin-right: 8px;

    margin-bottom: 5px;

    border-radius: 20px;

    text-decoration: none;

    background:
        #edf2f7;

    color:
        #333;

}


.filter-box a.active {

    background:
        #003366;

    color: white;

}


/* =========================================================
   TABLE
========================================================= */

.table-card {

    background: white;

    border-radius: 12px;

    overflow-x: auto;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.08);

}


table {

    width: 100%;

    border-collapse:
        collapse;

    min-width:
        1100px;

}


th {

    background:
        #003366;

    color: white;

    padding: 13px;

    text-align: left;

    font-size: 14px;

}


td {

    padding: 12px;

    border-bottom:
        1px solid #e5e5e5;

    vertical-align:
        middle;

    font-size: 14px;

}


tr:hover {

    background:
        #f8fbfd;

}


/* =========================================================
   PHOTO
========================================================= */

.applicant-photo {

    width: 55px;

    height: 55px;

    object-fit: cover;

    border-radius: 50%;

    border:
        2px solid #ddd;

}


.no-photo {

    width: 55px;

    height: 55px;

    border-radius: 50%;

    background:
        #e8eef4;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 22px;

}


/* =========================================================
   STATUS
========================================================= */

.status {

    display: inline-block;

    padding: 6px 12px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.status.pending {

    background:
        #fff3cd;

    color:
        #856404;

}


.status.approved {

    background:
        #d4edda;

    color:
        #155724;

}


.status.rejected {

    background:
        #f8d7da;

    color:
        #721c24;

}


/* =========================================================
   BUTTONS
========================================================= */

.btn {

    border: none;

    padding: 8px 12px;

    border-radius: 5px;

    color: white;

    cursor: pointer;

    font-size: 12px;

    text-decoration: none;

    display: inline-block;

    margin: 2px;

}


.btn-view {

    background:
        #003366;

}


.btn-approve {

    background:
        #198754;

}


.btn-reject {

    background:
        #dc3545;

}


.btn-pending {

    background:
        #f0ad4e;

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    text-align: center;

    padding: 50px;

    color:
        #777;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 900px) {

    .stats {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width: 550px) {

    .stats {

        grid-template-columns: 1fr;

    }

}

</style>

</head>


<body>


<!-- =======================================================
     HEADER
======================================================= -->

<div class="header">

    <h1>
        NISEL ONLINE EDUCATION
    </h1>

    <p>
        Teacher Application Management
    </p>

</div>



<div class="container">


<!-- =======================================================
     MESSAGE
======================================================= -->

<?php if ($message !== ""): ?>

<div class="message
<?php echo
    $message_type;
?>">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>



<!-- =======================================================
     STATISTICS
======================================================= -->

<div class="stats">


<div class="stat-card">

<h3>
Total Applications
</h3>

<div class="stat-number">

<?php echo $total; ?>

</div>

</div>


<div class="stat-card">

<h3>
Pending Applications
</h3>

<div class="stat-number">

<?php echo $pending; ?>

</div>

</div>


<div class="stat-card">

<h3>
Approved Applications
</h3>

<div class="stat-number">

<?php echo $approved; ?>

</div>

</div>


<div class="stat-card">

<h3>
Rejected Applications
</h3>

<div class="stat-number">

<?php echo $rejected; ?>

</div>

</div>


</div>



<!-- =======================================================
     FILTER
======================================================= -->

<div class="filter-box">

<strong>
Filter Applications:
</strong>

<a
href="teacher_applications.php"
class="<?php
echo $status_filter === 'All'
    ? 'active'
    : '';
?>"
>
All
</a>


<a
href="?status=Pending"
class="<?php
echo $status_filter === 'Pending'
    ? 'active'
    : '';
?>"
>
Pending
</a>


<a
href="?status=Approved"
class="<?php
echo $status_filter === 'Approved'
    ? 'active'
    : '';
?>"
>
Approved
</a>


<a
href="?status=Rejected"
class="<?php
echo $status_filter === 'Rejected'
    ? 'active'
    : '';
?>"
>
Rejected
</a>

</div>



<!-- =======================================================
     APPLICATION TABLE
======================================================= -->

<div class="table-card">


<?php if (
    $applications &&
    $applications->num_rows > 0
): ?>


<table>


<thead>

<tr>

<th>
Photo
</th>

<th>
Applicant
</th>

<th>
Contact
</th>

<th>
Qualification
</th>

<th>
Curriculum
</th>

<th>
Subjects
</th>

<th>
Experience
</th>

<th>
Status
</th>

<th>
Application Date
</th>

<th>
Actions
</th>

</tr>

</thead>


<tbody>


<?php while (
    $application =
    $applications->fetch_assoc()
): ?>


<tr>


<!-- PHOTO -->

<td>

<?php

$photo_path =
    "../uploads/teacher_applications/photos/"
    . $application['photo_filename'];

if (
    !empty(
        $application['photo_filename']
    ) &&
    file_exists(
        __DIR__ .
        "/../uploads/teacher_applications/photos/"
        . $application['photo_filename']
    )
):

?>

<img
    src="<?php
        echo htmlspecialchars(
            $photo_path
        );
    ?>"
    class="applicant-photo"
    alt="Teacher Photo"
>

<?php else: ?>

<div class="no-photo">
    👤
</div>

<?php endif; ?>

</td>



<!-- APPLICANT -->

<td>

<strong>

<?php

echo htmlspecialchars(
    $application['full_name']
);

?>

</strong>

<br>

<small>

Ref:

<?php

echo htmlspecialchars(
    $application[
        'application_reference'
    ]
);

?>

</small>

</td>



<!-- CONTACT -->

<td>

<?php

echo htmlspecialchars(
    $application['email']
);

?>

<br>

<?php

echo htmlspecialchars(
    $application['phone']
);

?>

</td>



<!-- QUALIFICATION -->

<td>

<?php

echo htmlspecialchars(
    $application['qualification']
);

?>

<br>

<small>

<?php

echo htmlspecialchars(
    $application['institution']
);

?>

</small>

</td>



<!-- CURRICULUM -->

<td>

<?php

echo nl2br(
    htmlspecialchars(
        $application['curricula']
    )
);

?>

</td>



<!-- SUBJECTS -->

<td>

<?php

echo nl2br(
    htmlspecialchars(
        $application['subjects']
    )
);

?>

</td>



<!-- EXPERIENCE -->

<td>

<?php

echo htmlspecialchars(
    $application[
        'teaching_experience'
    ]
);

?>

</td>



<!-- STATUS -->

<td>

<?php

$status =
    $application[
        'application_status'
    ];

$status_class =
    strtolower(
        $status
    );

?>

<span class="status
<?php
echo $status_class;
?>">

<?php

echo htmlspecialchars(
    $status
);

?>

</span>

</td>



<!-- DATE -->

<td>

<?php

echo date(
    "d M Y",
    strtotime(
        $application[
            'application_date'
        ]
    )
);

?>

</td>



<!-- ACTIONS -->

<td>


<a
class="btn btn-view"
href="teacher_application_view.php?id=<?php
echo $application['id'];
?>"
>
View
</a>


<?php if (
    $status !== "Approved"
): ?>

<form
method="POST"
style="display:inline;"
>

<input
type="hidden"
name="application_id"
value="<?php
echo $application['id'];
?>"
>

<button
type="submit"
name="action"
value="approve"
class="btn btn-approve"
onclick="
return confirm(
'Approve this teacher application?'
);
"
>

Approve

</button>

</form>

<?php endif; ?>



<?php if (
    $status !== "Rejected"
): ?>

<form
method="POST"
style="display:inline;"
>

<input
type="hidden"
name="application_id"
value="<?php
echo $application['id'];
?>"
>

<button
type="submit"
name="action"
value="reject"
class="btn btn-reject"
onclick="
return confirm(
'Reject this teacher application?'
);
"
>

Reject

</button>

</form>

<?php endif; ?>



<?php if (
    $status !== "Pending"
): ?>

<form
method="POST"
style="display:inline;"
>

<input
type="hidden"
name="application_id"
value="<?php
echo $application['id'];
?>"
>

<button
type="submit"
name="action"
value="pending"
class="btn btn-pending"
>

Pending

</button>

</form>

<?php endif; ?>


</td>


</tr>


<?php endwhile; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty">

<h3>
No Teacher Applications Found
</h3>

<p>

There are currently no applications
matching the selected filter.

</p>

</div>


<?php endif; ?>


</div>


</div>


</body>

</html>


<?php

$conn->close();

?>
