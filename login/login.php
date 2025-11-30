<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CCSearch Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="login.css">
</head>

<body>
  <div class="container">
    <div class="page-bg"></div>

    <!-- LEFT SIDE -->
    <div class="left-section">
      <div class="back-btn" onclick="window.location.href='/landing/landing.html'">
        <span>&larr;</span>
      </div>

      <div class="form-box">
        <img src="../image/Icon.png" alt="Logo" class="logo">
        <h2>Login to your CCSearch Account</h2>

        <!-- Login Form -->
        <form id="loginForm" action="login_auth.php" method="POST">
          <input type="text" name="studentID" id="studentId" placeholder="Student ID" required>

          <div class="password-field">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa-solid fa-eye toggle" onclick="togglePassword('password', this)"></i>
          </div>

          <button type="submit">Login</button>


          <p class="redirect-text">
            Don’t have an account?
            <a href="../register/register.html">Sign up</a>
          </p>
        </form>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-section">
      <div class="overlay"></div>
      <div class="quote">
        <p>Share credible knowledge.<br>Search reliable sources.</p>
      </div>
    </div>
  </div>

  <script>
    // 👁 Toggle password visibility
    function togglePassword(id, el) {
      const input = document.getElementById(id);
      if (input.type === "password") {
        input.type = "text";
        el.classList.remove("fa-eye");
        el.classList.add("fa-lock");
      } else {
        input.type = "password";
        el.classList.remove("fa-lock");
        el.classList.add("fa-eye");
      }
    }

    // Optional: client-side validation
    document.getElementById("loginForm").addEventListener("submit", (e) => {
      const studentId = document.getElementById("studentId").value.trim();
      const password = document.getElementById("password").value.trim();

      if (studentId === "" || password === "") {
        e.preventDefault();
        alert("Please fill in all fields.");
        return false;
      }
      // Form submits normally to login.php
    });


    // Clear input fields when page is loaded from Back/Forward cache
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        document.querySelectorAll("input").forEach(input => input.value = "");
      }
    });

  </script>
</body>

</html>