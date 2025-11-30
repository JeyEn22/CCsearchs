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

// Button action
document.getElementById("viewPublic")?.addEventListener("click", () => {
  alert("Viewing as public profile!");
});

// Profile image upload preview
const fileInput = document.getElementById("fileInput");
const profilePic = document.getElementById("profilePic");

if (fileInput && profilePic) {
  fileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        profilePic.src = e.target.result;
        profilePic.style.animation = "fadeIn 0.6s ease";
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
