<?php
// Admin login
session_start();
$config = require __DIR__ . '/../config.php';
$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}";
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

if(isset($_POST['username'])){
  $username = $_POST['username'];
  $password = $_POST['password'];
  $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
  $stmt->execute([$username]);
  $admin = $stmt->fetch(PDO::FETCH_ASSOC);
  if($admin && password_verify($password, $admin['password_hash'])){
    $_SESSION[$config['admin_session_name']] = $admin['id'];
    header('Location: dashboard.php'); exit;
  }
  $error = 'Invalid credentials';
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Admin Login</title></head><body>
  <h2>Admin Login</h2>
  <?php if(!empty($error)) echo "<p style='color:red'>".htmlspecialchars($error)."</p>";?>
  <form method="post">
    <label>Username<input name="username"></label>
    <label>Password<input type="password" name="password"></label>
    <button>Login</button>
  </form>
</body></html>
