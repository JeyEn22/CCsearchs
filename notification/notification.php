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
$pageTitle = 'Notification';
$activeNav = 'notification';
$additionalCSS = ['notification_page.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Notification Wrapper -->
<div class="notification-wrapper">
    <h2 class="notif-title">Notifications</h2>

    <!-- NEW SECTION -->
    <div class="notif-section">
        <h3>New</h3>
        <div class="notif-box">
            <!-- Notification Row -->
            <div class="notif-row">
                <input type="checkbox">
                <span class="notif-type">Publication</span>
                <p class="notif-text">"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna."</p>
                <img src="../icons/notification/notification_profile.png" class="notif-avatar">
                <span class="notif-time">Just now</span>
                <span class="notif-date">Oct 07</span>
            </div>
            <!-- Duplicate rows as needed… -->
        </div>
    </div>

    <!-- TODAY SECTION -->
    <div class="notif-section">
        <h3>Today</h3>
        <div class="notif-box">
            <div class="notif-row">
                <input type="checkbox">
                <span class="notif-type">Publication</span>
                <p class="notif-text">"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna."</p>
                <img src="../icons/notification/notification_profile.png" class="notif-avatar">
                <span class="notif-time">A few minutes ago</span>
                <span class="notif-date">Oct 07</span>
            </div>
        </div>
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
