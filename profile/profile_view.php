<?php
session_start();
include "../database/database.php";

// Check if studentID parameter is provided
if (!isset($_GET['studentID'])) {
    die("Invalid profile.");
}

$studentID = $_GET['studentID'];

// Query user information from registration table
$sql = "SELECT * FROM registration WHERE studentID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $studentID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Profile not found.");
}

$user = $result->fetch_assoc();
$stmt->close();

// Set layout variables
$pageTitle = 'Publications by ' . htmlspecialchars($user['firstName'] . ' ' . $user['lastName']);
$activeNav = 'publication';
$additionalCSS = ['../home/home_page.css', '../publication/publication_page.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Author Publications Header -->
<div class="welcome-header">
    <img src="../image/home_images/welcome-header.png" class="welcome-image" alt="Welcome Header">
    <div class="welcome-content">
        <h2>Publications by <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></h2>

        <?php if (!empty($user['department'])): ?>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($user['department']); ?></p>
        <?php endif; ?>

        <div class="author-stats">
            <?php
            // Get publication count
            $countSql = "SELECT COUNT(*) as pub_count FROM publications WHERE studentID = ?";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bind_param("s", $studentID);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $pubCount = $countRow['pub_count'];
            $countStmt->close();
            ?>

            <span class="stat-item">
                <strong><?php echo $pubCount; ?></strong> Publication<?php echo $pubCount != 1 ? 's' : ''; ?>
            </span>
        </div>
    </div>
</div>

<!-- Publications Content -->
<div class="content-section">

    <?php if ($pubCount > 0): ?>
        <div class="category-box">

            <?php
            // Fetch user's publications
            $pubSql = "SELECT p.*, r.firstName, r.lastName 
                       FROM publications p 
                       JOIN registration r ON p.studentID = r.studentID 
                       WHERE p.studentID = ? 
                       ORDER BY p.publicationID DESC";

            $pubStmt = $conn->prepare($pubSql);
            $pubStmt->bind_param("s", $studentID);
            $pubStmt->execute();
            $pubResult = $pubStmt->get_result();

            if ($pubResult->num_rows > 0):
                while ($pub = $pubResult->fetch_assoc()):
            ?>

                    <div class="card">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <a href="<?php echo htmlspecialchars($pub['file_path']); ?>" target="_blank">View Document</a>
                        </div>
                    </div>

            <?php
                endwhile;
            endif;
            ?>

            <?php $pubStmt->close(); ?>

        </div> <!-- END category-box -->

    <?php else: ?>

        <div class="empty-state">
            <i class="fas fa-book-open" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
            <h3>No publications yet</h3>
            <p>This author hasn't published any documents.</p>
        </div>

    <?php endif; ?>

</div> <!-- END content-section -->

<?php
$conn->close();

// Include layout footer
include "../layout/layout_footer.php";
?>
