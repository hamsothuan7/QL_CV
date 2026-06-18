<?php 

$server = "localhost";
$user = "root";
$pass = "";
//$pass = "Pasw@rd1473";
$database = "quanlyduan";

$conn = mysqli_connect($server, $user, $pass, $database);

if (!$conn) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        die(json_encode(['status' => false, 'message' => 'Connection Failed: ' . mysqli_connect_error()]));
    }
    die("Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

?>