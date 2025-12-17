<?php
session_start();
include "../includes/session_validator.php"; // Validate session

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database connection
include "../database/database.php";

// Get current user profile data
$studentID = $_SESSION['studentID'];
$userProfile = null;
$isPublicView = isset($_GET['public']) && $_GET['public'] == '1';

try {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE studentID = ?");
    $stmt->bind_param("s", $studentID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userProfile = $result->fetch_assoc();
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error loading profile: " . $e->getMessage());
}

// If no profile exists, create one from registration data (fallback)
if (!$userProfile) {
    try {
        $stmt = $conn->prepare("SELECT * FROM registration WHERE studentID = ?");
        $stmt->bind_param("s", $studentID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $regData = $result->fetch_assoc();
            // Create profile from registration data
            $profileImage = 'uploads/profile.png';
            $isPublic = 0;
            $insertStmt = $conn->prepare("INSERT INTO user_profiles (studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, profileImage, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssssssi", $studentID, $regData['firstName'], $regData['lastName'], $regData['contactNumber'], $regData['emailAddress'], $regData['currentAddress'], $regData['department'], $profileImage, $isPublic);
            $insertStmt->execute();
            $insertStmt->close();

            // Reload profile data
            $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE studentID = ?");
            $stmt->bind_param("s", $studentID);
            $stmt->execute();
            $result = $stmt->get_result();
            $userProfile = $result->fetch_assoc();
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error creating profile from registration: " . $e->getMessage());
    }
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
    // Table might not exist yet, create it
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS `favorite_authors` (
          `favoriteID` int(11) NOT NULL AUTO_INCREMENT,
          `studentID` varchar(20) NOT NULL,
          `favorite_studentID` varchar(20) NOT NULL,
          `added_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`favoriteID`),
          UNIQUE KEY `unique_favorite` (`studentID`, `favorite_studentID`),
          KEY `fk_favorite_student` (`studentID`),
          KEY `fk_favorite_author` (`favorite_studentID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
    ";
    $conn->query($createTableSQL);
    $favoritesCount = 0;
}

// Get profile visit count
$visitCount = 0;
try {
    $visitStmt = $conn->prepare("SELECT COUNT(DISTINCT visitorID) as visit_count FROM profile_visits WHERE profileOwnerID = ?");
    $visitStmt->bind_param("s", $studentID);
    $visitStmt->execute();
    $visitResult = $visitStmt->get_result();
    $visitRow = $visitResult->fetch_assoc();
    $visitCount = $visitRow['visit_count'];
    $visitStmt->close();
} catch (Exception $e) {
    $visitCount = 0;
}

// Get user's publications for public view
$userPublications = [];
$canDeletePublications = false;
if ($isPublicView) {
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

        // Check if current user can delete these publications (if they own them)
        $canDeletePublications = isset($_SESSION['studentID']) && $_SESSION['studentID'] === $studentID;
    } catch (Exception $e) {
        error_log("Error getting user publications: " . $e->getMessage());
    }
}

$conn->close();

// Set layout variables
$pageTitle = 'CCSearch Profile' . ($isPublicView ? ' (Public View)' : '');
$activeNav = 'profile';
$additionalCSS = ['profile_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Profile Banner Background -->
<div class="profile-banner"></div>

<!-- Main Section with Background -->
<section class="content">
    
    <!-- Profile Card -->
    <div class="profile-card">
        <div class="profile-image">
            <img src="<?php echo htmlspecialchars(isset($userProfile['profileImage']) && !empty($userProfile['profileImage']) ? '../' . $userProfile['profileImage'] : '../uploads/profile.png'); ?>" alt="User" id="profilePic">
            <?php if (!$isPublicView): ?>
            <label for="fileInput" class="camera-icon">
                <i class="fa fa-camera"></i>
            </label>
            <input type="file" id="fileInput" accept="image/*" hidden>
            <?php endif; ?>
        </div>

        <h3><?php echo htmlspecialchars((isset($userProfile['firstName']) ? $userProfile['firstName'] : 'Unknown') . ' ' . (isset($userProfile['lastName']) ? $userProfile['lastName'] : 'User')); ?></h3>
        <p>Uploaded Documents: <strong><?php echo $uploadCount; ?></strong></p>
        <p>Favorites by: <strong><?php echo $favoritesCount; ?></strong></p>
        <?php if (!$isPublicView): ?>
        <button id="viewPublic" onclick="window.location.href='profile.php?public=1'">View as Public</button>
        <?php else: ?>
        <button id="viewPublic" onclick="window.location.href='profile.php'">View as Private</button>
        <?php endif; ?>
        <p style="margin-top: 15px; font-size: 14px; color: #666;">Profile Views: <strong><?php echo $visitCount; ?></strong></p>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <div class="tabs">
            <?php if (!$isPublicView): ?>
            <button class="tab active" data-tab="personal">Personal Information</button>
            <button class="tab" data-tab="account">Account Settings</button>
            <?php else: ?>
            <button class="tab active" data-tab="uploaded">Uploaded Documents</button>
            <?php endif; ?>
        </div>

        <?php if (!$isPublicView): ?>
        <div class="tab-content" id="personal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="<?php echo htmlspecialchars(isset($userProfile['lastName']) ? $userProfile['lastName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="<?php echo htmlspecialchars(isset($userProfile['firstName']) ? $userProfile['firstName'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contactNumber" value="<?php echo htmlspecialchars(isset($userProfile['contactNumber']) ? $userProfile['contactNumber'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="emailAddress" value="<?php echo htmlspecialchars(isset($userProfile['emailAddress']) ? $userProfile['emailAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <input type="text" name="currentAddress" value="<?php echo htmlspecialchars(isset($userProfile['currentAddress']) ? $userProfile['currentAddress'] : ''); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars(isset($userProfile['department']) ? $userProfile['department'] : ''); ?>" readonly />
                </div>
            </div>
            <div class="button-container">
                <button class="update" id="editProfile">Edit</button>
                <button class="update" id="updateProfile" style="display: none;">Update</button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($isPublicView): ?>
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
                                    <?php if ($canDeletePublications): ?>
                                        <button onclick="event.stopPropagation(); deletePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
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
        <?php elseif (!$isPublicView): ?>
        <div class="tab-content hidden" id="account">
            <div class="account-settings-grid">
                <button class="account-btn change-password-btn" id="changePasswordBtn">
                    <i class="fa fa-key"></i>
                    <span>Change Password</span>
                </button>
                <button class="account-btn delete-account-btn" id="deleteAccountBtn">
                    <i class="fa fa-trash"></i>
                    <span>Delete Account</span>
                </button>
                <button class="account-btn theme-btn" id="themeBtn">
                    <i class="fa fa-palette"></i>
                    <span>Theme</span>
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

</section>

<!-- Modals -->
<!-- Change Password Modal -->
<div id="changePasswordModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <span class="close-modal" data-modal="changePasswordModal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="changePasswordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password</label>
                    <input type="password" id="confirmNewPassword" name="confirmNewPassword" required>
                </div>
                <div id="passwordValidation" class="password-rules">
                    <div id="lengthCheck" class="validation-item">
                        <i class="fas fa-times validation-icon"></i>
                        <span>At least 8 characters</span>
                    </div>
                    <div id="uppercaseCheck" class="validation-item">
                        <i class="fas fa-times validation-icon"></i>
                        <span>One uppercase letter</span>
                    </div>
                    <div id="numberCheck" class="validation-item">
                        <i class="fas fa-times validation-icon"></i>
                        <span>One number</span>
                    </div>
                    <div id="matchCheck" class="validation-item">
                        <i class="fas fa-times validation-icon"></i>
                        <span>Passwords match</span>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn cancel-btn" data-modal="changePasswordModal">Cancel</button>
            <button type="submit" form="changePasswordForm" class="modal-btn primary-btn">Change Password</button>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Account</h3>
            <span class="close-modal" data-modal="deleteAccountModal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="delete-warning">
                <i class="fa fa-exclamation-triangle warning-icon"></i>
                <h4>Are you sure you want to delete your account?</h4>
                <p>This action cannot be undone. All your data, including uploaded documents and profile information, will be permanently deleted.</p>
                <div class="form-group">
                    <label for="deleteConfirmation">Type "DELETE" to confirm:</label>
                    <input type="text" id="deleteConfirmation" name="deleteConfirmation" placeholder="DELETE" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn cancel-btn" data-modal="deleteAccountModal">Cancel</button>
            <button type="button" id="confirmDeleteBtn" class="modal-btn danger-btn">Delete Account</button>
        </div>
    </div>
</div>

<!-- Theme Selection Modal -->
<div id="themeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Choose Theme</h3>
            <span class="close-modal" data-modal="themeModal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="theme-options">
                <div class="theme-option" data-theme="light">
                    <div class="theme-preview light-preview">
                        <div class="preview-header"></div>
                        <div class="preview-content"></div>
                    </div>
                    <div class="theme-info">
                        <h4>Light Mode</h4>
                        <p>Clean and bright interface</p>
                    </div>
                    <div class="theme-radio">
                        <input type="radio" id="lightTheme" name="theme" value="light">
                        <label for="lightTheme"></label>
                    </div>
                </div>
                <div class="theme-option" data-theme="dark">
                    <div class="theme-preview dark-preview">
                        <div class="preview-header"></div>
                        <div class="preview-content"></div>
                    </div>
                    <div class="theme-info">
                        <h4>Dark Mode</h4>
                        <p>Easy on the eyes</p>
                    </div>
                    <div class="theme-radio">
                        <input type="radio" id="darkTheme" name="theme" value="dark">
                        <label for="darkTheme"></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn cancel-btn" data-modal="themeModal">Cancel</button>
            <button type="button" id="applyThemeBtn" class="modal-btn primary-btn">Apply Theme</button>
        </div>
    </div>
</div>

<!-- Delete Publication Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Publication</h3>
            <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete "<span id="deleteTitle"></span>"?</p>
            <p class="danger-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmDeleteBtn" onclick="confirmDelete()" class="btn btn-danger">Delete</button>
        </div>
    </div>
</div>

<?php
$additionalScripts = ['profile.js'];
// Include layout footer
include "../layout/layout_footer.php";
?>

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

<!-- Publication deletion functionality -->
<script>
let publicationToDelete = null;

function deletePublication(publicationID, title) {
    publicationToDelete = publicationID;
    document.getElementById('deleteTitle').textContent = title;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    publicationToDelete = null;
}

function confirmDelete() {
    if (!publicationToDelete) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.textContent = 'Deleting...';

    fetch('../home/delete_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToDelete)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the modal and reload the page
            closeDeleteModal();
            // Small delay to allow modal to close before redirect
            setTimeout(() => {
                window.location.href = window.location.pathname + window.location.search + '#uploaded';
            }, 300);
        } else {
            alert('Error deleting publication: ' + (data.message || 'Unknown error'));
            // Re-enable button
            btn.disabled = false;
            btn.textContent = 'Delete';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the publication.');
        // Re-enable button
        btn.disabled = false;
        btn.textContent = 'Delete';
    });
}
</script>

<!-- Debug information -->
<script>
console.log('Profile page loaded');
console.log('Is public view:', <?php echo $isPublicView ? 'true' : 'false'; ?>);
console.log('Update button exists:', !!document.getElementById('updateProfile'));
console.log('Body has public-view class:', document.body.classList.contains('public-view'));
</script>
<!-- Real-time Session Checker Modal -->
<div id="loginModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close-modal">&times;</span>
    </div>
    <div class="modal-body">
      <div class="modal-icon">
        <i id="modalIcon" class="fas"></i>
      </div>
      <h3 id="modalTitle">Session Status</h3>
      <p id="modalMessage"></p>
    </div>
    <div class="modal-footer">
      <button id="modalButton" class="modal-btn">OK</button>
    </div>
  </div>
</div>

<!-- Add modal styles for session checker -->
<style>
  .modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease-out;
    justify-content: center;
    align-items: center;
    pointer-events: none;
  }

  .modal-content {
    background-color: #fff;
    padding: 0;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 350px;
    height: auto;
    min-height: 280px;
    position: relative;
    pointer-events: auto;
    margin: auto;
    transform: translateY(0);
    display: flex;
    flex-direction: column;
  }

  .modal-header {
    padding: 12px 20px 0;
    text-align: right;
  }

  .close-modal {
    font-size: 20px;
    font-weight: 500;
    color: #aaa;
    cursor: pointer;
    transition: color 0.3s ease;
  }

  .close-modal:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px 24px;
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .modal-icon {
    margin-bottom: 16px;
  }

  .modal-icon .fas {
    font-size: 48px;
    color: #dc2626;
  }

  .modal-body h3 {
    margin: 12px 0 8px;
    color: #333;
    font-size: 18px;
    font-weight: 700;
  }

  .modal-body p {
    margin: 12px 0;
    color: #555;
    line-height: 1.6;
    font-size: 14px;
  }

  .modal-footer {
    padding: 16px 24px 24px;
    text-align: center;
  }

  .modal-btn {
    background-color: #dc2626;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.2s ease;
  }

  .modal-btn:hover {
    background-color: #b91c1c;
  }
</style>

<script src="../js/session_checker.js"></script>