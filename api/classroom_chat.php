<?php

session_start();

header('Content-Type: application/json');

require "../config/db.php";


function json_response(
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
| USER
|--------------------------------------------------------------------------
*/

$role = '';

$userId = '';

$userName = '';


if (
    isset($_SESSION['teacher_logged_in']) &&
    $_SESSION['teacher_logged_in'] === true
) {

    $role = 'teacher';

    $userId =
        (string)$_SESSION['teacher_id'];

    $userName =
        $_SESSION['teacher_name']
        ?? 'Teacher';

}

elseif (
    isset($_SESSION['student_logged_in']) &&
    $_SESSION['student_logged_in'] === true
) {

    $role = 'student';

    $userId =
        (string)$_SESSION['student_id'];

    $userName =
        $_SESSION['student_name']
        ?? 'Student';

}

else {

    json_response(
        false,
        [],
        'You are not logged in.'
    );
}


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

    json_response(
        false,
        [],
        'Invalid classroom.'
    );
}


/*
|--------------------------------------------------------------------------
| SEND MESSAGE
|--------------------------------------------------------------------------
*/

if ($action === 'send') {

    $message =
        trim(
            $_POST['message']
            ?? ''
        );


    if ($message === '') {

        json_response(
            false,
            [],
            'Message cannot be empty.'
        );
    }


    if (strlen($message) > 1000) {

        json_response(
            false,
            [],
            'Message is too long.'
        );
    }


    try {

        $stmt = $pdo->prepare("
            INSERT INTO classroom_messages
            (
                room_id,
                sender_role,
                sender_id,
                sender_name,
                message
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
            $userName,
            $message
        ]);


        json_response(
            true,
            [],
            'Message sent.'
        );


    } catch (PDOException $e) {

        json_response(
            false,
            [],
            'Unable to send message.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| GET MESSAGES
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
                sender_name,
                message,
                created_at

            FROM classroom_messages

            WHERE room_id = ?
            AND id > ?

            ORDER BY id ASC

            LIMIT 100
        ");


        $stmt->execute([
            $roomId,
            $lastId
        ]);


        $messages =
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );


        json_response(
            true,
            [
                'messages' =>
                    $messages
            ]
        );


    } catch (PDOException $e) {

        json_response(
            false,
            [],
            'Unable to load chat.'
        );
    }
}


json_response(
    false,
    [],
    'Invalid chat request.'
);
