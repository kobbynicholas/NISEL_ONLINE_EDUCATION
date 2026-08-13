<?php

/*
=========================================================
NISEL ONLINE EDUCATION
ADMIN AUTHENTICATION
=========================================================
*/

/*
Only start a session if one is not already active.
This prevents:

"session_start(): A session is already active"
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
=========================================================
CHECK ADMIN LOGIN
=========================================================
*/

if (
    empty($_SESSION['admin_logged_in'])
    ||
    $_SESSION['admin_logged_in'] !== true
) {

    header("Location: login.php");
    exit;
}


/*
=========================================================
OPTIONAL ADMIN INFORMATION
=========================================================
*/

$admin_id =
    $_SESSION['admin_id']
    ?? null;

$admin_name =
    $_SESSION['admin_name']
    ?? 'Administrator';

$admin_email =
    $_SESSION['admin_email']
    ?? '';

?>
