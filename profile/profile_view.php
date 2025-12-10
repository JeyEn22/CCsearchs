<?php
session_start();
include "../database/database.php";
include "../database/notifications.php";

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

// Create notification for profile visit (if visitor is logged in and not visiting own profile)
if (isset($_SESSION['studentID']) && $_SESSION['studentID'] !== $studentID) {
    notifyProfileVisit($studentID, $_SESSION['studentID']);
}

// Get uploaded documents count
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
    error_log("Error getting upload count: " . $e->getMessage());
}

// Get favorites count (how many people favorited this user)
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
    // Table might not exist yet, default to 0
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
    error_log("Error getting user publications: " . $e->getMessage());
}

// Set layout variables
$pageTitle = 'Publications by ' . htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$activeNav = 'publication';
$additionalCSS = ['../profile/profile_page.css', '../publication/publication_page.css'];
$additionalScripts = ['../profile/profile.js'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Profile Banner Background -->
<div class="profile-banner"></div>

<!-- Main Section with Background -->
<section class="content">
    <div class="profile-card">
        <div class="profile-image">
            <img src="<?php echo htmlspecialchars(isset($user['profileImage']) && !empty($user['profileImage']) ? '../' . $user['profileImage'] : '../uploads/profile.png'); ?>" alt="User" id="profilePic">
        </div>

        <h3><?php echo htmlspecialchars((isset($user['firstName']) ? $user['firstName'] : 'Unknown') . ' ' . (isset($user['lastName']) ? $user['lastName'] : 'User')); ?></h3>
        <p>Uploaded Documents: <strong><?php echo $uploadCount; ?></strong></p>
        <p>Favorites by: <strong><?php echo $favoritesCount; ?></strong></p>
    </div>
    <div class="info-section">
        <div class="tabs">
            <button class="tab active" data-tab="personal">Personal Information</button>
            <button class="tab" data-tab="uploaded">Uploaded Documents</button>
        </div>

        <div class="tab-content" id="personal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="<?php echo htmlspecialchars(isset($user['lastName']) ? $user['lastName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="<?php echo htmlspecialchars(isset($user['firstName']) ? $user['firstName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contactNumber" value="<?php echo htmlspecialchars(isset($user['contactNumber']) ? $user['contactNumber'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="emailAddress" value="<?php echo htmlspecialchars(isset($user['emailAddress']) ? $user['emailAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <input type="text" name="currentAddress" value="<?php echo htmlspecialchars(isset($user['currentAddress']) ? $user['currentAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars(isset($user['department']) ? $user['department'] : ''); ?>" readonly />
                </div>
            </div>
        </div>

        <div class="tab-content hidden" id="uploaded">
            <div class="card-grid">
                <?php if (!empty($userPublications)): ?>
                    <?php foreach ($userPublications as $pub): ?>
                        <div class="card" 
                             data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" 
                             data-title="<?php echo htmlspecialchars($pub['title']); ?>" 
                             data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" 
                             data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" 
                             data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" 
                             data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" 
                             data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" 
                             data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>"
                             onclick="previewPublication(this)">
                            <?php
                            $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                            $imageSrc .= '?t=' . time(); // Cache busting
                            ?>
                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                            <div class="card-info">
                                <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
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
                        <p>This user hasn't uploaded any documents.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$conn->close();

// Include layout footer
include "../layout/layout_footer.php";
?>

<script>
    // Publication preview functionality
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
        if (abstract) {
            html += '<div class="abstract-section">';
            html += '<div class="abstract-label"><strong>Abstract:</strong></div>';
            html += '<div class="abstract-text">' + abstract + '</div>';
            html += '</div>';
        }
        html += '</div>'; // Close preview-details-container
        html += '</div>'; // Close preview-content-wrapper

        // Action buttons (fixed at bottom)
        html += '<div class="preview-actions">';
        html += '<a href="' + filePath + '" target="_blank" class="btn btn-primary">';
        html += '<i class="fas fa-external-link-alt"></i> View Full Document';
        html += '</a>';
        html += '<a href="' + filePath + '" download class="btn btn-secondary">';
        html += '<i class="fas fa-download"></i> Download';
        html += '</a>';
        html += '</div>';

        html += '</div>';
        html += '</div>';

        modal.innerHTML = html;

        document.body.appendChild(modal);
        modal.style.display = 'flex';
        console.log('Modal created and displayed:', modal);
    }

    function closePreviewModal() {
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.remove();
        }
    }
</script>
