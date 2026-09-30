<?php
session_start();
$_SESSION['portal_user'] = null;
unset($_SESSION['portal_user']);
session_destroy();
header("Location: index.php");
exit;
