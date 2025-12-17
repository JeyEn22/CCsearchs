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

<!-- Preview publication modal functionality -->
<script>
function previewPublication(element) {
    const filePath = element.getAttribute('data-filepath');
    const title = element.getAttribute('data-title');
    const author = element.getAttribute('data-author');
    const publishDate = element.getAttribute('data-date');
    const abstract = element.getAttribute('data-abstract');
    const department = element.getAttribute('data-department');
    const type = element.getAttribute('data-type');
    const thumbnail = element.getAttribute('data-thumbnail');

    console.log('previewPublication called with:', {filePath, title, author, publishDate, abstract, department, type, thumbnail});

    // Create modal with proper CSS classes
    const modal = document.createElement('div');
    modal.id = 'previewModal';
    modal.className = 'modal';

    const formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    let html = '<div class="modal-content preview-modal-content">';
    html += '<div class="modal-header">';
    html += '<h3>' + title + '</h3>';
    html += '<span class="close-modal" onclick="closePreviewModal()">&times;</span>';
    html += '</div>';
    html += '<div class="modal-body">';
    html += '<div class="preview-content-wrapper">';

    // Thumbnail on left side
    if (thumbnail) {
        html += '<div class="preview-thumbnail-container">';
        html += '<img src="../' + thumbnail + '?t=' + Date.now() + '" alt="Document preview" class="preview-thumbnail">';
        html += '</div>';
    }

    // Publication details on right side
    html += '<div class="preview-details-container">';
    html += '<div class="publication-details">';
    html += '<div class="detail-row"><strong>Author:</strong> <span>' + author + '</span></div>';
    html += '<div class="detail-row"><strong>Published:</strong> <span>' + formattedDate + '</span></div>';

    if (department) {
        html += '<div class="detail-row"><strong>Department:</strong> <span>' + department + '</span></div>';
    }
    if (type) {
        html += '<div class="detail-row"><strong>Type:</strong> <span>' + type + '</span></div>';
    }
    html += '</div>';

    // Abstract section (separated from publication details)
    html += '<div class="abstract-section">';
    html += '<div class="abstract-label"><strong>Abstract:</strong></div>';
    html += '<div class="abstract-text">' + (abstract || 'No abstract available.') + '</div>';
    html += '</div>';
    html += '</div>'; // Close preview-details-container
    html += '</div>'; // Close preview-content-wrapper

    // Action buttons
    html += '<div class="preview-actions">';
    html += '<a href="../' + filePath + '" target="_blank" class="btn btn-primary">';
    html += '<i class="fas fa-external-link-alt"></i> View Full Document';
    html += '</a>';
    html += '<a href="../' + filePath + '" download class="btn btn-secondary">';
    html += '<i class="fas fa-download"></i> Download';
    html += '</a>';
    html += '</div>';

    html += '</div>';
    html += '</div>';

    modal.innerHTML = html;
    document.body.appendChild(modal);
    modal.style.display = 'flex';

    // Close modal when clicking outside the modal content
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closePreviewModal();
        }
    });
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.remove();
    }
}
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
