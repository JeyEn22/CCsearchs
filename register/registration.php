<?php
header('Content-Type: application/json');
include("../database/database.php");  // Connect to database

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

// Validate password strength
if (strlen($password) < 8) {
    $response['message'] = 'Password must be at least 8 characters long.';
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

// Validate contact number (exactly 11 digits)
if (!preg_match('/^[0-9]{11}$/', $contactNumber)) {
    $response['message'] = 'Contact number must be exactly 11 digits.';
    echo json_encode($response);
    exit();
}

// Check if student ID exists in student_information table
$checkStudent = $conn->prepare("SELECT * FROM student_information WHERE studentID = ?");
$checkStudent->bind_param("s", $studentID);
$checkStudent->execute();
$result = $checkStudent->get_result();

if ($result->num_rows == 0) {
    $response['message'] = 'Invalid Student ID. Please contact admin.';
    echo json_encode($response);
    exit();
}

// Check if email is already registered
$checkEmail = $conn->prepare("SELECT emailAddress FROM registration WHERE emailAddress = ?");
$checkEmail->bind_param("s", $emailAddress);
$checkEmail->execute();
$emailResult = $checkEmail->get_result();

if ($emailResult->num_rows > 0) {
    $response['message'] = 'Email is already registered.';
    echo json_encode($response);
    exit();
}

// Check if studentID is already registered
$checkStudentID = $conn->prepare("SELECT studentID FROM registration WHERE studentID = ?");
$checkStudentID->bind_param("s", $studentID);
$checkStudentID->execute();
$studentResult = $checkStudentID->get_result();

if ($studentResult->num_rows > 0) {
    $response['message'] = 'Student ID is already registered.';
    echo json_encode($response);
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert into registration table with all fields
$insert = $conn->prepare("INSERT INTO registration (firstName, lastName, contactNumber, emailAddress, currentAddress, department, studentID, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$insert->bind_param("ssssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $studentID, $hashedPassword);

if ($insert->execute()) {
    // Create profile entry automatically after successful registration
    $profileInsert = $conn->prepare("INSERT INTO user_profiles (studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, theme_preference) VALUES (?, ?, ?, ?, ?, ?, ?, 'light')");
    $profileInsert->bind_param("sssssss", $studentID, $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department);

    if (!$profileInsert->execute()) {
        // Log error but don't fail registration
        error_log("Failed to create profile for studentID: $studentID - " . $conn->error);
    }
    $profileInsert->close();

    $response['status'] = 'success';
    $response['message'] = 'Registration successful!';
    echo json_encode($response);
} else {
    $response['message'] = 'Registration failed. Please try again.';
    echo json_encode($response);
}

$insert->close();
$checkStudent->close();
$checkEmail->close();
$checkStudentID->close();

$conn->close();
?>