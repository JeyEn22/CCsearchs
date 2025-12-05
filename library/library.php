<?php
session_start();
include "../database/database.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$studentID = $_SESSION['studentID'];

// Fetch publications in student's library
$stmt = $conn->prepare("
    SELECT p.publicationID, p.title, p.published_datetime, p.authors, p.department, p.type, p.abstract, p.file_path
    FROM library l
    JOIN publications p ON l.publicationID = p.publicationID
    WHERE l.studentID = ?
    ORDER BY p.published_datetime DESC
");
if ($stmt) {
  $stmt->bind_param("s", $studentID);
  $stmt->execute();
  $result = $stmt->get_result();
  $libraryPublications = [];
  while ($row = $result->fetch_assoc()) {
    $libraryPublications[] = $row;
  }
  $stmt->close();
} else {
  die("Failed to prepare statement: " . $conn->error);
}

// Set layout variables
$pageTitle = 'CCSearch Library';
$activeNav = 'library';
$additionalCSS = ['library_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Welcome Header -->
<div class="welcome-header library-header">
  
    <img src="../image/home_images/welcome-header.png" alt="Wavy blue background" class="banner-background" />
    <div class="welcome-content">
        <h2>Welcome to CCSearch, Jelly! 👋</h2>
        <p>Where you can share credible knowledge and discover reliable sources — all in one place!</p>
        <div class="search-box">
            <input type="text" placeholder="Search..." />
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
    </div>
    />
</div>

<!-- Content Section -->
<div class="content-section">
    <div class="category-box">
        <div class="category-header">
            <h3>My Books</h3>
            <a href="#">View all →</a>
        </div>
        <div class="book-grid-wrapper">
            <div class="book-grid">
                <?php if (!empty($libraryPublications)): ?>
                    <?php foreach ($libraryPublications as $pub): ?>
                        <div class="book-card">
                            <p><?= htmlspecialchars($pub['title']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No publications in your library yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="small-sections-row">
        <div class="category-box small-section">
            <div class="category-header">
                <h3>Saved Books</h3>
                <a href="#">View all →</a>
            </div>
            <div class="mini-list">
                <?php
                // Fetch saved publications
                $stmt2 = $conn->prepare("
                    SELECT p.title
                    FROM saved_publications sp
                    JOIN publications p ON sp.publicationID = p.publicationID
                    WHERE sp.studentID = ?
                    ORDER BY sp.savedID DESC
                ");
                if ($stmt2) {
                    $stmt2->bind_param("s", $studentID);
                    $stmt2->execute();
                    $res2 = $stmt2->get_result();
                    while ($row2 = $res2->fetch_assoc()) {
                        echo "<div class='mini-item'><p>" . htmlspecialchars($row2['title']) . "</p></div>";
                    }
                    $stmt2->close();
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
