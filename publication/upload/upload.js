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
