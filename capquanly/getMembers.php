<?php
// getMembersByDepartment.php
include('../config.php');

$pb_ma = $_GET['pb_ma'];

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Updated query to include the condition where active is not equal to 1
$query = "SELECT TV_MA, TV_TEN FROM `thanhvien` WHERE `PB_MA` = '$pb_ma' AND `active` != 1";
$result = mysqli_query($conn, $query);

// Initialize an array to hold the members
$members = [];
while ($row = mysqli_fetch_assoc($result)) {
    $members[] = $row;
}

// Close the database connection
mysqli_close($conn);

// Output the members as a JSON response
echo json_encode($members);
?>
