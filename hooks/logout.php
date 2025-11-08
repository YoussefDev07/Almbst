<?php
unset($_COOKIE["id"]); 
setcookie("id", "0", time() - 1, "/");
header("location:../index.php");
exit();