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
$pageTitle = 'Authors';
$activeNav = 'authors';
$additionalCSS = ['authors_page.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Authors</h1>
    <div class="header-actions">
        <div class="search-box">
            <input type="text" placeholder="Search authors...">
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
        <div class="filter-box">
            <img src="../icons/authors/filter.png" alt="Filter">
            <span>Filter</span>
        </div>
    </div>
</div>

<!-- Content Box -->
<div class="content-section">
    <div class="authors-grid">
        <!-- Sample Author Cards -->
        <div class="author-card">
            <img src="../icons/authors/card_bg.png" class="banner-img" alt="Author Banner">
            <div class="profile-circle"></div>
            <h3>Tanjiro</h3>
            <p class="username">@Tanjiro_12</p>
            <button class="visit-btn">Visit</button>
            <div class="stats">
                <div><strong>20</strong><br>Books</div>
                <div><img src="../icons/authors/favorite.png" alt="Favorite"><br>Favorite</div>
            </div>
        </div>

        <!-- Add more author cards as needed -->
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
