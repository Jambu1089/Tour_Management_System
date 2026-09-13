<?php
$serverName = "localhost";
$userName   = "root";
$password   = "";
$dbName     = "tour_management_db";

function dbConnection() {
    global $serverName, $userName, $password, $dbName;
    
    $conn = mysqli_connect($serverName, $userName, $password, $dbName);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    return $conn;
}
?>