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

// Handle file upload
if (isset($_POST['upload'])) {
    $studentID = $_SESSION['studentID'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    // Handle file upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['file']['name'];
        $fileTmp = $_FILES['file']['tmp_name'];
        $uploadDir = "../uploads/";
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filePath = $uploadDir . basename($fileName);
        move_uploaded_file($fileTmp, $filePath);
        
        // Insert into database (adjust table name as needed)
        // $stmt = $conn->prepare("INSERT INTO uploads (studentID, title, description, file_path) VALUES (?, ?, ?, ?)");
        // $stmt->bind_param("ssss", $studentID, $title, $description, $filePath);
        // $stmt->execute();
        // $stmt->close();
        
        $successMessage = "File uploaded successfully!";
    }
}

// Set layout variables
$pageTitle = 'Upload';
$activeNav = 'upload';
$additionalCSS = ['upload/upload.css'];

// Include layout header
include "../layout/layout.php";
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Upload Document</h1>
</div>

<!-- Upload Form -->
<div class="content-section">
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required placeholder="Enter document title">
        </div>
        
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" rows="5" placeholder="Enter document description"></textarea>
        </div>
        
        <div class="form-group">
            <label for="file">Select File:</label>
            <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.txt">
            <small>Accepted formats: PDF, DOC, DOCX, TXT</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="upload" class="btn btn-primary">Upload</button>
            <button type="reset" class="btn btn-secondary">Clear</button>
        </div>
    </form>
</div>

<?php
// Include layout footer
include "../layout/layout_footer.php";
?>

