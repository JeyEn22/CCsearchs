<?php
session_start();

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
            $insertStmt = $conn->prepare("INSERT INTO user_profiles (studentID, firstName, lastName, contactNumber, emailAddress, currentAddress, department, theme_preference) VALUES (?, ?, ?, ?, ?, ?, ?, 'light')");
            $insertStmt->bind_param("sssssss", $studentID, $regData['firstName'], $regData['lastName'], $regData['contactNumber'], $regData['emailAddress'], $regData['currentAddress'], $regData['department']);
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
            <img src="<?php echo htmlspecialchars(isset($userProfile['profileImage']) && !empty($userProfile['profileImage']) ? '../' . $userProfile['profileImage'] : '../image/profile.png'); ?>" alt="User" id="profilePic">
            <?php if (!$isPublicView): ?>
            <label for="fileInput" class="camera-icon">
                <i class="fa fa-camera"></i>
            </label>
            <input type="file" id="fileInput" accept="image/*" hidden>
            <?php endif; ?>
        </div>

        <h3><?php echo htmlspecialchars((isset($userProfile['firstName']) ? $userProfile['firstName'] : 'Unknown') . ' ' . (isset($userProfile['lastName']) ? $userProfile['lastName'] : 'User')); ?></h3>
        <p>Uploaded Documents: <strong>20</strong></p>
        <p>Viewed Documents: <strong>14</strong></p>
        <?php if (!$isPublicView): ?>
        <button id="viewPublic" onclick="window.location.href='profile.php?public=1'">View as Public</button>
        <?php else: ?>
        <button id="viewPublic" onclick="window.location.href='profile.php'">View as Private</button>
        <?php endif; ?>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <div class="tabs">
            <button class="tab active" data-tab="personal">Personal Information</button>
            <?php if (!$isPublicView): ?>
            <button class="tab" data-tab="account">Account Settings</button>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="personal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="<?php echo htmlspecialchars(isset($userProfile['lastName']) ? $userProfile['lastName'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="<?php echo htmlspecialchars(isset($userProfile['firstName']) ? $userProfile['firstName'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contactNumber" value="<?php echo htmlspecialchars(isset($userProfile['contactNumber']) ? $userProfile['contactNumber'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="emailAddress" value="<?php echo htmlspecialchars(isset($userProfile['emailAddress']) ? $userProfile['emailAddress'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <input type="text" name="currentAddress" value="<?php echo htmlspecialchars(isset($userProfile['currentAddress']) ? $userProfile['currentAddress'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars(isset($userProfile['department']) ? $userProfile['department'] : ''); ?>" <?php echo $isPublicView ? 'readonly' : ''; ?> />
                </div>
            </div>
            <?php if (!$isPublicView): ?>
            <div class="button-container">
                <button class="update" id="updateProfile">Update</button>
            </div>
            <?php endif; ?>
        </div>

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
                <div id="passwordValidation" class="password-rules" style="display: none;">
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

<?php
$additionalScripts = ['profile.js'];
// Include layout footer
include "../layout/layout_footer.php";
?>

<!-- Debug information -->
<script>
console.log('Profile page loaded');
console.log('Is public view:', <?php echo $isPublicView ? 'true' : 'false'; ?>);
console.log('Update button exists:', !!document.getElementById('updateProfile'));
console.log('Body has public-view class:', document.body.classList.contains('public-view'));
</script>
