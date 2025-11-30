<?php
session_start();  // Start session for user login tracking
include("../database/database.php");  // Your database connection

// Receive POST values
$studentID = $_POST['studentID'];
$password = $_POST['password'];

// Validate required fields
if (empty($studentID) || empty($password)) {
    die("Please fill in all fields.");
}

// Check if user exists
$stmt = $conn->prepare("SELECT password FROM registration WHERE studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Invalid Student ID or Password.");
}

// Fetch hashed password
$row = $result->fetch_assoc();
$hashedPassword = $row['password'];

// Verify password
if (password_verify($password, $hashedPassword)) {

    $_SESSION['studentID'] = $studentID;  // Store session
    $conn->close();
    // Redirect to landing page
    header("Location: ../home/home.php");
    exit();
} else {
    die("Invalid Student ID or Password.");
}

?>