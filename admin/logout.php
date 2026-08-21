<?php
// logout
session_start(); $config = require __DIR__ . '/../config.php'; unset($_SESSION[$config['admin_session_name']]); header('Location: login.php');
