// Check if we're in public view and disable update button
document.addEventListener('DOMContentLoaded', function () {
  console.log('DOM loaded, checking public view status');
  console.log('Body classes:', document.body.className);

  // Log current profile image source
  const profilePic = document.getElementById('profilePic');
  if (profilePic) {
    console.log('Current profile image src:', profilePic.src);
  }

  // Check if URL contains public=1
  const urlParams = new URLSearchParams(window.location.search);
  const isPublicView = urlParams.get('public') === '1';

  if (isPublicView) {
    console.log('Public view detected, adding class and disabling update button');
    document.body.classList.add('public-view');

    const updateButton = document.getElementById("updateProfile");
    if (updateButton) {
      updateButton.disabled = true;
      updateButton.textContent = 'Updates Disabled (Public View)';
      console.log('Update button disabled');
    } else {
      console.log('Update button not found');
    }
  } else {
    console.log('Private view detected');
  }

  // Check for URL hash to activate specific tab
  const hash = window.location.hash.substring(1); // Remove the '#'
  if (hash) {
    console.log('URL hash detected:', hash);
    const targetTab = document.querySelector(`[data-tab="${hash}"]`);
    if (targetTab) {
      console.log('Activating tab:', hash);
      // Simulate click on the target tab
      targetTab.click();
    }
  }
});

// Tab switch
const tabs = document.querySelectorAll(".tab");
const contents = document.querySelectorAll(".tab-content");

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    // Remove active tab highlight
    tabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");

    // Hide all content & fade out
    contents.forEach(c => {
      c.classList.add("hidden");
      c.style.opacity = 0;
    });

    // Show selected panel
    const activeContent = document.getElementById(tab.dataset.tab);
    activeContent.classList.remove("hidden");

    // Fade-in effect
    setTimeout(() => {
      activeContent.style.opacity = 1;
    }, 50);
  });
});

// Button action for public/private view toggle is handled inline in HTML
// The onclick attribute in the HTML handles the navigation

// Edit/Update toggle functionality
document.addEventListener('DOMContentLoaded', function () {
  const editButton = document.getElementById("editProfile");
  const updateButton = document.getElementById("updateProfile");
  const personalInputs = document.querySelectorAll('#personal input');
  const cameraIcon = document.querySelector('.camera-icon');
  
  let isEditMode = false;

  if (editButton) {
    editButton.addEventListener("click", function (e) {
      e.preventDefault();
      isEditMode = !isEditMode;

      if (isEditMode) {
        // Enable all inputs
        personalInputs.forEach(input => {
          input.removeAttribute('readonly');
        });
        editButton.style.display = 'none';
        updateButton.style.display = 'inline-block';
        
        // Show camera icon when in edit mode
        if (cameraIcon) {
          cameraIcon.style.display = 'block';
        }
      } else {
        // Disable all inputs
        personalInputs.forEach(input => {
          input.setAttribute('readonly', 'readonly');
        });
        editButton.style.display = 'inline-block';
        updateButton.style.display = 'none';
        
        // Hide camera icon when not in edit mode
        if (cameraIcon) {
          cameraIcon.style.display = 'none';
        }
      }
    });
  }
});

// Profile update functionality
document.addEventListener('DOMContentLoaded', function () {
  const updateButton = document.getElementById("updateProfile");
  if (updateButton) {
    updateButton.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent any default form submission

      console.log('Update button clicked'); // Debug log

      // Check if we're in public view - if so, don't allow updates
      if (document.body.classList.contains('public-view')) {
        showNotification('Cannot update profile in public view', 'error');
        return;
      }

      // Get form data using field names
      const firstName = document.querySelector('input[name="firstName"]')?.value?.trim() || '';
      const lastName = document.querySelector('input[name="lastName"]')?.value?.trim() || '';
      const contactNumber = document.querySelector('input[name="contactNumber"]')?.value?.trim() || '';
      const emailAddress = document.querySelector('input[name="emailAddress"]')?.value?.trim() || '';
      const currentAddress = document.querySelector('input[name="currentAddress"]')?.value?.trim() || '';
      const department = document.querySelector('input[name="department"]')?.value?.trim() || '';

      console.log('Form data:', { firstName, lastName, contactNumber, emailAddress, currentAddress, department }); // Debug log

      // Validate required fields
      if (!firstName || !lastName || !contactNumber || !emailAddress || !currentAddress || !department) {
        showNotification('Please fill in all required fields', 'error');
        return;
      }

      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailAddress)) {
        showNotification('Please enter a valid email address', 'error');
        return;
      }

      // Validate contact number (11 digits)
      if (!/^[0-9]{11}$/.test(contactNumber)) {
        showNotification('Contact number must be exactly 11 digits', 'error');
        return;
      }

      // Create form data
      const formData = new FormData();
      formData.append('firstName', firstName);
      formData.append('lastName', lastName);
      formData.append('contactNumber', contactNumber);
      formData.append('emailAddress', emailAddress);
      formData.append('currentAddress', currentAddress);
      formData.append('department', department);

      // Handle profile image upload (optional)
      const fileInput = document.getElementById('fileInput');
      if (fileInput && fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
          showNotification('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
          return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          showNotification('File size too large. Maximum size is 5MB.', 'error');
          return;
        }

        formData.append('profileImage', file);
        console.log('Profile image included in form data:', file.name);
      }

      // Show loading state
      const originalText = updateButton.textContent;
      updateButton.textContent = 'Updating...';
      updateButton.disabled = true;

      console.log('Sending request to profile_update.php'); // Debug log

      // Send update request
      fetch('profile_update.php', {
        method: 'POST',
        body: formData
      })
        .then(response => {
          console.log('Response received:', response); // Debug log
          return response.json();
        })
        .then(data => {
          console.log('Response data:', data); // Debug log
          showNotification(data.message, data.status);
          if (data.status === 'success') {
            // If profile image was updated, update the image source before reload
            if (data.profile_image) {
              console.log('Updating profile image to:', '../' + data.profile_image);
              const profilePic = document.getElementById('profilePic');
              if (profilePic) {
                profilePic.src = '../' + data.profile_image;
                profilePic.style.animation = "fadeIn 0.6s ease";
                console.log('Profile image updated successfully');
              } else {
                console.log('Profile picture element not found');
              }
            }
            // Refresh the page to show updated data
            setTimeout(() => {
              window.location.reload();
            }, 1500);
          }
        })
        .catch(error => {
          console.error('Fetch error:', error);
          showNotification('An error occurred. Please try again.', 'error');
          // Clear file input on error
          const fileInput = document.getElementById('fileInput');
          if (fileInput) {
            fileInput.value = '';
          }
        })
        .finally(() => {
          // Reset button state
          updateButton.textContent = originalText;
          updateButton.disabled = false;
        });
    });
  } else {
    console.log('Update button not found'); // Debug log
  }
});

// Simple notification function
function showNotification(message, type) {
  console.log('Showing notification:', message, type);

  // Remove existing notification
  const existingNotification = document.querySelector('.notification');
  if (existingNotification) {
    existingNotification.remove();
  }

  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification ${type}`;
  notification.textContent = message;
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10000;
    max-width: 400px;
    background-color: ${type === 'success' ? '#28a745' : '#dc3545'};
  `;

  document.body.appendChild(notification);

  // Auto remove after 5 seconds
  setTimeout(() => {
    if (notification.parentNode) {
      notification.parentNode.removeChild(notification);
    }
  }, 5000);
}

// Profile image upload preview
const fileInput = document.getElementById("fileInput");
const profilePic = document.getElementById("profilePic");

if (fileInput && profilePic) {
  fileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      // Validate file type
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
      if (!allowedTypes.includes(file.type)) {
        showNotification('Invalid file type. Only JPG, PNG, and GIF images are allowed.', 'error');
        // Clear the file input
        fileInput.value = '';
        return;
      }

      // Validate file size (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        showNotification('File size too large. Maximum size is 5MB.', 'error');
        // Clear the file input
        fileInput.value = '';
        return;
      }

      // Additional validation: check if it's actually an image
      if (!file.type.startsWith('image/')) {
        showNotification('Please select a valid image file.', 'error');
        fileInput.value = '';
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = (e) => {
        profilePic.src = e.target.result;
        profilePic.style.animation = "fadeIn 0.6s ease";
        showNotification('Image selected. Click "Update Profile" to save changes.', 'success');
      };
      reader.readAsDataURL(file);
    }
  });
}

// Smooth transitions for tab content
contents.forEach(content => {
  content.style.transition = "opacity 0.3s ease";
});

// Detect page show from back/forward cache
window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload(0);
  }
});

// Account Settings Functionality
document.addEventListener('DOMContentLoaded', function () {
  // Modal open/close with fade animations
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      // Set display immediately
      modal.style.display = 'flex';
      // Allow pointer events
      modal.style.pointerEvents = 'auto';
      // Trigger animation by adding active class
      setTimeout(() => {
        modal.classList.add('modal-active');
      }, 10);
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      // Start fade-out animation
      modal.classList.remove('modal-active');
      
      // Wait for animation to complete before hiding
      setTimeout(() => {
        modal.style.display = 'none';
        modal.style.pointerEvents = 'none';
      }, 300); // Match CSS animation duration
      
      document.body.style.overflow = 'auto';
    }
  }

  // Close modals when clicking on background
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
      // Only close if clicking directly on the modal background, not on modal-content
      if (e.target === this) {
        closeModal(this.id);
      }
    });
    
    // Prevent clicks inside modal-content from bubbling to the modal
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
      modalContent.addEventListener('click', function(e) {
        e.stopPropagation();
      });
    }
  });

  // Close buttons
  document.querySelectorAll('.close-modal').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const modalId = this.getAttribute('data-modal');
      closeModal(modalId);
    });
  });

  // Cancel buttons
  document.querySelectorAll('.cancel-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      const modalId = this.getAttribute('data-modal');
      closeModal(modalId);
    });
  });

  // ===== CHANGE PASSWORD BUTTON =====
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', function() {
      openModal('changePasswordModal');
    });
  }

  // Change password form submission
  const changePasswordForm = document.getElementById('changePasswordForm');
  if (changePasswordForm) {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmNewPassword');
    
    // Function to update validation indicators
    function updatePasswordValidation() {
      const password = newPasswordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      // Length check (8+ characters)
      const lengthCheck = document.getElementById('lengthCheck');
      const hasLength = password.length >= 8;
      updateValidationItem(lengthCheck, hasLength);

      // Uppercase check
      const uppercaseCheck = document.getElementById('uppercaseCheck');
      const hasUppercase = /[A-Z]/.test(password);
      updateValidationItem(uppercaseCheck, hasUppercase);

      // Number check
      const numberCheck = document.getElementById('numberCheck');
      const hasNumber = /\d/.test(password);
      updateValidationItem(numberCheck, hasNumber);

      // Match check
      const matchCheck = document.getElementById('matchCheck');
      const matches = password === confirmPassword && password.length > 0;
      updateValidationItem(matchCheck, matches);
    }

    // Helper function to update validation item appearance
    function updateValidationItem(element, isValid) {
      if (!element) return;
      
      const icon = element.querySelector('.validation-icon');
      if (isValid) {
        element.classList.add('valid');
        element.classList.remove('invalid');
        if (icon) {
          icon.className = 'fas fa-check validation-icon';
        }
      } else {
        element.classList.add('invalid');
        element.classList.remove('valid');
        if (icon) {
          icon.className = 'fas fa-times validation-icon';
        }
      }
    }

    // Show validation rules and update on input
    if (newPasswordInput) {
      newPasswordInput.addEventListener('input', function() {
        const validationContainer = document.getElementById('passwordValidation');
        if (validationContainer) {
          validationContainer.style.display = 'block';
        }
        updatePasswordValidation();
      });
    }

    if (confirmPasswordInput) {
      confirmPasswordInput.addEventListener('input', function() {
        updatePasswordValidation();
      });
    }

    changePasswordForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const newPassword = newPasswordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      // Validate password requirements
      if (newPassword.length < 8) {
        showNotification('Password must be at least 8 characters long.', 'error');
        return;
      }
      if (!/[A-Z]/.test(newPassword)) {
        showNotification('Password must contain at least one uppercase letter.', 'error');
        return;
      }
      if (!/\d/.test(newPassword)) {
        showNotification('Password must contain at least one number.', 'error');
        return;
      }
      if (newPassword !== confirmPassword) {
        showNotification('Passwords do not match.', 'error');
        return;
      }

      const formData = new FormData(this);

      fetch('account_actions.php?action=change_password', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            showNotification('Password changed successfully!', 'success');
            changePasswordForm.reset();
            const validationContainer = document.getElementById('passwordValidation');
            if (validationContainer) {
              validationContainer.style.display = 'none';
            }
            // Reset validation indicators
            document.querySelectorAll('#passwordValidation .validation-item').forEach(item => {
              item.classList.remove('valid');
              item.classList.add('invalid');
              const icon = item.querySelector('.validation-icon');
              if (icon) {
                icon.className = 'fas fa-times validation-icon';
              }
            });
            closeModal('changePasswordModal');
          } else {
            showNotification(data.message || 'Error changing password.', 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('An error occurred. Please try again.', 'error');
        });
    });
  }

  // ===== THEME BUTTON =====
  const themeBtn = document.getElementById('themeBtn');
  if (themeBtn) {
    themeBtn.addEventListener('click', function() {
      const currentTheme = window.themeManager ? window.themeManager.getCurrentTheme() : 'light';
      document.querySelector(`input[name="theme"][value="${currentTheme}"]`).checked = true;
      openModal('themeModal');
    });
  }

  // Theme option selection
  document.querySelectorAll('.theme-option').forEach(option => {
    option.addEventListener('click', function() {
      const theme = this.getAttribute('data-theme');
      document.querySelector(`input[name="theme"][value="${theme}"]`).checked = true;
      document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
      this.classList.add('selected');
    });
  });

  // Apply theme button
  const applyThemeBtn = document.getElementById('applyThemeBtn');
  if (applyThemeBtn) {
    applyThemeBtn.addEventListener('click', function() {
      const selectedTheme = document.querySelector('input[name="theme"]:checked');
      if (selectedTheme) {
        const theme = selectedTheme.value;
        if (window.themeManager) {
          window.themeManager.applyTheme(theme);
        }
        closeModal('themeModal');
        showNotification(`Theme changed to ${theme === 'dark' ? 'Dark' : 'Light'} Mode`, 'success');
      }
    });
  }

  // ===== DELETE ACCOUNT BUTTON =====
  const deleteAccountBtn = document.getElementById('deleteAccountBtn');
  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', function() {
      openModal('deleteAccountModal');
    });
  }

  // Delete account confirmation
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function() {
      const deleteInput = document.getElementById('deleteConfirmation');
      if (!deleteInput || deleteInput.value.trim() !== 'DELETE') {
        showNotification('Please type "DELETE" to confirm.', 'error');
        return;
      }

      if (confirm('Are you absolutely sure? This cannot be undone.')) {
        fetch('account_actions.php?action=delete_account', {
          method: 'POST'
        })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              showNotification('Account deleted. Redirecting...', 'success');
              setTimeout(() => {
                window.location.href = '../login/login.php';
              }, 2000);
            } else {
              showNotification(data.message || 'Error deleting account.', 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred.', 'error');
          });
      }
    });
  }
});
