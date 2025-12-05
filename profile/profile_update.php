<?php
header('Content-Type: application/json');
session_start();
include("../database/database.php");

// Initialize response
$response = array('status' => 'error', 'message' => 'Unknown error');

// Check if user is logged in
if (!isset($_SESSION['studentID'])) {
    $response['message'] = 'User not authenticated';
    echo json_encode($response);
    exit();
}

$studentID = $_SESSION['studentID'];

try {
    // Debug: Log the received data
    error_log("Profile update request received for studentID: $studentID");
    error_log("POST data: " . print_r($_POST, true));

    // Check if profile is in public mode - prevent updates
    $publicCheck = $conn->prepare("SELECT is_public FROM user_profiles WHERE studentID = ?");
    if (!$publicCheck) {
        error_log("Failed to prepare public check query: " . $conn->error);
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit();
    }

    $publicCheck->bind_param("s", $studentID);
    if (!$publicCheck->execute()) {
        error_log("Failed to execute public check query: " . $publicCheck->error);
        $response['message'] = 'Database error';
        echo json_encode($response);
        exit();
    }

    $publicResult = $publicCheck->get_result();
    if ($publicResult->num_rows > 0) {
        $publicRow = $publicResult->fetch_assoc();
        if ($publicRow['is_public']) {
            $response['message'] = 'Cannot update profile in public view';
            echo json_encode($response);
            exit();
        }
    }
    $publicCheck->close();

    // Handle profile information update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
        $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
        $contactNumber = isset($_POST['contactNumber']) ? trim($_POST['contactNumber']) : '';
        $emailAddress = isset($_POST['emailAddress']) ? trim($_POST['emailAddress']) : '';
        $currentAddress = isset($_POST['currentAddress']) ? trim($_POST['currentAddress']) : '';
        $department = isset($_POST['department']) ? trim($_POST['department']) : '';

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($contactNumber) || empty($emailAddress) || empty($currentAddress) || empty($department)) {
            $response['message'] = 'All fields are required';
            echo json_encode($response);
            exit();
        }

        // Validate email format
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address';
            echo json_encode($response);
            exit();
        }

        // Validate contact number (exactly 11 digits)
        if (!preg_match('/^[0-9]{11}$/', $contactNumber)) {
            $response['message'] = 'Contact number must be exactly 11 digits';
            echo json_encode($response);
            exit();
        }

        // Check if email is already used by another user
        $checkEmail = $conn->prepare("SELECT studentID FROM user_profiles WHERE emailAddress = ? AND studentID != ?");
        $checkEmail->bind_param("ss", $emailAddress, $studentID);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();

        if ($emailResult->num_rows > 0) {
            $response['message'] = 'Email address is already in use by another account';
            echo json_encode($response);
            exit();
        }
        $checkEmail->close();

        // Handle profile image upload (optional)
        $profileImagePath = null;
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profileImage']['type'];
            $fileSize = $_FILES['profileImage']['size'];

            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF images are allowed.';
                echo json_encode($response);
                exit();
            }

            // Validate file size (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $response['message'] = 'File size too large. Maximum size is 5MB.';
                echo json_encode($response);
                exit();
            }

            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = $studentID . '_profile_' . time() . '.' . $fileExtension;
            $uploadDir = '../uploads/profiles/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $uploadPath = $uploadDir . $uniqueFilename;

            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadPath)) {
                $profileImagePath = 'uploads/profiles/' . $uniqueFilename;
                error_log("Profile image uploaded successfully: $profileImagePath");
            } else {
                $response['message'] = 'Failed to upload profile image';
                echo json_encode($response);
                exit();
            }
        }

        // Prepare update query with optional profile image
        if ($profileImagePath) {
            $updateStmt = $conn->prepare("UPDATE user_profiles SET firstName = ?, lastName = ?, contactNumber = ?, emailAddress = ?, currentAddress = ?, department = ?, profileImage = ?, updated_at = CURRENT_TIMESTAMP WHERE studentID = ?");
            $updateStmt->bind_param("ssssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $profileImagePath, $studentID);
        } else {
            $updateStmt = $conn->prepare("UPDATE user_profiles SET firstName = ?, lastName = ?, contactNumber = ?, emailAddress = ?, currentAddress = ?, department = ?, updated_at = CURRENT_TIMESTAMP WHERE studentID = ?");
            $updateStmt->bind_param("sssssss", $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $studentID);
        }

        if (!$updateStmt) {
            error_log("Failed to prepare update query: " . $conn->error);
            $response['message'] = 'Database error';
            echo json_encode($response);
            exit();
        }

        error_log("Executing update query with data: $firstName, $lastName, $contactNumber, $emailAddress, $currentAddress, $department, $studentID");

        if ($updateStmt->execute()) {
            $affectedRows = $updateStmt->affected_rows;
            error_log("Update successful, affected rows: $affectedRows");
            $response['status'] = 'success';
            $response['message'] = $profileImagePath ? 'Profile updated successfully!' : 'Profile updated successfully!';
            $response['updated_at'] = date('Y-m-d H:i:s');
            if ($profileImagePath) {
                $response['profile_image'] = $profileImagePath;
            }
        } else {
            error_log("Update failed: " . $updateStmt->error);
            $response['message'] = 'Failed to update profile: ' . $updateStmt->error;
        }

        $updateStmt->close();
    }
    // Handle public view toggle
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $action = $_GET['action'];

        if ($action === 'toggle_public') {
            // Get current is_public status
            $getStmt = $conn->prepare("SELECT is_public FROM user_profiles WHERE studentID = ?");
            $getStmt->bind_param("s", $studentID);
            $getStmt->execute();
            $result = $getStmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $currentStatus = $row['is_public'];
                $newStatus = !$currentStatus;

                // Update is_public status
                $updateStmt = $conn->prepare("UPDATE user_profiles SET is_public = ? WHERE studentID = ?");
                $updateStmt->bind_param("is", $newStatus, $studentID);

                if ($updateStmt->execute()) {
                    $response['status'] = 'success';
                    $response['message'] = $newStatus ? 'Profile is now public' : 'Profile is now private';
                    $response['is_public'] = $newStatus;
                } else {
                    $response['message'] = 'Failed to update profile visibility';
                }

                $updateStmt->close();
            }
            $getStmt->close();
        }
    }
    else {
        $response['message'] = 'Invalid request method';
    }

} catch (Exception $e) {
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
