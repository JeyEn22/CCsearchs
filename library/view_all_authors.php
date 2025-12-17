<?php
session_start();
include "../database/database.php";

// Redirect to login if user is not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

$studentID = $_SESSION['studentID'];

// Get filter parameters
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : '';

// Fetch all favorite authors
$query = "SELECT r.*, up.profileImage, up.theme_preference, COUNT(p.publicationID) as totalPublications
          FROM favorite_authors fa
          JOIN registration r ON fa.favorite_studentID = r.studentID
          LEFT JOIN user_profiles up ON fa.favorite_studentID = up.studentID
          LEFT JOIN publications p ON fa.favorite_studentID = p.studentID
          WHERE fa.studentID = ?";

$params = [$studentID];
$paramTypes = 's';

// Apply search filter
if (!empty($searchQuery)) {
    $query .= " AND (CONCAT(r.firstName, ' ', r.lastName) LIKE ? OR r.studentID LIKE ? OR r.department LIKE ?)";
    $searchParam = '%' . $searchQuery . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $paramTypes .= 'sss';
}

// Apply department filter
if (!empty($departmentFilter)) {
    $query .= " AND r.department = ?";
    $params[] = $departmentFilter;
    $paramTypes .= 's';
}

$query .= " GROUP BY r.studentID, r.firstName, r.lastName, r.emailAddress, r.contactNumber, r.currentAddress, r.department, up.profileImage, up.theme_preference
            ORDER BY fa.added_datetime DESC";

// Execute query
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $allAuthors = [];
    while ($row = $result->fetch_assoc()) {
        $allAuthors[] = $row;
    }
    $stmt->close();
} else {
    $allAuthors = [];
}

// Get unique departments for filter dropdown
$deptQuery = "SELECT DISTINCT r.department 
              FROM favorite_authors fa
              JOIN registration r ON fa.favorite_studentID = r.studentID
              WHERE fa.studentID = ? AND r.department IS NOT NULL AND r.department != ''
              ORDER BY r.department";
$deptStmt = $conn->prepare($deptQuery);
$departments = [];
if ($deptStmt) {
    $deptStmt->bind_param("s", $studentID);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
    $deptStmt->close();
}

// Set layout variables
$pageTitle = 'View All Favorite Authors';
$activeNav = 'library';
$additionalCSS = ['library_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Top Bar -->
<div class="top-bar">
  <span class="back" onclick="goBack()">&larr; Back</span>
  <div class="top-bar-right">
    <h1>View All Favorite Authors</h1>
    <div class="search-box">
      <input type="text" id="searchInput" placeholder="Search authors" value="<?php echo htmlspecialchars($searchQuery); ?>">
      <img src="../icons/authors/search.png" class="search-icon" alt="Search">
    </div>
    <i class="fa fa-filter filter-icon" onclick="toggleFilterModal()"></i>
  </div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="filter-modal">
  <div class="filter-modal-content">
    <div class="filter-modal-header">
      <h3>Filter Authors</h3>
      <span class="close-filter" onclick="toggleFilterModal()">&times;</span>
    </div>
    <div class="filter-modal-body">
      <div class="filter-group">
        <label>Department:</label>
        <select id="departmentFilter" class="filter-select">
          <option value="">All Departments</option>
          <?php foreach ($departments as $dept): ?>
            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $departmentFilter === $dept ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($dept); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="filter-modal-footer">
      <button class="btn btn-secondary" onclick="toggleFilterModal()">Cancel</button>
      <button class="btn btn-primary" onclick="applyFilters()">Apply</button>
    </div>
  </div>
</div>

<!-- Content Section -->
<div class="content-section">
  <?php if (!empty($allAuthors)): ?>
    <div class="authors-grid" id="authorsGrid">
      <?php foreach ($allAuthors as $author): ?>
        <div class="author-card" data-name="<?php echo htmlspecialchars(strtolower($author['firstName'] . ' ' . $author['lastName'])); ?>" data-studentid="<?php echo htmlspecialchars(strtolower($author['studentID'])); ?>" data-department="<?php echo htmlspecialchars(strtolower($author['department'] ?? '')); ?>">
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
    </div>
  <?php else: ?>
    <div class="no-authors" style="text-align: center; padding: 60px 20px;">
      <i class="fas fa-heart" style="font-size: 48px; color: #ccc; margin-bottom: 20px; display: block;"></i>
      <h3>No favorite authors found</h3>
      <p>Try adjusting your search or filters to see favorite authors.</p>
    </div>
  <?php endif; ?>
</div>

<script>
// Back button functionality
function goBack() {
  window.history.back();
}

// Toggle filter modal
function toggleFilterModal() {
  const modal = document.getElementById('filterModal');
  if (modal.style.display === 'flex') {
    modal.style.display = 'none';
  } else {
    modal.style.display = 'flex';
  }
}

// Apply filters
function applyFilters() {
  const searchInput = document.getElementById('searchInput');
  const departmentFilter = document.getElementById('departmentFilter');
  
  const params = new URLSearchParams();
  if (searchInput.value) {
    params.append('search', searchInput.value);
  }
  if (departmentFilter.value) {
    params.append('department', departmentFilter.value);
  }
  
  const queryString = params.toString();
  window.location.href = queryString ? 'view_all_authors.php?' + queryString : 'view_all_authors.php';
}

// Search functionality
document.getElementById('searchInput').addEventListener('keypress', function(e) {
  if (e.key === 'Enter') {
    applyFilters();
  }
});

// Search icon click
document.querySelector('.search-icon').addEventListener('click', function() {
  applyFilters();
});

// Unfavorite author functionality
function unfavoriteAuthor(authorID) {
  if (confirm('Are you sure you want to remove this author from your favorites?')) {
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
          // Reload the page to reflect changes
          location.reload();
        } else {
          alert('Error removing favorite');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
      });
  }
}

// Filter modal close when clicking outside
document.getElementById('filterModal').addEventListener('click', function(e) {
  if (e.target === this) {
    toggleFilterModal();
  }
});
</script>

<style>
.top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px;
  background: #27548a;
  border-bottom: 1px solid #1e3e68;
  gap: 15px;
  margin-bottom: 20px;
  position: sticky;
  top: 0;
  z-index: 100;
  border-radius: 8px;
}

.top-bar .back {
  cursor: pointer;
  font-size: 18px;
  color: white;
  font-weight: 600;
  min-width: 60px;
  flex-shrink: 0;
}

.top-bar h1 {
  font-size: 18px;
  margin: 0;
  color: white;
  white-space: nowrap;
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 15px;
  flex-shrink: 0;
}

.top-bar-center {
  display: flex;
  align-items: center;
  gap: 15px;
  flex: 0 1 auto;
}

.top-bar .search-box {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 20px;
  padding: 8px 15px;
  width: 300px;
  background: white;
  flex-shrink: 0;
}

.top-bar .search-box input {
  border: none;
  outline: none;
  background: transparent;
  flex: 1;
  font-size: 14px;
  color: #333;
}

.top-bar .search-icon,
.top-bar .filter-icon,
.top-bar .menu-icon {
  cursor: pointer;
  font-size: 20px;
  color: white;
}

.top-bar .search-icon:hover,
.top-bar .filter-icon:hover {
  color: #ddd;
}

.filter-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  justify-content: center;
  align-items: center;
}

.filter-modal-content {
  background: white;
  border-radius: 10px;
  width: 90%;
  max-width: 400px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.filter-modal-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-modal-header h3 {
  margin: 0;
  font-size: 18px;
  color: #333;
}

.close-filter {
  font-size: 24px;
  cursor: pointer;
  color: #aaa;
}

.close-filter:hover {
  color: #333;
}

.filter-modal-body {
  padding: 20px;
}

.filter-group {
  margin-bottom: 20px;
}

.filter-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #333;
  font-size: 14px;
}

.filter-select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.filter-modal-footer {
  padding: 15px 20px;
  border-top: 1px solid #eee;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.content-section {
  padding: 20px;
}

.authors-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  padding: 0;
}

.no-authors {
  color: #999;
  padding: 40px;
}

@media (max-width: 768px) {
  .top-bar {
    flex-wrap: wrap;
    gap: 10px;
  }

  .top-bar h1 {
    flex-basis: 100%;
    text-align: left;
  }

  .top-bar .search-box {
    max-width: 100%;
    flex: 1;
  }

  .authors-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
  }
}

/* Dark Mode Styles */
body.dark-theme .top-bar {
  background-color: #1E1E1E;
  border-bottom-color: #2A2A2A;
}

body.dark-theme .top-bar h1,
body.dark-theme .top-bar .back {
  color: #E0E0E0;
}

body.dark-theme .top-bar .search-box {
  background: #1E1E1E;
  border-color: #333;
}

body.dark-theme .top-bar .search-box input {
  color: #E0E0E0;
  background: transparent;
}

body.dark-theme .top-bar .search-box input::placeholder {
  color: #999;
}

body.dark-theme .top-bar .search-icon,
body.dark-theme .top-bar .filter-icon,
body.dark-theme .top-bar .menu-icon {
  color: #E0E0E0;
}

body.dark-theme .top-bar .search-icon:hover,
body.dark-theme .top-bar .filter-icon:hover {
  color: #27548a;
}

body.dark-theme .filter-modal-content {
  background: #1E1E1E;
}

body.dark-theme .filter-modal-header {
  border-bottom-color: #2A2A2A;
  color: #E0E0E0;
}

body.dark-theme .filter-modal-header h3 {
  color: #E0E0E0;
}

body.dark-theme .close-filter {
  color: #999;
}

body.dark-theme .close-filter:hover {
  color: #E0E0E0;
}

body.dark-theme .filter-modal-body {
  color: #E0E0E0;
}

body.dark-theme .filter-group label {
  color: #E0E0E0;
}

body.dark-theme .filter-select {
  background: #1E1E1E;
  color: #E0E0E0;
  border-color: #333;
}

body.dark-theme .filter-modal-footer {
  border-top-color: #2A2A2A;
}

/* Responsive Design */
@media (max-width: 1023px) {
  .top-bar {
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px;
  }

  .top-bar h1 {
    font-size: 16px;
  }

  .top-bar .search-box {
    width: 200px;
  }

  .authors-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
  }
}

@media (max-width: 767px) {
  .top-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    padding: 10px;
  }

  .top-bar .back {
    width: 100%;
  }

  .top-bar h1 {
    font-size: 14px;
    width: 100%;
  }

  .top-bar-right {
    width: 100%;
    gap: 10px;
  }

  .top-bar .search-box {
    flex: 1;
    width: auto;
  }

  .authors-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    padding: 0;
  }

  .content-section {
    padding: 10px;
  }

  .filter-modal-content {
    width: 95%;
    max-width: 100%;
  }

  .author-card {
    padding: 10px;
  }

  .banner-img {
    height: 80px;
  }

  .profile-circle {
    width: 60px;
    height: 60px;
  }
}

@media (max-width: 479px) {
  .top-bar {
    padding: 8px;
  }

  .top-bar h1 {
    font-size: 12px;
  }

  .top-bar .search-box {
    font-size: 12px;
    padding: 6px 10px;
  }

  .top-bar .search-icon {
    font-size: 16px;
  }

  .top-bar .filter-icon {
    font-size: 16px;
  }

  .authors-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
  }

  .author-card h3 {
    font-size: 12px;
  }

  .author-card .username {
    font-size: 10px;
  }

  .stats {
    font-size: 10px;
  }

  .visit-btn {
    font-size: 10px;
    padding: 5px 10px;
  }
}
</style>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>
