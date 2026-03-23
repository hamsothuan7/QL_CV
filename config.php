<?php 

$server = "localhost";
$user = "root";
$pass = "";
//$pass = "Pasw@rd1473";
$database = "quanlyduan";

$conn = mysqli_connect($server, $user, $pass, $database);

if (!$conn) {
    die("<script>alert('Connection Failed.')</script>");
}

?>