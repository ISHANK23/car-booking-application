<?php
    require('inc/header.inc.php');
    require('inc/connection.inc.php');
//session_unset();
if(isset($_POST['login']))
{
$username=$_POST['username'];
$password=md5($_POST['password']); 

if(empty($username) || empty($password)){
  echo "<script>
  Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: 'Please enter username and password',
  })
    </script>";
}else{
$chekMail="SELECT * FROM users WHERE email='$username' AND password='$password'";
$res=mysqli_query($con,$chekMail);
if($row=mysqli_num_rows($res)>0){

  header('Location:my_account.php');

 //header('Location:register.php');
}
}
//header('Location:register.php');
}

?>
<script>
  if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}
</script>
<!--Login form-->
<section class="login">
<div class="container">
<form method="POST">
  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Email address</label>
    <input type="text" class="form-control" name="username" id="exampleFormControlInput1" placeholder="Enter your email">
  </div>


  <div class="form-group col-lg-6">
    <label for="exampleFormControlInput1">Password</label>
    <input type="password" class="form-control"  name="password" id="exampleFormControlInput1" placeholder="Enter your password">
  </div>  

  <div class="form-group col-lg-6">
    <button type="submit" name="login" class="btn btn-primary">Login</button>
  </div>

 <div class="container-fluid">
 <p class="text-secondary">Not registered? <a href="register.php">Create Account</a></p>
 </div>

</form>
</div>
</section>


<?php
    require('inc/footer.inc.php');
?>