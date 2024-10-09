<?php

include 'config.php';

session_start();

if(isset($_POST['submit'])){

   // Sanitize and escape the user input
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);  // Using md5 for simplicity, but it's better to use stronger hashing methods like bcrypt

   // Query to check the user's credentials
   $select = "SELECT * FROM users WHERE email = '$email' AND password = '$pass'";

   $result = mysqli_query($conn, $select);

   if(mysqli_num_rows($result) > 0){

      $row = mysqli_fetch_array($result);

      // Redirect based on user type
      if($row['user_type'] == 'admin'){
         $_SESSION['admin_name'] = $row['name'];
         header('location:admin_dash.php');
      } elseif($row['user_type'] == 'user'){
         $_SESSION['user_name'] = $row['name'];
         header('location:order_management.php');
      }

   } else {
      $error[] = 'Incorrect email or password!';
   }

}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link rel="stylesheet" href="Loginstyle.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  </head>
  <body>
    <div class="wrapper">
      <h1>Login</h1>
      <form action="" method="POST">
        <div class="input-box">
          <input type="email" name="email" placeholder="Email" required />
          <i class="bx bxs-user"></i>
        </div>
        <div class="input-box">
          <input type="password" name="password" placeholder="Password" required />
          <i class="bx bxs-lock-alt"></i>
        </div>
        <button type="submit" name="submit" class="btn">Login</button>
        <?php
          if (isset($error)) {
            foreach ($error as $msg) {
              echo "<p class='error-msg'>$msg</p>";
            }
          }
        ?>
      </form>
    </div>
  </body>
</html>
