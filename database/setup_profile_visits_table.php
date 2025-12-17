<?php
include("database.php");

$sql = "
CREATE TABLE IF NOT EXISTS `profile_visits` (
  `visitID` int(11) NOT NULL AUTO_INCREMENT,
  `profileOwnerID` varchar(10) NOT NULL,
  `visitorID` varchar(10),
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`visitID`),
  KEY `idx_profileOwner` (`profileOwnerID`),
  KEY `idx_visitor` (`visitorID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
";

if ($conn->query($sql) === TRUE) {
    echo "✅ profile_visits table created successfully!";
} else {
    echo "❌ Error creating table: " . $conn->error;
}

$conn->close();
?>
