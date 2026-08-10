<?php

session_start();

/* =========================================================
   DESTROY STUDENT SESSION
========================================================= */

$_SESSION = [];


/* Remove the session cookie */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/* Destroy session */

session_destroy();


/* =========================================================
   RETURN TO STUDENT LOGIN
========================================================= */

header("Location: login.php");
exit;

?>
