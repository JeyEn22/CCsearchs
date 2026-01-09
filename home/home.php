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
            <input type="text" id="homeSearchInput" placeholder="Search titles" value="<?php echo htmlspecialchars($searchQuery); ?>" />
            <img src="../icons/authors/search.png" class="search-icon" alt="Search" id="homeSearchIcon" style="cursor: pointer;">
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
                    <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="event.stopPropagation(); savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
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
                    <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="event.stopPropagation(); savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
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
                    <div class="card" data-filepath="<?php echo htmlspecialchars($pub['file_path']); ?>" data-title="<?php echo htmlspecialchars($pub['title']); ?>" data-author="<?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?>" data-studentid="<?php echo htmlspecialchars($pub['studentID']); ?>" data-date="<?php echo htmlspecialchars($pub['published_datetime']); ?>" data-abstract="<?php echo htmlspecialchars($pub['abstract'] ?? ''); ?>" data-department="<?php echo htmlspecialchars($pub['department'] ?? ''); ?>" data-type="<?php echo htmlspecialchars($pub['type'] ?? ''); ?>" data-thumbnail="<?php echo htmlspecialchars($pub['thumbnail'] ?? ''); ?>" onclick="previewPublication(this)">
                        <?php
                        $imageSrc = isset($pub['thumbnail']) && !empty($pub['thumbnail']) ? '../' . $pub['thumbnail'] : '../uploads/publications/covers/default_cover.jpg';
                        $imageSrc .= '?t=' . time(); // Cache busting
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" class="cover-img" alt="Publication cover">
                        <div class="card-info">
                            <h4 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h4>
                            <div class="posted-by">
                                Posted by: <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($pub['studentID']); ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($pub['firstName'] . ' ' . $pub['lastName']); ?></a>
                            </div>
                            <div class="posted-by">Published: <?php echo date("M d, Y", strtotime($pub['published_datetime'])); ?></div>
                            <div class="card-actions">
                                <button onclick="event.stopPropagation(); previewPublication(this.closest('.card'))" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="event.stopPropagation(); savePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-success btn-sm">
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

// Home search functionality with real-time filtering
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('homeSearchInput');
    const searchIcon = document.getElementById('homeSearchIcon');
    
    function performSearch() {
        const q = searchInput.value.trim();
        if (q) {
            window.location.href = 'home.php?search=' + encodeURIComponent(q);
        } else {
            window.location.href = 'home.php';
        }
    }
    
    function filterCardsRealTime() {
        const query = searchInput.value.toLowerCase().trim();
        const allCards = document.querySelectorAll('.card-grid .card');
        
        allCards.forEach(card => {
            const titleElement = card.querySelector('.card-title');
            if (titleElement) {
                const titleText = titleElement.textContent.toLowerCase();
                const authorElement = card.querySelector('.posted-by');
                let authorText = '';
                if (authorElement) {
                    authorText = authorElement.textContent.toLowerCase();
                }
                
                // Show card if title or author matches the search query
                if (titleText.includes(query) || authorText.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            }
        });
        
        // Update section visibility if all cards in a section are hidden
        document.querySelectorAll('.category-box').forEach(section => {
            const visibleCards = section.querySelectorAll('.card-grid .card[style*="display: block"], .card-grid .card:not([style*="display: none"])');
            const allCardsInSection = section.querySelectorAll('.card-grid .card');
            
            // If there are cards and none are visible, hide the section
            if (allCardsInSection.length > 0 && visibleCards.length === 0 && query !== '') {
                section.style.display = 'none';
            } else {
                section.style.display = 'block';
            }
        });
    }
    
    if (searchInput) {
        // Real-time filtering as user types
        searchInput.addEventListener('input', () => {
            filterCardsRealTime();
        });
        
        // Enter key search (server-side)
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // Search icon click (server-side)
    if (searchIcon) {
        searchIcon.addEventListener('click', () => {
            performSearch();
        });
    }
    
    // Apply real-time filter on page load if there's a search query
    if (searchInput && searchInput.value.trim() !== '') {
        filterCardsRealTime();
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


<script src="../js/session_checker.js"></script>
<script src="../assets/js/preview.js"></script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>