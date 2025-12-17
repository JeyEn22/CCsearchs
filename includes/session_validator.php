<?php
/**
 * Session Validator
 * 
 * Include this file at the top of all authenticated pages to validate user sessions.
 * Detects if another user has logged in with the same account and forces logout with modal.
 * 
 * Usage:
 *   session_start();
 *   include '../includes/session_validator.php';
 */

// Only validate if user is logged in
if (isset($_SESSION['studentID']) && isset($_SESSION['sessionToken'])) {
    include_once "../database/database.php";
    
    $studentID = $_SESSION['studentID'];
    $currentToken = $_SESSION['sessionToken'];
    
    // Check if this session token is still valid in the database
    $stmt = $conn->prepare("SELECT sessionToken FROM login_sessions WHERE studentID = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $databaseToken = $row['sessionToken'];
            
            // If tokens don't match, this session has been invalidated by a new login
            if ($currentToken !== $databaseToken) {
                // Destroy the current session
                session_unset();
                session_destroy();
                
                // Set a flag to show the modal on login page
                session_start();
                $_SESSION['logout_reason'] = 'security_alert';
                
                // Redirect to login page with modal flag
                header("Location: ../login/login.php?reason=kicked_out");
                exit();
            }
        } else {
            // Session record not found in database, destroy session
            session_unset();
            session_destroy();
            
            session_start();
            $_SESSION['logout_reason'] = 'session_expired';
            
            header("Location: ../login/login.php?reason=session_expired");
            exit();
        }
        
        $stmt->close();
    }
}
?>
