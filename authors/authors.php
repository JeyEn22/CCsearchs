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

// Fetch authors data
include "../database/database.php";
$authorsQuery = "
    SELECT
        r.studentID,
        r.firstName,
        r.lastName,
        r.emailAddress,
        r.contactNumber,
        r.currentAddress,
        r.department,
        up.profileImage,
        up.theme_preference,
        up.is_public,
        COUNT(p.publicationID) as totalPublications
    FROM registration r
    LEFT JOIN user_profiles up ON r.studentID = up.studentID
    LEFT JOIN publications p ON r.studentID = p.studentID
    WHERE r.studentID != '{$_SESSION['studentID']}'
    GROUP BY r.studentID, r.firstName, r.lastName, r.emailAddress, r.contactNumber, r.currentAddress, r.department, up.profileImage, up.theme_preference, up.is_public
    ORDER BY r.studentID
";

$authorsResult = $conn->query($authorsQuery);
$authors = [];
if ($authorsResult) {
    while ($row = $authorsResult->fetch_assoc()) {
        $authors[] = $row;
    }
    $authorsResult->free();
}
$conn->close();

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
        <?php if (!empty($authors)): ?>
            <?php foreach ($authors as $author): ?>
                <div class="author-card">
                    <img src="../icons/authors/card_bg.png" class="banner-img" alt="Author Banner">
                    <div class="profile-circle" style="background-image: url('<?php echo htmlspecialchars(!empty($author['profileImage']) ? '../' . $author['profileImage'] : '../uploads/profiles/profile.png'); ?>');"></div>
                    <h3><?php echo htmlspecialchars($author['firstName'] . ' ' . $author['lastName']); ?></h3>
                    <p class="username"><?php echo htmlspecialchars($author['studentID']); ?></p>
                    <a href="../profile/profile_view.php?studentID=<?php echo htmlspecialchars($author['studentID']); ?>" class="visit-btn">Visit</a>
                    <div class="stats">
                        <div><strong><?php echo htmlspecialchars($author['totalPublications']); ?></strong><br>Books</div>
                        <div>
                            <button onclick="toggleFavorite('<?php echo htmlspecialchars($author['studentID']); ?>', this)" class="favorite-btn" id="fav-<?php echo htmlspecialchars($author['studentID']); ?>">
                                <i class="fas fa-heart"></i>
                            </button><br>Favorite
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-authors">
                <p>No authors found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Favorite author functionality
function toggleFavorite(authorID, button) {
    const icon = button.querySelector('i');

    // Toggle visual state immediately
    const isFavorited = button.classList.contains('favorited');
    if (isFavorited) {
        button.classList.remove('favorited');
        icon.className = 'far fa-heart'; // Empty heart when not favorited
    } else {
        button.classList.add('favorited');
        icon.className = 'fas fa-heart'; // Filled heart when favorited
    }

    // Send request to server
    fetch('toggle_favorite_author.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'authorID=' + encodeURIComponent(authorID)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Revert visual state on error
            if (isFavorited) {
                button.classList.add('favorited');
                icon.className = 'fas fa-heart';
            } else {
                button.classList.remove('favorited');
                icon.className = 'far fa-heart';
            }
            alert('Error updating favorite: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert visual state on error
        if (isFavorited) {
            button.classList.add('favorited');
            icon.className = 'fas fa-heart';
        } else {
            button.classList.remove('favorited');
            icon.className = 'far fa-heart';
        }
        alert('An error occurred while updating favorite.');
    });
}

// Initialize favorite states on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load current favorite states
    fetch('get_favorite_authors.php')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.favorites) {
            data.favorites.forEach(authorID => {
                const button = document.getElementById(`fav-${authorID}`);
                if (button) {
                    button.classList.add('favorited');
                    const icon = button.querySelector('i');
                    if (icon) icon.className = 'fas fa-heart'; // Filled heart for favorited
                }
            });

            // Set empty heart for non-favorited authors
            const allButtons = document.querySelectorAll('.favorite-btn');
            allButtons.forEach(button => {
                if (!button.classList.contains('favorited')) {
                    const icon = button.querySelector('i');
                    if (icon) icon.className = 'far fa-heart'; // Empty heart for not favorited
                }
            });
        }
    })
    .catch(error => {
        console.error('Error loading favorites:', error);
    });
});
</script>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
