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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CCSearch Dashboard</title>
  <link rel="stylesheet" href="home.css" />
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
          <a href="#" class="active"><img src="../icons/sidebar-icons/home.png" alt=""> Home</a>
          <a href="../profile/profile.php"><img src="../icons/sidebar-icons/profile.png" alt=""> Profile</a>
          <a href="../libary/libary.php"><img src="../icons/sidebar-icons/library.png" alt=""> My Library</a>
          <a href="#"><img src="../icons/sidebar-icons/publication.png" alt=""> Publication</a>
          <a href="../authors/authors.php"><img src="../icons/sidebar-icons/authors.png" alt=""> Authors</a>
          <a href="../notification/notification.php"><img src="../icons/sidebar-icons/notification.png" alt="">
            Notification</a>
        </nav>
      </div>
      <div class="logout">
        <a href="logout.php"><img src="../icons/sidebar-icons/logout.png" alt=""> Logout</a>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-section">
      <div class="welcome-header">
        <img src="../image/home_images/welcome-header.png" class="welcome-image" alt="Welcome Header">

        <div class="welcome-content">
          <h2>Welcome to CCSearch Jelly</h2>
          <p>Discover and explore the best research works</p>
          <div class="search-bar">
            <input type="text" placeholder="Search..." />
            <button>Search</button>
          </div>
        </div>
      </div>

      <div class="content-section">
        <!-- CATEGORY 1 -->
        <div class="category-box">
          <div class="category-header">
            <h3>Most Viewed Research</h3>
            <a href="#">See all</a>
          </div>
          <div class="card-grid">
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
          </div>
        </div>

        <!-- CATEGORY 2 -->
        <div class="category-box">
          <div class="category-header">
            <h3>Newly Added</h3>
            <a href="#">See all</a>
          </div>
          <div class="card-grid">
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
            <div class="card">
              <div class="card-thumbnail"></div>
              <p>Placeholder Title</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- RIGHT PANEL -->
    <aside class="right-panel">
      <div class="tools-box">
        <div class="tools-header">
          <h3>Tools</h3>
          <a href="#">See all</a>
        </div>
        <ul>
          <li><img src="../icons/tool-icons/quillbot.png" alt=""> Quillbot</li>
          <li><img src="../icons/tool-icons/canva.png" alt=""> Canva </li>
          <li><img src="../icons/tool-icons/grammarly.png" alt=""> Grammarly</li>
        </ul>
      </div>

      <div class="recent-box">
        <h3>Recent Activity</h3>
        <div class="recent-item">
          <div class="recent-thumb"></div>
          <p>New upload detected <br><span>2 hours ago</span></p>
        </div>
        <div class="recent-item">
          <div class="recent-thumb"></div>
          <p>Profile updated <br><span>Yesterday</span></p>
        </div>
      </div>
    </aside>

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