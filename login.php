<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="container" id="signIn">
    <h1 class="form-title">Sign In</h1>
    
    <!-- 🔁 FIXED: Correct action URL -->
    <form method="post" action="login_process.php">
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="email" id="email" placeholder="Email" required>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="password" placeholder="Password" required>
      </div>

      <div class="login-options">
        <label class="keep-login">
          <input type="checkbox" name="remember"> Keep me logged in
        </label>
        <a href="#" class="forgot">Forgot Password?</a>
      </div>

      <input type="submit" class="btn" value="Sign In" name="signIn">
    </form>

    <p class="or">----------or--------</p>
    <div class="icons">
      <i class="fab fa-google"></i>
      <i class="fab fa-facebook"></i>
    </div>
    <div class="links">
      <p>Don't have an account yet?</p>
      <a href="signup.php"><button>Sign Up</button></a>
    </div>
  </div>
</body>
</html>
