<?php
$host = "localhost";     // Usually localhost
$user = "root";          // SQLyog default user
$pass = "";              // Your SQLyog password (if any)
$db = "research_repository";

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>