<?php
/**
 * Check Session Validity
 * 
 * Returns JSON response indicating if current session is still valid
 * Called by session_checker.js every 2 seconds from authenticated pages
 */

header('Content-Type: application/json');
session_start();

$response = array('valid' => false);

// Check if user is logged in
if (!isset($_SESSION['studentID']) || !isset($_SESSION['sessionToken'])) {
    echo json_encode($response);
    exit();
}

include_once "../database/database.php";

$studentID = $_SESSION['studentID'];
$currentToken = $_SESSION['sessionToken'];

// Query database for current session token
$stmt = $conn->prepare("SELECT sessionToken FROM login_sessions WHERE studentID = ? LIMIT 1");
if (!$stmt) {
    echo json_encode($response);
    exit();
}

$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $databaseToken = $row['sessionToken'];
    
    // Check if tokens match
    if ($currentToken === $databaseToken) {
        $response['valid'] = true;
    }
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
