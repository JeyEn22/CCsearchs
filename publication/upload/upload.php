<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Research</title>
  <link rel="stylesheet" href="upload.css">
</head>

<body>

  <div class="outer-background">
    <div class="white-box">

      <!-- LEFT SECTION -->
      <div class="left-section">
        <div class="back">&lt;&lt; Back</div>

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
          <input type="text" id="authors" value="Batas, John Nathaniel - De Leon, Jelly - Nasser, Maxion - Salvador, Sheree Ann">
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

  <script src="upload.js"></script>
</body>
</html>
