<?php
 require('inc/header.inc.php');
    require('inc/connection.inc.php');
    unset($_SESSION['username']);
    unset($_SESSION['id']);
    unset($_SESSION['user_id']);
    unset($_SESSION['display_name']);
    session_destroy();
    echo '<script>swal({

        showConfirmButton: false
      }, function(){
            window.location.href = "index.php";
      });</script>';
      die();
?>