<?php
 require('inc/header.inc.php');
    require('inc/connection.inc.php');
    unset($_SESSION['username']);
    unset($_SESSION['id']);
    session_destroy();
    echo '<script>swal({

        showConfirmButton: false
      }, function(){
            window.location.href = "index.php";
      });</script>';
      die();
?>