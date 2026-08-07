<?php

session_start();

if (!isset($_SESSION['teacher_id'])) {

    header("Location: teacher_login.php");
    exit();

}

?>
