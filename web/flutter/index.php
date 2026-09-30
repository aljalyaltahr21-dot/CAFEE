<?php
session_start();
if (!isset($_SESSION['portal_user']) || empty($_SESSION['portal_user'])) {
    header("Location: ../index.php?error=unauthorized");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <base href="./">
  <meta charset="UTF-8">
  <meta content="IE=Edge" http-equiv="X-UA-Compatible">
  <meta name="description" content="نظام كاشير فلاتر السحابي للمقاهي">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="apple-mobile-web-app-title" content="CafePOS">
  <link rel="apple-touch-icon" href="icons/Icon-192.png">
  <link rel="icon" type="image/png" href="favicon.png"/>
  <title>نظام الكاشير السحابي | CafePOS Flutter</title>
  <link rel="manifest" href="manifest.json">
</head>
<body>
  <script src="flutter_bootstrap.js" async></script>
</body>
</html>
