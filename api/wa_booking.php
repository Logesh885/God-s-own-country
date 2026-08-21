<?php
// Store WhatsApp-sent booking analytics
// Expects JSON POST with fields: name, phone, travel_date, people, package_id, package_name, requirements, source
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if(!is_array($data)){
        http_response_code(400);
        echo json_encode(['success'=>false,'error'=>'invalid_payload']);
        exit;
    }

    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $travel_date = $data['travel_date'] ?? null;
    $people = intval($data['people'] ?? 0);
    $package_id = intval($data['package_id'] ?? 0);
    $package_name = trim($data['package_name'] ?? '');
    $requirements = trim($data['requirements'] ?? '');
    $source = trim($data['source'] ?? 'wa_button');

    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('INSERT INTO wa_bookings (name, phone, travel_date, people, package_id, package_name, requirements, source, user_agent, ip, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$name, $phone, $travel_date, $people, $package_id, $package_name, $requirements, $source, $user_agent, $ip]);

    echo json_encode(['success'=>true]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>'server_error']);
}
