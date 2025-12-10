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
$stmt = $conn->prepare("SELECT p.*, r.firstName, r.lastName FROM publications p JOIN registration r ON p.studentID = r.studentID WHERE p.studentID = ? ORDER BY p.publicationID DESC");
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
        <div class="card-grid">
            <?php if (!empty($libraryPublications)): ?>
                <?php foreach ($libraryPublications as $pub): ?>
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
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <?php if (isset($_SESSION['studentID']) && $_SESSION['studentID'] === $pub['studentID']): ?>
                                    <button onclick="deletePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Your library is empty</h3>
                    <p>Publications you upload will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Saved Books -->
    <div class="category-box">
        <div class="category-header">
            <h3>Saved Books</h3>
            <a href="#">View all →</a>
        </div>
        <div class="card-grid">
            <?php
            // Fetch saved publications with full details
            $stmt2 = $conn->prepare("
                SELECT p.*, r.firstName, r.lastName
                FROM saved_publications sp
                JOIN publications p ON sp.publicationID = p.publicationID
                JOIN registration r ON p.studentID = r.studentID
                WHERE sp.studentID = ?
                ORDER BY sp.savedID DESC
                LIMIT 10
            ");
            if ($stmt2) {
                $stmt2->bind_param("s", $studentID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $savedPublications = [];
                while ($row2 = $res2->fetch_assoc()) {
                    $savedPublications[] = $row2;
                }
                $stmt2->close();
            }

            if (!empty($savedPublications)):
                foreach ($savedPublications as $pub):
            ?>
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
                                <button onclick="previewPublication('<?php echo htmlspecialchars($pub['file_path']); ?>', '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['firstName'] . ' ' . $pub['lastName'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['published_datetime'])); ?>', '<?php echo htmlspecialchars(addslashes($pub['abstract'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['department'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($pub['type'] ?? '')); ?>')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Preview
                                </button>
                                <button onclick="unsavePublication(<?php echo $pub['publicationID']; ?>, '<?php echo htmlspecialchars(addslashes($pub['title'])); ?>')" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Unsave
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bookmark" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No saved publications</h3>
                    <p>Publications you save will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Favorite Authors -->
    <div class="category-box favorite-authors-box">
        <div class="category-header">
            <h3>Favorite Authors</h3>
            <a href="../authors/authors.php">View all →</a>
        </div>
        <div class="authors-grid">
            <?php
            // Fetch favorite authors with their info
            $favoriteAuthors = [];
            try {
                $stmt3 = $conn->prepare("
                SELECT r.*, up.profileImage, up.theme_preference, COUNT(p.publicationID) as totalPublications
                FROM favorite_authors fa
                JOIN registration r ON fa.favorite_studentID = r.studentID
                LEFT JOIN user_profiles up ON fa.favorite_studentID = up.studentID
                LEFT JOIN publications p ON fa.favorite_studentID = p.studentID
                WHERE fa.studentID = ?
                GROUP BY r.studentID, r.firstName, r.lastName, r.emailAddress, r.contactNumber, r.currentAddress, r.department, up.profileImage, up.theme_preference
                ORDER BY fa.added_datetime DESC
                    LIMIT 10
                ");
                if ($stmt3) {
                    $stmt3->bind_param("s", $studentID);
                    $stmt3->execute();
                    $res3 = $stmt3->get_result();
                    while ($row3 = $res3->fetch_assoc()) {
                        $favoriteAuthors[] = $row3;
                    }
                    $stmt3->close();
                }
            } catch (Exception $e) {
                // Table doesn't exist yet, create it
                $createTableSQL = "
                    CREATE TABLE IF NOT EXISTS `favorite_authors` (
                      `favoriteID` int(11) NOT NULL AUTO_INCREMENT,
                      `studentID` varchar(20) NOT NULL,
                      `favorite_studentID` varchar(20) NOT NULL,
                      `added_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`favoriteID`),
                      UNIQUE KEY `unique_favorite` (`studentID`, `favorite_studentID`),
                      KEY `fk_favorite_student` (`studentID`),
                      KEY `fk_favorite_author` (`favorite_studentID`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
                ";
                $conn->query($createTableSQL);
                // favoriteAuthors remains empty
            }

            if (!empty($favoriteAuthors)):
                foreach ($favoriteAuthors as $author):
            ?>
                <div class="author-card">
                    <img src="../icons/authors/card_bg.png" class="banner-img" alt="Author Banner">
                    <div class="profile-circle" style="background-image: url('<?php echo htmlspecialchars(isset($author['profileImage']) && !empty($author['profileImage']) ? '../' . $author['profileImage'] : '../uploads/profiles/profile.png'); ?>');"></div>
                    <h3><?php echo htmlspecialchars($author['firstName'] . ' ' . $author['lastName']); ?></h3>
                    <p class="username"><?php echo htmlspecialchars($author['studentID']); ?></p>
                    <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($author['studentID']); ?>" class="visit-btn">Visit</a>
                    <div class="stats">
                        <div><strong><?php echo htmlspecialchars($author['totalPublications']); ?></strong><br>Books</div>
                        <div>
                            <button onclick="unfavoriteAuthor('<?php echo htmlspecialchars($author['studentID']); ?>')" class="favorite-btn unfavorited">
                                <i class="fas fa-heart-broken"></i>
                            </button><br>Unfavorite
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="no-authors">
                    <i class="fas fa-heart" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No favorite authors</h3>
                    <p>Authors you favorite will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Delete Publication Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Publication</h3>
            <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete "<span id="deleteTitle"></span>"?</p>
            <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmDeleteBtn" onclick="confirmDelete()" class="btn btn-danger">Delete</button>
        </div>
    </div>
</div>

<!-- Unsave Publication Modal -->
<div id="unsaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Unsave Publication</h3>
            <span class="close-modal" onclick="closeUnsaveModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Remove "<span id="unsaveTitle"></span>" from your saved publications?</p>
            <p>You can always save it again later.</p>
        </div>
        <div class="modal-footer">
            <button onclick="closeUnsaveModal()" class="btn btn-secondary">Cancel</button>
            <button id="confirmUnsaveBtn" onclick="confirmUnsave()" class="btn btn-danger">Remove</button>
        </div>
    </div>
</div>

<script>
// Delete publication functionality
let publicationToDelete = null;

function deletePublication(publicationID, title) {
    publicationToDelete = publicationID;
    document.getElementById('deleteTitle').textContent = title;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    publicationToDelete = null;
}

function confirmDelete() {
    if (!publicationToDelete) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmDeleteBtn');
    btn.disabled = true;
    btn.textContent = 'Deleting...';

    fetch('../home/delete_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToDelete)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to reflect changes
            location.reload();
        } else {
            alert('Error deleting publication: ' + (data.message || 'Unknown error'));
            closeDeleteModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the publication.');
        closeDeleteModal();
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Delete';
    });
}

// Unsave publication functionality
let publicationToUnsave = null;

function unsavePublication(publicationID, title) {
    publicationToUnsave = publicationID;
    document.getElementById('unsaveTitle').textContent = title;
    document.getElementById('unsaveModal').style.display = 'flex';
}

function closeUnsaveModal() {
    document.getElementById('unsaveModal').style.display = 'none';
    publicationToUnsave = null;
}

function confirmUnsave() {
    if (!publicationToUnsave) return;

    // Disable button to prevent double-clicks
    const btn = document.getElementById('confirmUnsaveBtn');
    btn.disabled = true;
    btn.textContent = 'Removing...';

    fetch('unsave_publication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'publicationID=' + encodeURIComponent(publicationToUnsave)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to reflect changes
            location.reload();
        } else {
            alert('Error removing publication: ' + (data.message || 'Unknown error'));
            closeUnsaveModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the publication.');
        closeUnsaveModal();
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Remove';
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const deleteModal = document.getElementById('deleteModal');
    const unsaveModal = document.getElementById('unsaveModal');
    const previewModal = document.getElementById('previewModal');

    if (event.target === deleteModal) {
        closeDeleteModal();
    }
    if (event.target === unsaveModal) {
        closeUnsaveModal();
    }
    if (previewModal && event.target === previewModal) {
        closePreviewModal();
    }
}

    // Unfavorite author functionality
    function unfavoriteAuthor(authorID) {
        if (confirm('Remove this author from your favorites?')) {
            fetch('../authors/toggle_favorite_author.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'authorID=' + encodeURIComponent(authorID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the author card from the page
                    const authorCard = document.querySelector(`button[onclick*="${authorID}"]`).closest('.author-card');
                    if (authorCard) {
                        authorCard.remove();
                    }
                    // Check if there are no more favorite authors
                    const remainingCards = document.querySelectorAll('.authors-grid .author-card');
                    if (remainingCards.length === 0) {
                        const authorsGrid = document.querySelector('.authors-grid');
                        authorsGrid.innerHTML = `
                            <div class="no-authors">
                                <i class="fas fa-heart" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                                <h3>No favorite authors</h3>
                                <p>Authors you favorite will appear here.</p>
                            </div>
                        `;
                    }
                } else {
                    alert('Error removing author: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the author.');
            });
        }
    }

    // Publication preview functionality
    function previewPublication(filePath, title, author, publishDate, abstract, department, type) {
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
                    <div class="publication-details">
                        <div class="detail-row">
                            <strong>Author:</strong> <span>${author}</span>
                        </div>
                        <div class="detail-row">
                            <strong>Published:</strong> <span>${formattedDate}</span>
                        </div>
                        ${department ? `<div class="detail-row"><strong>Department:</strong> <span>${department}</span></div>` : ''}
                        ${type ? `<div class="detail-row"><strong>Type:</strong> <span>${type}</span></div>` : ''}
                        ${abstract ? `<div class="detail-row"><strong>Abstract:</strong> <div class="abstract-text">${abstract}</div></div>` : ''}
                    </div>
                    <!-- File preview removed as requested -->
                    <!-- <div class="preview-iframe-container">
                        <iframe src="${filePath}" width="100%" height="500px" style="border: none;"></iframe>
                    </div> -->
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
