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

// Set layout variables
$pageTitle = 'CCSearch Profile';
$activeNav = 'profile';
$additionalCSS = ['profile_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Profile Banner Background -->
<div class="profile-banner"></div>

<!-- Main Section with Background -->
<div class="profile-wrapper">
    <section class="content">
        <!-- Profile Card -->
        <div class="profile-card">
        <div class="profile-image">
            <img src="../image/profile.png" alt="User" id="profilePic">
            <label for="fileInput" class="camera-icon">
                <i class="fa fa-camera"></i>
            </label>
            <input type="file" id="fileInput" accept="image/*" hidden>
        </div>

        <h3>Nani Daski</h3>
        <p>Uploaded Documents: <strong>20</strong></p>
        <p>Viewed Documents: <strong>14</strong></p>
        <button id="viewPublic">View as Public</button>
    </div>

    <!-- Information Section -->
    <div class="info-section">
        <div class="tabs">
            <button class="tab active" data-tab="personal">Personal Information</button>
            <button class="tab" data-tab="account">Account Settings</button>
        </div>

        <div class="tab-content" id="personal">
            <div class="form-grid">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" value="Daski" />
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" value="Nani" />
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" value="09123456789" />
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="nani@example.com" />
                </div>
                <div class="form-group">
                    <label>Current Address</label>
                    <input type="text" value="123 Sample Street, Cav" />
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" value="Information Technology" />
                </div>
            </div>
            <div class="button-container">
                <button class="update">Update</button>
            </div>
        </div>

        <div class="tab-content hidden" id="account">
            <div class="form-grid">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="nani_d" />
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" value="********" />
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" value="********" />
                </div>
            </div>
            <div class="button-container">
                <button class="update">Save Changes</button>
            </div>
        </div>
    </div>
    </section>
</div>

<?php
$additionalScripts = ['profile/profile.js'];
// Include layout footer
include "../layout/layout_footer.php";
?>
