<?php
// connection.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pcldb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    throw new Exception("Database connection error", 500);
}

$conn->set_charset("utf8mb4");
?>