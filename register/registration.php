<?php
// Enable error reporting to help debug
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
include("../database/database.php");  // Connect to database

// Check database connection
if (!$conn) {
    $response = array('status' => 'error', 'message' => 'Database connection error');
    echo json_encode($response);
    exit();
}

// Initialize response
$response = array('status' => 'error', 'message' => 'Unknown error');

// Receive POST values
$firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$contactNumber = isset($_POST['contactNumber']) ? trim($_POST['contactNumber']) : '';
$emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
$currentAddress = isset($_POST['currentAddress']) ? trim($_POST['currentAddress']) : '';
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$studentID = isset($_POST['studentID']) ? trim($_POST['studentID']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

// Validate required fields
$requiredFields = [
    'firstName' => $firstName,
    'lastName' => $lastName,
    'contactNumber' => $contactNumber,
    'emailAddress' => $emailAddress,
    'currentAddress' => $currentAddress,
    'department' => $department,
    'studentID' => $studentID,
    'password' => $password,
    'confirmPassword' => $confirmPassword
];

$missingFields = [];
foreach ($requiredFields as $fieldName => $fieldValue) {
    if (empty($fieldValue)) {
        $missingFields[] = $fieldName;
    }
}

if (!empty($missingFields)) {
    $response['message'] = 'The following fields are required: ' . implode(', ', $missingFields);
    echo json_encode($response);
    exit();
}

// Validate password match
if ($password !== $confirmPassword) {
    $response['message'] = 'Passwords do not match.';
    echo json_encode($response);
    exit();
}

// Validate password strength (minimum 6 characters as per form)
if (strlen($password) < 6) {
    $response['message'] = 'Password must be at least 6 characters long.';
    echo json_encode($response);
    exit();
}

if (!preg_match('/[A-Z]/', $password)) {
    $response['message'] = 'Password must contain at least one uppercase letter.';
    echo json_encode($response);
    exit();
}

if (!preg_match('/\d/', $password)) {
    $response['message'] = 'Password must contain at least one number.';
    echo json_encode($response);
    exit();
}

// Validate email format
if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit();
}

// Validate contact number (allow various phone number formats)
if (!preg_match('/^[0-9+\-\s]{7,15}$/', $contactNumber)) {
    $response['message'] = 'Please enter a valid contact number (7-15 digits, may include +, -, spaces).';
    echo json_encode($response);
    exit();
}

// Validate student ID format (should be in format like 2023-00001 or similar)
if (!preg_match('/^\d{4}-\d{5}$/', $studentID)) {
    $response['message'] = 'Student ID must be in format YYYY-XXXXX (e.g., 2023-00001).';
    echo json_encode($response);
    exit();
}

// Check if studentID exists in student_information table
$checkStudentExists = $conn->prepare("SELECT studentID FROM student_information WHERE studentID = ?");
$checkStudentExists->bind_param("s", $studentID);
$checkStudentExists->execute();
$studentExistsResult = $checkStudentExists->get_result();

if ($studentExistsResult->num_rows === 0) {
    $response['message'] = 'Student ID not found in the system. Please contact your administrator.';
    echo json_encode($response);
    exit();
}
$checkStudentExists->close();

// Check if email is already registered
$checkEmail = $conn->prepare("SELECT emailAddress FROM registration WHERE emailAddress = ?");
$checkEmail->bind_param("s", $emailAddress);
$checkEmail->execute();
$emailResult = $checkEmail->get_result();

if ($emailResult->num_rows > 0) {
    $response['status'] = 'error';
    $response['message'] = 'Email is already registered. Please use a different email or login to your existing account.';
    $checkEmail->close();
    echo json_encode($response);
    exit();
}
$checkEmail->close();

// Check if studentID is already registered
$checkStudentID = $conn->prepare("SELECT studentID FROM registration WHERE studentID = ?");
$checkStudentID->bind_param("s", $studentID);
$checkStudentID->execute();
$studentResult = $checkStudentID->get_result();

if ($studentResult->num_rows > 0) {
    $response['status'] = 'error';
    $response['message'] = 'Student ID is already registered. Please contact support if you need help accessing your account.';
    $checkStudentID->close();
    echo json_encode($response);
    exit();
}
$checkStudentID->close();

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into registration table with all fields
$insert = $conn->prepare("INSERT INTO registration (firstName, lastName, contactNumber, emailAddress, currentAddress, department, studentID, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

if (!$insert) {
    $response['status'] = 'error';
    $response['message'] = 'Prepare failed: ' . $conn->error;
    echo json_encode($response);
    exit();
}

$insert->bind_param("ssssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $studentID, $hashedPassword);

if ($insert->execute()) {
    // Create profile entry automatically after successful registration
    // If this fails, registration still succeeds
    $profileInsert = $conn->prepare("INSERT INTO user_profiles (studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, theme_preference) VALUES (?, ?, ?, ?, ?, ?, ?, 'light')");
    if ($profileInsert) {
        $profileInsert->bind_param("sssssss", $studentID, $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department);
        $profileInsert->execute();
        $profileInsert->close();
    }

    $response['status'] = 'success';
    $response['message'] = 'Registration successful! Redirecting to login...';
    echo json_encode($response);
    exit();
} else {
    $response['status'] = 'error';
    $response['message'] = 'Registration failed. Error: ' . $insert->error;
    echo json_encode($response);
    exit();
}
?>