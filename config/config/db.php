<?php

$host="localhost";
$user="root";
$password="";
$database="nisel_online_education";

$conn=new mysqli($host,$user,$password,$database);

if($conn->connect_error){
    die("Database Connection Failed");
}

?>
