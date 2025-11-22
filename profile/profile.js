// Tab switch
const tabs = document.querySelectorAll(".tab");
const contents = document.querySelectorAll(".tab-content");

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    tabs.forEach(t => t.classList.remove("active"));
    tab.classList.add("active");

    contents.forEach(c => c.classList.add("hidden"));
    document.getElementById(tab.dataset.tab).classList.remove("hidden");
  });
});

// Button action
document.getElementById("viewPublic").addEventListener("click", () => {
  alert("Viewing as public profile!");
});

// Profile image upload preview (single, fixed version)
const fileInput = document.getElementById("fileInput");
const profilePic = document.getElementById("profilePic");

if (fileInput && profilePic) {
  fileInput.addEventListener("change", (event) => {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        profilePic.src = e.target.result; // changes image instantly
        profilePic.style.animation = "fadeIn 0.6s ease"; // adds smooth fade
      };
      reader.readAsDataURL(file);
    }
  });
}

// Simple fade animation for tab switch
contents.forEach(content => {
  content.style.transition = "opacity 0.3s ease";
});

tabs.forEach(tab => {
  tab.addEventListener("click", () => {
    contents.forEach(c => {
      c.style.opacity = 0;
      setTimeout(() => { c.style.opacity = 1; }, 200);
    });
  });

  const logoutBtn = document.getElementById("logoutBtn");
if (logoutBtn) {
  logoutBtn.addEventListener("click", (e) => {
    e.preventDefault();
    alert("You have been logged out!");
    window.location.href = "file:///C:/Users/John%20Nathaniel%20Batas/Downloads/CCsearch/login/login.html"; // 👈 change this if needed
  });
}
});
