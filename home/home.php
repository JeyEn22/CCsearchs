<?php
session_start();
include "../database/database.php"; // Make sure this connects to your database

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Fetch Most Viewed Research
$stmt1 = $conn->prepare("
    SELECT publicationID, title, published_datetime, views, bg_image
    FROM publications
    ORDER BY views DESC
    LIMIT 5
");
if ($stmt1) {
  $stmt1->execute();
  $result1 = $stmt1->get_result();
  $mostViewed = [];
  while ($row = $result1->fetch_assoc()) {
    $mostViewed[] = $row;
  }
  $stmt1->close();
} else {
  die("Failed to fetch Most Viewed Research: " . $conn->error);
}

// Fetch Newly Added Publications
$stmt2 = $conn->prepare("
    SELECT publicationID, title, published_datetime, bg_image
    FROM publications
    ORDER BY published_datetime DESC
    LIMIT 5
");
if ($stmt2) {
  $stmt2->execute();
  $result2 = $stmt2->get_result();
  $newlyAdded = [];
  while ($row = $result2->fetch_assoc()) {
    $newlyAdded[] = $row;
  }
  $stmt2->close();
} else {
  die("Failed to fetch Newly Added Publications: " . $conn->error);
}

// Set layout variables
$pageTitle = 'CCSearch Dashboard';
$activeNav = 'home';
$additionalCSS = ['home_page.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Welcome Header -->
<div class="welcome-header">
    <img src="../image/home_images/welcome-header.png" class="welcome-image" alt="Welcome Header">
    <div class="welcome-content">
        <h2>Welcome to CCSearch Jelly</h2>
        <p>Discover and explore the best research works</p>
        <div class="search-box">
            <input type="text" placeholder="Search..." />
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
    </div>
</div>

<!-- Content Sections -->
<div class="content-section">
    <!-- Most Viewed Research -->
    <div class="category-box">
        <div class="category-header">
            <h3>Most Viewed Research</h3>
            <a href="#">See all</a>
        </div>
        <div class="card-grid">
            <?php if (!empty($mostViewed)): ?>
                <?php foreach ($mostViewed as $pub): ?>
                    <?php
                    $bg = !empty($pub['bg_image']) ? $pub['bg_image'] : '../icons/publications/template_card.png';
                    ?>
                    <div class="card">
                        <div class="card-thumbnail" style="background-image:url('<?= htmlspecialchars($bg) ?>'); 
                            background-size:cover; 
                            background-position:center;">
                        </div>
                        <div class="card-info">
                            <h4 class="card-title"><?= htmlspecialchars($pub['title']) ?></h4>
                            <small>Published: <?= date("M d, Y", strtotime($pub['published_datetime'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No publications available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Newly Added Publications -->
    <div class="category-box">
        <div class="category-header">
            <h3>Newly Added</h3>
            <a href="#">See all</a>
        </div>
        <div class="card-grid">
            <?php if (!empty($newlyAdded)): ?>
                <?php foreach ($newlyAdded as $pub): ?>
                    <?php
                    $bg = !empty($pub['bg_image']) ? $pub['bg_image'] : '../icons/publications/template_card.png';
                    ?>
                    <div class="card">
                        <div class="card-thumbnail" style="background-image:url('<?= htmlspecialchars($bg) ?>'); 
                            background-size:cover; 
                            background-position:center;">
                        </div>
                        <div class="card-info">
                            <h4 class="card-title"><?= htmlspecialchars($pub['title']) ?></h4>
                            <small>Published: <?= date("M d, Y", strtotime($pub['published_datetime'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No publications available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>