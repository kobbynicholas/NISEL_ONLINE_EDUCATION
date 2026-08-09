<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CHECK TEACHER LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['teacher_logged_in']) ||
    $_SESSION['teacher_logged_in'] !== true ||
    empty($_SESSION['teacher_id'])
) {

    header("Location: teacher/login.php");
    exit();

}
