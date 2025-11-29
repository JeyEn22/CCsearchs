<?php
include("../database/database.php");  // Connect to database

// Receive POST values
$studentID = $_POST['studentID'];
$emailAddress = $_POST['emailAddress'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];

// Validate required fields
if (empty($studentID) || empty($emailAddress) || empty($password) || empty($confirmPassword)) {
    die("All fields are required.");
}

// Validate password match
if ($password !== $confirmPassword) {
    die("Passwords do not match.");
}

// Check if student ID exists
$checkStudent = $conn->prepare("SELECT * FROM student_information WHERE studentID = ?");
$checkStudent->bind_param("s", $studentID);
$checkStudent->execute();
$result = $checkStudent->get_result();

if ($result->num_rows == 0) {
    die("Invalid Student ID. Please contact admin.");
}

// Optional: check if email is already registered
$checkEmail = $conn->prepare("SELECT emailAddress FROM registration WHERE emailAddress = ?");
$checkEmail->bind_param("s", $emailAddress);
$checkEmail->execute();
$emailResult = $checkEmail->get_result();

if ($emailResult->num_rows > 0) {
    die("Email is already registered.");
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into registration table
$insert = $conn->prepare("INSERT INTO registration (studentID, emailAddress, password) VALUES (?, ?, ?)");
$insert->bind_param("sss", $studentID, $emailAddress, $hashedPassword);

if ($insert->execute()) {
    // Redirect to login 
    header("Location: ../login/login.html");
    exit();
} else {
    echo "Error: " . $insert->error;
}

$conn->close();
?>