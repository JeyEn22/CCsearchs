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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notification</title>
  <link rel="stylesheet" href="notification.css" />
</head>

<body>

  <div class="layout-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div>
        <div class="logo-container">
          <img src="../icons/sidebar-icons/icon.png" alt="Logo" />
          <h2>CCSEARCH</h2>
        </div>
        <nav class="nav-menu">
          <a href="../home/home.php"><img src="../icons/sidebar-icons/home.png" alt=""> Home</a>
          <a href="../profile/profile.php"><img src="../icons/sidebar-icons/profile.png" alt=""> Profile</a>
          <a href="../libary/libary.php"><img src="../icons/sidebar-icons/library.png" alt=""> My Library</a>
          <a href="#"><img src="../icons/sidebar-icons/publication.png" alt=""> Publication</a>
          <a href="../authors/authors.php"><img src="../icons/sidebar-icons/authors.png" alt=""> Authors</a>
          <a href="#" class="active"><img src="../icons/sidebar-icons/notification.png" alt=""> Notification</a>
        </nav>
      </div>
      <div class="logout">
        <a href="#"><img src="/icons/sidebar-icons/logout.png" alt=""> Logout</a>
      </div>
    </aside>

    <!-- MAIN CONTENT WRAPPER -->
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
            <p class="notif-text">“Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
              incididunt ut labore et dolore magna.”</p>
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
            <p class="notif-text">“Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
              incididunt ut labore et dolore magna.”</p>
            <img src="../icons/notification/notification_profile.png" class="notif-avatar">
            <span class="notif-time">A few minutes ago</span>
            <span class="notif-date">Oct 07</span>
          </div>


        </div>
      </div>

    </div>

  </div>

  <script>
    // Detect page show from back/forward cache
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        // Page was loaded from bfcache, force reload
        window.location.reload(0);
      }
    });
  </script>

</body>

</html>