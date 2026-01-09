<?php
session_start();
include "../database/database.php"; // Make sure this connects to your database
include "../includes/session_validator.php"; // Validate session

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchResults = [];

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

// Search across all publications by title if a search term is provided
if ($searchQuery !== '') {
  $stmtSearch = $conn->prepare("
    SELECT p.*, r.firstName, r.lastName
    FROM publications p
    JOIN registration r ON p.studentID = r.studentID
    WHERE p.title LIKE ?
    ORDER BY p.published_datetime DESC
  ");
  if ($stmtSearch) {
    $like = '%' . $searchQuery . '%';
    $stmtSearch->bind_param("s", $like);
    $stmtSearch->execute();
    $resSearch = $stmtSearch->get_result();
    while ($row = $resSearch->fetch_assoc()) {
      $searchResults[] = $row;
    }
    $stmtSearch->close();
  }
}


// Get current user's first name for greeting
$userFirstName = 'User';
if (isset($_SESSION['studentID'])) {
    $stmtUser = $conn->prepare("SELECT firstName FROM registration WHERE studentID = ? LIMIT 1");
    if ($stmtUser) {
        $stmtUser->bind_param("s", $_SESSION['studentID']);
        if ($stmtUser->execute()) {
            $resUser = $stmtUser->get_result();
            if ($rowUser = $resUser->fetch_assoc()) {
                $userFirstName = $rowUser['firstName'];
            }
            $resUser->free();
        }
        $stmtUser->close();
    }
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
        <h2>Welcome to CCSearch <?php echo htmlspecialchars($userFirstName); ?></h2>
        <p>Discover and explore the best research works</p>
        <div class="search-box">
            <input type="text" id="homeSearchInput" placeholder="Search titles" />
            <img src="../icons/authors/search.png" class="search-icon" alt="Search">
        </div>
    </div>
</div>

<!-- Content Sections -->
<div class="content-section">
    <?php if (!empty($searchQuery)): ?>
    <!-- Search Results -->
    <div class="category-box">
        <div class="category-header">
            <h3>Search Results</h3>
            <span class="posted-by">Showing titles matching "<?php echo htmlspecialchars($searchQuery); ?>"</span>
        </div>
        <div class="card-grid">
            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $pub): ?>
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
                            <div class="posted-by"><?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
                                    <i class="fas fa-bookmark"></i> Save
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No publications found for that title.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Most Viewed Research -->
    <div class="category-box">
        <div class="category-header">
            <h3>Most Viewed Research</h3>
            <a href="view_all.php?category=most_viewed">View all</a>
        </div>
        <div class="card-grid" id="mostViewedGrid">
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
                            <div class="posted-by"><?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
                                    <i class="fas fa-bookmark"></i> Save
                                </button>
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
            <a href="view_all.php?category=newly_added">View all</a>
        </div>
        <div class="card-grid" id="newlyAddedGrid">
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
                            <div class="posted-by"><?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])) ; ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['thumbnail'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
                                    <i class="fas fa-bookmark"></i> Save
                                </button>
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


<script>
// Save publication functionality
function savePublication(publicationID, title) {
    // Check if modal exists, create if not
    var modal = document.getElementById('saveModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'saveModal';
        modal.className = 'modal';
        modal.innerHTML = '<div class="modal-content"><div class="modal-header"><h3>Save Publication</h3><span class="close-modal" onclick="closeSaveModal()">&times;</span></div><div class="modal-body"><p>Do you want to save "<span id="saveTitle"></span>" to your saved publications?</p><p>You can view saved publications in your library.</p></div><div class="modal-footer"><button onclick="closeSaveModal()" class="btn btn-secondary">Cancel</button><button id="confirmSaveBtn" onclick="confirmSave()" class="btn btn-success">Save Publication</button></div></div>';
        document.body.appendChild(modal);
    }

    window.publicationToSave = publicationID;
    document.getElementById('saveTitle').textContent = title;
    modal.style.display = 'flex';
    modal.style.opacity = '1';
}

function closeSaveModal() {
    const modal = document.getElementById('saveModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            window.publicationToSave = null;
        }, 300); // Match CSS transition
    }
}

function confirmSave() {
    if (!window.publicationToSave) return;

    const btn = document.getElementById('confirmSaveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('save_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(window.publicationToSave)
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
// Preview handled by centralized script: ../assets/js/preview.js

// Close modals when clicking outside (save modal only)
window.addEventListener('click', function(event) {
    const saveModal = document.getElementById('saveModal');
    if (saveModal && event.target === saveModal) {
        closeSaveModal();
    }
});

// Home search functionality (server-side like publication page)
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('homeSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const q = searchInput.value.trim();
                if (q) {
                    window.location.href = 'home.php?search=' + encodeURIComponent(q);
                } else {
                    window.location.href = 'home.php';
                }
            }
        });
    }
});
</script>

<!-- Real-time Session Checker Modal -->
<div id="loginModal" class="modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <span class="close-modal">&times;</span>
    </div>
    <div class="modal-body">
      <div class="modal-icon">
        <i id="modalIcon" class="fas"></i>
      </div>
      <h3 id="modalTitle">Session Status</h3>
      <p id="modalMessage"></p>
    </div>
    <div class="modal-footer">
      <button id="modalButton" class="modal-btn">OK</button>
    </div>
  </div>
</div>

<!-- Add modal styles for session checker -->
<style>
  .modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    opacity: 0;
    transition: opacity 0.3s ease-out;
    justify-content: center;
    align-items: center;
    pointer-events: none;
  }

  .modal-content {
    background-color: #fff;
    padding: 0;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 350px;
    height: auto;
    min-height: 280px;
    position: relative;
    pointer-events: auto;
    margin: auto;
    transform: translateY(0);
    display: flex;
    flex-direction: column;
  }

  .modal-header {
    padding: 12px 20px 0;
    text-align: right;
  }

  .close-modal {
    font-size: 20px;
    font-weight: 500;
    color: #aaa;
    cursor: pointer;
    transition: color 0.3s ease;
  }

  .close-modal:hover {
    color: #000;
  }

  .modal-body {
    padding: 20px 24px;
    text-align: center;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }

  .modal-icon {
    margin-bottom: 16px;
  }

  .modal-icon .fas {
    font-size: 48px;
    color: #dc2626;
  }

  .modal-body h3 {
    margin: 12px 0 8px;
    color: #333;
    font-size: 18px;
    font-weight: 700;
  }

  .modal-body p {
    margin: 12px 0;
    color: #555;
    line-height: 1.6;
    font-size: 14px;
  }

  .modal-footer {
    padding: 16px 24px 24px;
    text-align: center;
  }

  .modal-btn {
    background-color: #dc2626;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.2s ease;
  }

  .modal-btn:hover {
    background-color: #b91c1c;
  }
</style>

<script src="../js/session_checker.js"></script>
<script src="../assets/js/preview.js"></script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>