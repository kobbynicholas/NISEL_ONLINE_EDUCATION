<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| WEBRTC CLASSROOM SIGNALING API
|--------------------------------------------------------------------------
*/

session_start();

header('Content-Type: application/json');

require "../config/db.php";


/*
|--------------------------------------------------------------------------
| RESPONSE HELPER
|--------------------------------------------------------------------------
*/

function response_json(
    bool $success,
    array $data = [],
    string $message = ''
) {

    echo json_encode([
        'success' => $success,
        'message' => $message,
        ...$data
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DETERMINE USER
|--------------------------------------------------------------------------
*/

$role = '';

$userId = '';

$userName = '';


/*
|--------------------------------------------------------------------------
| TEACHER
|--------------------------------------------------------------------------
*/

if (
    isset($_SESSION['teacher_logged_in']) &&
    $_SESSION['teacher_logged_in'] === true &&
    !empty($_SESSION['teacher_id'])
) {

    $role = 'teacher';

    $userId =
        (string)$_SESSION['teacher_id'];

    $userName =
        $_SESSION['teacher_name']
        ?? 'Teacher';
}


/*
|--------------------------------------------------------------------------
| STUDENT
|--------------------------------------------------------------------------
*/

elseif (
    isset($_SESSION['student_logged_in']) &&
    $_SESSION['student_logged_in'] === true &&
    !empty($_SESSION['student_id'])
) {

    $role = 'student';

    $userId =
        (string)$_SESSION['student_id'];

    $userName =
        $_SESSION['student_name']
        ?? 'Student';
}


/*
|--------------------------------------------------------------------------
| NOT LOGGED IN
|--------------------------------------------------------------------------
*/

else {

    response_json(
        false,
        [],
        'You are not logged in.'
    );
}


/*
|--------------------------------------------------------------------------
| REQUEST
|--------------------------------------------------------------------------
*/

$action =
    $_POST['action']
    ??
    $_GET['action']
    ??
    '';


$roomId =
    (int)(
        $_POST['room_id']
        ??
        $_GET['room_id']
        ??
        0
    );


if ($roomId <= 0) {

    response_json(
        false,
        [],
        'Invalid classroom.'
    );
}


/*
|--------------------------------------------------------------------------
| VERIFY CLASS EXISTS
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            id,
            title,
            subject,
            teacher_id
        FROM live_classes
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $roomId
    ]);

    $class =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$class) {

        response_json(
            false,
            [],
            'Classroom not found.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TEACHER SECURITY
    |--------------------------------------------------------------------------
    */

    if (
        $role === 'teacher' &&
        (string)$class['teacher_id']
        !==
        (string)$userId
    ) {

        response_json(
            false,
            [],
            'You are not authorised to access this classroom.'
        );
    }


} catch (PDOException $e) {

    response_json(
        false,
        [],
        'Unable to verify classroom.'
    );
}


/*
|--------------------------------------------------------------------------
| SEND SIGNAL
|--------------------------------------------------------------------------
*/

if ($action === 'send') {

    $signalType =
        trim(
            $_POST['signal_type']
            ?? ''
        );


    $signalData =
        $_POST['signal_data']
        ?? '';


    if (
        $signalType === '' ||
        $signalData === ''
    ) {

        response_json(
            false,
            [],
            'Invalid signal.'
        );
    }


    $allowedTypes = [
        'offer',
        'answer',
        'candidate',
        'ready',
        'leave'
    ];


    if (
        !in_array(
            $signalType,
            $allowedTypes,
            true
        )
    ) {

        response_json(
            false,
            [],
            'Invalid signal type.'
        );
    }


    try {

        $stmt = $pdo->prepare("
            INSERT INTO classroom_signals
            (
                room_id,
                sender_role,
                sender_id,
                signal_type,
                signal_data
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        $stmt->execute([
            $roomId,
            $role,
            $userId,
            $signalType,
            $signalData
        ]);


        response_json(
            true,
            [
                'signal_id' =>
                    $pdo->lastInsertId()
            ],
            'Signal sent.'
        );


    } catch (PDOException $e) {

        response_json(
            false,
            [],
            'Unable to send signal.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| POLL SIGNALS
|--------------------------------------------------------------------------
*/

if ($action === 'poll') {

    $lastId =
        (int)(
            $_POST['last_id']
            ??
            $_GET['last_id']
            ??
            0
        );


    try {

        $stmt = $pdo->prepare("
            SELECT
                id,
                sender_role,
                sender_id,
                signal_type,
                signal_data,
                created_at

            FROM classroom_signals

            WHERE room_id = ?
            AND id > ?

            ORDER BY id ASC

            LIMIT 100
        ");


        $stmt->execute([
            $roomId,
            $lastId
        ]);


        $signals =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        response_json(
            true,
            [
                'signals' => $signals
            ]
        );


    } catch (PDOException $e) {

        response_json(
            false,
            [],
            'Unable to retrieve classroom signals.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| CLEAN OLD SIGNALS
|--------------------------------------------------------------------------
*/

if ($action === 'cleanup') {

    /*
     * Only teacher can clean the classroom signaling
     * records.
     */

    if ($role !== 'teacher') {

        response_json(
            false,
            [],
            'Only the teacher can perform this action.'
        );
    }


    try {

        $stmt = $pdo->prepare("
            DELETE FROM classroom_signals
            WHERE room_id = ?
        ");


        $stmt->execute([
            $roomId
        ]);


        response_json(
            true,
            [],
            'Classroom signals cleared.'
        );


    } catch (PDOException $e) {

        response_json(
            false,
            [],
            'Unable to clear classroom signals.'
        );
    }
}


response_json(
    false,
    [],
    'Invalid classroom request.'
);
