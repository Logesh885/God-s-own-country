<?php
// Admin dashboard - updated with WA bookings link
session_start();
$config = require __DIR__ . '/../config.php';
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:1rem}ul{line-height:2}</style>
</head>
<body>
  <h1>Admin Dashboard</h1>
  <ul>
    <li><a href="packages.php">Manage Packages</a></li>
    <li><a href="bookings.php">View Bookings</a></li>
    <li><a href="enquiries.php">Customer Enquiries</a></li>
    <li><a href="gallery.php">Gallery</a></li>
    <li><a href="wa_bookings.php">WhatsApp Bookings (Analytics)</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</body>
</html>
