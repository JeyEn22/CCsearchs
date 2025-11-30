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
    <title>Authors</title>
    <link rel="stylesheet" href="authors.css">
</head>

<body>

    <div class="layout-container">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo-container">
                <img src="../icons/sidebar-icons/icon.png" alt="Logo" />
                <h2>CCSearch</h2>
            </div>

            <nav class="nav-menu">
                <a href="../home/home.php"><img src="../icons/sidebar-icons/home.png"> Home</a>
                <a href="../profile/profile.php"><img src="../icons/sidebar-icons/profile.png"> Profile</a>
                <a href="../library/library.php"><img src="../icons/sidebar-icons/library.png"> My Library</a>
                <a href="../publication/publication.php"><img src="../icons/sidebar-icons/publication.png">
                    Publication</a>
                <a class="active" href="#"><img src="../icons/sidebar-icons/authors.png"> Authors</a>
                <a href="../notification/notification.php"><img src="../icons/sidebar-icons/notification.png">
                    Notification</a>
            </nav>

            <div class="logout">
                <a href="logout.php"><img src="../icons/sidebar-icons/logout.png"> Logout</a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-container">

            <!-- HEADER BAR -->
            <div class="top-bar">
                <span class="back-btn">&lt;&lt; Back</span>

                <h1>Authors</h1>

                <div class="search-filter">
                    <div class="search-box">
                        <input type="text" placeholder="Search">
                        <img src="../icons/authors/search.png" class="search-icon">
                    </div>
                    <div class="filter-box">
                        <img src="../icons/authors/filter.png">
                        <span>Filter</span>
                    </div>
                </div>
            </div>

            <!-- WHITE CONTENT BOX -->
            <div class="content-box">
                <div class="authors-grid">

                    <!-- SAMPLE CARD – Duplicate as needed -->
                    <div class="author-card">
                        <img src="../icons/authors/card_bg.png" class="banner-img">
                        <div class="profile-circle"></div>

                        <h3>Tanjiro</h3>
                        <p class="username">@Tanjiro_12</p>

                        <button class="visit-btn">Visit</button>

                        <div class="stats">
                            <div><strong>20</strong><br>Books</div>
                            <div><img src="../icons/authors/favorite.png"><br>Favorite</div>
                        </div>
                    </div>






        </main>

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