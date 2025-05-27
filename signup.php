<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="container" id="signup">
    <h1 class="form-title">Sign Up</h1>
    <form id="register-form">
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="fName" placeholder="First Name" required>
      </div>
      <div class="input-group">
        <i class="fas fa-user"></i>
        <input type="text" name="lName" placeholder="Last Name" required>
      </div>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" required>
      </div>

      <input type="submit" class="btn" value="Sign Up" name="signUp">
    </form>

    <p class="or">----------or--------</p>
    <div class="icons">
      <i class="fab fa-google"></i>
      <i class="fab fa-facebook"></i>
    </div>

    <div class="links">
      <p>Already have an account?</p>
      <a href="login.php"><button>Sign In</button></a>
    </div>
  </div>

  <!-- Success Modal -->
  <div id="success-modal" style="display:none; position: fixed; top: 20%; left: 50%; transform: translate(-50%, -20%); background: white; padding: 20px; border: 2px solid #4caf50; box-shadow: 0 0 10px rgba(0,0,0,0.2); z-index: 1000; border-radius: 8px;">
    <p>🎉 Registration successful! Please check your email to verify your account.</p>
    <button id="close-modal" style="margin-top:10px;">OK</button>
  </div>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- AJAX Submit Script -->
  <script>
    $('#register-form').on('submit', function(e) {
      e.preventDefault();

      $.post('register.php', $(this).serialize(), function(response) {
        if (response.status === 'success') {
          $('#success-modal').fadeIn();
        } else {
          alert(response.message || 'Registration failed.');
        }
      }, 'json');
    });

    $('#close-modal').on('click', function() {
      $('#success-modal').fadeOut();
    });
  </script>
</body>
</html>

