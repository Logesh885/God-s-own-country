<?php
// api/wa_booking.php
// Records analytics for WhatsApp-sent bookings and includes simple rate limiting
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";

try{
    // Only accept POST with JSON
    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        http_response_code(405);
        echo json_encode(['success'=>false,'error'=>'method_not_allowed']);
        exit;
    }
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if(!is_array($data)){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'invalid_payload']);
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Basic rate limiting by IP using DB counts
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

    // Count in last 1 minute
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM wa_bookings WHERE ip = ? AND created_at >= (NOW() - INTERVAL 1 MINUTE)');
    $stmt->execute([$ip]);
    $cnt1 = (int)$stmt->fetchColumn();
    if($cnt1 >= 5){
        http_response_code(429);
        echo json_encode(['success'=>false,'error'=>'rate_limited']);
        exit;
    }
    // Count in last 24 hours
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM wa_bookings WHERE ip = ? AND created_at >= (NOW() - INTERVAL 1 DAY)');
    $stmt->execute([$ip]);
    $cnt24 = (int)$stmt->fetchColumn();
    if($cnt24 >= 1000){
        http_response_code(429);
        echo json_encode(['success'=>false,'error'=>'daily_rate_limited']);
        exit;
    }

    // Simple content checks
    $name = substr(trim($data['name'] ?? ''),0,200);
    $phone = substr(trim($data['phone'] ?? ''),0,50);
    $travel_date = $data['travel_date'] ?? null;
    $people = intval($data['people'] ?? 0);
    $package_id = intval($data['package_id'] ?? 0);
    $package_name = substr(trim($data['package_name'] ?? ''),0,255);
    $requirements = substr(trim($data['requirements'] ?? ''),0,2000);
    $source = substr(trim($data['source'] ?? 'wa_button'),0,100);

    // Basic anti-spam heuristics: require either phone or name or package
    if($phone === '' && $name === '' && $package_name === ''){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'insufficient_data']);
        exit;
    }

    // Validate phone - simple digits check (allow + and spaces)
    $phoneDigits = preg_replace('/[^0-9]/','',$phone);
    if($phone !== '' && strlen($phoneDigits) < 7){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'invalid_phone']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO wa_bookings (name, phone, travel_date, people, package_id, package_name, requirements, source, user_agent, ip, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$name, $phone, $travel_date, $people, $package_id, $package_name, $requirements, $source, $user_agent, $ip]);

    echo json_encode(['success'=>true]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'server_error']);
}
