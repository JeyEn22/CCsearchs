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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Publication</title>
  <link rel="stylesheet" href="publication.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

  <div class="layout-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div>
        <div class="logo-container">
          <img src="../image/Icon.png" alt="Logo" class="sidebar-logo" />
          <h2>CCSEARCH</h2>
        </div>
        <nav class="nav-menu">
          <a href="../home/home.php"><img src="../icons/sidebar-icons/home.png" alt=""> Home</a>
          <a href="../profile/profile.php"><img src="../icons/sidebar-icons/profile.png" alt=""> Profile</a>
          <a href="../library/library.php"><img src="../icons/sidebar-icons/library.png" alt=""> My Library</a>
          <a href="#" class="active"><img src="../icons/sidebar-icons/publication.png" alt="">
            Publication</a>
          <a href="../authors/authors.php"><img src="../icons/sidebar-icons/authors.png" alt=""> Authors</a>
          <a href="../notification/notification.php"><img src="../icons/sidebar-icons/notification.png" alt="">
            Notification</a>
        </nav>
      </div>
      <div class="logout">
        <a href="logout.php"><img src="../icons/sidebar-icons/logout.png"> Logout</a>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">
      <div class="top-bar">
        <span class="back" onclick="goBack()">&larr; Back</span>
        <h1>Publication</h1>
        <div class="search-bar">
          <input type="text" placeholder="Search">
          <i class="fa fa-search"></i>
        </div>
        <i class="fa fa-filter filter-icon"></i>
        <i class="fa fa-bars menu-icon"></i>
      </div>

      <div class="scroll-area">

        <!-- Research Titles -->
        <section class="section-box">
          <h2>Research Titles</h2>
          <div class="card-row">
            <div class="card-item upload-card" onclick="openModal()">
              <i class="fa fa-plus"></i>
              <span>Upload more books</span>
            </div>

            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 1</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 2</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 3</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 4</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 5</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 6</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Document 7</p>
            </div>
          </div>
          <p class="caption">Case Study (by Booking Activity)</p>
        </section>

        <!-- Other Documents -->
        <section class="section-box">
          <h2>Other Documents</h2>
          <div class="card-row">
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 1</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 2</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 3</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 4</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 5</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 6</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 7</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 8</p>
            </div>
          </div>
          <div class="card-row">
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 9</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 10</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 11</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 12</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 13</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 14</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 15</p>
            </div>
            <div class="card-item">
              <div class="doc-card"></div>
              <p class="card-title">Doc 16</p>
            </div>
          </div>
          <p class="caption">CCSearch Research Records of CCSP</p>
        </section>

      </div>
    </main>
  </div>

  <!-- Modal -->
  <div id="uploadModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>

      <!-- Your current white-box content -->
      <div class="white-box">
        <!-- LEFT SECTION -->
        <div class="left-section">
          <div class="back" onclick="closeModal()">&lt;&lt; Back</div>
          <div class="preview-container">
            <img src="frame-image.png" alt="Document Preview" class="preview-frame">
          </div>
          <button class="upload-btn" onclick="document.getElementById('file-input').click()">Upload File</button>
          <input type="file" id="file-input" style="display:none" accept=".pdf,.doc,.docx,.txt">
        </div>

        <!-- RIGHT SECTION -->
        <div class="right-section">
          <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" value="CCSearch: Research Website of CCSFP">
          </div>
          <div class="form-group">
            <label for="published">Published:</label>
            <input type="text" id="published" value="October 09, 2025 2:07 PM">
          </div>
          <div class="form-group">
            <label for="authors">Authors:</label>
            <input type="text" id="authors"
              value="Batas, John Nathaniel - De Leon, Jelly - Nasser, Maxion - Salvador, Sheree Ann">
          </div>
          <div class="form-group">
            <label for="department">Department:</label>
            <select id="department">
              <option>Institute of Information Technology</option>
            </select>
          </div>
          <div class="form-group">
            <label for="types">Types:</label>
            <select id="types">
              <option>Reference Book</option>
            </select>
          </div>
          <div class="form-group">
            <label for="abstract">Abstract/Description:</label>
            <textarea id="abstract"></textarea>
          </div>
          <div class="publish-wrapper">
            <button class="publish-btn">Publish</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Detect page show from back/forward cache
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        // Page was loaded from bfcache, force reload
        window.location.reload(0);
      }
    });


    function openModal() {
      document.getElementById("uploadModal").style.display = "block";
    }

    function closeModal() {
      document.getElementById("uploadModal").style.display = "none";
    }

    // Close if clicked outside modal content
    window.onclick = function (event) {
      const modal = document.getElementById("uploadModal");
      if (event.target === modal) {
        modal.style.display = "none";
      }
    };


    const uploadBtn = document.querySelector('.upload-btn');
    const fileInput = document.getElementById('file-input');
    const previewBox = document.querySelector('.preview-box');
    const previewText = previewBox.querySelector('.preview-text');
    const previewIcon = previewBox.querySelector('.preview-icon');

    // Trigger file input
    uploadBtn.addEventListener('click', () => {
      fileInput.click();
    });

    // When a file is selected
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (file) {
        previewText.textContent = file.name;

        // Change icon based on file type
        const ext = file.name.split('.').pop().toLowerCase();
        switch (ext) {
          case 'pdf':
            previewIcon.textContent = '📕';
            break;
          case 'doc':
          case 'docx':
            previewIcon.textContent = '📄';
            break;
          case 'txt':
            previewIcon.textContent = '📝';
            break;
          default:
            previewIcon.textContent = '📁';
        }
      } else {
        previewText.textContent = 'No file selected';
        previewIcon.textContent = '📄';
      }
    });

    function goBack() {
      window.history.back();
    }


  </script>

</body>

</html>