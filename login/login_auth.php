<?php
header('Content-Type: application/json');
session_start();
include("../database/database.php");

// Initialize response
$response = array('status' => 'error', 'message' => 'Unknown error');

// Receive POST values
$studentID = isset($_POST['studentID']) ? trim($_POST['studentID']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate required fields
if (empty($studentID) || empty($password)) {
    $response['message'] = 'Please fill in all fields.';
    echo json_encode($response);
    exit();
}

// Create login_sessions table if it doesn't exist
$createTableSQL = "
    CREATE TABLE IF NOT EXISTS `login_sessions` (
      `sessionID` int(11) NOT NULL AUTO_INCREMENT,
      `studentID` varchar(10) NOT NULL,
      `sessionToken` varchar(255) NOT NULL,
      `ipAddress` varchar(45) DEFAULT NULL,
      `userAgent` varchar(255) DEFAULT NULL,
      `loginTime` timestamp DEFAULT CURRENT_TIMESTAMP,
      `lastActivity` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`sessionID`),
      KEY `idx_studentID` (`studentID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";
if (!$conn->query($createTableSQL)) {
    error_log("Failed to create login_sessions table: " . $conn->error);
}

// Check if user exists
$stmt = $conn->prepare("SELECT password FROM registration WHERE studentID = ?");
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Invalid Student ID or Password.';
    echo json_encode($response);
    exit();
}

// Fetch hashed password
$row = $result->fetch_assoc();
$hashedPassword = $row['password'];
$stmt->close();

// Verify password
if (password_verify($password, $hashedPassword)) {
    // Check for existing active sessions
    $sessionCheckStmt = $conn->prepare("SELECT sessionID FROM login_sessions WHERE studentID = ?");
    if (!$sessionCheckStmt) {
        error_log("Prepare failed: " . $conn->error);
        $response['message'] = 'Database error. Please try again.';
        echo json_encode($response);
        exit();
    }
    
    $sessionCheckStmt->bind_param("s", $studentID);
    $sessionCheckStmt->execute();
    $sessionResult = $sessionCheckStmt->get_result();
    
    if ($sessionResult->num_rows > 0) {
        // Account is already logged in, force logout the old user
        // Delete the existing session
        $deleteStmt = $conn->prepare("DELETE FROM login_sessions WHERE studentID = ?");
        if ($deleteStmt) {
            $deleteStmt->bind_param("s", $studentID);
            $deleteStmt->execute();
            $deleteStmt->close();
        }
        
        // Create notification table if it doesn't exist
        $notifTableSQL = "
            CREATE TABLE IF NOT EXISTS `notifications` (
              `notificationID` int(11) NOT NULL AUTO_INCREMENT,
              `recipientID` varchar(10) NOT NULL,
              `senderID` varchar(10),
              `type` varchar(50) DEFAULT 'login_attempt',
              `relatedID` varchar(50),
              `message` text NOT NULL,
              `is_read` tinyint(1) DEFAULT 0,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`notificationID`),
              KEY `fk_notification_recipient` (`recipientID`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
        ";
        $conn->query($notifTableSQL);
        
        // Get current user's IP and browser info
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $browserInfo = substr($userAgent, 0, 100);
        
        // Send notification to the logged-in user
        $notifMessage = "⚠️ SECURITY ALERT: Someone logged into your account from IP: {$ipAddress} | Device: {$browserInfo}. If this wasn't you, CHANGE YOUR PASSWORD IMMEDIATELY!";
        $insertNotifStmt = $conn->prepare("INSERT INTO notifications (recipientID, type, message) VALUES (?, 'login_alert', ?)");
        if ($insertNotifStmt) {
            $insertNotifStmt->bind_param("ss", $studentID, $notifMessage);
            $insertNotifStmt->execute();
            $insertNotifStmt->close();
        }
    }
    
    $sessionCheckStmt->close();
    
    // Create new session token
    $sessionToken = bin2hex(random_bytes(32));
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Insert new session record
    $insertSessionStmt = $conn->prepare("INSERT INTO login_sessions (studentID, sessionToken, ipAddress, userAgent) VALUES (?, ?, ?, ?)");
    if (!$insertSessionStmt) {
        error_log("Insert prepare failed: " . $conn->error);
        $response['message'] = 'Login failed. Please try again.';
        echo json_encode($response);
        exit();
    }
    
    $insertSessionStmt->bind_param("ssss", $studentID, $sessionToken, $ipAddress, $userAgent);
    if (!$insertSessionStmt->execute()) {
        error_log("Insert execute failed: " . $insertSessionStmt->error);
        $response['message'] = 'Login failed. Please try again.';
        echo json_encode($response);
        exit();
    }
    $insertSessionStmt->close();
    
    // Set session variables
    $_SESSION['studentID'] = $studentID;
    $_SESSION['sessionToken'] = $sessionToken;
    
    $response['status'] = 'success';
    $response['message'] = 'Let\'s go!';
    echo json_encode($response);
} else {
    $response['message'] = 'Invalid Student ID or Password.';
    echo json_encode($response);
}

$conn->close();
?>