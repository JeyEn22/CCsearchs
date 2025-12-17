/**
 * Real-time Session Checker
 * Polls the server every 2 seconds to detect if session is still valid
 * If session becomes invalid (user kicked out), shows modal and logs out
 */

// Guard against multiple inclusions
if (typeof sessionCheckInterval === 'undefined') {
  let sessionCheckInterval = null;

function startSessionChecker() {
  // Check session validity every 2 seconds
  sessionCheckInterval = setInterval(checkSessionValidity, 2000);
}

function stopSessionChecker() {
  if (sessionCheckInterval) {
    clearInterval(sessionCheckInterval);
  }
}

function checkSessionValidity() {
  fetch('../includes/check_session.php', {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.valid === false) {
      // Session is invalid - user has been kicked out
      stopSessionChecker();
      showKickedOutModal();
    }
  })
  .catch(error => {
    // Silently fail - network error or page being unloaded
    console.log('Session check error:', error);
  });
}

function showKickedOutModal() {
  // Check if modal exists
  const modal = document.getElementById('loginModal');
  
  if (modal) {
    // Ensure modal has proper styling
    modal.style.display = 'flex';
    modal.style.position = 'fixed';
    modal.style.zIndex = '10000';
    modal.style.left = '0';
    modal.style.top = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
    modal.style.backdropFilter = 'blur(4px)';
    modal.style.justifyContent = 'center';
    modal.style.alignItems = 'center';
    modal.style.opacity = '1';
    modal.style.pointerEvents = 'auto';
    
    // Ensure modal-content has proper styling
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
      modalContent.style.backgroundColor = '#fff';
      modalContent.style.borderRadius = '12px';
      modalContent.style.boxShadow = '0 10px 40px rgba(0, 0, 0, 0.3)';
      modalContent.style.width = '90%';
      modalContent.style.maxWidth = '350px';
      modalContent.style.height = 'auto';
      modalContent.style.minHeight = '320px';
      modalContent.style.padding = '0';
      modalContent.style.position = 'relative';
      modalContent.style.pointerEvents = 'auto';
      modalContent.style.margin = 'auto';
      modalContent.style.display = 'flex';
      modalContent.style.flexDirection = 'column';
    }
    
    // Update modal content
    const modalTitle = document.getElementById('modalTitle');
    const modalIcon = document.getElementById('modalIcon');
    const modalMessage = document.getElementById('modalMessage');
    const modalButton = document.getElementById('modalButton');
    
    if (modalTitle) modalTitle.textContent = 'Session Ended';
    if (modalIcon) modalIcon.className = 'fas fa-exclamation-circle';
    if (modalIcon) modalIcon.style.fontSize = '48px';
    if (modalIcon) modalIcon.style.color = '#dc2626';
    
    if (modalMessage) {
      modalMessage.textContent = 'Someone logged into your account from another location. Your session has been ended. Change your password immediately. Please login again.';
      modalMessage.style.color = '#555';
      modalMessage.style.fontSize = '14px';
      modalMessage.style.lineHeight = '1.6';
      modalMessage.style.marginBottom = '16px';
    }
    
    if (modalButton) {
      modalButton.textContent = 'OK';
      modalButton.style.backgroundColor = '#dc2626';
      modalButton.style.width = '100%';
      modalButton.style.padding = '12px 24px';
      modalButton.style.border = 'none';
      modalButton.style.borderRadius = '6px';
      modalButton.style.color = 'white';
      modalButton.style.fontSize = '14px';
      modalButton.style.fontWeight = '600';
      modalButton.style.cursor = 'pointer';
      modalButton.onclick = () => {
        window.location.href = '../login/login.php';
      };
    }
  } else {
    // Fallback: create simple alert if modal doesn't exist
    alert('Someone logged into your account from another location. Your session has been ended. Change your password immediately and login again.');
    window.location.href = '../login/login.php';
  }
}

// Start checking when page loads
window.addEventListener('load', startSessionChecker);

// Stop checking when page unloads
window.addEventListener('beforeunload', stopSessionChecker);

// Resume checking if page comes back from background
window.addEventListener('focus', () => {
  if (!sessionCheckInterval) {
    startSessionChecker();
  }
});

// Pause checking if page goes to background
window.addEventListener('blur', stopSessionChecker);

} // End of guard clause
