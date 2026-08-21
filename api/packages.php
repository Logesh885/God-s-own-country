<?php
// returns packages as JSON
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
try{
  $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $stmt = $pdo->query('SELECT id, title, short_desc, price FROM packages ORDER BY id DESC');
  $pkgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
  header('Content-Type: application/json');
  echo json_encode($pkgs);
}catch(Exception $e){
  http_response_code(500); echo json_encode([]);
}
