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
    <form method="post" action="register.php">
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

     <!-- <div class="captcha-box"> 
        <label for="captcha">Enter CAPTCHA:</label>
        <input type="text" id="captcha" name="captcha" placeholder="Type the text shown" required>
        <div class="captcha-img">
          <img src="generate-captcha.php" alt="CAPTCHA Image">
          <button type="button" onclick="reloadCaptcha()">↻</button>
        </div>
      </div>-->

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

   <script>
  //  function reloadCaptcha() {
   //   const img = document.querySelector('.captcha-img img');
    //  img.src = 'generate-captcha.php?' + Date.now();
   // }
 </script>
</body>
</html>
