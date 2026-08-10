<?php

require "../teacher_auth.php";
require "../config/db.php";


/* =========================================================
   TEACHER INFORMATION
========================================================= */

$teacher_id = $_SESSION['teacher_id'] ?? '';
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';


if (empty($teacher_id)) {

    header("Location: login.php");
    exit;

}


/* =========================================================
   SCHEDULE FILE
========================================================= */

$data_directory = dirname(__DIR__) . "/data";
$schedule_file = $data_directory . "/schedules.json";


/*
|--------------------------------------------------------------------------
| Create data folder if it doesn't exist
|--------------------------------------------------------------------------
*/

if (!is_dir($data_directory)) {

    mkdir(
        $data_directory,
        0777,
        true
    );

}


/*
|--------------------------------------------------------------------------
| Create JSON file if it doesn't exist
|--------------------------------------------------------------------------
*/

if (!file_exists($schedule_file)) {

    file_put_contents(
        $schedule_file,
        json_encode([], JSON_PRETTY_PRINT)
    );

}


/* =========================================================
   READ EXISTING SCHEDULES
========================================================= */

$schedules = [];

$json = file_get_contents($schedule_file);

if ($json !== false && trim($json) !== "") {

    $decoded = json_decode(
        $json,
        true
    );

    if (is_array($decoded)) {

        $schedules = $decoded;

    }

}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   GET TEACHER'S ASSIGNED BOOKINGS
========================================================= */

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            booking_reference,
            student_name,
            email,
            phone,
            curriculum,
            class_year,
            subjects,
            amount,
            payment_status,
            teacher_id,
            teacher_name,
            assignment_status
        FROM bookings
        WHERE teacher_id = ?
        ORDER BY student_name ASC
    ");

    $stmt->execute([
        $teacher_id
    ]);

    $students = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


} catch (PDOException $e) {

    die(
        "Unable to load assigned students: "
        . htmlspecialchars(
            $e->getMessage()
        )
    );

}


/* =========================================================
   CREATE 8 LESSONS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['create_schedule'])
) {

    $booking_id = intval(
        $_POST['booking_id'] ?? 0
    );

    $start_date = trim(
        $_POST['start_date'] ?? ''
    );

    $day_one = trim(
        $_POST['day_one'] ?? ''
    );

    $day_two = trim(
        $_POST['day_two'] ?? ''
    );

    $lesson_time = trim(
        $_POST['lesson_time'] ?? ''
    );


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $booking_id <= 0 ||
        $start_date === "" ||
        $day_one === "" ||
        $day_two === "" ||
        $lesson_time === ""
    ) {

        $message =
            "Please complete all schedule fields.";

        $message_type = "error";

    } elseif ($day_one === $day_two) {

        $message =
            "Please select two different lesson days.";

        $message_type = "error";

    } else {


        /* =================================================
           FIND BOOKING BELONGING TO THIS TEACHER
        ================================================= */

        try {

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    booking_reference,
                    student_name,
                    email,
                    curriculum,
                    class_year,
                    subjects,
                    teacher_id,
                    teacher_name
                FROM bookings
                WHERE id = ?
                AND teacher_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $booking_id,
                $teacher_id
            ]);

            $booking = $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        } catch (PDOException $e) {

            $booking = false;

            $message =
                "Unable to verify booking.";

            $message_type = "error";

        }


        if (!$booking) {

            $message =
                "This booking is not assigned to you.";

            $message_type = "error";

        } else {


            /* =============================================
               CHECK EXISTING SCHEDULE
            ============================================= */

            $already_scheduled = false;

            foreach ($schedules as $existing) {

                if (
                    isset(
                        $existing['booking_id']
                    )
                    &&
                    (int)$existing['booking_id']
                    === $booking_id
                ) {

                    $already_scheduled = true;

                    break;

                }

            }


            if ($already_scheduled) {

                $message =
                    "This student already has an 8-lesson schedule.";

                $message_type = "error";

            } else {


                /* =========================================
                   VALIDATE DATE
                ========================================= */

                $date_object = DateTime::createFromFormat(
                    'Y-m-d',
                    $start_date
                );


                if (
                    !$date_object
                    ||
                    $date_object->format('Y-m-d')
                    !== $start_date
                ) {

                    $message =
                        "Please enter a valid starting date.";

                    $message_type = "error";

                } else {


                    /* =====================================
                       DAY NUMBER
                    ===================================== */

                    $day_numbers = [

                        'Sunday' => 0,

                        'Monday' => 1,

                        'Tuesday' => 2,

                        'Wednesday' => 3,

                        'Thursday' => 4,

                        'Friday' => 5,

                        'Saturday' => 6

                    ];


                    /*
                     * We sort the two selected days so the
                     * lessons always appear chronologically.
                     */

                    $selected_days = [

                        $day_numbers[$day_one],
                        $day_numbers[$day_two]

                    ];


                    sort(
                        $selected_days
                    );


                    /*
                     * Convert numbers back to names.
                     */

                    $number_to_day = array_flip(
                        $day_numbers
                    );


                    $selected_day_names = [

                        $number_to_day[
                            $selected_days[0]
                        ],

                        $number_to_day[
                            $selected_days[1]
                        ]

                    ];


                    /* =====================================
                       GENERATE 8 LESSONS
                    ===================================== */

                    $new_lessons = [];


                    $lesson_number = 1;


                    /*
                     * Start searching from the selected
                     * starting date.
                     */

                    $current_date = new DateTime(
                        $start_date
                    );


                    /*
                     * Generate 8 lessons.
                     *
                     * Each week has 2 lessons.
                     */

                    for (
                        $week = 0;
                        $week < 4;
                        $week++
                    ) {


                        foreach (
                            $selected_days
                            as $day_number
                        ) {


                            /*
                             * Create a fresh date based
                             * on the starting date.
                             */

                            $lesson_date = new DateTime(
                                $start_date
                            );


                            /*
                             * Move to the beginning of
                             * the appropriate week.
                             */

                            $lesson_date->modify(
                                "+".($week * 7)." days"
                            );


                            /*
                             * Calculate the difference
                             * between current weekday and
                             * requested weekday.
                             */

                            $current_day =
                                (int)$lesson_date->format(
                                    'w'
                                );


                            $difference =
                                $day_number
                                -
                                $current_day;


                            /*
                             * For the first week, if the
                             * selected day has already
                             * passed the starting date,
                             * move to the following week.
                             */

                            if (
                                $week === 0
                                &&
                                $difference < 0
                            ) {

                                $difference += 7;

                            }


                            $lesson_date->modify(
                                ($difference >= 0 ? "+" : "")
                                . $difference
                                . " days"
                            );


                            /*
                             * Make sure the date is not
                             * before the selected start.
                             */

                            if (
                                $lesson_date < $current_date
                            ) {

                                $lesson_date->modify(
                                    "+7 days"
                                );

                            }


                            $new_lessons[] = [

                                'lesson_id' =>
                                    uniqid(
                                        'lesson_',
                                        true
                                    ),

                                'booking_id' =>
                                    $booking_id,

                                'booking_reference' =>
                                    $booking[
                                        'booking_reference'
                                    ],

                                'teacher_id' =>
                                    $teacher_id,

                                'teacher_name' =>
                                    $teacher_name,

                                'student_name' =>
                                    $booking[
                                        'student_name'
                                    ],

                                'student_email' =>
                                    $booking[
                                        'email'
                                    ],

                                'subjects' =>
                                    $booking[
                                        'subjects'
                                    ],

                                'curriculum' =>
                                    $booking[
                                        'curriculum'
                                    ],

                                'class_year' =>
                                    $booking[
                                        'class_year'
                                    ],

                                'lesson_number' =>
                                    $lesson_number,

                                'lesson_date' =>
                                    $lesson_date->format(
                                        'Y-m-d'
                                    ),

                                'lesson_time' =>
                                    $lesson_time,

                                'lesson_status' =>
                                    'Scheduled',

                                'created_at' =>
                                    date(
                                        'Y-m-d H:i:s'
                                    )

                            ];


                            $lesson_number++;

                        }

                    }


                    /* =====================================
                       SAVE SCHEDULE
                    ===================================== */

                    foreach (
                        $new_lessons
                        as $lesson
                    ) {

                        $schedules[] = $lesson;

                    }


                    $saved = file_put_contents(

                        $schedule_file,

                        json_encode(
                            $schedules,
                            JSON_PRETTY_PRINT
                            | JSON_UNESCAPED_SLASHES
                        ),

                        LOCK_EX

                    );


                    if ($saved !== false) {

                        $message =
                            "8 lessons have been scheduled successfully.";

                        $message_type = "success";

                    } else {

                        $message =
                            "Unable to save the schedule. Please check that the data folder is writable.";

                        $message_type = "error";

                    }

                }

            }

        }

    }

}


/* =========================================================
   UPDATE LESSON STATUS
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST['update_status'])
) {

    $lesson_id = trim(
        $_POST['lesson_id'] ?? ''
    );

    $new_status = trim(
        $_POST['lesson_status'] ?? ''
    );


    $allowed_statuses = [

        'Scheduled',

        'Completed',

        'Cancelled'

    ];


    if (
        $lesson_id === ""
        ||
        !in_array(
            $new_status,
            $allowed_statuses,
            true
        )
    ) {

        $message =
            "Invalid lesson status.";

        $message_type = "error";

    } else {


        $updated = false;


        foreach (
            $schedules
            as &$lesson
        ) {


            if (
                isset(
                    $lesson['lesson_id']
                )
                &&
                $lesson['lesson_id']
                === $lesson_id
                &&
                (string)$lesson['teacher_id']
                === (string)$teacher_id
            ) {

                $lesson[
                    'lesson_status'
                ] = $new_status;

                $updated = true;

                break;

            }

        }


        unset($lesson);


        if ($updated) {

            file_put_contents(

                $schedule_file,

                json_encode(
                    $schedules,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                ),

                LOCK_EX

            );


            $message =
                "Lesson status updated.";

            $message_type = "success";

        } else {

            $message =
                "Lesson not found.";

            $message_type = "error";

        }

    }

}


/* =========================================================
   FILTER TEACHER'S SCHEDULES
========================================================= */

$teacher_schedules = [];


foreach (
    $schedules
    as $lesson
) {

    if (
        isset(
            $lesson['teacher_id']
        )
        &&
        (string)$lesson['teacher_id']
        === (string)$teacher_id
    ) {

        $teacher_schedules[] =
            $lesson;

    }

}


/*
 * Sort by date and time
 */

usort(
    $teacher_schedules,
    function (
        $a,
        $b
    ) {

        $date_a =
            ($a['lesson_date'] ?? '')
            . ' '
            . ($a['lesson_time'] ?? '');

        $date_b =
            ($b['lesson_date'] ?? '')
            . ' '
            . ($b['lesson_time'] ?? '');

        return strcmp(
            $date_a,
            $date_b
        );

    }
);


/* =========================================================
   STATISTICS
========================================================= */

$total_lessons =
    count($teacher_schedules);


$completed_lessons = 0;

$scheduled_lessons = 0;

$cancelled_lessons = 0;


foreach (
    $teacher_schedules
    as $lesson
) {

    $status =
        $lesson['lesson_status']
        ?? 'Scheduled';


    if (
        $status === 'Completed'
    ) {

        $completed_lessons++;

    } elseif (
        $status === 'Cancelled'
    ) {

        $cancelled_lessons++;

    } else {

        $scheduled_lessons++;

    }

}


/* =========================================================
   TODAY'S LESSONS
========================================================= */

$today = date(
    'Y-m-d'
);


$today_lessons = [];


foreach (
    $teacher_schedules
    as $lesson
) {

    if (
        ($lesson['lesson_date'] ?? '')
        === $today
    ) {

        $today_lessons[] =
            $lesson;

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
My Schedule | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    font-family: Arial, sans-serif;

    background: #eef3f8;

    color: #333;

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


.logo {

    text-align: center;

    font-size: 19px;

    font-weight: bold;

    line-height: 1.5;

    margin-bottom: 35px;

}


.menu a {

    display: block;

    color: white;

    text-decoration: none;

    padding: 13px;

    margin-bottom: 7px;

    border-radius: 7px;

}


.menu a:hover {

    background: #0055a5;

}


.menu a.active {

    background: #0055a5;

}


/* =====================================================
   MAIN
===================================================== */

.main {

    margin-left: 240px;

    padding: 30px;

}


/* =====================================================
   TOP BAR
===================================================== */

.topbar {

    background: white;

    padding: 20px;

    border-radius: 10px;

    margin-bottom: 25px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


.topbar h2 {

    margin: 0;

    color: #003366;

}


.teacher {

    color: #666;

}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.page-header h1 {

    margin: 0 0 8px;

    color: #003366;

}


.page-header p {

    margin: 0;

    color: #666;

}


/* =====================================================
   MESSAGE
===================================================== */

.message {

    padding: 14px;

    border-radius: 7px;

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


/* =====================================================
   STATISTICS
===================================================== */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 20px;

    margin-bottom: 25px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.stat-card h3 {

    margin: 0;

    font-size: 30px;

    color: #003366;

}


.stat-card p {

    margin: 8px 0 0;

    color: #777;

}


/* =====================================================
   CREATE SCHEDULE
===================================================== */

.create-box {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.create-box h2 {

    margin-top: 0;

    color: #003366;

}


.form-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

}


.form-group label {

    display: block;

    font-weight: bold;

    margin-bottom: 7px;

}


.form-group select,
.form-group input {

    width: 100%;

    padding: 11px;

    border: 1px solid #ccc;

    border-radius: 6px;

    font-size: 14px;

}


.create-button {

    margin-top: 20px;

    padding: 12px 22px;

    border: none;

    background: #003366;

    color: white;

    border-radius: 6px;

    cursor: pointer;

    font-weight: bold;

}


.create-button:hover {

    background: #0055a5;

}


/* =====================================================
   INFO
===================================================== */

.info-box {

    background: #f4f9ff;

    border-left: 5px solid #003366;

    padding: 15px;

    margin-top: 15px;

    border-radius: 5px;

    color: #555;

}


/* =====================================================
   SCHEDULE
===================================================== */

.schedule-box {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 4px 12px rgba(0,0,0,.06);

}


.schedule-box h2 {

    margin-top: 0;

    color: #003366;

}


.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1000px;

}


th {

    background: #003366;

    color: white;

    padding: 12px;

    text-align: left;

    white-space: nowrap;

}


td {

    padding: 12px;

    border-bottom: 1px solid #ddd;

    vertical-align: middle;

}


tr:hover {

    background: #f7faff;

}


/* =====================================================
   BADGES
===================================================== */

.badge {

    display: inline-block;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: bold;

}


.scheduled {

    background: #cfe2ff;

    color: #084298;

}


.completed {

    background: #d4edda;

    color: #155724;

}


.cancelled {

    background: #f8d7da;

    color: #721c24;

}


/* =====================================================
   STATUS FORM
===================================================== */

.status-form {

    display: flex;

    gap: 6px;

}


.status-form select {

    padding: 7px;

    border: 1px solid #ccc;

    border-radius: 5px;

}


.status-form button {

    padding: 7px 12px;

    background: #003366;

    color: white;

    border: none;

    border-radius: 5px;

    cursor: pointer;

}


/* =====================================================
   EMPTY
===================================================== */

.empty {

    text-align: center;

    padding: 45px;

    color: #777;

}


.empty-icon {

    font-size: 45px;

    margin-bottom: 10px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;

    }


    .main {

        margin-left: 0;

        padding: 15px;

    }


    .topbar {

        flex-direction: column;

        align-items: flex-start;

        gap: 10px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

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


        <a
            href="schedule.php"
            class="active"
        >

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


<!-- =====================================================
     MAIN
===================================================== -->

<div class="main">


    <div class="topbar">


        <h2>

            My Schedule

        </h2>


        <div class="teacher">

            Welcome,

            <strong>

                <?php

                echo htmlspecialchars(
                    $teacher_name
                );

                ?>

            </strong>

        </div>


    </div>



    <div class="page-header">


        <h1>

            📅 Lesson Schedule

        </h1>


        <p>

            Each student receives

            <strong>
                2 lessons per week
            </strong>

            for a total of

            <strong>
                8 lessons per month.
            </strong>

        </p>


    </div>



    <?php if ($message !== ""): ?>


        <div class="message
            <?php echo htmlspecialchars(
                $message_type
            ); ?>"
        >

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =================================================
         STATISTICS
    ================================================= -->

    <div class="stats">


        <div class="stat-card">

            <h3>

                <?php

                echo $total_lessons;

                ?>

            </h3>

            <p>
                Total Lessons
            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $scheduled_lessons;

                ?>

            </h3>

            <p>
                Scheduled
            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $completed_lessons;

                ?>

            </h3>

            <p>
                Completed
            </p>

        </div>


        <div class="stat-card">

            <h3>

                <?php

                echo $cancelled_lessons;

                ?>

            </h3>

            <p>
                Cancelled
            </p>

        </div>


    </div>



    <!-- =================================================
         CREATE 8-LESSON SCHEDULE
    ================================================= -->

    <div class="create-box">


        <h2>

            ➕ Create 8-Lesson Schedule

        </h2>


        <p>

            Select a student, starting date,
            two lesson days and the lesson time.

            The system will automatically create
            8 lessons.

        </p>


        <form
            method="POST"
        >


            <div class="form-grid">


                <!-- STUDENT -->

                <div class="form-group">

                    <label>

                        Student

                    </label>


                    <select
                        name="booking_id"
                        required
                    >

                        <option value="">

                            Select Student

                        </option>


                        <?php foreach (
                            $students
                            as $student
                        ): ?>


                            <?php

                            /*
                             * Check whether this student
                             * already has a schedule.
                             */

                            $has_schedule = false;


                            foreach (
                                $schedules
                                as $existing
                            ) {

                                if (

                                    isset(
                                        $existing[
                                            'booking_id'
                                        ]
                                    )

                                    &&

                                    (int)$existing[
                                        'booking_id'
                                    ]
                                    ===
                                    (int)$student['id']

                                ) {

                                    $has_schedule =
                                        true;

                                    break;

                                }

                            }


                            ?>


                            <option
                                value="<?php
                                    echo (int)
                                        $student['id'];
                                ?>"
                                <?php
                                if (
                                    $has_schedule
                                ) {
                                    echo "disabled";
                                }
                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $student[
                                        'student_name'
                                    ]
                                );

                                ?>


                                <?php

                                if (
                                    $has_schedule
                                ) {

                                    echo " - Schedule Created";

                                }

                                ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>



                <!-- START DATE -->

                <div class="form-group">

                    <label>

                        Starting Date

                    </label>


                    <input
                        type="date"
                        name="start_date"
                        required
                    >


                </div>



                <!-- DAY ONE -->

                <div class="form-group">

                    <label>

                        First Lesson Day

                    </label>


                    <select
                        name="day_one"
                        required
                    >

                        <option value="">

                            Select Day

                        </option>


                        <option value="Monday">
                            Monday
                        </option>


                        <option value="Tuesday">
                            Tuesday
                        </option>


                        <option value="Wednesday">
                            Wednesday
                        </option>


                        <option value="Thursday">
                            Thursday
                        </option>


                        <option value="Friday">
                            Friday
                        </option>


                        <option value="Saturday">
                            Saturday
                        </option>


                        <option value="Sunday">
                            Sunday
                        </option>


                    </select>


                </div>



                <!-- DAY TWO -->

                <div class="form-group">

                    <label>

                        Second Lesson Day

                    </label>


                    <select
                        name="day_two"
                        required
                    >

                        <option value="">

                            Select Day

                        </option>


                        <option value="Monday">
                            Monday
                        </option>


                        <option value="Tuesday">
                            Tuesday
                        </option>


                        <option value="Wednesday">
                            Wednesday
                        </option>


                        <option value="Thursday">
                            Thursday
                        </option>


                        <option value="Friday">
                            Friday
                        </option>


                        <option value="Saturday">
                            Saturday
                        </option>


                        <option value="Sunday">
                            Sunday
                        </option>


                    </select>


                </div>



                <!-- TIME -->

                <div class="form-group">

                    <label>

                        Lesson Time

                    </label>


                    <input
                        type="time"
                        name="lesson_time"
                        required
                    >


                </div>


            </div>



            <button
                type="submit"
                name="create_schedule"
                class="create-button"
            >

                Create 8-Lesson Schedule

            </button>


        </form>



        <div class="info-box">

            <strong>

                Example:

            </strong>

            If you select

            <strong>
                Monday & Thursday
            </strong>

            at

            <strong>
                4:00 PM
            </strong>,

            the system will create 8 lessons
            across four weeks.

        </div>


    </div>



    <!-- =================================================
         TODAY'S LESSONS
    ================================================= -->

    <div class="schedule-box">


        <h2>

            📅 Today's Lessons

        </h2>


        <?php if (
            count($today_lessons) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>

                        <th>
                            Time
                        </th>

                        <th>
                            Student
                        </th>

                        <th>
                            Subject
                        </th>

                        <th>
                            Lesson
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>


                    <?php foreach (
                        $today_lessons
                        as $lesson
                    ): ?>


                        <tr>


                            <td>

                                <?php

                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $lesson[
                                            'lesson_time'
                                        ]
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $lesson[
                                            'student_name'
                                        ]
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                Lesson

                                <?php

                                echo (int)
                                    $lesson[
                                        'lesson_number'
                                    ];

                                ?>

                                of 8

                            </td>


                            <td>


                                <?php

                                $status =
                                    $lesson[
                                        'lesson_status'
                                    ]
                                    ?? 'Scheduled';


                                if (
                                    $status ===
                                    'Completed'
                                ) {

                                    echo '

                                    <span
                                        class="badge completed"
                                    >

                                        Completed

                                    </span>

                                    ';

                                } elseif (
                                    $status ===
                                    'Cancelled'
                                ) {

                                    echo '

                                    <span
                                        class="badge cancelled"
                                    >

                                        Cancelled

                                    </span>

                                    ';

                                } else {

                                    echo '

                                    <span
                                        class="badge scheduled"
                                    >

                                        Scheduled

                                    </span>

                                    ';

                                }

                                ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    📅

                </div>


                <h3>

                    No Lessons Today

                </h3>


                <p>

                    You have no lessons scheduled
                    for today.

                </p>


            </div>


        <?php endif; ?>


    </div>



    <!-- =================================================
         ALL LESSONS
    ================================================= -->

    <div class="schedule-box">


        <h2>

            📚 All My Scheduled Lessons

        </h2>


        <?php if (
            count($teacher_schedules) > 0
        ): ?>


            <div class="table-wrapper">


                <table>


                    <tr>


                        <th>
                            Lesson
                        </th>


                        <th>
                            Student
                        </th>


                        <th>
                            Subject
                        </th>


                        <th>
                            Date
                        </th>


                        <th>
                            Time
                        </th>


                        <th>
                            Status
                        </th>


                        <th>
                            Update
                        </th>


                    </tr>


                    <?php foreach (
                        $teacher_schedules
                        as $lesson
                    ): ?>


                        <tr>


                            <td>

                                <strong>

                                    Lesson

                                    <?php

                                    echo (int)
                                        $lesson[
                                            'lesson_number'
                                        ];

                                    ?>

                                </strong>

                                <br>

                                <small>

                                    of 8

                                </small>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'student_name'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $lesson[
                                        'subjects'
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(

                                    date(
                                        "d M Y",
                                        strtotime(
                                            $lesson[
                                                'lesson_date'
                                            ]
                                        )
                                    )

                                );

                                ?>


                                <br>


                                <small>

                                    <?php

                                    echo htmlspecialchars(

                                        date(
                                            "l",
                                            strtotime(
                                                $lesson[
                                                    'lesson_date'
                                                ]
                                            )
                                        )

                                    );

                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(

                                    date(
                                        "h:i A",
                                        strtotime(
                                            $lesson[
                                                'lesson_time'
                                            ]
                                        )
                                    )

                                );

                                ?>

                            </td>


                            <td>


                                <?php

                                $status =
                                    $lesson[
                                        'lesson_status'
                                    ]
                                    ?? 'Scheduled';


                                if (
                                    $status ===
                                    'Completed'
                                ) {

                                    echo '

                                    <span
                                        class="badge completed"
                                    >

                                        Completed

                                    </span>

                                    ';

                                } elseif (
                                    $status ===
                                    'Cancelled'
                                ) {

                                    echo '

                                    <span
                                        class="badge cancelled"
                                    >

                                        Cancelled

                                    </span>

                                    ';

                                } else {

                                    echo '

                                    <span
                                        class="badge scheduled"
                                    >

                                        Scheduled

                                    </span>

                                    ';

                                }

                                ?>


                            </td>


                            <td>


                                <form
                                    method="POST"
                                    class="status-form"
                                >


                                    <input
                                        type="hidden"
                                        name="lesson_id"
                                        value="<?php
                                            echo htmlspecialchars(
                                                $lesson[
                                                    'lesson_id'
                                                ]
                                            );
                                        ?>"
                                    >


                                    <select
                                        name="lesson_status"
                                    >

                                        <option
                                            value="Scheduled"
                                            <?php
                                            if (
                                                ($lesson[
                                                    'lesson_status'
                                                ] ?? '')
                                                ===
                                                'Scheduled'
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            Scheduled

                                        </option>


                                        <option
                                            value="Completed"
                                            <?php
                                            if (
                                                ($lesson[
                                                    'lesson_status'
                                                ] ?? '')
                                                ===
                                                'Completed'
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            Completed

                                        </option>


                                        <option
                                            value="Cancelled"
                                            <?php
                                            if (
                                                ($lesson[
                                                    'lesson_status'
                                                ] ?? '')
                                                ===
                                                'Cancelled'
                                            ) {
                                                echo "selected";
                                            }
                                            ?>
                                        >

                                            Cancelled

                                        </option>


                                    </select>


                                    <button
                                        type="submit"
                                        name="update_status"
                                    >

                                        Save

                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                </table>


            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    📚

                </div>


                <h3>

                    No Schedule Created

                </h3>


                <p>

                    Select a student above and create
                    their 8-lesson monthly schedule.

                </p>


            </div>


        <?php endif; ?>


    </div>


</div>


</body>

</html>
