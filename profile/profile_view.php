<?php
session_start();
include "../database/database.php";

// Check if studentID parameter is provided
if (!isset($_GET['studentID'])) {
    die("Invalid profile.");
}

$studentID = $_GET['studentID'];

// Query user information from registration table
$sql = "SELECT * FROM registration WHERE studentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Profile not found.");
}

$user = $result->fetch_assoc();
$stmt->close();

// Track profile visit (log visitor)
if (isset($_SESSION['studentID']) && $_SESSION['studentID'] !== $studentID) {
    // Create profile_visits table if it doesn't exist
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `profile_visits` (
          `visitID` int(11) NOT NULL AUTO_INCREMENT,
          `profileOwnerID` varchar(10) NOT NULL,
          `visitorID` varchar(10),
          `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`visitID`),
          KEY `idx_profileOwner` (`profileOwnerID`),
          KEY `idx_visitor` (`visitorID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ";
    $conn->query($createTableSQL);
    
    // Record the visit
    $visitorID = $_SESSION['studentID'];
    $insertVisit = $conn->prepare("INSERT INTO profile_visits (profileOwnerID, visitorID) VALUES (?, ?)");
    $insertVisit->bind_param("ss", $studentID, $visitorID);
    $insertVisit->execute();
    $insertVisit->close();
}

// Get user's publications count
$uploadCount = 0;
try {
    $uploadStmt = $conn->prepare("SELECT COUNT(*) as upload_count FROM publications WHERE studentID = ?");
    $uploadStmt->bind_param("s", $studentID);
    $uploadStmt->execute();
    $uploadResult = $uploadStmt->get_result();
    $uploadRow = $uploadResult->fetch_assoc();
    $uploadCount = $uploadRow['upload_count'];
    $uploadStmt->close();
} catch (Exception $e) {
    $uploadCount = 0;
}

// Get count of how many people have favorited this user
$favoritesCount = 0;
try {
    $favStmt = $conn->prepare("SELECT COUNT(*) as fav_count FROM favorite_authors WHERE favorite_studentID = ?");
    $favStmt->bind_param("s", $studentID);
    $favStmt->execute();
    $favResult = $favStmt->get_result();
    $favRow = $favResult->fetch_assoc();
    $favoritesCount = $favRow['fav_count'];
    $favStmt->close();
} catch (Exception $e) {
    $favoritesCount = 0;
}

// Get user's publications
$userPublications = [];
try {
    $pubStmt = $conn->prepare("
        SELECT p.*, r.firstName, r.lastName
        FROM publications p
        JOIN registration r ON p.studentID = r.studentID
        WHERE p.studentID = ?
        ORDER BY p.publicationID DESC
    ");
    $pubStmt->bind_param("s", $studentID);
    $pubStmt->execute();
    $pubResult = $pubStmt->get_result();
    
    while ($pub = $pubResult->fetch_assoc()) {
        $userPublications[] = $pub;
    }
    $pubStmt->close();
} catch (Exception $e) {
    $userPublications = [];
}

// Set layout variables
$pageTitle = htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) . '\'s Profile';
$activeNav = 'authors';
$additionalCSS = ['../profile/profile_page.css', '../home/home_page.css', '../publication/publication_page.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Profile Banner -->
<div class="profile-banner"></div>

<!-- Main Section with Background -->
<section class="content">
    
    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-image">
            <img src="<?php echo htmlspecialchars(isset($user['profileImage']) && !empty($user['profileImage']) ? '../' . $user['profileImage'] : '../uploads/profile.png'); ?>" alt="User" id="profilePic">
        </div>

        <h3><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></h3>
        <p>Uploaded Documents: <strong><?php echo $uploadCount; ?></strong></p>
        <p>Favorites by: <strong><?php echo $favoritesCount; ?></strong></p>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <div class="tabs">
            <button class="tab active" data-tab="uploaded">Uploaded Documents</button>
        </div>

        <div class="tab-content" id="uploaded">
            <div class="card-grid">
                <?php if (!empty($userPublications)): ?>
                    <?php foreach ($userPublications as $pub): ?>
                        <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                            <?php
                            $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                            $imageSrc .= '?t=' . time(); // Cache busting
                            ?>
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                            <div class="card-info">
                                <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                                <div class="posted-by">
                                    Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                                </div>
                                <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                                <div class="card-actions">
                                    <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open empty-icon"></i>
                        <h3>No publications yet</h3>
                        <p>This author hasn't published any documents.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</section>

<?php
$conn->close();
?>

<!-- Back Button -->
<div class="back-btn" onclick="window.history.back()" style="margin-top: 10px; margin-left: 0px; margin-bottom: 20px; font-size: 14px; cursor: pointer; width: fit-content; color: #333; transition: opacity 0.3s ease; display: inline-block; padding: 8px 12px; border-radius: 4px;">
    <span>&larr; Back</span>
</div>
<style>
  .back-btn:hover {
    background-color: #f0f0f0;
    opacity: 0.8;
  }
</style>

<!-- Preview handled by centralized script: ../assets/js/preview.js -->
<!-- No inline JS required; global functions are provided by /assets/js/preview.js -->
<script src="../assets/js/preview.js"></script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
