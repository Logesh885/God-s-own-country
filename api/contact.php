<?php
// simple contact/enquiry endpoint
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try{
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $message = $_POST['message'] ?? '';
  $stmt = $pdo->prepare('INSERT INTO enquiries (name, email, phone, message, created_at) VALUES (?,?,?,?,NOW())');
  $stmt->execute([$name,$email,$phone,$message]);
  echo 'Enquiry submitted. Thank you.';
}catch(Exception $e){
  http_response_code(500); echo 'Failed to submit enquiry.';
}
