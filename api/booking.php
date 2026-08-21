<?php
// accept booking POST and store to bookings table, then send confirmation emails
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try{
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $travel_date = $_POST['travel_date'] ?? null;
  $people = intval($_POST['people'] ?? 1);
  $package_id = intval($_POST['package_id'] ?? 0);
  $requirements = $_POST['requirements'] ?? '';

  $stmt = $pdo->prepare('INSERT INTO bookings (name, email, phone, travel_date, people, package_id, requirements, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
  $stmt->execute([$name,$email,$phone,$travel_date,$people,$package_id,$requirements]);

  // fetch package title for email
  $pkgTitle = '';
  if($package_id){
    $ps = $pdo->prepare('SELECT title FROM packages WHERE id = ? LIMIT 1'); $ps->execute([$package_id]); $pv = $ps->fetch(PDO::FETCH_ASSOC); $pkgTitle = $pv['title'] ?? '';
  }

  // send emails (non-blocking-ish)
  require_once __DIR__ . '/../includes/mail.php';
  $booking = ['name'=>$name,'email'=>$email,'phone'=>$phone,'travel_date'=>$travel_date,'people'=>$people,'package_title'=>$pkgTitle,'package_id'=>$package_id,'requirements'=>$requirements];
  // attempt send but don't block user response
  try{ send_booking_emails($booking, $config); }catch(Exception $e){ /* ignore */ }

  echo 'Booking submitted. We will contact you shortly.';
}catch(Exception $e){
  http_response_code(500); echo 'Failed to submit booking.';
}
