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

// Fetch Newly Added Publications
$stmt1 = $conn->prepare("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID ORDER BY p.publicationID DESC LIMIT 10");
if ($stmt1) {
  $stmt1->execute();
  $result1 = $stmt1->get_result();
  $newlyAdded = [];
  while ($row = $result1->fetch_assoc()) {
    $newlyAdded[] = $row;
  }
  $stmt1->close();
} else {
  die("Failed to fetch Newly Added Publications: " . $conn->error);
}

// Fetch Most Viewed Research
$stmt2 = $conn->prepare("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID ORDER BY p.views DESC LIMIT 10");
if ($stmt2) {
  $stmt2->execute();
  $result2 = $stmt2->get_result();
  $mostViewed = [];
  while ($row = $result2->fetch_assoc()) {
    $mostViewed[] = $row;
  }
  $stmt2->close();
} else {
  die("Failed to fetch Most Viewed Research: " . $conn->error);
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
                    <div class="card">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <?php if (isset($_SESSION['studentID'])): ?>
                                    <button onclick="savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
                                        <i class="fas fa-bookmark"></i> Save
                                    </button>
                                <?php endif; ?>
                            </div>
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
            <a href="../publication/publication.php">See all</a>
        </div>
        <div class="card-grid">
            <?php if (!empty($newlyAdded)): ?>
                <?php foreach ($newlyAdded as $pub): ?>
                    <div class="card">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <?php if (isset($_SESSION['studentID'])): ?>
                                    <button onclick="savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
                                        <i class="fas fa-bookmark"></i> Save
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No publications available.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Save Publication Modal -->
<div id="saveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Save Publication</h3>
            <span class="close-modal" onclick="closeSaveModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Do you want to save "<span id="saveTitle"></span>" to your saved publications?</p>
            <p>You can view saved publications in your library.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeSaveModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmSaveBtn" onclick="confirmSave()" class="btn btn-success">Save Publication</button>
        </div>
    </div>
</div>

<script>
// Save publication functionality
let publicationToSave = null;

function savePublication(publicationID, title) {
    publicationToSave = publicationID;
    document.getElementById('saveTitle').textContent = title;
    document.getElementById('saveModal').style.display = 'flex';
}

function closeSaveModal() {
    document.getElementById('saveModal').style.display = 'none';
    publicationToSave = null;
}

function confirmSave() {
    if (!publicationToSave) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmSaveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('save_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToSave)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Publication saved successfully! You can view it in your library.');
            closeSaveModal();
        } else {
            alert('Error saving publication: ' + (data.message || 'Unknown error'));
            closeSaveModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the publication.');
        closeSaveModal();
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Save Publication';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const saveModal = document.getElementById('saveModal');
    const previewModal = document.getElementById('previewModal');

    if (saveModal && event.target === saveModal) {
        closeSaveModal();
    }
    if (previewModal && event.target === previewModal) {
        closePreviewModal();
    }
}

// Publication preview functionality
function previewPublication(filePath, title, author, publishDate, abstract, department, type, thumbnail) {
    // Format the publication date
    const formattedDate = new Date(publishDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Create preview modal
    const modal = document.createElement('div');
    modal.id = 'previewModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content preview-modal-content">
            <div class="modal-header">
                <h3>${title}</h3>
                <span class="close-modal" onclick="closePreviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="preview-content-wrapper">
                    ${thumbnail ? `<div class="preview-thumbnail-container">
                        <img src="../${thumbnail}?t=${Date.now()}" alt="Document preview" class="preview-thumbnail">
                    </div>` : ''}
                    <div class="preview-details-container">
                        <div class="publication-details">
                            <div class="detail-row">
                                <strong>Author:</strong> <span>${author}</span>
                            </div>
                            <div class="detail-row">
                                <strong>Published:</strong> <span>${formattedDate}</span>
                            </div>
                            ${department ? `<div class="detail-row"><strong>Department:</strong> <span>${department}</span></div>` : ''}
                            ${type ? `<div class="detail-row"><strong>Type:</strong> <span>${type}</span></div>` : ''}
                        </div>
                        ${abstract ? `<div class="abstract-section">
                            <div class="abstract-label"><strong>Abstract:</strong></div>
                            <div class="abstract-text">${abstract}</div>
                        </div>` : ''}
                    </div>
                </div>
                <div class="preview-actions">
                    <a href="${filePath}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> View Full Document
                    </a>
                    <a href="${filePath}" download class="btn btn-secondary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.remove();
    }
}
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>