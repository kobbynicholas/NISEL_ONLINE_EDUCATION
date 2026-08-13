<?php

/*
=========================================================
NISEL ONLINE EDUCATION
ADMIN - BOOKINGS MANAGEMENT
=========================================================
*/

require "../admin_auth.php";
require "../config/db.php";


/*
=========================================================
PDO SETTINGS
=========================================================
*/

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);


/*
=========================================================
HELPER
=========================================================
*/

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
=========================================================
MESSAGE
=========================================================
*/

$message = "";
$message_type = "success";


/*
=========================================================
ASSIGN / UNASSIGN TEACHER
=========================================================
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["action"])
) {

    $action =
        trim(
            $_POST["action"]
        );

    $booking_id =
        isset($_POST["booking_id"])
            ? (int)$_POST["booking_id"]
            : 0;


    /*
    =====================================================
    ASSIGN TEACHER
    =====================================================
    */

    if (
        $action === "assign"
        &&
        $booking_id > 0
    ) {

        $teacher_id =
            isset($_POST["teacher_id"])
                ? (int)$_POST["teacher_id"]
                : 0;


        if ($teacher_id <= 0) {

            $message =
                "Please select a teacher.";

            $message_type =
                "error";

        } else {

            try {

                /*
                -----------------------------------------
                GET TEACHER
                -----------------------------------------
                */

                $stmt =
                    $pdo->prepare("
                        SELECT
                            teacher_id,
                            teacher_name,
                            email,
                            phone,
                            status
                        FROM teachers
                        WHERE teacher_id = ?
                        LIMIT 1
                    ");

                $stmt->execute([
                    $teacher_id
                ]);

                $teacher =
                    $stmt->fetch();


                if (!$teacher) {

                    throw new Exception(
                        "Selected teacher was not found."
                    );

                }


                /*
                -----------------------------------------
                CHECK ACTIVE TEACHER
                -----------------------------------------
                */

                if (
                    isset($teacher["status"])
                    &&
                    strtolower(
                        trim(
                            $teacher["status"]
                        )
                    ) !== "active"
                ) {

                    throw new Exception(
                        "The selected teacher is not active."
                    );

                }


                /*
                -----------------------------------------
                UPDATE BOOKING
                -----------------------------------------
                */

                $stmt =
                    $pdo->prepare("
                        UPDATE bookings

                        SET
                            teacher_id = ?,
                            teacher_name = ?,
                            assignment_status = 'Assigned'

                        WHERE id = ?
                    ");

                $stmt->execute([
                    $teacher["teacher_id"],
                    $teacher["teacher_name"],
                    $booking_id
                ]);


                $message =
                    "Teacher successfully assigned to the booking.";

                $message_type =
                    "success";


            } catch (Exception $e) {

                $message =
                    "Unable to assign teacher: "
                    . $e->getMessage();

                $message_type =
                    "error";

            }

        }

    }


    /*
    =====================================================
    UNASSIGN TEACHER
    =====================================================
    */

    elseif (
        $action === "unassign"
        &&
        $booking_id > 0
    ) {

        try {

            $stmt =
                $pdo->prepare("
                    UPDATE bookings

                    SET
                        teacher_id = NULL,
                        teacher_name = NULL,
                        assignment_status = 'Unassigned'

                    WHERE id = ?
                ");

            $stmt->execute([
                $booking_id
            ]);


            $message =
                "Teacher assignment removed successfully.";

            $message_type =
                "success";


        } catch (PDOException $e) {

            $message =
                "Unable to remove teacher assignment: "
                . $e->getMessage();

            $message_type =
                "error";

        }

    }


    /*
    =====================================================
    INVALID ACTION
    =====================================================
    */

    else {

        if ($action !== "") {

            $message =
                "Invalid booking action.";

            $message_type =
                "error";

        }

    }

}


/*
=========================================================
GET ACTIVE TEACHERS
=========================================================
*/

try {

    $stmt =
        $pdo->prepare("
            SELECT
                teacher_id,
                teacher_name,
                email,
                phone,
                status

            FROM teachers

            WHERE
                status = 'Active'

            ORDER BY
                teacher_name ASC
        ");

    $stmt->execute();

    $teachers =
        $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load teachers: "
        . e($e->getMessage())
    );

}


/*
=========================================================
SEARCH / FILTERS
=========================================================
*/

$search =
    trim(
        $_GET["search"] ?? ""
    );


$payment_filter =
    trim(
        $_GET["payment"] ?? ""
    );


$assignment_filter =
    trim(
        $_GET["assignment"] ?? ""
    );


$status_filter =
    trim(
        $_GET["status"] ?? ""
    );


/*
=========================================================
BUILD BOOKING QUERY
=========================================================
*/

$sql = "
    SELECT
        b.*
    FROM bookings b
    WHERE 1 = 1
";


$params = [];


/*
=========================================================
SEARCH
=========================================================
*/

if ($search !== "") {

    $sql .= "
        AND (
            b.student_name LIKE ?
            OR b.email LIKE ?
            OR b.phone LIKE ?
            OR b.booking_reference LIKE ?
            OR b.subjects LIKE ?
            OR b.teacher_name LIKE ?
        )
    ";

    $search_value =
        "%" . $search . "%";


    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;

    $params[] =
        $search_value;
}


/*
=========================================================
PAYMENT FILTER
=========================================================
*/

if ($payment_filter !== "") {

    $sql .= "
        AND b.payment_status = ?
    ";

    $params[] =
        $payment_filter;
}


/*
=========================================================
ASSIGNMENT FILTER
=========================================================
*/

if ($assignment_filter !== "") {

    if (
        strtolower(
            $assignment_filter
        ) === "assigned"
    ) {

        $sql .= "
            AND b.teacher_id IS NOT NULL
        ";

    } elseif (
        strtolower(
            $assignment_filter
        ) === "unassigned"
    ) {

        $sql .= "
            AND (
                b.teacher_id IS NULL
                OR b.teacher_id = 0
                OR b.teacher_name IS NULL
                OR b.teacher_name = ''
            )
        ";

    }

}


/*
=========================================================
LESSON STATUS FILTER
=========================================================
*/

if ($status_filter !== "") {

    $sql .= "
        AND b.lesson_status = ?
    ";

    $params[] =
        $status_filter;
}


/*
=========================================================
ORDER
=========================================================
*/

$sql .= "
    ORDER BY
        b.id DESC
";


/*
=========================================================
GET BOOKINGS
=========================================================
*/

try {

    $stmt =
        $pdo->prepare(
            $sql
        );

    $stmt->execute(
        $params
    );

    $bookings =
        $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Unable to load bookings: "
        . e($e->getMessage())
    );

}


/*
=========================================================
SUMMARY COUNTS
=========================================================
*/

$total_bookings =
    count($bookings);


$paid_bookings = 0;

$unpaid_bookings = 0;

$assigned_bookings = 0;

$unassigned_bookings = 0;


foreach (
    $bookings
    as $booking
) {

    $payment =
        strtolower(
            trim(
                $booking["payment_status"]
                ?? ""
            )
        );


    if (
        in_array(
            $payment,
            [
                "paid",
                "success",
                "successful",
                "completed"
            ],
            true
        )
    ) {

        $paid_bookings++;

    } else {

        $unpaid_bookings++;

    }


    $teacher_id =
        $booking["teacher_id"]
        ?? null;


    $teacher_name =
        trim(
            $booking["teacher_name"]
            ?? ""
        );


    if (
        !empty($teacher_id)
        ||
        $teacher_name !== ""
    ) {

        $assigned_bookings++;

    } else {

        $unassigned_bookings++;

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Bookings | NISEL ONLINE EDUCATION
</title>


<style>

/*
=========================================================
RESET
=========================================================
*/

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;

    background:
        #eef3f8;

    color:
        #172033;

}


/*
=========================================================
LAYOUT
=========================================================
*/

.page {

    width: 96%;

    max-width: 1550px;

    margin:
        25px auto 40px;

}


/*
=========================================================
HEADER
=========================================================
*/

.page-header {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 20px;

    margin-bottom:
        22px;

}


.title-area h1 {

    margin: 0;

    color:
        #003b70;

    font-size:
        28px;

    font-weight:
        800;

}


.title-area p {

    margin:
        7px 0 0;

    color:
        #6b7788;

    font-size:
        13px;

}


.header-button {

    display:
        inline-flex;

    align-items:
        center;

    gap: 8px;

    padding:
        11px 17px;

    border-radius:
        9px;

    background:
        #003b70;

    color:
        #ffffff;

    text-decoration:
        none;

    font-size:
        13px;

    font-weight:
        700;

}


/*
=========================================================
SUMMARY CARDS
=========================================================
*/

.summary-grid {

    display:
        grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:
        16px;

    margin-bottom:
        20px;

}


.summary-card {

    position:
        relative;

    overflow:
        hidden;

    background:
        #ffffff;

    border-radius:
        14px;

    padding:
        20px;

    box-shadow:
        0 6px 25px
        rgba(15,35,60,.07);

    border:
        1px solid
        #e7edf3;

}


.summary-card::after {

    content: "";

    position:
        absolute;

    right:
        -20px;

    bottom:
        -25px;

    width:
        80px;

    height:
        80px;

    border-radius:
        50%;

    background:
        rgba(0,59,112,.06);

}


.summary-label {

    color:
        #718096;

    font-size:
        11px;

    font-weight:
        700;

    text-transform:
        uppercase;

    letter-spacing:
        .5px;

}


.summary-number {

    margin-top:
        8px;

    color:
        #003b70;

    font-size:
        28px;

    font-weight:
        850;

}


/*
=========================================================
MESSAGE
=========================================================
*/

.alert {

    display:
        flex;

    align-items:
        center;

    gap:
        10px;

    margin-bottom:
        18px;

    padding:
        13px 16px;

    border-radius:
        10px;

    font-size:
        13px;

    font-weight:
        600;

}


.alert-success {

    background:
        #e7f7ee;

    color:
        #137a43;

    border:
        1px solid
        #c6ecd8;

}


.alert-error {

    background:
        #fdeaea;

    color:
        #a52626;

    border:
        1px solid
        #f4caca;

}


/*
=========================================================
MAIN CARD
=========================================================
*/

.card {

    background:
        #ffffff;

    border:
        1px solid
        #e7edf3;

    border-radius:
        15px;

    box-shadow:
        0 6px 25px
        rgba(15,35,60,.07);

    overflow:
        hidden;

}


/*
=========================================================
FILTER BAR
=========================================================
*/

.filters {

    display:
        grid;

    grid-template-columns:
        minmax(220px, 1fr)
        180px
        180px
        180px
        auto;

    gap:
        10px;

    padding:
        18px;

    border-bottom:
        1px solid
        #edf1f5;

    background:
        #fbfcfe;

}


.input,
.select {

    width:
        100%;

    height:
        42px;

    border:
        1px solid
        #d9e1ea;

    border-radius:
        8px;

    padding:
        0 12px;

    background:
        #ffffff;

    color:
        #263445;

    font-size:
        12px;

    outline:
        none;

}


.input:focus,
.select:focus {

    border-color:
        #0877c9;

    box-shadow:
        0 0 0 3px
        rgba(8,119,201,.08);

}


.filter-button {

    height:
        42px;

    border:
        none;

    border-radius:
        8px;

    padding:
        0 17px;

    background:
        #003b70;

    color:
        #ffffff;

    cursor:
        pointer;

    font-weight:
        700;

}


/*
=========================================================
TABLE
=========================================================
*/

.table-wrapper {

    width:
        100%;

    overflow-x:
        auto;

}


table {

    width:
        100%;

    min-width:
        1250px;

    border-collapse:
        collapse;

}


thead th {

    padding:
        14px 13px;

    text-align:
        left;

    background:
        #003b70;

    color:
        #ffffff;

    font-size:
        11px;

    font-weight:
        800;

    white-space:
        nowrap;

}


tbody td {

    padding:
        13px;

    border-bottom:
        1px solid
        #edf1f5;

    vertical-align:
        middle;

    font-size:
        12px;

}


tbody tr:hover {

    background:
        #f8fbfe;

}


/*
=========================================================
STUDENT
=========================================================
*/

.student-name {

    color:
        #172033;

    font-weight:
        800;

}


.student-email {

    margin-top:
        3px;

    color:
        #718096;

    font-size:
        10px;

}


/*
=========================================================
BOOKING REFERENCE
=========================================================
*/

.reference {

    color:
        #003b70;

    font-family:
        monospace;

    font-size:
        11px;

    font-weight:
        700;

}


/*
=========================================================
SUBJECT
=========================================================
*/

.subject {

    display:
        inline-block;

    padding:
        5px 8px;

    border-radius:
        6px;

    background:
        #edf5fc;

    color:
        #075a9e;

    font-size:
        10px;

    font-weight:
        800;

}


/*
=========================================================
BADGES
=========================================================
*/

.badge {

    display:
        inline-flex;

    align-items:
        center;

    padding:
        5px 9px;

    border-radius:
        20px;

    font-size:
        9px;

    font-weight:
        800;

    white-space:
        nowrap;

}


.badge-paid {

    background:
        #dcf7e8;

    color:
        #137a43;

}


.badge-unpaid {

    background:
        #fff0d7;

    color:
        #986000;

}


.badge-assigned {

    background:
        #e6f0ff;

    color:
        #185ca8;

}


.badge-unassigned {

    background:
        #f1f3f5;

    color:
        #6b7280;

}


.badge-active {

    background:
        #e3f8ee;

    color:
        #177548;

}


.badge-pending {

    background:
        #fff1d6;

    color:
        #986000;

}


.badge-cancelled {

    background:
        #fde6e6;

    color:
        #a52626;

}


/*
=========================================================
TEACHER
=========================================================
*/

.teacher-name {

    color:
        #263445;

    font-weight:
        700;

}


.no-teacher {

    color:
        #9aa5b1;

    font-style:
        italic;

}


/*
=========================================================
ACTION
=========================================================
*/

.action-cell {

    min-width:
        260px;

}


.assign-form {

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

}


.assign-select {

    min-width:
        145px;

    height:
        35px;

    padding:
        0 8px;

    border:
        1px solid
        #d7dfe8;

    border-radius:
        7px;

    background:
        #ffffff;

    font-size:
        10px;

    outline:
        none;

}


.assign-button {

    height:
        35px;

    border:
        none;

    border-radius:
        7px;

    padding:
        0 10px;

    background:
        #0877c9;

    color:
        #ffffff;

    font-size:
        10px;

    font-weight:
        800;

    cursor:
        pointer;

}


.assign-button:hover {

    background:
        #075f9e;

}


.unassign-button {

    height:
        35px;

    border:
        none;

    border-radius:
        7px;

    padding:
        0 10px;

    background:
        #f3e6e6;

    color:
        #a52626;

    font-size:
        10px;

    font-weight:
        800;

    cursor:
        pointer;

}


.unassign-button:hover {

    background:
        #f8d4d4;

}


/*
=========================================================
EMPTY
=========================================================
*/

.empty {

    padding:
        60px 20px;

    text-align:
        center;

    color:
        #7b8794;

}


.empty-icon {

    font-size:
        40px;

    margin-bottom:
        12px;

}


.empty h3 {

    margin:
        0 0 5px;

    color:
        #344054;

}


.empty p {

    margin:
        0;

    font-size:
        12px;

}


/*
=========================================================
RESPONSIVE
=========================================================
*/

@media(max-width:1100px) {

    .summary-grid {

        grid-template-columns:
            repeat(2, 1fr);

    }


    .filters {

        grid-template-columns:
            1fr 1fr;

    }

}


@media(max-width:650px) {

    .page {

        width:
            94%;

    }


    .page-header {

        align-items:
            flex-start;

        flex-direction:
            column;

    }


    .summary-grid {

        grid-template-columns:
            1fr;

    }


    .filters {

        grid-template-columns:
            1fr;

    }

}

</style>

</head>


<body>


<div class="page">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="page-header">

        <div class="title-area">

            <h1>
                Bookings
            </h1>

            <p>
                Manage student bookings, payments and teacher assignments.
            </p>

        </div>


        <a
            href="dashboard.php"
            class="header-button"
        >

            ← Dashboard

        </a>

    </div>


    <!-- =================================================
         MESSAGE
    ================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="alert
            <?php
            echo $message_type === "error"
                ? "alert-error"
                : "alert-success";
            ?>"
        >

            <?php if ($message_type === "error"): ?>

                ⚠️

            <?php else: ?>

                ✓

            <?php endif; ?>


            <?= e($message) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         SUMMARY
    ================================================== -->

    <div class="summary-grid">


        <div class="summary-card">

            <div class="summary-label">
                Total Bookings
            </div>

            <div class="summary-number">
                <?= number_format($total_bookings) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Paid Bookings
            </div>

            <div class="summary-number">
                <?= number_format($paid_bookings) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Assigned
            </div>

            <div class="summary-number">
                <?= number_format($assigned_bookings) ?>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                Unassigned
            </div>

            <div class="summary-number">
                <?= number_format($unassigned_bookings) ?>
            </div>

        </div>


    </div>


    <!-- =================================================
         MAIN CARD
    ================================================== -->

    <div class="card">


        <!-- FILTERS -->

        <form
            method="GET"
            class="filters"
        >


            <input
                type="text"
                name="search"
                class="input"
                placeholder="Search student, email, booking, subject or teacher..."
                value="<?= e($search) ?>"
            >


            <select
                name="payment"
                class="select"
            >

                <option value="">
                    All Payments
                </option>

                <option
                    value="Paid"
                    <?= $payment_filter === "Paid"
                        ? "selected"
                        : "" ?>
                >
                    Paid
                </option>

                <option
                    value="Pending"
                    <?= $payment_filter === "Pending"
                        ? "selected"
                        : "" ?>
                >
                    Pending
                </option>

                <option
                    value="Unpaid"
                    <?= $payment_filter === "Unpaid"
                        ? "selected"
                        : "" ?>
                >
                    Unpaid
                </option>

            </select>


            <select
                name="assignment"
                class="select"
            >

                <option value="">
                    All Assignments
                </option>

                <option
                    value="Assigned"
                    <?= $assignment_filter === "Assigned"
                        ? "selected"
                        : "" ?>
                >
                    Assigned
                </option>

                <option
                    value="Unassigned"
                    <?= $assignment_filter === "Unassigned"
                        ? "selected"
                        : "" ?>
                >
                    Unassigned
                </option>

            </select>


            <select
                name="status"
                class="select"
            >

                <option value="">
                    All Lesson Status
                </option>

                <option
                    value="Pending"
                    <?= $status_filter === "Pending"
                        ? "selected"
                        : "" ?>
                >
                    Pending
                </option>

                <option
                    value="Scheduled"
                    <?= $status_filter === "Scheduled"
                        ? "selected"
                        : "" ?>
                >
                    Scheduled
                </option>

                <option
                    value="Completed"
                    <?= $status_filter === "Completed"
                        ? "selected"
                        : "" ?>
                >
                    Completed
                </option>

                <option
                    value="Cancelled"
                    <?= $status_filter === "Cancelled"
                        ? "selected"
                        : "" ?>
                >
                    Cancelled
                </option>

            </select>


            <button
                type="submit"
                class="filter-button"
            >

                🔎 Filter

            </button>


        </form>


        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="table-wrapper">


            <table>


                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Booking
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Curriculum
                        </th>

                        <th>
                            Class
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Payment
                        </th>

                        <th>
                            Assignment
                        </th>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (count($bookings) > 0): ?>


                    <?php foreach (
                        $bookings
                        as $booking
                    ): ?>


                        <?php

                        /*
                        -------------------------------------
                        PAYMENT
                        -------------------------------------
                        */

                        $payment_status =
                            trim(
                                $booking[
                                    "payment_status"
                                ]
                                ??
                                "Pending"
                            );


                        $payment_lower =
                            strtolower(
                                $payment_status
                            );


                        $is_paid =
                            in_array(
                                $payment_lower,
                                [
                                    "paid",
                                    "success",
                                    "successful",
                                    "completed"
                                ],
                                true
                            );


                        /*
                        -------------------------------------
                        ASSIGNMENT
                        -------------------------------------
                        */

                        $booking_teacher_id =
                            (int)(
                                $booking[
                                    "teacher_id"
                                ]
                                ??
                                0
                            );


                        $booking_teacher_name =
                            trim(
                                $booking[
                                    "teacher_name"
                                ]
                                ??
                                ""
                            );


                        $is_assigned =
                            (
                                $booking_teacher_id > 0
                                ||
                                $booking_teacher_name !== ""
                            );


                        /*
                        -------------------------------------
                        LESSON STATUS
                        -------------------------------------
                        */

                        $lesson_status =
                            trim(
                                $booking[
                                    "lesson_status"
                                ]
                                ??
                                "Pending"
                            );


                        $lesson_lower =
                            strtolower(
                                $lesson_status
                            );


                        $lesson_class =
                            "badge-pending";


                        if (
                            in_array(
                                $lesson_lower,
                                [
                                    "cancelled",
                                    "canceled"
                                ],
                                true
                            )
                        ) {

                            $lesson_class =
                                "badge-cancelled";

                        }

                        elseif (
                            in_array(
                                $lesson_lower,
                                [
                                    "completed"
                                ],
                                true
                            )
                        ) {

                            $lesson_class =
                                "badge-active";

                        }


                        /*
                        -------------------------------------
                        DATE
                        -------------------------------------
                        */

                        $date_display =
                            !empty(
                                $booking["lesson_date"]
                            )
                                ? date(
                                    "d M Y",
                                    strtotime(
                                        $booking[
                                            "lesson_date"
                                        ]
                                    )
                                )
                                : "—";


                        /*
                        -------------------------------------
                        TIME
                        -------------------------------------
                        */

                        $time_display =
                            !empty(
                                $booking["lesson_time"]
                            )
                                ? date(
                                    "h:i A",
                                    strtotime(
                                        $booking[
                                            "lesson_time"
                                        ]
                                    )
                                )
                                : "—";

                        ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <strong>
                                    <?= e(
                                        $booking["id"]
                                    ) ?>
                                </strong>

                            </td>


                            <!-- BOOKING -->

                            <td>

                                <div class="reference">

                                    <?= e(
                                        $booking[
                                            "booking_reference"
                                        ]
                                        ??
                                        "BOOKING-" .
                                        $booking["id"]
                                    ) ?>

                                </div>

                            </td>


                            <!-- STUDENT -->

                            <td>

                                <div class="student-name">

                                    <?= e(
                                        $booking[
                                            "student_name"
                                        ]
                                        ??
                                        "Unknown Student"
                                    ) ?>

                                </div>


                                <div class="student-email">

                                    <?= e(
                                        $booking[
                                            "email"
                                        ]
                                        ??
                                        ""
                                    ) ?>

                                </div>

                            </td>


                            <!-- SUBJECT -->

                            <td>

                                <span class="subject">

                                    <?= e(
                                        $booking[
                                            "subjects"
                                        ]
                                        ??
                                        "—"
                                    ) ?>

                                </span>

                            </td>


                            <!-- CURRICULUM -->

                            <td>

                                <?= e(
                                    $booking[
                                        "curriculum"
                                    ]
                                    ??
                                    "—"
                                ) ?>

                            </td>


                            <!-- CLASS -->

                            <td>

                                <?= e(
                                    $booking[
                                        "class_year"
                                    ]
                                    ??
                                    "—"
                                ) ?>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?= e(
                                    $date_display
                                ) ?>

                            </td>


                            <!-- TIME -->

                            <td>

                                <?= e(
                                    $time_display
                                ) ?>

                            </td>


                            <!-- PAYMENT -->

                            <td>

                                <?php if ($is_paid): ?>

                                    <span
                                        class="badge badge-paid"
                                    >

                                        ✓ Paid

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="badge badge-unpaid"
                                    >

                                        ●
                                        <?= e(
                                            $payment_status
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ASSIGNMENT -->

                            <td>

                                <?php if ($is_assigned): ?>

                                    <span
                                        class="
                                            badge
                                            badge-assigned
                                        "
                                    >

                                        ✓ Assigned

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            badge
                                            badge-unassigned
                                        "
                                    >

                                        Unassigned

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- TEACHER -->

                            <td>

                                <?php if ($is_assigned): ?>

                                    <div class="teacher-name">

                                        <?= e(
                                            $booking_teacher_name
                                            ?: "Assigned Teacher"
                                        ) ?>

                                    </div>

                                <?php else: ?>

                                    <div class="no-teacher">

                                        No teacher assigned

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- ACTION -->

                            <td class="action-cell">


                                <form
                                    method="POST"
                                    class="assign-form"
                                >


                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?= e(
                                            $booking["id"]
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="assign"
                                    >


                                    <select
                                        name="teacher_id"
                                        class="assign-select"
                                        required
                                    >

                                        <option value="">
                                            Select Teacher
                                        </option>


                                        <?php foreach (
                                            $teachers
                                            as $teacher
                                        ): ?>


                                            <option
                                                value="<?= e(
                                                    $teacher[
                                                        "teacher_id"
                                                    ]
                                                ) ?>"

                                                <?= (
                                                    $booking_teacher_id
                                                    ===
                                                    (int)$teacher[
                                                        "teacher_id"
                                                    ]
                                                )
                                                    ? "selected"
                                                    : "" ?>
                                            >

                                                <?= e(
                                                    $teacher[
                                                        "teacher_name"
                                                    ]
                                                ) ?>

                                            </option>


                                        <?php endforeach; ?>


                                    </select>


                                    <button
                                        type="submit"
                                        class="assign-button"
                                        onclick="
                                            return confirm(
                                                'Assign this teacher to the booking?'
                                            );
                                        "
                                    >

                                        <?= $is_assigned
                                            ? "Update"
                                            : "Assign" ?>

                                    </button>


                                </form>


                                <?php if ($is_assigned): ?>


                                    <form
                                        method="POST"
                                        style="
                                            margin-top:6px;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= e(
                                                $booking["id"]
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="unassign"
                                        >


                                        <button
                                            type="submit"
                                            class="unassign-button"
                                            onclick="
                                                return confirm(
                                                    'Remove this teacher assignment?'
                                                );
                                            "
                                        >

                                            Remove Teacher

                                        </button>

                                    </form>


                                <?php endif; ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="12"
                        >

                            <div class="empty">

                                <div class="empty-icon">
                                    📋
                                </div>

                                <h3>
                                    No bookings found
                                </h3>

                                <p>
                                    There are no bookings matching your current filters.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </div>


</div>


</body>

</html>
