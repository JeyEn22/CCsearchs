<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCSearch Registration</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="register.css">
</head>
<body>
  <div class="container">
    <div class="page-bg"></div>

    <!-- LEFT SECTION -->
    <div class="left-section">
      <div class="back-btn" onclick="window.location.href='/login/login.html'">
        <span>&larr;</span>
      </div>

      <div class="form-box">
        <img src="../image/Icon.png" alt="Logo" class="logo">
        <h2>Create your CCSearch Account</h2>

        <!-- FIXED FORM -->
        <form id="registerForm" action="registration.php" method="POST">
          <input type="email" name="emailAddress" id="emailAddress" placeholder="Email" required>
          <input type="text" name="studentID" id="studentID" placeholder="Student ID" required>

          <div class="password-field">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle" onclick="togglePassword('password', this)"></i>
          </div>

          <div class="password-field">
            <input type="password" name="confirmPassword" id="confirm" placeholder="Confirm Password" required>
            <i class="fa-solid fa-eye toggle" onclick="togglePassword('confirm', this)"></i>
          </div>

          <button type="submit">Register</button>

          <p class="redirect-text">
            Already have an account? <a href="../login/login.html">Login</a>
          </p>
        </form>
      </div>
    </div>

    <!-- RIGHT SECTION -->
    <div class="right-section">
      <div class="overlay"></div>
      <div class="quote">
        <h3>Join CCSearch today<br>and share credible knowledge.</h3>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    function togglePassword(id, el) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        el.classList.replace("fa-eye", "fa-lock");
      } else {
        input.type = "password";
        el.classList.replace("fa-lock", "fa-eye");
      }
    }

    // Client-side password match check (optional)
    document.getElementById("registerForm").addEventListener("submit", (e) => {
      const pass = document.getElementById("password").value.trim();
      const confirm = document.getElementById("confirm").value.trim();

      if (pass !== confirm) {
        e.preventDefault();
        alert("❌ Passwords do not match!");
        return false;
      }
    });
  </script>
</body>
</html>
