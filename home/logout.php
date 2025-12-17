<?php
session_start();
include("../database/database.php");

// Get the current studentID before destroying the session
$studentID = isset($_SESSION['studentID']) ? $_SESSION['studentID'] : null;

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
      KEY `idx_studentID` (`studentID`),
      CONSTRAINT `fk_sessions_student` FOREIGN KEY (`studentID`) REFERENCES `registration` (`studentID`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
";
$conn->query($createTableSQL);

// Remove session from database
if ($studentID) {
    try {
        $deleteSessionStmt = $conn->prepare("DELETE FROM login_sessions WHERE studentID = ?");
        if ($deleteSessionStmt) {
            $deleteSessionStmt->bind_param("s", $studentID);
            $deleteSessionStmt->execute();
            $deleteSessionStmt->close();
        }
    } catch (Exception $e) {
        // Silently continue even if deletion fails
    }
    $conn->close();
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header("Location: ../login/login.php");
exit();
?>