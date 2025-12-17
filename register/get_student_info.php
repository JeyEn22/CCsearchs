<?php
header('Content-Type: application/json');
include("../database/database.php");

// Get student ID from request
$studentID = isset($_GET['studentID']) ? trim($_GET['studentID']) : '';

if (empty($studentID)) {
    echo json_encode(['status' => 'error', 'message' => 'Student ID is required']);
    exit();
}

// Query student_information table
$stmt = $conn->prepare("SELECT studentID, firstName, lastName, contactNumber, currentAddress, department FROM student_information WHERE studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'data' => $student]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Student ID not found']);
}

$stmt->close();
$conn->close();
?>
