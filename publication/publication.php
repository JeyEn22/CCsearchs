<?php
session_start();
include "../database/database.php";

// Redirect to login if not logged in
if (!isset($_SESSION['studentID'])) {
  header("Location: ../login/login.html");
  exit();
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Handle publication upload
if (isset($_POST['publish'])) {
  $studentID = $_SESSION['studentID'];
  $title = $_POST['title'];
  $published = date('Y-m-d H:i:s', strtotime($_POST['published']));
  $authors = $_POST['authors'];
  $department = $_POST['department'];
  $type = $_POST['type'];
  $abstract = $_POST['abstract'];

  // Handle PDF/Word file upload
  $fileName = $_FILES['file']['name'];
  $fileTmp = $_FILES['file']['tmp_name'];
  $filePath = "../uploads/" . basename($fileName);
  move_uploaded_file($fileTmp, $filePath);

  // Handle card background image upload
  $bgName = $_FILES['bg_image']['name'];
  $bgTmp = $_FILES['bg_image']['tmp_name'];
  $bgPath = "../uploads/" . basename($bgName);
  move_uploaded_file($bgTmp, $bgPath);

  // Insert into publications table
  $stmt = $conn->prepare("INSERT INTO publications 
        (studentID, title, published_datetime, authors, department, type, abstract, file_path, bg_image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("sssssssss", $studentID, $title, $published, $authors, $department, $type, $abstract, $filePath, $bgPath);
  $stmt->execute();

  $publicationID = $stmt->insert_id;

  // Insert into library
  $stmt2 = $conn->prepare("INSERT INTO library (studentID, publicationID) VALUES (?, ?)");
  $stmt2->bind_param("si", $studentID, $publicationID);
  $stmt2->execute();

  $stmt->close();
  $stmt2->close();

  echo "<script>alert('Publication uploaded successfully!'); location.href='publication.php';</script>";
}

// Set layout variables
$pageTitle = 'Publication';
$activeNav = 'publication';
$additionalCSS = ['publication_page.css'];
$additionalExternalCSS = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Top Bar -->
<div class="top-bar">
    <span class="back" onclick="goBack()">&larr; Back</span>
    <h1>Publication</h1>
      <div class="search-box">
          <input type="text" placeholder="Search">
          <img src="../icons/authors/search.png" class="search-icon" alt="Search">
      </div>
    <i class="fa fa-filter filter-icon"></i>
    <i class="fa fa-bars menu-icon"></i>
</div>

<!-- Scroll Area -->
<div class="scroll-area">
    <!-- Research Titles -->
    <section class="section-box">
        <h2>Research Titles</h2>
        <div class="card-row">
            <div class="card-item upload-card" onclick="openModal()">
                <i class="fa fa-plus"></i>
                <span>Upload more books</span>
            </div>

            <?php
            $studentID = $_SESSION['studentID'];
            $result = $conn->query("SELECT * FROM publications WHERE studentID='$studentID' ORDER BY published_datetime DESC");
            if ($result) {
              while ($row = $result->fetch_assoc()) {
                echo '<div class="card-item">';
                echo '<div class="doc-card" style="background-image:url(' . htmlspecialchars($row['bg_image']) . '); background-size:cover; background-position:center;"></div>';
                echo '<p class="card-title">' . htmlspecialchars($row['title']) . '</p>';
                echo '<small>' . date("M d, Y", strtotime($row['published_datetime'])) . '</small>';
                echo '</div>';
              }
            }
            ?>
        </div>
    </section>

    <!-- Other Documents -->
    <section class="section-box">
        <h2>Other Documents</h2>
        <div class="card-row">
            <?php
            $result2 = $conn->query("SELECT * FROM publications WHERE studentID<>'$studentID' ORDER BY published_datetime DESC");
            if ($result2) {
              while ($row2 = $result2->fetch_assoc()) {
                echo '<div class="card-item">';
                echo '<div class="doc-card" style="background-image:url(' . htmlspecialchars($row2['bg_image']) . '); background-size:cover; background-position:center;"></div>';
                echo '<p class="card-title">' . htmlspecialchars($row2['title']) . '</p>';
                echo '<small>' . date("M d, Y", strtotime($row2['published_datetime'])) . '</small>';
                echo '</div>';
              }
            }
            ?>
        </div>
        <p class="caption">CCSearch Research Records of CCSP</p>
    </section>
</div>

<!-- Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <form method="post" enctype="multipart/form-data" class="white-box">
            <!-- LEFT SECTION -->
            <div class="left-section">
                <div class="back" onclick="closeModal()">&lt;&lt; Back</div>
                <div class="preview-container">
                    <img src="../icons/publications/template_card.png" alt="Document Preview" class="preview-frame">
                </div>
                <!-- PDF/Word upload -->
                <button type="button" class="upload-btn" onclick="document.getElementById('file-input').click()">Upload File</button>
                <input type="file" id="file-input" name="file" style="display:none" accept=".pdf,.doc,.docx,.txt">
                <!-- Background image upload -->
                <button type="button" class="upload-btn" onclick="document.getElementById('bg-input').click()">Upload Background</button>
                <input type="file" id="bg-input" name="bg_image" style="display:none" accept=".jpg,.jpeg,.png">
            </div>
            <!-- RIGHT SECTION -->
            <div class="right-section">
                <div class="form-group">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="published">Published:</label>
                    <input type="datetime-local" id="published" name="published" required>
                </div>
                <div class="form-group">
                    <label for="authors">Authors:</label>
                    <input type="text" id="authors" name="authors" required>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department">
                        <option>Institute of Information Technology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="types">Types:</label>
                    <select id="types" name="type">
                        <option>Reference Book</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="abstract">Abstract/Description:</label>
                    <textarea id="abstract" name="abstract"></textarea>
                </div>
                <div class="publish-wrapper">
                    <button type="submit" name="publish" class="publish-btn">Publish</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$pageScripts = '
<script>
    function openModal() { document.getElementById("uploadModal").style.display = "block"; }
    function closeModal() { document.getElementById("uploadModal").style.display = "none"; }
    window.onclick = function (event) {
        const modal = document.getElementById("uploadModal");
        if (event.target === modal) modal.style.display = "none";
    }
    function goBack() { window.history.back(); }
</script>
';

// Include layout footer
include "../layout/layout_footer.php";
?>
