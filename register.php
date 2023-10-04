<?php
    require('inc/header.inc.php');
    require('inc/connection.inc.php');

if(isset($_POST['submit']))
{
$username=$_POST['username'];
$email=$_POST['email']; 
$phone=$_POST['phone'];
$password=md5($_POST['password']); 
$sql="INSERT INTO users(username,email,phone,password) VALUES('$username','$email','$phone','$password')";
$chekMail="SELECT email FROM users WHERE email='$email'";
$res=mysqli_query($con,$chekMail);

$res2=mysqli_query($con,$sql);

header('Location:register.php');
}

?>

<script>
  if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}
function validate()
{
 var error="";
 var name = document.getElementById( "username" );


 var email = document.getElementById( "email" );


 var phone = document.getElementById( "phone" );


 var password = document.getElementById( "password" );


 else
 {
  return true;
  alert('Registration successfull. Now you can login');
 }
}

function resetForm(){
document.getElementById("form").reset();
}
</script>

<!--Register form-->
<section class="register">
<div class="container">
<form id="form" method="POST" onsubmit="return validate();">
  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Name</label>
    <input type="text" class="form-control" name="username" id="username" placeholder="Enter your name">
  
  </div>

  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Email address</label>
    <input type="text" class="form-control" name="email"  id="email" placeholder="Enter your email">
    
  </div>

  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Phone</label>
    <input type="text" class="form-control" name="phone"  id="phone" placeholder="Enter your phone number">
   
  </div>

  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Password</label>
    <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password">
   
  </div>




  <div class="form-group col-lg-6">
    <button type="submit" name="submit"  class="btn btn-success">Register</button>
  </div>

  <div class="container-fluid">
 <p class="text-secondary">Already Registered <a href="login.php">Login</a></p>
 </div>

</form>
<div class="error" id="error"></div>
</div>
</section>


<?php
    require('inc/footer.inc.php');
?>