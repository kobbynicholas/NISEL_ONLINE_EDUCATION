<?php
session_start();

require "../config/db.php";

/*
|--------------------------------------------------------------------------
| TEACHER AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION["teacher_logged_in"]) ||
    $_SESSION["teacher_logged_in"] !== true
) {
    header("Location: login.php");
    exit;
}

$teacherId = (int)($_SESSION["teacher_id"] ?? 0);

if ($teacherId <= 0) {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

$classId = (int)(
    $_GET["id"]
    ?? $_POST["class_id"]
    ?? 0
);


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function h($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| GET TEACHER'S LIVE CLASSES
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            subject
        FROM live_classes
        WHERE teacher_id = ?
        ORDER BY id DESC
    ");

    $stmt->execute([
        $teacherId
    ]);

    $classes = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | IF A CLASS WAS SELECTED
    |--------------------------------------------------------------------------
    */

    $class = null;
    $items = [];

    if ($classId > 0) {

        /*
        |--------------------------------------------------------------------------
        | VERIFY CLASS BELONGS TO TEACHER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                title,
                subject
            FROM live_classes
            WHERE id = ?
            AND teacher_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $classId,
            $teacherId
        ]);

        $class = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if (!$class) {

            $error = "The selected class was not found or does not belong to you.";

            $classId = 0;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | HANDLE POST REQUESTS
    |--------------------------------------------------------------------------
    */

    if (
        $_SERVER["REQUEST_METHOD"] === "POST"
        && $classId > 0
        && $class
    ) {

        $action = $_POST["action"] ?? "";


        /*
        |--------------------------------------------------------------------------
        | ADD RECORDING
        |--------------------------------------------------------------------------
        */

        if ($action === "add") {

            $title = trim(
                $_POST["title"] ?? ""
            );

            $url = trim(
                $_POST["recording_url"] ?? ""
            );

            $date = trim(
                $_POST["recording_date"] ?? ""
            );

            $duration = (int)(
                $_POST["duration_minutes"] ?? 0
            );


            if ($title === "") {

                throw new RuntimeException(
                    "Recording title is required."
                );

            }


            if ($url === "") {

                throw new RuntimeException(
                    "Recording URL is required."
                );

            }


            if (!filter_var(
                $url,
                FILTER_VALIDATE_URL
            )) {

                throw new RuntimeException(
                    "Please enter a valid recording URL."
                );

            }


            /*
            |--------------------------------------------------------------------------
            | INSERT RECORDING
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO live_class_recordings
                (
                    live_class_id,
                    teacher_id,
                    title,
                    recording_url,
                    recording_date,
                    duration_minutes
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");


            $stmt->execute([
                $classId,
                $teacherId,
                $title,
                $url,
                $date !== ""
                    ? $date
                    : null,
                $duration > 0
                    ? $duration
                    : null
            ]);


            $message = "Recording added successfully.";
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE RECORDING
        |--------------------------------------------------------------------------
        */

        if ($action === "delete") {

            $recordingId = (int)(
                $_POST["recording_id"] ?? 0
            );


            if ($recordingId <= 0) {

                throw new RuntimeException(
                    "Invalid recording."
                );

            }


            $stmt = $pdo->prepare("
                DELETE FROM live_class_recordings
                WHERE id = ?
                AND live_class_id = ?
                AND teacher_id = ?
            ");


            $stmt->execute([
                $recordingId,
                $classId,
                $teacherId
            ]);


            if ($stmt->rowCount() > 0) {

                $message =
                    "Recording removed successfully.";

            } else {

                $error =
                    "Recording could not be removed.";

            }
        }


        /*
        |--------------------------------------------------------------------------
        | GET RECORDINGS
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT *
            FROM live_class_recordings
            WHERE live_class_id = ?
            AND teacher_id = ?
            ORDER BY
                recording_date DESC,
                id DESC
        ");

        $stmt->execute([
            $classId,
            $teacherId
        ]);

        $items = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

} catch (Throwable $e) {

    $error = $e->getMessage();

    if (!isset($classes)) {
        $classes = [];
    }

    if (!isset($items)) {
        $items = [];
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
    Recordings | NISEL ONLINE EDUCATION
</title>


<style>

* {
    box-sizing: border-box;
}


body {

    margin: 0;

    background: #f4f7fb;

    color: #102a43;

    font-family:
        Inter,
        Arial,
        Helvetica,
        sans-serif;
}


.wrap {

    width: min(
        1100px,
        94%
    );

    margin: 32px auto;
}


/* ==========================================
   TOP
========================================== */

.top {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 15px;

    margin-bottom: 22px;
}


h1 {

    margin:
        4px 0;

    font-size: 28px;

}


.muted {

    font-size: 11px;

    color: #718397;
}


.btn {

    display: inline-block;

    border: 0;

    border-radius: 9px;

    padding:
        10px 14px;

    background:
        #063b66;

    color: #fff;

    text-decoration: none;

    font-size: 11px;

    font-weight: 850;

    cursor: pointer;
}


.btn:hover {

    background:
        #07558f;

}


.delete {

    background:
        #a12e25;

}


.delete:hover {

    background:
        #7d1f19;

}


/* ==========================================
   ALERTS
========================================== */

.alert {

    padding: 12px;

    border-radius: 9px;

    font-size: 11px;

    margin-bottom: 15px;
}


.ok {

    background:
        #e9f8f1;

    color:
        #13734f;
}


.err {

    background:
        #fff0ef;

    color:
        #a12e25;
}


/* ==========================================
   CLASS GRID
========================================== */

.class-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(
                260px,
                1fr
            )
        );

    gap: 18px;
}


.class-card {

    background: #fff;

    border:
        1px solid #e5ebf2;

    border-radius: 16px;

    padding: 22px;

    box-shadow:
        0 12px 35px
        rgba(
            16,
            42,
            67,
            .06
        );
}


.class-card h2 {

    margin:
        0 0 8px;

    font-size: 18px;

}


.subject {

    color:
        #0877c9;

    font-weight: 700;

    font-size: 12px;

    margin-bottom: 18px;
}


.class-card .btn {

    width: 100%;

    text-align: center;
}


/* ==========================================
   RECORDING AREA
========================================== */

.grid {

    display: grid;

    grid-template-columns:
        330px 1fr;

    gap: 18px;
}


.card {

    background: #fff;

    border:
        1px solid #e5ebf2;

    border-radius: 18px;

    padding: 20px;

    box-shadow:
        0 12px 35px
        rgba(
            16,
            42,
            67,
            .06
        );
}


.card h2 {

    font-size: 16px;

    margin-top: 0;
}


/* ==========================================
   FORM
========================================== */

label {

    display: block;

    font-size: 9px;

    font-weight: 900;

    color: #53697c;

    margin:
        10px 0 6px;
}


input {

    width: 100%;

    padding: 10px;

    border:
        1px solid #dce5ec;

    border-radius: 9px;

    font: inherit;

    font-size: 11px;
}


input:focus {

    outline: none;

    border-color:
        #0877c9;

    box-shadow:
        0 0 0 3px
        rgba(
            8,
            119,
            201,
            .10
        );
}


/* ==========================================
   RECORDING ITEM
========================================== */

.item {

    padding: 14px;

    border:
        1px solid #e7edf2;

    border-radius: 11px;

    margin-bottom: 9px;
}


.row {

    display: flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap: 10px;
}


.item strong {

    font-size: 13px;
}


.item a.watch {

    display: inline-block;

    margin-top: 10px;

    color:
        #0a74b6;

    font-weight: 800;

    font-size: 11px;

    text-decoration: none;
}


.empty {

    padding: 30px;

    text-align: center;

    color: #718397;

    font-size: 12px;
}


/* ==========================================
   MOBILE
========================================== */

@media(max-width:800px) {

    .grid {

        grid-template-columns:
            1fr;
    }


    .top {

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .row {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

}

</style>

</head>


<body>


<div class="wrap">


<?php if ($classId <= 0): ?>


    <!-- =====================================================
         SELECT CLASS
    ====================================================== -->

    <div class="top">

        <div>

            <h1>
                Class Recordings
            </h1>

            <div class="muted">

                Select a live class to manage
                its recordings.

            </div>

        </div>


        <a
            class="btn"
            href="live_classes.php"
        >
            ← Live Classes
        </a>

    </div>


    <?php if ($message): ?>

        <div class="alert ok">

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert err">

            <?= h($error) ?>

        </div>

    <?php endif; ?>


    <?php if (!$classes): ?>


        <div class="card">

            <div class="empty">

                <div
                    style="
                        font-size:40px;
                        margin-bottom:10px;
                    "
                >
                    🎥
                </div>

                You have no live classes yet.

                <br><br>

                <a
                    href="live_classes.php"
                    class="btn"
                >
                    Go to Live Classes
                </a>

            </div>

        </div>


    <?php else: ?>


        <div class="class-grid">


            <?php foreach ($classes as $c): ?>


                <div class="class-card">

                    <h2>

                        <?= h($c["title"]) ?>

                    </h2>


                    <div class="subject">

                        <?= h($c["subject"]) ?>

                    </div>


                    <a
                        class="btn"
                        href="recordings.php?id=<?= (int)$c["id"] ?>"
                    >

                        🎥 Manage Recordings

                    </a>

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


<?php else: ?>


    <!-- =====================================================
         SELECTED CLASS
    ====================================================== -->


    <div class="top">

        <div>

            <h1>
                Class Recordings
            </h1>

            <?php if ($class): ?>

                <div class="muted">

                    <?= h($class["title"]) ?>

                    ·

                    <?= h($class["subject"]) ?>

                </div>

            <?php endif; ?>

        </div>


        <a
            class="btn"
            href="recordings.php"
        >

            ← All Classes

        </a>

    </div>


    <?php if ($message): ?>

        <div class="alert ok">

            <?= h($message) ?>

        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert err">

            <?= h($error) ?>

        </div>

    <?php endif; ?>


    <?php if ($class): ?>


        <div class="grid">


            <!-- =================================================
                 ADD RECORDING
            ================================================== -->

            <div class="card">

                <h2>
                    Add Recording
                </h2>


                <form
                    method="POST"
                    action=""
                >

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >


                    <input
                        type="hidden"
                        name="class_id"
                        value="<?= $classId ?>"
                    >


                    <label>
                        TITLE
                    </label>

                    <input
                        type="text"
                        name="title"
                        placeholder="Physics Lesson 4"
                        required
                    >


                    <label>
                        RECORDING URL
                    </label>

                    <input
                        type="url"
                        name="recording_url"
                        placeholder="https://..."
                        required
                    >


                    <label>
                        RECORDING DATE
                    </label>

                    <input
                        type="datetime-local"
                        name="recording_date"
                    >


                    <label>
                        DURATION (MINUTES)
                    </label>

                    <input
                        type="number"
                        min="1"
                        name="duration_minutes"
                        placeholder="60"
                    >


                    <button
                        class="btn"
                        type="submit"
                        style="margin-top:12px"
                    >

                        ➕ Add Recording

                    </button>

                </form>

            </div>


            <!-- =================================================
                 RECORDINGS
            ================================================== -->

            <div class="card">

                <h2>
                    Available Recordings
                </h2>


                <?php if (!$items): ?>


                    <div class="empty">

                        🎥

                        <br><br>

                        No recordings have been
                        added for this class yet.

                    </div>


                <?php else: ?>


                    <?php foreach ($items as $r): ?>


                        <div class="item">


                            <div class="row">


                                <strong>

                                    <?= h(
                                        $r["title"]
                                    ) ?>

                                </strong>


                                <form
                                    method="POST"
                                    onsubmit="
                                        return confirm(
                                            'Remove this recording?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >


                                    <input
                                        type="hidden"
                                        name="recording_id"
                                        value="<?= (int)$r["id"] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="class_id"
                                        value="<?= $classId ?>"
                                    >


                                    <button
                                        class="btn delete"
                                        type="submit"
                                    >

                                        Delete

                                    </button>

                                </form>


                            </div>


                            <div class="muted">

                                <?php

                                if (
                                    !empty(
                                        $r["recording_date"]
                                    )
                                ) {

                                    echo h(
                                        date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $r["recording_date"]
                                            )
                                        )
                                    );

                                } else {

                                    echo "Date not specified";

                                }

                                ?>

                                ·

                                <?php

                                if (
                                    !empty(
                                        $r["duration_minutes"]
                                    )
                                ) {

                                    echo (int)
                                        $r[
                                            "duration_minutes"
                                        ]
                                        . " minutes";

                                } else {

                                    echo
                                        "Duration not specified";

                                }

                                ?>

                            </div>


                            <a
                                class="watch"
                                href="<?= h(
                                    $r["recording_url"]
                                ) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >

                                ▶ Watch Recording

                            </a>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </div>


    <?php endif; ?>


<?php endif; ?>


</div>


</body>

</html>
