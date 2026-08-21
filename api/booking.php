<?php
// accept booking POST and store to bookings table
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try{
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $name = $_POST['name'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $travel_date = $_POST['travel_date'] ?? null;
  $people = intval($_POST['people'] ?? 1);
  $package_id = intval($_POST['package_id'] ?? 0);
  $requirements = $_POST['requirements'] ?? '';

  $stmt = $pdo->prepare('INSERT INTO bookings (name, phone, travel_date, people, package_id, requirements, created_at) VALUES (?,?,?,?,?,?,NOW())');
  $stmt->execute([$name,$phone,$travel_date,$people,$package_id,$requirements]);

  echo 'Booking submitted. We will contact you shortly.';
}catch(Exception $e){
  http_response_code(500); echo 'Failed to submit booking.';
}
