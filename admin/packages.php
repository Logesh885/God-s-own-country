<?php
// packages management (list & simple add form)
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if(empty($_SESSION[$config['admin_session_name']])){ header('Location: login.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = $_POST['title']; $short_desc = $_POST['short_desc']; $price = $_POST['price']; $stmt = $pdo->prepare('INSERT INTO packages (title, short_desc, price, created_at) VALUES (?,?,?,NOW())'); $stmt->execute([$title,$short_desc,$price]);
}
$pkgs = $pdo->query('SELECT * FROM packages ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Manage Packages</title></head><body>
  <h2>Packages</h2>
  <form method="post">
    <label>Title<input name="title"></label>
    <label>Short Desc<input name="short_desc"></label>
    <label>Price<input name="price"></label>
    <button>Add</button>
  </form>
  <ul>
    <?php foreach($pkgs as $p): ?>
      <li><?=htmlspecialchars($p['title'])?> — ₹<?=htmlspecialchars($p['price'])?></li>
    <?php endforeach; ?>
  </ul>
  <p><a href="dashboard.php">Back</a></p>
</body></html>
