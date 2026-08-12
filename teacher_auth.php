<?php

/*
|--------------------------------------------------------------------------
| NISEL ONLINE EDUCATION
| TEACHER AUTHENTICATION
|--------------------------------------------------------------------------
|
| This file protects all teacher pages.
|
| Required session variables:
|
|   teacher_id
|   teacher_name
|   teacher_email
|   teacher_logged_in
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CHECK TEACHER LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['teacher_id']) ||
    empty($_SESSION['teacher_id'])
) {

    header(
        "Location: /online/teacher/login.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET SESSION INFORMATION
|--------------------------------------------------------------------------
*/

$teacher_id =
    $_SESSION['teacher_id'];

$teacher_name =
    $_SESSION['teacher_name']
    ?? 'Teacher';

$teacher_email =
    $_SESSION['teacher_email']
    ?? '';

$teacher_logged_in =
    $_SESSION['teacher_logged_in']
    ?? true;


/*
|--------------------------------------------------------------------------
| FINAL SECURITY CHECK
|--------------------------------------------------------------------------
*/

if (empty($teacher_id)) {

    session_unset();

    session_destroy();

    header(
        "Location: /online/teacher/login.php"
    );

    exit;
}
